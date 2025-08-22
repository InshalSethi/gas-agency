<?php
require_once '../config/db.php';
require_once '../config/db_functions.php';
require '../config/auth.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $contact_info = $_POST['contact_info'];
    $address = $_POST['address'];

    $stmt = $conn->prepare("INSERT INTO vendors (name, contact_info, address) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $contact_info, $address);

    if ($stmt->execute()) {
        header("Location: vendor_management.php");
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Vendor</title>
    <?php include '../libs/links.php'; ?>

</head>
<body>
  <?php include '../libs/sidebar.php'; ?>

<div class="container-fluid mt-4">
    <h1>Add Vendor</h1>
    <form action="add_vendor.php" method="POST">
        <div class="form-group">
            <label for="name">Vendor Name</label>
            <input type="text" class="form-control" id="name" name="name" required>
        </div>
        <div class="form-group">
            <label for="contact_info">Contact Info</label>
            <textarea type="text" class="form-control" id="contact_info" name="contact_info" required></textarea>
        </div>
        <div class="form-group">
            <label for="contact_info">Address</label>
            <textarea type="text" class="form-control" id="address" name="address" required></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Add Vendor</button>
        <a href="vendor_management.php" class="btn btn-secondary">Back</a>
    </form>
</div>
<?php include('../libs/jslinks.php'); ?>
</body>
</html>
