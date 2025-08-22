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
  <ul class="admin-links">
    <li><a href="<?php echo baseurl('') ?>" class="admin-link">Dashboard</a></li>
    <li><a href="<?php echo baseurl('views/customer_management.php') ?>" class="admin-link">Customers</a></li>
    <li><a href="<?php echo baseurl('views/inactive_customers.php') ?>" class="admin-link">InActive Customers</a></li>
    <li><a href="<?php echo baseurl('views/vendor_management.php') ?>" class="admin-link">Vendors</a></li>
    <li><a href="<?php echo baseurl('views/cylinder_management.php') ?>" class="admin-link">Cylinders</a></li>
    <!-- <li><a href="<?php echo baseurl('views/empty_stock.php') ?>" class="admin-link">Empty Stock</a></li> -->
    <li><a href="<?php echo baseurl('views/sale-invoices.php') ?>" class="admin-link">Sale Invoices</a></li>
    <li><a href="<?php echo baseurl('views/purchase-invoices.php') ?>" class="admin-link">Purchase Invoices</a></li>
    <li><a href="<?php echo baseurl('views/transaction_management.php') ?>" class="admin-link">Transactions</a></li>
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