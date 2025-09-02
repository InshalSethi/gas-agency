<?php
// views/dashboard.php
require 'config/db.php';
require_once 'config/db_functions.php';
require 'config/auth.php';

// Start measuring execution time
$start_time = microtime(true);

// $pendingRefills = fetchPendingRefills();
$inactiveCustomers = fetchInactiveCustomers();
// $pendingPayments = fetchPendingPayments();
$allCustomers = fetchAllCustomers();
// var_dump($allCustomers);die();
// $pendingRefillsCount = count($pendingRefills);
$inactiveCustomersCount = count($inactiveCustomers);
// $pendingPaymentsCount = count($pendingPayments);
$allCustomersCount = count($allCustomers);

// Initialize variables for totals
$tbonusCylinders = [];
$tpurchasedCylinders = [];
$temptyReturnCylinders = [];
$tpendingCylinders = [];
$totalReceivable = 0;
$totalBalance = 0;
$totalReceived = 0;
$totalBonus = 0;
$totalPurchased = 0;
$totalEmptyReturn = 0;
$totalPending = 0;

// Get all customer IDs for batch processing
$customerIds = array_column($allCustomers, 'id');

// Prefetch all bonus cylinders for all customers in one query
$bonusItemsAll = [];
if (!empty($customerIds)) {
    $db->where("customer_id", $customerIds, 'IN');
    $allBonusItems = $db->get("bonus_cylinders");
    
    // Group bonus items by customer_id
    foreach ($allBonusItems as $item) {
        $bonusItemsAll[$item['customer_id']][] = $item;
    }
}

// Prefetch all products (cylinders) for quick lookup
$cylinderLookup = [];
$db->where("deleted_at", NULL, 'IS');
$allCylinders = $db->get("cylinders");
foreach ($allCylinders as $cylinder) {
    $cylinderLookup[$cylinder['id']] = $cylinder;
}

// Prefetch all invoices for all customers in one query
$invoicesAll = [];
if (!empty($customerIds)) {
    $db->where("customer_id", $customerIds, 'IN');
    $db->where("deleted_at", NULL, 'IS');
    $allInvoices = $db->get("invoices");
    
    // Group invoices by customer_id
    foreach ($allInvoices as $invoice) {
        $invoicesAll[$invoice['customer_id']][] = $invoice;
    }
}

// Prefetch all invoice items for all invoices in one query
$invoiceItemsAll = [];
if (!empty($allInvoices)) {
    $invoiceIds = array_column($allInvoices, 'id');
    if (!empty($invoiceIds)) {
        $db->where("invoice_id", $invoiceIds, 'IN');
        $allInvoiceItems = $db->get("invoice_items");
        
        // Group invoice items by invoice_id
        foreach ($allInvoiceItems as $item) {
            $invoiceItemsAll[$item['invoice_id']][] = $item;
        }
    }
}

// Prefetch all transactions for all customers in one query
$transactionsAll = [];
if (!empty($customerIds)) {
    $db->where("customer_id", $customerIds, 'IN');
    $db->where("deleted_at", NULL, 'IS');
    $allTransactions = $db->get("transactions");
    
    // Group transactions by customer_id
    foreach ($allTransactions as $transaction) {
        $transactionsAll[$transaction['customer_id']][] = $transaction;
    }
}

