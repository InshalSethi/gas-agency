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
                <div class="d-flex justify-content-end mt-2 mb-2">
                  <a class="btn btn-success" href="create-transaction.php"><i class="fa fa-plus"></i> Create Transaction</a>
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
                        <th>Balance</th>
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
        // Initialize DataTables
        var customerTable = $('#customerTable').DataTable({
            processing: true,
            serverSide: true,
            "paging":   true,
            "iDisplayLength": 100,
            "ajax": {
                "url": "fetch_transaction.php",
                "type": "GET",
                "data": function(d) {
                    d.search_query = $('#search_query').val();
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
        
    });
</script>
</body>
</html>
