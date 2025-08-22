<?php
// views/inactive_customer.php
require '../config/db.php';
require_once '../config/db_functions.php';
require '../config/auth.php';
// Query inactive customers
$inactiveCustomers = fetchInactiveCustomers();
$inactiveCustomersCount = count($inactiveCustomers);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Inactive Customers</title>
    <?php include '../libs/links.php'; ?>
    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.24/css/jquery.dataTables.css">
</head>
<body>
    <?php include '../libs/sidebar.php'; ?>
    <div class="container-fluid mt-4">
        <header class="mb-2"><h2 class="text-white mb-0">Inactive Customers</h2></header>
        <?php //if ($inactiveCustomersCount > 0) { ?>
            <div class="table-container">
                <div class="mt-2 mb-2">
                    <div class="row">
                      <div class="col-md-10"></div>
                      <div class="col-md-2">
                        <button class="btn btn-primary mt-2 w-100" id="downloadExcel"><i class="fa fa-download"></i> Download Excel</button>
                      </div>
                    </div>
                </div>
                <table id="inactiveCustomersTable" class="table table-bordered table-hover">
                    <thead class="thead-light">
                        <tr>
                            <th>ID</th>
                            <th>Customer Name</th>
                            <th>Phone</th>
                            <th>CNIC</th>
                            <th>Last Invoice Date</th>
                            <th>Days Ago</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inactiveCustomers as $inactiveCustomer) { ?>
                            <tr>
                                <td><?php echo htmlspecialchars($inactiveCustomer['customer_id']); ?></td>
                                <td><?php echo htmlspecialchars($inactiveCustomer['customer_name']); ?></td>
                                <td><?php echo htmlspecialchars($inactiveCustomer['customer_phone']); ?></td>
                                <td><?php echo htmlspecialchars($inactiveCustomer['customer_cnic']); ?></td>
                                <td><?php echo htmlspecialchars($inactiveCustomer['last_invoice_date']); ?></td>
                                <td><?php echo htmlspecialchars($inactiveCustomer['days_since_last_invoice']); ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        <?php //} else { ?>
            <!-- <div class="alert alert-info" role="alert">
                No inactive customers found.
            </div> -->
        <?php //} ?>
    </div>
    <?php include('../libs/jslinks.php'); ?>
    <!-- DataTables JS -->
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.js"></script>
    <!-- XLSX Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.16.9/xlsx.full.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#inactiveCustomersTable').DataTable({
                "pageLength": 25,
                "order": [[3, "desc"]] // Sort by 'Days Ago' column in descending order
            });

            // Function to download the table data as an Excel file
            $('#downloadExcel').click(function() {
                var table = document.getElementById("inactiveCustomersTable");
                var wb = XLSX.utils.table_to_book(table, { sheet: "Inactive Customers" });
                XLSX.writeFile(wb, "Inactive_Customers.xlsx");
            });
        });
    </script>
</body>
</html>
<?php
// $conn->close();
?>
