<nav class="navbar navbar-expand-lg navbar-light bg-light fixed-top">
  <a class="navbar-brand" href="#">Cylinder Management</a>
  <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
  </button>
</nav>
<div class="sidebar">
  <!-- <div class="sidebar-header">
    <div class="sidebar-logo">
      <img src="your-logo.png" alt="Logo"/>
    </div>
  </div> -->
  <?php
  // Get current page name
  $current_page = basename($_SERVER['PHP_SELF']);
  $current_dir = basename(dirname($_SERVER['PHP_SELF']));

  // Function to check if menu item should be active
  function isActive($page_name, $current_page, $current_dir = '') {
    if ($current_dir === 'views') {
      return $page_name === $current_page ? 'active' : '';
    } else {
      // For root directory (index.php)
      return ($page_name === 'index.php' && $current_page === 'index.php') ? 'active' : '';
    }
  }
  ?>
  <ul class="admin-links">
    <li><a href="<?php echo baseurl('') ?>" class="admin-link <?php echo isActive('index.php', $current_page, $current_dir); ?>">Dashboard</a></li>
    <li><a href="<?php echo baseurl('views/customer_management.php') ?>" class="admin-link <?php echo isActive('customer_management.php', $current_page, $current_dir); ?>">Customers</a></li>
    <li><a href="<?php echo baseurl('views/inactive_customers.php') ?>" class="admin-link <?php echo isActive('inactive_customers.php', $current_page, $current_dir); ?>">InActive Customers</a></li>
    <li><a href="<?php echo baseurl('views/vendor_management.php') ?>" class="admin-link <?php echo isActive('vendor_management.php', $current_page, $current_dir); ?>">Vendors</a></li>
    <li><a href="<?php echo baseurl('views/cylinder_management.php') ?>" class="admin-link <?php echo isActive('cylinder_management.php', $current_page, $current_dir); ?>">Cylinders</a></li>
    <!-- <li><a href="<?php echo baseurl('views/empty_stock.php') ?>" class="admin-link">Empty Stock</a></li> -->
    <li><a href="<?php echo baseurl('views/sale-invoices.php') ?>" class="admin-link <?php echo isActive('sale-invoices.php', $current_page, $current_dir); ?>">Sale Invoices</a></li>
    <li><a href="<?php echo baseurl('views/purchase-invoices.php') ?>" class="admin-link <?php echo isActive('purchase-invoices.php', $current_page, $current_dir); ?>">Purchase Invoices</a></li>
    <li><a href="<?php echo baseurl('views/transaction_management.php') ?>" class="admin-link <?php echo isActive('transaction_management.php', $current_page, $current_dir); ?>">Transactions</a></li>
    <li><a href="<?php echo baseurl('views/reports.php') ?>" class="admin-link <?php echo isActive('reports.php', $current_page, $current_dir); ?>">Reports</a></li>
    <!-- <li><a href="<?php echo baseurl('views/pending_payments.php') ?>" class="admin-link">Pending Payments</a></li>
    <li><a href="<?php echo baseurl('views/vendor_payment_management.php') ?>" class="admin-link">Venodr Payment</a></li>
    <li><a href="<?php echo baseurl('views/pending_refills.php') ?>" class="admin-link">Pending Refils</a></li>
    <li><a href="<?php echo baseurl('views/inactive_customers.php') ?>" class="admin-link">Inactive Customers</a></li> -->
    <li><a href="#" class="admin-link" data-toggle="modal" data-target="#profileModal" id="changePasswordModal">Change Password</a></li>

    <!-- Add more admin options here -->
  </ul>
  <div class="logout-btn">
    <a href="<?php echo baseurl('logout.php') ?>" class="btn btn-danger btn-block">Logout</a>
  </div>
</div>