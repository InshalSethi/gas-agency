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

// Get all non-deleted customers with search filter applied
$db->where("deleted_at", NULL, 'IS');

if (!empty($searchQuery)) {
    $db->where("(name LIKE '%" . $searchQuery . "%' OR phone LIKE '%" . $searchQuery . "%' OR cnic LIKE '%" . $searchQuery . "%' OR created_at LIKE '%" . $searchQuery . "%')");
}

$customers = $db->get('customers', null, ['id', 'customer_id', 'name', 'phone', 'cnic']);

// Get all customer IDs
$customerIds = array_column($customers, 'id');

// Get balance calculations for all customers
$balanceData = [];
foreach ($customerIds as $customerId) {
    $receivedCustomersArray = [];
    $totalReceivable = 0;
    $totalReceived = 0;

    // Fetch invoices
    $db->where("deleted_at", NULL, 'IS');
    $db->where("customer_id", $customerId);
    $invoices = $db->get("invoices");
    foreach ($invoices as $invoice) {
        $totalReceivable += $invoice['grand_total'];
        
        if (!in_array($invoice['customer_id'], $receivedCustomersArray)) {
            array_push($receivedCustomersArray, $invoice['customer_id']);
        }
    }

    // Fetch transactions
    if (!empty($receivedCustomersArray)) {
        $db->where('customer_id', $receivedCustomersArray, 'IN');
        $db->where("deleted_at", NULL, 'IS');
        $transactions = $db->get("transactions");
        foreach ($transactions as $transaction) {
            $totalReceived += $transaction['amount'];
        }
    }
    
    $balance = $totalReceivable - $totalReceived;
    
    $balanceData[$customerId] = [
        'total_receivable' => $totalReceivable,
        'total_received' => $totalReceived,
        'balance' => $balance
    ];
}

// Get stock summaries for all customers
$stockSummaries = [];
foreach ($customerIds as $customerId) {
    $query = "SELECT 
                customer_id, 
                customer_name,
                GROUP_CONCAT(CONCAT(product_name, '(', empty_qty, ')') ORDER BY product_name SEPARATOR ', ') AS empty_stock,
                GROUP_CONCAT(CONCAT(product_name, '(', qty, ')') ORDER BY product_name SEPARATOR ', ') AS qty_stock
            FROM (
                SELECT 
                    c.id AS customer_id, 
                    c.name AS customer_name, 
                    cy.name as product_name,
                    SUM(ii.empty_qty) AS empty_qty,
                    SUM(ii.qty) AS qty
                FROM 
                    customers c
                JOIN 
                    invoices i ON c.id = i.customer_id
                JOIN 
                    invoice_items ii ON i.id = ii.invoice_id
                JOIN 
                    cylinders cy ON cy.id = ii.product_id
                WHERE 
                    c.id = ? AND i.deleted_at IS NULL
                GROUP BY 
                    c.id, c.name, cy.name
            ) AS subquery
            GROUP BY 
                customer_id, customer_name;";

    $getStockDetails = $db->rawQueryOne($query, [$customerId]);
    
    $purStock = [];
    $empStock = [];

    // Parse purchased stock
    if ($getStockDetails && !empty($getStockDetails['qty_stock'])) {
        $qtyStockArray = explode(',', $getStockDetails['qty_stock']);
        foreach ($qtyStockArray as $item) {
            preg_match('/(.+?)\((\d+)\)/', trim($item), $matches);
            if (isset($matches[1]) && isset($matches[2])) {
                $purStock[trim($matches[1])] = (int)$matches[2];
            }
        }
    }

    // Parse empty stock
    if ($getStockDetails && !empty($getStockDetails['empty_stock'])) {
        $emptyStockArray = explode(',', $getStockDetails['empty_stock']);
        foreach ($emptyStockArray as $item) {
            preg_match('/(.+?)\((\d+)\)/', trim($item), $matches);
            if (isset($matches[1]) && isset($matches[2])) {
                $empStock[trim($matches[1])] = (int)$matches[2];
            }
        }
    }
    
    $stockSummaries[$customerId] = [
        'purchased' => $purStock,
        'empty' => $empStock,
        'purchased_str' => ($getStockDetails && isset($getStockDetails['qty_stock'])) ? $getStockDetails['qty_stock'] : '',
        'empty_str' => ($getStockDetails && isset($getStockDetails['empty_stock'])) ? $getStockDetails['empty_stock'] : ''
    ];
}

