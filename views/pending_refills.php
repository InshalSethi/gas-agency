<?php
// views/dashboard.php
require '../config/db.php';
require_once '../config/db_functions.php';
require '../config/auth.php';

$pendingRefills = fetchPendingRefills();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Pending Refills</title>
    <?php include '../libs/links.php'; ?>

</head>
<body>
  <?php include '../libs/sidebar.php'; ?>

    <div class="container-fluid mt-4">
        <header class="mb-2"><h1 class="text-white mb-0">Pending Refills (Older than 1 Month)</h1></header>

        <table id="refillTable" class="table table-striped table-hover">
                <thead class="thead-dark">
            <tr>
                <th>Customer ID</th>
                <th>Name</th>
                <th>Last Refill Date</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($pendingRefills as $refill): ?>
                <tr>
                    <td><?php echo htmlspecialchars($refill['customer_id']); ?></td>
                    <td><?php echo htmlspecialchars($refill['name']); ?></td>
                    <td><?php echo htmlspecialchars($refill['transaction_date']); ?></td>
                </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <a type="button" class="btn btn-secondary" href="dashboard.php">Back Home</a>

    </div>
    <?php include('../libs/jslinks.php'); ?>
</body>
</html>
