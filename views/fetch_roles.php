<?php
require '../config/db.php';
require '../config/db_functions.php';
require '../config/auth.php';

if (!checkPermission('roles_view')) {
    echo json_encode(["data" => []]);
    exit();
}

$draw = $_REQUEST['draw'] ?? 1;
$row = $_REQUEST['start'] ?? 0;
$rowperpage = $_REQUEST['length'] ?? 10;
$searchQuery = $_REQUEST['search']['value'] ?? '';

if (!empty($searchQuery)) {
    $db->where("(name LIKE '%$searchQuery%' OR description LIKE '%$searchQuery%')");
}

$db->withTotalCount();
$roles = $db->get("roles", [$row, $rowperpage]);
$totalFiltered = $db->totalCount;

// Total without filter
$totalAllRoles = $db->getValue("roles", "count(*)");

$data = [];
foreach ($roles as $role) {
    $actions = '';
    if (checkPermission('roles_edit')) {
        $actions .= '<a href="edit_role.php?id=' . $role['id'] . '" class="btn btn-warning btn-sm">Edit</a> ';
    }
    if (checkPermission('roles_delete') && $role['id'] != 1) {
        $actions .= '<button class="btn btn-danger btn-sm" onclick="deleteRole(' . $role['id'] . ')">Delete</button>';
    }
    
    $data[] = [
        "id" => $role['id'],
        "name" => $role['name'],
        "description" => $role['description'] ?? 'N/A',
        "actions" => $actions
    ];
}

$response = [
    "draw" => intval($draw),
    "iTotalRecords" => $totalAllRoles,
    "iTotalDisplayRecords" => $totalFiltered,
    "data" => $data
];

echo json_encode($response);
?>
