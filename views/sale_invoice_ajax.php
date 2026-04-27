<?php
require '../config/db.php';
require_once '../config/db_functions.php';
require_once '../config/auth.php';

$draw = $_REQUEST['draw'];
$row = $_REQUEST['start'];
$rowperpage = $_REQUEST['length'];
$columnIndex = $_REQUEST['order'][0]['column'];
$columnName = $_REQUEST['columns'][$columnIndex]['data'];
$columnSortOrder = $_REQUEST['order'][0]['dir'];
$searchQuery = $_REQUEST['search']['value'];

$start_date = !empty($_REQUEST['start_date']) ? date("Y-m-d", strtotime($_REQUEST['start_date'])) : null;
$end_date = !empty($_REQUEST['end_date']) ? date("Y-m-d", strtotime($_REQUEST['end_date'])) : null;

$cols = array(
    "inv.id", "inv.customer_id", "inv.empty_cylinders", "inv.date",
    "inv.grand_total", "inv.received", "inv.balance", "inv.description",
    "inv.created_at", "cus.name as customer"
);

// Total Records (All non-deleted)
$totalRecords = $db->where("deleted_at", NULL, "IS")->getValue("invoices", "count(*)");

// Reset for filtered query
$db->join("customers cus", "inv.customer_id=cus.id", "LEFT");
$db->where("cus.deleted_at", NULL, 'IS');
$db->where("inv.deleted_at", NULL, 'IS');

if ($start_date && $end_date) {
    $db->where("inv.date", Array($start_date, $end_date), 'BETWEEN');
}

if (!empty($searchQuery)) {
    $db->where("(cus.name LIKE ? OR inv.description LIKE ? OR inv.date LIKE ? OR inv.grand_total LIKE ? OR inv.received LIKE ? OR inv.balance LIKE ?)", 
        array('%'.$searchQuery.'%', '%'.$searchQuery.'%', '%'.$searchQuery.'%', '%'.$searchQuery.'%', '%'.$searchQuery.'%', '%'.$searchQuery.'%'));
}

// Clone for display records count (filtered)
$countDb = clone $db;
$totalDisplayRecords = $countDb->getValue("invoices inv", "count(*)");

$db->orderBy("inv.id", "desc");
$invoices = $db->get('invoices inv', Array($row, $rowperpage), $cols);

if (empty($invoices)) {
    echo json_encode([
        "draw" => intval($draw),
        "iTotalRecords" => $totalRecords,
        "iTotalDisplayRecords" => 0,
        "data" => []
    ]);
    exit;
}

// Batch fetch invoice items to avoid N+1
$invoiceIds = array_column($invoices, 'id');
$db->where("invoice_id", $invoiceIds, "IN");
$allItems = $db->get("invoice_items");
$itemTotals = [];
foreach ($allItems as $item) {
    if (!isset($itemTotals[$item['invoice_id']])) {
        $itemTotals[$item['invoice_id']] = ['qty' => 0, 'empty' => 0];
    }
    $itemTotals[$item['invoice_id']]['qty'] += (int)$item['qty'];
    $itemTotals[$item['invoice_id']]['empty'] += (int)$item['empty_qty'];
}

$data = [];
foreach ($invoices as $invoice) {
    $totals = isset($itemTotals[$invoice['id']]) ? $itemTotals[$invoice['id']] : ['qty' => 0, 'empty' => 0];
    
    $actions = '';
    if (checkPermission('sale_invoices_edit')) {
        $actions .= '<a href="edit-invoice.php?id=' . $invoice['id'] . '" class="btn btn-warning btn-sm">Edit</a> ';
    }
    if (checkPermission('sale_invoices_delete')) {
        $actions .= '<a class="btn btn-danger btn-sm" onclick="deleteRow(' . $invoice['id'] . ')">Delete</a>';
    }

    $data[] = [
        'id' => $invoice['id'],
        'description' => $invoice['description'],
        'customer' => '<a href="customer-ledger.php?id=' . $invoice['customer_id'] . '" class="text-primary"><b>'.$invoice['customer'].'</b></a>',
        'empty_cylinders' => $totals['empty'],
        'qty' => $totals['qty'],
        'date' => date("d-m-Y", strtotime($invoice['date'])),
        'grand_total' => $invoice['grand_total'],
        'received' => $invoice['received'],
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
