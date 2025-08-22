<?php
require '../config/db.php';
require_once '../config/db_functions.php';
require '../config/auth.php';

$customer_id = $_REQUEST['id'];
$db->where("id", $customer_id);
$customer = $db->getOne("customers");

// Initialize arrays to hold cylinder details
$bonusCylinders = [];
$purchasedCylinders = [];
$emptyReturnCylinders = [];
$pendingCylinders = [];
$totalReceivable = 0;
$totalBonus = 0;
// Fetch bonus cylinders
$db->where("customer_id", $customer_id);
$bonusItems = $db->get("bonus_cylinders");
foreach($bonusItems as $bonusItem) {
    $db->where("id", $bonusItem['product_id']);
    $pro = $db->getOne("cylinders");
    $bonusCylinders[$pro['name']] = $bonusItem['qty'];
    $totalBonus+=$bonusItem['qty'];
}

// Fetch invoices and their items
$db->where("customer_id", $customer_id);
$db->where("deleted_at", NULL, 'IS');
$invoices = $db->get("invoices");

foreach($invoices as $invoice) {
    $totalReceivable += $invoice['grand_total'];
    $db->where("invoice_id", $invoice['id']);
    $invoiceItems = $db->get("invoice_items");
    foreach($invoiceItems as $invoiceItem) {

        $db->where("id", $invoiceItem['product_id']);
        $product = $db->getOne("cylinders");
        // Calculate purchased and empty return cylinders
        if (!isset($purchasedCylinders[$product['name']])) {
            $purchasedCylinders[$product['name']] = 0;
        }
        if (!isset($emptyReturnCylinders[$product['name']])) {
            $emptyReturnCylinders[$product['name']] = 0;
        }
        $purchasedCylinders[$product['name']] += $invoiceItem['qty'];
        $emptyReturnCylinders[$product['name']] += $invoiceItem['empty_qty'];
    }
}

// Calculate pending cylinders
// foreach ($purchasedCylinders as $productName => $purchasedQty) {
//     $emptyQty = isset($emptyReturnCylinders[$productName]) ? $emptyReturnCylinders[$productName] : 0;
//     $bonusQty = isset($bonusCylinders[$productName]) ? $bonusCylinders[$productName] : 0;
//     $pendingQty = $purchasedQty - ($emptyQty+$bonusQty);
//     // var_dump($pendingQty);die();
//     // If there's bonus stock, use it to reduce the pending quantity
//     // if ($pendingQty > 0 && $bonusQty > 0) {
//         $usedBonus = min($pendingQty, $bonusQty);
//         // var_dump($usedBonus);die();
//         // $pendingQty -= $usedBonus;
//         // var_dump($pendingQty);die();
//         $bonusCylinders[$productName] -= $usedBonus; // Update remaining bonus stock
//         // var_dump($pendingQty);die();
//     // }
    
//     // if ($pendingQty > 0) {
//         $pendingCylinders[$productName] = $pendingQty;
//     // }
// }

foreach ($purchasedCylinders as $productName => $purchasedQty) {
    $emptyQty = isset($emptyReturnCylinders[$productName]) ? $emptyReturnCylinders[$productName] : 0;
    $bonusQty = isset($bonusCylinders[$productName]) ? $bonusCylinders[$productName] : 0;
    $pendingQty = $purchasedQty - ($emptyQty + $bonusQty);

    // Ensure $bonusCylinders has the product name key
    if (!isset($bonusCylinders[$productName])) {
        $bonusCylinders[$productName] = 0;
    }

    $usedBonus = min($pendingQty, $bonusQty);
    $bonusCylinders[$productName] -= $usedBonus; // Update remaining bonus stock

    // Update pending cylinders
    $pendingCylinders[$productName] = $pendingQty;
}


// var_dump($pendingCylinders);die();
// Calculate totals

$totalPurchased = array_sum($purchasedCylinders);
$totalEmptyReturn = array_sum($emptyReturnCylinders);
$totalPending = array_sum($pendingCylinders);

// Fetch transactions
$db->where("customer_id", $customer_id);
$db->where("deleted_at", NULL, 'IS');
$transactions = $db->get("transactions");
$totalReceived = array_sum(array_column($transactions, 'amount'));
$totalBalance = $totalReceivable - $totalReceived;

$getBonusStockDetails = getCustomerBonus($db, $customer_id);
?>

<!DOCTYPE html>
<html>
<head>
  <title>Customer Ledger</title>
  <?php include '../libs/links.php'; ?>
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
  <style>
    .card {
      border-radius: 10px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
      transition: transform 0.3s, box-shadow 0.3s;
      background-color: #f8f9fa;
    }

    .card:hover {
      transform: translateY(-10px);
      box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
    }

    .card-header {
      background-color: #17a2b8;
      color: white;
      border-bottom: none;
      border-top-left-radius: 10px;
      border-top-right-radius: 10px;
      font-size: 16px;
    }

    .card-title a {
      color: #17a2b8;
      text-decoration: none;
    }

    .card-title a:hover {
      text-decoration: underline;
    }

    .icon {
      font-size: 2em;
      margin-right: 10px;
    }
  </style>
