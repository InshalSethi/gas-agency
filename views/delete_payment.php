<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: views/login.php");
    exit();
}

$payment_id = $_GET['id'];

$query = "DELETE FROM payments WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $payment_id);

if ($stmt->execute()) {
    header("Location: vendor_payment_management.php");
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
?>
