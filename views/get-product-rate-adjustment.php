<?php
// get-product-rate-adjustment.php
// AJAX endpoint to fetch product-specific rate adjustments for a customer-product combination
// Returns only product-specific rates from customer_rate_adjustment_products table
// No fallback to customer-level rates

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/db_functions.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if required parameters are provided
if (!isset($_GET['customer_id']) || empty($_GET['customer_id']) || 
    !isset($_GET['product_id']) || empty($_GET['product_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Customer ID and Product ID are required'
    ]);
    exit;
}

$customer_id = $_GET['customer_id'];
$product_id = $_GET['product_id'];

// Debug logging (remove in production)
// error_log("Product Rate Adjustment Request - Customer ID: $customer_id, Product ID: $product_id");

try {
    // Get customer name for response
    $db->where("id", $customer_id);
    $customer = $db->getOne("customers", "name");

    // Fetch product-specific rate adjustment only
    $db->where("customer_id", $customer_id);
    $db->where("product_id", $product_id);
    $rate_adjustment = $db->getOne("customer_rate_adjustment_products", "percentage_discount, percentage_increase, product_name");

    if ($rate_adjustment) {
        // Product-specific rates found
        echo json_encode([
            'success' => true,
            'data' => [
                'customer_id' => $customer_id,
                'product_id' => $product_id,
                'customer_name' => $customer ? $customer['name'] : '',
                'product_name' => $rate_adjustment['product_name'],
                'percentage_discount' => floatval($rate_adjustment['percentage_discount']),
                'percentage_increase' => floatval($rate_adjustment['percentage_increase']),
                'source' => 'product_specific'
            ]
        ]);
    } else {
        // No product-specific rates found - return zero rates (use base price)
        echo json_encode([
            'success' => true,
            'data' => [
                'customer_id' => $customer_id,
                'product_id' => $product_id,
                'customer_name' => $customer ? $customer['name'] : '',
                'product_name' => '',
                'percentage_discount' => 0,
                'percentage_increase' => 0,
                'source' => 'no_rates_found'
            ]
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
