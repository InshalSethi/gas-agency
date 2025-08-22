<?php
// views/edit_cylinder.php
require '../config/db.php';
require_once '../config/db_functions.php';
require '../config/auth.php';

$id = $_GET['id'];
$db->where("id",$id);
$cylinder = $db->getOne("cylinders");
// var_dump($cylinder);die();
$name = $cylinder['name'];
$retailPrice = $cylinder['retail_rate'];
$supplierPrice = $cylinder['supplier_rate'];
$EmptyQty = $cylinder['empty_qty'];
$qty = $cylinder['qty'];

if (!$cylinder) {
    echo "Cylinder not found.";
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Cylinder</title>
    <?php include '../libs/links.php'; ?>
</head>
<body>
  <?php include '../libs/sidebar.php'; ?>

  <div class="container-fluid">
      <header>
          <h1 class="text-white mb-0">Edit Cylinder</h1>
      </header>
      <?php 
            if ( isset($_POST['save']) ) {

              $name=$_POST['name'];
              $retail_rate=$_POST['retail_rate'];
              $supplier_rate=$_POST['supplier_rate'];
              $empty_quantity=$_POST['empty_quantity'];
              $quantity=$_POST['quantity'];

              $time_zone = date_default_timezone_set("Asia/Karachi");
              $date = date("Y-m-d h:i:s");

              $ins_arr = array(
                "name"=>$name,
                "retail_rate"=>$retail_rate,
                "supplier_rate"=>$supplier_rate,
                "empty_qty"=>$empty_quantity,
                "qty"=>$quantity,
                "updated_at"=>$date,
              );

              $db->where("id",$id);
              // echo $db->getLastQuery();
              // var_dump($insert);die();
              
              if ($db->update("cylinders",$ins_arr)){
                echo "<div class='alert alert-fill-success' role='alert'><i class='mdi mdi-alert-circle'></i>Data Updated Successfully.</div>";
                ?>
                <script>window.location.href="<?php echo baseurl('views/cylinder_management.php'); ?>";</script>
                <?php
              }else{
                echo "<div class='alert alert-fill-danger' role='alert'><i class='mdi mdi-alert-circle'></i>Alert! Data Not Updated.</div>";
              }

            }
            ?>
      <form action="" method="POST">
          <input type="hidden" name="id" value="<?php echo htmlspecialchars($cylinder['id']); ?>">
          <div class="form-group">
              <label for="name">Name</label>
              <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($cylinder['name']); ?>" required>
          </div>
          <div class="form-group">
              <label for="retailRate">Retail Rate</label>
              <input type="number" class="form-control" id="supplierRate" name="retail_rate" value="<?php echo htmlspecialchars($retailPrice); ?>" required>
          </div>
          <div class="form-group">
              <label for="supplierRate">Supplier Rate</label>
              <input type="number" class="form-control" id="supplierRate" name="supplier_rate" value="<?php echo htmlspecialchars($supplierPrice); ?>" required>
          </div>
          <div class="form-group">
              <label for="empty_quantity">Empty QTY</label>
              <input type="number" class="form-control" id="empty_quantity" name="empty_quantity" value="<?php echo htmlspecialchars($EmptyQty); ?>" required>
          </div>
          <div class="form-group">
              <label for="quantity">Quantity</label>
              <input type="number" class="form-control" id="quantity" name="quantity" value="<?php echo htmlspecialchars($qty); ?>" required>
          </div>
          <button type="submit" name="save" class="btn btn-primary">Update Cylinder</button>
          <button type="button" class="btn btn-secondary" onclick="window.history.back()">Back</button>
      </form>
  </div>

  <?php include('../libs/jslinks.php'); ?>
</body>
</html>
