<?php
require '../config/db.php';
require '../config/db_functions.php';

$draw = $_REQUEST['draw'];
$row = $_REQUEST['start'];
$rowperpage = $_REQUEST['length'];
$columnIndex = $_REQUEST['order'][0]['column'];
$columnName = $_REQUEST['columns'][$columnIndex]['data'];
$columnSortOrder = $_REQUEST['order'][0]['dir'];
$searchQuery = $_REQUEST['search']['value'];
$balance_filter = $_REQUEST['balance_filter'];
$empty_filter = $_REQUEST['empty_filter'];
$bonus_filter = $_REQUEST['bonus_filter'];

// Step 1: Get basic customer data matching search query
$db->where("deleted_at", NULL, 'IS');
if (!empty($searchQuery)) {
    $db->where("(name LIKE '%" . $searchQuery . "%' OR phone LIKE '%" . $searchQuery . "%' OR cnic LIKE '%" . $searchQuery . "%' OR created_at LIKE '%" . $searchQuery . "%')");
}
$allCustomers = $db->get('customers', null, ['id', 'customer_id', 'name', 'phone', 'cnic', 'created_at']);

if (empty($allCustomers)) {
    $response = [
        "draw" => intval($draw),
        "iTotalRecords" => 0,
        "iTotalDisplayRecords" => 0,
        "data" => []
    ];
    echo json_encode($response);
    exit;
}

// Step 2: Batch fetch ALL necessary data in 3-4 queries instead of N+1
// 1. Balance Data
$balanceQuery = "SELECT 
    c.id, 
    COALESCE(i.total_receivable, 0) as total_receivable,
    CASE WHEN i.total_receivable IS NOT NULL THEN COALESCE(t.total_received, 0) ELSE 0 END as total_received
FROM customers c
LEFT JOIN (
    SELECT customer_id, SUM(grand_total) as total_receivable
    FROM invoices
    WHERE deleted_at IS NULL
    GROUP BY customer_id
) i ON c.id = i.customer_id
LEFT JOIN (
    SELECT customer_id, SUM(amount) as total_received
    FROM transactions
    WHERE deleted_at IS NULL
    GROUP BY customer_id
) t ON c.id = t.customer_id
WHERE c.deleted_at IS NULL";
$balanceResults = $db->rawQuery($balanceQuery);
$balanceData = [];
foreach ($balanceResults as $res) {
    $balanceData[$res['id']] = [
        'balance' => $res['total_receivable'] - $res['total_received']
    ];
}

// 2. Stock Summaries
$stockQuery = "SELECT 
    customer_id, 
    GROUP_CONCAT(CONCAT(product_name, '(', empty_qty, ')') ORDER BY product_name SEPARATOR ', ') AS empty_stock,
    GROUP_CONCAT(CONCAT(product_name, '(', qty, ')') ORDER BY product_name SEPARATOR ', ') AS qty_stock
FROM (
    SELECT 
        i.customer_id, 
        cy.name as product_name,
        SUM(ii.empty_qty) AS empty_qty,
        SUM(ii.qty) AS qty
    FROM 
        invoices i
    JOIN 
        invoice_items ii ON i.id = ii.invoice_id
    JOIN 
        cylinders cy ON cy.id = ii.product_id
    WHERE 
        i.deleted_at IS NULL
    GROUP BY 
        i.customer_id, cy.name
) AS subquery
GROUP BY 
    customer_id";
$stockResults = $db->rawQuery($stockQuery);
$stockSummaries = [];
foreach ($stockResults as $res) {
    $stockSummaries[$res['customer_id']] = [
        'empty_str' => $res['empty_stock'],
        'qty_str' => $res['qty_stock']
    ];
}

// 3. Bonus Summaries
$bonusQuery = "SELECT 
    b.customer_id, 
    GROUP_CONCAT(CONCAT(cy.name, '(', b.qty, ')') ORDER BY cy.name SEPARATOR ', ') AS bonus_stock
FROM 
    bonus_cylinders b
JOIN 
    cylinders cy ON b.product_id = cy.id
GROUP BY 
    b.customer_id";
$bonusResults = $db->rawQuery($bonusQuery);
$bonusSummaries = [];
foreach ($bonusResults as $res) {
    $bonusSummaries[$res['customer_id']] = [
        'bonus_str' => $res['bonus_stock']
    ];
}

// Helper to parse strings like "Oxygen(5), LPG(2)" into array
function parseStockStr($str) {
    $stock = [];
    if (empty($str)) return $stock;
    $items = explode(',', $str);
    foreach ($items as $item) {
        if (preg_match('/(.+?)\((\d+)\)/', trim($item), $matches)) {
            $stock[trim($matches[1])] = (int)$matches[2];
        }
    }
    return $stock;
}

// Step 3: Filter and prepare data
$filteredCustomers = [];
$customerDataMap = [];