// Get bonus summaries for all customers
$bonusSummaries = [];
foreach ($customerIds as $customerId) {
    $getBonusStockDetails = getCustomerBonus($db, $customerId);
    
    if (!empty($getBonusStockDetails['bonus_stock'])) {
        $bonusStock = [];
        $bonusStockArray = explode(',', $getBonusStockDetails['bonus_stock']);
        foreach ($bonusStockArray as $item) {
            preg_match('/(.+?)\((\d+)\)/', trim($item), $matches);
            if (isset($matches[1]) && isset($matches[2])) {
                $bonusStock[trim($matches[1])] = (int)$matches[2];
            }
        }
        
        $bonusSummaries[$customerId] = [
            'bonus' => $bonusStock,
            'bonus_str' => $getBonusStockDetails['bonus_stock'],
            'total_bonus' => array_sum($bonusStock)
        ];
    } else {
        $bonusSummaries[$customerId] = [
            'bonus' => [],
            'bonus_str' => '',
            'total_bonus' => 0
        ];
    }
}

// Process and write data to CSV
foreach ($customers as $customer) {
    $customerId = $customer['id'];
    
    // Calculate balance
    $balance = isset($balanceData[$customerId]) ? $balanceData[$customerId]['balance'] : 0;
    
    // Get stock data
    $purchasedStock = isset($stockSummaries[$customerId]) ? $stockSummaries[$customerId]['purchased'] : [];
    $emptyStock = isset($stockSummaries[$customerId]) ? $stockSummaries[$customerId]['empty'] : [];
    $purchasedStr = isset($stockSummaries[$customerId]) ? $stockSummaries[$customerId]['purchased_str'] : '';
    $emptyStr = isset($stockSummaries[$customerId]) ? $stockSummaries[$customerId]['empty_str'] : '';
    
    // Get bonus data
    $bonusStock = isset($bonusSummaries[$customerId]) ? $bonusSummaries[$customerId]['bonus'] : [];
    $bonusStr = isset($bonusSummaries[$customerId]) ? $bonusSummaries[$customerId]['bonus_str'] : '';
    
    // Calculate pending cylinders
    $pendingCylinders = [];
    foreach ($purchasedStock as $product => $qty) {
        $empQty = isset($emptyStock[$product]) ? $emptyStock[$product] : 0;
        $bonusQty = isset($bonusStock[$product]) ? $bonusStock[$product] : 0;
        $pendingQty = $qty - ($empQty + $bonusQty);
        if ($pendingQty > 0) {
            $pendingCylinders[$product] = $pendingQty;
        }
    }

    $pendingStr = implode(', ', array_map(function ($product, $qty) {
        return "$product($qty)";
    }, array_keys($pendingCylinders), $pendingCylinders));

    // Calculate total pending using sumBracketNumbers function
    $pendingTotal = 0;
    if (!empty($pendingStr)) {
        $pendingTotal = sumBracketNumbers($pendingStr);
    }
    
    // Get total bonus
    $totalBonus = isset($bonusSummaries[$customerId]) ? $bonusSummaries[$customerId]['total_bonus'] : 0;
    
    // Apply filters (same logic as fetch_customers.php)
    $includeRecord = true;
    
    if ($balance_filter === 'receivable' && $balance <= 0) {
        $includeRecord = false;
    }
    if ($bonus_filter === 'given' && $totalBonus <= 0) {
        $includeRecord = false;
    }
    if ($empty_filter === 'receivable' && $pendingTotal == 0) {
        $includeRecord = false;
    }
    
    // Skip this record if it doesn't match filters
    if (!$includeRecord) {
        continue;
    }
    
    // Prepare row data
    $row = [
        $customer['customer_id'],
        $customer['name'],
        $customer['phone'],
        $customer['cnic'],
        number_format($balance, 2),
        $purchasedStr ?: 'N/A',
        $emptyStr ?: 'N/A',
        $bonusStr ?: 'N/A',
        $pendingStr ?: 'N/A'
    ];
    
    fputcsv($output, $row);
}

fclose($output);
exit;
?>
