<?php
require '../config/db.php';
require_once '../config/db_functions.php';
require '../config/auth.php';

// Get filter parameters from URL
$searchQuery = isset($_REQUEST['search_query']) ? $_REQUEST['search_query'] : '';
$balance_filter = isset($_REQUEST['balance_filter']) ? $_REQUEST['balance_filter'] : '';
$empty_filter = isset($_REQUEST['empty_filter']) ? $_REQUEST['empty_filter'] : '';
$bonus_filter = isset($_REQUEST['bonus_filter']) ? $_REQUEST['bonus_filter'] : '';

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="customers_' . date('Y-m-d_His') . '.csv"');

// Create output stream
$output = fopen('php://output', 'w');

// Add BOM for UTF-8 to help Excel display special characters correctly
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// CSV Headers
$headers = [
    'Customer ID',
    'Name',
    'Phone',
    'CNIC',
    'Balance',
    'Purchased Cylinders',
    'Empty Cylinders',
    'Bonus Cylinders',
    'Pending Cylinders'
];

fputcsv($output, $headers);

// Step 1: Get customers matching search query
$db->where("deleted_at", NULL, 'IS');
if (!empty($searchQuery)) {
    $db->where("(name LIKE '%" . $searchQuery . "%' OR phone LIKE '%" . $searchQuery . "%' OR cnic LIKE '%" . $searchQuery . "%' OR created_at LIKE '%" . $searchQuery . "%')");
}
$customers = $db->get('customers', null, ['id', 'customer_id', 'name', 'phone', 'cnic']);

if (empty($customers)) {
    fclose($output);
    exit;
}

// Step 2: Batch fetch ALL necessary data
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
$balanceDataMap = [];
foreach ($balanceResults as $res) {
    $balanceDataMap[$res['id']] = $res['total_receivable'] - $res['total_received'];
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
$stockDataMap = [];
foreach ($stockResults as $res) {
    $stockDataMap[$res['customer_id']] = [
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
$bonusDataMap = [];
foreach ($bonusResults as $res) {
    $bonusDataMap[$res['customer_id']] = $res['bonus_stock'];
}

// Helper to parse strings like "Oxygen(5), LPG(2)" into array
function parseStockStrLocal($str) {
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

// Process and write data to CSV
foreach ($customers as $customer) {
    $id = $customer['id'];
    
    $balance = isset($balanceDataMap[$id]) ? $balanceDataMap[$id] : 0;
    $qtyStr = isset($stockDataMap[$id]) ? $stockDataMap[$id]['qty_str'] : '';
    $emptyStr = isset($stockDataMap[$id]) ? $stockDataMap[$id]['empty_str'] : '';
    $bonusStr = isset($bonusDataMap[$id]) ? $bonusDataMap[$id] : '';
    
    // Parse for pending calculation
    $purStock = parseStockStrLocal($qtyStr);
    $empStock = parseStockStrLocal($emptyStr);
    $bonStock = parseStockStrLocal($bonusStr);
    
    $pendingCylinders = [];
    foreach ($purStock as $product => $qty) {
        $empQty = isset($empStock[$product]) ? $empStock[$product] : 0;
        $bonusQty = isset($bonStock[$product]) ? $bonStock[$product] : 0;
        $pendingQty = $qty - ($empQty + $bonusQty);
        if ($pendingQty > 0) {
            $pendingCylinders[$product] = $pendingQty;
        }
    }

    $pendingStr = implode(', ', array_map(function ($p, $q) { return "$p($q)"; }, array_keys($pendingCylinders), $pendingCylinders));
    $pendingTotal = array_sum($pendingCylinders);
    $totalBonus = array_sum($bonStock);
    
    // Filters
    $include = true;
    if ($balance_filter === 'receivable' && $balance <= 0) $include = false;
    if ($bonus_filter === 'given' && $totalBonus <= 0) $include = false;
    if ($empty_filter === 'receivable' && $pendingTotal <= 0) $include = false;
    
    if (!$include) continue;
    
    $row = [
        $customer['customer_id'],
        $customer['name'],
        $customer['phone'],
        $customer['cnic'],
        number_format($balance, 2),
        $qtyStr ?: 'N/A',
        $emptyStr ?: 'N/A',
        $bonusStr ?: 'N/A',
        $pendingStr ?: 'N/A'
    ];
    
    fputcsv($output, $row);
}

fclose($output);
exit;