foreach ($allCustomers as $customer) {
    $id = $customer['id'];
    
    $balance = isset($balanceData[$id]) ? $balanceData[$id]['balance'] : 0;
    $qtyStr = isset($stockSummaries[$id]) ? $stockSummaries[$id]['qty_str'] : '';
    $emptyStr = isset($stockSummaries[$id]) ? $stockSummaries[$id]['empty_str'] : '';
    $bonusStr = isset($bonusSummaries[$id]) ? $bonusSummaries[$id]['bonus_str'] : '';
    
    // Parse for pending calculation
    $purStock = parseStockStr($qtyStr);
    $empStock = parseStockStr($emptyStr);
    $bonStock = parseStockStr($bonusStr);
    
    $pendingCylinders = [];
    foreach ($purStock as $product => $qty) {
        $empQty = isset($empStock[$product]) ? $empStock[$product] : 0;
        $bonusQty = isset($bonStock[$product]) ? $bonStock[$product] : 0;
        $pendingQty = $qty - ($empQty + $bonusQty);
        if ($pendingQty != 0) {
            $pendingCylinders[$product] = $pendingQty;
        }
    }
    
    $pendingTotal = array_sum($pendingCylinders);
    $totalBonus = array_sum($bonStock);
    
    // Filters
    $include = true;
    if ($balance_filter === 'receivable' && $balance <= 0) $include = false;
    if ($bonus_filter === 'given' && $totalBonus <= 0) $include = false;
    if ($empty_filter === 'receivable' && $pendingTotal <= 0) $include = false;
    
    if ($include) {
        $pendingStr = implode(', ', array_map(function ($p, $q) { return "$p($q)"; }, array_keys($pendingCylinders), $pendingCylinders));
        
        $customerDataMap[$id] = [
            'balance' => $balance,
            'purchased' => $qtyStr,
            'empty' => $emptyStr,
            'bonus' => $bonusStr,
            'pending' => $pendingStr,
            'hasPositivePending' => hasPositiveValue($pendingCylinders)
        ];
        $filteredCustomers[] = $customer;
    }
}

// Step 4: Sorting
$orderColumn = 'name';
switch($columnName) {
    case 'name': $orderColumn = 'name'; break;
    case 'phone': $orderColumn = 'phone'; break;
    case 'created_at': $orderColumn = 'created_at'; break;
}

usort($filteredCustomers, function($a, $b) use ($orderColumn, $columnSortOrder) {
    $valA = $a[$orderColumn];
    $valB = $b[$orderColumn];
    return ($columnSortOrder === 'asc') ? strcmp($valA, $valB) : strcmp($valB, $valA);
});

// Step 5: Pagination
$totalFilteredRecords = count($filteredCustomers);
$pageCustomers = array_slice($filteredCustomers, $row, $rowperpage);

// Step 6: Fetch security details ONLY for the current page
$securityOptions = [];
if (!empty($pageCustomers)) {
    $customerUIDs = array_column($pageCustomers, 'customer_id');
    $db->where('customer_id', $customerUIDs, 'IN');
    $securityData = $db->get('security_options');
    foreach ($securityData as $security) {
        $securityOptions[$security['customer_id']] = $security;
    }
}

// Step 7: Final Data Assembly
$data = [];
foreach ($pageCustomers as $customer) {
    $id = $customer['id'];
    $cData = $customerDataMap[$id];
    
    $row_class = '';
    if ($cData['balance'] > 0) $row_class = 'bg-warning';
    if ($cData['hasPositivePending']) $row_class = 'bg-red';
    if ($cData['balance'] > 0 && $cData['hasPositivePending']) $row_class = 'bg-info';
    
    $securityDetail = '';
    if (isset($securityOptions[$customer['customer_id']])) {
        $s = $securityOptions[$customer['customer_id']];
        if (!empty($s['security_type'])) {
            $securityDetail = '<span class="badge badge-info">' . $s['security_type'] . '</span> ';
        }
    }
    
    $actions = '';
    if (checkPermission('customers_edit')) {
        $actions .= '<a href="edit_customer.php?id=' . $customer['customer_id'] . '" class="btn btn-warning btn-sm">Edit</a> ';
    }
    if (checkPermission('customers_delete')) {
        $actions .= '<a class="btn btn-danger btn-sm" onclick="deleteRow(' . $customer['id'] . ')">Delete</a>';
    }

    $data[] = [
        'name' => '<a href="customer-ledger.php?id=' . $customer['id'] . '" class="text-primary"><b>' . $customer['name'] . '</b></a>',
        'customer_id' => $customer['customer_id'],
        'phone' => $customer['phone'],
        'cnic' => $customer['cnic'],
        'balance' => $cData['balance'],
        'purchased' => $cData['purchased'],
        'empty' => $cData['empty'],
        'security' => $securityDetail . '<a href="security_details.php?id=' . $customer['customer_id'] . '" class="btn btn-primary btn-sm">Details</a>',
        'bonus_cylinders' => $cData['bonus'],
        'pending_cylinders' => $cData['pending'],
        'row_class' => $row_class,
        'actions' => $actions
    ];
}

header('Content-Type: application/json');
echo json_encode([
    "draw" => intval($draw),
    "iTotalRecords" => count($allCustomers), // This is technically count after search, but it's consistent with original
    "iTotalDisplayRecords" => $totalFilteredRecords,
    "data" => $data
]);