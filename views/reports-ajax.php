<?php
require '../config/db.php';

// Get search query if available
$draw = $_REQUEST['draw'];
$row = $_REQUEST['start'];
$rowperpage = $_REQUEST['length']; // Rows display per page
$columnIndex = $_REQUEST['order'][0]['column']; // Column index
$columnName = $_REQUEST['columns'][$columnIndex]['data']; // Column name
$columnSortOrder = $_REQUEST['order'][0]['dir']; // asc or desc
$searchQuery = $_REQUEST['search']['value'];

// Handle date range
$start_date = date("Y-m-d", strtotime($_REQUEST['start_date']));
$end_date = date("Y-m-d", strtotime($_REQUEST['end_date']));

$totalRecords = 0;

// Define columns for the query
$cols = array(
    "inv.id as invoice_id",
    "inv.customer_id",
    "inv.date",
    "cus.name as customer_name",
    "cy.name as cylinder_name",
    "ii.qty"
);

// Build the query with joins
$db->join("customers cus", "inv.customer_id=cus.id", "LEFT");
$db->join("invoice_items ii", "inv.id=ii.invoice_id", "INNER");
$db->join("cylinders cy", "ii.product_id=cy.id", "INNER");

// Apply filters
$db->where("cus.deleted_at", NULL, 'IS');
$db->where("inv.deleted_at", NULL, 'IS');
$db->where("cy.deleted_at", NULL, 'IS');

// Date range filter
if (!empty($_REQUEST['start_date']) && !empty($_REQUEST['end_date'])) {
    $db->where("inv.date", Array($start_date, $end_date), 'BETWEEN');
}

// Search filter
if (!empty($searchQuery)) {
    $db->where("(cus.name LIKE '%{$searchQuery}%' OR 
                cy.name LIKE '%{$searchQuery}%' OR 
                inv.id LIKE '%{$searchQuery}%' OR 
                inv.date LIKE '%{$searchQuery}%' OR 
                ii.qty LIKE '%{$searchQuery}%')");
}

// Apply ordering
$orderColumn = 'inv.id';
switch($columnName) {
    case 'invoice_id':
        $orderColumn = 'inv.id';
        break;
    case 'customer':
        $orderColumn = 'cus.name';
        break;
    case 'cylinder':
        $orderColumn = 'cy.name';
        break;
    case 'qty':
        $orderColumn = 'ii.qty';
        break;
    case 'date':
        $orderColumn = 'inv.date';
        break;
}

$db->orderBy($orderColumn, $columnSortOrder);

// Get the data
$reports = $db->get('invoices inv', Array($row, $rowperpage), $cols);
$totalRecords = $db->count;

// Get cylinder summary for chips using raw query
$summaryQuery = "SELECT cy.name as cylinder_name, SUM(ii.qty) as total_qty
                FROM invoices inv
                LEFT JOIN customers cus ON inv.customer_id = cus.id
                INNER JOIN invoice_items ii ON inv.id = ii.invoice_id
                INNER JOIN cylinders cy ON ii.product_id = cy.id
                WHERE cus.deleted_at IS NULL
                AND inv.deleted_at IS NULL
                AND cy.deleted_at IS NULL";

// Add date filter if provided
if (!empty($_REQUEST['start_date']) && !empty($_REQUEST['end_date'])) {
    $summaryQuery .= " AND inv.date BETWEEN '$start_date' AND '$end_date'";
}

// Add search filter if provided
if (!empty($searchQuery)) {
    $summaryQuery .= " AND (cus.name LIKE '%{$searchQuery}%' OR
                      cy.name LIKE '%{$searchQuery}%' OR
                      inv.id LIKE '%{$searchQuery}%' OR
                      inv.date LIKE '%{$searchQuery}%' OR
                      ii.qty LIKE '%{$searchQuery}%')";
}

$summaryQuery .= " GROUP BY cy.name ORDER BY cy.name";

$summaryData = $db->rawQuery($summaryQuery);

// Process cylinder summary
$cylinderSummary = [];
foreach ($summaryData as $summary) {
    $cylinderSummary[$summary['cylinder_name']] = $summary['total_qty'];
}

// Initialize an array to hold the output data
$data = [];

// Process each row
foreach ($reports as $report) {
    $data[] = [
        'invoice_id' => $report['invoice_id'],
        'customer' => '<a href="customer-ledger.php?id=' . $report['customer_id'] . '" class="text-primary"><b>' . $report['customer_name'] . '</b></a>',
        'cylinder' => $report['cylinder_name'],
        'qty' => $report['qty'],
        'date' => date("d-m-Y", strtotime($report['date']))
    ];
}

// Output the data in JSON format
$response = array(
    "draw" => intval($draw),
    "iTotalRecords" => $totalRecords,
    "iTotalDisplayRecords" => $totalRecords,
    "data" => $data,
    "cylinderSummary" => $cylinderSummary
);

header('Content-Type: application/json');
echo json_encode($response);
?>
