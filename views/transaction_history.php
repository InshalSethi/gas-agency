<?php
// views/transaction_history.php
require_once '../config/db.php';

$customer_id = $_GET['customer_id'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Transaction History</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.21/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.5/css/responsive.bootstrap4.min.css">
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

  <div class="container mt-5">
      <header><h1 class="text-center">Transaction History</h1></header>
      <div class="table-responsive">
          <table id="transactionTable" class="table table-striped table-bordered">
              <thead class="thead-dark">
                  <tr>
                      <th>Transaction Date</th>
                      <th>Transaction Type</th>
                      <th>Cylinder Count</th>
                      <th>Amount</th>
                      <th>Balance</th>
                  </tr>
              </thead>
              <tbody>
                  <!-- Data will be populated by DataTables -->
              </tbody>
          </table>
      </div>
      <div class="text-center mt-3">
          <button class="btn btn-primary" onclick="window.history.back()">Back</button>
      </div>
  </div>

  <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
  <script src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.10.21/js/dataTables.bootstrap4.min.js"></script>
  <script src="https://cdn.datatables.net/responsive/2.2.5/js/dataTables.responsive.min.js"></script>
  <script src="https://cdn.datatables.net/responsive/2.2.5/js/responsive.bootstrap4.min.js"></script>
  <script>
  $(document).ready(function() {
      $('#transactionTable').DataTable({
          "processing": true,
          "serverSide": true,
          "responsive": true,
          "ajax": {
              "url": "get_transactions.php",
              "type": "GET",
              "data": {
                  "customer_id": "<?php echo $customer_id; ?>"
              }
          },
          "columns": [
              { "data": "transaction_date" },
              { "data": "transaction_type" },
              { "data": "cylinder_count" },
              { "data": "amount" },
              { "data": "balance" }
          ],
          "order": [[ 0, 'desc' ]],  // Order by transaction date descending
          "pageLength": 10           // Set default page length
      });
  });
  </script>
</body>
</html>
