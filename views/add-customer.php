<?php
// edit_customer.php
require '../config/db.php';
require_once '../config/db_functions.php';
require '../config/auth.php';

    if(isset($_POST['save'])){
        $name = $_POST['name'];
        $phone = $_POST['phone'];
        $cnic = $_POST['cnic'];
        $percentage_discount = isset($_POST['percentage_discount']) ? $_POST['percentage_discount'] : 0;
        $percentage_increase = isset($_POST['percentage_increase']) ? $_POST['percentage_increase'] : 0;
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
    $customer_id = uniqid();
        $ins_arr = array(
            "customer_id"=>$customer_id,
            "name"=>$name,
            "phone"=>$phone,
            "cnic"=>$cnic,
            "percentage_discount"=>$percentage_discount,
            "percentage_increase"=>$percentage_increase,
            "security_deposit"=>$cash_amount,
            "gas_types"=>$gasTypes,
            "active"=>1,
            "created_at"=>$date
        );
        // var_dump($ins_arr);die();
        $CusId = $db->insert("customers",$ins_arr);

        $so_arr = array(
                "customer_id"=>$customer_id,
                "person_name"=>$person_name,
                "person_cnic"=>$person_cnic,
                "person_phone"=>$person_phone,
                "cash_amount"=>$cash_amount,
                "cheque_details"=>$cheque_details
            );
        // var_dump($ins_arr);die();
        $db->insert("security_options",$so_arr);

        if (isset($_POST['package_id'])) {
            $pro_id=$_POST['package_id'];
            $pro_name=$_POST['package_name'];
            $pro_quantity=$_POST['package_quantity'];

            $total_pkg=count($pro_id);
            for ($i=0; $i < $total_pkg; $i++) {

                if( $pro_id[$i] != '' ){
                    $bonus_arr=array(
                    "customer_id"=>$CusId,
                    "product_id"=>$pro_id[$i],
                    "product_name"=>$pro_name[$i],
                    "qty"=>$pro_quantity[$i],
                    "created_at"=>$date
                    );
                    $db->insert('bonus_cylinders',$bonus_arr);
                }

            }
        }

        // Add new customer rate adjustment products
        if (isset($_POST['rate_product_id'])) {
            $rate_pro_id=$_POST['rate_product_id'];
            $rate_pro_name=$_POST['rate_product_name'];
            $rate_percentage_discount=$_POST['rate_percentage_discount'];
            $rate_percentage_increase=$_POST['rate_percentage_increase'];

            $total_rate_products=count($rate_pro_id);
            for ($i=0; $i < $total_rate_products; $i++) {

                if( $rate_pro_id[$i] != '' ){
                    $rate_adjustment_arr=array(
                    "customer_id"=>$CusId,
                    "product_id"=>$rate_pro_id[$i],
                    "product_name"=>$rate_pro_name[$i],
                    "percentage_discount"=>$rate_percentage_discount[$i],
                    "percentage_increase"=>$rate_percentage_increase[$i],
                    "created_at"=>$date
                    );
                    // var_dump($rate_adjustment_arr);die();
                    $db->insert('customer_rate_adjustment_products',$rate_adjustment_arr);
                }

            }
        }

        header("Location: customer_management.php");
        exit();
    }
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Customer</title>
    <?php include '../libs/links.php'; ?>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
