<?php
// edit_cylinder_process.php
session_start();
require 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: views/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $gas_type = $_POST['gas_type'];
    $total_amount = $_POST['total_amount'];
    $empty_cylinders = $_POST['empty_cylinders'];

    // Calculate available amount
    $available_amount = $total_amount - $empty_cylinders;

    $stmt = $conn->prepare("UPDATE cylinders SET gas_type = ?, total_amount = ?, available_amount = ?, empty_cylinders = ? WHERE id = ?");
    $stmt->bind_param("siiii", $gas_type, $total_amount, $available_amount, $empty_cylinders, $id);

    if ($stmt->execute()) {
        header("Location: views/cylinder_management.php");
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
}
?>
