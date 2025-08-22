<?php
session_start();
require_once '../config/db.php';
require_once '../config/db_functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$pendingPayments = fetchPendingPayments();

$data = [];
foreach ($pendingPayments as $payment) {
    $data[] = [
        'customer_id' => htmlspecialchars($payment['customer_id']),
        'name' => htmlspecialchars($payment['name']),
        'pending_balance' => htmlspecialchars($payment['pending_balance']),
    ];
}

echo json_encode(['data' => $data]);
?>
