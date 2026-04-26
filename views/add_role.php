<?php
require '../config/db.php';
require_once '../config/db_functions.php';
require '../config/auth.php';

if (!checkPermission('roles_add')) {
    echo "Access Denied. You do not have permission to add roles.";
    exit();
}

$message = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $perms = $_POST['permissions'] ?? [];

    $data = [
        "name" => $name,
        "description" => $description
    ];

    $roleId = $db->insert("roles", $data);
    if ($roleId) {
        foreach ($perms as $permId) {
            $db->insert("role_permissions", [
                "role_id" => $roleId,
                "permission_id" => $permId
            ]);
        }
        header("Location: role_management.php");
        exit();
    } else {
        $message = "Error: " . $db->getLastError();
    }
}

// Group permissions by module for the UI
$all_permissions = $db->get("permissions");
$grouped_perms = [];
foreach ($all_permissions as $p) {
    // Basic logic to group by slug prefix
    $parts = explode('_', $p['slug']);
    $module = $parts[0];
    if (count($parts) > 2) {
        // handle multi-word modules if any, e.g. sale_invoices
        if ($parts[0] . '_' . $parts[1] == 'sale_invoices' || $parts[0] . '_' . $parts[1] == 'purchase_invoices' || $parts[0] . '_' . $parts[1] == 'inactive_customers') {
            $module = $parts[0] . ' ' . $parts[1];
        }
    }
    
    $moduleName = ucwords(str_replace('_', ' ', $module));
    $grouped_perms[$moduleName][] = $p;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Role</title>
    <?php include '../libs/links.php'; ?>
    <style>
        .perm-module { margin-bottom: 20px; border: 1px solid #ddd; padding: 15px; border-radius: 5px; background: #fff; }
        .perm-module h5 { border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 15px; color: #333; }
        .perm-item { display: inline-block; margin-right: 20px; margin-bottom: 10px; }
    </style>
</head>
<body>
    <?php include '../libs/sidebar.php'; ?>

    <div class="container-fluid mt-4">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h4>Add New Role</h4>
            </div>
            <div class="card-body" style="background: #f4f7f6;">
                <?php if ($message): ?>
                    <div class="alert alert-danger"><?php echo $message; ?></div>
                <?php endif; ?>
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Role Name</label>
                                <input type="text" name="name" class="form-control" required placeholder="e.g. Manager">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Description</label>
                                <input type="text" name="description" class="form-control" placeholder="Brief role description">
                            </div>
                        </div>
                    </div>

                    <h4 class="mt-4 mb-3">Permissions</h4>
                    <div class="row">
                        <?php foreach ($grouped_perms as $module => $perms): ?>
                            <div class="col-md-6">
                                <div class="perm-module">
                                    <h5><?php echo $module; ?></h5>
                                    <?php foreach ($perms as $p): ?>
                                        <div class="perm-item">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input" id="perm_<?php echo $p['id']; ?>" name="permissions[]" value="<?php echo $p['id']; ?>">
                                                <label class="custom-control-label font-weight-normal" for="perm_<?php echo $p['id']; ?>">
                                                    <?php 
                                                        $action = explode('_', $p['slug']);
                                                        echo ucwords(end($action)); 
                                                    ?>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary btn-lg">Save Role</button>
                        <a href="role_management.php" class="btn btn-secondary btn-lg">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include('../libs/jslinks.php'); ?>
</body>
</html>
