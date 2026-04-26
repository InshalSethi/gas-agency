<?php
require '../config/db.php';

require_once '../config/db_functions.php';
require '../config/auth.php';

if (!checkPermission('purchase_invoices_view')) {
    echo json_encode(["data" => []]);
    exit();
}

$draw = $_REQUEST['draw'];
$row = $_REQUEST['start'];
$rowperpage = $_REQUEST['length']; // Rows display per page
$columnIndex = $_REQUEST['order'][0]['column']; // Column index
$columnName = $_REQUEST['columns'][$columnIndex]['data']; // Column name
$columnSortOrder = $_REQUEST['order'][0]['dir']; // asc or desc
$searchQuery = $_REQUEST['search']['value'];
$totalRecords=0;
$cols=array(
    "inv.id",
    "inv.vendor_id",
    "inv.date",
    "inv.grand_total",
    "inv.paid",
    "inv.balance",
    "inv.created_at",
    "ven.name as vendor"
);
$db->join("vendors ven", "inv.vendor_id=ven.id", "LEFT");

if (!empty($searchQuery)) {
         $db->where ("ven.name", '%'.$searchQuery.'%', 'like');
         $db->orWhere ("inv.date", '%'.$searchQuery.'%', 'like');
         $db->orWhere ("inv.grand_total", '%'.$searchQuery.'%', 'like');
         $db->orWhere ("inv.paid", '%'.$searchQuery.'%', 'like');
         $db->orWhere ("inv.balance", '%'.$searchQuery.'%', 'like');
}

$db->where ("inv.deleted_at", NULL, 'IS');
$db->orderBy("inv.id","desc");
$invoices=$db->get('purchase_invoices inv',Array ($row, $rowperpage),$cols);
$totalRecords = $db->count;
$data = [];

foreach( $invoices as $invoice ){
    $totalInvCylinders = 0;
    $totalEmptyCylinders = 0;
    $db->where("purchase_invoice_id", $invoice['id']);
    $invoiceItems = $db->get("purchase_invoice_items");
    foreach ($invoiceItems as $invoiceItem) {
        $totalInvCylinders += $invoiceItem['qty'];
        $totalEmptyCylinders += $invoiceItem['empty_qty'];
    }

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
        'empty_cylinders' => $totalEmptyCylinders,
        'total_cylinders' => $totalInvCylinders,
        'date' => date("d-m-Y", strtotime($invoice['date'])),
        'grand_total' => $invoice['grand_total'],
        'paid' => $invoice['paid'],
        'balance' => $invoice['balance'],
        'created_at' => date("d-m-Y h:i:s", strtotime($invoice['created_at'])),
        'actions' => $actions
    ];
}

// Output the data in JSON format
$response = array(

        "draw" => intval($draw),

        "iTotalRecords" =>  $totalRecords,

        "iTotalDisplayRecords" => $totalRecords,

        "data" => $data,

      );

      echo json_encode($response);
// echo json_encode(['data' => $data]);

?>
