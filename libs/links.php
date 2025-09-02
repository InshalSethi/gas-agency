<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<link rel="stylesheet" href="<?php echo baseurl('css/bootstrap.min.css'); ?>">
  <!-- <link rel="stylesheet" href="<?php //echo baseurl('css/jquery.dataTables.min.css'); ?>"> -->
  <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.24/css/jquery.dataTables.min.css">
  <link rel="stylesheet" type="text/css" href="<?php echo baseurl('css/styles.css') ?>">
  <!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" /> -->
  <link rel="stylesheet" type="text/css" href="<?php echo baseurl('css/font-awesome/css/all.min.css') ?>">
  <!-- Date Range Picker CSS -->
  <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
  <style>
    .bg-red{
      background-color: #f29d9d !important;
    }
    .alert-fill-success {
        background-color: #d4edda;
        color: #155724;
        border-color: #c3e6cb;
    }

    .alert-fill-danger {
        background-color: #f8d7da;
        color: #721c24;
        border-color: #f5c6cb;
    }
    .select2-container{
      width: 100% !important;
    }
    .select2-container .select2-search--inline .select2-search__field{
      height: 25px !important;
    }
  body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f1f1f1;

  }
  th{
    text-align: center;
  }
  td{
    padding: 0px!important;
    text-align: center;
  }
  .container {
    max-width: 1200px;
    margin: auto;
    padding: 20px;
  }

  h1 {
    font-size: 2.5rem;
    font-weight: bold;
    color: #343a40; /* Dark grey text */
    margin-bottom: 30px;
    text-align: center;
  }

  .card {
    background-color: #ffffff; /* White cards */
    border: none;
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    transition: transform 0.2s ease-in-out;
  }

  .card:hover {
    transform: translateY(-5px); /* Lift card on hover */
  }

  .card-header {
    background-color: #007bff; /* Blue header */
    color: #ffffff; /* White text */
    font-size: 1.8rem;
    padding: 15px;
    border-top-left-radius: 10px;
    border-top-right-radius: 10px;
    margin-bottom: 0;
  }

  .card-body {
    padding: 20px;
  }

  .card-title {
    font-size: 2.5rem;
    margin-bottom: 0;
  }

  .card-text {
    font-size: 1.6rem;
    color: #6c757d; /* Medium grey text */
  }

  .card a {
    color: #007bff; /* Blue links */
    text-decoration: none;
    transition: color 0.2s ease-in-out;
  }

  .card a:hover {
    color: #0056b3; /* Darker blue on hover */
    text-decoration: underline;
  }

  .summary-box {
    display: flex;
    justify-content: space-around;
    align-items: center;
    background-color: #f2f2f2;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
  }

  .summary-item {
    text-align: center;
/*    padding: 20px;*/
    border-radius: 10px;
    width: 100%;
/*    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);*/
    margin-bottom: 20px;
  }
  .summary-gas {
    text-align: center;
    padding: 10px;
    border-radius: 5px;
    width: 150px;
  }
  .summary-item h3 {
    margin: 0;
    font-size: 24px;
  }
  .summary-gas h4 {
    margin: 0;
    font-size: 22px;
  }
  .summary-item p {
    margin: 5px 0 0;
    font-size: 18px;
  }
  .total-cylinders {
    background-color: #4CAF50;
    color: white;
  }
  .empty-cylinders {
    background-color: red;
    color: white;
  }
  .empty-cylinders-blue {
    background-color: blue;
    color: white;
  }
  .empty-cylinders-red {
    background-color: red;
    color: white;
  }
  .cylinders-container {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    justify-content: center; /* This centers the items horizontally */
  }
  .cylinder-row {
    background-color: #20B2AA; /* Sea green background */
    color: white;
  }
  .cylinder-empty {
    background-color: red; /* Sea green background */
    color: white;
  }
  .cylinder-payment {
    background-color: blue; /* Sea green background */
    color: white;
  }
  .cylinder-total {
    background-color: green; /* Sea green background */
    color: white;
  }
  .cylinder-inactive {
    background-color: #37a383; /* Sea green background */
    color: white;
  }
  .cylinder-row td {
    padding: 7px;
    border: 1px solid #ddd;
  }
  a {
    text-decoration: none;
    color: inherit; /* Optional: Keep link text color consistent */
  }

  a:hover {
    text-decoration: underline; /* Optional: Underline on hover */
  }
  .navbar-brand {
    font-size: 1.5rem;
  }

  .card-deck .card {
    min-width: 1.8rem;
    margin-bottom: 20px;
  }

  .gas-item, .empty-gas-item {
    background-color: #e9ecef;
    padding: 15px;
    border-radius: 8px;
    text-align: center;
    width: 170px; /* Adjust the width as needed */
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    margin-bottom: 20px;
  }

  .gas-item h4, .empty-gas-item h4 {
    margin-bottom: 10px;
  }

  .mt-4 .btn {
    margin-right: 10px;
  }

  

  .sidebar {
    position: fixed;
    left: 0;
    top: 56px; /* Adjust to match the navbar height */
    bottom: 0;
    width: 250px;
    padding-top: 20px; /* Adjust as needed */
    background-color: #343a40; /* Dark background color */
    overflow-y: auto; /* Add scrollbar if content overflows */
    z-index: 1000; /* Ensure sidebar is above other content */
  }

  .admin-links {
    list-style-type: none;
    padding: 0;
    margin: 0;
  }

  .admin-link {
    padding: 15px 20px;
    color: #ffffff;
    text-decoration: none;
    display: block;
    transition: background-color 0.3s ease;
  }

  .admin-link:hover {
    background-color: #495057; /* Darker background color on hover */
    color: white;
    text-decoration: none;
  }

  .admin-link.active {
    background-color: #adb5bd; /* Active link background color */
  }

  .admin-link i {
    margin-right: 10px; /* Add space between icon and text */
  }

  .sidebar-header {
    padding: 10px 20px;
    color: #ffffff;
    text-align: center;
    background-color: #212529; /* Header background color */
  }

  .sidebar-logo {
    margin-bottom: 20px; /* Add space below the logo */
  }

  .sidebar-logo img {
    width: 80%; /* Adjust logo size as needed */
    display: block;
    margin: 0 auto; /* Center the logo */
  }

  .content {
    padding: 20px;
  }
  .gas-item {
    padding: 20px;
    border-radius: 8px;
    text-align: center;
    width: 160px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    margin: 1px; /* Add some margin for spacing between items */
  }

  .gas-item:nth-child(odd) {
    background-color: #ff9999; /* Light red */
  }

  .gas-item:nth-child(even) {
    background-color: #99ccff; /* Light blue */
  }

  .gas-item h4 {
    margin-bottom: 15px;
    font-size: 18px;
    color: #333; /* Dark text color for contrast */
  }

  .gas-item .btn {
    margin-top: 10px;
    margin-right: 5px;
  }
body {
    padding-top: 50px; /* Add padding to the top to accommodate the fixed navbar */
    padding-left: 250px; /* Add padding to the left to accommodate the sidebar */
  }
  @media (min-width: 302px) and (max-width: 1023px) {
  body {
    padding-top: 0px; /* Add padding to the top to accommodate the fixed navbar */
    padding-left: 0px; /* Add padding to the left to accommodate the sidebar */
  }
  .sidebar{
    display: none;
  }
  .dataTables_wrapper {
      overflow: scroll;
  }

  /* Mobile navbar toggler styles */
  .navbar-toggler.sidebar-open {
    background-color: white !important;
    border-color: #dee2e6;
  }

  .navbar-toggler.sidebar-open .navbar-toggler-icon {
    background-image: none;
    font-size: 1.5rem;
    font-weight: bold;
    color: #333;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
  }

  .navbar-toggler .navbar-toggler-icon {
    transition: all 0.3s ease;
  }
  .container-fluid{
    margin-top: 80px !important;
  }
}
</style>