// Process each customer using prefetched data
foreach ($allCustomers as $allCustomer) {
    $customerId = $allCustomer['id'];
    $bonusCylinders = [];
    $purchasedCylinders = [];
    $emptyReturnCylinders = [];
    $balance = 0;
    $received = 0;
    $receivable = 0;
    $bonus = 0;

    // Process bonus cylinders using prefetched data
    if (isset($bonusItemsAll[$customerId])) {
        foreach ($bonusItemsAll[$customerId] as $bonusItem) {
            $pro = $cylinderLookup[$bonusItem['product_id']];
            $bonusCylinders[$pro['name']] = ($bonusCylinders[$pro['name']] ?? 0) + $bonusItem['qty'];
            $bonus += $bonusItem['qty'];
        }
    }

    // Process invoices using prefetched data
    if (isset($invoicesAll[$customerId])) {
        foreach ($invoicesAll[$customerId] as $invoice) {
            $receivable += $invoice['grand_total'];
            
            // Process invoice items using prefetched data
            if (isset($invoiceItemsAll[$invoice['id']])) {
                foreach ($invoiceItemsAll[$invoice['id']] as $invoiceItem) {
                    $product = $cylinderLookup[$invoiceItem['product_id']];
                    $purchasedCylinders[$product['name']] = ($purchasedCylinders[$product['name']] ?? 0) + $invoiceItem['qty'];
                    $emptyReturnCylinders[$product['name']] = ($emptyReturnCylinders[$product['name']] ?? 0) + $invoiceItem['empty_qty'];
                }
            }
        }
    }

    // Calculate pending cylinders
    $pendingCylinders = [];
    foreach ($purchasedCylinders as $productName => $purchasedQty) {
        $emptyQty = $emptyReturnCylinders[$productName] ?? 0;
        $bonusQty = $bonusCylinders[$productName] ?? 0;
        $pendingQty = $purchasedQty - $emptyQty;

        // Use bonus cylinders to reduce pending quantity
        if ($pendingQty > 0 && $bonusQty > 0) {
            $usedBonus = min($pendingQty, $bonusQty);
            $pendingQty -= $usedBonus;
        }

        if ($pendingQty > 0) {
            $pendingCylinders[$productName] = $pendingQty;
        }
    }

    $purchased = array_sum($purchasedCylinders);
    $emptyReturn = array_sum($emptyReturnCylinders);
    $pending = array_sum($pendingCylinders);

    // Process transactions using prefetched data
    if (isset($transactionsAll[$customerId])) {
        $received = array_sum(array_column($transactionsAll[$customerId], 'amount'));
    }
    $balance = $receivable - $received;

    // Update totals
    $totalReceivable += $receivable;
    $totalReceived += $received;
    $totalBalance += $balance;
    // $totalBonus += $bonus;

    // $totalPurchased += $purchased;
    // $totalEmptyReturn += $emptyReturn;
    // $totalPending += $pending;

    // Store totals for this customer
    $tbonusCylinders[$customerId] = $bonusCylinders;
    $tpurchasedCylinders[$customerId] = $purchasedCylinders;
    $temptyReturnCylinders[$customerId] = $emptyReturnCylinders;
    $tpendingCylinders[$customerId] = $pendingCylinders;
}

// Calculate totals
$totalBonus = array_reduce($tbonusCylinders, function($carry, $item) {
    return $carry + array_sum($item);
}, 0);
$totalPurchased = array_reduce($tpurchasedCylinders, function($carry, $item) {
    return $carry + array_sum($item);
}, 0);
$totalEmptyReturn = array_reduce($temptyReturnCylinders, function($carry, $item) {
    return $carry + array_sum($item);
}, 0);
$totalPending = array_reduce($tpendingCylinders, function($carry, $item) {
    return $carry + array_sum($item);
}, 0);

// Calculate today's sold cylinders
$todaySoldCylinders = [];
$totalTodaySold = 0;
$today = date('Y-m-d');

// Get today's invoices
$db->where("deleted_at", NULL, 'IS');
$db->where("date", $today);
$todayInvoices = $db->get("invoices");

foreach ($todayInvoices as $todayInvoice) {
    // Get invoice items for today's invoices
    $db->where("invoice_id", $todayInvoice['id']);
    $todayInvoiceItems = $db->get("invoice_items");

    foreach ($todayInvoiceItems as $item) {
        $product = $cylinderLookup[$item['product_id']];
        $todaySoldCylinders[$product['name']] = ($todaySoldCylinders[$product['name']] ?? 0) + $item['qty'];
        $totalTodaySold += $item['qty'];
    }
}

// Calculate today's empty cylinders
$todayEmptyCylinders = [];
$totalTodayEmpty = 0;

// Get today's empty cylinders from empty_cylinders table
$db->where("ecy.deleted_at", NULL, 'IS');
$db->where("DATE(ecy.created_at)", $today);
$db->join("invoice_items ii", "ecy.invoice_item_id=ii.id", "LEFT");
$db->join("cylinders cy", "ii.product_id=cy.id", "LEFT");
$todayEmptyRecords = $db->get("empty_cylinders ecy", null, "ecy.cylinders as qty, cy.name as cylinder_name, cy.id as cylinder_id");

foreach ($todayEmptyRecords as $emptyRecord) {
    if (!empty($emptyRecord['cylinder_name'])) {
        $todayEmptyCylinders[$emptyRecord['cylinder_name']] = ($todayEmptyCylinders[$emptyRecord['cylinder_name']] ?? 0) + $emptyRecord['qty'];
        $totalTodayEmpty += $emptyRecord['qty'];
    }
}