</head>
<style>

    body{
        overflow-x: hidden;
    }
    .btn-ntf {
        cursor: pointer;
    }

    .table td, .jsgrid .jsgrid-table td {
        padding: 0;
    }

    .btn-del {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 25px!important;
        height: 25px!important;
        padding: 2px!important;
        margin: 0 auto; /* Center the button */
    }

    td.center-icon {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .select2-container--default .select2-results__option[aria-disabled=true] {
        color: #f00;
    }
    @media(min-width: 320px) and (max-width: 1020px){
        body{
            width: fit-content;
        }
    }
</style>
<body>
  <?php include '../libs/sidebar.php'; ?>

    <div class="container">
        <header><h1 class="text-white mb-0">Add Customer</h1></header>
        <form method="POST" action="">
            <h5>Cutomer Info</h5>
            <div class="form-group">
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" class="form-control" value="" required>
            </div>
            <div class="form-group">
                <label for="phone">Phone:</label>
                <input type="text" id="phone" name="phone" class="form-control" value="" required>
            </div>
            <div class="form-group">
                <label for="cnic">CNIC:</label>
                <input type="text" id="cnic" name="cnic" class="form-control" value="" required>
            </div>
            <div class="form-group">
                <label for="percentage_discount">Percentage Discount (%):</label>
                <input type="number" id="percentage_discount" name="percentage_discount" class="form-control" value="0" min="0" max="100" step="0.01">
            </div>
            <div class="form-group">
                <label for="percentage_increase">Percentage Increase (%):</label>
                <input type="number" id="percentage_increase" name="percentage_increase" class="form-control" value="0" min="0" max="100" step="0.01">
            </div>
            <div class="form-group">
                <label for="gasType">Gas Type</label>
                <select class="form-control" id="gasType" name="gas_types[]" multiple="multiple" required>
                    <option value="">Select Cylinder Type</option>
                    <?php 
                    $gasTypeArray = json_decode($customer['gas_types'], true);
                    $cylinder_query = "SELECT id, name FROM cylinders";
                    $cylinder_result = $conn->query($cylinder_query);
                    $gas_types = [];
                    while ($row = $cylinder_result->fetch_assoc()) {
                        $gas_types[] = $row;
                    }
                    foreach ($gas_types as $gas_type) { 
                        ?>
                        <option value="<?php echo $gas_type['id']; ?>" ><?php echo $gas_type['name']; ?></option>
                    <?php } ?>
                </select>
            </div>
            <!-- <div class="form-group">
                <label for="bonus_cylinders">Bonus Cylinders (optional):</label>
                <input type="number" id="bonus_cylinders" name="bonus_cylinders" class="form-control" value="">
            </div> -->
            <h5>Bonus Cylinders</h5>
            <div class="row" style="padding-left: 15px;">
                <div class="col-md-3">
                    <div class="form-group row">
                      <?php 
                      $db->orderBy("name",'asc');
                      $packages=$db->get('cylinders');
                      ?>
                      <select class="js-example-basic-single w-100" style="overflow-x:hidden;">
                        <option value=""  >Select Cylinder</option>
                        <?php
                        foreach($packages as $pac){
                           ?>
                           <option 
                           value="<?php echo $pac['id']; ?>" 
                           package-name="<?php echo $pac['name']; ?>" 
                           package-price="<?php echo $pac['retail_rate']; ?>" 
                           stock-qty=<?php echo $pac['qty']; ?> 
                           >

                           <?php echo $pac['name']; ?>  

                       </option>
                       <?php } ?>
                    </select>
                    </div>
                </div>
                <div class="col-md-9">
                    <div class="table-responsive" >
                        <table class="table table-bordered table-hover" id="normalinvoice">
                            <thead>
                                <tr>
                                  <th class="text-center">Name<i class="text-danger">*</i></th>
                                  <th class="text-center">Quantity</th>
                                  <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody id="addinvoiceItem"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Customer Rate Adjustment Products Section -->
            <h5>Customer Rate Adjustment Products</h5>
            <div class="row" style="padding-left: 15px;">
                <div class="col-md-3">
                    <div class="form-group row">
                      <?php
                      $db->orderBy("name",'asc');
                      $rate_products=$db->get('cylinders');
                      ?>
                      <select class="js-rate-product-select w-100" style="overflow-x:hidden;">
                        <option value=""  >Select Product</option>
                        <?php
                        foreach($rate_products as $rate_product){
                           ?>
                           <option
                           value="<?php echo $rate_product['id']; ?>"
                           data-name="<?php echo $rate_product['name']; ?>"
                           >
                           <?php echo $rate_product['name']; ?>

                           </option>
                           <?php } ?>
                    </select>
                    </div>
                </div>
                <div class="col-md-9">
                    <div class="table-responsive" >
                        <table class="table table-bordered table-hover" id="rateAdjustmentTable">
                            <thead>
                                <tr>
                                  <th class="text-center">Product Name<i class="text-danger">*</i></th>
                                  <th class="text-center">% Discount</th>
                                  <th class="text-center">% Increase</th>
                                  <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody id="addRateAdjustmentItem">
                                <!-- Dynamic rows will be added here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <h5>Cutomer Security</h5>
            <div class="form-group" id="person_security" >
                <label for="person_name">Person Name:</label>
                <input type="text" id="person_name" name="person_name" class="form-control" value="">
                <label for="person_cnic">Person CNIC:</label>
                <input type="text" id="person_cnic" name="person_cnic" class="form-control" value="">
                <label for="person_phone">Person Phone:</label>
                <input type="text" id="person_phone" name="person_phone" class="form-control" value="">
            </div>
            <div class="form-group" id="cash_security" >
                <label for="cash_amount">Cash Amount:</label>
                <input type="number" id="cash_amount" name="cash_amount" class="form-control" value="0">
            </div>
            <div class="form-group" id="cheque_security" >
                <label for="cheque_details">Cheque Details:</label>
                <input type="text" id="cheque_details" name="cheque_details" class="form-control" value="">
            </div>
            <button type="submit" name="save" class="btn btn-primary">Craete</button>
            <button type="button" class="btn btn-secondary" onclick="window.history.back()">Back</button>
        </form>
    </div>
<?php include('../libs/jslinks.php'); ?>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).on('keydown', function(e) {
        // Trigger on alt + s key press
        if (e.altKey && e.key === 's') {
            // Open Select2 dropdown
            $('.js-example-basic-single').select2('open');
        }
        if (e.altKey && e.key === 'a') {
            // Open Select2 dropdown
            $('.js-example-basic-single').select2('close');
        }
    });
    $(document).ready(function() { 
        $('#gasType').select2({
            placeholder: "Select Cylinder Type"
        });
        $(".js-example-basic-single").select2(); $('.js-example-basic-single').select2('open');
        $(".js-rate-product-select").select2({
            placeholder: "Select Product"
        });
    });
    $(".js-example-basic-single").change(function(){
        var pac_id = $(this).children("option:selected").val();

        if (pac_id != '') {


          var pac_price = $('option:selected', this).attr('package-price');
          var stockQty = $('option:selected', this).attr('stock-qty');
          var pac_name=$('option:selected', this).attr('package-name');

          var pro_full_name = pac_name 

          if ($('tr').hasClass('invoice-row'+pac_id+'')) {
            alert("This Product Already Added In Invoice");
        } else{



            $("#addinvoiceItem").append('<tr class="invoice-row'+pac_id+'"><td style="width: 500px"><input name="package_name[]"  class="form-control form-control-sm productSelection"  required="" value="'+pro_full_name+'" autocomplete="off" tabindex="1" type="text"><input type="hidden" class="autocomplete_hidden_value" value="'+pac_id+'" name="package_id[]" ></td><td style="width: 150px;"><input name="package_quantity[]" autocomplete="off" class="total_qty_1 form-control form-control-sm" id="total_qty_'+pac_id+'" onkeyup="quantity_calculate('+pac_id+');" value="1" required="" placeholder="0.00" tabindex="3" type="number"><input type="hidden" id="stock_qty'+pac_id+'" value="'+stockQty+'"/> </td><td><button  class="btn btn-danger btn-rounded btn-icon btn-del" type="button" onclick="deleteRow('+pac_id+')" value="Delete" tabindex="5"><i class="fa fa-trash"></i></button></td></tr>');

            
            $(".select2-search__field").val('');
            $(".js-example-basic-single").select2("open");


        }
    } else{
      var text='Please Select Valid Item!';
      showToast('error',text,'Notification');
    }

    });

    // Rate Adjustment Product Selection Handler
    $(".js-rate-product-select").change(function(){
        var rate_product_id = $(this).children("option:selected").val();
        var rate_product_name = $(this).children("option:selected").data('name');

        if(rate_product_id != ''){
            // Check if product already exists
            var existingProduct = false;
            $('input[name="rate_product_id[]"]').each(function(){
                if($(this).val() == rate_product_id){
                    existingProduct = true;
                    return false;
                }
            });

            if(existingProduct){
                var text='Product already added!';
                showToast('error',text,'Notification');
                return;
            }

            var rate_row_id = Math.floor(Math.random() * 100000);
            var rate_row = '<tr class="rate-adjustment-row'+rate_row_id+'">';
            rate_row += '<td>';
            rate_row += '<input type="hidden" name="rate_product_id[]" value="'+rate_product_id+'">';
            rate_row += '<input type="text" name="rate_product_name[]" value="'+rate_product_name+'" class="form-control" readonly>';
            rate_row += '</td>';
            rate_row += '<td>';
            rate_row += '<input type="number" name="rate_percentage_discount[]" value="0" class="form-control" step="any" min="0" max="100" placeholder="0.00">';
            rate_row += '</td>';
            rate_row += '<td>';
            rate_row += '<input type="number" name="rate_percentage_increase[]" value="0" class="form-control" step="any" min="0" placeholder="0.00">';
            rate_row += '</td>';
            rate_row += '<td class="center-icon">';
            rate_row += '<button type="button" class="btn btn-danger btn-del" onclick="deleteRateAdjustmentRow('+rate_row_id+')"><i class="fa fa-trash"></i></button>';
            rate_row += '</td>';
            rate_row += '</tr>';

            $("#addRateAdjustmentItem").append(rate_row);

            $(".select2-search__field").val('');
            $(".js-rate-product-select").select2("open");
        } else {
            var text='Please Select Valid Product!';
            showToast('error',text,'Notification');
        }
    });

    function deleteRow(rem_id) {

    $(".js-example-basic-single").on("select2-closed", function(e) {
      $(".js-example-basic-single").select2("open");
  });
    $(".invoice-row"+rem_id+"").remove();
      // update grand total
    CalculateTotalAmount();
      // set discount price given
    var total_dis_update=0;
    $(".total_discount").each(function(){
      total_dis_update += + parseFloat($(this).val());
  });
    $("#total_discount_ammount").val(total_dis_update);

}

function deleteRateAdjustmentRow(rem_id) {
    $(".js-rate-product-select").on("select2-closed", function(e) {
        $(".js-rate-product-select").select2("open");
    });
    $(".rate-adjustment-row"+rem_id+"").remove();
}
</script>
</body>
</html>