</head>
<body>
  <?php include '../libs/sidebar.php'; ?>

  <div class="container-fluid mt-4">
    <div class="row">
      <div class="col-md-12">
        <h2 class="text-left"><?php echo $customer['name']; ?> </h2>
        <p><small>Phone: <?php echo $customer['phone']; ?>, CNIC: <?php echo $customer['cnic']; ?></small> </p>
        <div class="row">

          <div class="col-md-3 mb-4">
            <div class="card total-cylinders">
              <div class="card-header">
                <i class="fas fa-cube icon"></i>Bonus Cylinders
              </div>
              <div class="card-body">
                <h5 class="card-title"><a href=""><?php echo $totalBonus; ?></a></h5>
                  <?php 
                  if (!empty($getBonusStockDetails)) {
                    // Example: $getBonusStockDetails = 'CO2(1), Hydrogen(1)';
                    
                    // Convert string to array
                    $bonusStockDetailsArray = explode(', ', $getBonusStockDetails['bonus_stock']);
                    ksort($bonusStockDetailsArray);
                    // Iterate over the array and create badges
                    foreach ($bonusStockDetailsArray as $bonusDetail) { ?>
                    <span class="badge badge-secondary"><?php echo $bonusDetail; ?></span>
                      <?php
                    }
                  } else {
                    echo 'No Bonus';
                  }
                  ?>
              </div>
            </div>
          </div>


          <div class="col-md-3 mb-4">
            <div class="card empty-cylinders">
              <div class="card-header">
                <i class="fas fa-dropbox icon"></i>Empty Return
              </div>
              <div class="card-body">
                <h5 class="card-title"><a href=""><?php echo $totalEmptyReturn; ?></a></h5>
                <?php ksort($emptyReturnCylinders); foreach($emptyReturnCylinders as $productName => $emptyQty): ?>
                  <span class="badge badge-secondary"><?php echo "$productName ($emptyQty)"; ?></span>
                <?php endforeach; ?>
              </div>
            </div>
          </div>

          <div class="col-md-3 mb-4">
            <div class="card empty-cylinders">
              <div class="card-header">
                <i class="fas fa-history icon"></i>Purchased Cylinders
              </div>
              <div class="card-body">
                <h5 class="card-title"><a href=""><?php echo $totalPurchased; ?></a></h5>
                <?php ksort($purchasedCylinders); foreach($purchasedCylinders as $productName => $purchasedQty): ?>
                  <span class="badge badge-secondary"><?php echo "$productName ($purchasedQty)"; ?></span>
                <?php endforeach; ?>
              </div>
            </div>
          </div>

          <div class="col-md-3 mb-4">
            <div class="card total-customers">
              <div class="card-header">
                <i class="fas fa-users icon"></i>Pending Cylinders
              </div>
              <div class="card-body">
                <h5 class="card-title">
                  <a href="" <?php if($totalPending > 0){echo 'class="text-danger"';}else{echo 'class="text-success"';} ?>>
                    <?php echo $totalPending; ?>
                  </a>
                </h5>
                <?php ksort($pendingCylinders); foreach($pendingCylinders as $productName => $pendingQty): ?>
                  <span class="badge badge-secondary"><?php echo "$productName ($pendingQty)"; ?></span>
                <?php endforeach; ?>
              </div>
            </div>
          </div>

          <div class="col-md-3 mb-4">
            <div class="card pending-refills">
              <div class="card-header">
                <i class="fas fa-usd icon"></i>Receivable
              </div>
              <div class="card-body">
                <h5 class="card-title"><a href=""><?php echo $totalReceivable; ?></a></h5>
              </div>
            </div>
          </div>

          <div class="col-md-3 mb-4">
            <div class="card pending-refills">
              <div class="card-header">
                <i class="fas fa-usd icon"></i>Received
              </div>
              <div class="card-body">
                <h5 class="card-title"><a href=""><?php echo $totalReceived; ?></a></h5>
              </div>
            </div>
          </div>

          <div class="col-md-3 mb-4">
            <div class="card pending-refills">
              <div class="card-header">
                <i class="fas fa-exclamation-triangle icon"></i>Balance
              </div>
              <div class="card-body">
                <h5 class="card-title"><a href=""><?php echo $totalBalance; ?></a></h5>
              </div>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-12">
            <div class="card">
              <div class="card-body">
                <div class="table-container">
                  <table id="customerLedgerTable" class="table table-striped table-hover">
                    <thead class="thead-dark">
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Quantity</th>
                            <th>Empty</th>
                            <!-- <th>Pending</th> -->
                            <th>Date</th>
                            <th>Total</th>
                            <th>Received</th>
                            <th>Balance</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <?php include('../libs/jslinks.php'); ?>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  <script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
  <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
  <script>
    function resetEmptyCylinders() {
      var txt;
      var r = confirm("Are you sure you want to reset?");
      if (r == true) {
        window.location = "index.php?reset=all";
      }
    }

    $(document).ready(function() {
      var customerLedgerTable = $('#customerLedgerTable').DataTable({
        processing: true,
        serverSide: true,
        "paging": true,
        "iDisplayLength": 100,
        "ajax": {
          "url": "customer-ledger-ajax.php",
          "type": "GET",
          data: function(d) {
            d.customer_id = <?php echo $customer_id; ?>;
          }
        },
        "columns": [
          { "data": "id" },
          { "data": "customer" },
          { "data": "total_cylinders" },
          { "data": "empty_cylinders" },
          // { "data": "pending_cylinders" },
          { "data": "date" },
          { "data": "grand_total" },
          { "data": "received" },
          { "data": "balance" },
          { "data": "created_at" },
          { "data": "actions" }
        ],
        rowCallback: function (row, data) {
          if (data.row_class) {
            $(row).addClass(data.row_class);
          }
        },
      });

      $('input[type="search"],filter').keyup(function (e) {
        e.preventDefault();
        customerLedgerTable.draw();
      });

      $('.filter').change(function (e) {
        e.preventDefault();
        customerLedgerTable.draw();
      });
    });
  </script>
</body>
</html>