<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require '../config/db.php';
require_once '../config/db_functions.php';
require '../config/auth.php';

$payment_id = $_GET['id'];

$query = "SELECT * FROM payments WHERE id = ?";
$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param("i", $payment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $payment = $result->fetch_assoc();
    // var_dump($payment); // Debugging output
} else {
    echo "Error preparing statement: " . $conn->error;
}
// die();
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $vendor_id = $_POST['vendor_id'];
    $total_cash_amount = $_POST['total_cash_amount'];
    $cash_paid = $_POST['cash_paid'];
    $remaining_balance = $total_cash_amount - $cash_paid;

    $update_query = "UPDATE payments SET vendor_id = ?, total_cash_amount = ?, cash_paid = ?, remaining_balance = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_query);
    if ($update_stmt) {
        $update_stmt->bind_param("iiiii", $vendor_id, $total_cash_amount, $cash_paid, $remaining_balance, $payment_id);

        if ($update_stmt->execute()) {
            header("Location: vendor_payment_management.php");
        } else {
            echo "Error: " . $update_stmt->error;
        }
        $update_stmt->close();
    } else {
        echo "Error preparing update statement: " . $conn->error;
    }

    // Insert into vendor_transaction_history table
    $stmt = $conn->prepare("INSERT INTO vendor_transaction_history (vendor_id, total_amount, amount_paid, remaining_amount) VALUES (?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("iiii", $vendor_id, $total_cash_amount, $cash_paid, $remaining_balance);

        if ($stmt->execute()) {
            header("Location: vendor_payment_management.php");
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "Error preparing insert statement: " . $conn->error;
    }
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Payment</title>
    <?php include '../libs/links.php'; ?>
    <script>
        $(document).ready(function() {
            function calculateRemainingBalance() {
                var totalCashAmount = parseFloat($('#totalCashAmount').val()) || 0;
                var cashPaid = parseFloat($('#cashPaid').val()) || 0;
                var remainingBalance = totalCashAmount - cashPaid;
                $('#remainingBalance').val(remainingBalance);
            }

            $('#totalCashAmount, #cashPaid').on('input', calculateRemainingBalance);
            calculateRemainingBalance(); // Initial calculation
        });
    </script>
</head>
<body>
  <?php include '../libs/sidebar.php'; ?>

    <div class="container-fluid mt-4">
        <h1 class="mb-4">Edit Payment</h1>
        <form method="POST" action="">
            <div class="form-group">
                <label for="vendorSelect">Select Vendor</label>
                <select class="form-control" id="vendorSelect" name="vendor_id" required>
                    <option value="">Select Vendor</option>
                    <?php
                    $vendor_query = "SELECT id, name FROM vendors";
                    $vendor_result = $conn->query($vendor_query);
                    while ($vendor_row = $vendor_result->fetch_assoc()) {
                        $selected = $vendor_row['id'] == $payment['vendor_id'] ? 'selected' : '';
                        echo "<option value='{$vendor_row['id']}' $selected>{$vendor_row['name']}</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label for="totalCashAmount">Total Cash Amount</label>
                <input type="number" class="form-control" id="totalCashAmount" name="total_cash_amount" value="<?php echo $payment['total_cash']; ?>" required>
            </div>
            <div class="form-group">
                <label for="cashPaid">Cash Paid</label>
                <input type="number" class="form-control" id="cashPaid" name="cash_paid" value="<?php echo $payment['cash_paid']; ?>" required>
            </div>
            <div class="form-group">
                <label for="remainingBalance">Remaining Balance</label>
                <input type="number" class="form-control" id="remainingBalance" name="remaining_balance" value="<?php echo $payment['remaining_balance']; ?>" readonly required>
            </div>
            <button type="submit" class="btn btn-primary">Update Payment</button>
        </form>
    </div>
    <?php include('../libs/jslinks.php'); ?>
</body>
</html>
