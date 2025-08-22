<?php
// delete_customer.php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $customer_id = $_GET['id'];

    // First, delete any associated security options
    $stmt = $conn->prepare("DELETE FROM security_options WHERE customer_id = ?");
    $stmt->bind_param("s", $customer_id);
    $stmt->execute();

    // Then, delete the customer
    $stmt = $conn->prepare("DELETE FROM customers WHERE customer_id = ?");
    $stmt->bind_param("s", $customer_id);
    $stmt->execute();

    header("Location: customer_management.php");
    exit();
} else {
    header("Location: customer_management.php");
    exit();
}
?>
