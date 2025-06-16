<?php
// Utility to reassign satellites from one user to another

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Satellite Reassignment Tool</h1>";

// Connect directly
$conn = new mysqli('localhost', 'root', '', 'satellite_tracker');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Process reassignment
if (isset($_POST['from_user']) && isset($_POST['to_user'])) {
    $fromUserId = (int)$_POST['from_user'];
    $toUserId = (int)$_POST['to_user'];
    
    // Validate both users exist
    $userCheck = $conn->prepare("SELECT id FROM users WHERE id IN (?, ?)");
    $userCheck->bind_param("ii", $fromUserId, $toUserId);
    $userCheck->execute();
    $userResult = $userCheck->get_result();
    
    if ($userResult->num_rows !== 2) {
        echo "<div style='padding: 15px; background-color: #f8d7da; color: #721c24; border-radius: 4px; margin-bottom: 15px;'>
            Error: One or both users do not exist.
        </div>";
    } else {
        // Count satellites to be reassigned
        $countStmt = $conn->prepare("SELECT COUNT(*) as count FROM satellites WHERE added_by = ?");
        $countStmt->bind_param("i", $fromUserId);
        $countStmt->execute();
        $countResult = $countStmt->get_result();
        $satelliteCount = $countResult->fetch_assoc()['count'];
        
        if ($satelliteCount === 0) {
            echo "<div style='padding: 15px; background-color: #fff3cd; color: #856404; border-radius: 4px; margin-bottom: 15px;'>
                No satellites found for user ID $fromUserId to reassign.
            </div>";
        } else {
            // Update satellites
            $updateStmt = $conn->prepare("UPDATE satellites SET added_by = ? WHERE added_by = ?");
            $updateStmt->bind_param("ii", $toUserId, $fromUserId);
            
            if ($updateStmt->execute()) {
                $affectedRows = $updateStmt->affected_rows;
                echo "<div style='padding: 15px; background-color: #d4edda; color: #155724; border-radius: 4px; margin-bottom: 15px;'>
                    Successfully reassigned $affectedRows satellites from user ID $fromUserId to user ID $toUserId.
                </div>";
            } else {
                echo "<div style='padding: 15px; background-color: #f8d7da; color: #721c24; border-radius: 4px; margin-bottom: 15px;'>
                    Error reassigning satellites: " . $conn->error . "
                </div>";
            }
        }
    }
}

// Get all users
$result = $conn->query("SELECT id, email, first_name, last_name FROM users ORDER BY id");

// Get satellite counts for each user
$satelliteCounts = [];
$countResult = $conn->query("SELECT added_by, COUNT(*) as count FROM satellites GROUP BY added_by");
if ($countResult) {
    while ($row = $countResult->fetch_assoc()) {
        $satelliteCounts[$row['added_by']] = $row['count'];
    }
}

if ($result && $result->num_rows > 0) {
    echo "<h2>Users</h2>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr>
        <th style='padding: 8px; text-align: left;'>ID</th>
        <th style='padding: 8px; text-align: left;'>Name</th>
        <th style='padding: 8px; text-align: left;'>Email</th>
        <th style='padding: 8px; text-align: left;'>Satellites Added</th>
    </tr>";
    
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
        $satelliteCount = isset($satelliteCounts[$row['id']]) ? $satelliteCounts[$row['id']] : 0;
        
        echo "<tr>";
        echo "<td style='padding: 8px;'>" . $row['id'] . "</td>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . "</td>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($row['email']) . "</td>";
        echo "<td style='padding: 8px;'>" . $satelliteCount . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Form for reassigning satellites
    echo "<h2>Reassign Satellites</h2>";
    echo "<form method='post' action='reassign_satellites.php'>";
    echo "<div style='margin-bottom: 10px;'>";
    echo "<label style='display: block; margin-bottom: 5px;'>From User:</label>";
    echo "<select name='from_user' required style='padding: 5px; width: 100%;'>";
    echo "<option value=''>Select User</option>";
    foreach ($users as $user) {
        $satelliteCount = isset($satelliteCounts[$user['id']]) ? $satelliteCounts[$user['id']] : 0;
        if ($satelliteCount > 0) {
            echo "<option value='" . $user['id'] . "'>" . 
                htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . 
                " (ID: " . $user['id'] . ", Satellites: " . $satelliteCount . ")</option>";
        }
    }
    echo "</select>";
    echo "</div>";
    
    echo "<div style='margin-bottom: 10px;'>";
    echo "<label style='display: block; margin-bottom: 5px;'>To User:</label>";
    echo "<select name='to_user' required style='padding: 5px; width: 100%;'>";
    echo "<option value=''>Select User</option>";
    foreach ($users as $user) {
        echo "<option value='" . $user['id'] . "'>" . 
            htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . 
            " (ID: " . $user['id'] . ")</option>";
    }
    echo "</select>";
    echo "</div>";
    
    echo "<button type='submit' style='padding: 8px 16px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;'>Reassign Satellites</button>";
    echo "</form>";
} else {
    echo "<p>No users found</p>";
}

// Navigation links
echo "<hr>";
echo "<p>Debug Tools:</p>";
echo "<ul>";
echo "<li><a href='/VBIS-main/public/debug-database'>Check Database Structure</a></li>";
echo "<li><a href='/VBIS-main/public/debug-session'>Check Session</a></li>";
echo "<li><a href='/VBIS-main/public/fix-passwords'>Fix User Passwords</a></li>";
echo "<li><a href='/VBIS-main/public'>Return to Application</a></li>";
echo "</ul>";
?> 