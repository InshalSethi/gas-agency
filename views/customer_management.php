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
    $db->update('customers',$uparr);
?>
<script>  window.location.href="customer_management.php"; </script>
    <?php
  }    } 
  ?>
<!DOCTYPE html>
<html>
<head>
    <title>Customers</title>

    <?php include '../libs/links.php'; ?>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
</head>
<body>
  <?php include '../libs/sidebar.php'; ?>

  <div class="container-fluid mt-4">
    <header><h4 class="text-white">Customers</h4></header>
    <div class="table-container">
        <div class="mt-2 mb-2">
                    <div class="row">

                      <div class="col-md-2">
                        <select id="balanceFilter" class="form-control filter mt-2">
                          <option value="">Select By Balance</option>
                          <option value="receivable">Receivable</option>
                        </select>
                      </div>
                      <div class="col-md-2">
                        <select id="emptyFilter" class="form-control filter mt-2">
                          <option value="">Select By Pending Cylinder</option>
                          <option value="receivable">Receivable</option>
                        </select>
                      </div>
                      <div class="col-md-2">
                        <select id="bonusFilter" class="form-control filter mt-2">
                          <option value="">Select By Bonus</option>
                          <option value="given">Given</option>
                        </select>
                      </div>
                      <div class="col-md-2"></div>
                      <div class="col-md-2">
                        <button class="btn btn-primary mt-2 w-100" id="resetFilterBtn"><i class="fa fa-refresh"></i> Reset Filter</button>
                      </div>
                      <div class="col-md-2">
                        <a class="btn btn-success mt-2 w-100"  href="add-customer.php"><i class="fa fa-plus"></i> Add Customer</a>
                      </div>
                    </div>
                  </div>
      <table id="customerTable" class="table table-striped table-hover">
        <thead class="thead-dark">
            <tr>
                <th>Customer ID</th>
                <th>Name</th>
                <th>Phone</th>
                <th>Balance</th>
                <th>Quantity</th>
                <th>Empty</th>
                <th>Bonus</th>
                <th>Pending</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>


<br>
</div>

