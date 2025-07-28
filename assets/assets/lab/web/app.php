<?php
require_once 'config.php';

class TaskManager {
    private $db;
    private $redis;
    
    public function __construct() {
        debug_log("TaskManager constructor called");
        
        $this->db = getMongoConnection();
        $this->redis = getRedisConnection();
        
        debug_log("TaskManager initialized - MongoDB: " . ($this->db ? 'OK' : 'FAILED'));
        debug_log("TaskManager initialized - Redis: " . ($this->redis ? 'OK' : 'FAILED'));
        
        if (!$this->db) {
            debug_log("WARNING: TaskManager running without MongoDB connection");
        }
        if (!$this->redis) {
            debug_log("WARNING: TaskManager running without Redis connection");
        }
    }
    
    public function getAllTasks($useCache = true) {
        debug_log("getAllTasks called with useCache=$useCache");
        $cacheKey = 'tasks:all';
        
        if ($useCache && $this->redis) {
            debug_log("Checking Redis cache for tasks");
            try {
                $cached = $this->redis->get($cacheKey);
                if ($cached) {
                    debug_log("Tasks found in cache, returning cached data");
                    return json_decode($cached, true);
                }
                debug_log("No cached tasks found");
            } catch (Exception $e) {
                debug_log("Redis cache error: " . $e->getMessage());
            }
        }
        
        if (!$this->db) {
            debug_log("No MongoDB connection, returning empty array");
            return [];
        }
        
        try {
            debug_log("Querying MongoDB for tasks");
            $collection = $this->db->selectCollection('tasks');
            debug_log("Collection selected: tasks");
            
            $cursor = $collection->find([], ['sort' => ['created_at' => -1]]);
            debug_log("MongoDB query executed");
            
            $tasks = [];
            $count = 0;
            
            foreach ($cursor as $task) {
                $count++;
                $tasks[] = [
                    'id' => (string)$task['_id'],
                    'title' => $task['title'],
                    'description' => $task['description'],
                    'status' => $task['status'],
                    'priority' => $task['priority'],
                    'assigned_to' => $task['assigned_to'],
                    'created_at' => $task['created_at']->toDateTime()->format('Y-m-d H:i:s'),
                    'updated_at' => $task['updated_at']->toDateTime()->format('Y-m-d H:i:s')
                ];
            }
            
            debug_log("Processed $count tasks from MongoDB");
            
            // Cache for 5 minutes
            if ($this->redis) {
                try {
                    $this->redis->setex($cacheKey, 300, json_encode($tasks));
                    debug_log("Tasks cached in Redis");
                } catch (Exception $e) {
                    debug_log("Redis caching error: " . $e->getMessage());
                }
            }
            
            return $tasks;
        } catch (Exception $e) {
            debug_log("Error fetching tasks: " . $e->getMessage());
            debug_log("Exception type: " . get_class($e));
            return [];
        }
    }
    
    public function createTask($title, $description, $priority = 'medium', $assigned_to = 'unassigned') {
        debug_log("createTask called: title='$title', priority='$priority', assigned_to='$assigned_to'");
        
        if (!$this->db) {
            debug_log("Cannot create task - no MongoDB connection");
            return false;
        }
        
        try {
            $collection = $this->db->selectCollection('tasks');
            debug_log("Inserting new task into MongoDB");
            
            $taskData = [
                'title' => $title,
                'description' => $description,
                'status' => 'pending',
                'priority' => $priority,
                'assigned_to' => $assigned_to,
                'created_at' => new MongoDB\BSON\UTCDateTime(),
                'updated_at' => new MongoDB\BSON\UTCDateTime()
            ];
            debug_log("Task data prepared: " . json_encode($taskData, JSON_UNESCAPED_UNICODE));
            
            $result = $collection->insertOne($taskData);
            $taskId = (string)$result->getInsertedId();
            debug_log("Task inserted with ID: $taskId");
            
            // Clear cache
            if ($this->redis) {
                try {
                    $this->redis->del('tasks:all');
                    debug_log("Task cache cleared");
                } catch (Exception $e) {
                    debug_log("Redis cache clear error: " . $e->getMessage());
                }
            }
            
            return $taskId;
        } catch (Exception $e) {
            debug_log("Error creating task: " . $e->getMessage());
            debug_log("Exception type: " . get_class($e));
            return false;
        }
    }
    
