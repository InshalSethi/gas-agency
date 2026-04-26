-- Role and Permission Tables
CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT
);

CREATE TABLE IF NOT EXISTS permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT
);

CREATE TABLE IF NOT EXISTS role_permissions (
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
);

-- Seed Roles
INSERT IGNORE INTO roles (id, name, description) VALUES (1, 'Admin', 'Full System Access');
INSERT IGNORE INTO roles (id, name, description) VALUES (2, 'Manager', 'Can manage most things but not users');
INSERT IGNORE INTO roles (id, name, description) VALUES (3, 'Staff', 'Basic data entry');

-- Seed Permissions
INSERT IGNORE INTO permissions (name, slug, description) VALUES ('Manage Users', 'manage_users', 'Can create, edit and delete users');
INSERT IGNORE INTO permissions (name, slug, description) VALUES ('Manage Roles', 'manage_roles', 'Can manage roles and permissions');
INSERT IGNORE INTO permissions (name, slug, description) VALUES ('View Dashboard', 'view_dashboard', 'Can view the main dashboard');
INSERT IGNORE INTO permissions (name, slug, description) VALUES ('Manage Cylinders', 'manage_cylinders', 'Can manage cylinders');
INSERT IGNORE INTO permissions (name, slug, description) VALUES ('Manage Customers', 'manage_customers', 'Can manage customers');
INSERT IGNORE INTO permissions (name, slug, description) VALUES ('Manage Vendors', 'manage_vendors', 'Can manage vendors');

-- Assign All Permissions to Admin
INSERT IGNORE INTO role_permissions (role_id, permission_id) 
SELECT 1, id FROM permissions;

-- Update users table (Standard MySQL 8.0 doesn't support IF NOT EXISTS in ALTER TABLE easily)
-- We will do this in separate commands to avoid failure of the whole script
-- But for a script, we can just hope they don't exist yet or use a procedure.
-- Procedure to add columns safely
DROP PROCEDURE IF EXISTS AddRBACColumns;
DELIMITER //
CREATE PROCEDURE AddRBACColumns()
BEGIN
    IF NOT EXISTS (SELECT * FROM information_schema.columns WHERE table_name = 'users' AND column_name = 'email' AND table_schema = DATABASE()) THEN
        ALTER TABLE users ADD COLUMN email VARCHAR(100) UNIQUE AFTER username;
    END IF;
    IF NOT EXISTS (SELECT * FROM information_schema.columns WHERE table_name = 'users' AND column_name = 'role_id' AND table_schema = DATABASE()) THEN
        ALTER TABLE users ADD COLUMN role_id INT AFTER password;
        ALTER TABLE users ADD CONSTRAINT fk_user_role FOREIGN KEY (role_id) REFERENCES roles(id);
    END IF;
END //
DELIMITER ;
CALL AddRBACColumns();
DROP PROCEDURE AddRBACColumns;

-- Create/Update Admin User
-- Password is 'password'
INSERT INTO users (username, email, password, role_id) 
VALUES ('admin', 'admin@admin.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1) 
ON DUPLICATE KEY UPDATE email = 'admin@admin.com', role_id = 1;
