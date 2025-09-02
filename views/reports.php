<?php
require '../config/db.php';
require_once '../config/db_functions.php';
require '../config/auth.php';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reports - Product Wise Sales</title>
    <?php include '../libs/links.php'; ?>
    <link href="<?php echo baseurl('css/select2.min.css'); ?>" rel="stylesheet" />
    <link rel="stylesheet" type="text/css" href="<?php echo baseurl('css/daterangepicker.css'); ?>" />
    <style>
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            background-color: #f8f9fa;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }

        .card-header {
            background-color: #17a2b8;
            color: white;
            border-bottom: none;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
            font-size: 16px;
        }

        .table-container {
            overflow-x: auto;
        }

        .filter-section {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php include '../libs/sidebar.php'; ?>

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-md-12">
                <h2 class="text-left">Product Wise Sales Report</h2>
                <p>Detailed report showing which products were sold to which customers.</p>

                <div class="card mt-2">
                    <div class="card-header">
                        <i class="fas fa-table"></i> Product Sales Data
                    </div>
                    <div class="card-body">
                        <div class="filter-section">
                            <div class="row">
                                <div class="col-md-5">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <input type="text" id="dateRangePicker" class="form-control filter mt-0" placeholder="Select Date Range" />
                                        </div>
                                        <div class="col-md-4">
                                            <button class="btn btn-info" id="resetFilterBtn" title="Reset Filter">
                                                <i class="fa fa-refresh"></i>
                                            </button>
                                            <button class="btn btn-primary ml-1" id="downloadExcelBtn" title="Download Excel">
                                                <i class="fa fa-download"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-7">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="card" id="cylinderSummary" style="padding: 10px;">
                                                <strong>Total Cylinders Sold:</strong>
                                                <div id="cylinderChips" class="mt-2">
                                                    <!-- Chips will be populated here -->
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="table-container">
                            <table id="reportsTable" class="table table-striped table-hover">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>Invoice ID</th>
                                        <th>Customer</th>
                                        <th>Cylinder</th>
                                        <th>Qty</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include('../libs/jslinks.php'); ?>
    <script src="<?php echo baseurl('js/select2.min.js'); ?>"></script>
    <script type="text/javascript" src="<?php echo baseurl('js/moment.min.js'); ?>"></script>
    <script type="text/javascript" src="<?php echo baseurl('js/daterangepicker.min.js'); ?>"></script>
    <script src="<?php echo baseurl('js/xlsx.full.min.js'); ?>"></script>

    <script>
    $(document).ready(function() {
        var defaultStartDate = moment();
        var defaultEndDate = moment();

        // Initialize date range picker
        $('#dateRangePicker').daterangepicker({
            opens: 'left',
            startDate: defaultStartDate,
            endDate: defaultEndDate
        }, function(start, end, label) {
            $('#reportsTable').DataTable().ajax.reload();
        });

        // Initialize DataTables
        var reportsTable = $('#reportsTable').DataTable({
            processing: true,
            serverSide: true,
            "paging": true,
            "iDisplayLength": 100,
            "ajax": {
                "url": "reports-ajax.php",
                "type": "GET",
                data: function(d) {
                    var dateRange = $('#dateRangePicker').val().split(' - ');
                    d.start_date = dateRange[0];
                    d.end_date = dateRange[1];
                },
                "dataSrc": function(json) {
                    // Update cylinder chips
                    updateCylinderChips(json.cylinderSummary);
                    return json.data;
                }
            },
            "columns": [
                { "data": "invoice_id" },
                { "data": "customer" },
                { "data": "cylinder" },
                { "data": "qty" },
                { "data": "date" }
            ],
            "order": [[ 0, "desc" ]]
        });

        // Function to update cylinder chips
        function updateCylinderChips(cylinderSummary) {
            var chipsHtml = '';
            var totalCylinders = 0;

            if (cylinderSummary && Object.keys(cylinderSummary).length > 0) {
                for (var cylinderName in cylinderSummary) {
                    var qty = cylinderSummary[cylinderName];
                    totalCylinders += parseInt(qty);
                    chipsHtml += '<span class="badge badge-secondary mr-2 mb-1">' + cylinderName + ' (' + qty + ')</span>';
                }
            } else {
                chipsHtml = '<span class="badge badge-light">No data found</span>';
            }

            // Update the total cylinders sold text
            $('#cylinderSummary strong').text('Total Cylinders Sold: ' + totalCylinders);
            $('#cylinderChips').html(chipsHtml);
        }

        // Search form submission
        $('input[type="search"], .filter').keyup(function(e) {
            e.preventDefault();
            reportsTable.draw();
        });

        $('.filter').change(function(e) {
            e.preventDefault();
            reportsTable.draw();
        });

        $('#resetFilterBtn').click(function() {
            // Reset date range picker to default values
            $('#dateRangePicker').data('daterangepicker').setStartDate(defaultStartDate);
            $('#dateRangePicker').data('daterangepicker').setEndDate(defaultEndDate);
            
            // Reload the DataTable
            reportsTable.draw();
        });

        // Function to download Excel file
        function downloadExcel() {
            var tableData = [];
            // Get table headers
            var headers = [];
            $('#reportsTable thead th').each(function(index, th) {
                headers.push($(th).text().trim());
            });
            tableData.push(headers);

            // Get table rows
            $('#reportsTable tbody tr').each(function(index, tr) {
                var rowData = [];
                $(tr).find('td').each(function(index, td) {
                    rowData.push($(td).text().trim());
                });
                tableData.push(rowData);
            });

            // Convert to worksheet
            var worksheet = XLSX.utils.aoa_to_sheet(tableData);
            var workbook = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(workbook, worksheet, "Product Sales Report");

            // Generate Excel file and trigger download
            XLSX.writeFile(workbook, 'product_sales_report.xlsx');
        }

        // Attach event listener to the button
        $('#downloadExcelBtn').click(function() {
            downloadExcel();
        });
    });
    </script>
</body>
</html>
