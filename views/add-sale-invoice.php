<?php
// views/dashboard.php
require '../config/db.php';
require_once '../config/db_functions.php';
require '../config/auth.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Create Sale Invoices</title>
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

  <div class="container mt-4" style="">
    <!-- <header><h4 class="text-white">Create Sale Invoices</h4></header> -->
    <div class="row">

        <?php
        if(isset($_POST['save'])){
            $customer_id=$_POST['customer_id'];
            $empty_cylinders=$_POST['empty_cylinders'];
            $invoice_date=$_POST['date'];

            $sub_total=$_POST['grand_total_price'];
            $flat_discount=$_POST['flat_discount'];
            $perc_discount=$_POST['perc_discount'];
            $grand_total=$_POST['total_ammount_after_dis'];
            $received=$_POST['paid_amount'];
            $balance=$_POST['due_amount'];
            $description=$_POST['description'];
            $time_zone = date_default_timezone_set("Asia/Karachi");
            $date = date("Y-m-d h:i:s");




            $invoice_array=array( 
                "customer_id"=>$customer_id,
                "empty_cylinders"=>$empty_cylinders,
                "date"=>$invoice_date,
                "flat_discount"=>$flat_discount,
                "percentage_discount"=>$perc_discount,
                "sub_total"=>$sub_total,
                "grand_total"=>$grand_total,
                "received"=>$received,
                "balance"=>$balance,
                "description"=>$description,
                "created_at"=>$date

            );

            $invoice_id=$db->insert('invoices',$invoice_array); 

            $pro_id=$_POST['package_id'];
            $pro_name=$_POST['package_name'];
            $pro_empty_quantity=$_POST['empty_quantity'];
            $pro_quantity=$_POST['package_quantity'];
            $pro_rate=$_POST['package_rate'];
            $total_price=$_POST['total_price'];
            $increase_flags = isset($_POST['increase_flag']) ? $_POST['increase_flag'] : array();


            $total_pkg=count($pro_id);
            for ($i=0; $i < $total_pkg; $i++) { 

                if( $pro_id[$i] != '' ){
                  // Check if increase flag is set for this item
                  $increase_value = 0;
                  if (isset($increase_flags[$i]) && $increase_flags[$i] == '1') {
                      $increase_value = 1;
                  }

                  $invoice_pkg_arr=array(
                    "invoice_id"=>$invoice_id,
                    "product_id"=>$pro_id[$i],
                    "product_name"=>$pro_name[$i],
                    "empty_qty"=>$pro_empty_quantity[$i],
                    "qty"=>$pro_quantity[$i],
                    "rate"=>$pro_rate[$i],
                    "total"=>$total_price[$i],
                    "increase"=>$increase_value,
                    "created_at"=>$date
                );
                  $invoiceItemId = $db->insert('invoice_items',$invoice_pkg_arr);
                  RemoveStockQty($pro_id[$i],$pro_quantity[$i],$db);
                  addEmptyStockQty($pro_id[$i],$pro_empty_quantity[$i],$db);

                  if($pro_empty_quantity[$i] > 0)
                  {
                    $cyarr=array( 
                        "invoice_id"=>$invoice_id,
                        "invoice_item_id"=>$invoiceItemId,
                        "cylinders"=>$pro_empty_quantity[$i],
                        "status"=>'pending',
                        "created_at"=>$date
                    );

                    $db->insert('empty_cylinders',$cyarr);
                  }
              }

          }

          if($received > 0)
          {
            $arr=array( 
                "invoice_id"=>$invoice_id,
                "entity"=>'sale',
                "customer_id"=>$customer_id,
                "date"=>$invoice_date,
                "amount"=>$received,
                "created_at"=>$date

            );

            $transaction=$db->insert('transactions',$arr);
          }

          
      } 
      
      ?>


      <div class="col-12 grid-margin">
          <!-- <div class="card">
            <div class="card-body"> -->
                <h4 class="card-title"> Invoice </h4>
                <form action=""  method="POST" class="form-sample">



                    <div class="row">
                      <div class="col-md-5 inv-col">
                        <div class="form-group row">
                          <label class="col-sm-12 col-form-label">Customer Name</label>
                          <div class="col-sm-12">
                            <?php
                            $db->where("deleted_at", NULL, 'IS');
                            $customer=$db->get("customers");

                            ?>
                            <select  id="cus_name"  name="customer_id" class="form-control form-control-sm customer_name" required >
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
                </div>
            </div>
            <div class="col-md-3 inv-col" style="display: none;">
                <div class="form-group row">
                  <label class="col-sm-12 col-form-label"> Empty Cylinders</label>
                  <div class="col-sm-12">
                    <input type="number" name="empty_cylinders" value="" class="form-control form-control-sm" autocomplete="off">
                </div>
            </div>
        </div>
        <div class="col-md-3 inv-col">
            <div class="form-group row">
              <label class="col-sm-12 col-form-label"> Date</label>
              <div class="col-sm-12">
                <input type="date" name="date" value="<?php echo date("Y-m-d"); ?>" class="form-control form-control-sm" autocomplete="off">
            </div>
        </div>
    </div>
    <div class="col-md-4 inv-col">
                <div class="form-group row">
                  <label class="col-sm-12 col-form-label"> Description</label>
                  <div class="col-sm-12">
                    <textarea type="text" name="description" value="" cols="2" rows="2" class="form-control form-control-sm" autocomplete="off" required></textarea>
                </div>
            </div>
        </div>
