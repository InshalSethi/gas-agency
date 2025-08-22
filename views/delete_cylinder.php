<?php
// views/delete_cylinder.php
// session_start();
require '../config/db.php';
require_once '../config/db_functions.php';
require '../config/auth.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$id = $_GET['id'];
$query = "SELECT * FROM cylinders WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$cylinder = $result->fetch_assoc();

if (!$cylinder) {
    echo "Cylinder not found.";
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Delete Cylinder</title>
    <link rel="stylesheet" type="text/css" href="../css/styles.css">
</head>
<body>
    <div class="container">
        <header><h1>Delete Cylinder</h1></header>
        <p>Are you sure you want to delete this cylinder?</p>
        <form action="../delete_cylinder_process.php" method="POST">
            <input type="hidden" name="id" value="<?php echo $cylinder['id']; ?>">
            <input type="submit" value="Delete Cylinder">
        </form>
        <a href="<?php echo baseurl('index.php'); ?>">Cancel</a>
    </div>
</body>
</html>
