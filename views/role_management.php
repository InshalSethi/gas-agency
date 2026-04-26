<?php
require '../config/db.php';
require_once '../config/db_functions.php';
require '../config/auth.php';

if (!checkPermission('roles_view')) {
    echo "Access Denied. You do not have permission to view roles.";
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Role Management</title>
    <?php include '../libs/links.php'; ?>
</head>
<body>
    <?php include '../libs/sidebar.php'; ?>

    <div class="container-fluid mt-4">
        <header><h4 class="text-white">Role Management</h4></header>
        <div class="table-container">
            <div class="mt-2 mb-2 text-right">
                <?php if (checkPermission('roles_add')): ?>
                <a class="btn btn-success" href="add_role.php"><i class="fa fa-plus"></i> Add Role</a>
                <?php endif; ?>
            </div>
            <table id="roleTable" class="table table-striped table-hover">
                <thead class="thead-dark">
                    <tr>
                        <th>ID</th>
                        <th>Role Name</th>
                        <th>Description</th>
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
            var roleTable = $('#roleTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "fetch_roles.php",
                    type: "GET"
                },
                columns: [
                    { data: "id" },
                    { data: "name" },
                    { data: "description" },
                    { data: "actions" }
                ]
            });
        });

        function deleteRole(id) {
            if (id == 1) {
                alert("Cannot delete Admin role.");
                return;
            }
            if (confirm('Are you sure you want to delete this role? This will affect all users assigned to it.')) {
                $.ajax({
                    url: 'delete_role.php',
                    type: 'POST',
                    data: { id: id },
                    success: function (response) {
                        var res = JSON.parse(response);
                        if(res.status === 'success') {
                            $('#roleTable').DataTable().ajax.reload();
                        } else {
                            alert(res.message);
                        }
                    }
                });
            }
        }
    </script>
</body>
</html>