</div>


<div class="row">
  <div class="col-md-3">
    <div class="form-group row">
      <?php 
      $db->where("deleted_at", NULL, 'IS');
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
       <?php
   } ?>
</select>
</div>

</div>


<div class="col-md-9">
  <div class="table-responsive" >
    <table class="table table-bordered table-hover" id="normalinvoice">
      <thead>
        <tr>
          <th class="text-center" style="width: 80px;">
            <input type="checkbox" id="selectAllIncrease" onchange="toggleAllIncrease()" title="Select All for Increase" />
            <label for="selectAllIncrease" class="mb-0 ml-1" style="font-size: 12px;">All</label>
          </th>
          <th class="text-center">Product Name<i class="text-danger">*</i></th>
          <th class="text-center">Empty</th>
          <th class="text-center">Quantity</th>
          <th class="text-center">Rate <i class="text-danger">*</i></th>
          <th class="text-center">Total </th>
          <th class="text-center">Action</th>
      </tr>
  </thead>
  <tbody id="addinvoiceItem">

  </tbody>
  <tfoot>
      <tr>
        <td colspan="4" style="text-align:right;"><b>Sub Total:</b></td>
        <td class="text-right" colspan="2" >
          <input id="grandTotal" class="form-control form-control-sm" name="grand_total_price" value="0.00" tabindex="-1"  type="number" readonly="">
      </td>
  </tr>
  <tr>
    <td style="text-align:right;" colspan="4"><b>Flat Discount:</b></td>
    <td class="text-right" colspan="2">
      <input id="flat_discount" autocomplete="off" onkeyup="calculations();" class="form-control form-control-sm" name="flat_discount" tabindex="-1" value="0" type="number" step="any" min="0" >
  </td>
</tr>
<tr>
    <td style="text-align:right;" colspan="4"><b>% Discount:</b></td>
    <td class="text-right" colspan="2">
      <input id="perc_discount" autocomplete="off" onkeyup="calculations();" class="form-control form-control-sm" name="perc_discount" tabindex="-1" value="0" type="number" step="any" min="0" max="100" >
  </td>
</tr>


<tr>
    <td style="text-align:right;" colspan="4"><b>Grand Total:</b></td>
    <td class="text-right" colspan="2">
      <input id="total_ammount_after_dis" class="form-control form-control-sm" name="total_ammount_after_dis" tabindex="-1" value="0.00" readonly="" type="number">
  </td>
