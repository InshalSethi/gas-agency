<?php
// views/dashboard.php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Create Transaction</title>
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

    <div class="container">
        <header><h1>Create Transaction</h1></header>

        <?php
        if (!isset($_GET['customer_id'])) {
            echo "No customer selected.";
            exit;
        }
        $customer_id = $_GET['customer_id'];
        require_once '../config/db.php';
        require_once '../config/db_functions.php';
        $customer = fetchCustomerById($customer_id);
        if (!$customer) {
            echo "Customer not found.";
            exit;
        }

        $hasActiveRentalsOrPurchases = hasActiveRentalsOrPurchases($customer_id);
        ?>

        <form method="POST" action="process_transaction.php">
            <input type="hidden" name="customer_id" value="<?php echo $customer_id; ?>">
            <div class="form-group">
                <label for="customer_name">Customer Name:</label>
                <input type="text" id="customer_name" name="customer_name" value="<?php echo $customer['name']; ?>" readonly>
            </div>
            <div class="form-group">
                <label for="transaction_type">Transaction Type:</label>
                <select id="transaction_type" name="transaction_type" required onchange="toggleFields(this.value)">
                    <option value="Purchase">Purchase</option>
                    <option value="Rental">Rental</option>
                    <option value="Refill">Refill</option>
                    <option value="Return">Return</option>
                </select>
            </div>
            <div class="form-group">
                <label for="gas_type">Select Gas Type:</label>
                <select id="gas_type" name="gas_type">
                    <?php
                    $gas_types = fetchGasTypes();
                    foreach ($gas_types as $gas) {
                        echo "<option value='{$gas['gas_type']}'>{$gas['gas_type']} - Available: {$gas['available_amount']}</option>";
                    }
                    ?>
                </select>
                <button type="button" class="btn btn-primary" onclick="addCylinder()">Add Cylinder</button>
            </div>
            <div class="form-group">
                <label for="selected_cylinders">Selected Cylinders:</label>
                <ul id="selected_cylinders"></ul>
            </div>
            <div class="form-group">
                <label for="amount">Amount:</label>
                <input type="number" id="amount" name="amount" step="0.01" required>
            </div>
            <div class="form-group">
                <label for="balance">Balance:</label>
                <input type="number" id="balance" name="balance" step="0.01" required>
            </div>
            <button type="submit" class="btn btn-primary">Create Transaction</button>
            <button type="button" class="btn btn-secondary" onclick="window.history.back()">Back</button>
        </form>
    </div>

    <script>
        let selectedCylinders = [];

        function addCylinder() {
            const gasTypeSelect = document.getElementById('gas_type');
            const selectedGasType = gasTypeSelect.value;
            const selectedGasText = gasTypeSelect.options[gasTypeSelect.selectedIndex].text;

            if (selectedGasType) {
                selectedCylinders.push(selectedGasType);
                const listItem = document.createElement('li');
                listItem.textContent = selectedGasText;
                document.getElementById('selected_cylinders').appendChild(listItem);
            }
        }

        function toggleFields(value) {
            const gasSelection = document.getElementById('gas_selection');
            const cylinderCount = document.getElementById('cylinder_count');
            const amount = document.getElementById('amount');
            const balance = document.getElementById('balance');

            if (value === 'Return') {
                gasSelection.style.display = 'none';
                cylinderCount.required = false;
                amount.required = false;
                balance.required = false;
            } else {
                gasSelection.style.display = 'block';
                cylinderCount.required = true;
                amount.required = true;
                balance.required = true;
            }
        }

        document.getElementById('paymentForm').addEventListener('submit', function(event) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'selected_cylinders';
            input.value = JSON.stringify(selectedCylinders);
            this.appendChild(input);
        });
    </script>
</body>
</html>
