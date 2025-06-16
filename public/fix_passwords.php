<?php
// Script to fix user passwords

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>User Password Fixer</h1>";

// Connect directly
$conn = new mysqli('localhost', 'root', '', 'satellite_tracker');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get current URL
$current_url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
$current_url = strtok($current_url, '#'); // Remove any anchor

// Check if we're processing a fix
if (isset($_POST['user_id']) && isset($_POST['new_password'])) {
    $userId = (int)$_POST['user_id'];
    $password = $_POST['new_password'];
    
    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Update the user
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $hashedPassword, $userId);
    
    if ($stmt->execute()) {
        echo "<div style='padding: 15px; background-color: #d4edda; color: #155724; border-radius: 4px; margin-bottom: 15px;'>
            Password successfully updated for user ID $userId
        </div>";
    } else {
        echo "<div style='padding: 15px; background-color: #f8d7da; color: #721c24; border-radius: 4px; margin-bottom: 15px;'>
            Error updating password: " . $conn->error . "
        </div>";
    }
}

// Get all users
$result = $conn->query("SELECT id, email, first_name, last_name, password FROM users ORDER BY id");

if ($result && $result->num_rows > 0) {
    echo "<h2>Users</h2>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr>
        <th style='padding: 8px; text-align: left;'>ID</th>
        <th style='padding: 8px; text-align: left;'>Name</th>
        <th style='padding: 8px; text-align: left;'>Email</th>
        <th style='padding: 8px; text-align: left;'>Password Length</th>
        <th style='padding: 8px; text-align: left;'>Actions</th>
    </tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td style='padding: 8px;'>" . $row['id'] . "</td>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . "</td>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($row['email']) . "</td>";
        echo "<td style='padding: 8px;'>" . strlen($row['password']) . "</td>";
        echo "<td style='padding: 8px;'><a href='#fix-form' onclick='setUserId(" . $row['id'] . ")'>Fix Password</a></td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Form to fix a password with explicit full path
    echo "<h2 id='fix-form'>Fix Password</h2>";
    echo "<form method='post' action='fix_passwords.php'>";
    echo "<div style='margin-bottom: 10px;'>";
    echo "<label style='display: block; margin-bottom: 5px;'>User ID:</label>";
    echo "<input type='number' name='user_id' id='user_id' required style='padding: 5px; width: 100%;'>";
    echo "</div>";
    echo "<div style='margin-bottom: 10px;'>";
    echo "<label style='display: block; margin-bottom: 5px;'>New Password:</label>";
    echo "<input type='text' name='new_password' required style='padding: 5px; width: 100%;'>";
    echo "</div>";
    echo "<button type='submit' style='padding: 8px 16px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;'>Update Password</button>";
    echo "</form>";
    
    echo "<script>
    function setUserId(id) {
        document.getElementById('user_id').value = id;
    }
    </script>";
} else {
    echo "<p>No users found</p>";
}

// Add route to fix database
echo "<hr>";
echo "<p>Debug Tools:</p>";
echo "<ul>";
echo "<li><a href='/VBIS-main/public/debug-database'>Check Database Structure</a></li>";
echo "<li><a href='/VBIS-main/public/debug-session'>Check Session</a></li>";
echo "<li><a href='/VBIS-main/public'>Return to Application</a></li>";
echo "</ul>";
?> 