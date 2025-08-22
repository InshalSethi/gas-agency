<?php
// delete_cylinder_process.php
// session_start();
require 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: views/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];

    $stmt = $conn->prepare("DELETE FROM cylinders WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header("Location: views/cylinder_management.php");
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
}
?>
