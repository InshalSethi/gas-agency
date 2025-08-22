<?php
// File: get_invoices.php

require '../config/db.php';
require '../config/db_functions.php';

// Get search query and pagination parameters
$draw = $_REQUEST['draw'];
$row = $_REQUEST['start'];
$rowperpage = $_REQUEST['length'];
$columnIndex = $_REQUEST['order'][0]['column'];
$columnName = $_REQUEST['columns'][$columnIndex]['data'];
$columnSortOrder = $_REQUEST['order'][0]['dir'];
$searchQuery = $_REQUEST['search']['value'];
$customer_id = $_REQUEST['customer_id'];

// Initialize database connection

$totalRecords = 0;
$cols = array(
    "inv.id", "inv.customer_id", "inv.empty_cylinders", "inv.date",
    "inv.grand_total", "inv.received", "inv.balance", "inv.created_at",
    "cus.name as customer"
);

$db->join("customers cus", "inv.customer_id=cus.id", "LEFT");

if (!empty($customer_id)) {
    $db->where("inv.customer_id", $customer_id);
}

if (!empty($searchQuery)) {
    $db->where("cus.name", '%' . $searchQuery . '%', 'like');
    $db->orWhere("inv.date", '%' . $searchQuery . '%', 'like');
    $db->orWhere("inv.grand_total", '%' . $searchQuery . '%', 'like');
    $db->orWhere("inv.received", '%' . $searchQuery . '%', 'like');
    $db->orWhere("inv.balance", '%' . $searchQuery . '%', 'like');
}

$db->where("inv.deleted_at", NULL, 'IS');
$db->orderBy("inv.id", "desc");
$invoices = $db->get('invoices inv', Array($row, $rowperpage), $cols);
$totalRecords = $db->count;

// Get bonus stock details for the customer
$getBonusStockDetails = getCustomerBonus($db, $customer_id);

// Parse the bonus stock details
$bonusStock = [];
if (!empty($getBonusStockDetails['bonus_stock'])) {
    $bonusStockArray = explode(',', $getBonusStockDetails['bonus_stock']);
    foreach ($bonusStockArray as $item) {
        preg_match('/(\w+)\((\d+)\)/', trim($item), $matches);
        if (isset($matches[1]) && isset($matches[2])) {
            $bonusStock[$matches[1]] = (int)$matches[2];
        }
    }
}

// Initialize an array to hold the output data
$data = [];

// Process each invoice
foreach ($invoices as $invoice) {
    $totalInvCylinders = 0;
    $totalEmptyCylinders = 0;
    $row_class = '';

    // Get invoice items
    $db->where("invoice_id", $invoice['id']);
    $invoiceItems = $db->get("invoice_items");
    
    foreach ($invoiceItems as $invoiceItem) {
        $totalInvCylinders += $invoiceItem['qty'];
        $totalEmptyCylinders += $invoiceItem['empty_qty'];
    }

    // Determine row class based on balance and cylinder counts
    if ($invoice['balance'] > 0) {
        $row_class = 'bg-warning';
    }
    if ($totalInvCylinders > $totalEmptyCylinders) {
        $row_class = 'bg-red';
    }
    if ($invoice['balance'] > 0 && $totalInvCylinders > $totalEmptyCylinders) {
        $row_class = 'bg-info';
    }

    // Get detailed stock information for the invoice
    $query = "SELECT 
        customer_id, 
        customer_name,
        GROUP_CONCAT(CONCAT(product_name, '(', empty_qty, ')') ORDER BY product_name SEPARATOR ', ') AS empty_stock,
        GROUP_CONCAT(CONCAT(product_name, '(', qty, ')') ORDER BY product_name SEPARATOR ', ') AS qty_stock
    FROM (
        SELECT 
            c.id AS customer_id, 
            c.name AS customer_name, 
            cy.name as product_name,
            SUM(ii.empty_qty) AS empty_qty,
            SUM(ii.qty) AS qty
        FROM 
            customers c
        JOIN 
            invoices i ON c.id = i.customer_id
        JOIN 
            invoice_items ii ON i.id = ii.invoice_id
        JOIN 
            cylinders cy ON cy.id = ii.product_id
        WHERE 
            c.id = ? AND i.id = ? AND i.deleted_at IS NULL
        GROUP BY 
            c.id, c.name, cy.name
    ) AS subquery
    GROUP BY 
        customer_id, customer_name;";

    $getStockDetails = $db->rawQueryOne($query, [$invoice['customer_id'], $invoice['id']]);
    
    $purStock = [];
    $empStock = [];

    // Parse purchased stock
    if (!empty($getStockDetails['qty_stock'])) {
        $qtyStockArray = explode(',', $getStockDetails['qty_stock']);
        foreach ($qtyStockArray as $item) {
            preg_match('/(.+?)\((\d+)\)/', trim($item), $matches);
            if (isset($matches[1]) && isset($matches[2])) {
                $purStock[$matches[1]] = (int)$matches[2];
            }
        }
    }

    // Parse empty stock
    if (!empty($getStockDetails['empty_stock'])) {
        $emptyStockArray = explode(',', $getStockDetails['empty_stock']);
        foreach ($emptyStockArray as $item) {
            preg_match('/(.+?)\((\d+)\)/', trim($item), $matches);
            if (isset($matches[1]) && isset($matches[2])) {
                $empStock[$matches[1]] = (int)$matches[2];
            }
        }
    }

    // Calculate pending cylinders
    $pendingCylinders = [];
    $bonusStockCopy = $bonusStock; // Create a copy to avoid modifying the original
    foreach ($purStock as $product => $qty) {
        $empQty = isset($empStock[$product]) ? $empStock[$product] : 0;
        $bonusQty = isset($bonusStockCopy[$product]) ? $bonusStockCopy[$product] : 0;
        $pendingQty = $qty - $empQty;
        
        // If there's bonus stock, use it to reduce the pending quantity
        if ($pendingQty > 0 && $bonusQty > 0) {
            $usedBonus = min($pendingQty, $bonusQty);
            $pendingQty -= $usedBonus;
            $bonusStockCopy[$product] -= $usedBonus; // Update remaining bonus stock
        }
        
        if ($pendingQty > 0) {
            $pendingCylinders[$product] = $pendingQty;
        }
    }

    // Prepare data for output
    $data[] = [
        'id' => $invoice['id'],
        'customer' => $invoice['customer'],
        'empty_cylinders' => implode(', ', array_map(function ($product, $qty) {
            return "$product($qty)";
        }, array_keys($empStock), $empStock)),
        'total_cylinders' => implode(', ', array_map(function ($product, $qty) {
            return "$product($qty)";
        }, array_keys($purStock), $purStock)),
        'pending_cylinders' => implode(', ', array_map(function ($product, $qty) {
            return "$product($qty)";
        }, array_keys($pendingCylinders), $pendingCylinders)),
        'date' => date("d-m-Y", strtotime($invoice['date'])),
        'grand_total' => $invoice['grand_total'],
        'received' => $invoice['received'],
        'balance' => $invoice['balance'],
        'created_at' => date("d-m-Y h:i:s", strtotime($invoice['created_at'])),
        'row_class' => $row_class,
        'actions' => '<a href="edit-invoice.php?id=' . $invoice['id'] . '" class="btn btn-warning btn-sm">Edit</a>'
    ];
}

// Prepare the response for DataTables
$response = array(
    "draw" => intval($draw),
    "iTotalRecords" => $totalRecords,
    "iTotalDisplayRecords" => $totalRecords,
    "data" => $data
);

// Output the data in JSON format
header('Content-Type: application/json');
echo json_encode($response);
?>