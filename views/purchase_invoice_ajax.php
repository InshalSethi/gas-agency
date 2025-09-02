<?php
require '../config/db.php';

// Get search query if available
// var_dump($_REQUEST);die();
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



// var_dump($result);die();
// Initialize the base SQL query
// $sql = "SELECT c.*, s.security_type, s.person_name, s.cash_amount, s.cheque_details
//         FROM customers c
//         LEFT JOIN security_options s ON c.customer_id = s.customer_id";

// // Modify the query if there is a search query
// if ($searchQuery) {
//     $sql .= " WHERE c.name LIKE '%$searchQuery%' OR c.phone LIKE '%$searchQuery%' OR c.cnic LIKE '%$searchQuery%'";
// }

if (!empty($searchQuery)) {
         $db->where ("ven.name", '%'.$searchQuery.'%', 'like');
         $db->orWhere ("inv.date", '%'.$searchQuery.'%', 'like');
         $db->orWhere ("inv.grand_total", '%'.$searchQuery.'%', 'like');
         $db->orWhere ("inv.paid", '%'.$searchQuery.'%', 'like');
         $db->orWhere ("inv.balance", '%'.$searchQuery.'%', 'like');
}

// $result = $conn->query($sql);
$db->where ("inv.deleted_at", NULL, 'IS');
$db->orderBy("inv.id","desc");
$invoices=$db->get('purchase_invoices inv',Array ($row, $rowperpage),$cols);
$totalRecords = $db->count;
// echo ''.$db->getLastQuery();die();
// Initialize an array to hold the output data
$data = [];

// Fetch rows from the result set
foreach( $invoices as $invoice ){
    $totalInvCylinders = 0;
    $totalEmptyCylinders = 0;
    $db->where("purchase_invoice_id", $invoice['id']);
    $invoiceItems = $db->get("purchase_invoice_items");
    foreach ($invoiceItems as $invoiceItem) {
        $totalInvCylinders += $invoiceItem['qty'];
        $totalEmptyCylinders += $invoiceItem['empty_qty'];
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
        'actions' => '
            <a href="edit-purchase-invoice.php?id=' . $invoice['id'] . '" class="btn btn-warning btn-sm">Edit</a>
            <a class="btn btn-danger btn-sm"  onclick="deleteRow('.$invoice['id'].')">Delete</a>'
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
