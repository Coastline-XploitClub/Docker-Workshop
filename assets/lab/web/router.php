<?php
// Router for PHP built-in web server
// This file handles routing for the TaskManager Pro application

$request_uri = $_SERVER['REQUEST_URI'];
$parsed_url = parse_url($request_uri);
$path = $parsed_url['path'];

// Debug logging for router
function router_debug($message) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] ROUTER: $message\n";
    error_log($logMessage, 3, './debug.log');
}

router_debug("=== Router called ===");
router_debug("Request URI: $request_uri");
router_debug("Parsed path: $path");
router_debug("Method: " . $_SERVER['REQUEST_METHOD']);

// Handle API routes
if (strpos($path, '/api/') === 0) {
    router_debug("API route detected, forwarding to api.php");
    require_once 'api.php';
    return true;
}

// Handle static files
$file_path = __DIR__ . $path;

// If path ends with /, try index.html
if ($path === '/' || $path === '') {
    $file_path = __DIR__ . '/index.html';
    router_debug("Root path, serving index.html");
}

// Check if it's a static file that exists
if (is_file($file_path)) {
    router_debug("Serving static file: $file_path");
    
    // Set appropriate content type
    $extension = pathinfo($file_path, PATHINFO_EXTENSION);
    $content_types = [
        'html' => 'text/html',
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'pdf' => 'application/pdf',
        'txt' => 'text/plain'
    ];
    
    if (isset($content_types[$extension])) {
        header('Content-Type: ' . $content_types[$extension]);
        router_debug("Content-Type set to: " . $content_types[$extension]);
    }
    
    // Output the file
    readfile($file_path);
    return true;
}

// If no file found, check if it might be an API route that should go to api.php
if (preg_match('#^/api/#', $path)) {
    router_debug("Unmatched API route, forwarding to api.php anyway");
    require_once 'api.php';
    return true;
}

// Default fallback - serve index.html for SPA-like behavior
router_debug("No match found, serving index.html as fallback");
header('Content-Type: text/html');
readfile(__DIR__ . '/index.html');
return true;
?> 