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

// Step 1: Get ALL customers matching search query (before filters)
$db->where("deleted_at", NULL, 'IS');

if (!empty($searchQuery)) {
    $db->where("(name LIKE '%" . $searchQuery . "%' OR phone LIKE '%" . $searchQuery . "%' OR cnic LIKE '%" . $searchQuery . "%' OR created_at LIKE '%" . $searchQuery . "%')");
}

// Get all customers with search filter
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

// Get all customer IDs
$allCustomerIds = array_column($allCustomers, 'id');

// Step 2: Calculate balance, stock, and bonus for ALL customers to apply filters
// Get balance calculations using the same logic as original code
$balanceData = [];
foreach ($allCustomerIds as $customerId) {
    $receivedCustomersArray = [];
    $totalReceivable = 0;
    $totalReceived = 0;

    // Fetch invoices - exactly like original code
    $db->where("deleted_at", NULL, 'IS');
    $db->where("customer_id", $customerId);
    $invoices = $db->get("invoices");
    foreach ($invoices as $invoice) {
        $totalReceivable += $invoice['grand_total'];
        
        if (!in_array($invoice['customer_id'], $receivedCustomersArray)) {
            array_push($receivedCustomersArray, $invoice['customer_id']);
        }
    }

    // Fetch transactions - exactly like original code
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

// Get stock summaries using the same query structure as original code
$stockSummaries = [];
foreach ($allCustomerIds as $customerId) {
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

    // Parse purchased stock - exactly like original code
    if ($getStockDetails && !empty($getStockDetails['qty_stock'])) {
        $qtyStockArray = explode(',', $getStockDetails['qty_stock']);
        foreach ($qtyStockArray as $item) {
            preg_match('/(.+?)\((\d+)\)/', trim($item), $matches);
            if (isset($matches[1]) && isset($matches[2])) {
                $purStock[trim($matches[1])] = (int)$matches[2];
            }
        }
    }

    // Parse empty stock - exactly like original code
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

// Get bonus summaries using the same logic as original code
$bonusSummaries = [];
foreach ($allCustomerIds as $customerId) {
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

// Step 3: Apply filters to get filtered customer IDs
$filteredCustomerIds = [];
$customerDataMap = [];
foreach ($allCustomers as $customer) {
    $customerId = $customer['id'];
    
    // Calculate balance
    $balance = isset($balanceData[$customerId]) ? $balanceData[$customerId]['balance'] : 0;
    
    // Get stock data
    $purchasedStock = isset($stockSummaries[$customerId]) ? $stockSummaries[$customerId]['purchased'] : [];
    $emptyStock = isset($stockSummaries[$customerId]) ? $stockSummaries[$customerId]['empty'] : [];
    
    // Get bonus data
    $bonusStock = isset($bonusSummaries[$customerId]) ? $bonusSummaries[$customerId]['bonus'] : [];
    $totalBonus = isset($bonusSummaries[$customerId]) ? $bonusSummaries[$customerId]['total_bonus'] : 0;
    
    // Calculate pending cylinders
    $pendingCylinders = [];
    foreach ($purchasedStock as $product => $qty) {
        $empQty = isset($emptyStock[$product]) ? $emptyStock[$product] : 0;
        $bonusQty = isset($bonusStock[$product]) ? $bonusStock[$product] : 0;
        $pendingQty = $qty - ($empQty + $bonusQty);
        $pendingCylinders[$product] = $pendingQty;
    }

    $pendingStr = implode(', ', array_map(function ($product, $qty) {
        return "$product($qty)";
    }, array_keys($pendingCylinders), $pendingCylinders));

    // Calculate total pending
    $pendingTotal = 0;
    if (!empty($pendingStr)) {
        $pendingTotal = sumBracketNumbers($pendingStr);
    }
    
    // Apply filters
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
    
    if ($includeRecord) {
        $filteredCustomerIds[] = $customerId;
        // Store customer data with calculated values for later use
        $customerDataMap[$customerId] = [
            'customer' => $customer,
            'balance' => $balance,
            'purchasedStock' => $purchasedStock,
            'emptyStock' => $emptyStock,
            'bonusStock' => $bonusStock,
            'pendingCylinders' => $pendingCylinders,
            'pendingTotal' => $pendingTotal,
            'totalBonus' => $totalBonus
        ];
    }
}

// Step 4: Get filtered customers and sort them
$filteredCustomers = [];
foreach ($filteredCustomerIds as $customerId) {
    $filteredCustomers[] = $customerDataMap[$customerId]['customer'];
}

// Apply ordering to filtered customers
$orderColumn = 'name';
switch($columnName) {
    case 'name':
        $orderColumn = 'name';
        break;
    case 'phone':
        $orderColumn = 'phone';
        break;
    case 'created_at':
        $orderColumn = 'created_at';
        break;
}

usort($filteredCustomers, function($a, $b) use ($orderColumn, $columnSortOrder) {
    $valA = $a[$orderColumn];
    $valB = $b[$orderColumn];
    
    if ($columnSortOrder === 'asc') {
        return strcmp($valA, $valB);
    } else {
        return strcmp($valB, $valA);
    }
});

// Step 5: Paginate filtered customers
$totalFilteredRecords = count($filteredCustomers);
$pageCustomers = array_slice($filteredCustomers, $row, $rowperpage);

if (empty($pageCustomers)) {
    $response = [
        "draw" => intval($draw),
        "iTotalRecords" => count($allCustomers),
        "iTotalDisplayRecords" => $totalFilteredRecords,
        "data" => []
    ];
    echo json_encode($response);
    exit;
}

// Get customer IDs for current page
$customerIds = array_column($pageCustomers, 'id');

// Step 6: Get security options for paginated customers
$securityOptions = [];
if (!empty($customerIds)) {
    $db->where('customer_id', array_column($pageCustomers, 'customer_id'), 'IN');
    $securityData = $db->get('security_options');
    foreach ($securityData as $security) {
        $securityOptions[$security['customer_id']] = $security;
    }
}

// Step 7: Process paginated data
$data = [];
foreach ($pageCustomers as $customer) {
    $customerId = $customer['id'];
    $customerData = $customerDataMap[$customerId];
    
    $balance = $customerData['balance'];
    $purchasedStock = $customerData['purchasedStock'];
    $emptyStock = $customerData['emptyStock'];
    $bonusStock = $customerData['bonusStock'];
    $pendingCylinders = $customerData['pendingCylinders'];
    
    $purchasedStr = isset($stockSummaries[$customerId]) ? $stockSummaries[$customerId]['purchased_str'] : '';
    $emptyStr = isset($stockSummaries[$customerId]) ? $stockSummaries[$customerId]['empty_str'] : '';
    $bonusStr = isset($bonusSummaries[$customerId]) ? $bonusSummaries[$customerId]['bonus_str'] : '';
    
    $pendingStr = implode(', ', array_map(function ($product, $qty) {
        return "$product($qty)";
    }, array_keys($pendingCylinders), $pendingCylinders));
    
    // Apply hasPositiveValue function like original for row class
    $hasPositivePending = hasPositiveValue($pendingCylinders);
    
    // Determine row class - exactly like original code
    $row_class = '';
    if ($balance > 0) {
        $row_class = 'bg-warning';
    }
    if ($hasPositivePending) {
        $row_class = 'bg-red';
    }
    if ($balance > 0 && $hasPositivePending) {
        $row_class = 'bg-info';
    }
    
    // Security details
    $securityDetail = '';
    if (isset($securityOptions[$customer['customer_id']])) {
        $security = $securityOptions[$customer['customer_id']];
        if (!empty($security['security_type'])) {
            $securityDetail = '<span class="badge badge-info">' . $security['security_type'] . '</span> ';
        }
    }
    
    $data[] = [
        'name' => '<a href="customer-ledger.php?id=' . $customer['id'] . '" class="text-primary"><b>' . $customer['name'] . '</b></a>',
        'customer_id' => $customer['customer_id'],
        'phone' => $customer['phone'],
        'cnic' => $customer['cnic'],
        'balance' => $balance,
        'purchased' => $purchasedStr,
        'empty' => $emptyStr,
        'security' => $securityDetail . '<a href="security_details.php?id=' . $customer['customer_id'] . '" class="btn btn-primary btn-sm">Details</a>',
        'bonus_cylinders' => $bonusStr,
        'pending_cylinders' => $pendingStr,
        'row_class' => $row_class,
        'actions' => '
            <a href="edit_customer.php?id=' . $customer['customer_id'] . '" class="btn btn-warning btn-sm">Edit</a>
            <a class="btn btn-danger btn-sm" onclick="deleteRow(' . $customer['id'] . ')">Delete</a>'
    ];
}

// Prepare response with correct counts
$response = [
    "draw" => intval($draw),
    "iTotalRecords" => count($allCustomers),
    "iTotalDisplayRecords" => $totalFilteredRecords,
    "data" => $data
];

echo json_encode($response);
?>