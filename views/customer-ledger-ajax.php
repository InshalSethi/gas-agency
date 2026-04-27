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
$customer_id = $_REQUEST['customer_id'];

$cols = array(
    "inv.id", "inv.customer_id", "inv.empty_cylinders", "inv.date",
    "inv.grand_total", "inv.received", "inv.balance", "inv.created_at",
    "cus.name as customer"
);

$db->join("customers cus", "inv.customer_id=cus.id", "LEFT");

if (!empty($customer_id)) {
    $db->where("inv.customer_id", $customer_id);
}

if (!empty($searchQuery)) {
    $db->where("(cus.name LIKE ? OR inv.date LIKE ? OR inv.grand_total LIKE ? OR inv.received LIKE ? OR inv.balance LIKE ?)", 
        array('%'.$searchQuery.'%', '%'.$searchQuery.'%', '%'.$searchQuery.'%', '%'.$searchQuery.'%', '%'.$searchQuery.'%'));
}

$db->where("inv.deleted_at", NULL, 'IS');
$db->orderBy("inv.id", "desc");

// We need the total count for filtered records
$countDb = clone $db;
$invoices = $db->get('invoices inv', array($row, $rowperpage), $cols);

$totalRecordsQuery = "SELECT COUNT(*) as count FROM invoices WHERE deleted_at IS NULL" . (!empty($customer_id) ? " AND customer_id = $customer_id" : "");
$totalRecords = $db->rawQueryOne($totalRecordsQuery)['count'];
$totalDisplayRecords = $countDb->getValue('invoices inv', 'count(*)'); // This gives filtered count

if (empty($invoices)) {
    echo json_encode(["draw" => intval($draw), "iTotalRecords" => $totalRecords, "iTotalDisplayRecords" => 0, "data" => []]);
    exit;
}

$invoiceIds = array_column($invoices, 'id');

// Batch fetch items for calculating classes
$db->where('invoice_id', $invoiceIds, 'IN');
$allItems = $db->get('invoice_items');
$itemTotals = [];
foreach ($allItems as $item) {
    if (!isset($itemTotals[$item['invoice_id']])) $itemTotals[$item['invoice_id']] = ['qty' => 0, 'empty_qty' => 0];
    $itemTotals[$item['invoice_id']]['qty'] += $item['qty'];
    $itemTotals[$item['invoice_id']]['empty_qty'] += $item['empty_qty'];
}

// Batch fetch stock details
$stockQuery = "SELECT 
    invoice_id,
    GROUP_CONCAT(CONCAT(product_name, '(', empty_qty, ')') ORDER BY product_name SEPARATOR ', ') AS empty_stock,
    GROUP_CONCAT(CONCAT(product_name, '(', qty, ')') ORDER BY product_name SEPARATOR ', ') AS qty_stock
FROM (
    SELECT 
        ii.invoice_id, 
        cy.name as product_name,
        SUM(ii.empty_qty) AS empty_qty,
        SUM(ii.qty) AS qty
    FROM 
        invoice_items ii
    JOIN 
        cylinders cy ON cy.id = ii.product_id
    WHERE 
        ii.invoice_id IN (" . implode(',', $invoiceIds) . ")
    GROUP BY 
        ii.invoice_id, cy.name
) AS subquery
GROUP BY 
    invoice_id";
$stockResults = $db->rawQuery($stockQuery);
$stockMap = [];
foreach ($stockResults as $res) {
    $stockMap[$res['invoice_id']] = $res;
}

// Get bonus stock for the customer (single query)
$getBonusStockDetails = getCustomerBonus($db, $customer_id);
$bonusStock = [];
if (!empty($getBonusStockDetails['bonus_stock'])) {
    $items = explode(',', $getBonusStockDetails['bonus_stock']);
    foreach ($items as $item) {
        if (preg_match('/(.+?)\((\d+)\)/', trim($item), $matches)) {
            $bonusStock[trim($matches[1])] = (int)$matches[2];
        }
    }
}

function parseStockLocal($str) {
    $s = [];
    if (empty($str)) return $s;
    foreach (explode(',', $str) as $i) {
        if (preg_match('/(.+?)\((\d+)\)/', trim($i), $m)) $s[trim($m[1])] = (int)$m[2];
    }
    return $s;
}

$data = [];
foreach ($invoices as $invoice) {
    $totals = isset($itemTotals[$invoice['id']]) ? $itemTotals[$invoice['id']] : ['qty' => 0, 'empty_qty' => 0];
    $row_class = '';
    if ($invoice['balance'] > 0) $row_class = 'bg-warning';
    if ($totals['qty'] > $totals['empty_qty']) $row_class = 'bg-red';
    if ($invoice['balance'] > 0 && $totals['qty'] > $totals['empty_qty']) $row_class = 'bg-info';

    $stock = isset($stockMap[$invoice['id']]) ? $stockMap[$invoice['id']] : ['qty_stock' => '', 'empty_stock' => ''];
    $purStock = parseStockLocal($stock['qty_stock']);
    $empStock = parseStockLocal($stock['empty_stock']);

    $pending = [];
    $bonusCopy = $bonusStock;
    foreach ($purStock as $p => $q) {
        $eq = isset($empStock[$p]) ? $empStock[$p] : 0;
        $bq = isset($bonusCopy[$p]) ? $bonusCopy[$p] : 0;
        $pq = $q - $eq;
        if ($pq > 0 && $bq > 0) {
            $used = min($pq, $bq);
            $pq -= $used;
            $bonusCopy[$p] -= $used;
        }
        if ($pq > 0) $pending[$p] = $pq;
    }

    $data[] = [
        'id' => $invoice['id'],
        'customer' => $invoice['customer'],
        'empty_cylinders' => $stock['empty_stock'],
        'total_cylinders' => $stock['qty_stock'],
        'pending_cylinders' => implode(', ', array_map(function ($p, $q) { return "$p($q)"; }, array_keys($pending), $pending)),
        'date' => date("d-m-Y", strtotime($invoice['date'])),
        'grand_total' => $invoice['grand_total'],
        'received' => $invoice['received'],
        'balance' => $invoice['balance'],
        'created_at' => date("d-m-Y h:i:s", strtotime($invoice['created_at'])),
        'row_class' => $row_class,
        'actions' => '<a href="edit-invoice.php?id=' . $invoice['id'] . '" class="btn btn-warning btn-sm">Edit</a>'
    ];
}

header('Content-Type: application/json');
echo json_encode(["draw" => intval($draw), "iTotalRecords" => $totalRecords, "iTotalDisplayRecords" => $totalDisplayRecords, "data" => $data]);