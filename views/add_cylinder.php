<?php
// views/add_cylinder.php
require_once '../config/db.php';
require_once '../config/db_functions.php';
require '../config/auth.php';

// Fetch vendors for the dropdown
$vendors = fetchAllVendors();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Cylinder</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="../css/styles.css">
    <style>
    .mt-4 .btn {
        margin-right: 10px;
    }
    .card-body {
    display: flex;
    align-items: center;
    justify-content: center;
  }

  body {
      padding-top: 56px; /* Add padding to the top to accommodate the fixed navbar */
      padding-left: 250px; /* Add padding to the left to accommodate the sidebar */
  }

  .sidebar {
      position: fixed;
      left: 0;
      top: 56px; /* Adjust to match the navbar height */
      bottom: 0;
      width: 250px;
      padding-top: 60px; /* Adjust as needed */
      background-color: #343a40; /* Dark background color */
      overflow-y: auto; /* Add scrollbar if content overflows */
      z-index: 1000; /* Ensure sidebar is above other content */
  }

  .admin-links {
      list-style-type: none;
      padding: 0;
      margin: 0;
  }

  .admin-link {
      padding: 15px 20px;
      color: #ffffff;
      text-decoration: none;
      display: block;
      transition: background-color 0.3s ease;
  }

  .admin-link:hover {
      background-color: #495057; /* Darker background color on hover */
  }

  .admin-link.active {
      background-color: #adb5bd; /* Active link background color */
  }

  .admin-link i {
      margin-right: 10px; /* Add space between icon and text */
  }

  .sidebar-header {
      padding: 10px 20px;
      color: #ffffff;
      text-align: center;
      background-color: #212529; /* Header background color */
  }

  .sidebar-logo {
      margin-bottom: 20px; /* Add space below the logo */
  }

  .sidebar-logo img {
      width: 80%; /* Adjust logo size as needed */
      display: block;
      margin: 0 auto; /* Center the logo */
  }

  .content {
      padding: 20px;
  }

  /* Mobile styles */
  @media (min-width: 302px) and (max-width: 1023px) {
    body {
      padding-top: 0px;
      padding-left: 0px;
    }
    .sidebar{
      display: none;
    }

    /* Mobile navbar toggler styles */
    .navbar-toggler.sidebar-open {
      background-color: white !important;
      border-color: #dee2e6;
    }

    .navbar-toggler.sidebar-open .navbar-toggler-icon {
      background-image: none;
      font-size: 1.5rem;
      font-weight: bold;
      color: #333;
      display: flex;
      align-items: center;
      justify-content: center;
      width: 100%;
      height: 100%;
    }

    .navbar-toggler .navbar-toggler-icon {
      transition: all 0.3s ease;
    }
  }
</style>
<script>
    function toggleNewVendor() {
        var newVendorSection = document.getElementById('newVendorSection');
        newVendorSection.style.display = newVendorSection.style.display === 'none' ? 'block' : 'none';
    }
</script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light fixed-top">
        <a class="navbar-brand" href="#">Cylinder Management</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
    </nav>

    <div class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <!--img src="your-logo.png" alt="Logo"-->
            </div>
        </div>
        <ul class="admin-links">
            <li><a href="dashboard.php" class="admin-link">Dashboard</a></li>
            <li><a href="customer_management.php" class="admin-link">Customer Management</a></li>
            <li><a href="vendor_management.php" class="admin-link">Vendor Management</a></li>
            <li><a href="cylinder_management.php" class="admin-link">Cylinder Management</a></li>
            <li><a href="transaction_management.php" class="admin-link">Transaction Management</a></li>
            <li><a href="pending_payments.php" class="admin-link">Pending Payments</a></li>
            <li><a href="vendor_payment_management.php" class="admin-link">Venodr Payment Management</a></li>
            <li><a href="pending_refills.php" class="admin-link">Pending Refils</a></li>
            <li><a href="inactive_customers.php" class="admin-link">Inactive Customers</a></li>

            <!-- Add more admin options here -->
        </ul>
        <div class="logout-btn">
            <a href="../logout.php" class="btn btn-danger btn-block">Logout</a>
        </div>
    </div>

    <div class="container">
        <header><h1>Add New Cylinder</h1></header>
        <form action="../add_cylinder_process.php" method="POST">
            <div class="form-group">
                <label for="vendor_id">Vendor</label>
                <select class="form-control" id="vendor_id" name="vendor_id" required>
                    <option value="">Select Vendor</option>
                    <?php foreach ($vendors as $vendor): ?>
                        <option value="<?php echo $vendor['id']; ?>"><?php echo htmlspecialchars($vendor['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <small><a href="javascript:void(0);" onclick="toggleNewVendor()">Add New Vendor</a></small>
            </div>
            <div id="newVendorSection" style="display: none;">
                <div class="form-group">
                    <label for="new_vendor_name">New Vendor Name</label>
                    <input type="text" class="form-control" id="new_vendor_name" name="new_vendor_name">
                </div>
                <div class="form-group">
                    <label for="new_vendor_contact">New Vendor Contact</label>
                    <input type="text" class="form-control" id="new_vendor_contact" name="new_vendor_contact">
                </div>
            </div>
            <div class="form-group">
                <label for="gas_type">Gas Type</label>
                <input type="text" class="form-control" id="gas_type" name="gas_type" required>
            </div>
            <div class="form-group">
                <label for="total_amount">Total Amount</label>
                <input type="number" class="form-control" id="total_amount" name="total_amount" required>
            </div>
            <div class="form-group">
                <label for="available_amount">Available Amount</label>
                <input type="number" class="form-control" id="available_amount" name="available_amount" required>
            </div>
            <div class="form-group">
                <label for="empty_cylinders">Empty Cylinders</label>
                <input type="number" class="form-control" id="empty_cylinders" name="empty_cylinders" required>
            </div>
            <div class="form-group">
                <label for="amount_paid">Amount Paid</label>
                <input type="number" step="0.01" class="form-control" id="amount_paid" name="amount_paid" required>
            </div>
            <button type="submit" class="btn btn-primary">Add Cylinder</button>
            <button type="button" class="btn btn-secondary" onclick="window.history.back()">Back</button>
        </form>
    </div>
    <?php include('../libs/jslinks.php'); ?>
</body>
</html>
