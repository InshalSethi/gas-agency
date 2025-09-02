<?php
// views/dashboard.php
require '../config/db.php';
require_once '../config/db_functions.php';
require '../config/auth.php';
if (isset($_REQUEST['id'])) {
    $x=$_REQUEST['id'];
    date_default_timezone_set("Asia/Karachi");
    $delDate =  date("Y-m-d h:i:s");
    if ( $x != '')   {
        $db->where("id",$x);
        $transaction=$db->getOne("transactions");
        $amount = $transaction['amount'];
        if($transaction['invoice_id'])
        {
            $db->where('id',$transaction['invoice_id']);
            $invoice = $db->getOne('invoices');
            $invoiceReceived = $invoice['received'];
            $invoiceBalance = $invoice['balance'];
            
            $balance = $invoiceBalance + $amount;
            $received = $invoiceReceived - $transaction['amount'];
            
            $invArr=array( 
                "received"=>$received,
                "balance"=>$balance
                
            );
                // var_dump($invArr);die();
            $db->where('id',$transaction['invoice_id']);
            $db->update('invoices',$invArr);
            $uparr = array("amount"=>$received,"deleted_at"=>$delDate);
                // echo $amount;die();
        }else{
            $uparr = array("deleted_at"=>$delDate);
        }
        
        
        
        $db->where("id",$x);
        $db->update('transactions',$uparr);
        ?>
        <script>  window.location.href="transaction_management.php"; </script>
        <?php
    }    } 
    ?>
    <!DOCTYPE html>
    <html>
    <head>
      <head>
          <title>Transactions</title>
          <?php include '../libs/links.php'; ?>
      </head>
      <body>
          <?php include '../libs/sidebar.php'; ?>
          
          <div class="container-fluid mt-4">
            <header><h1 class="text-white mb-0">Transactions</h1></header>
            <div class="table-container mt-4">
                <!-- Date Range Filter -->
                <div class="row mb-3">
                    <div class="col-md-4">
                        <input type="text" id="daterange" class="form-control form-control-sm mt-0" style="background: white; cursor: pointer;" readonly>
                        <input type="hidden" id="start_date">
                        <input type="hidden" id="end_date">
                    </div>
                    <div class="col-md-4">
                        <button type="button" id="reset_filter_btn" class="btn btn-secondary btn-sm">
                            <i class="fa fa-refresh"></i> Reset to This Week
                        </button>
                    </div>
                    <div class="col-md-4 d-flex justify-content-end align-items-end">
                        <a class="btn btn-success" href="create-transaction.php"><i class="fa fa-plus"></i> Create Transaction</a>
                    </div>
                </div>

                <!-- Balance Summary -->
                <div class="row mb-3" id="balanceSummary" style="display: none;">
                    <div class="col-md-12">
                        <div class="card bg-dark border-light">
                            <div class="card-body py-2">
                                <div class="row text-center">
                                    <div class="col-md-4">
                                        <small class="text-muted">Opening Balance</small>
                                        <div class="h6 mb-0" id="openingBalanceDisplay">0.00</div>
                                    </div>
                                    <div class="col-md-4">
                                        <small class="text-muted">Period Activity</small>
                                        <div class="h6 mb-0" id="periodActivityDisplay">0.00</div>
                                    </div>
                                    <div class="col-md-4">
                                        <small class="text-muted">Closing Balance</small>
                                        <div class="h6 mb-0" id="closingBalanceDisplay">0.00</div>
                                        <small class="text-muted" style="font-size: 0.7rem;">*After all transactions in date range</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

              <table id="customerTable" class="table table-striped table-hover">
                <thead class="thead-dark">
                    <tr>
                        <th>ID</th>
                        <th>Customer</th>
                        <th>Vendor</th>
                        <th>Date</th>
                        <th>Created At</th>
                        <th>Received</th>
                        <th>Paid</th>
                        <th>Running Balance</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
    <?php include('../libs/jslinks.php'); ?>
    <script>
        function deleteRow(clicked_id) {
          var txt;
          var r = confirm(" Are you sure to delete this?");
          if (r == true) { 
            txt = "You pressed OK!";
            
            var stateID = clicked_id;
            
            window.location = "transaction_management.php?id="+clicked_id; 
            
        } else {
            
            
        }
        
    }
    $(document).ready(function () {
        // Function to get this week's date range
        function getThisWeekRange() {
            var today = moment();
            var startOfWeek = today.clone().startOf('week'); // Start from Sunday
            var endOfWeek = today.clone().endOf('week'); // End on Saturday

            return {
                start: startOfWeek,
                end: endOfWeek
            };
        }

        // Initialize date range picker
        var thisWeek = getThisWeekRange();

        $('#daterange').daterangepicker({
            startDate: thisWeek.start,
            endDate: thisWeek.end,
            ranges: {
                'Today': [moment(), moment()],
                'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                'This Week': [moment().startOf('week'), moment().endOf('week')],
                'Last Week': [moment().subtract(1, 'week').startOf('week'), moment().subtract(1, 'week').endOf('week')]
            },
            locale: {
                format: 'MM/DD/YYYY'
            }
        }, function(start, end, label) {
            // Update hidden fields when date range changes
            $('#start_date').val(start.format('YYYY-MM-DD'));
            $('#end_date').val(end.format('YYYY-MM-DD'));

            // Reload the DataTable
            customerTable.ajax.reload();
        });

        // Set initial hidden field values
        $('#start_date').val(thisWeek.start.format('YYYY-MM-DD'));
        $('#end_date').val(thisWeek.end.format('YYYY-MM-DD'));

        // Function to update balance summary
        function updateBalanceSummary(openingBalance, closingBalance) {
            var periodActivity = closingBalance - openingBalance;

            // Format and display opening balance
            $('#openingBalanceDisplay').html(formatBalance(openingBalance));
            $('#openingBalanceDisplay').removeClass('text-success text-danger').addClass(openingBalance >= 0 ? 'text-success' : 'text-danger');

            // Format and display period activity
            $('#periodActivityDisplay').html(formatBalance(periodActivity));
            $('#periodActivityDisplay').removeClass('text-success text-danger').addClass(periodActivity >= 0 ? 'text-success' : 'text-danger');

            // Format and display closing balance
            $('#closingBalanceDisplay').html(formatBalance(closingBalance));
            $('#closingBalanceDisplay').removeClass('text-success text-danger').addClass(closingBalance >= 0 ? 'text-success' : 'text-danger');

            // Show the balance summary
            $('#balanceSummary').show();
        }

        // Function to format balance with proper currency formatting
        function formatBalance(amount) {
            return new Intl.NumberFormat('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(amount);
        }

        // Initialize DataTables
        var customerTable = $('#customerTable').DataTable({
            processing: true,
            serverSide: true,
            "paging": true,
            "iDisplayLength": 50,
            "ajax": {
                "url": "fetch_transaction.php",
                "type": "GET",
                "data": function(d) {
                    d.search_query = $('#search_query').val();
                    d.start_date = $('#start_date').val();
                    d.end_date = $('#end_date').val();
                },
                "dataSrc": function(json) {
                    // Update balance summary when data is loaded
                    updateBalanceSummary(json.openingBalance, json.closingBalance);
                    return json.data;
                }
            },
            "columns": [
                { "data": "id" },
                { "data": "customer_name" },
                { "data": "vendor_name" },
                { "data": "date" },
                { "data": "created_at" },
                { "data": "received" },
                { "data": "paid" },
                { "data": "balance" },
                { "data": "actions" }
                ]
        });

        // Reset filter functionality
        $('#reset_filter_btn').on('click', function() {
            var thisWeek = getThisWeekRange();
            $('#daterange').data('daterangepicker').setStartDate(thisWeek.start);
            $('#daterange').data('daterangepicker').setEndDate(thisWeek.end);
            $('#start_date').val(thisWeek.start.format('YYYY-MM-DD'));
            $('#end_date').val(thisWeek.end.format('YYYY-MM-DD'));
            customerTable.ajax.reload();
        });

    });
</script>
</body>
</html>
