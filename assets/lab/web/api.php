<?php
// Enable error reporting and debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include debug logging from config
require_once 'app.php';

// Debug function if not already defined
if (!function_exists('debug_log')) {
    function debug_log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] API DEBUG: $message\n";
        error_log($logMessage, 3, './debug.log');
    }
}

debug_log("=== API Request Started ===");
debug_log("Method: " . $_SERVER['REQUEST_METHOD']);
debug_log("URI: " . $_SERVER['REQUEST_URI']);
debug_log("Content-Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'not set'));

// Set JSON headers at the very beginning
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

debug_log("JSON headers set");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    debug_log("OPTIONS request - exiting");
    exit(0);
}

// Test if we can output JSON
function test_json_output($data) {
    $json = json_encode($data);
    if ($json === false) {
        debug_log("JSON encoding failed: " . json_last_error_msg());
        return false;
    }
    debug_log("JSON test successful: " . substr($json, 0, 100) . "...");
    return true;
}

// Test JSON output capability
test_json_output(['test' => 'success', 'timestamp' => time()]);

// Initialize TaskManager with debug info
debug_log("Initializing TaskManager...");
try {
    $taskManager = new TaskManager();
    debug_log("TaskManager created successfully");
} catch (Exception $e) {
    debug_log("TaskManager creation failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'System initialization failed: ' . $e->getMessage()]);
    exit(1);
}

$method = $_SERVER['REQUEST_METHOD'];
$path = $_SERVER['REQUEST_URI'];

// Get input data with debugging
$rawInput = file_get_contents('php://input');
debug_log("Raw input length: " . strlen($rawInput));
debug_log("Raw input preview: " . substr($rawInput, 0, 200));

$input = null;
if (!empty($rawInput)) {
    $input = json_decode($rawInput, true);
    if ($input === null && json_last_error() !== JSON_ERROR_NONE) {
        debug_log("JSON decode error: " . json_last_error_msg());
        echo json_encode(['success' => false, 'error' => 'Invalid JSON input: ' . json_last_error_msg()]);
        exit(1);
    }
    debug_log("Input decoded successfully: " . json_encode($input));
}

// Simple routing
$routes = [
    'GET /api/tasks' => 'getTasks',
    'POST /api/tasks' => 'createTask',
    'PUT /api/tasks/([^/]+)/status' => 'updateTaskStatus',
    'DELETE /api/tasks/([^/]+)' => 'deleteTask',
    'GET /api/users/([^/]+)/stats' => 'getUserStats',
    'POST /api/upload' => 'uploadFile'
];

function getTasks() {
    global $taskManager;
    debug_log("=== getTasks called ===");
    
    try {
        $tasks = $taskManager->getAllTasks();
        debug_log("Tasks retrieved: " . count($tasks) . " items");
        
        $response = ['success' => true, 'data' => $tasks];
        debug_log("Response prepared, outputting JSON...");
        echo json_encode($response);
        debug_log("JSON output complete");
    } catch (Exception $e) {
        debug_log("getTasks error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Failed to get tasks: ' . $e->getMessage()]);
    }
}

function createTask() {
    global $taskManager, $input;
    debug_log("=== createTask called ===");
    debug_log("Input data: " . json_encode($input));
    
    if (!isset($input['title']) || !isset($input['description'])) {
        debug_log("Missing required fields");
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Title and description required']);
        return;
    }
    
    try {
        $userId = $_SESSION['user_id'] ?? 'anonymous';
        debug_log("Creating task for user: $userId");
        
        $taskId = $taskManager->createTask(
            $input['title'],
            $input['description'],
            $input['priority'] ?? 'medium',
            $input['assigned_to'] ?? $userId
        );
        
        if ($taskId) {
            debug_log("Task created successfully with ID: $taskId");
            $taskManager->logUserActivity($userId, 'create_task', "Created task: {$input['title']}");
            echo json_encode(['success' => true, 'data' => ['id' => $taskId]]);
        } else {
            debug_log("Task creation failed");
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to create task']);
        }
    } catch (Exception $e) {
        debug_log("createTask exception: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Task creation error: ' . $e->getMessage()]);
    }
}

function updateTaskStatus($taskId) {
    global $taskManager, $input;
    debug_log("=== updateTaskStatus called ===");
    debug_log("Task ID: $taskId");
    debug_log("Input: " . json_encode($input));
    
    if (!isset($input['status'])) {
        debug_log("Status not provided");
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Status required']);
        return;
    }
    
    $validStatuses = ['pending', 'in_progress', 'completed', 'cancelled'];
    if (!in_array($input['status'], $validStatuses)) {
        debug_log("Invalid status: " . $input['status']);
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid status']);
        return;
    }
    
    try {
        $userId = $_SESSION['user_id'] ?? 'anonymous';
        $success = $taskManager->updateTaskStatus($taskId, $input['status']);
        
        if ($success) {
            debug_log("Task status updated successfully");
            $taskManager->logUserActivity($userId, 'update_task_status', "Updated task $taskId to {$input['status']}");
            echo json_encode(['success' => true]);
        } else {
            debug_log("Task status update failed");
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to update task']);
        }
    } catch (Exception $e) {
        debug_log("updateTaskStatus exception: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Update error: ' . $e->getMessage()]);
    }
}

function deleteTask($taskId) {
    global $taskManager;
    debug_log("=== deleteTask called ===");
    debug_log("Task ID: $taskId");
    
    try {
        $userId = $_SESSION['user_id'] ?? 'anonymous';
        $success = $taskManager->deleteTask($taskId);
        
        if ($success) {
            debug_log("Task deleted successfully");
            $taskManager->logUserActivity($userId, 'delete_task', "Deleted task $taskId");
            echo json_encode(['success' => true]);
        } else {
            debug_log("Task deletion failed");
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to delete task']);
        }
    } catch (Exception $e) {
        debug_log("deleteTask exception: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Delete error: ' . $e->getMessage()]);
    }
}

function getUserStats($userId) {
    global $taskManager;
    debug_log("=== getUserStats called ===");
    debug_log("User ID: $userId");
    
    try {
        $stats = $taskManager->getUserStats($userId);
        debug_log("Stats retrieved: " . json_encode($stats));
        echo json_encode(['success' => true, 'data' => $stats]);
    } catch (Exception $e) {
        debug_log("getUserStats exception: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Stats error: ' . $e->getMessage()]);
    }
}

function uploadFile() {
    debug_log("=== uploadFile called ===");
    debug_log("FILES: " . json_encode($_FILES));
    
    if (!isset($_FILES['file'])) {
        debug_log("No file uploaded");
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'No file uploaded']);
        return;
    }
    
    try {
        $file = $_FILES['file'];
        $uploadDir = UPLOAD_DIR;
        
        debug_log("Upload directory: $uploadDir");
        debug_log("File info: name={$file['name']}, size={$file['size']}, tmp_name={$file['tmp_name']}");
        
        if (!is_dir($uploadDir)) {
            debug_log("Creating upload directory");
            mkdir($uploadDir, 0755, true);
        }
        
        $filename = time() . '_' . basename($file['name']);
        $filepath = $uploadDir . $filename;
        debug_log("Target filepath: $filepath");
        
        if ($file['size'] > MAX_FILE_SIZE) {
            debug_log("File too large: " . $file['size'] . " > " . MAX_FILE_SIZE);
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'File too large']);
            return;
        }
        
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            debug_log("File uploaded successfully");
            $userId = $_SESSION['user_id'] ?? 'anonymous';
            global $taskManager;
            $taskManager->logUserActivity($userId, 'file_upload', "Uploaded file: $filename");
            
            echo json_encode(['success' => true, 'data' => ['filename' => $filename]]);
        } else {
            debug_log("File upload failed - move_uploaded_file returned false");
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Upload failed']);
        }
    } catch (Exception $e) {
        debug_log("uploadFile exception: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Upload error: ' . $e->getMessage()]);
    }
}

// Route matching
debug_log("=== Starting route matching ===");
debug_log("Available routes: " . json_encode(array_keys($routes)));

$matched = false;
foreach ($routes as $route => $handler) {
    list($routeMethod, $routePattern) = explode(' ', $route, 2);
    
    if ($method === $routeMethod) {
        $pattern = '#^' . $routePattern . '$#';
        debug_log("Testing route: $route against $path with pattern: $pattern");
        
        if (preg_match($pattern, $path, $matches)) {
            debug_log("Route matched! Calling handler: $handler");
            $matched = true;
            array_shift($matches); // Remove full match
            
            try {
                call_user_func_array($handler, $matches);
                debug_log("Handler completed successfully");
            } catch (Exception $e) {
                debug_log("Handler exception: " . $e->getMessage());
                echo json_encode(['success' => false, 'error' => 'Handler error: ' . $e->getMessage()]);
            }
            break;
        }
    }
}

if (!$matched) {
    debug_log("No route matched - returning 404");
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Route not found']);
}

debug_log("=== API Request Complete ===");
?>