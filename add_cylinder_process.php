<?php
require 'config/db.php';


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $retail_rate = $_POST['retail_rate'];
    $supplier_rate = $_POST['supplier_rate'];
    $empty_quantity = $_POST['empty_quantity'];
    $quantity = $_POST['quantity'];
    $time_zone = date_default_timezone_set("Asia/Karachi");
    $date = date("Y-m-d h:i:s");
    $ins_arr = array(
        "name"=>$name,
        "retail_rate"=>$retail_rate,
        "supplier_rate"=>$supplier_rate,
        "empty_qty"=>$empty_quantity,
        "qty"=>$quantity,
        "created_at"=>$date,
    );

    $insert = $db->insert("cylinders",$ins_arr);
    
    header("Location: views/cylinder_management.php");
}
?>
