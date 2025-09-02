<?php
// get-customer-discount.php
// AJAX endpoint to fetch customer discount and increase percentages

require '../config/db.php';
require_once '../config/db_functions.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if customer_id is provided
if (!isset($_GET['customer_id']) || empty($_GET['customer_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Customer ID is required'
    ]);
    exit;
}

$customer_id = $_GET['customer_id'];

try {
    // Fetch customer discount and increase percentages
    $db->where("id", $customer_id);
    $customer = $db->getOne("customers", "percentage_discount, percentage_increase, name");
    
    if ($customer) {
        echo json_encode([
            'success' => true,
            'data' => [
                'customer_id' => $customer_id,
                'customer_name' => $customer['name'],
                'percentage_discount' => $customer['percentage_discount'] ? floatval($customer['percentage_discount']) : 0,
                'percentage_increase' => $customer['percentage_increase'] ? floatval($customer['percentage_increase']) : 0
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Customer not found'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
