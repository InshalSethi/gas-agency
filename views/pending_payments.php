<?php
require '../config/db.php';
require_once '../config/db_functions.php';
require '../config/auth.php';
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Payments</title>
    <?php include '../libs/links.php'; ?>
</head>
<body>
  <?php include '../libs/sidebar.php'; ?>

    <div class="container-fluid mt-4">
        <header><h1 class="text-white mb-0">Pending Payments</h1></header>

        <div class="payment-form">
            <h2>Make a Payment</h2>
            <form id="paymentForm">
                <div class="form-group">
                    <label for="customer_select">Select Customer</label>
                    <select id="customer_select" class="form-control" required>
                        <option value="">Select Customer</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="customer_id">Customer ID</label>
                    <input type="text" class="form-control" id="customer_id" name="customer_id" readonly>
                </div>
                <div class="form-group">
                    <label for="pending_balance">Pending Balance</label>
                    <input type="text" class="form-control" id="pending_balance" name="pending_balance" readonly>
                </div>
                <div class="form-group">
                    <label for="amount">Amount to Pay</label>
                    <input type="number" class="form-control" id="amount" name="amount" required>
                </div>
                <button type="submit" class="btn btn-primary">Submit Payment</button>
            </form>
        </div>
    </div>
<?php include('../libs/jslinks.php'); ?>
    <script>
        $(document).ready(function () {
            // Initialize Select2
            $('#customer_select').select2({
                placeholder: 'Select a customer',
                ajax: {
                    url: 'fetch_pending_payments.php',
                    dataType: 'json',
                    processResults: function (data) {
                        return {
                            results: data.data.map(function (customer) {
                                return {
                                    id: customer.customer_id,
                                    text: customer.name,
                                    pending_balance: customer.pending_balance
                                };
                            })
                        };
                    }
                }
            });

            // Handle customer selection
            $('#customer_select').on('select2:select', function (e) {
                var data = e.params.data;
                $('#customer_id').val(data.id);
                $('#pending_balance').val(data.pending_balance);
            });

            // Handle form submission via Ajax
            $('#paymentForm').submit(function (e) {
                e.preventDefault();
                $.ajax({
                    url: 'process_payment.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function (response) {
                        alert('Payment submitted successfully');
                        $('#paymentForm')[0].reset();
                        $('#customer_select').val(null).trigger('change');
                        $('#customer_id').val('');
                        $('#pending_balance').val('');
                    }
                });
            });
        });
    </script>
</body>
</html>
