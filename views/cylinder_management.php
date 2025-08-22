<?php
// views/cylinder_management.php
require '../config/db.php';
require_once '../config/db_functions.php';
require '../config/auth.php';

// Fetch vendors from the database
$vendors = [];
$vendorResult = $conn->query("SELECT id, name FROM vendors WHERE deleted= 0");
if ($vendorResult->num_rows > 0) {
    while ($row = $vendorResult->fetch_assoc()) {
        $vendors[] = $row;
    }
}
// Fetch gas types from the cylinders table
$cylinder_query = "SELECT id, name FROM cylinders";
$cylinder_result = $conn->query($cylinder_query);
$gas_types = [];
while ($row = $cylinder_result->fetch_assoc()) {
    $gas_types[] = $row;
}

?>

<!DOCTYPE html>
<html>
<head>
  <title>Cylinder Management</title>
  <?php include '../libs/links.php'; ?>
</head>

<body>
  <?php include '../libs/sidebar.php'; ?>

  <div class="container-fluid mt-4">
      <div class="row">
          <div class="col-md-12">
              <header><h1 class="text-white mb-0">Cylinders</h1></header>
              <div class="row d-flex justify-content-center">
                  <div class="card-deck summary-item">
                      <!-- Your summary cards here -->
                  </div>
              </div>
          </div>
      </div>
      <div class="table-container">
        <div class="mt-2 mb-2">
                    <div class="row">

                      <div class="col-md-2">
                        <select id="cylinderFilter" class="form-control filter mt-2">
                          <option value="">Select Cylinder By</option>
                          <option value="empty_exist">Empty Exist</option>
                          <option value="empty_finished">Empty Finish</option>
                          <option value="qty_exist">Stock Exist</option>
                          <option value="qty_finished">Stock Finish</option>
                        </select>
                      </div>
                      <div class="col-md-6"></div>
                      <div class="col-md-2">
                        <button class="btn btn-primary mt-2 w-100" id="resetFilterBtn"><i class="fa fa-refresh"></i> Reset Filter</button>
                      </div>
                      <div class="col-md-2">
                        <button class="btn btn-success mt-2 w-100" data-toggle="modal" data-target="#addCylinderModal"><i class="fas fa-plus"></i> Add New Cylinder</button>
                      </div>
                    </div>
                  </div>
      <table id="cylinderTable" class="table table-striped table-bordered">
          <thead class="thead-dark">
              <tr>
                <th>ID</th>
                  <th>Name</th>
                  <th>Retail Rate</th>
                  <th>Empty</th>
                  <th>QTY</th>
                  <th>Created At</th>
                  <th>Actions</th>
              </tr>
          </thead>
      </table>
    </div>
  </div>

  <!-- Add New Cylinder Modal -->
  <div class="modal fade" id="addCylinderModal" tabindex="-1" aria-labelledby="addCylinderModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="addCylinderForm" method="POST" action="../add_cylinder_process.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCylinderModalLabel">Add New Cylinder</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- <div class="form-group">
                        <label for="gasType">Gas Type</label>
                        <select class="form-control" id="gasType" name="gas_type" required>
                          <option value=""></option>
                          <?php foreach ($gas_types as $gas_type) { ?>
                              <option value="<?php echo $gas_type['id']; ?>"><?php echo $gas_type['name']; ?></option>
                          <?php } ?>
                          <option value="new">Add New Gas Type</option>
                        </select>
                    </div> -->
                    <div class="form-group" id="newGasType">
                        <label for="newGasType">Name</label>
                        <input type="text" class="form-control" id="newGasType" name="name" required>
                    </div>
                    <!-- <div class="form-group">
                        <label for="vendor">Vendor</label>
                        <select class="form-control" id="vendor" name="vendor_id" required>
                          <option value=""></option>
                            <?php foreach ($vendors as $vendor) { ?>
                                <option value="<?php echo $vendor['id']; ?>"><?php echo $vendor['name']; ?></option>
                            <?php } ?>
                            <option value="new">Add New Vendor</option>
                        </select>
                    </div> 
                    <div class="form-group" id="newVendorField" style="display: none;">
                        <label for="newVendorName">New Vendor Name</label>
                        <input type="text" class="form-control" id="newVendorName" name="new_vendor_name">
                        <label for="newVendorContact">Contact No</label>
                        <input type="text" class="form-control" id="newVendorContact" name="new_vendor_contact">
                        <label for="newVendorAddress">Address</label>
                        <input type="text" class="form-control" id="newVendorAddress" name="new_vendor_address">
                    </div>-->
                    <div class="form-group">
                        <label for="retailRate">Retail Rate</label>
                        <input type="number" class="form-control" id="retailRate" name="retail_rate" value="0" required>
                    </div>
                    <div class="form-group">
                        <label for="supplierRate">Supplier Rate</label>
                        <input type="number" class="form-control" id="supplierRate" name="supplier_rate" value="0" required>
                    </div>
                    <div class="form-group">
                        <label for="empty_quantity">Empty QTY</label>
                        <input type="number" class="form-control" id="empty_quantity" name="empty_quantity" value="0" required>
                    </div>
                    <div class="form-group">
                        <label for="quantity">Quantity</label>
                        <input type="number" class="form-control" id="quantity" name="quantity" value="0" required>
                    </div>
                    <!-- <div class="form-group">
                        <label for="cashPaid">Cash Paid</label>
                        <input type="number" class="form-control" id="cashPaid" name="cash_paid" required>
                    </div>
                    <div class="form-group">
                        <label for="remainingBalance">Remaining Balance</label>
                        <input type="number" class="form-control" id="remainingBalance" name="remaining_balance" readonly>
                    </div>
                    <div class="form-group">
                        <label for="totalAmount">Total Cylinders</label>
                        <input type="number" class="form-control" id="totalAmount" name="total_amount" required>
                    </div>
                    <div class="form-group">
                        <label for="availableAmount">Available Cylinders</label>
                        <input type="number" class="form-control" id="availableAmount" name="available_amount" required>
                    </div>
                    <div class="form-group">
                        <label for="emptyCylinders">Empty Cylinders</label>
                        <input type="number" class="form-control" id="emptyCylinders" name="empty_cylinders" required>
                    </div> -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add Cylinder</button>
                </div>
            </form>
        </div>
    </div>
  </div>
