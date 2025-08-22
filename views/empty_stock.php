<?php
// views/cylinder_management.php
require '../config/db.php';
require_once '../config/db_functions.php';
require '../config/auth.php';

if (isset($_REQUEST['reset'])) {
  $x=$_REQUEST['reset'];
  if ( $x != '')   {
    date_default_timezone_set("Asia/Karachi");
    $delDate =  date("Y-m-d h:i:s");

    $uparr = array("status"=>'completed',"deleted_at"=>$delDate);
    $db->where ("status", 'pending');
    $db->update('empty_cylinders',$uparr);
?>
<script>  window.location.href="empty_stock.php"; </script>
    <?php
  }    } 
  ?>

<!DOCTYPE html>
<html>
<head>
  <title>Empty Stock</title>
  <?php include '../libs/links.php'; ?>
</head>

<body>
  <?php include '../libs/sidebar.php'; ?>

  <div class="container-fluid mt-4">
      <div class="row">
          <div class="col-md-12">
              <header><h1 class="text-white mb-0">Empty Cylinders Stock</h1></header>
              <div class="row d-flex justify-content-center">
                  <div class="card-deck summary-item">
                      <!-- Your summary cards here -->
                  </div>
              </div>
          </div>
      </div>
      <div class="table-container">
        <div class="d-flex justify-content-start mt-2 mb-2 float-right">
              <button class="btn btn-secondary" onclick="resetEmptyCylinders()" style="cursor: pointer;"><i class="fas fa-refresh"></i> Reset</button>
            </div>
      <table id="emptycylinderTable" class="table table-striped table-bordered">
          <thead class="thead-dark">
              <tr>
                    <th>ID</th>
                    <th>Cylinder</th>
                    <th>QTY</th>
                    <th>Created At</th>
              </tr>
          </thead>
      </table>
    </div>
  </div>

<?php include('../libs/jslinks.php'); ?>
  <script>
    function resetEmptyCylinders() {
      var txt;
      var r = confirm(" Are you sure you want to reset?");
      if (r == true) { 

        window.location = "empty_stock.php?reset=all"; 

      } 

    }
  $(document).ready(function() {

          var emptycylinderTable = $('#emptycylinderTable').DataTable({
                processing: true,
                serverSide: true,
                "paging":   true,
                "iDisplayLength": 100,
              "ajax": "fetch_empty_stock.php",
              "columns": [
                    { "data": "id" },
                  { "data": "cylinder" },
                  { "data": "qty" },
                  { "data": "created_at" },
              ]
          });

          // Search form submission
        $('input[type="search"],filter').keyup(function (e) {
            e.preventDefault();
            emptycylinderTable.draw();
        });

         
      });
  </script>
</body>
</html>
