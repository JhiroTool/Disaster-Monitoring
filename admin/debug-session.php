<?php
session_start();
require_once '../config/database.php';

// Debug information
echo "<h3>Session Debug Information:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h3>User Role Check:</h3>";
echo "Session Role: " . ($_SESSION['role'] ?? 'NOT SET') . "<br>";
echo "User ID: " . ($_SESSION['user_id'] ?? 'NOT SET') . "<br>";

// Check if we have the auth functions
if (file_exists('includes/auth.php')) {
    require_once 'includes/auth.php';
    echo "<br>Auth file loaded successfully<br>";
    
    // Test hasRole function
    if (function_exists('hasRole')) {
        $isAdmin = hasRole(['admin']);
        echo "hasRole(['admin']) result: " . ($isAdmin ? 'TRUE' : 'FALSE') . "<br>";
    } else {
        echo "hasRole function not found<br>";
    }
} else {
    echo "Auth file not found<br>";
}
?>