</tr>

<tr>
    <td style="text-align:right;" colspan="4"><b>Paid Amount:</b></td>
    <td class="text-right" colspan="2">
      <input id="paidAmount" autocomplete="off" class="form-control form-control-sm" name="paid_amount" onkeyup="invoice_paidamount();" value="0" value="" placeholder="0.00" tabindex="7" type="number">
  </td>
</tr>
<tr>                               
    <td style="text-align:right;" colspan="4"><b>Balance:</b></td>
    <td class="text-right" colspan="2">
      <input id="dueAmmount" class="form-control form-control-sm" name="due_amount" value="0.00"  type="number" readonly="">
  </td>
</tr>
</tfoot>
</table>                            
</div>
</div>

</div>







<div class="row" style="margin-top: 30px;">
    <div class="col-md-12">
      <div class="text-center">
        <input class="btn btn-primary" name="save" id="add_invoice" type="submit" value="Create Invoice">

    </div>
</div>
</div>






</form>
<!-- </div>
</div> -->
</div>

</div>

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
        $(".js-example-basic-single,.customer_name").select2();
        $('.js-example-basic-single').select2('open');

        // Auto-apply customer discount when customer is selected
        $('.customer_name').on('change', function() {
            var customer_id = $(this).val();
            if (customer_id) {
                fetchCustomerDiscount(customer_id);
            } else {
                // Reset discount if no customer selected
                $('#perc_discount').val(0);
                calculations();
            }
        });
    });

    // Global variable to store customer percentage increase
    var customerPercentageIncrease = 0;

    // Function to fetch customer discount via AJAX
    function fetchCustomerDiscount(customer_id) {
        $.ajax({
            url: 'get-customer-discount.php',
            type: 'GET',
            data: { customer_id: customer_id },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Store customer percentage increase globally
                    customerPercentageIncrease = response.data.percentage_increase || 0;

                    // Auto-populate percentage discount
                    var discount = response.data.percentage_discount || 0;
                    $('#perc_discount').val(discount);

                    // Clear flat discount to avoid conflicts
                    $('#flat_discount').val(0);

                    // Remove readonly attributes to ensure calculation works
                    $('#perc_discount').removeAttr('readonly');
                    $('#flat_discount').removeAttr('readonly');

                    // Add visual feedback for auto-applied discount
                    if (discount > 0) {
                        $('#perc_discount').addClass('border-success');
                        setTimeout(function() {
                            $('#perc_discount').removeClass('border-success');
                        }, 2000);
                    }

                    // Force trigger calculations multiple times to ensure it works
                    setTimeout(function() {
                        calculations();
                    }, 100);

                    // Also trigger change event on discount field
                    $('#perc_discount').trigger('change');
                    $('#perc_discount').trigger('keyup');

                    console.log('Customer discount applied:', discount + '%');
                    console.log('Customer increase available:', customerPercentageIncrease + '%');

                    // Update base prices for existing cylinders when customer is selected
                    updateExistingCylinderBasePrices();
                } else {
                    console.error('Error fetching customer discount:', response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error fetching customer discount:', error);
            }
        });
    }

    // Function to update base prices for existing cylinders when customer is selected
    function updateExistingCylinderBasePrices() {
        $('.increase-checkbox').each(function() {
            var checkbox = $(this);
            var product_id = checkbox.data('product-id');
            var currentRate = parseFloat($('#item_price_' + product_id).val());

            // Update the base price to current rate (in case it was already modified)
            $('#base_price_' + product_id).val(currentRate);

            console.log('Updated base price for product ' + product_id + ': ' + currentRate);
        });
    }

    // Function to toggle all percentage increases
    function toggleAllIncrease() {
        var selectAllCheckbox = $('#selectAllIncrease');
        var isChecked = selectAllCheckbox.is(':checked');

        // Apply to all existing item checkboxes
        $('.increase-checkbox').each(function() {
            var checkbox = $(this);
            var product_id = checkbox.data('product-id');

            // Set checkbox state
            checkbox.prop('checked', isChecked);

            // Trigger individual toggle function to handle calculations
            toggleIncrease(product_id);
        });

        console.log('Select all increase: ' + (isChecked ? 'checked' : 'unchecked'));
    }

    // Function to toggle percentage increase for specific item
    function toggleIncrease(product_id) {
        var checkbox = $('#increase_' + product_id);
        var basePrice = parseFloat($('#base_price_' + product_id).val());
        var currentRate = parseFloat($('#item_price_' + product_id).val());
        var quantity = parseFloat($('#total_qty_' + product_id).val()) || 1;

        if (checkbox.is(':checked')) {
            // Apply percentage increase
            if (customerPercentageIncrease > 0) {
                var increasedRate = basePrice + (basePrice * customerPercentageIncrease / 100);
                $('#item_price_' + product_id).val(increasedRate.toFixed(2));

                // Update hidden field to indicate increase is applied
                $('#increase_flag_' + product_id).val('1');

                // Add visual feedback
                $('#item_price_' + product_id).addClass('border-warning');
                setTimeout(function() {
                    $('#item_price_' + product_id).removeClass('border-warning');
                }, 1500);

                console.log('Increase applied to product ' + product_id + ': ' + customerPercentageIncrease + '%');
            }
        } else {
            // Revert to base price
            $('#item_price_' + product_id).val(basePrice.toFixed(2));

            // Update hidden field to indicate increase is not applied
            $('#increase_flag_' + product_id).val('0');

            console.log('Increase removed from product ' + product_id);
        }

        // Recalculate total for this item
        var newRate = parseFloat($('#item_price_' + product_id).val());
        var newTotal = quantity * newRate;
        $('#total_price_' + product_id).val(newTotal.toFixed(2));

        // Trigger overall calculations
        CalculateTotalAmount();
        calculations();

        // Update select all checkbox state
        updateSelectAllState();
    }

    // Function to update the select all checkbox state based on individual checkboxes
    function updateSelectAllState() {
        var totalCheckboxes = $('.increase-checkbox').length;
        var checkedCheckboxes = $('.increase-checkbox:checked').length;

        var selectAllCheckbox = $('#selectAllIncrease');

        if (checkedCheckboxes === 0) {
            // None checked
            selectAllCheckbox.prop('checked', false);
            selectAllCheckbox.prop('indeterminate', false);
        } else if (checkedCheckboxes === totalCheckboxes) {
            // All checked
            selectAllCheckbox.prop('checked', true);
            selectAllCheckbox.prop('indeterminate', false);
        } else {
            // Some checked (indeterminate state)
            selectAllCheckbox.prop('checked', false);
            selectAllCheckbox.prop('indeterminate', true);
        }
    }

    function calculations () {
        var subTotal            = 0
        var grand_total         = 0
        var tempResult          = 0
        var balance             = 0
        var flat_discount       = parseFloat( $("#flat_discount").val() );
        var percentage_discount = parseFloat($("#perc_discount").val()); 
        var gst                 = parseFloat( $("#gst").val() );
        var wh_tax              = parseFloat( $("#wh_tax").val() );
        var paidAmount          = parseFloat( $("#paidAmount").val() );



        $(".total_price").each(function(){
          subTotal += + parseFloat($(this).val());
      });
        tempResult = subTotal;
    //Flat Discount Calculation
        if (!flat_discount) { 
          flat_discount = 0;
          $("#perc_discount").removeAttr("readonly");
      }else{
          $("#perc_discount").attr("readonly", true);
          tempResult = subTotal - flat_discount; 
      }

    //Percentage Discount Calculation
      if (!percentage_discount) { 
          percentage_discount = 0;
          $("#flat_discount").removeAttr("readonly");
      }else{
          $("#flat_discount").attr("readonly", true);
          let discount_amount = (percentage_discount / 100) * subTotal;
          tempResult = subTotal - discount_amount;
      }


    //GST Calculation
      if (!gst) { gst = 0; }
      let temp1 = (gst / 100) * tempResult;
      let grandTemp1 = tempResult + temp1;

    //WH Tax Calculation
      if (!wh_tax) { wh_tax = 0; }
      let temp2 = (wh_tax / 100) * grandTemp1;
      grand_total = grandTemp1 + temp2;

      console.log('tempResult',grandTemp1,grand_total)

      $("#total_ammount_after_dis").val(Math.round(grand_total));

      balance = grand_total - paidAmount
      $("#dueAmmount").val(Math.round(balance));


  }

  function  FlatDiscount() {
    var total_price_update=0;
    var flat_discount=parseFloat( $("#flat_discount").val() );
    var gst = parseFloat( $("#gst").val() );
    if (!flat_discount || flat_discount == 0) { 
      flat_discount = 0;
      // $("#flat_discount").val(flat_discount);
      $("#perc_discount").removeAttr("readonly");
  }else{
      $("#perc_discount").attr("readonly", true);
  }
  $(".total_price").each(function(){
      total_price_update += + parseFloat($(this).val());
  });
  var grand_total=total_price_update

  var result=grand_total-flat_discount; 
  $("#total_ammount_after_dis").val(result);
  $("#grandTotal").val(grand_total);
  invoice_paidamount();
}
function percentageDiscount()
{
    var total_price_update = 0;
    var percentage_discount = parseFloat($("#perc_discount").val()); 
    var gst = parseFloat( $("#gst").val() );
    if (!percentage_discount) { 
      percentage_discount = 0;
      // $("#perc_discount").val(percentage_discount) ;
      $("#flat_discount").removeAttr("readonly");
  }else{
      $("#flat_discount").attr("readonly", true);
  }
  $(".total_price").each(function () {
      total_price_update += +parseFloat($(this).val());
  });

  var grand_total = total_price_update;
  var discount_amount = (percentage_discount / 100) * grand_total;
  var result = grand_total - discount_amount;

  $("#total_ammount_after_dis").val(result);
  $("#grandTotal").val(grand_total);
  invoice_paidamount();
}
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



        $("#addinvoiceItem").append('<tr class="invoice-row'+pac_id+'">'+
            '<td class="text-center" style="width: 80px;">'+
                '<input type="checkbox" class="increase-checkbox" id="increase_'+pac_id+'" data-product-id="'+pac_id+'" onchange="toggleIncrease('+pac_id+')" />'+
                '<input type="hidden" id="base_price_'+pac_id+'" value="'+pac_price+'" />'+
                '<input type="hidden" name="increase_flag[]" id="increase_flag_'+pac_id+'" value="0" />'+
            '</td>'+
            '<td style="width: 500px">'+
                '<input name="package_name[]" class="form-control form-control-sm productSelection" required="" value="'+pro_full_name+'" autocomplete="off" tabindex="1" type="text">'+
                '<input type="hidden" class="autocomplete_hidden_value" value="'+pac_id+'" name="package_id[]" />'+
            '</td>'+
            '<td style="width: 150px;">'+
                '<input name="empty_quantity[]" autocomplete="off" class="total_empty_qty_1 form-control form-control-sm mt-2" id="total_empty_qty_'+pac_id+'" value="0" required="" placeholder="0.00" tabindex="3" type="number">'+
            '</td>'+
            '<td style="width: 150px;">'+
                '<input name="package_quantity[]" autocomplete="off" class="total_qty_1 form-control form-control-sm" id="total_qty_'+pac_id+'" onkeyup="quantity_calculate('+pac_id+');" value="1" required="" placeholder="0.00" tabindex="3" type="text">'+
                '<input type="hidden" id="stock_qty'+pac_id+'" value="'+stockQty+'"/>'+
            '</td>'+
            '<td style="width: 150px;">'+
                '<input name="package_rate[]" value="'+pac_price+'" id="item_price_'+pac_id+'" class="price_item form-control form-control-sm" tabindex="7" readonly="" type="text">'+
            '</td>'+
            '<td style="width: 242px">'+
                '<input class="total_price form-control form-control-sm" name="total_price[]" id="total_price_'+pac_id+'" value="'+pac_price+'" tabindex="-1" readonly="" type="text">'+
            '</td>'+
            '<td>'+
                '<button class="btn btn-danger btn-rounded btn-icon btn-del" type="button" onclick="deleteRow('+pac_id+')" value="Delete" tabindex="5"><i class="fa fa-trash"></i></button>'+
            '</td>'+
        '</tr>');

        
        $(".select2-search__field").val('');
        
        CalculateTotalAmount();

        // Update select all checkbox state when new row is added
        updateSelectAllState();

        $(".js-example-basic-single").select2("open");


    }
} else{
  var text='Please Select Valid Item!';
  showToast('error',text,'Notification');
}

});






