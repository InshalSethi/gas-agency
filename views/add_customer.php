<?php
// add_customer.php
require '../config/db.php';
require_once '../config/db_functions.php';
require '../config/auth.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $customer_id = uniqid();
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $cnic = $_POST['cnic'];
    $bonus_cylinders = isset($_POST['bonus_cylinders']) ? $_POST['bonus_cylinders'] : 0;
    $person_name = $_POST['person_name'];
    $person_cnic = $_POST['person_cnic'];
    $person_phone = $_POST['person_phone'];
    $cash_amount = $_POST['cash_amount'];
    $cheque_details = $_POST['cheque_details'];
    $gas_types = $_POST['gas_types'];
    $time_zone = date_default_timezone_set("Asia/Karachi");
    $date = date("Y-m-d h:i:s");
    // Convert each element to an integer
    $gas_types_int = array_map('intval', $gas_types);

    // Encode to JSON format
    $gasTypes = json_encode($gas_types_int);
    // echo $gasTypes;die();
    // var_dump($gas_types);die();
        $ins_arr = array(
            "customer_id"=>$customer_id,
            "name"=>$name,
            "phone"=>$phone,
            "cnic"=>$cnic,
            "bonus_cylinders"=>$bonus_cylinders,
            "security_deposit"=>$cash_amount,
            "gas_types"=>$gasTypes,
            "active"=>1,
            "created_at"=>$date
        );
    // var_dump($ins_arr);die();
    $insert = $db->insert("customers",$ins_arr);

    $so_arr = array(
            "customer_id"=>$customer_id,
            "person_name"=>$person_name,
            "person_cnic"=>$person_cnic,
            "person_phone"=>$person_phone,
            "cash_amount"=>$cash_amount,
            "cheque_details"=>$cheque_details
        );
    // var_dump($ins_arr);die();
    $insert = $db->insert("security_options",$so_arr);
    var_dump($insert);die();
    $stmt = $conn->prepare("INSERT INTO customers (customer_id, name, phone, cnic, bonus_cylinders) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssi", $customer_id, $name, $phone, $cnic, $bonus_cylinders);
    $stmt->execute();

    // $security_type = $_POST['security_type'];
    if ($security_type == 'Person') {
        $person_name = $_POST['person_name'];
        $person_cnic = $_POST['person_cnic'];
        $person_phone = $_POST['person_phone'];
        $stmt = $conn->prepare("INSERT INTO security_options (customer_id, security_type, person_name, person_cnic, person_phone) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $customer_id, $security_type, $person_name, $person_cnic, $person_phone);
    } elseif ($security_type == 'Cash') {
        $cash_amount = $_POST['cash_amount'];
        $stmt = $conn->prepare("INSERT INTO security_options (customer_id, security_type, cash_amount) VALUES (?, ?, ?)");
        $stmt->bind_param("ssd", $customer_id, $security_type, $cash_amount);

        // Update the customer's security deposit
        $stmt2 = $conn->prepare("UPDATE customers SET security_deposit = ? WHERE customer_id = ?");
        $stmt2->bind_param("ds", $cash_amount, $customer_id);
        $stmt2->execute();
    } elseif ($security_type == 'Cheque') {
        $cheque_details = $_POST['cheque_details'];
        $stmt = $conn->prepare("INSERT INTO security_options (customer_id, security_type, cheque_details) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $customer_id, $security_type, $cheque_details);
    }

    $stmt->execute();
    header("Location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Customer</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="../css/styles.css">
    <style>
        .form-group {
            margin-bottom: 15px;
        }

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

    <div class="container">
        <header><h1>Add Customer</h1></header>
        <form method="POST" action="add_customer.php">
            <div class="form-group">
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="phone">Phone:</label>
                <input type="text" id="phone" name="phone" required>
            </div>
            <div class="form-group">
                <label for="cnic">CNIC:</label>
                <input type="text" id="cnic" name="cnic" required>
            </div>
            <div class="form-group">
                <label for="bonus_cylinders">Bonus Cylinders (optional):</label>
                <input type="number" id="bonus_cylinders" name="bonus_cylinders">
            </div>
            <div class="form-group">
                <label for="security_type">Security Type:</label>
                <select id="security_type" name="security_type" required>
                    <option value="Person">Person</option>
                    <option value="Cash">Cash</option>
                    <option value="Cheque">Cheque</option>
                </select>
            </div>
            <div class="form-group" id="person_security" style="display: none;">
                <label for="person_name">Person Name:</label>
                <input type="text" id="person_name" name="person_name">
                <label for="person_cnic">Person CNIC:</label>
                <input type="text" id="person_cnic" name="person_cnic">
                <label for="person_phone">Person Phone:</label>
                <input type="text" id="person_phone" name="person_phone">
            </div>
            <div class="form-group" id="cash_security" style="display: none;">
                <label for="cash_amount">Cash Amount:</label>
                <input type="number" id="cash_amount" name="cash_amount">
            </div>
            <div class="form-group" id="cheque_security" style="display: none;">
                <label for="cheque_details">Cheque Details:</label>
                <input type="text" id="cheque_details" name="cheque_details">
            </div>
            <button type="submit" class="btn btn-primary">Add Customer</button>
            <button type="button" class="btn btn-secondary" onclick="window.history.back()">Back</button>
        </form>
    </div>
<?php include('../libs/jslinks.php'); ?>
    <script>
        document.getElementById('security_type').addEventListener('change', function() {
            document.getElementById('person_security').style.display = 'none';
            document.getElementById('cash_security').style.display = 'none';
            document.getElementById('cheque_security').style.display = 'none';

            if (this.value === 'Person') {
                document.getElementById('person_security').style.display = 'block';
            } else if (this.value === 'Cash') {
                document.getElementById('cash_security').style.display = 'block';
            } else if (this.value === 'Cheque') {
                document.getElementById('cheque_security').style.display = 'block';
            }
        });
    </script>
</body>
</html>
