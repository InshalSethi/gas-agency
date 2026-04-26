<?php
require '../config/db.php';
require_once '../config/db_functions.php';
require '../config/auth.php';
if (!checkPermission('users_view')) {
    echo "Access Denied. You do not have permission to manage users.";
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>User Management</title>
    <?php include '../libs/links.php'; ?>
</head>
<body>
    <?php include '../libs/sidebar.php'; ?>

    <div class="container-fluid mt-4">
        <header><h4 class="text-white">User Management</h4></header>
        <div class="table-container">
            <div class="mt-2 mb-2 text-right">
                <?php if (checkPermission('users_add')): ?>
                <a class="btn btn-success" href="add_user.php"><i class="fa fa-plus"></i> Add User</a>
                <?php endif; ?>
            </div>
            <table id="userTable" class="table table-striped table-hover">
                <thead class="thead-dark">
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <?php include('../libs/jslinks.php'); ?>
    <script>
        $(document).ready(function () {
            var userTable = $('#userTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "fetch_users.php",
                    type: "GET"
                },
                columns: [
                    { data: "id" },
                    { data: "username" },
                    { data: "email" },
                    { data: "role_name" },
                    { data: "actions" }
                ]
            });
        });

        function deleteUser(id) {
            if (confirm('Are you sure you want to delete this user?')) {
                $.ajax({
                    url: 'delete_user.php',
                    type: 'POST',
                    data: { id: id },
                    success: function (response) {
                        $('#userTable').DataTable().ajax.reload();
                    }
                });
            }
        }
    </script>
</body>
</html>
