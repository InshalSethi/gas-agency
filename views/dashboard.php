<?php
// views/dashboard.php
session_start();
require '../config/auth.php';
require '../config/db.php';
require_once '../config/db_functions.php';


$pendingRefills = fetchPendingRefills();
$inactiveCustomers = fetchInactiveCustomers();
$pendingPayments = fetchPendingPayments();
$allCustomers = fetchAllCustomers();

$pendingRefillsCount = count($pendingRefills);
$inactiveCustomersCount = count($inactiveCustomers);
$pendingPaymentsCount = count($pendingPayments);
$allCustomersCount = count($allCustomers);

// Query to get cylinder data
$query = "SELECT * FROM cylinders";
$result = $conn->query($query);

// Query to get total cylinders by gas type
$totalQuery = "SELECT gas_type, SUM(total_amount) as total_cylinders, SUM(available_amount) as available_cylinders FROM cylinders GROUP BY gas_type";
$totalResult = $conn->query($totalQuery);

// Query to get the sum of all cylinders
$sumQuery = "SELECT SUM(total_amount) as total_cylinders, SUM(available_amount) as available_cylinders FROM cylinders";
$sumResult = $conn->query($sumQuery);
$sumRow = $sumResult->fetch_assoc();
$sum_e_Query = "SELECT SUM(empty_cylinders) as total_empty_cylinders, SUM(available_amount) as empty_cylinders FROM cylinders";
$sum_e_Result = $conn->query($sum_e_Query);
$sum_e_Row = $sum_e_Result->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
  <title>Dashboard</title>
  <?php include '../libs/links.php'; ?>
</head>
<body>
  

  <?php include '../libs/sidebar.php'; ?>

  <div class="container-fluid mt-4">
    <div class="row">
      <div class="col-md-12">
        <h1>Dashboard</h1>
        <div class="row">
          <div class="col-md-4 mb-4">
            <div class="card total-cylinders">
              <div class="card-header">Total Cylinders</div>
              <div class="card-body">
                <h5 class="card-title"><?php echo $sumRow['total_cylinders']; ?></h5>
              </div>
            </div>
          </div>

          <?php
                  // Determine class for empty cylinders container
          $emptyCylindersClass = ($sum_e_Row['total_empty_cylinders'] > 50) ? 'bg-danger' : 'bg-secondary';
          ?>
          <div class="col-md-4 mb-4">
            <div class="card empty-cylinders <?php echo $emptyCylindersClass; ?>">
              <div class="card-header">Empty Cylinders</div>
              <div class="card-body">
                <h5 class="card-title"><?php echo $sum_e_Row['total_empty_cylinders']; ?></h5>
              </div>
            </div>
          </div>
          <div class="col-md-4 mb-4">
            <div class="card total-customers">
              <div class="card-header">Total Customers</div>
              <div class="card-body">
                <h5 class="card-title"><a href="customer_management.php"><?php echo $allCustomersCount; ?></a></h5>
                <p class="mb-0">Manage customer details</p>
              </div>
            </div>
          </div>
          <div class="col-md-4 mb-4">
            <div class="card pending-refills">
              <div class="card-header">Pending Refills</div>
              <div class="card-body">
                <h5 class="card-title"><a href="pending_refills.php"><?php echo $pendingRefillsCount; ?></a></h5>
                <p class="mb-0">Process pending refills</p>
              </div>
            </div>
          </div>
          <div class="col-md-4 mb-4">
            <div class="card pending-payments">
              <div class="card-header">Pending Payments</div>
              <div class="card-body">
                <h5 class="card-title"><a href="pending_payments.php"><?php echo $pendingPaymentsCount; ?></a></h5>
                <p class="mb-0">Review pending payments</p>
              </div>
            </div>
          </div>
          <div class="col-md-4 mb-4">
            <div class="card inactive-customers">
              <div class="card-header">Inactive Customers</div>
              <div class="card-body">
                <h5 class="card-title"><a href="inactive_customers.php"><?php echo $inactiveCustomersCount; ?></a></h5>
                <p class="mb-0">Manage inactive accounts</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- Profile Modal for changing password -->
  <div class="modal fade" id="profileModal" tabindex="-1" aria-labelledby="profileModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <form id="changePasswordForm">
          <div class="modal-header">
            <h5 class="modal-title" id="profileModalLabel">Change Password</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <div class="form-group">
              <label for="current_password">Current Password</label>
              <input type="password" id="current_password" name="current_password" class="form-control" required>
            </div>
            <div class="form-group">
              <label for="new_password">New Password</label>
              <input type="password" id="new_password" name="new_password" class="form-control" required>
            </div>
            <div class="form-group">
              <label for="confirm_password">Confirm New Password</label>
              <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
            </div>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-primary">Change Password</button>
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script>
    $(document).ready(function() {
            // Fetch user details and populate modal
      $.get('../fetch_user_details.php', function(user) {
                // Populate form with user details
                $('#current_username').val(user.username); // Assuming you have an input with id 'current_username' for displaying username
              });

            // Change Password Form
      $('#changePasswordForm').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        $.post('change_password.php', formData, function(response) {
          $('#profileModal').modal('hide');
          $('#changePasswordForm')[0].reset();
                    alert(response.message); // Show a success message or handle the response as needed
                    // Optionally, logout user after password change
                    window.location.href = '../logout.php';
                  }).fail(function(xhr, status, error) {
                    var errorMessage = xhr.status + ': ' + xhr.statusText;
                    alert('Error - ' + errorMessage);
                  });
                });
    });
  </script>
</body>

</html>
