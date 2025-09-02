<?php
// views/db_functions.php
$main_site = 'http://localhost/cylinder/';
define ('WP_HOME',$main_site);
date_default_timezone_set("Asia/Karachi");


function baseurl($ur){
 $url=WP_HOME.$ur;
 return $url;
}
require_once 'db.php';

//My Functions
function sumBracketNumbers($pendingCylindersStr) {
    // Regular expression to match numbers inside brackets
    preg_match_all('/\((\d+)\)/', $pendingCylindersStr, $matches);
    
    // Sum all the numbers found in the matches
    $sum = array_sum($matches[1]);
    
    return $sum;
}
function hasPositiveValue($array) {
    foreach ($array as $value) {
        if ($value > 0) {
            return true;
        }
    }
    return false;
}
function getCustomerBonus($db,$customer_id){
    $query2 = "SELECT 
                c.id as customer_id, 
                c.name as customer_name,
                GROUP_CONCAT(CONCAT(cy.name, '(', b.qty, ')') ORDER BY cy.name SEPARATOR ', ') AS bonus_stock
            FROM 
                customers c
            JOIN 
                bonus_cylinders b ON b.customer_id = c.id
            JOIN 
                cylinders cy ON b.product_id = cy.id
            WHERE 
                c.id = ?
            GROUP BY 
                c.id;";

    // Use rawQueryOne to execute the query with the customer_id parameter
    return $db->rawQueryOne($query2, [$customer_id]);
}
function RemoveStockQty($pro_id,$pro_qty,$db){
  $new_qty=0;
  $db->where('id',$pro_id);
  $old_qty=$db->getValue('cylinders','qty');

  $new_qty= $old_qty -$pro_qty;

  if ($new_qty > 0) {
    $update=array("qty"=>$new_qty);
    $db->where('id',$pro_id);
    $db->update('cylinders',$update);
} else{
    $update=array("qty"=>'0');
    $db->where('id',$pro_id);
    $db->update('cylinders',$update);
}
}
function addEmptyStockQty($pro_id,$pro_qty,$db){
  $new_qty=0;
  $db->where('id',$pro_id);
  $old_qty=$db->getValue('cylinders','empty_qty');

  $new_qty= $old_qty + $pro_qty;

  if ($new_qty > 0) {
    $update=array("empty_qty"=>$new_qty);
    $db->where('id',$pro_id);
    $db->update('cylinders',$update);
} else{
    $update=array("empty_qty"=>'0');
    $db->where('id',$pro_id);
    $db->update('cylinders',$update);
}
}
function removeEmptyStockQty($pro_id,$pro_qty,$db){
  $new_qty=0;
  $db->where('id',$pro_id);
  $old_qty=$db->getValue('cylinders','empty_qty');

  $new_qty= $old_qty - $pro_qty;

  if ($new_qty > 0) {
    $update=array("empty_qty"=>$new_qty);
    $db->where('id',$pro_id);
    $db->update('cylinders',$update);
} else{
    $update=array("empty_qty"=>'0');
    $db->where('id',$pro_id);
    $db->update('cylinders',$update);
}
}

function AddStockQty($pro_id,$pro_qty,$rate,$db){
  $new_qty=0;
  $db->where('id',$pro_id);
  $old_qty=$db->getValue('cylinders','qty');

  $new_qty= $old_qty +$pro_qty;

  if ($new_qty > 0) {
    $update=array("qty"=>$new_qty,"supplier_rate"=>$rate);
    $db->where('id',$pro_id);
    $db->update('cylinders',$update);
} else{
    $update=array("qty"=>'0');
    $db->where('id',$pro_id);
    $db->update('cylinders',$update);
}
}

