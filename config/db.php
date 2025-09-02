<?php
session_start();
include('MysqliDb.php');
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cylinder";

$conn = new mysqli($servername, $username, $password, $dbname);
$db = new MysqliDb ($servername, $username, $password, $dbname);
?>