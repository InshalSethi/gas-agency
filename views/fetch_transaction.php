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

// Date range filter parameters
$startDate = isset($_REQUEST['start_date']) ? $_REQUEST['start_date'] : '';
$endDate = isset($_REQUEST['end_date']) ? $_REQUEST['end_date'] : '';

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
$db->join("purchase_invoices p_inv", "tr.purchase_invoice_id=p_inv.id", "LEFT");

$db->where ("cus.deleted_at", NULL, 'IS');
$db->where ("ven.deleted_at", NULL, 'IS');
$db->where ("inv.deleted_at", NULL, 'IS');
$db->where ("p_inv.deleted_at", NULL, 'IS');
$db->where ("tr.deleted_at", NULL, 'IS');

// Apply date range filter
if (!empty($startDate) && !empty($endDate)) {
    $db->where ("tr.date", $startDate, '>=');
    $db->where ("tr.date", $endDate, '<=');
}

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

// Apply date range filter
if (!empty($startDate) && !empty($endDate)) {
    $db->where ("tr.date", $startDate, '>=');
    $db->where ("tr.date", $endDate, '<=');
}

if (!empty($searchQuery)) {
   $db->where ("cus.name", '%'.$searchQuery.'%', 'like');
   $db->orWhere ("ven.name", '%'.$searchQuery.'%', 'like');
   $db->orWhere ("tr.date", '%'.$searchQuery.'%', 'like');
   $db->orWhere ("tr.amount", '%'.$searchQuery.'%', 'like');
   $db->orWhere ("tr.created_at", '%'.$searchQuery.'%', 'like');
}
$db->orderBy("tr.id","desc");
$transactions=$db->get('transactions tr',Array ($row, $rowperpage),$cols);

// Opening balance is always 0 (no transactions initially)
$openingBalance = 0;

// Calculate closing balance from ALL transactions from beginning up to end date (or all transactions if no date filter)
$closingBalance = $openingBalance;

// Get all transactions to calculate the total closing balance
$db->join("customers cus", "cus.id=tr.customer_id", "LEFT");
$db->join("vendors ven", "ven.id=tr.vendor_id", "LEFT");
$db->join("invoices inv", "inv.id=tr.invoice_id", "LEFT");
$db->join("purchase_invoices p_inv", "tr.purchase_invoice_id=p_inv.id", "LEFT");
$db->where("cus.deleted_at", NULL, 'IS');
$db->where("ven.deleted_at", NULL, 'IS');
$db->where("inv.deleted_at", NULL, 'IS');
$db->where("p_inv.deleted_at", NULL, 'IS');
$db->where("tr.deleted_at", NULL, 'IS');

// For closing balance: if date filter is applied, get balance from beginning up to end date
// If no date filter, get balance from all transactions
if (!empty($endDate)) {
    $db->where("tr.date", $endDate, '<='); // From beginning up to end date
}

$db->orderBy("tr.id", "asc");

$closingTransactions = $db->get('transactions tr', null, 'tr.entity, tr.amount');

foreach ($closingTransactions as $closingTx) {
    if ($closingTx['entity'] === 'sale') {
        $closingBalance += $closingTx['amount'];
    } elseif ($closingTx['entity'] === 'purchase') {
        $closingBalance -= $closingTx['amount'];
    }
}

// For running balance calculation, we need to calculate balance for each transaction
// based on its chronological position, not pagination position
// Since we're showing in descending order but want running balance in chronological order

$data = [];

// For each transaction, calculate its running balance by getting all transactions up to that point
foreach( $transactions as $transaction ){
    $received = '';
    $paid = '';

    // Calculate running balance for this specific transaction
    // Get ALL transactions from the very beginning up to this transaction (chronologically)
    // This ensures running balance is cumulative from the start, regardless of date filters
    $db->join("customers cus", "cus.id=tr.customer_id", "LEFT");
    $db->join("vendors ven", "ven.id=tr.vendor_id", "LEFT");
    $db->join("invoices inv", "inv.id=tr.invoice_id", "LEFT");
    $db->join("purchase_invoices p_inv", "tr.purchase_invoice_id=p_inv.id", "LEFT");
    $db->where("cus.deleted_at", NULL, 'IS');
    $db->where("ven.deleted_at", NULL, 'IS');
    $db->where("inv.deleted_at", NULL, 'IS');
    $db->where("p_inv.deleted_at", NULL, 'IS');
    $db->where("tr.deleted_at", NULL, 'IS');
    $db->where("tr.id", $transaction['id'], '<='); // All transactions up to and including this one

    // DO NOT apply date filter here - we want ALL transactions from beginning for running balance

    $db->orderBy("tr.id", "asc");
    $balanceTransactions = $db->get('transactions tr', null, 'tr.entity, tr.amount');

    // Calculate running balance up to this transaction (from the very beginning)
    $currentRowBalance = $openingBalance;
    foreach ($balanceTransactions as $balanceTx) {
        if ($balanceTx['entity'] === 'sale') {
            $currentRowBalance += $balanceTx['amount'];
        } elseif ($balanceTx['entity'] === 'purchase') {
            $currentRowBalance -= $balanceTx['amount'];
        }
    }

    if( $transaction['entity'] === 'sale')
    {
        $received = '<span class="text-success">'.$transaction['amount'].'</span>';
    }
    if( $transaction['entity'] === 'purchase')
    {
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
        'balance' => '<span class="fw-bold ' . ($currentRowBalance >= 0 ? 'text-success' : 'text-danger') . '">' . number_format($currentRowBalance, 2) . '</span>',
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

    "openingBalance" => $openingBalance,

    "closingBalance" => $closingBalance

);

echo json_encode($response);
// echo json_encode([
//     "data" => $data
// ]);
?>
