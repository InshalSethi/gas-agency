<?php
require '../config/db.php';
require '../config/db_functions.php';
require '../config/auth.php';

if (!isAdmin()) {
    echo json_encode(["status" => "error", "message" => "Access Denied."]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $id = $_POST['id'];

    // Prevent deleting itself if needed, or deleting the last admin
    if ($id == $_SESSION['user_id']) {
        echo json_encode(["status" => "error", "message" => "You cannot delete yourself."]);
        exit();
    }

    $db->where("id", $id);
    if ($db->delete("users")) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => $db->getLastError()]);
    }
}
?>
