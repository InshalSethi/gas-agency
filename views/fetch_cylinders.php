<?php
require '../config/db.php';
require_once '../config/db_functions.php';
require '../config/auth.php';

if (!checkPermission('cylinders_view')) {
    echo json_encode(["draw" => 0, "iTotalRecords" => 0, "iTotalDisplayRecords" => 0, "data" => []]);
    exit();
}

$draw = $_REQUEST['draw'];
$row = $_REQUEST['start'];
$rowperpage = $_REQUEST['length'];
$columnIndex = $_REQUEST['order'][0]['column'];
$columnName = $_REQUEST['columns'][$columnIndex]['data'];
$columnSortOrder = $_REQUEST['order'][0]['dir'];
$searchQuery = $_REQUEST['search']['value'];
$cylinderFilter = $_REQUEST['cylinder_filter'];

$cols = array("id", "name", "empty_qty", "qty", "retail_rate", "created_at");

// Total Records
$totalRecords = $db->where("deleted_at", NULL, "IS")->getValue("cylinders", "count(*)");

// Reset for filtered query
$db->where("deleted_at", NULL, 'IS');

if (!empty($cylinderFilter)) {
    if ($cylinderFilter === 'empty_exist') $db->where("empty_qty", '0', '>');
    if ($cylinderFilter === 'empty_finished') $db->where("empty_qty", '0', '<=');
    if ($cylinderFilter === 'qty_exist') $db->where("qty", '0', '>');
    if ($cylinderFilter === 'qty_finished') $db->where("qty", '0', '<=');
}

if (!empty($searchQuery)) {
    $db->where("(name LIKE ? OR retail_rate LIKE ? OR empty_qty LIKE ? OR qty LIKE ? OR created_at LIKE ?)", 
        array('%'.$searchQuery.'%', '%'.$searchQuery.'%', '%'.$searchQuery.'%', '%'.$searchQuery.'%', '%'.$searchQuery.'%'));
}

// Clone for display records count
$countDb = clone $db;
$totalDisplayRecords = $countDb->getValue("cylinders", "count(*)");

$db->orderBy("id", "desc");
$cylinders = $db->get('cylinders cy', Array($row, $rowperpage), $cols);

$data = [];
foreach($cylinders as $cylinder) {
    $actions = '';
    if (checkPermission('cylinders_edit')) {
        $actions .= '<a href="edit_cylinder.php?id=' . $cylinder['id'] . '" class="btn btn-warning btn-sm">Edit</a> ';
    }
    if (checkPermission('cylinders_delete')) {
        $actions .= '<a href="delete_cylinder.php?id=' . $cylinder['id'] . '" class="btn btn-sm btn-outline-danger">Delete</a>';
    }

    $data[] = [
        'id' => $cylinder['id'],
        'name' => $cylinder['name'],
        'empty_qty' => $cylinder['empty_qty'],
        'qty' => $cylinder['qty'],
        'retail_rate' => $cylinder['retail_rate'],
        'created_at' => !empty($cylinder['created_at']) ? date("d-m-Y h:i:s", strtotime($cylinder['created_at'])) : '',
        'actions' => $actions
    ];
}

echo json_encode([
    "draw" => intval($draw),
    "iTotalRecords" => $totalRecords,
    "iTotalDisplayRecords" => $totalDisplayRecords,
    "data" => $data,
]);
?>
