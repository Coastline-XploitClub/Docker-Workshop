<?php
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
define('UPLOAD_DIR', '/var/www/html/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// MongoDB Connection
function getMongoConnection() {
    try {
        $connectionString = "mongodb://";
        
        if (MONGO_USER && MONGO_PASS) {
            $connectionString .= MONGO_USER . ":" . MONGO_PASS . "@";
        }
        
        $connectionString .= MONGO_HOST . ":" . MONGO_PORT . "/" . MONGO_DB;
        
        $client = new MongoDB\Client($connectionString);
        return $client->selectDatabase(MONGO_DB);
    } catch (Exception $e) {
        error_log("MongoDB connection failed: " . $e->getMessage());
        return null;
    }
}

// Redis Connection
function getRedisConnection() {
    try {
        $redis = new Redis();
        $redis->connect(REDIS_HOST, REDIS_PORT);
        
        if (REDIS_PASS) {
            $redis->auth(REDIS_PASS);
        }
        
        return $redis;
    } catch (Exception $e) {
        error_log("Redis connection failed: " . $e->getMessage());
        return null;
    }
}

// Session Configuration
ini_set('session.save_handler', 'redis');
ini_set('session.save_path', 'tcp://' . REDIS_HOST . ':' . REDIS_PORT);
session_start();
?>