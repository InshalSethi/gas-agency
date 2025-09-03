<?php
require '../config/db.php';
require_once '../config/db_functions.php';
require '../config/auth.php';

$vendor_id = $_REQUEST['id'];
$db->where("id", $vendor_id);
$vendor = $db->getOne("vendors");

// Initialize arrays to hold cylinder details
$purchasedCylinders = [];
$emptyReturnCylinders = [];
$totalPayable = 0;
$totalPaid = 0;
$totalBalance = 0;

// Fetch purchase invoices and their items
$db->where("vendor_id", $vendor_id);
$db->where("deleted_at", NULL, 'IS');
$purchaseInvoices = $db->get("purchase_invoices");

foreach($purchaseInvoices as $invoice) {
    $totalPayable += floatval($invoice['grand_total']);
    $totalPaid += floatval($invoice['paid']);
    
    $db->where("purchase_invoice_id", $invoice['id']);
    $invoiceItems = $db->get("purchase_invoice_items");
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
        $purchasedCylinders[$product['name']] += intval($invoiceItem['qty']);
        $emptyReturnCylinders[$product['name']] += intval($invoiceItem['empty_qty']);
    }
}

$totalBalance = $totalPayable - $totalPaid;

// Calculate pending cylinders (purchased - empty returned)
$pendingCylinders = [];
$totalPending = 0;

// Get all unique product names from both purchased and empty return cylinders
$allProducts = array_unique(array_merge(array_keys($purchasedCylinders), array_keys($emptyReturnCylinders)));

foreach($allProducts as $productName) {
    $purchasedQty = isset($purchasedCylinders[$productName]) ? $purchasedCylinders[$productName] : 0;
    $emptyQty = isset($emptyReturnCylinders[$productName]) ? $emptyReturnCylinders[$productName] : 0;
    $pendingQty = $purchasedQty - $emptyQty;

    // Add to total pending (can be negative, but we'll show individual products only if positive)
    $totalPending += $pendingQty;

    // Only show individual products with positive pending quantities
    // if ($pendingQty > 0) {
        $pendingCylinders[$productName] = $pendingQty;
    // }
}

// Fetch transactions
$db->where("vendor_id", $vendor_id);
$db->where("deleted_at", NULL, 'IS');
$transactions = $db->get("transactions");
$totalTransactions = array_sum(array_map('floatval', array_column($transactions, 'amount')));
?>