function ReverseTheProductItemForPurchase($meta_id,$db){

  // get meta row value
  $db->where('id',$meta_id);
  $item=$db->getOne('purchase_invoice_items');

  // get qty of package
  $no_of_emptyitem=$item['empty_qty'];
  $no_of_item=$item['qty'];

  if ($no_of_item > 0 || $no_of_emptyitem > 0) {
      $db->where('id',$item['product_id']);
      $stock_qty=$db->getOne('cylinders');
      // available qty
      $set_qty=0;
      $pkg_pro_emptyqty=$stock_qty['empty_qty'];
      $pkg_pro_qty=$stock_qty['qty'];
      $new_emptyqty= $pkg_pro_emptyqty + $no_of_emptyitem;
      $new_qty= $pkg_pro_qty - $no_of_item;
      
      // new updated qty
      $update_arr=array("empty_qty" =>$new_emptyqty,"qty" =>$new_qty);
      $db->where('id',$item['product_id']);
      $db->update('cylinders',$update_arr);
      

      
  }
}

function ReverseTheProductItem($meta_id,$db){

  // get meta row value
  $db->where('id',$meta_id);
  $item=$db->getOne('invoice_items');

  // get qty of package
  $no_of_emptyitem=$item['empty_qty'];
  $no_of_item=$item['qty'];

  if ($no_of_item > 0 || $no_of_emptyitem > 0) {
      $db->where('id',$item['product_id']);
      $stock_qty=$db->getOne('cylinders');
      // available qty
      $set_qty=0;
      $pkg_pro_emptyqty=$stock_qty['empty_qty'];
      $pkg_pro_qty=$stock_qty['qty'];
      $new_emptyqty= $pkg_pro_emptyqty - $no_of_emptyitem;
      $new_qty= $pkg_pro_qty + $no_of_item;
      
      // new updated qty
      $update_arr=array("empty_qty" =>$new_emptyqty,"qty" =>$new_qty);
      $db->where('id',$item['product_id']);
      $db->update('cylinders',$update_arr);
      

      
  }
}

function ChangeInProductInvoice($oldEmptyQty,$newEmptyQty,$oldQty,$newQty,$metaID,$proId,$db){
   
   
  $tempQty=0;
  $updateQty=0;
  $db->where('id',$proId);
  $proEmptyQty=$db->getValue('cylinders','empty_qty');
  $tempEmptyQty=$proEmptyQty - $oldEmptyQty;

    $db->where('id',$proId);
  $proQty=$db->getValue('cylinders','qty');
  $tempQty=$proQty + $oldQty;


  $updateEmptyQty= $tempEmptyQty + $newEmptyQty;
  $updateQty= $tempQty - $newQty;


  $proArray=array( 'empty_qty' =>$updateEmptyQty ,'qty' =>$updateQty );
  // var_dump($proArray);die();
  $db->where('id',$proId);
  $db->update('cylinders',$proArray);
}

function ChangeInProductInvoiceForPurchase($oldEmptyQty,$newEmptyQty,$oldQty,$newQty,$metaID,$proId,$db){
   
   
  $tempQty=0;
  $updateQty=0;
  $db->where('id',$proId);
  $proEmptyQty=$db->getValue('cylinders','empty_qty');
  $tempEmptyQty=$proEmptyQty + $oldEmptyQty;
// var_dump($tempEmptyQty);die();
  $db->where('id',$proId);
  $proQty=$db->getValue('cylinders','qty');
  $tempQty=$proQty - $oldQty;


    $updateEmptyQty= $tempEmptyQty - $newEmptyQty;
    $updateQty= $tempQty + $newQty;


  $proArray=array( 'empty_qty' =>$updateEmptyQty ,'qty' =>$updateQty );
  // var_dump($proArray);die();
  $db->where('id',$proId);
  $db->update('cylinders',$proArray);
}

function ChangeInProductRateForPurchase($metaID,$proId,$rate,$db){
   
  $proArray=array( 'supplier_rate' =>$rate );
  $db->where('id',$proId);
  $db->update('cylinders',$proArray);
}

// Function to fetch existing customers
function fetchExistingCustomers() {
    global $conn;
    $sql = "SELECT customer_id, name FROM customers";
    $result = $conn->query($sql);

    $customers = [];
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $customers[] = $row;
        }
    }
    return $customers;
}

