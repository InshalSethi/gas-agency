<?php
require '../config/db.php';
require_once '../config/db_functions.php';
require '../config/auth.php';

if (!isAdmin()) {
    echo "Access Denied.";
    exit();
}

$message = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role_id = $_POST['role_id'];

    $data = [
        "username" => $username,
        "email" => $email,
        "password" => $password,
        "role_id" => $role_id
    ];

    if ($db->insert("users", $data)) {
        header("Location: user_management.php");
        exit();
    } else {
        $message = "Error: " . $db->getLastError();
    }
}

$roles = $db->get("roles");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add User</title>
    <?php include '../libs/links.php'; ?>
</head>
<body>
    <?php include '../libs/sidebar.php'; ?>

    <div class="container mt-4">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h4>Add New User</h4>
            </div>
            <div class="card-body">
                <?php if ($message): ?>
                    <div class="alert alert-danger"><?php echo $message; ?></div>
                <?php endif; ?>
                <form method="POST">
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Role</label>
                        <select name="role_id" class="form-control" required>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?php echo $role['id']; ?>"><?php echo $role['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">Save User</button>
                        <a href="user_management.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include('../libs/jslinks.php'); ?>
</body>
</html>
