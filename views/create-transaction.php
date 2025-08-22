<?php
require_once '../config/db.php';
require_once '../config/db_functions.php';
require '../config/auth.php';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create Transaction</title>
    <?php include '../libs/links.php'; ?>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    
</head>
<body>
  <?php include '../libs/sidebar.php'; ?>

  <div class="container-fluid mt-4">
    <div class="card">
      <div class="card-header bg-dark">
        <h1 class="text-white mb-0">Create Transaction</h1>
    </div>
    <div class="card-body">
        <?php 
        if(isset($_POST['save'])){
            $entity=$_POST['entity'];
            if(!empty($_POST['customer_id']))
            {
                $customer_id=$_POST['customer_id'];
            }else{
                $customer_id=null;
            }
            if(!empty($_POST['vendor_id']))
            {
                $vendor_id=$_POST['vendor_id'];
            }else{
                $vendor_id=null;
            }
            $trdate=$_POST['date'];
            $amount=$_POST['amount'];
            $time_zone = date_default_timezone_set("Asia/Karachi");
            $date = date("Y-m-d h:i:s");

            $arr=array( 
                "entity"=>$entity,
                "customer_id"=>$customer_id,
                "vendor_id"=>$vendor_id,
                "date"=>$trdate,
                "amount"=>$amount,
                "created_at"=>$date

            );

            $transaction=$db->insert('transactions',$arr);
            if (!empty($transaction)){
                echo "<div class='alert alert-fill-success' role='alert'><i class='mdi mdi-alert-circle'></i>Data Saved Successfully.</div>";
                ?>
                <script>window.location.href="<?php echo baseurl('views/transaction_management.php'); ?>";</script>
                <?php
            }else{
                echo "<div class='alert alert-fill-danger' role='alert'><i class='mdi mdi-alert-circle'></i>Alert! Data Not Saved.</div>";
            }
            
        }
        ?>
        <form action="" method="POST">
            <div class="form-row">
                <div class="form-group col-md-3">
                    <label for="entity">Type</label>
                    <select class="form-control" id="entity" name="entity" required>
                        <option value="">Select Any</option>
                        <option value="sale">Sale</option>
                        <option value="purchase">Purchase</option>
                    </select>
                </div>
                <div class="form-group col-md-3" id="customer-field" style="display:none;">
                    <label for="customer_id">Customer</label>
                    <?php
                    $db->where("deleted_at", NULL, 'IS');
                    $customer = $db->get("customers");
                    ?>
                    <select id="cus_name" name="customer_id" class="form-control">
                        <option value="">Select Customer</option>
                        <?php foreach ($customer as $cus) { 
                            $receivedCustomersArray = [];
                            $balance = 0;
                            $totalReceivable = 0;
                            $totalReceived = 0;

                            $db->where("deleted_at", NULL, 'IS');
                            $db->where("customer_id", $cus['id']);
                            $invoices = $db->get("invoices");
                            foreach ($invoices as $invoice) {
                                $totalReceivable += $invoice['grand_total'];

                                if (!in_array($invoice['customer_id'], $receivedCustomersArray)) {
                                    array_push($receivedCustomersArray, $invoice['customer_id']);
                              }
                            }

                            if (!empty($receivedCustomersArray)) {
                                $db->where('customer_id', $receivedCustomersArray, 'IN');
                                $db->where("deleted_at", NULL, 'IS');
                                $transactions = $db->get("transactions");
                                foreach ($transactions as $transaction) {
                                    $totalReceived += $transaction['amount'];
                                }
                            }
                            $balance = $totalReceivable - $totalReceived;
                            ?>
                            <option value="<?php echo $cus['id']; ?>"><?php echo $cus['name'].'( '.$balance.' )'; ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group col-md-3" id="vendor-field" style="display:none;">
                    <label for="vendor_id">Vendor</label>
                    <?php
                    $db->where("deleted_at", NULL, 'IS');
                    $vendors = $db->get("vendors");
                    ?>
                    <select id="ven_name" name="vendor_id" class="form-control">
                        <option value="">Select Vendor</option>
                        <?php foreach ($vendors as $vendor) { ?>
                            <option value="<?php echo $vendor['id']; ?>"><?php echo $vendor['name']; ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group col-md-3">
                    <label for="date">Date</label>
                    <input type="date" name="date" value="<?php echo date("Y-m-d"); ?>" class="form-control" autocomplete="off" required>
                </div>
                <div class="form-group col-md-3">
                    <label for="amount">Amount</label>
                    <input type="number" name="amount" value="0" class="form-control" autocomplete="off" required>
                </div>
            </div>
            <button type="submit" name="save" class="btn btn-primary">Create</button>
            <a href="transaction_management.php" class="btn btn-secondary text-white">Back</a>
        </form>
    </div>
</div>
</div>
<?php include('../libs/jslinks.php'); ?>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() { $("#ven_name,#cus_name").select2(); $('#ven_name,#cus_name').select2('open'); });
    $(document).ready(function() {
        $('#entity').change(function() {
            var selectedType = $(this).val();
            if (selectedType == 'sale') {
                $('#customer-field').show();
                $('#vendor-field').hide();
            } else if (selectedType == 'purchase') {
                $('#customer-field').hide();
                $('#vendor-field').show();
            } else {
                $('#customer-field').hide();
                $('#vendor-field').hide();
            }
        });
            // Trigger change event on page load
        $('#entity').change();
    });
</script>
</body>
</html>
