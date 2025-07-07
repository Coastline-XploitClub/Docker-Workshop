<?php
require_once 'app.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$taskManager = new TaskManager();
$method = $_SERVER['REQUEST_METHOD'];
$path = $_SERVER['REQUEST_URI'];
$input = json_decode(file_get_contents('php://input'), true);

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
    $tasks = $taskManager->getAllTasks();
    echo json_encode(['success' => true, 'data' => $tasks]);
}

function createTask() {
    global $taskManager, $input;
    
    if (!isset($input['title']) || !isset($input['description'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Title and description required']);
        return;
    }
    
    $userId = $_SESSION['user_id'] ?? 'anonymous';
    $taskId = $taskManager->createTask(
        $input['title'],
        $input['description'],
        $input['priority'] ?? 'medium',
        $input['assigned_to'] ?? $userId
    );
    
    if ($taskId) {
        $taskManager->logUserActivity($userId, 'create_task', "Created task: {$input['title']}");
        echo json_encode(['success' => true, 'data' => ['id' => $taskId]]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to create task']);
    }
}

function updateTaskStatus($taskId) {
    global $taskManager, $input;
    
    if (!isset($input['status'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Status required']);
        return;
    }
    
    $validStatuses = ['pending', 'in_progress', 'completed', 'cancelled'];
    if (!in_array($input['status'], $validStatuses)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid status']);
        return;
    }
    
    $userId = $_SESSION['user_id'] ?? 'anonymous';
    $success = $taskManager->updateTaskStatus($taskId, $input['status']);
    
    if ($success) {
        $taskManager->logUserActivity($userId, 'update_task_status', "Updated task $taskId to {$input['status']}");
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to update task']);
    }
}

function deleteTask($taskId) {
    global $taskManager;
    
    $userId = $_SESSION['user_id'] ?? 'anonymous';
    $success = $taskManager->deleteTask($taskId);
    
    if ($success) {
        $taskManager->logUserActivity($userId, 'delete_task', "Deleted task $taskId");
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to delete task']);
    }
}

function getUserStats($userId) {
    global $taskManager;
    
    $stats = $taskManager->getUserStats($userId);
    echo json_encode(['success' => true, 'data' => $stats]);
}

function uploadFile() {
    if (!isset($_FILES['file'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'No file uploaded']);
        return;
    }
    
    $file = $_FILES['file'];
    $uploadDir = UPLOAD_DIR;
    
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $filename = time() . '_' . basename($file['name']);
    $filepath = $uploadDir . $filename;
    
    if ($file['size'] > MAX_FILE_SIZE) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'File too large']);
        return;
    }
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        $userId = $_SESSION['user_id'] ?? 'anonymous';
        global $taskManager;
        $taskManager->logUserActivity($userId, 'file_upload', "Uploaded file: $filename");
        
        echo json_encode(['success' => true, 'data' => ['filename' => $filename]]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Upload failed']);
    }
}

// Route matching
$matched = false;
foreach ($routes as $route => $handler) {
    list($routeMethod, $routePattern) = explode(' ', $route, 2);
    
    if ($method === $routeMethod) {
        $pattern = '#^' . $routePattern . '$#';
        if (preg_match($pattern, $path, $matches)) {
            $matched = true;
            array_shift($matches); // Remove full match
            call_user_func_array($handler, $matches);
            break;
        }
    }
}

if (!$matched) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Route not found']);
}
?>