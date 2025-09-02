<?php
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
$vendor_id = $_REQUEST['vendor_id'];
$start_date = isset($_REQUEST['start_date']) ? $_REQUEST['start_date'] : '';
$end_date = isset($_REQUEST['end_date']) ? $_REQUEST['end_date'] : '';

$totalRecords = 0;
$cols = array(
    "inv.id", "inv.vendor_id", "inv.date",
    "inv.grand_total", "inv.paid", "inv.balance", "inv.created_at",
    "ven.name as vendor"
);

$db->join("vendors ven", "inv.vendor_id=ven.id", "LEFT");
$db->where("inv.vendor_id", $vendor_id);
$db->where("ven.deleted_at", NULL, 'IS');
$db->where("inv.deleted_at", NULL, 'IS');

if (!empty($searchQuery)) {
    $db->where("(ven.name LIKE '%{$searchQuery}%' OR
                inv.date LIKE '%{$searchQuery}%' OR
                inv.grand_total LIKE '%{$searchQuery}%' OR
                inv.paid LIKE '%{$searchQuery}%' OR
                inv.balance LIKE '%{$searchQuery}%')");
}

// Add date range filter
if (!empty($start_date) && !empty($end_date)) {
    $start_date_formatted = date('Y-m-d', strtotime($start_date));
    $end_date_formatted = date('Y-m-d', strtotime($end_date));
    $db->where("inv.date", array($start_date_formatted, $end_date_formatted), 'BETWEEN');
}

// Apply ordering
$orderColumn = 'inv.id';
switch($columnName) {
    case 'id':
        $orderColumn = 'inv.id';
        break;
    case 'vendor':
        $orderColumn = 'ven.name';
        break;
    case 'date':
        $orderColumn = 'inv.date';
        break;
    case 'grand_total':
        $orderColumn = 'inv.grand_total';
        break;
    case 'paid':
        $orderColumn = 'inv.paid';
        break;
    case 'balance':
        $orderColumn = 'inv.balance';
        break;
    case 'created_at':
        $orderColumn = 'inv.created_at';
        break;
}

// Add date range filter for main query
if (!empty($start_date) && !empty($end_date)) {
    $start_date_formatted = date('Y-m-d', strtotime($start_date));
    $end_date_formatted = date('Y-m-d', strtotime($end_date));
    $db->where("inv.date", array($start_date_formatted, $end_date_formatted), 'BETWEEN');
}

$db->orderBy($orderColumn, $columnSortOrder);
$invoices = $db->get('purchase_invoices inv', Array($row, $rowperpage), $cols);

// Get total count for pagination
$db->join("vendors ven", "inv.vendor_id=ven.id", "LEFT");
$db->where("inv.vendor_id", $vendor_id);
$db->where("ven.deleted_at", NULL, 'IS');
$db->where("inv.deleted_at", NULL, 'IS');

if (!empty($searchQuery)) {
    $db->where("(ven.name LIKE '%{$searchQuery}%' OR
                inv.date LIKE '%{$searchQuery}%' OR
                inv.grand_total LIKE '%{$searchQuery}%' OR
                inv.paid LIKE '%{$searchQuery}%' OR
                inv.balance LIKE '%{$searchQuery}%')");
}

// Add date range filter for count query
if (!empty($start_date) && !empty($end_date)) {
    $start_date_formatted = date('Y-m-d', strtotime($start_date));
    $end_date_formatted = date('Y-m-d', strtotime($end_date));
    $db->where("inv.date", array($start_date_formatted, $end_date_formatted), 'BETWEEN');
}

$totalRecords = $db->getValue('purchase_invoices inv', 'COUNT(*)');

// Initialize an array to hold the output data
$data = [];

// Process each invoice
foreach ($invoices as $invoice) {
    $totalInvCylinders = 0;
    $totalEmptyCylinders = 0;
    $row_class = '';
    
    // Get invoice items
    $db->where("purchase_invoice_id", $invoice['id']);
    $invoiceItems = $db->get("purchase_invoice_items");
    foreach ($invoiceItems as $invoiceItem) {
        $totalInvCylinders += $invoiceItem['qty'];
        $totalEmptyCylinders += $invoiceItem['empty_qty'];
    }
    
    // Determine row class based on balance
    if ($invoice['balance'] > 0) {
        $row_class = 'bg-warning';
    }
    
    // Get detailed cylinder information
    $query = "SELECT 
            v.id as vendor_id, v.name as vendor_name,
            GROUP_CONCAT(CONCAT(cy.name, '(', ii.empty_qty, ')') ORDER BY cy.name SEPARATOR ', ') AS empty_stock,
            GROUP_CONCAT(CONCAT(cy.name, '(', ii.qty, ')') ORDER BY cy.name SEPARATOR ', ') AS qty_stock
          FROM 
            vendors v
          JOIN 
            purchase_invoices i ON v.id = i.vendor_id
          JOIN 
            purchase_invoice_items ii ON i.id = ii.purchase_invoice_id
          JOIN 
            cylinders cy ON cy.id = ii.product_id
          WHERE 
            v.id = ? and i.id = ?
          GROUP BY 
            v.id";

    $getStockDetails = $db->rawQueryOne($query, [$invoice['vendor_id'], $invoice['id']]);
    $purStock = '';
    $empStock = '';
    if(!empty($getStockDetails)) {
        $purStock = $getStockDetails['qty_stock'];
        $empStock = $getStockDetails['empty_stock'];
    }

    $data[] = [
        'id' => $invoice['id'],
        'vendor' => '<a href="vendor-ledger.php?id=' . $invoice['vendor_id'] . '" class="text-primary"><b>' . $invoice['vendor'] . '</b></a>',
        'empty_cylinders' => $empStock,
        'total_cylinders' => $purStock,
        'date' => date("d-m-Y", strtotime($invoice['date'])),
        'grand_total' => $invoice['grand_total'],
        'paid' => $invoice['paid'],
        'balance' => $invoice['balance'],
        'created_at' => date("d-m-Y h:i:s", strtotime($invoice['created_at'])),
        'row_class' => $row_class,
        'actions' => '<a href="edit-purchase-invoice.php?id=' . $invoice['id'] . '" class="btn btn-warning btn-sm">Edit</a>'
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
