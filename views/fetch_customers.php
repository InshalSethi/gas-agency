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

// Step 1: Get filtered customer IDs first
$db->where("deleted_at", NULL, 'IS');

if (!empty($searchQuery)) {
    $db->where("(name LIKE '%" . $searchQuery . "%' OR phone LIKE '%" . $searchQuery . "%' OR cnic LIKE '%" . $searchQuery . "%' OR created_at LIKE '%" . $searchQuery . "%')");
}

// Get total count
$totalCustomers = $db->get('customers', null, 'id');
$totalRecords = count($totalCustomers);

// Step 2: Get customer IDs for current page
$db->where("deleted_at", NULL, 'IS');

if (!empty($searchQuery)) {
    $db->where("(name LIKE '%" . $searchQuery . "%' OR phone LIKE '%" . $searchQuery . "%' OR cnic LIKE '%" . $searchQuery . "%' OR created_at LIKE '%" . $searchQuery . "%')");
}

// Apply ordering
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

$db->orderBy($orderColumn, $columnSortOrder);
$pageCustomers = $db->get('customers', [$row, $rowperpage], ['id', 'customer_id', 'name', 'phone', 'cnic', 'created_at']);

if (empty($pageCustomers)) {
    $response = [
        "draw" => intval($draw),
        "iTotalRecords" => $totalRecords,
        "iTotalDisplayRecords" => 0,
        "data" => []
    ];
    echo json_encode($response);
    exit;
}

// Get customer IDs array
$customerIds = array_column($pageCustomers, 'id');
$customerIdsList = implode(',', $customerIds);

// Step 3: Get all related data in batch queries
// Get security options
$securityOptions = [];
if (!empty($customerIds)) {
    $db->where('customer_id', $customerIds, 'IN');
    $securityData = $db->get('security_options');
    foreach ($securityData as $security) {
        $securityOptions[$security['customer_id']] = $security;
    }
}

// Get balance calculations using the same logic as original code
$balanceData = [];
foreach ($customerIds as $customerId) {
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

    // Parse purchased stock - exactly like original code
    if (!empty($getStockDetails['qty_stock'])) {
        $qtyStockArray = explode(',', $getStockDetails['qty_stock']);
        foreach ($qtyStockArray as $item) {
            preg_match('/(.+?)\((\d+)\)/', trim($item), $matches);
            if (isset($matches[1]) && isset($matches[2])) {
                $purStock[trim($matches[1])] = (int)$matches[2];
            }
        }
    }

    // Parse empty stock - exactly like original code
    if (!empty($getStockDetails['empty_stock'])) {
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
        'purchased_str' => $getStockDetails['qty_stock'] ?: '',
        'empty_str' => $getStockDetails['empty_stock'] ?: ''
    ];
}

// Get bonus summaries using the same logic as original code
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

// Step 4: Process and filter data
$data = [];
foreach ($pageCustomers as $customer) {
    $customerId = $customer['id'];
    
    // Calculate balance - exactly like original code
    $balance = isset($balanceData[$customerId]) ? $balanceData[$customerId]['balance'] : 0;
    
    // Get stock data
    $purchasedStock = isset($stockSummaries[$customerId]) ? $stockSummaries[$customerId]['purchased'] : [];
    $emptyStock = isset($stockSummaries[$customerId]) ? $stockSummaries[$customerId]['empty'] : [];
    $purchasedStr = isset($stockSummaries[$customerId]) ? $stockSummaries[$customerId]['purchased_str'] : '';
    $emptyStr = isset($stockSummaries[$customerId]) ? $stockSummaries[$customerId]['empty_str'] : '';
    
    // Get bonus data
    $bonusStock = isset($bonusSummaries[$customerId]) ? $bonusSummaries[$customerId]['bonus'] : [];
    $bonusStr = isset($bonusSummaries[$customerId]) ? $bonusSummaries[$customerId]['bonus_str'] : '';
    $totalBonus = isset($bonusSummaries[$customerId]) ? $bonusSummaries[$customerId]['total_bonus'] : 0;
    
    // Calculate pending cylinders - exactly like original code
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

    // Calculate total pending using sumBracketNumbers function like original
    $pendingTotal = 0;
    if (!empty($pendingStr)) {
        $pendingTotal = sumBracketNumbers($pendingStr);
    }
    
    // Apply hasPositiveValue function like original for row class
    $hasPositivePending = hasPositiveValue($pendingCylinders);
    
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
    
    if (!$includeRecord) {
        continue;
    }
    
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
    if (isset($securityOptions[$customerId])) {
        $security = $securityOptions[$customerId];
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

// Prepare response
$response = [
    "draw" => intval($draw),
    "iTotalRecords" => $totalRecords,
    "iTotalDisplayRecords" => $totalRecords,
    "data" => $data
];

echo json_encode($response);
?>