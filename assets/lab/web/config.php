<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Debug log function
function debug_log($message) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] DEBUG: $message\n";
    error_log($logMessage, 3, './debug.log');
    
    // Only output to browser for HTML pages, not API responses
    if (php_sapi_name() !== 'cli' && 
        (!isset($_SERVER['REQUEST_URI']) || strpos($_SERVER['REQUEST_URI'], '/api/') !== 0)) {
        echo "<!-- DEBUG: $message -->\n";
    }
}

debug_log("=== Config.php loaded ===");

// Check if we're running via web server
debug_log("SAPI: " . php_sapi_name());
debug_log("Request URI: " . ($_SERVER['REQUEST_URI'] ?? 'N/A'));

// Database Configuration
define('MONGO_HOST', getenv('MONGO_HOST') ?: 'localhost');
define('MONGO_PORT', getenv('MONGO_PORT') ?: 27017);
define('MONGO_DB', getenv('MONGO_DB') ?: 'taskapp');
define('MONGO_USER', getenv('MONGO_USER') ?: '');
define('MONGO_PASS', getenv('MONGO_PASS') ?: '');

// Redis Configuration
define('REDIS_HOST', getenv('REDIS_HOST') ?: 'localhost');
define('REDIS_PORT', getenv('REDIS_PORT') ?: 6379);
define('REDIS_PASS', getenv('REDIS_PASS') ?: '');

// Application Configuration
define('APP_NAME', 'TaskManager Pro');
define('UPLOAD_DIR', './uploads_local/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

debug_log("Configuration loaded - MongoDB: " . MONGO_HOST . ":" . MONGO_PORT . "/" . MONGO_DB);
debug_log("Redis: " . REDIS_HOST . ":" . REDIS_PORT);

// Check for required PHP extensions
debug_log("Checking PHP extensions...");
$required_extensions = ['mongodb', 'redis'];
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        debug_log("Extension $ext: LOADED");
    } else {
        debug_log("Extension $ext: MISSING!");
    }
}

// Check if MongoDB Composer library is available
debug_log("Checking for MongoDB Composer library...");
if (class_exists('MongoDB\Client', false)) {
    debug_log("MongoDB\\Client class: ALREADY LOADED");
} else {
    // Try to load composer autoloader
    $autoloader_paths = [
        __DIR__ . '/vendor/autoload.php',
        __DIR__ . '/../vendor/autoload.php',
        __DIR__ . '/../../vendor/autoload.php',
        __DIR__ . '/../../../vendor/autoload.php'
    ];
    
    $autoloader_found = false;
    foreach ($autoloader_paths as $path) {
        if (file_exists($path)) {
            debug_log("Found Composer autoloader at: $path");
            require_once $path;
            $autoloader_found = true;
            break;
        }
    }
    
    if (!$autoloader_found) {
        debug_log("CRITICAL: Composer autoloader NOT FOUND! MongoDB library needs to be installed via Composer.");
        debug_log("Run: composer require mongodb/mongodb");
    } else {
        if (class_exists('MongoDB\Client')) {
            debug_log("MongoDB\\Client class: LOADED via Composer");
        } else {
            debug_log("ERROR: MongoDB\\Client class still not available after loading autoloader");
        }
    }
}

// MongoDB Connection
function getMongoConnection() {
    debug_log("=== Attempting MongoDB connection ===");
    
    // Check if MongoDB library is available
    if (!class_exists('MongoDB\Client')) {
        debug_log("ERROR: MongoDB\\Client class not available");
        return null;
    }
    
    try {
        $connectionString = "mongodb://";
        
        if (MONGO_USER && MONGO_PASS) {
            $connectionString .= MONGO_USER . ":" . MONGO_PASS . "@";
            debug_log("Using authenticated connection");
        } else {
            debug_log("Using non-authenticated connection");
        }
        
        $connectionString .= MONGO_HOST . ":" . MONGO_PORT . "/" . MONGO_DB;
        debug_log("Connection string: " . str_replace(MONGO_PASS, '***', $connectionString));
        
        $client = new MongoDB\Client($connectionString);
        $db = $client->selectDatabase(MONGO_DB);
        
        // Test the connection
        $result = $db->command(['ping' => 1]);
        debug_log("MongoDB ping successful: " . json_encode($result->toArray()));
        
        return $db;
    } catch (Exception $e) {
        debug_log("MongoDB connection failed: " . $e->getMessage());
        debug_log("Exception type: " . get_class($e));
        return null;
    }
}

// Redis Connection
function getRedisConnection() {
    debug_log("=== Attempting Redis connection ===");
    
    if (!extension_loaded('redis')) {
        debug_log("ERROR: Redis extension not loaded");
        return null;
    }
    
    try {
        $redis = new Redis();
        debug_log("Redis object created");
        
        $connected = $redis->connect(REDIS_HOST, REDIS_PORT);
        if (!$connected) {
            debug_log("Redis connection failed");
            return null;
        }
        debug_log("Redis connected to " . REDIS_HOST . ":" . REDIS_PORT);
        
        if (REDIS_PASS) {
            $auth_result = $redis->auth(REDIS_PASS);
            debug_log("Redis auth result: " . ($auth_result ? 'SUCCESS' : 'FAILED'));
        }
        
        // Test the connection
        $pong = $redis->ping();
        debug_log("Redis ping result: " . $pong);
        
        return $redis;
    } catch (Exception $e) {
        debug_log("Redis connection failed: " . $e->getMessage());
        debug_log("Exception type: " . get_class($e));
        return null;
    }
}

// Test connections on load
debug_log("=== Testing connections on config load ===");
$test_mongo = getMongoConnection();
$test_redis = getRedisConnection();

debug_log("MongoDB connection test: " . ($test_mongo ? 'SUCCESS' : 'FAILED'));
debug_log("Redis connection test: " . ($test_redis ? 'SUCCESS' : 'FAILED'));

// Session Configuration - only if Redis is working
if ($test_redis) {
    debug_log("Configuring Redis sessions...");
    ini_set('session.save_handler', 'redis');
    $redis_session_path = 'tcp://' . REDIS_HOST . ':' . REDIS_PORT;
    if (REDIS_PASS) {
        $redis_session_path .= '?auth=' . REDIS_PASS;
    }
    ini_set('session.save_path', $redis_session_path);
    debug_log("Redis session path: " . str_replace(REDIS_PASS, '***', $redis_session_path));
} else {
    debug_log("Redis not available, falling back to file sessions");
    ini_set('session.save_handler', 'files');
}

// Start session
try {
    session_start();
    debug_log("Session started successfully. Session ID: " . session_id());
} catch (Exception $e) {
    debug_log("Session start failed: " . $e->getMessage());
}

debug_log("=== Config.php initialization complete ===");
?>