<?php
// fetch_user_details.php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    // Redirect if user is not logged in
    header("Location: views/login.php");
    exit();
}

// Fetch user details
$userId = $_SESSION['user_id'];
$query = "SELECT id, username FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

echo json_encode($user);
?>