<!-- Add Customer Modal -->
<div class="modal fade" id="addCustomerModal" tabindex="-1" aria-labelledby="addCustomerModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="addCustomerForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCustomerModalLabel">Add Customer</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <h5>Cutomer Info</h5>
                    <div class="form-group">
                        <label for="add_name">Name</label>
                        <input type="text" id="add_name" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="add_phone">Phone</label>
                        <input type="text" id="add_phone" name="phone" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="add_cnic">CNIC</label>
                        <input type="text" id="add_cnic" name="cnic" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="gasType">Gas Type</label>
                        <select class="form-control" id="gasType" name="gas_types[]" multiple="multiple" required>
                            <option value="">Select Cylinder Type</option>
                            <?php 
                            $cylinder_query = "SELECT id, name FROM cylinders";
                            $cylinder_result = $conn->query($cylinder_query);
                            $gas_types = [];
                            while ($row = $cylinder_result->fetch_assoc()) {
                                $gas_types[] = $row;
                            }
                            foreach ($gas_types as $gas_type) { ?>
                                <option value="<?php echo $gas_type['id']; ?>"><?php echo $gas_type['name']; ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="add_bonus_cylinders">Bonus Cylinders</label>
                        <input type="number" id="add_bonus_cylinders" name="bonus_cylinders" class="form-control">
                    </div>
                    <h5>Cutomer Security</h5>
                    <div class="form-group" style="display:none;">
                        <label for="add_security_type">Security Type</label>
                        <select id="add_security_type" name="security_type" class="form-control" required>
                            <option value="Person">Person</option>
                            <option value="Cash">Cash</option>
                            <option value="Cheque">Cheque</option>
                        </select>
                    </div>
                    <div class="form-group" id="add_person_security" >
                        <label for="add_person_name">Person Name</label>
                        <input type="text" id="add_person_name" name="person_name" class="form-control">
                        <label for="add_person_cnic">Person CNIC</label>
                        <input type="text" id="add_person_cnic" name="person_cnic" class="form-control">
                        <label for="add_person_phone">Person Phone</label>
                        <input type="text" id="add_person_phone" name="person_phone" class="form-control">
                    </div>
                    <div class="form-group" id="add_cash_security">
                        <label for="add_cash_amount">Cash Amount</label>
                        <input type="number" id="add_cash_amount" name="cash_amount" class="form-control">
                    </div>
                    <div class="form-group" id="add_cheque_security">
                        <label for="add_cheque_details">Cheque Details</label>
                        <input type="text" id="add_cheque_details" name="cheque_details" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Add Customer</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Customer Modal -->
<div class="modal fade" id="editCustomerModal" tabindex="-1" aria-labelledby="editCustomerModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editCustomerForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="editCustomerModalLabel">Edit Customer</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="edit_customer_id" name="customer_id">
                    <div class="form-group">
                        <label for="edit_name">Name</label>
                        <input type="text" id="edit_name" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_phone">Phone</label>
                        <input type="text" id="edit_phone" name="phone" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_cnic">CNIC</label>
                        <input type="text" id="edit_cnic" name="cnic" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_bonus_cylinders">Bonus Cylinders</label>
                        <input type="number" id="edit_bonus_cylinders" name="bonus_cylinders" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="edit_security_type">Security Type</label>
                        <select id="edit_security_type" name="security_type" class="form-control" required>
                            <option value="Person">Person</option>
                            <option value="Cash">Cash</option>
                            <option value="Cheque">Cheque</option>
                        </select>
                    </div>
                    <div class="form-group" id="edit_person_security" style="display: none;">
                        <label for="edit_person_name">Person Name</label>
                        <input type="text" id="edit_person_name" name="person_name" class="form-control">
                        <label for="edit_person_cnic">Person CNIC</label>
                        <input type="text" id="edit_person_cnic" name="person_cnic" class="form-control">
                        <label for="edit_person_phone">Person Phone</label>
                        <input type="text" id="edit_person_phone" name="person_phone" class="form-control">
                    </div>
                    <div class="form-group" id="edit_cash_security" style="display: none;">
                        <label for="edit_cash_amount">Cash Amount</label>
                        <input type="number" id="edit_cash_amount" name="cash_amount" class="form-control">
                    </div>
                    <div class="form-group" id="edit_cheque_security" style="display: none;">
                        <label for="edit_cheque_details">Cheque Details</label>
                        <input type="text" id="edit_cheque_details" name="cheque_details" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Update Customer</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php include('../libs/jslinks.php'); ?>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    function deleteRow(clicked_id) {
          var txt;
          var r = confirm(" Are you sure to delete this?");
          if (r == true) { 
            txt = "You pressed OK!";
            
            var stateID = clicked_id;
            
            window.location = "customer_management.php?id="+clicked_id; 
            
        } else {
            
            
        }
        
    }
    $(document).ready(function () {
        $('#gasType').select2({
            placeholder: "Select Cylinder Type"
        });
        // Initialize DataTables
        var customerTable = $('#customerTable').DataTable({
            processing: true,
            serverSide: true,
            "paging":   true,
            "iDisplayLength": 100,
            "ajax": {
                "url": "fetch_customers.php",
                "type": "GET",
                "data": function(d) {
                    d.search_query = $('#search_query').val();
                    d.balance_filter = $('#balanceFilter').val()
                    d.empty_filter = $('#emptyFilter').val()
                    d.bonus_filter = $('#bonusFilter').val()
                }
            },
            "columns": [
                { "data": "customer_id" },
                { "data": "name" },
                { "data": "phone" },
                { "data": "balance" },
                { "data": "purchased" },
                { "data": "empty" },
                { "data": "bonus_cylinders" },
                { "data": "pending_cylinders" },
                { "data": "actions" }
            ],
            rowCallback: function (row, data) {
              if (data.row_class) {
                $(row).addClass(data.row_class);
              }
            },
        });

        $('input[type="search"],filter').keyup(function (e) {
            e.preventDefault();
            customerTable.draw();
          });

          $('.filter').change(function (e) {
            e.preventDefault();
            customerTable.draw();
          });
          $('#resetFilterBtn').click(function() {
              // Reset date range picker to default values
            $('#search_query').val(null)
            $('#balanceFilter').val(null)
            $('#emptyFilter').val(null)
            $('#bonusFilter').val(null)
            
              // Reload the DataTable
            customerTable.draw();
          });

        // Add customer form submission
        $('#addCustomerForm').submit(function (e) {
            e.preventDefault();
            if (!validateSecurityInputs('add')) return;
            $.ajax({
                url: 'add_customer.php',
                type: 'POST',
                data: $(this).serialize(),
                success: function (response) {
                    $('#addCustomerModal').modal('hide');
                    customerTable.ajax.reload();
                    // Clear form inputs
                    $('#addCustomerForm')[0].reset();
                    // Manually reset select fields and hide/show logic
                    // $('#add_security_type').val('Person').change(); // Change to default value
                    // $('#add_person_security, #add_cash_security, #add_cheque_security').hide(); // Hide all security options
                    // $('#add_person_name, #add_person_cnic, #add_person_phone, #add_cash_amount, #add_cheque_details').val(''); // Clear input values
                }
            });
        });

        // Edit customer form submission
        $('#editCustomerForm').submit(function (e) {
            e.preventDefault();
            if (!validateSecurityInputs('edit')) return;
            $.ajax({
                url: 'edit_customer.php',
                type: 'POST',
                data: $(this).serialize(),
                success: function (response) {
                    $('#editCustomerModal').modal('hide');
                    customerTable.ajax.reload();
                }
            });
        });

        // Search form submission
        $('input[type="search"]').submit(function (e) {
            e.preventDefault();
            customerTable.draw();
        });

        // Show appropriate security fields based on selection
        // $('#add_security_type, #edit_security_type').change(function () {
        //     let idPrefix = $(this).attr('id').split('_')[0];
        //     $('#' + idPrefix + '_person_security').hide();
        //     $('#' + idPrefix + '_cash_security').hide();
        //     $('#' + idPrefix + '_cheque_security').hide();

        //     if ($(this).val() === 'Person') {
        //         $('#' + idPrefix + '_person_security').show();
        //     } else if ($(this).val() === 'Cash') {
        //         $('#' + idPrefix + '_cash_security').show();
        //     } else if ($(this).val() === 'Cheque') {
        //         $('#' + idPrefix + '_cheque_security').show();
        //     }
        // });

        // Initialize modals to show appropriate security fields
        $('#addCustomerModal, #editCustomerModal').on('show.bs.modal', function (e) {
            let idPrefix = $(this).find('form').attr('id').split('Customer')[0].toLowerCase();
            $('#' + idPrefix + '_security_type').change();
        });

        // Edit customer button click
        $(document).on('click', '.edit-btn', function () {
            let customerData = $(this).data('customer');
            $('#edit_customer_id').val(customerData.customer_id);
            $('#edit_name').val(customerData.name);
            $('#edit_phone').val(customerData.phone);
            $('#edit_cnic').val(customerData.cnic);
            $('#edit_bonus_cylinders').val(customerData.bonus_cylinders);
            $('#edit_security_type').val(customerData.security_type).change();
            $('#edit_person_name').val(customerData.person_name);
            $('#edit_person_cnic').val(customerData.person_cnic);
            $('#edit_person_phone').val(customerData.person_phone);
            $('#edit_cash_amount').val(customerData.cash_amount);
            $('#edit_cheque_details').val(customerData.cheque_details);

            $('#editCustomerModal').modal('show');
        });

        // Delete customer button click
        $(document).on('click', '.delete-btn', function () {
            if (confirm('Are you sure you want to delete this customer?')) {
                let customerId = $(this).data('customer-id');
                $.ajax({
                    url: 'delete_customer.php',
                    type: 'POST',
                    data: { customer_id: customerId },
                    success: function (response) {
                        customerTable.ajax.reload();
                    }
                });
            }
        });

        // Validate security inputs
        function validateSecurityInputs(prefix) {
            let securityType = $('#' + prefix + '_security_type').val();
            if (securityType === 'Person') {
                if (!$('#' + prefix + '_person_name').val() || !$('#' + prefix + '_person_cnic').val() || !$('#' + prefix + '_person_phone').val()) {
                    alert('Please fill out all person security fields.');
                    return false;
                }
            } else if (securityType === 'Cash') {
                if (!$('#' + prefix + '_cash_amount').val()) {
                    alert('Please fill out the cash amount.');
                    return false;
                }
            } else if (securityType === 'Cheque') {
                if (!$('#' + prefix + '_cheque_details').val()) {
                    alert('Please fill out the cheque details.');
                    return false;
                }
            }
            return true;
        }
    });
</script>
</body>
</html>