// Function to add a new customer
function addNewCustomer($name, $phone, $cnic) {
    global $conn;
    $customerId = uniqid(); // Generate a unique customer ID
    $stmt = $conn->prepare("INSERT INTO customers (customer_id, name, phone, cnic) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $customerId, $name, $phone, $cnic);
    $stmt->execute();
    $stmt->close();
    return $customerId;
}

// Function to record a transaction
function recordTransaction($customerId, $transactionType, $cylinderCount, $amount, $balance) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO transactions (customer_id, transaction_type, cylinder_count, amount, balance) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssidd", $customerId, $transactionType, $cylinderCount, $amount, $balance);
    $stmt->execute();
    $transactionId = $stmt->insert_id;
    $stmt->close();
    return $transactionId;
}

// Function to generate an invoice (simplified example)
function generateInvoice($transactionId) {
    // Generate the invoice based on the transaction details and return the invoice ID
    return $transactionId; // For simplicity, using the transaction ID as the invoice ID
}

function fetchCustomerById($customer_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM customers WHERE customer_id = ?");
    $stmt->bind_param("s", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_assoc();
}

function hasActiveRentalsOrPurchases($customer_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM transactions WHERE customer_id = ? AND (transaction_type = 'Rental' OR transaction_type = 'Purchase')");
    $stmt->bind_param("s", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['count'] > 0;
}

function fetchGasTypes() {
    global $conn;
    $sql = "SELECT gas_type, available_amount FROM cylinders";
    $result = $conn->query($sql);
    $gas_types = [];

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $gas_types[] = $row;
        }
    }

    return $gas_types;
}


function fetchPendingRefills() {
    global $conn;
    $sql = "
    SELECT c.customer_id, c.name, t.date
    FROM customers c
    JOIN transactions t ON c.customer_id = t.customer_id
    WHERE t.entity = 'Refill'
    AND t.date < NOW() - INTERVAL 1 MONTH";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

function fetchInactiveCustomers() {
    global $conn;

    // $sql = "SELECT c.id AS customer_id, 
    // c.name AS customer_name, 
    // c.phone AS customer_phone, 
    // c.cnic AS customer_cnic, 
    // MAX(i.date) AS last_invoice_date,
    // DATEDIFF(CURDATE(), MAX(i.date)) AS days_since_last_invoice
    // FROM customers c
    // INNER JOIN invoices i ON c.id = i.customer_id
    // WHERE i.date <= DATE_SUB(CURDATE(), INTERVAL 40 DAY)
    // AND c.deleted_at IS NULL AND i.deleted_at IS NULL
    // GROUP BY c.id, c.name, c.phone, c.cnic
    // ORDER BY last_invoice_date;";
    $sql = "SELECT 
                c.id AS customer_id, 
                c.name AS customer_name, 
                c.phone AS customer_phone, 
                c.cnic AS customer_cnic, 
                MAX(i.date) AS last_invoice_date,
                DATEDIFF(CURDATE(), MAX(i.date)) AS days_since_last_invoice
            FROM 
                customers c
            INNER JOIN 
                invoices i ON c.id = i.customer_id
            WHERE 
                c.deleted_at IS NULL 
                AND i.deleted_at IS NULL
            GROUP BY 
                c.id, c.name, c.phone, c.cnic
            HAVING 
                MAX(i.date) <= DATE_SUB(CURDATE(), INTERVAL 40 DAY)
            ORDER BY 
                last_invoice_date;";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

function fetchPendingPayments() {
    global $conn;
    $sql = "
    SELECT customer_id, name, balance as pending_balance
    FROM customers
    WHERE balance > 0";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

function fetchAllCustomers() {
    global $conn;
    $sql = "SELECT * FROM customers WHERE deleted_at is null";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}
function fetchAllVendors() {
    global $conn;
    $result = $conn->query("SELECT * FROM vendors where deleted = 0");
    return $result->fetch_all(MYSQLI_ASSOC);
}

function fetchVendorById($id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM vendors WHERE id = ? AND deleted = 0");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function deleteVendor($id) {
    global $conn;

    // Soft delete the vendor
    $stmt = $conn->prepare("UPDATE vendors SET deleted = 1 WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    // Soft delete the vendor's transaction history
    $stmt = $conn->prepare("UPDATE vendor_transaction_history SET deleted = 1 WHERE vendor_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
}


?>
