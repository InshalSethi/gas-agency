<?php
require '../config/db.php';
require_once '../config/db_functions.php';
require '../config/auth.php';

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
    "tr.id",
    "tr.customer_id",
    "tr.vendor_id",
    "tr.invoice_id",
    "tr.purchase_invoice_id",
    "tr.entity",
    "tr.date",
    "tr.amount",
    "tr.created_at",
    "cus.name as customer_name",
    "ven.name as vendor_name",
    "inv.received as sale_received",
);
$db->join("customers cus", "tr.customer_id=cus.id", "LEFT");
$db->join("vendors ven", "tr.vendor_id=ven.id", "LEFT");
$db->join("invoices inv", "tr.invoice_id=inv.id", "LEFT");

$db->where ("cus.deleted_at", NULL, 'IS');
$db->where ("ven.deleted_at", NULL, 'IS');
$db->where ("inv.deleted_at", NULL, 'IS');
$db->where ("tr.deleted_at", NULL, 'IS');
if (!empty($searchQuery)) {
   $db->where ("cus.name", '%'.$searchQuery.'%', 'like');
   $db->orWhere ("ven.name", '%'.$searchQuery.'%', 'like');
   $db->orWhere ("tr.date", '%'.$searchQuery.'%', 'like');
   $db->orWhere ("tr.amount", '%'.$searchQuery.'%', 'like');
   $db->orWhere ("tr.created_at", '%'.$searchQuery.'%', 'like');
}

$db->get('transactions tr',null,$cols);
$totalRecords = $db->count;


// $sql = "SELECT c.*, s.security_type, s.person_name, s.cash_amount, s.cheque_details
//         FROM customers c
//         LEFT JOIN security_options s ON c.customer_id = s.customer_id";
// if ($searchQuery) {
//     $sql .= " WHERE c.name LIKE '%$searchQuery%' OR c.phone LIKE '%$searchQuery%' OR c.cnic LIKE '%$searchQuery%'";
// }
$db->join("customers cus", "tr.customer_id=cus.id", "LEFT");
$db->join("vendors ven", "tr.vendor_id=ven.id", "LEFT");
$db->join("invoices inv", "tr.invoice_id=inv.id", "LEFT");

$db->where ("cus.deleted_at", NULL, 'IS');
$db->where ("ven.deleted_at", NULL, 'IS');
$db->where ("inv.deleted_at", NULL, 'IS');
$db->where ("tr.deleted_at", NULL, 'IS');
if (!empty($searchQuery)) {
   $db->where ("cus.name", '%'.$searchQuery.'%', 'like');
   $db->orWhere ("ven.name", '%'.$searchQuery.'%', 'like');
   $db->orWhere ("tr.date", '%'.$searchQuery.'%', 'like');
   $db->orWhere ("tr.amount", '%'.$searchQuery.'%', 'like');
   $db->orWhere ("tr.created_at", '%'.$searchQuery.'%', 'like');
}
$db->orderBy("tr.id","asc");
$transactions=$db->get('transactions tr',Array ($row, $rowperpage),$cols);
// $result = $conn->query($sql);
$balance = 0;
$data = [];
// while ($row = $result->fetch_assoc()) {
foreach( $transactions as $transaction ){
    $received = '';
    $paid = '';
    if( $transaction['entity'] === 'sale')
    {
        $balance+=$transaction['amount'];
        $received = '<span class="text-success">'.$transaction['amount'].'</span>';
    }
    if( $transaction['entity'] === 'purchase')
    {
        $balance-=$transaction['amount'];
        $paid = '<span class="text-danger">'.$transaction['amount'].'</span>';
    }
    // $securityDetail = '';
    // if ($row['security_type'] == 'Person') {
    //     $securityDetail = "Person: " . $row['person_name'];
    // } elseif ($row['security_type'] == 'Cash') {
    //     $securityDetail = "Cash: " . $row['cash_amount'];
    // } elseif ($row['security_type'] == 'Cheque') {
    //     $securityDetail = "Cheque";
    // }

    $data[] = [
        'id' => $transaction['id'],
        'customer_name' => '<a href="customer-ledger.php?id=' . $transaction['customer_id'] . '" class="text-primary"><b>'.$transaction['customer_name'].'</b></a>',
        'vendor_name' => $transaction['vendor_name'],
        'date' => $transaction['date'],
        'created_at' => $transaction['created_at'],
        'received' => $received,
        'paid' => $paid,
        'balance' => $balance,
        'actions' => '
        <a href="edit-transaction.php?id=' . $transaction['id'] . '" class="btn btn-info btn-sm">Edit</a>
        <a class="btn btn-danger btn-sm"  onclick="deleteRow('.$transaction['id'].')">Delete</a>'
    ];
}

$response = array(

    "draw" => intval($draw),

    "iTotalRecords" =>  $totalRecords,

    "iTotalDisplayRecords" => $totalRecords,

    "data" => $data,

);

echo json_encode($response);
// echo json_encode([
//     "data" => $data
// ]);
?>