    public function updateTaskStatus($taskId, $status) {
        debug_log("updateTaskStatus called: taskId='$taskId', status='$status'");
        
        if (!$this->db) {
            debug_log("Cannot update task - no MongoDB connection");
            return false;
        }
        
        try {
            $collection = $this->db->selectCollection('tasks');
            debug_log("Updating task in MongoDB");
            
            $result = $collection->updateOne(
                ['_id' => new MongoDB\BSON\ObjectId($taskId)],
                ['$set' => [
                    'status' => $status,
                    'updated_at' => new MongoDB\BSON\UTCDateTime()
                ]]
            );
            
            $modified = $result->getModifiedCount();
            debug_log("Task update result: $modified documents modified");
            
            // Clear cache
            if ($this->redis) {
                try {
                    $this->redis->del('tasks:all');
                    debug_log("Task cache cleared after update");
                } catch (Exception $e) {
                    debug_log("Redis cache clear error: " . $e->getMessage());
                }
            }
            
            return $modified > 0;
        } catch (Exception $e) {
            debug_log("Error updating task: " . $e->getMessage());
            debug_log("Exception type: " . get_class($e));
            return false;
        }
    }
    
    public function deleteTask($taskId) {
        debug_log("deleteTask called: taskId='$taskId'");
        
        if (!$this->db) {
            debug_log("Cannot delete task - no MongoDB connection");
            return false;
        }
        
        try {
            $collection = $this->db->selectCollection('tasks');
            debug_log("Deleting task from MongoDB");
            
            $result = $collection->deleteOne(['_id' => new MongoDB\BSON\ObjectId($taskId)]);
            $deleted = $result->getDeletedCount();
            debug_log("Task deletion result: $deleted documents deleted");
            
            // Clear cache
            if ($this->redis) {
                try {
                    $this->redis->del('tasks:all');
                    debug_log("Task cache cleared after deletion");
                } catch (Exception $e) {
                    debug_log("Redis cache clear error: " . $e->getMessage());
                }
            }
            
            return $deleted > 0;
        } catch (Exception $e) {
            debug_log("Error deleting task: " . $e->getMessage());
            debug_log("Exception type: " . get_class($e));
            return false;
        }
    }
    
    public function getUserStats($userId) {
        debug_log("getUserStats called for user: $userId");
        $cacheKey = "user:stats:$userId";
        
        if ($this->redis) {
            try {
                $cached = $this->redis->get($cacheKey);
                if ($cached) {
                    debug_log("User stats found in cache");
                    return json_decode($cached, true);
                }
                debug_log("No cached user stats found");
            } catch (Exception $e) {
                debug_log("Redis cache error for user stats: " . $e->getMessage());
            }
        }
        
        if (!$this->db) {
            debug_log("Cannot get user stats - no MongoDB connection");
            return [];
        }
        
        try {
            $collection = $this->db->selectCollection('tasks');
            debug_log("Calculating user stats from MongoDB");
            
            $stats = [
                'total' => $collection->countDocuments(['assigned_to' => $userId]),
                'pending' => $collection->countDocuments(['assigned_to' => $userId, 'status' => 'pending']),
                'in_progress' => $collection->countDocuments(['assigned_to' => $userId, 'status' => 'in_progress']),
                'completed' => $collection->countDocuments(['assigned_to' => $userId, 'status' => 'completed'])
            ];
            
            debug_log("User stats calculated: " . json_encode($stats));
            
            // Cache for 2 minutes
            if ($this->redis) {
                try {
                    $this->redis->setex($cacheKey, 120, json_encode($stats));
                    debug_log("User stats cached");
                } catch (Exception $e) {
                    debug_log("Redis caching error for user stats: " . $e->getMessage());
                }
            }
            
            return $stats;
        } catch (Exception $e) {
            debug_log("Error fetching user stats: " . $e->getMessage());
            debug_log("Exception type: " . get_class($e));
            return [];
        }
    }
    
    public function logUserActivity($userId, $action, $details = '') {
        debug_log("logUserActivity called: user='$userId', action='$action'");
        
        if (!$this->db) {
            debug_log("Cannot log activity - no MongoDB connection");
            return false;
        }
        
        try {
            $collection = $this->db->selectCollection('activity_logs');
            debug_log("Logging activity to MongoDB");
            
            $activityData = [
                'user_id' => $userId,
                'action' => $action,
                'details' => $details,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                'timestamp' => new MongoDB\BSON\UTCDateTime()
            ];
            
            $collection->insertOne($activityData);
            debug_log("Activity logged successfully");
            
            return true;
        } catch (Exception $e) {
            debug_log("Error logging activity: " . $e->getMessage());
            debug_log("Exception type: " . get_class($e));
            return false;
        }
    }
}
?>