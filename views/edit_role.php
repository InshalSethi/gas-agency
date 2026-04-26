<?php
require '../config/db.php';
require_once '../config/db_functions.php';
require '../config/auth.php';

if (!checkPermission('roles_edit')) {
    echo "Access Denied. You do not have permission to edit roles.";
    exit();
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: role_management.php");
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

    $db->where("id", $id);
    if ($db->update("roles", $data)) {
        // Sync permissions: Delete old, Insert new
        $db->where("role_id", $id);
        $db->delete("role_permissions");

        foreach ($perms as $permId) {
            $db->insert("role_permissions", [
                "role_id" => $id,
                "permission_id" => $permId
            ]);
        }
        $message = "Role updated successfully!";
        header("Location: role_management.php");
        exit();
    } else {
        $message = "Error: " . $db->getLastError();
    }
}

$db->where("id", $id);
$role = $db->getOne("roles");

// Get existing permissions for this role
$db->where("role_id", $id);
$existing_perms = array_column($db->get("role_permissions", null, "permission_id"), 'permission_id');

// Group permissions for UI
$all_permissions = $db->get("permissions");
$grouped_perms = [];
foreach ($all_permissions as $p) {
    $parts = explode('_', $p['slug']);
    $module = $parts[0];
    if (count($parts) > 2) {
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
    <title>Edit Role</title>
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
            <div class="card-header bg-warning text-white">
                <h4>Edit Role: <?php echo $role['name']; ?></h4>
            </div>
            <div class="card-body" style="background: #f4f7f6;">
                <?php if ($message): ?>
                    <div class="alert alert-info"><?php echo $message; ?></div>
                <?php endif; ?>
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Role Name</label>
                                <input type="text" name="name" class="form-control" value="<?php echo $role['name']; ?>" required <?php echo ($id == 1 ? 'readonly' : ''); ?>>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Description</label>
                                <input type="text" name="description" class="form-control" value="<?php echo $role['description']; ?>">
                            </div>
                        </div>
                    </div>

                    <h4 class="mt-4 mb-3">Permissions</h4>
                    <?php if ($id == 1): ?>
                        <div class="alert alert-warning">Admin role always has all permissions.</div>
                    <?php endif; ?>

                    <div class="row">
                        <?php foreach ($grouped_perms as $module => $perms): ?>
                            <div class="col-md-6">
                                <div class="perm-module">
                                    <h5><?php echo $module; ?></h5>
                                    <?php foreach ($perms as $p): ?>
                                        <div class="perm-item">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input" id="perm_<?php echo $p['id']; ?>" name="permissions[]" value="<?php echo $p['id']; ?>" <?php echo (in_array($p['id'], $existing_perms) || $id == 1) ? 'checked' : ''; ?> <?php echo ($id == 1 ? 'disabled' : ''); ?>>
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
                        <?php if ($id == 1): ?>
                             <!-- hidden fields to ensure perms are sent if needed, though they aren't for admin -->
                        <?php endif; ?>
                    </div>

                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary btn-lg">Update Role</button>
                        <a href="role_management.php" class="btn btn-secondary btn-lg">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include('../libs/jslinks.php'); ?>
</body>
</html>
