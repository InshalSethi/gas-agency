<?php
require '../config/db.php';

$draw = $_REQUEST['draw'];
$row = $_REQUEST['start'];
$rowperpage = $_REQUEST['length']; // Rows display per page
$columnIndex = $_REQUEST['order'][0]['column']; // Column index
$columnName = $_REQUEST['columns'][$columnIndex]['data']; // Column name
$columnSortOrder = $_REQUEST['order'][0]['dir']; // asc or desc
$searchQuery = $_REQUEST['search']['value'];
$cylinderFilter = $_REQUEST['cylinder_filter'];
$totalRecords = 0;

$cols = array(
    "id",
    "name",
    "empty_qty",
    "qty",
    "retail_rate",
    "created_at",
);

if (!empty($cylinderFilter)) {
    if ($cylinderFilter === 'empty_exist') {
        $db->where("empty_qty", '0', '>');
    }
    if ($cylinderFilter === 'empty_finished') {
        $db->where("empty_qty", '0', '<=');
    }
    if ($cylinderFilter === 'qty_exist') {
        $db->where("qty", '0', '>');
    }
    if ($cylinderFilter === 'qty_finished') {
        $db->where("qty", '0', '<=');
    }
}

if (!empty($searchQuery)) {
    $db->where("name", '%' . $searchQuery . '%', 'like');
    $db->orWhere("retail_rate", '%' . $searchQuery . '%', 'like');
    $db->orWhere("empty_qty", '%' . $searchQuery . '%', 'like');
    $db->orWhere("qty", '%' . $searchQuery . '%', 'like');
    $db->orWhere("created_at", '%' . $searchQuery . '%', 'like');
}

$db->where("deleted_at", NULL, 'IS');
$db->orderBy("id", "desc");
$cylinders = $db->get('cylinders cy', Array($row, $rowperpage), $cols);

$totalRecords = $db->count;
$data = [];

foreach($cylinders as $cylinder) {
    $data[] = [
        'id' => $cylinder['id'],
        'name' => $cylinder['name'],
        'empty_qty' => $cylinder['empty_qty'],
        'qty' => $cylinder['qty'],
        'retail_rate' => $cylinder['retail_rate'],
        'created_at' => date("d-m-Y h:i:s", strtotime($cylinder['created_at'])),
        'actions' => '<a href="edit_cylinder.php?id=' . $cylinder['id'] . '" class="btn btn-warning btn-sm">Edit</a><a href="delete_cylinder.php?id=' . $cylinder['id'] . '" class="btn btn-sm btn-outline-danger">Delete</a>'
    ];
}

$response = array(
    "draw" => intval($draw),
    "iTotalRecords" => $totalRecords,
    "iTotalDisplayRecords" => $totalRecords,
    "data" => $data,
);

echo json_encode($response);

?>
