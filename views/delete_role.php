<?php
require '../config/db.php';
require '../config/db_functions.php';
require '../config/auth.php';

if (!checkPermission('roles_delete')) {
    echo json_encode(["status" => "error", "message" => "Access Denied."]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $id = $_POST['id'];

    if ($id == 1) {
        echo json_encode(["status" => "error", "message" => "Cannot delete Admin role."]);
        exit();
    }

    // Check if any users have this role
    $db->where("role_id", $id);
    $userCount = $db->getValue("users", "count(*)");
    if ($userCount > 0) {
        echo json_encode(["status" => "error", "message" => "Cannot delete role because it is assigned to $userCount users."]);
        exit();
    }

    $db->where("id", $id);
    if ($db->delete("roles")) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => $db->getLastError()]);
    }
}
?>
