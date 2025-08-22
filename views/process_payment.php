<?php
// views/process_payment.php
require_once('../config/db.php');

$customer_id = $_POST['customer_id'];
$amount = $_POST['amount'];
$pending_balance = $_POST['pending_balance'];

// Fetch the current pending balance
$query = "SELECT balance FROM customers WHERE customer_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $customer_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $current_balance = $row['balance'];

    // Calculate the new pending balance
    $new_balance = $pending_balance - $amount;

    // Update the balance
    $update_stmt = $conn->prepare("UPDATE customers SET balance = ? WHERE customer_id = ?");
    $update_stmt->bind_param("ds", $new_balance, $customer_id);  // Binding balance as double and customer_id as string

    if ($update_stmt->execute()) {
        echo "Payment processed successfully.";
    } else {
        echo "Error processing payment: " . $update_stmt->error;
    }
} else {
    echo "Customer not found or no pending payment.";
}

$stmt->close();
$conn->close();

header("Location: pending_payments.php");
exit();
?>
