<?php
// Simple API test script
echo "=== API Test Script ===\n";
echo "Testing API endpoints to see actual responses\n\n";

// Test 1: Check if we can load the basic files
echo "1. Testing file includes:\n";
try {
    require_once 'config.php';
    echo "   ✓ config.php loaded\n";
} catch (Exception $e) {
    echo "   ✗ config.php failed: " . $e->getMessage() . "\n";
    exit(1);
}

try {
    require_once 'app.php';
    echo "   ✓ app.php loaded\n";
} catch (Exception $e) {
    echo "   ✗ app.php failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 2: Check TaskManager creation
echo "\n2. Testing TaskManager creation:\n";
try {
    $taskManager = new TaskManager();
    echo "   ✓ TaskManager created successfully\n";
} catch (Exception $e) {
    echo "   ✗ TaskManager creation failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 3: Test JSON encoding
echo "\n3. Testing JSON encoding:\n";
$testData = ['test' => 'success', 'timestamp' => time()];
$json = json_encode($testData);
if ($json === false) {
    echo "   ✗ JSON encoding failed: " . json_last_error_msg() . "\n";
} else {
    echo "   ✓ JSON encoding works: $json\n";
}

// Test 4: Test basic API response
echo "\n4. Testing basic API response structure:\n";
try {
    $tasks = $taskManager->getAllTasks();
    echo "   ✓ getAllTasks returned: " . count($tasks) . " tasks\n";
    
    $response = ['success' => true, 'data' => $tasks];
    $responseJson = json_encode($response);
    if ($responseJson === false) {
        echo "   ✗ Response JSON encoding failed: " . json_last_error_msg() . "\n";
    } else {
        echo "   ✓ Response JSON encoding works (length: " . strlen($responseJson) . ")\n";
    }
} catch (Exception $e) {
    echo "   ✗ API response test failed: " . $e->getMessage() . "\n";
}

// Test 5: Simulate actual API call
echo "\n5. Simulating actual API call:\n";
ob_start();
try {
    // Simulate the headers that would be sent
    header('Content-Type: application/json', false);
    
    // Call the function that the API would call
    $tasks = $taskManager->getAllTasks();
    $response = ['success' => true, 'data' => $tasks];
    echo json_encode($response);
    
    $output = ob_get_contents();
    ob_end_clean();
    
    echo "   ✓ Simulated API output length: " . strlen($output) . "\n";
    echo "   ✓ First 200 chars: " . substr($output, 0, 200) . "\n";
    
    // Test if it's valid JSON
    $decoded = json_decode($output, true);
    if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
        echo "   ✗ Output is not valid JSON: " . json_last_error_msg() . "\n";
    } else {
        echo "   ✓ Output is valid JSON\n";
    }
    
} catch (Exception $e) {
    ob_end_clean();
    echo "   ✗ Simulation failed: " . $e->getMessage() . "\n";
}

// Test 6: Check debug log
echo "\n6. Checking debug log:\n";
if (file_exists('./debug.log')) {
    $logContent = file_get_contents('./debug.log');
    $lines = explode("\n", $logContent);
    $recentLines = array_slice($lines, -10);
    echo "   Last 10 debug log entries:\n";
    foreach ($recentLines as $line) {
        if (!empty(trim($line))) {
            echo "   " . $line . "\n";
        }
    }
} else {
    echo "   No debug log found\n";
}

echo "\n=== Test Complete ===\n";
?> 