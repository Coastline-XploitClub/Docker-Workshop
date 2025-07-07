<?php
require_once 'config.php';

class TaskManager {
    private $db;
    private $redis;
    
    public function __construct() {
        $this->db = getMongoConnection();
        $this->redis = getRedisConnection();
    }
    
    public function getAllTasks($useCache = true) {
        $cacheKey = 'tasks:all';
        
        if ($useCache && $this->redis) {
            $cached = $this->redis->get($cacheKey);
            if ($cached) {
                return json_decode($cached, true);
            }
        }
        
        if (!$this->db) {
            return [];
        }
        
        try {
            $collection = $this->db->selectCollection('tasks');
            $cursor = $collection->find([], ['sort' => ['created_at' => -1]]);
            $tasks = [];
            
            foreach ($cursor as $task) {
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
            
            // Cache for 5 minutes
            if ($this->redis) {
                $this->redis->setex($cacheKey, 300, json_encode($tasks));
            }
            
            return $tasks;
        } catch (Exception $e) {
            error_log("Error fetching tasks: " . $e->getMessage());
            return [];
        }
    }
    
    public function createTask($title, $description, $priority = 'medium', $assigned_to = 'unassigned') {
        if (!$this->db) {
            return false;
        }
        
        try {
            $collection = $this->db->selectCollection('tasks');
            $result = $collection->insertOne([
                'title' => $title,
                'description' => $description,
                'status' => 'pending',
                'priority' => $priority,
                'assigned_to' => $assigned_to,
                'created_at' => new MongoDB\BSON\UTCDateTime(),
                'updated_at' => new MongoDB\BSON\UTCDateTime()
            ]);
            
            // Clear cache
            if ($this->redis) {
                $this->redis->del('tasks:all');
            }
            
            return (string)$result->getInsertedId();
        } catch (Exception $e) {
            error_log("Error creating task: " . $e->getMessage());
            return false;
        }
    }
    
    public function updateTaskStatus($taskId, $status) {
        if (!$this->db) {
            return false;
        }
        
        try {
            $collection = $this->db->selectCollection('tasks');
            $result = $collection->updateOne(
                ['_id' => new MongoDB\BSON\ObjectId($taskId)],
                ['$set' => [
                    'status' => $status,
                    'updated_at' => new MongoDB\BSON\UTCDateTime()
                ]]
            );
            
            // Clear cache
            if ($this->redis) {
                $this->redis->del('tasks:all');
            }
            
            return $result->getModifiedCount() > 0;
        } catch (Exception $e) {
            error_log("Error updating task: " . $e->getMessage());
            return false;
        }
    }
    
    public function deleteTask($taskId) {
        if (!$this->db) {
            return false;
        }
        
        try {
            $collection = $this->db->selectCollection('tasks');
            $result = $collection->deleteOne(['_id' => new MongoDB\BSON\ObjectId($taskId)]);
            
            // Clear cache
            if ($this->redis) {
                $this->redis->del('tasks:all');
            }
            
            return $result->getDeletedCount() > 0;
        } catch (Exception $e) {
            error_log("Error deleting task: " . $e->getMessage());
            return false;
        }
    }
    
    public function getUserStats($userId) {
        $cacheKey = "user:stats:$userId";
        
        if ($this->redis) {
            $cached = $this->redis->get($cacheKey);
            if ($cached) {
                return json_decode($cached, true);
            }
        }
        
        if (!$this->db) {
            return [];
        }
        
        try {
            $collection = $this->db->selectCollection('tasks');
            $stats = [
                'total' => $collection->countDocuments(['assigned_to' => $userId]),
                'pending' => $collection->countDocuments(['assigned_to' => $userId, 'status' => 'pending']),
                'in_progress' => $collection->countDocuments(['assigned_to' => $userId, 'status' => 'in_progress']),
                'completed' => $collection->countDocuments(['assigned_to' => $userId, 'status' => 'completed'])
            ];
            
            // Cache for 2 minutes
            if ($this->redis) {
                $this->redis->setex($cacheKey, 120, json_encode($stats));
            }
            
            return $stats;
        } catch (Exception $e) {
            error_log("Error fetching user stats: " . $e->getMessage());
            return [];
        }
    }
    
    public function logUserActivity($userId, $action, $details = '') {
        if (!$this->db) {
            return false;
        }
        
        try {
            $collection = $this->db->selectCollection('activity_logs');
            $collection->insertOne([
                'user_id' => $userId,
                'action' => $action,
                'details' => $details,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                'timestamp' => new MongoDB\BSON\UTCDateTime()
            ]);
            
            return true;
        } catch (Exception $e) {
            error_log("Error logging activity: " . $e->getMessage());
            return false;
        }
    }
}
?>