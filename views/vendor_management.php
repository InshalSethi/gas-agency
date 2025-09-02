<?php
require_once '../config/db.php';
require_once '../config/db_functions.php';
require '../config/auth.php';

if (isset($_REQUEST['id'])) {
  $x=$_REQUEST['id'];
  if ( $x != '')   {
    date_default_timezone_set("Asia/Karachi");
    $delDate =  date("Y-m-d h:i:s");

    $uparr = array("deleted_at"=>$delDate);
    $db->where("id",$x);
    $db->update('vendors',$uparr);
?>
<script>  window.location.href="vendor_management.php"; </script>
    <?php
  }    } 
  ?>

<!DOCTYPE html>
<html>
<head>
    <title>Vendor Management</title>
    <?php include '../libs/links.php'; ?>
</head>
<body>
  <?php include '../libs/sidebar.php'; ?>

  <div class="container-fuild m-4">
    <header><h4 class="text-white">Vendors</h4></header>
    <div class="table-container">
        <div class="d-flex justify-content-start mt-2 mb-2 float-right">
          <a href="add_vendor.php" class="btn btn-success" ><i class="fa fa-plus"></i> Add Vendor</a>
      </div>
      <table id="vendorTable" class="table table-bordered table-striped">
        <thead  class="thead-dark">
            <tr>
                <th>Name</th>
                <th>Contact Info</th>
                <th>Address</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $db->where("deleted_at", NULL, 'IS');
            $vendors=$db->get("vendors");
            foreach ($vendors as $vendor): ?>
                <tr>
                    <td><a href="vendor-ledger.php?id=<?php echo $vendor['id']; ?>" class="text-primary"><b><?php echo htmlspecialchars($vendor['name']); ?></b></a></td>
                    <td><?php echo htmlspecialchars($vendor['contact_info']); ?></td>
                    <td><?php echo htmlspecialchars($vendor['address']); ?></td>
                    <td>
                        <a href="edit_vendor.php?id=<?php echo $vendor['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                        <form action="vendor_management.php" method="POST" style="display:inline;">
                            <input type="hidden" name="vendor_id" value="<?php echo $vendor['id']; ?>">
                            <a  class="btn btn-danger btn-sm" onclick="deleteRow(<?php echo $vendor['id']; ?>)">Delete</a>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</div>
<?php include('../libs/jslinks.php'); ?>
<script>
    function deleteRow(clicked_id) {
          var txt;
          var r = confirm(" Are you sure to delete this?");
          if (r == true) { 
            txt = "You pressed OK!";
            
            var stateID = clicked_id;
            console.log(clicked_id);
            window.location = "vendor_management.php?id="+clicked_id; 
            
        } else {
            
            
        }
        
    }
    $(document).ready(function () {
        $('#vendorTable').DataTable();
    });
</script>
</body>
</html>
