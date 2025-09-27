<?php
session_start();
require_once 'includes/auth.php';

echo "<h2>Session Debug Information</h2>";
echo "<div style='background: #f4f4f4; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<strong>Current Session Data:</strong><br>";
echo "User ID: " . ($_SESSION['user_id'] ?? 'NOT SET') . "<br>";
echo "Username: " . ($_SESSION['username'] ?? 'NOT SET') . "<br>";
echo "Role: " . ($_SESSION['role'] ?? 'NOT SET') . "<br>";
echo "First Name: " . ($_SESSION['first_name'] ?? 'NOT SET') . "<br>";
echo "Last Name: " . ($_SESSION['last_name'] ?? 'NOT SET') . "<br>";
echo "</div>";

echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<strong>Permission Checks:</strong><br>";
echo "isAdmin(): " . (isAdmin() ? 'TRUE' : 'FALSE') . "<br>";
echo "isLguAdmin(): " . (isLguAdmin() ? 'TRUE' : 'FALSE') . "<br>";
echo "hasRole(['admin']): " . (hasRole(['admin']) ? 'TRUE' : 'FALSE') . "<br>";
echo "hasRole(['lgu_admin']): " . (hasRole(['lgu_admin']) ? 'TRUE' : 'FALSE') . "<br>";
echo "</div>";

if (!isset($_SESSION['user_id'])) {
    echo "<div style='background: #ffebee; padding: 15px; border-radius: 5px; margin: 10px 0; color: #d32f2f;'>";
    echo "<strong>WARNING:</strong> No user session found. Please <a href='login.php'>login</a> first.";
    echo "</div>";
}
?>

<a href="dashboard.php" style="display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin-top: 10px;">Back to Dashboard</a>
<a href="disaster-types.php" style="display: inline-block; padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 4px; margin-top: 10px; margin-left: 10px;">Try Disaster Types</a>