<?php include('../libs/jslinks.php'); ?>
  <script>
  $(document).ready(function() {
      // $('#vendor').change(function() {
      //     if ($(this).val() === 'new') {
      //         $('#newVendorField').show();
      //     } else {
      //         $('#newVendorField').hide();
      //     }
      // });
      //     $('#gasType').change(function() {
      //         if ($(this).val() === 'new') {
      //             $('#newGasType').show();
      //         } else {
      //             $('#newGasType').hide();
      //         }
      //     });

      //     $('#cashPaid, #totalCashAmount').on('input', function() {
      //         var totalCashAmount = parseFloat($('#totalCashAmount').val()) || 0;
      //         var cashPaid = parseFloat($('#cashPaid').val()) || 0;
      //         var remainingBalance = totalCashAmount - cashPaid;
      //         $('#remainingBalance').val(remainingBalance);
      //     });

          var cylinderTable = $('#cylinderTable').DataTable({
                processing: true,
                serverSide: true,
                "paging":   true,
                "iDisplayLength": 100,
              "ajax": {
          "url": "fetch_cylinders.php",
          "type": "GET",
          data: function(d) {
            d.cylinder_filter = $('#cylinderFilter').val()
          }
        },
              "columns": [
                { "data": "id" },
                  { "data": "name" },
                  { "data": "retail_rate" },
                  { "data": "empty_qty" },
                  { "data": "qty" },
                  { "data": "created_at" },
                  { "data": "actions" }
                  // {
                  //     "data": "id",
                  //     "render": function(data, type, row) {
                  //         return `<a href="edit_cylinder.php?id=${data}" class="btn btn-sm btn-outline-secondary">Edit</a>
                  //                 <a href="delete_cylinder.php?id=${data}" class="btn btn-sm btn-outline-danger">Delete</a>`;
                  //     }
                  // }
              ]
          });
          $('input[type="search"],filter').keyup(function (e) {
            e.preventDefault();
            cylinderTable.draw();
          });

          $('.filter').change(function (e) {
            e.preventDefault();
            cylinderTable.draw();
          });
          $('#resetFilterBtn').click(function() {
              // Reset date range picker to default values
            $('#cylinderFilter').val(null)
            
              // Reload the DataTable
            cylinderTable.draw();
          });

          $('#addCylinderForm').on('submit', function(event) {
              event.preventDefault();
              $.ajax({
                  url: '../add_cylinder_process.php',
                  type: 'POST',
                  data: $(this).serialize(),
                  success: function(response) {
                      $('#addCylinderModal').modal('hide');
                      $('#addCylinderForm')[0].reset();
                      cylinderTable.ajax.reload();
                  }
              });
          });
      });
  </script>
</body>
</html>
