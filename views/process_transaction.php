<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $customer_id = $_POST['customer_id'];
    $transaction_type = $_POST['transaction_type'];
    $amount = $_POST['amount'];
    $balance = $_POST['balance'];
    $selected_cylinders = json_decode($_POST['selected_cylinders'], true);

    $conn->begin_transaction();

    try {
        foreach ($selected_cylinders as $gasType) {
            if ($transaction_type === 'Rental' || $transaction_type === 'Purchase') {
                $updateCylinderStmt = $conn->prepare("UPDATE cylinders SET available_amount = available_amount - 1 WHERE gas_type = ?");
                $updateCylinderStmt->bind_param("s", $gasType);
                $updateCylinderStmt->execute();

                $updateCustomerStmt = $conn->prepare("UPDATE customers SET cylinders = cylinders + 1, gas_types = CONCAT_WS(',', gas_types, ?) WHERE customer_id = ?");
                $updateCustomerStmt->bind_param("ss", $gasType, $customer_id);
                $updateCustomerStmt->execute();
            } elseif ($transaction_type === 'Return') {
                $updateCylinderStmt = $conn->prepare("UPDATE cylinders SET available_amount = available_amount + 1 WHERE gas_type = ?");
                $updateCylinderStmt->bind_param("s", $gasType);
                $updateCylinderStmt->execute();

                $updateCustomerStmt = $conn->prepare("UPDATE customers SET cylinders = cylinders - 1 WHERE customer_id = ?");
                $updateCustomerStmt->bind_param("s", $customer_id);
                $updateCustomerStmt->execute();
            } elseif ($transaction_type === 'Refill') {
                $updateCylinderStmt = $conn->prepare("UPDATE cylinders SET available_amount = available_amount - 1, empty_cylinders = empty_cylinders + 1 WHERE gas_type = ?");
                $updateCylinderStmt->bind_param("s", $gasType);
                $updateCylinderStmt->execute();
            }
        }

        $stmt = $conn->prepare("SELECT balance FROM customers WHERE customer_id = ?");
        $stmt->bind_param("s", $customer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $currentBalance = $row['balance'];

        $newBalance = $currentBalance + $balance;
        $updateStmt = $conn->prepare("UPDATE customers SET balance = ? WHERE customer_id = ?");
        $updateStmt->bind_param("ds", $newBalance, $customer_id);
        $updateStmt->execute();

        $transactionStmt = $conn->prepare("INSERT INTO transactions (customer_id, transaction_type, cylinder_count, amount, balance) VALUES (?, ?, ?, ?, ?)");
        $transactionStmt->bind_param("ssidd", $customer_id, $transaction_type, count($selected_cylinders), $amount, $balance);
        $transactionStmt->execute();

        $conn->commit();

        $invoiceId = $transactionStmt->insert_id;
        $transactionDate = date('Y-m-d H:i:s');

        echo "<h2>Invoice</h2>";
        echo "<p>Invoice ID: $invoiceId</p>";
        echo "<p>Customer ID: $customer_id</p>";
        echo "<p>Transaction Type: $transaction_type</p>";
        echo "<p>Cylinder Count: " . count($selected_cylinders) . "</p>";
        echo "<p>Amount: $amount</p>";
        echo "<p>Balance: $balance</p>";
        echo "<p>Date: $transactionDate</p>";
        echo "<button onclick=\"window.print()\">Print Invoice</button>";
    } catch (Exception $e) {
        $conn->rollback();
        echo "Transaction failed: " . $e->getMessage();
    } finally {
        $stmt->close();
        $conn->close();
    }
} else {
    echo "Invalid request method.";
}
?>
