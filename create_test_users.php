<?php
// Script to create test users for the VBIS system

// Database connection
$host = 'localhost';
$dbname = 'satellite_tracker';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connected to database successfully!<br>";
    
    // Create Administrator user
    $adminFirstName = 'Admin';
    $adminLastName = 'Example';
    $adminEmail = 'admin@example.com';
    $adminPassword = password_hash('adminexamplepass', PASSWORD_DEFAULT);
    
    // Create regular User
    $userFirstName = 'User';
    $userLastName = 'Example';
    $userEmail = 'user@example.com';
    $userPassword = password_hash('userexamplepass', PASSWORD_DEFAULT);
    
    // Check if users already exist
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
    
    // Check admin
    $stmt->execute(['email' => $adminEmail]);
    $adminExists = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check user
    $stmt->execute(['email' => $userEmail]);
    $userExists = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $adminId = null;
    $userId = null;
    
    // Create admin if doesn't exist
    if (!$adminExists) {
        $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password) VALUES (:first_name, :last_name, :email, :password)");
        $stmt->execute([
            'first_name' => $adminFirstName,
            'last_name' => $adminLastName,
            'email' => $adminEmail,
            'password' => $adminPassword
        ]);
        $adminId = $pdo->lastInsertId();
        echo "Admin user created with ID: $adminId<br>";
    } else {
        $adminId = $adminExists['id'];
        echo "Admin user already exists with ID: $adminId<br>";
        
        // Update admin user
        $stmt = $pdo->prepare("UPDATE users SET first_name = :first_name, last_name = :last_name, password = :password WHERE id = :id");
        $stmt->execute([
            'first_name' => $adminFirstName,
            'last_name' => $adminLastName,
            'password' => $adminPassword,
            'id' => $adminId
        ]);
        echo "Admin user updated<br>";
    }
    
    // Create regular user if doesn't exist
    if (!$userExists) {
        $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password) VALUES (:first_name, :last_name, :email, :password)");
        $stmt->execute([
            'first_name' => $userFirstName,
            'last_name' => $userLastName,
            'email' => $userEmail,
            'password' => $userPassword
        ]);
        $userId = $pdo->lastInsertId();
        echo "Regular user created with ID: $userId<br>";
    } else {
        $userId = $userExists['id'];
        echo "Regular user already exists with ID: $userId<br>";
        
        // Update regular user
        $stmt = $pdo->prepare("UPDATE users SET first_name = :first_name, last_name = :last_name, password = :password WHERE id = :id");
        $stmt->execute([
            'first_name' => $userFirstName,
            'last_name' => $userLastName,
            'password' => $userPassword,
            'id' => $userId
        ]);
        echo "Regular user updated<br>";
    }
    
    // Get role IDs
    $stmt = $pdo->prepare("SELECT id, name FROM roles");
    $stmt->execute();
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $adminRoleId = null;
    $userRoleId = null;
    
    foreach ($roles as $role) {
        if ($role['name'] === 'Administrator') {
            $adminRoleId = $role['id'];
        } else if ($role['name'] === 'Korisnik' || $role['name'] === 'User') {
            $userRoleId = $role['id'];
        }
    }
    
    echo "Admin role ID: $adminRoleId, User role ID: $userRoleId<br>";
    
    // Assign roles if they exist
    if ($adminRoleId && $adminId) {
        // Check if role assignment already exists
        $stmt = $pdo->prepare("SELECT id FROM user_roles WHERE id_user = :user_id AND id_role = :role_id");
        $stmt->execute(['user_id' => $adminId, 'role_id' => $adminRoleId]);
        $roleExists = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$roleExists) {
            $stmt = $pdo->prepare("INSERT INTO user_roles (id_user, id_role) VALUES (:user_id, :role_id)");
            $stmt->execute(['user_id' => $adminId, 'role_id' => $adminRoleId]);
            echo "Admin role assigned to admin user<br>";
        } else {
            echo "Admin role already assigned to admin user<br>";
        }
    }
    
    if ($userRoleId && $userId) {
        // Check if role assignment already exists
        $stmt = $pdo->prepare("SELECT id FROM user_roles WHERE id_user = :user_id AND id_role = :role_id");
        $stmt->execute(['user_id' => $userId, 'role_id' => $userRoleId]);
        $roleExists = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$roleExists) {
            $stmt = $pdo->prepare("INSERT INTO user_roles (id_user, id_role) VALUES (:user_id, :role_id)");
            $stmt->execute(['user_id' => $userId, 'role_id' => $userRoleId]);
            echo "User role assigned to regular user<br>";
        } else {
            echo "User role already assigned to regular user<br>";
        }
    }
    
    // Also assign user role to admin for testing
    if ($userRoleId && $adminId) {
        // Check if role assignment already exists
        $stmt = $pdo->prepare("SELECT id FROM user_roles WHERE id_user = :user_id AND id_role = :role_id");
        $stmt->execute(['user_id' => $adminId, 'role_id' => $userRoleId]);
        $roleExists = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$roleExists) {
            $stmt = $pdo->prepare("INSERT INTO user_roles (id_user, id_role) VALUES (:user_id, :role_id)");
            $stmt->execute(['user_id' => $adminId, 'role_id' => $userRoleId]);
            echo "User role also assigned to admin user<br>";
        } else {
            echo "User role already assigned to admin user<br>";
        }
    }
    
    echo "<br>Test users have been created and roles assigned successfully!<br>";
    echo "<br>You can now login with:<br>";
    echo "Admin: $adminEmail / adminexamplepass<br>";
    echo "User: $userEmail / userexamplepass<br>";
    
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?> 