// Get cylinder data
$db->where("deleted_at", NULL, 'IS');
$db->orderBy("name", "asc");
$cylinders = $db->get("cylinders"); 
$totalCylinders = 0;
$totalEmptyProductsCylinders = 0;
foreach($cylinders as $cylinder){
  $totalEmptyProductsCylinders += $cylinder['empty_qty'];
  $totalCylinders += $cylinder['qty'];
}

// Aggregate totals for InStock Cylinders
$totalInStockCylinders = [];
$totalEmptyStockCylinders = [];

foreach ($cylinders as $cylinder) {
    if (!isset($totalInStockCylinders[$cylinder['name']])) {
        $totalInStockCylinders[$cylinder['name']] = 0;
    }
    $totalInStockCylinders[$cylinder['name']] += $cylinder['qty'];

    if (!isset($totalEmptyStockCylinders[$cylinder['name']])) {
        $totalEmptyStockCylinders[$cylinder['name']] = 0;
    }
    $totalEmptyStockCylinders[$cylinder['name']] += $cylinder['empty_qty'];
}

// Calculate execution time
$execution_time = microtime(true) - $start_time;

// Handle reset request
if (isset($_REQUEST['reset'])) {
  $x = $_REQUEST['reset'];
  if ($x != '') {
    date_default_timezone_set("Asia/Karachi");
    $delDate = date("Y-m-d h:i:s");

    $uparr = array("status" => 'completed', "deleted_at" => $delDate);
    $db->where("status", 'pending');
    $db->update('empty_cylinders', $uparr);
    ?>
    <script>window.location.href = "index.php";</script>
    <?php
  }
}
?>

  <!DOCTYPE html>
  <html>
  <head>
    <title>Dashboard</title>
    <?php include 'libs/links.php'; ?>
    <link href="<?php echo baseurl('css/select2.min.css'); ?>" rel="stylesheet" />
    <link rel="stylesheet" type="text/css" href="<?php echo baseurl('css/daterangepicker.css'); ?>" />
    <style>
      .card {
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s, box-shadow 0.3s;
        background-color: #f8f9fa; /* Light background color */
      }

      .card:hover {
        transform: translateY(-10px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
      }

      .card-header {
        background-color: #17a2b8; /* Consistent light color */
        color: white;
        border-bottom: none;
        border-top-left-radius: 10px;
        border-top-right-radius: 10px;
        font-size: 16px;
      }

      .card-title a {
        color: #17a2b8; /* Matching the header background color */
        text-decoration: none;
      }

      .card-title a:hover {
        text-decoration: underline;
      }

      .icon {
        font-size: 2em;
        margin-right: 10px;
      }
      .icon-img{
        width: 30px;
        margin-right: 10px;
        border-radius: 5px;
      }
    </style>
  </head>
  <body>
    <?php include 'libs/sidebar.php'; ?>

    <div class="container-fluid mt-4">
      <div class="row">
        <div class="col-md-12">
          <h2 class="text-left">Dashboard</h2>
          <p>Welcome aboard! Your dashboard awaits your next move. </p>
          <div class="row">
            <div class="col-md-3 mb-4">
            <div class="card total-cylinders">
              <div class="card-header">
                <img class="icon-img" src="<?php echo baseurl('images/cylinder.jpg'); ?>"/>InStock Cylinders
              </div>
              <div class="card-body">
                <h5 class="card-title">
                  <a href="<?php echo baseurl('views/cylinder_management.php'); ?>"><?php echo $totalCylinders; ?></a>
                </h5>
                <?php foreach ($totalInStockCylinders as $productName => $qty): ?>
                  <span class="badge badge-secondary"><?php echo "$productName ($qty)"; ?></span>
                <?php endforeach; ?>
              </div>
            </div>
          </div>

          <div class="col-md-3 mb-4">
            <div class="card empty-cylinders">
              <div class="card-header">
                <i class="fas fa-exclamation-triangle icon"></i>
                <span>Empty Stock</span>
              </div>
              <div class="card-body">
                <h5 class="card-title">
                  <a href="<?php echo baseurl('views/cylinder_management.php'); ?>"><?php echo $totalEmptyProductsCylinders; ?></a>
                </h5>
                <?php foreach ($totalEmptyStockCylinders as $productName => $qty): ?>
                  <span class="badge badge-secondary"><?php echo "$productName ($qty)"; ?></span>
                <?php endforeach; ?>
              </div>
            </div>
          </div>

          <!-- <div class="col-md-3 mb-4">
            <div class="card total-customers">
              <div class="card-header">
                <i class="fas fa-users icon"></i>Total Customers
              </div>
              <div class="card-body">
                <h5 class="card-title"><a href="customer_management.php"><?php echo $allCustomersCount; ?></a></h5>
              </div>
            </div>
          </div> -->

          <!-- <div class="col-md-3 mb-4">
            <div class="card pending-refills">
              <div class="card-header">
                <i class="fas fa-sync icon"></i>Pending Refills
              </div>
              <div class="card-body">
                <h5 class="card-title"><a href="pending_refills.php"><?php echo $pendingRefillsCount; ?></a></h5>
              </div>
            </div>
          </div> -->

          <div class="col-md-3 mb-4">
            <div class="card inactive-customers">
              <div class="card-header">
                <i class="fas fa-user-slash icon"></i>Inactive Customers
              </div>
              <div class="card-body">
                <h5 class="card-title"><a href="<?php echo baseurl('views/inactive_customers.php'); ?>"><?php echo $inactiveCustomersCount; ?></a></h5>
              </div>
            </div>
          </div>

          <div class="col-md-3 mb-4">
            <div class="card pending-payments">
              <div class="card-header">
                <i class="fas fa-money-bill-wave icon"></i>Pending Payments
              </div>
              <div class="card-body">
                <h5 class="card-title"><a href="<?php echo baseurl('views/customer_management.php'); ?>"><?php echo $totalBalance; ?></a></h5>
              </div>
            </div>
          </div>

          
          

          <div class="col-md-3 mb-4">
            <div class="card empty-cylinders">
              <div class="card-header">
                <i class="fas fa-history icon"></i>Total Bonus
              </div>
              <div class="card-body">
                <h5 class="card-title"><a href="<?php echo baseurl('views/customer_management.php'); ?>"><?php echo $totalBonus; ?></a></h5>
                <?php 
                $pertbonusCylinders = [];

                // Aggregate totals
                foreach ($tbonusCylinders as $subArray) {
                    foreach ($subArray as $productName => $quantity) {
                        if (!isset($pertbonusCylinders[$productName])) {
                            $pertbonusCylinders[$productName] = 0;
                        }
                        $pertbonusCylinders[$productName] += $quantity;
                    }
                }
                ksort($pertbonusCylinders);
                foreach($pertbonusCylinders as $productName => $emptyQty): 
                  ?>
                  <span class="badge badge-secondary"><?php echo "$productName ($emptyQty)"; ?></span>
                <?php endforeach; ?>
              </div>
            </div>
          </div>

          <div class="col-md-3 mb-4">
            <div class="card empty-cylinders">
              <div class="card-header">
                <i class="fas fa-history icon"></i>Empty Return
              </div>
              <div class="card-body">
                <h5 class="card-title"><a href="<?php echo baseurl('views/sale-invoices.php'); ?>"><?php echo $totalEmptyReturn; ?></a></h5>
                <?php 
                $pertemptyReturnCylinders = [];

                // Aggregate totals
                foreach ($temptyReturnCylinders as $subArray) {
                    foreach ($subArray as $productName => $quantity) {
                        if (!isset($pertemptyReturnCylinders[$productName])) {
                            $pertemptyReturnCylinders[$productName] = 0;
                        }
                        $pertemptyReturnCylinders[$productName] += $quantity;
                    }
                }
                ksort($pertemptyReturnCylinders);
                foreach($pertemptyReturnCylinders as $productName => $emptyQty): 
                  ?>
                  <span class="badge badge-secondary"><?php echo "$productName ($emptyQty)"; ?></span>
                <?php endforeach; ?>
              </div>
            </div>
          </div>

          <div class="col-md-3 mb-4">
            <div class="card empty-cylinders">
              <div class="card-header">
                <i class="fas fa-history icon"></i>Total Sold
              </div>
              <div class="card-body">
                <h5 class="card-title"><a href="<?php echo baseurl('views/sale-invoices.php'); ?>"><?php echo $totalPurchased; ?></a></h5>
                <?php
                $pertpurchasedCylinders = [];

                // Aggregate totals
                foreach ($tpurchasedCylinders as $subArray) {
                    foreach ($subArray as $productName => $quantity) {
                        if (!isset($pertpurchasedCylinders[$productName])) {
                            $pertpurchasedCylinders[$productName] = 0;
                        }
                        $pertpurchasedCylinders[$productName] += $quantity;
                    }
                }
                ksort($pertpurchasedCylinders);
                foreach($pertpurchasedCylinders as $productName => $emptyQty):
                  ?>
                  <span class="badge badge-secondary"><?php echo "$productName ($emptyQty)"; ?></span>
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
                <h5 class="card-title"><a href="<?php echo baseurl('views/customer_management.php'); ?>" <?php if($totalPending > 0){echo 'class="text-danger"';}else{echo 'class="text-success"';} ?>><?php echo $totalPending; ?></a></h5>
                <?php 
                $pretpendingCylinders = [];

                // Aggregate totals
                foreach ($tpendingCylinders as $subArray) {
                    foreach ($subArray as $productName => $quantity) {
                        if (!isset($pretpendingCylinders[$productName])) {
                            $pretpendingCylinders[$productName] = 0;
                        }
                        $pretpendingCylinders[$productName] += $quantity;
                    }
                }
                ksort($pretpendingCylinders);
                foreach($pretpendingCylinders as $productName => $emptyQty): 
                  ?>
                  <span class="badge badge-secondary"><?php echo "$productName ($emptyQty)"; ?></span>
                <?php endforeach; ?>
              </div>
            </div>
          </div>

          <div class="col-md-3 mb-4">
            <div class="card empty-cylinders">
              <div class="card-header">
                <i class="fas fa-calendar-day icon"></i>Today Sold
              </div>
              <div class="card-body">
                <h5 class="card-title"><a href="<?php echo baseurl('views/sale-invoices.php'); ?>"><?php echo $totalTodaySold; ?></a></h5>
                <?php
                ksort($todaySoldCylinders);
                foreach($todaySoldCylinders as $productName => $soldQty):
                  ?>
                  <span class="badge badge-secondary"><?php echo "$productName ($soldQty)"; ?></span>
                <?php endforeach; ?>
              </div>
            </div>
          </div>

          <div class="col-md-3 mb-4">
            <div class="card empty-cylinders">
              <div class="card-header">
                <i class="fas fa-calendar-day icon"></i>Today Empty
              </div>
              <div class="card-body">
                <h5 class="card-title"><a href="<?php echo baseurl('views/empty_stock.php'); ?>" class="text-danger"><?php echo $totalTodayEmpty; ?></a></h5>
                <?php
                ksort($todayEmptyCylinders);
                foreach($todayEmptyCylinders as $productName => $emptyQty):
                  ?>
                  <span class="badge badge-secondary"><?php echo "$productName ($emptyQty)"; ?></span>
                <?php endforeach; ?>
              </div>
            </div>
          </div>

          

        </div>
        <div class="row">
          <div class="col-md-12">
            <div class="card">
              <div class="card-body">
                <div class="table-container">
                  <div class="mt-2 mb-2">
                    <div class="row">
                      <div class="col-md-3">
                        <input type="text" id="dateRangePicker" class="form-control filter mt-0" placeholder="Select Date Range" />
                      </div>
                      <div class="col-md-2">
                        <select id="statusFilter" class="form-control filter">
                          <option value="">Select Status By</option>
                          <option value="empty_pending">Empty Pending</option>
                          <option value="empty_received">Empty Received</option>
                        </select>
                      </div>
                      <div class="col-md-2">
                        <select id="paymentFilter" class="form-control filter">
                          <option value="">Select Payment By</option>
                          <option value="payment_pending">Payment Pending</option>
                          <option value="payment_received">Payment Received</option>
                        </select>
                      </div>
                      <div class="col-md-2">
                        <select id="cylinderFilter" class="form-control filter">
                          <option value="">Select Cylinder By</option>
                          <?php foreach ($cylinders as $cylinder): ?>
                            <option value="<?php echo $cylinder['id']; ?>"><?php echo $cylinder['name']; ?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                      <div class="col-md-3">
                        <button class="btn btn-info" id="resetFilterBtn" title="Reset Filter"><i class="fa fa-refresh"></i></button>
                        <button class="btn btn-primary ml-1" id="downloadExcelBtn" title="Download Excel"><i class="fa fa-download"></i></button>
                        <a class="btn btn-success ml-1 text-white" href="views/add-sale-invoice.php" title="Add Invoice"><i class="fa fa-plus"></i> Add Invoice</a>
                      </div>
                    </div>
                  </div>
                  <table id="dashboardTable" class="table table-striped table-hover">
                    <thead class="thead-dark">
                      <tr>
                        <th>ID</th>
                        <th>Customer</th>
                        <th>Quantity</th>
                        <th>Balance</th>
                        <th>Empty</th>
                        <th>Date</th>
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

  <?php include('libs/jslinks.php'); ?>
  <script src="<?php echo baseurl('js/select2.min.js'); ?>"></script>
  <script type="text/javascript" src="<?php echo baseurl('js/moment.min.js'); ?>"></script>
  <script type="text/javascript" src="<?php echo baseurl('js/daterangepicker.min.js'); ?>"></script>
  <script src="<?php echo baseurl('js/xlsx.full.min.js'); ?>"></script>

  <script>
$(document).ready(function() {
  // Fetch user details and populate modal
  $.get('<?php echo baseurl('fetch_user_details.php'); ?>', function(user) {
    // Populate form with user details
    $('#current_username').val(user.username); // Assuming you have an input with id 'current_username' for displaying username
  });

  var defaultStartDate = moment().subtract(0, 'days');
  var defaultEndDate = moment();
  // Initialize date range picker
  $('#dateRangePicker').daterangepicker({
    opens: 'left'
  }, function(start, end, label) {
    $('#dashboardTable').DataTable().ajax.reload();
  });

  // Initialize DataTables
  var dashboardTable = $('#dashboardTable').DataTable({
    processing: true,
    serverSide: true,
    "paging": true,
    "iDisplayLength": 100,
    "ajax": {
      "url": "dashboard-customer.php",
      "type": "GET",
      data: function(d) {
        var dateRange = $('#dateRangePicker').val().split(' - ');
        d.start_date = dateRange[0];
        d.end_date = dateRange[1];
        d.status_filter = $('#statusFilter').val();
        d.payment_filter = $('#paymentFilter').val();
        d.cylinder_filter = $('#cylinderFilter').val();
      }
    },
    "columns": [
      { "data": "id" },
      { "data": "customer" },
      { "data": "total_cylinders" },
      { "data": "balance" },
      { "data": "empty_cylinders" },
      { "data": "date" },
      { "data": "actions" },
    ],
    rowCallback: function(row, data) {
      if (data.row_class) {
        $(row).addClass(data.row_class);
      }
    },
  });

  // Search form submission
  $('input[type="search"], filter').keyup(function(e) {
    e.preventDefault();
    dashboardTable.draw();
  });

  $('.filter').change(function(e) {
    e.preventDefault();
    dashboardTable.draw();
  });

  $('#resetFilterBtn').click(function() {
    // Reset date range picker to default values
    $('#dateRangePicker').data('daterangepicker').setStartDate(defaultStartDate);
    $('#dateRangePicker').data('daterangepicker').setEndDate(defaultEndDate);
    $('#statusFilter').val(null);
    $('#paymentFilter').val(null);
    $('#cylinderFilter').val(null);

    // Reload the DataTable
    dashboardTable.draw();
  });

  // Function to download Excel file
  function downloadExcel() {
    var tableData = [];
    // Get table headers, excluding the "Actions" column
    var headers = [];
    $('#dashboardTable thead th').each(function(index, th) {
      if ($(th).text().trim() !== 'Actions') {
        headers.push($(th).text().trim());
      }
    });
    tableData.push(headers);

    // Get table rows, excluding the "Actions" column
    $('#dashboardTable tbody tr').each(function(index, tr) {
      var rowData = [];
      $(tr).find('td').each(function(index, td) {
        if (index !== 6) { // Assuming "Actions" column is the 7th column (0-based index)
          rowData.push($(td).text().trim());
        }
      });
      tableData.push(rowData);
    });

    // Convert to worksheet
    var worksheet = XLSX.utils.aoa_to_sheet(tableData);
    var workbook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, worksheet, "Sheet1");

    // Generate Excel file and trigger download
    XLSX.writeFile(workbook, 'dashboard_data.xlsx');
  }

  // Attach event listener to the button
  $('#downloadExcelBtn').click(function() {
    downloadExcel();
  });
});
</script>

</body>
</html>
