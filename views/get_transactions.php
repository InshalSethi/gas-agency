<?php
// get_transactions.php
require_once '../config/db.php';

$customer_id = $_GET['customer_id'];

// Initialize the response array
$response = array(
    "draw" => intval($_GET['draw']),
    "recordsTotal" => 0,
    "recordsFiltered" => 0,
    "data" => array()
);

// Count total records
$totalRecordsQuery = "SELECT COUNT(*) AS total FROM transactions WHERE customer_id = ?";
$stmt = $conn->prepare($totalRecordsQuery);
$stmt->bind_param('s', $customer_id);
$stmt->execute();
$totalRecordsResult = $stmt->get_result();
$totalRecords = $totalRecordsResult->fetch_assoc()['total'];
$response['recordsTotal'] = $totalRecords;
$stmt->close();

// Count filtered records and get filtered data
$searchValue = $_GET['search']['value'];
$limit = $_GET['length'];
$offset = $_GET['start'];

$filteredQuery = "SELECT t.*, c.name FROM transactions t JOIN customers c ON t.customer_id = c.customer_id WHERE t.customer_id = ? AND (t.transaction_type LIKE ? OR t.amount LIKE ? OR t.balance LIKE ?)";
$stmt = $conn->prepare($filteredQuery);
$searchParam = "%$searchValue%";
$stmt->bind_param('ssss', $customer_id, $searchParam, $searchParam, $searchParam);
$stmt->execute();
$filteredResult = $stmt->get_result();
$response['recordsFiltered'] = $filteredResult->num_rows;

// Fetch the actual data with limit and offset
$dataQuery = $filteredQuery . " ORDER BY t.transaction_date DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($dataQuery);
$stmt->bind_param('ssssii', $customer_id, $searchParam, $searchParam, $searchParam, $limit, $offset);
$stmt->execute();
$dataResult = $stmt->get_result();
while ($row = $dataResult->fetch_assoc()) {
    $response['data'][] = $row;
}

$stmt->close();
$conn->close();

echo json_encode($response);
?>
