<?php
// security_details.php
require '../config/db.php';
require_once '../config/db_functions.php';
require '../config/auth.php';

$customerId = $_GET['id'];

// Query to get customer security details
$query = "SELECT c.name, s.security_type, s.person_name, s.person_cnic, s.person_phone, s.cash_amount, s.cheque_details
          FROM security_options s
          JOIN customers c ON s.customer_id = c.customer_id
          WHERE c.customer_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $customerId);
$stmt->execute();
$result = $stmt->get_result();
$customer = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Security Details</title>
    <?php include '../libs/links.php'; ?>

</head>
<body>
  <?php include '../libs/sidebar.php'; ?>

    <div class="container">
        <header><h1>Security Details for <?php echo $customer['name']; ?></h1></header>

        <div class="details-box">
            <h3>Security Type: <?php echo $customer['security_type']; ?></h3>
            
                <p>Person Name: <?php echo $customer['person_name']; ?></p>
                <p>Person CNIC: <?php echo $customer['person_cnic']; ?></p>
                <p>Person Phone: <?php echo $customer['person_phone']; ?></p>
            
                <p>Cash Amount: <?php echo $customer['cash_amount']; ?></p>
            
                <p>Cheque Details: <?php echo $customer['cheque_details']; ?></p>
            
        </div>

        <button type="button" class="btn btn-primary" onclick="window.history.back()" style="margin-top: 0.55rem;">Back</button>
    </div>
</body>
</html>
