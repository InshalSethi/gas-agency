<?php
// views/dashboard.php
require '../config/db.php';
require_once '../config/db_functions.php';
require '../config/auth.php';
if (isset($_REQUEST['id'])) {
  $x=$_REQUEST['id'];
  if ( $x != '')   {
    date_default_timezone_set("Asia/Karachi");
    $delDate =  date("Y-m-d h:i:s");

    $uparr = array("deleted_at"=>$delDate);
    $db->where("id",$x);
    $db->update('invoices',$uparr);
?>
<script>  window.location.href="sale-invoices.php"; </script>
    <?php
  }    } 
  ?>
<!DOCTYPE html>
<html>
<head>
    <title>Sale Invoices</title>
    <?php include '../libs/links.php'; ?>
    <link rel="stylesheet" type="text/css" href="<?php echo baseurl('css/daterangepicker.css'); ?>" />
</head>
<body>
  <?php include '../libs/sidebar.php'; ?>

  <div class="container-fluid mt-4">
    <header><h4 class="text-white">Sale Invoices</h4></header>
    <div class="table-container">
        <div class="mt-2 mb-2">
            <div class="row">
              <div class="col-md-3">
                <input type="text" id="dateRangePicker" class="form-control filter w-100" placeholder="Select Date Range" />
              </div>
              <div class="col-md-3"></div>
              <div class="col-md-2">
                <button class="btn btn-info mt-2 w-100" id="resetFilterBtn"><i class="fa fa-refresh"></i> Reset Filter</button>
              </div>
              <div class="col-md-2">
                <button class="btn btn-primary mt-2 w-100" id="exportBtn"><i class="fa fa-download"></i> Download Excel</button>
              </div>
              <div class="col-md-2">
                <a class="btn btn-success mt-2 w-100" href="add-sale-invoice.php"><i class="fa fa-plus"></i> Add Invoice</a>
              </div>
            </div>
        </div>
        <table id="saleInvoiceTable" class="table table-striped table-hover">
            <thead class="thead-dark">
                <tr>
                    <th>ID</th>
                    <th>Customer</th>
                    <th>Note</th>
                    <th>Empty</th>
                    <th>QTY</th>
                    <th>Date</th>
                    <th>Total</th>
                    <th>Received</th>
                    <th>Balance</th>
                    <th>Cretaed At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
  </div>

  <?php include('../libs/jslinks.php'); ?>
  <script type="text/javascript" src="<?php echo baseurl('js/moment.min.js'); ?>"></script>
  <script type="text/javascript" src="<?php echo baseurl('js/daterangepicker.min.js'); ?>"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.16.9/xlsx.full.min.js"></script>




  <script>

    function deleteRow(clicked_id) {
      var txt;
      var r = confirm(" Are you sure to delete this?");
      if (r == true) { 
        txt = "You pressed OK!";

        var stateID = clicked_id;

        window.location = "sale-invoices.php?id="+clicked_id; 

      } else {


      }

    }
    var defaultStartDate = moment().subtract(0, 'days');
    var defaultEndDate = moment();
    // Initialize date range picker
    $('#dateRangePicker').daterangepicker({
        opens: 'left'
    }, function(start, end, label) {
        $('#dashboardTable').DataTable().ajax.reload();
    });
    $(document).ready(function () {
        
        // Initialize DataTables
        var saleInvoiceTable = $('#saleInvoiceTable').DataTable({
            processing: true,
            serverSide: true,
            "paging":   true,
            "iDisplayLength": 100,
            "ajax": {
                "url": "sale_invoice_ajax.php",
                "type": "GET",
                "data": function(d) {
                    d.search_query = $('#search_query').val();
                    var dateRange = $('#dateRangePicker').val().split(' - ');
                    d.start_date = dateRange[0];
                    d.end_date = dateRange[1];
                }
            },
            "columns": [
                { "data": "id" },
                { "data": "customer" },
                { "data": "description" },
                { "data": "empty_cylinders" },
                { "data": "qty" },
                { "data": "date" },
                { "data": "grand_total" },
                { "data": "received" },
                { "data": "balance" },
                { "data": "created_at" },
                { "data": "actions" }
            ]
        });

        // Search form submission
        $('input[type="search"]').keyup(function (e) {
            e.preventDefault();
            saleInvoiceTable.draw();
        });

          $('.filter').change(function(e) {
            e.preventDefault();
            saleInvoiceTable.draw();
          });

          $('#resetFilterBtn').click(function() {
            // Reset date range picker to default values
            $('#dateRangePicker').data('daterangepicker').setStartDate(defaultStartDate);
            $('#dateRangePicker').data('daterangepicker').setEndDate(defaultEndDate);
            
            // Reload the DataTable
            saleInvoiceTable.draw();
          });


        // Function to download Excel file
          function downloadExcel() {
            var tableData = [];
            // Define the desired header order
            var headers = ['Note', 'Customer', 'Qty', 'Received', 'Empty'];
            tableData.push(headers);

            // Get table rows and push only the desired columns
            $('#saleInvoiceTable tbody tr').each(function() {
                var rowData = [];
                var columns = $(this).find('td');
                rowData.push(columns.eq(2).text().trim()); // Note
                rowData.push(columns.eq(1).text().trim()); // Customer
                rowData.push(columns.eq(4).text().trim()); // Qty
                rowData.push(columns.eq(7).text().trim()); // Received
                rowData.push(columns.eq(3).text().trim()); // Empty
                tableData.push(rowData);
            });

            // Convert to worksheet
            var worksheet = XLSX.utils.aoa_to_sheet(tableData);
            var workbook = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(workbook, worksheet, "Sheet1");

            // Generate Excel file and trigger download
            XLSX.writeFile(workbook, 'saleInvoiceTable.xlsx');
        }

          // Attach event listener to the button
          $('#exportBtn').click(function() {
            downloadExcel();
          });

    });
  </script>
</body>
</html>
