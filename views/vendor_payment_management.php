<?php
require '../config/db.php';
require_once '../config/db_functions.php';
require '../config/auth.php';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Vendor Payment Management</title>
    <?php include '../libs/links.php'; ?>
</head>


<body>
    <?php include '../libs/sidebar.php'; ?>

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-md-12">
                <header><h1 class="text-white mb-0">Payment Management</h1></header>
                <div class="table-container">
                    <div class="d-flex justify-content-start mt-2 mb-2 float-right">
                      <button class="btn btn-success" data-toggle="modal" data-target="#addPaymentModal"><i class="fas fa-plus"></i> Add New Payment</button>
                  </div>
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
<?php include('../libs/jslinks.php'); ?>
<script>
    $(document).ready(function() {
        var paymentTable = $('#paymentTable').DataTable({
            "ajax": "fetch_payments.php",
            "columns": [
                { "data": "vendor_name" },
                { "data": "total_cash" },
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
