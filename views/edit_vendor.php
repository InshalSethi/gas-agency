<?php
require_once '../config/db.php';
require_once '../config/db_functions.php';
require '../config/auth.php';

if (!isset($_GET['id'])) {
    header("Location: vendor_management.php");
    exit();
}

$vendor_id = $_GET['id'];
$vendor = fetchVendorById($vendor_id);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $contact_info = $_POST['contact_info'];
    $address = $_POST['address'];

    $stmt = $conn->prepare("UPDATE vendors SET name = ?, contact_info = ?, address = ? WHERE id = ?");
    $stmt->bind_param("sssi", $name, $contact_info, $address, $vendor_id);

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
    <title>Edit Vendor</title>
    <?php include '../libs/links.php'; ?>

</head>
<body>
  <?php include '../libs/sidebar.php'; ?>

<div class="container-fluid mt-4">
    <h1>Edit Vendor</h1>
    <form action="edit_vendor.php?id=<?php echo $vendor_id; ?>" method="POST">
        <div class="form-group">
            <label for="name">Vendor Name</label>
            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($vendor['name']); ?>" required>
        </div>
        <div class="form-group">
            <label for="contact_info">Contact Info</label>
            <textarea class="form-control" id="contact_info" name="contact_info" required><?php echo htmlspecialchars($vendor['contact_info']); ?></textarea>
        </div>
        <div class="form-group">
            <label for="address">Address</label>
            <textarea class="form-control" id="address" name="address" required><?php echo htmlspecialchars($vendor['address']); ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Update Vendor</button>
        <a href="vendor_management.php" class="btn btn-secondary">Back</a>
    </form>
</div>
<?php include('../libs/jslinks.php'); ?>
</body>
</html>