<!DOCTYPE html>
<html>
<head>
    <title>Vendor Ledger - <?php echo $vendor['name']; ?></title>
    <?php include '../libs/links.php'; ?>
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
                <h2 class="text-left"><?php echo $vendor['name']; ?></h2>
                <p><small>Contact: <?php echo $vendor['contact_info']; ?>, Address: <?php echo $vendor['address']; ?></small></p>
                
                <div class="row">
                    <div class="col-md-3 mb-4">
                        <div class="card total-cylinders">
                            <div class="card-header">
                                <i class="fas fa-shopping-cart icon"></i>Purchased Cylinders
                            </div>
                            <div class="card-body">
                                <h5 class="card-title">
                                    <a href="<?php echo baseurl('views/purchase-invoices.php'); ?>"><?php echo number_format(array_sum(array_map('intval', $purchasedCylinders))); ?></a>
                                </h5>
                                <?php ksort($purchasedCylinders); foreach ($purchasedCylinders as $productName => $qty): ?>
                                    <span class="badge badge-secondary"><?php echo "$productName ($qty)"; ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 mb-4">
                        <div class="card empty-cylinders">
                            <div class="card-header">
                                <i class="fas fa-exclamation-triangle icon"></i>Empty Return
                            </div>
                            <div class="card-body">
                                <h5 class="card-title">
                                    <a href="<?php echo baseurl('views/purchase-invoices.php'); ?>"><?php echo number_format(array_sum(array_map('intval', $emptyReturnCylinders))); ?></a>
                                </h5>
                                <?php ksort($emptyReturnCylinders); foreach ($emptyReturnCylinders as $productName => $qty): ?>
                                    <span class="badge badge-secondary"><?php echo "$productName ($qty)"; ?></span>
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
                                <i class="fas fa-money-bill-wave icon"></i>Total Payable
                            </div>
                            <div class="card-body">
                                <h5 class="card-title"><a href=""><?php echo number_format($totalPayable); ?></a></h5>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 mb-4">
                        <div class="card pending-refills">
                            <div class="card-header">
                                <i class="fas fa-hand-holding-usd icon"></i>Total Paid
                            </div>
                            <div class="card-body">
                                <h5 class="card-title"><a href=""><?php echo number_format($totalPaid); ?></a></h5>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 mb-4">
                        <div class="card pending-refills">
                            <div class="card-header">
                                <i class="fas fa-exclamation-triangle icon"></i>Balance
                            </div>
                            <div class="card-body">
                                <h5 class="card-title"><a href=""><?php echo number_format($totalBalance); ?></a></h5>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label for="dateRangePicker">Date Range:</label>
                                        <input type="text" id="dateRangePicker" class="form-control mt-0" placeholder="Select date range" />
                                    </div>
                                    <div class="col-md-2">
                                        <label>&nbsp;</label>
                                        <button type="button" id="clearDateFilter" class="btn btn-secondary form-control">Reset to This Month</button>
                                    </div>
                                </div>
                                <div class="table-container">
                                    <table id="vendorLedgerTable" class="table table-striped table-hover">
                                        <thead class="thead-dark">
                                            <tr>
                                                <th>ID</th>
                                                <!-- <th>Vendor</th> -->
                                                <th>Quantity</th>
                                                <th>Empty</th>
                                                <th>Date</th>
                                                <th>Total</th>
                                                <th>Paid</th>
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

    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <?php include('../libs/jslinks.php'); ?>
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script>
    $(document).ready(function() {
        // Get current date and first day of current month
        var today = moment();
        var startOfMonth = moment().startOf('month');

        // Initialize date range picker with default range (start of month to today)
        $('#dateRangePicker').daterangepicker({
            startDate: startOfMonth,
            endDate: today,
            locale: {
                cancelLabel: 'Clear',
                format: 'MM/DD/YYYY'
            }
        });

        // Set the initial value to show the default range
        $('#dateRangePicker').val(startOfMonth.format('MM/DD/YYYY') + ' - ' + today.format('MM/DD/YYYY'));

        $('#dateRangePicker').on('apply.daterangepicker', function(ev, picker) {
            $(this).val(picker.startDate.format('MM/DD/YYYY') + ' - ' + picker.endDate.format('MM/DD/YYYY'));
            vendorLedgerTable.draw();
        });

        $('#dateRangePicker').on('cancel.daterangepicker', function(ev, picker) {
            // Reset to default range (start of month to today)
            var today = moment();
            var startOfMonth = moment().startOf('month');
            $(this).val(startOfMonth.format('MM/DD/YYYY') + ' - ' + today.format('MM/DD/YYYY'));
            vendorLedgerTable.draw();
        });

        $('#clearDateFilter').on('click', function() {
            // Reset to default range (start of month to today)
            var today = moment();
            var startOfMonth = moment().startOf('month');
            $('#dateRangePicker').val(startOfMonth.format('MM/DD/YYYY') + ' - ' + today.format('MM/DD/YYYY'));
            $('#dateRangePicker').data('daterangepicker').setStartDate(startOfMonth);
            $('#dateRangePicker').data('daterangepicker').setEndDate(today);
            vendorLedgerTable.draw();
        });

        var vendorLedgerTable = $('#vendorLedgerTable').DataTable({
            processing: true,
            serverSide: true,
            "paging": true,
            "iDisplayLength": 10,
            "ajax": {
                "url": "vendor-ledger-ajax.php",
                "type": "GET",
                data: function(d) {
                    d.vendor_id = <?php echo $vendor_id; ?>;
                    var dateRange = $('#dateRangePicker').val();
                    if (dateRange && dateRange.includes(' - ')) {
                        var dates = dateRange.split(' - ');
                        d.start_date = dates[0];
                        d.end_date = dates[1];
                    } else {
                        // Use default range if no date is selected
                        d.start_date = moment().startOf('month').format('MM/DD/YYYY');
                        d.end_date = moment().format('MM/DD/YYYY');
                    }
                }
            },
            "columns": [
                { "data": "id" },
                // { "data": "vendor" },
                { "data": "total_cylinders" },
                { "data": "empty_cylinders" },
                { "data": "date" },
                {
                    "data": "grand_total",
                    "render": function(data, type, row) {
                        return Math.round(parseFloat(data)).toLocaleString('en-US');
                    }
                },
                {
                    "data": "paid",
                    "render": function(data, type, row) {
                        return Math.round(parseFloat(data)).toLocaleString('en-US');
                    }
                },
                {
                    "data": "balance",
                    "render": function(data, type, row) {
                        return Math.round(parseFloat(data)).toLocaleString('en-US');
                    }
                },
                { "data": "created_at" },
                { "data": "actions" }
            ],
            rowCallback: function (row, data) {
                if (data.row_class) {
                    $(row).addClass(data.row_class);
                }
            },
        });
    });
    </script>
</body>
</html>
