<?php
require '../config/db.php';
require_once '../config/db_functions.php';
require_once '../config/auth.php';

if (!checkPermission('purchase_invoices_view')) {
    echo json_encode(["draw" => 0, "iTotalRecords" => 0, "iTotalDisplayRecords" => 0, "data" => []]);
    exit();
}

$draw = $_REQUEST['draw'];
$row = $_REQUEST['start'];
$rowperpage = $_REQUEST['length'];
$columnIndex = $_REQUEST['order'][0]['column'];
$columnName = $_REQUEST['columns'][$columnIndex]['data'];
$columnSortOrder = $_REQUEST['order'][0]['dir'];
$searchQuery = $_REQUEST['search']['value'];

$cols = array(
    "inv.id", "inv.vendor_id", "inv.date", "inv.grand_total", "inv.paid",
    "inv.balance", "inv.created_at", "ven.name as vendor"
);

// Total Records
$totalRecords = $db->where("deleted_at", NULL, "IS")->getValue("purchase_invoices", "count(*)");

// Reset for filtered query
$db->join("vendors ven", "inv.vendor_id=ven.id", "LEFT");
$db->where("inv.deleted_at", NULL, 'IS');

if (!empty($searchQuery)) {
    $db->where("(ven.name LIKE ? OR inv.date LIKE ? OR inv.grand_total LIKE ? OR inv.paid LIKE ? OR inv.balance LIKE ?)", 
        array('%'.$searchQuery.'%', '%'.$searchQuery.'%', '%'.$searchQuery.'%', '%'.$searchQuery.'%', '%'.$searchQuery.'%'));
}

// Clone for display records count
$countDb = clone $db;
$totalDisplayRecords = $countDb->getValue("purchase_invoices inv", "count(*)");

$db->orderBy("inv.id", "desc");
$invoices = $db->get('purchase_invoices inv', Array($row, $rowperpage), $cols);

if (empty($invoices)) {
    echo json_encode([
        "draw" => intval($draw),
        "iTotalRecords" => $totalRecords,
        "iTotalDisplayRecords" => 0,
        "data" => []
    ]);
    exit;
}

// Batch fetch items to avoid N+1
$invoiceIds = array_column($invoices, 'id');
$db->where("purchase_invoice_id", $invoiceIds, "IN");
$allItems = $db->get("purchase_invoice_items");
$itemTotals = [];
foreach ($allItems as $item) {
    if (!isset($itemTotals[$item['purchase_invoice_id']])) {
        $itemTotals[$item['purchase_invoice_id']] = ['qty' => 0, 'empty' => 0];
    }
    $itemTotals[$item['purchase_invoice_id']]['qty'] += (int)$item['qty'];
    $itemTotals[$item['purchase_invoice_id']]['empty'] += (int)$item['empty_qty'];
}

$data = [];
foreach ($invoices as $invoice) {
    $totals = isset($itemTotals[$invoice['id']]) ? $itemTotals[$invoice['id']] : ['qty' => 0, 'empty' => 0];
    
    $actions = '';
    if (checkPermission('purchase_invoices_edit')) {
        $actions .= '<a href="edit-purchase-invoice.php?id=' . $invoice['id'] . '" class="btn btn-warning btn-sm">Edit</a> ';
    }
    if (checkPermission('purchase_invoices_delete')) {
        $actions .= '<a class="btn btn-danger btn-sm" onclick="deleteRow(' . $invoice['id'] . ')">Delete</a>';
    }

    $data[] = [
        'id' => $invoice['id'],
        'vendor' => '<a href="vendor-ledger.php?id=' . $invoice['vendor_id'] . '" class="text-primary"><b>' . $invoice['vendor'] . '</b></a>',
        'empty_cylinders' => $totals['empty'],
        'total_cylinders' => $totals['qty'],
        'date' => date("d-m-Y", strtotime($invoice['date'])),
        'grand_total' => $invoice['grand_total'],
        'paid' => $invoice['paid'],
        'balance' => $invoice['balance'],
        'created_at' => date("d-m-Y h:i:s", strtotime($invoice['created_at'])),
        'actions' => $actions
    ];
}

echo json_encode([
    "draw" => intval($draw),
    "iTotalRecords" => $totalRecords,
    "iTotalDisplayRecords" => $totalDisplayRecords,
    "data" => $data,
]);
?>
