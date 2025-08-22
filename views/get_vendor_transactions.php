<?php
require_once '../config/db.php';

// Get vendor_id from the request
$vendor_id = isset($_GET['vendor_id']) ? intval($_GET['vendor_id']) : 0;

// Columns array to map the request column index to the database column
$columns = [
    0 => 'transaction_date',
    1 => 'total_amount',
    2 => 'amount_paid',
    3 => 'remaining_amount'
];

// Read values from DataTables request
$limit = $_GET['length'];
$start = $_GET['start'];
$order = $columns[$_GET['order'][0]['column']];
$dir = $_GET['order'][0]['dir'];
$search = $_GET['search']['value'];

// Fetch the total number of records for the given vendor_id
$query = "SELECT COUNT(*) AS total FROM vendor_transaction_history WHERE vendor_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $vendor_id);
$stmt->execute();
$result = $stmt->get_result();
$totalData = $result->fetch_assoc()['total'];
$stmt->close();

/*/ Fetch filtered records
$query = "SELECT * FROM vendor_transaction_history WHERE vendor_id = ?";
if (!empty($search)) {
    $query .= " AND (transaction_date LIKE ? OR total_amount LIKE ? OR amount_paid LIKE ? OR remaining_amount LIKE ?)";
}
$query .= " ORDER BY $order $dir LIMIT ?, ?";

$stmt = $conn->prepare($query);
if (!empty($search)) {
    $searchTerm = "%$search%";
    $stmt->bind_param("issssii", $vendor_id, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $start, $limit);
} else {
    $stmt->bind_param("iii", $vendor_id, $start, $limit);
}
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $nestedData = [];
    $nestedData['transaction_date'] = $row['transaction_date'];
    $nestedData['total_amount'] = $row['total_amount'];
    $nestedData['amount_paid'] = $row['amount_paid'];
    $nestedData['remaining_amount'] = $row['remaining_amount'];
    $data[] = $nestedData;
}

$json_data = [
    "draw" => intval($_GET['draw']),
    "recordsTotal" => intval($totalData),
    "recordsFiltered" => intval($totalData),
    "data" => $data
];

echo json_encode($json_data);*/
$query = "SELECT * FROM vendor_transaction_history WHERE vendor_id = ? AND deleted = 0";
if (!empty($search)) {
    $query .= " AND (transaction_date LIKE ? OR total_amount LIKE ? OR amount_paid LIKE ? OR remaining_amount LIKE ?)";
}
$query .= " ORDER BY $order $dir LIMIT ?, ?";

$stmt = $conn->prepare($query);
if (!empty($search)) {
    $searchTerm = "%$search%";
    $stmt->bind_param("issssii", $vendor_id, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $start, $limit);
} else {
    $stmt->bind_param("iii", $vendor_id, $start, $limit);
}
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $nestedData = [];
    $nestedData['transaction_date'] = $row['transaction_date'];
    $nestedData['total_amount'] = $row['total_amount'];
    $nestedData['amount_paid'] = $row['amount_paid'];
    $nestedData['remaining_amount'] = $row['remaining_amount'];
    $data[] = $nestedData;
}

$json_data = [
    "draw" => intval($_GET['draw']),
    "recordsTotal" => intval($totalData),
    "recordsFiltered" => intval($totalData),
    "data" => $data
];

echo json_encode($json_data);

?>
