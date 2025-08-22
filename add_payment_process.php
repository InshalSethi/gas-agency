<?php
require 'config/db.php';
require_once 'config/db_functions.php';
require 'config/auth.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $vendor_id = $_POST['vendor_id'];
    $total_cash_amount = $_POST['total_cash_amount'];
    $cash_paid = $_POST['cash_paid'];
    $date = date("Y-m-d");
    $remaining_balance = $_POST['remaining_balance'];

    // Prepare the SQL statement with the correct number of placeholders
    $stmt = $conn->prepare("INSERT INTO payments (vendor_id, total_cash, cash_paid, payment_date, remaining_balance) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iiisi", $vendor_id, $total_cash_amount, $cash_paid, $date, $remaining_balance);

    if ($stmt->execute()) {
        header("Location: views/vendor_payment_management.php");
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
}
?>
