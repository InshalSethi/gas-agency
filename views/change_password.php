<?php
// change_password.php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    // Redirect if user is not logged in
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$currentPassword = $_POST['current_password'];
$newPassword = $_POST['new_password'];
$confirmPassword = $_POST['confirm_password'];

// Validate password fields
if ($newPassword !== $confirmPassword) {
    $response = ['success' => false, 'message' => 'New password and confirm password do not match.'];
    echo json_encode($response);
    exit();
}

// Fetch current password from database
$query = "SELECT password FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user || !password_verify($currentPassword, $user['password'])) {
    $response = ['success' => false, 'message' => 'Current password is incorrect.'];
    echo json_encode($response);
    exit();
}

// Update password
$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
$updateQuery = "UPDATE users SET password = ? WHERE id = ?";
$updateStmt = $conn->prepare($updateQuery);
$updateStmt->bind_param('si', $hashedPassword, $userId);

if ($updateStmt->execute()) {
    $response = ['success' => true, 'message' => 'Password changed successfully.'];
} else {
    $response = ['success' => false, 'message' => 'Error updating password. Please try again.'];
}

echo json_encode($response);
?>
