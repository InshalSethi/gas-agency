<?php
require 'config/db.php';

// Get search query if available
// var_dump($_REQUEST);die();
$draw = $_REQUEST['draw'];

$row = $_REQUEST['start'];

$rowperpage = $_REQUEST['length']; // Rows display per page

$columnIndex = $_REQUEST['order'][0]['column']; // Column index

$columnName = $_REQUEST['columns'][$columnIndex]['data']; // Column name

$columnSortOrder = $_REQUEST['order'][0]['dir']; // asc or desc

$searchQuery = $_REQUEST['search']['value'];

$start_date = date("Y-m-d", strtotime($_REQUEST['start_date']));
$end_date = date("Y-m-d", strtotime($_REQUEST['end_date']));

$cylinderFilter = $_REQUEST['cylinder_filter'];
$paymentFilter = $_REQUEST['payment_filter'];

$totalRecords = 0;
$cols = array(
    "inv.id", "inv.customer_id",
    "inv.empty_cylinders",
    "inv.date",
    "inv.grand_total",
    "inv.received",
    "inv.balance",
    "inv.created_at",
    "cus.name as customer"
);
$db->join("customers cus", "inv.customer_id=cus.id", "LEFT");

$db->where("cus.deleted_at", NULL, 'IS');
$db->where("inv.deleted_at", NULL, 'IS');

if (!empty($_REQUEST['start_date']) && !empty($_REQUEST['end_date'])) {
    $db->where("inv.date", Array($start_date, $end_date), 'BETWEEN');
}
if (!empty($paymentFilter)) {
    if ($paymentFilter === 'payment_pending') {
        $db->where("inv.balance", '0', '>');
    }
    if ($paymentFilter === 'payment_received') {
        $db->where("inv.balance", '0', '<=');
    }
}
if (!empty($searchQuery)) {
    $db->where("cus.name", '%' . $searchQuery . '%', 'like');
    $db->orWhere("inv.date", '%' . $searchQuery . '%', 'like');
    $db->orWhere("inv.grand_total", '%' . $searchQuery . '%', 'like');
    $db->orWhere("inv.received", '%' . $searchQuery . '%', 'like');
    $db->orWhere("inv.balance", '%' . $searchQuery . '%', 'like');
}


$db->orderBy("inv.id", "desc");
$invoices = $db->get('invoices inv', Array($row, $rowperpage), $cols);
$totalRecords = $db->count;

// Initialize an array to hold the output data
$data = [];

// Fetch rows from the result set
foreach ($invoices as $invoice) {
    $totalInvCylinders = 0;
    $totalEmptyCylinders = 0;
    $row_class = '';
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

    $query = "SELECT 
            c.id as customer_id, c.name as customer_name,
            GROUP_CONCAT(CONCAT(cy.name, '(', ii.empty_qty, ')') ORDER BY cy.name SEPARATOR ', ') AS empty_stock,
            GROUP_CONCAT(CONCAT(cy.name, '(', ii.qty, ')') ORDER BY cy.name SEPARATOR ', ') AS qty_stock
          FROM 
            customers c
          JOIN 
            invoices i ON c.id = i.customer_id
          JOIN 
            invoice_items ii ON i.id = ii.invoice_id
          JOIN 
            cylinders cy ON cy.id = ii.product_id
          WHERE 
            c.id = ? and i.id = ?
          GROUP BY 
            c.id";

    // Use rawQueryOne to execute the query with the customer_id parameter
    $getStockDetails = $db->rawQueryOne($query, [$invoice['customer_id'],$invoice['id']]);
    $purStock = '';
    $empStock = '';
    if(!empty($getStockDetails))
        $purStock = $getStockDetails['qty_stock'];
    if(!empty($getStockDetails))
        $empStock = $getStockDetails['empty_stock'];

    $pushData = [
        'id' => $invoice['id'],
        'customer' => '<a href="views/customer-ledger.php?id=' . $invoice['customer_id'] . '" class="text-primary"><b>' . $invoice['customer'] . '</b></a>',
        'empty_cylinders' => $empStock,
        'total_cylinders' => $purStock,
        'date' => date("d-m-Y", strtotime($invoice['date'])),
        'grand_total' => $invoice['grand_total'],
        'received' => $invoice['received'],
        'balance' => $invoice['balance'],
        'created_at' => date("d-m-Y h:i:s", strtotime($invoice['created_at'])),
        'row_class' => $row_class,
        'actions' => '<a href="views/edit-invoice.php?id=' . $invoice['id'] . '" class="btn btn-warning btn-sm">Edit</a>'
    ];

    if (!empty($cylinderFilter)) {
        if ($cylinderFilter === 'empty_pending' && $totalInvCylinders > $totalEmptyCylinders) {
            $data[] = $pushData;
        } elseif ($cylinderFilter === 'empty_received' && $totalInvCylinders <= $totalEmptyCylinders) {
            $data[] = $pushData;
        }
    } else {
        $data[] = $pushData;
    }
}

// Output the data in JSON format
$response = array(
    "draw" => intval($draw),
    "iTotalRecords" => $totalRecords,
    "iTotalDisplayRecords" => $totalRecords,
    "data" => $data,
);

echo json_encode($response);
?>