function CalculateTotalAmount(){

    var total_price_update=0;
    $(".total_price").each(function(){
      total_price_update += + parseFloat($(this).val());
  });
    var flat_discount=parseFloat( $("#flat_discount").val() );
    var result_dis=total_price_update-flat_discount;
    $("#total_ammount_after_dis").val(result_dis);
    $("#grandTotal").val(total_price_update);
    invoice_paidamount();
    // Trigger discount calculations when total amount changes
    calculations();

}


function invoice_paidamount(){

    var grand_amount=0;
    var new_amount=0;
    var result_dis=0;
    var grandTotal = parseFloat( $("#total_ammount_after_dis").val() );
    var rece_amount=parseFloat( $("#paidAmount").val() );
    new_amount = grandTotal - rece_amount
    
    $("#dueAmmount").val(new_amount);

}


function quantity_calculate(pro_id){

    var pro_qty  = parseFloat($("#total_qty_"+pro_id+"").val());
    var StockQty = parseFloat($("#stock_qty"+pro_id+"").val()) ;
    
    if(!pro_qty)
    {
      pro_qty = 0
  }
  if(pro_qty <= StockQty ){
    var pro_price=$("#item_price_"+pro_id+"").val();
    var new_total=parseFloat(pro_qty) * parseFloat(pro_price);
    $("#total_price_"+pro_id+"").val(new_total);
    CalculateTotalAmount();
    // Trigger discount calculations when cylinders are modified
    calculations();
} else{
    var pro_price=$("#item_price_"+pro_id+"").val();
    alert('Stock Quantity of This Product is '+StockQty+'');
    $("#total_qty_"+pro_id+"").val(StockQty);
    var new_total=parseFloat(StockQty) * parseFloat(pro_price);
    $("#total_price_"+pro_id+"").val(new_total);
    CalculateTotalAmount();
    // Trigger discount calculations when cylinders are modified
    calculations();
}
var flatDiscount = $("#pro_dis_flat"+pro_id+"").val();
if(flatDiscount > 0)
{
    proflatDiscount(pro_id)
}
var percentageDiscount = $("#pro_dis_perc"+pro_id+"").val();
if(percentageDiscount > 0)
{
    proPercDiscount(pro_id)
}
calculations()
console.log('-->',pro_qty,StockQty)
}



function deleteRow(rem_id) {

    $(".js-example-basic-single").on("select2-closed", function(e) {
      $(".js-example-basic-single").select2("open");
  });
    $(".invoice-row"+rem_id+"").remove();
      // update grand total
    CalculateTotalAmount();
      // update select all checkbox state after row removal
    updateSelectAllState();
      // set discount price given
    var total_dis_update=0;
    $(".total_discount").each(function(){
      total_dis_update += + parseFloat($(this).val());
  });
    $("#total_discount_ammount").val(total_dis_update);

}
</script>
</body>
</html>
