<?php
// Simple test script to verify image upload functionality
echo "<h2>Upload Test</h2>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_image'])) {
    echo "<h3>Upload Details:</h3>";
    echo "<pre>";
    print_r($_FILES['test_image']);
    echo "</pre>";
    
    if ($_FILES['test_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/emergency_images/';
        $filename = 'test_' . time() . '_' . $_FILES['test_image']['name'];
        $upload_path = $upload_dir . $filename;
        
        echo "<p>Attempting to upload to: " . $upload_path . "</p>";
        
        if (move_uploaded_file($_FILES['test_image']['tmp_name'], $upload_path)) {
            echo "<p style='color: green;'>✓ Upload successful!</p>";
            echo "<img src='$upload_path' style='max-width: 300px; border: 1px solid #ccc;'>";
        } else {
            echo "<p style='color: red;'>✗ Upload failed!</p>";
            echo "<p>Error: " . error_get_last()['message'] . "</p>";
        }
    } else {
        echo "<p style='color: red;'>Upload error code: " . $_FILES['test_image']['error'] . "</p>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Upload Test</title>
</head>
<body>
    <form method="POST" enctype="multipart/form-data">
        <h3>Test Image Upload</h3>
        <input type="file" name="test_image" accept="image/*" required>
        <br><br>
        <button type="submit">Upload Test Image</button>
    </form>
    
    <hr>
    
    <h3>Directory Permissions:</h3>
    <?php
    $upload_dir = 'uploads/emergency_images/';
    echo "<p>Directory exists: " . (is_dir($upload_dir) ? 'Yes' : 'No') . "</p>";
    echo "<p>Directory writable: " . (is_writable($upload_dir) ? 'Yes' : 'No') . "</p>";
    echo "<p>Directory permissions: " . substr(sprintf('%o', fileperms($upload_dir)), -4) . "</p>";
    
    echo "<h3>PHP Upload Settings:</h3>";
    echo "<p>file_uploads: " . (ini_get('file_uploads') ? 'On' : 'Off') . "</p>";
    echo "<p>upload_max_filesize: " . ini_get('upload_max_filesize') . "</p>";
    echo "<p>post_max_size: " . ini_get('post_max_size') . "</p>";
    echo "<p>upload_tmp_dir: " . ini_get('upload_tmp_dir') . "</p>";
    ?>
</body>
</html>