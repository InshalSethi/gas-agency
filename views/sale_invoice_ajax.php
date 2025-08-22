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

$start_date = date("Y-m-d", strtotime($_REQUEST['start_date']));
$end_date = date("Y-m-d", strtotime($_REQUEST['end_date']));

$totalRecords=0;
$cols=array(
    "inv.id","inv.customer_id",
    "inv.empty_cylinders",
    "inv.date",
    "inv.grand_total",
    "inv.received",
    "inv.balance",
    "inv.description",
    "inv.created_at",
    "cus.name as customer"
);
$db->join("customers cus", "inv.customer_id=cus.id", "LEFT");



// var_dump($result);die();
// Initialize the base SQL query
// $sql = "SELECT c.*, s.security_type, s.person_name, s.cash_amount, s.cheque_details
//         FROM customers c
//         LEFT JOIN security_options s ON c.customer_id = s.customer_id";

// // Modify the query if there is a search query
// if ($searchQuery) {
//     $sql .= " WHERE c.name LIKE '%$searchQuery%' OR c.phone LIKE '%$searchQuery%' OR c.cnic LIKE '%$searchQuery%'";
// }
if (!empty($_REQUEST['start_date']) && !empty($_REQUEST['end_date'])) {
    $db->where("inv.date", Array($start_date, $end_date), 'BETWEEN');
}
$db->where ("cus.deleted_at", NULL, 'IS');
$db->where ("inv.deleted_at", NULL, 'IS');
if (!empty($searchQuery)) {
         $db->where ("cus.name", '%'.$searchQuery.'%', 'like');
         $db->orWhere ("inv.description", '%'.$searchQuery.'%', 'like');
         $db->orWhere ("inv.date", '%'.$searchQuery.'%', 'like');
         $db->orWhere ("inv.grand_total", '%'.$searchQuery.'%', 'like');
         $db->orWhere ("inv.received", '%'.$searchQuery.'%', 'like');
         $db->orWhere ("inv.balance", '%'.$searchQuery.'%', 'like');
}

// $result = $conn->query($sql);

$db->orderBy("inv.id","desc");
$invoices=$db->get('invoices inv',Array ($row, $rowperpage),$cols);
$totalRecords = $db->count;
// echo ''.$db->getLastQuery();die();
// Initialize an array to hold the output data
$data = [];

// Fetch rows from the result set
foreach( $invoices as $invoice ){

    $totalInvCylinders = 0;
    $totalEmptyCylinders = 0;
    $db->where("invoice_id", $invoice['id']);
    $invoiceItems = $db->get("invoice_items");
    foreach ($invoiceItems as $invoiceItem) {
        $totalInvCylinders += $invoiceItem['qty'];
        $totalEmptyCylinders += $invoiceItem['empty_qty'];
    }

    $data[] = [
        'id' => $invoice['id'],
        'description' => $invoice['description'],
        'customer' => '<a href="customer-ledger.php?id=' . $invoice['customer_id'] . '" class="text-primary"><b>'.$invoice['customer'].'</b></a>',
        'empty_cylinders' => $totalEmptyCylinders,
        'qty' => $totalInvCylinders,
        'date' => date("d-m-Y", strtotime($invoice['date'])),
        'grand_total' => $invoice['grand_total'],
        'received' => $invoice['received'],
        'balance' => $invoice['balance'],
        'created_at' => date("d-m-Y h:i:s", strtotime($invoice['created_at'])),
        'actions' => '
            <a href="edit-invoice.php?id=' . $invoice['id'] . '" class="btn btn-warning btn-sm">Edit</a>
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
