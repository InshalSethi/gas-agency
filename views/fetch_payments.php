<?php
require '../config/db.php';
require_once '../config/db_functions.php';
require '../config/auth.php';

$query = "SELECT p.id, v.id as vendor_id, v.name as vendor_name, p.total_cash, p.cash_paid, p.remaining_balance
          FROM payments p
          JOIN vendors v ON p.vendor_id = v.id";

$result = $conn->query($query);

$data = array();
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode(array('data' => $data));
?>
