<?php
// views/dashboard.php
require '../config/db.php';
require_once '../config/db_functions.php';
require '../config/auth.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Create Purcahse Invoices</title>
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
            $vendor_id=$_POST['vendor_id'];
            $invoice_date=$_POST['date'];

            $sub_total=$_POST['grand_total_price'];
            $flat_discount=$_POST['flat_discount'];
            $perc_discount=$_POST['perc_discount'];
            $grand_total=$_POST['total_ammount_after_dis'];
            $received=$_POST['paid_amount'];
            $balance=$_POST['due_amount'];
            $time_zone = date_default_timezone_set("Asia/Karachi");
            $date = date("Y-m-d h:i:s");




            $invoice_array=array( 
                "vendor_id"=>$vendor_id,
                "date"=>$invoice_date,
                "flat_discount"=>$flat_discount,
                "percentage_discount"=>$perc_discount,
                "sub_total"=>$sub_total,
                "grand_total"=>$grand_total,
                "paid"=>$received,
                "balance"=>$balance,
                "created_at"=>$date

            );

            $invoice_id=$db->insert('purchase_invoices',$invoice_array); 

            $pro_id=$_POST['package_id'];
            $pro_name=$_POST['package_name'];
            $pro_empty_quantity=$_POST['empty_quantity'];
            $pro_quantity=$_POST['package_quantity'];
            $pro_rate=$_POST['package_rate'];
            $total_price=$_POST['total_price'];


            $total_pkg=count($pro_id);
            for ($i=0; $i < $total_pkg; $i++) { 

                if( $pro_id[$i] != '' ){
                  $invoice_pkg_arr=array( 
                    "purchase_invoice_id"=>$invoice_id,
                    "product_id"=>$pro_id[$i],
                    "product_name"=>$pro_name[$i],
                    "empty_qty"=>$pro_empty_quantity[$i],
                    "qty"=>$pro_quantity[$i],
                    "rate"=>$pro_rate[$i],
                    "total"=>$total_price[$i],
                    "created_at"=>$date
                );
                  $db->insert('purchase_invoice_items',$invoice_pkg_arr);
                  AddStockQty($pro_id[$i],$pro_quantity[$i],$pro_rate[$i],$db);
                  removeEmptyStockQty($pro_id[$i],$pro_empty_quantity[$i],$db);
              }

          }

          if($received > 0)
          {
            $arr=array( 
                "purchase_invoice_id"=>$invoice_id,
                "entity"=>'purchase',
                "vendor_id"=>$vendor_id,
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
                      <div class="col-md-6 inv-col">
                        <div class="form-group row">
                          <label class="col-sm-12 col-form-label">Vendor Name</label>
                          <div class="col-sm-12">
                            <?php
                            $db->where("deleted_at", NULL, 'IS');
                            $vendors=$db->get("vendors");

                            ?>
                            <select  id="ven_name"  name="vendor_id" class="form-control form-control-sm vendor_name" required >
                              <option value="" >Select Vendors</option>
                              <?php 
                              foreach($vendors as $vendor ){ ?>
                                <option value="<?php echo $vendor['id']; ?>" ><?php echo $vendor['name']; ?></option>
                                <?php

                            }

                            ?>
                        </select>
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
           package-price="<?php echo $pac['supplier_rate']; ?>" 
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
    $(document).ready(function() { $(".js-example-basic-single,.vendor_name").select2(); $('.js-example-basic-single').select2('open'); });

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



        $("#addinvoiceItem").append('<tr class="invoice-row'+pac_id+'"><td style="width: 500px"><input name="package_name[]"  class="form-control form-control-sm productSelection"  required="" value="'+pro_full_name+'" autocomplete="off" tabindex="1" type="text"><input type="hidden" class="autocomplete_hidden_value" value="'+pac_id+'" name="package_id[]" ></td><td style="width: 150px;"><input name="empty_quantity[]" autocomplete="off" class="total_empty_qty_1 form-control form-control-sm mt-2" id="total_empty_qty_'+pac_id+'"  value="0" required="" placeholder="0.00" tabindex="3" type="number"></td><td style="width: 150px;"><input name="package_quantity[]" autocomplete="off" class="total_qty_1 form-control form-control-sm" id="total_qty_'+pac_id+'" onkeyup="quantity_calculate('+pac_id+');" value="1" required="" placeholder="0.00" tabindex="3" type="text"><input type="hidden" id="stock_qty'+pac_id+'" value="'+stockQty+'"/> </td><td style="width: 150px;"><input name="package_rate[]" value="'+pac_price+'" id="item_price_'+pac_id+'" class=" price_item form-control form-control-sm" tabindex="7" onkeyup="quantity_calculate('+pac_id+');" type="text"></td><td style="width: 242px"><input class="total_price form-control form-control-sm" name="total_price[]" id="total_price_'+pac_id+'" value="'+pac_price+'" tabindex="-1" readonly="" type="text"></td><td><button  class="btn btn-danger btn-rounded btn-icon btn-del" type="button" onclick="deleteRow('+pac_id+')" value="Delete" tabindex="5"><i class="fa fa-trash"></i></button></td></tr>');

        
        $(".select2-search__field").val('');
        
        CalculateTotalAmount();
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
  // if(pro_qty <= StockQty ){
    var pro_price=$("#item_price_"+pro_id+"").val();
    var new_total=parseFloat(pro_qty) * parseFloat(pro_price);
    $("#total_price_"+pro_id+"").val(new_total);
    CalculateTotalAmount();
// } else{
//     var pro_price=$("#item_price_"+pro_id+"").val();
//     alert('Stock Quantity of This Product is '+StockQty+'');
//     $("#total_qty_"+pro_id+"").val(StockQty);
//     var new_total=parseFloat(StockQty) * parseFloat(pro_price);
//     $("#total_price_"+pro_id+"").val(new_total);
//     CalculateTotalAmount();
// }
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
