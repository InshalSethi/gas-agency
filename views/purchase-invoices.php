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
    $db->update('purchase_invoices',$uparr);
?>
<script>  window.location.href="purchase-invoices.php"; </script>
    <?php
  }    } 
  ?>
<!DOCTYPE html>
<html>
<head>
    <title>Purchase Invoices</title>
    <?php include '../libs/links.php'; ?>
</head>
<body>
  <?php include '../libs/sidebar.php'; ?>

  <div class="container-fluid mt-4">
    <header><h4 class="text-white">Purchase Invoices</h4></header>
    <div class="table-container">
        <div class="d-flex justify-content-end mt-2 mb-2">
          <a class="btn btn-success" href="add-purchase-invoice.php"><i class="fa fa-plus"></i> Add Invoice</a>
        </div>
        <table id="purchaseInvoiceTable" class="table table-striped table-hover">
            <thead class="thead-dark">
                <tr>
                    <th>ID</th>
                    <th>Vendor</th>
                    <th>Empty</th>
                    <th>Total QTY</th>
                    <th>Date</th>
                    <th>Total</th>
                    <th>Paid</th>
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
  <script>
    function deleteRow(clicked_id) {
      var txt;
      var r = confirm(" Are you sure to delete this?");
      if (r == true) { 
        txt = "You pressed OK!";

        var stateID = clicked_id;

        window.location = "purchase-invoices.php?id="+clicked_id; 

      } else {


      }

    }
    $(document).ready(function () {
        
        // Initialize DataTables
        var saleInvoiceTable = $('#purchaseInvoiceTable').DataTable({
            processing: true,
            serverSide: true,
            "paging":   true,
            "iDisplayLength": 100,
            "ajax": {
                "url": "purchase_invoice_ajax.php",
                "type": "GET",
                "data": function(d) {
                    d.search_query = $('#search_query').val();
                }
            },
            "columns": [
                { "data": "id" },
                { "data": "vendor" },
                { "data": "empty_cylinders" },
                { "data": "total_cylinders" },
                { "data": "date" },
                { "data": "grand_total" },
                { "data": "paid" },
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

    });
  </script>
</body>
</html>
