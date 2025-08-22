<?php
// edit_customer.php
require '../config/db.php';
require_once '../config/db_functions.php';
require '../config/auth.php';

if (isset($_GET['id'])) {
    $customer_id = $_GET['id'];

    $db->where("customer_id",$customer_id);
    $customer = $db->getOne("customers");
    $cId = $customer['id'];

    $db->where("customer_id",$customer_id);
    $security = $db->getOne("security_options");
    

    if(isset($_POST['save'])){
        $name = $_POST['name'];
        $phone = $_POST['phone'];
        $cnic = $_POST['cnic'];
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
    // echo $gasTypes;die();
    // var_dump($gas_types);die();
        $ins_arr = array(
            "customer_id"=>$customer_id,
            "name"=>$name,
            "phone"=>$phone,
            "cnic"=>$cnic,
            "security_deposit"=>$cash_amount,
            "gas_types"=>$gasTypes,
            "active"=>1,
            "updated_at"=>$date
        );
        // var_dump($ins_arr);die();
        $db->where("customer_id",$customer_id);
        $db->update("customers",$ins_arr);

        $so_arr = array(
                "customer_id"=>$customer_id,
                "person_name"=>$person_name,
                "person_cnic"=>$person_cnic,
                "person_phone"=>$person_phone,
                "cash_amount"=>$cash_amount,
                "cheque_details"=>$cheque_details
            );
        // var_dump($ins_arr);die();
        $db->where("customer_id",$customer_id);
        $db->update("security_options",$so_arr);

        //Delete from db if any previous item is deleted from row
        if (isset($_POST['del_item_id'])) {

          $del_id=$_POST['del_item_id'];

          $del_count=count($del_id);

          for ($i=0; $i <$del_count ; $i++) {

            $db->where("id",$del_id[$i]);

            $db->delete("bonus_cylinders");    

          }

        }
        //Edit bonus cylinders
        if (isset($_POST['package_item_id'])) {



          $package_item_id=$_POST['package_item_id'];

          $old_pro_id=$_POST['old_package_id'];

          $old_pro_name=$_POST['old_package_name'];

          $old_pro_quantity=$_POST['old_package_quantity'];

          $pre_old_pro_quantity=$_POST['pre_old_package_quantity'];





          $order_count_pkg=count($package_item_id);

          for ($i=0; $i < $order_count_pkg; $i++) {

            $invoice_pkg_arr_old=array(  

              "product_name"=>$old_pro_name[$i],

              "qty"=>$old_pro_quantity[$i],

              "updated_at"=>$date 

            );

            $db->where("id",$package_item_id[$i]);

            $db->update('bonus_cylinders',$invoice_pkg_arr_old);
          }

        }
        // Add new bonus cylinders
        if (isset($_POST['package_id'])) {
            $pro_id=$_POST['package_id'];
            $pro_name=$_POST['package_name'];
            $pro_quantity=$_POST['package_quantity'];

            $total_pkg=count($pro_id);
            for ($i=0; $i < $total_pkg; $i++) { 

                if( $pro_id[$i] != '' ){
                    $bonus_arr=array( 
                    "customer_id"=>$cId,
                    "product_id"=>$pro_id[$i],
                    "product_name"=>$pro_name[$i],
                    "qty"=>$pro_quantity[$i],
                    "created_at"=>$date
                    );
                    $db->insert('bonus_cylinders',$bonus_arr);
                }

            }
        }
        header("Location: customer_management.php");
        exit();
    }
} else {
    header("Location: customer_management.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Customer</title>
    <?php include '../libs/links.php'; ?>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
</head>
<body>
  <?php include '../libs/sidebar.php'; ?>

    <div class="container">
        <header><h1 class="text-white mb-0">Edit Customer</h1></header>
        <form method="POST" action="edit_customer.php?id=<?php echo $customer_id; ?>">
            <h5>Cutomer Info</h5>
            <div class="form-group">
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($customer['name']); ?>" required>
            </div>
            <div class="form-group">
                <label for="phone">Phone:</label>
                <input type="text" id="phone" name="phone" class="form-control" value="<?php echo htmlspecialchars($customer['phone']); ?>" required>
            </div>
            <div class="form-group">
                <label for="cnic">CNIC:</label>
                <input type="text" id="cnic" name="cnic" class="form-control" value="<?php echo htmlspecialchars($customer['cnic']); ?>" required>
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
                        $selected = in_array($gas_type['id'], $gasTypeArray) ? 'selected' : '';
                        ?>
                        <option value="<?php echo $gas_type['id']; ?>" <?php echo $selected; ?>><?php echo $gas_type['name']; ?></option>
                    <?php } ?>
                </select>
            </div>
            <!-- <div class="form-group">
                <label for="bonus_cylinders">Bonus Cylinders (optional):</label>
                <input type="number" id="bonus_cylinders" name="bonus_cylinders" class="form-control" value="<?php echo htmlspecialchars($customer['bonus_cylinders']); ?>">
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
                            <tbody id="addinvoiceItem">
                                <?php 

                                $db->where("customer_id",$cId);

                                $detail=$db->get("bonus_cylinders");

                                foreach($detail as $de){ ?>
                                    <tr class="invoice-row<?php echo $de['product_id']; ?>">

                                      <td style="width: 500px">

                                        <input name="old_package_name[]" class="form-control form-control-sm productSelection" required="" value="<?php echo $de['product_name']; ?>" autocomplete="off" tabindex="1" type="text">

                                        <input type="hidden" name="package_item_id[]" class="autocomplete_hidden_value" value="<?php echo $de['id']; ?>" >

                                        <input type="hidden"  value="<?php echo $de['product_id']; ?>" name="old_package_id[]">

                                      </td>



                                      <td style="width: 150px;">

                                        <input name="pre_old_package_quantity[]" class=" form-control form-control-sm"  value="<?php echo $de['qty']; ?>" type="hidden">



                                        <input name="old_package_quantity[]" autocomplete="off" class="total_qty_1 form-control form-control-sm mt-2" id="total_qty_<?php echo $de['product_id']; ?>" onkeyup="quantity_calculate(<?php echo $de['product_id']; ?>);" value="<?php echo $de['qty']; ?>" required="" placeholder="0.00" tabindex="3" type="number"> 

                                        <input type="hidden" id="stock_qty<?php echo $de['product_id']; ?>" value="<?php $db->where('id',$de['product_id']); echo $stockQty=$db->getValue('cylinders','qty'); ?>"/>

                                      </td>

                                      <td>

                                        <button class="btn btn-danger btn-rounded btn-icon btn-del" type="button" onclick="deleteRowOld('<?php echo $de['product_id']; ?>','<?php echo $de['id']; ?>')" value="Delete" tabindex="5"><i class="fa fa-trash"></i></button>

                                      </td>

                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>                            
                    </div>
                </div>
            </div>
            <h5>Cutomer Security</h5>
            <div class="form-group" id="person_security" >
                <label for="person_name">Person Name:</label>
                <input type="text" id="person_name" name="person_name" class="form-control" value="<?php echo htmlspecialchars($security['person_name']); ?>">
                <label for="person_cnic">Person CNIC:</label>
                <input type="text" id="person_cnic" name="person_cnic" class="form-control" value="<?php echo htmlspecialchars($security['person_cnic']); ?>">
                <label for="person_phone">Person Phone:</label>
                <input type="text" id="person_phone" name="person_phone" class="form-control" value="<?php echo htmlspecialchars($security['person_phone']); ?>">
            </div>
            <div class="form-group" id="cash_security" >
                <label for="cash_amount">Cash Amount:</label>
                <input type="number" id="cash_amount" name="cash_amount" class="form-control" value="<?php echo htmlspecialchars($security['cash_amount']); ?>">
            </div>
            <div class="form-group" id="cheque_security" >
                <label for="cheque_details">Cheque Details:</label>
                <input type="text" id="cheque_details" name="cheque_details" class="form-control" value="<?php echo htmlspecialchars($security['cheque_details']); ?>">
            </div>
            <button type="submit"  name="save" class="btn btn-primary">Update Customer</button>
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
    });
    $(".js-example-basic-single").change(function(){
        var pac_id = $(this).children("option:selected").val();

        if (pac_id != '') {


          var pac_price = $('option:selected', this).attr('package-price');
          var stockQty = $('option:selected', this).attr('stock-qty');
          var pac_name=$('option:selected', this).attr('package-name');

          var pro_full_name = pac_name 

          if ($('tr').hasClass('invoice-row'+pac_id+'')) {
            alert("This Product Already Added!");
        } else{



            $("#addinvoiceItem").append('<tr class="invoice-row'+pac_id+'"><td style="width: 500px"><input name="package_name[]"  class="form-control form-control-sm productSelection"  required="" value="'+pro_full_name+'" autocomplete="off" tabindex="1" type="text"><input type="hidden" class="autocomplete_hidden_value" value="'+pac_id+'" name="package_id[]" ></td><td style="width: 150px;"><input name="package_quantity[]" autocomplete="off" class="total_qty_1 form-control form-control-sm mt-2" id="total_qty_'+pac_id+'" onkeyup="quantity_calculate('+pac_id+');" value="1" required="" placeholder="0.00" tabindex="3" type="number"><input type="hidden" id="stock_qty'+pac_id+'" value="'+stockQty+'"/> </td><td><button  class="btn btn-danger btn-rounded btn-icon btn-del" type="button" onclick="deleteRowOld('+pac_id+')" value="Delete" tabindex="5"><i class="fa fa-trash"></i></button></td></tr>');

            
            $(".select2-search__field").val('');
            $(".js-example-basic-single").select2("open");


        }
    } else{
      var text='Please Select Valid Item!';
      showToast('error',text,'Notification');
    }

    });
    function deleteRowOld(rem_id,meta_id) {



    $(".js-example-basic-single").on("select2-closed", function(e) {

      $(".js-example-basic-single").select2("open");

    });

    $("#addinvoiceItem").append('<input type="hidden" value="'+meta_id+'" name="del_item_id[]">');

    $(".invoice-row"+rem_id+"").remove();

    CalculateTotalAmount();



  }
</script>
</body>
</html>
