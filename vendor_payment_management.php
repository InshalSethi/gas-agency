<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Vendor Payment Management</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.24/css/jquery.dataTables.min.css">
    <link rel="stylesheet" type="text/css" href="../css/styles.css">

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
</head>

<style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f8f9fa; /* Light grey background */
}
.container {
    max-width: 1200px;
    margin: auto;
    padding: 20px;
}

h1 {
    font-size: 2.5rem;
    font-weight: bold;
    color: #343a40; /* Dark grey text */
    margin-bottom: 30px;
    text-align: center;
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

</style>

<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light fixed-top">
        <a class="navbar-brand" href="#">Payment Management</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
    </nav>

    <div class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
            </div>
        </div>
        <ul class="admin-links">
            <li><a href="dashboard.php" class="admin-link">Dashboard</a></li>
            <li><a href="customer_management.php" class="admin-link">Customer Management</a></li>
            <li><a href="cylinder_management.php" class="admin-link">Cylinder Management</a></li>
            <li><a href="transaction_management.php" class="admin-link">Transaction Management</a></li>
            <li><a href="pending_payments.php" class="admin-link">Pending Payments</a></li>
            <li><a href="pending_refills.php" class="admin-link">Pending Refils</a></li>
            <li><a href="inactive_customers.php" class="admin-link">Inactive Customers</a></li>
            <li><a href="vendor_payment_management.php" class="admin-link">Venodr Payment Management</a></li>
        </ul>
        <div class="logout-btn">
            <a href="../logout.php" class="btn btn-danger btn-block">Logout</a>
        </div>
    </div>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <h1 class="mb-4">Payment Management</h1>
                <table id="paymentTable" class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>Vendor Name</th>
                            <th>Total Cash Amount</th>
                            <th>Cash Paid</th>
                            <th>Remaining Balance</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                </table>
                <div class="d-flex justify-content-start mb-4">
                    <button class="btn btn-primary" data-toggle="modal" data-target="#addPaymentModal"><i class="fas fa-plus"></i> Add New Payment</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add New Payment Modal -->
    <div class="modal fade" id="addPaymentModal" tabindex="-1" aria-labelledby="addPaymentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="addPaymentForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addPaymentModalLabel">Add New Payment</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="vendorSelect">Select Vendor</label>
                            <select class="form-control" id="vendorSelect" name="vendor_id" required>
                                <option value="">Select Vendor</option>
                                <?php
                                $vendor_query = "SELECT id, name FROM vendors";
                                $vendor_result = $conn->query($vendor_query);
                                while ($vendor_row = $vendor_result->fetch_assoc()) {
                                    echo "<option value='{$vendor_row['id']}'>{$vendor_row['name']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="totalCashAmount">Total Cash Amount</label>
                            <input type="number" class="form-control" id="totalCashAmount" name="total_cash_amount" required>
                        </div>
                        <div class="form-group">
                            <label for="cashPaid">Cash Paid</label>
                            <input type="number" class="form-control" id="cashPaid" name="cash_paid" required>
                        </div>
                        <div class="form-group">
                            <label for="remainingBalance">Remaining Balance</label>
                            <input type="number" class="form-control" id="remainingBalance" name="remaining_balance" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            var paymentTable = $('#paymentTable').DataTable({
                "ajax": "fetch_payments.php",
                "columns": [
                    { "data": "vendor_name" },
                    { "data": "total_cash_amount" },
                    { "data": "cash_paid" },
                    { "data": "remaining_balance" },
                    {
                        "data": "vendor_id", // Include the vendor_id here
                        "render": function(data, type, row) {
                            return `<a href="edit_payment.php?id=${row.id}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                    <a class='btn btn-sm btn-outline-info view-history' href="vendor_transaction_history.php?vendor_id=${data}">View History</a>
                                    <a href="delete_payment.php?id=${row.id}" class="btn btn-sm btn-outline-danger">Delete</a>`;
                        }
                    }
                ]
            });

            $('#addPaymentForm').on('submit', function(event) {
                event.preventDefault();
                $.ajax({
                    url: '../add_payment_process.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        $('#addPaymentModal').modal('hide');
                        $('#addPaymentForm')[0].reset();
                        paymentTable.ajax.reload();
                    }
                });
            });
        });
    </script>
</body>
</html>
