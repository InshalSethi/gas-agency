<?php
session_start();
include('MysqliDb.php');
$servername = "localhost";
$username = "sultangr_cylinder_sys_root";
$password = "1d8AtZRo?RRA";
$dbname = "sultangr_cylinder_sys";

$conn = new mysqli($servername, $username, $password, $dbname);
$db = new MysqliDb ($servername, $username, $password, $dbname);
?>