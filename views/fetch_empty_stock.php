<?php
require '../config/db.php';

// Get search query if available
// var_dump($_REQUEST);die();
$draw = $_REQUEST['draw'];

$row = $_REQUEST['start'];

$rowperpage = $_REQUEST['length']; // Rows display per page

$columnIndex = $_REQUEST['order'][0]['column']; // Column index

$columnName = $_REQUEST['columns'][$columnIndex]['data']; // Column name

$columnSortOrder = $_REQUEST['order'][0]['dir']; // asc or desc

$searchQuery = $_REQUEST['search']['value'];

$totalRecords=0;
$cols=array(
    "ecy.invoice_id",
    "ecy.invoice_item_id",
    "ecy.cylinders as qty",
    "ecy.status",
    "ecy.created_at",
    "cy.name as cylinder",
    "cy.id as pro_id",
);
$db->join("invoices inv", "inv.id=ecy.invoice_id", "LEFT");
$db->join("invoice_items inv_i", "inv_i.id=ecy.invoice_item_id", "LEFT");
$db->join("cylinders cy", "cy.id=inv_i.product_id", "LEFT");



// var_dump($result);die();
if (!empty($searchQuery)) {
         $db->where ("cy.name", '%'.$searchQuery.'%', 'like');
         $db->orWhere ("ecy.cylinders", '%'.$searchQuery.'%', 'like');
         $db->orWhere ("ecy.created_at", '%'.$searchQuery.'%', 'like');
         $db->orWhere ("cy.id", '%'.$searchQuery.'%', 'like');
         // $db->orWhere ("inv.balance", '%'.$searchQuery.'%', 'like');
}

// $result = $conn->query($sql);
$db->where ("ecy.deleted_at", NULL, 'IS');
$db->orderBy("ecy.id","desc");
$emptyCylinders=$db->get('empty_cylinders ecy',Array ($row, $rowperpage),$cols);
$totalRecords = $db->count;
// echo ''.$db->getLastQuery();die();
// Initialize an array to hold the output data
$data = [];

// Fetch rows from the result set
foreach( $emptyCylinders as $emptyCylinder ){
    
    $data[] = [
        'id' => $emptyCylinder['pro_id'],
        'cylinder' => $emptyCylinder['cylinder'],
        'qty' => $emptyCylinder['qty'],
        'created_at' => date("d-m-Y h:i:s", strtotime($emptyCylinder['created_at'])),
    ];
}

// Output the data in JSON format
$response = array(

        "draw" => intval($draw),

        "iTotalRecords" =>  $totalRecords,

        "iTotalDisplayRecords" => $totalRecords,

        "data" => $data,

      );

      echo json_encode($response);
// echo json_encode(['data' => $data]);

?>
