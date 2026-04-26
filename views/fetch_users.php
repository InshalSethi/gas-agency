<?php
require '../config/db.php';
require '../config/db_functions.php';
require '../config/auth.php';

if (!checkPermission('users_view')) {
    echo json_encode(["data" => []]);
    exit();
}

$draw = $_REQUEST['draw'] ?? 1;
$row = $_REQUEST['start'] ?? 0;
$rowperpage = $_REQUEST['length'] ?? 10;
$searchQuery = $_REQUEST['search']['value'] ?? '';

// Total without filter
$totalAllUsers = $db->getValue("users", "count(*)");

$db->join("roles r", "u.role_id = r.id", "LEFT");
if (!empty($searchQuery)) {
    $db->where("(u.username LIKE '%$searchQuery%' OR u.email LIKE '%$searchQuery%' OR r.name LIKE '%$searchQuery%')");
}

$db->withTotalCount();
$users = $db->get("users u", [$row, $rowperpage], "u.id, u.username, u.email, r.name as role_name");
$totalFiltered = $db->totalCount;

$data = [];
foreach ($users as $user) {
    $actions = '';
    if (checkPermission('users_edit')) {
        $actions .= '<a href="edit_user.php?id=' . $user['id'] . '" class="btn btn-warning btn-sm">Edit</a> ';
    }
    if (checkPermission('users_delete')) {
        $actions .= '<button class="btn btn-danger btn-sm" onclick="deleteUser(' . $user['id'] . ')">Delete</button>';
    }

    $data[] = [
        "id" => $user['id'],
        "username" => $user['username'],
        "email" => $user['email'] ?? 'N/A',
        "role_name" => $user['role_name'] ?? 'No Role',
        "actions" => $actions
    ];
}

$response = [
    "draw" => intval($draw),
    "iTotalRecords" => $totalAllUsers,
    "iTotalDisplayRecords" => $totalFiltered,
    "data" => $data
];

echo json_encode($response);
?>
