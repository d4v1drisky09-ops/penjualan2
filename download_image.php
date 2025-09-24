<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Allow access to product images for non-admin users
    // This is needed for the catalog page to display images
    // Uncomment the following lines if you want to restrict image access to admin only
    /*
    header('HTTP/1.0 403 Forbidden');
    exit;
    */
}

// Get image path from query parameter
if (!isset($_GET['path']) || empty($_GET['path'])) {
    header('HTTP/1.0 400 Bad Request');
    exit;
}

$image_path = $_GET['path'];

// Sanitize the path to prevent directory traversal
$image_path = str_replace('../', '', $image_path);

// Check if file exists
if (!file_exists($image_path)) {
    header('HTTP/1.0 404 Not Found');
    exit;
}

// Get file info
$file_info = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($file_info, $image_path);
finfo_close($file_info);

// Set appropriate headers
header('Content-Type: ' . $mime_type);
header('Content-Length: ' . filesize($image_path));
header('Cache-Control: public, max-age=86400'); // Cache for 1 day

// Output the file
readfile($image_path);
exit;
?>