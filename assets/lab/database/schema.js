// MongoDB Schema Setup Script
// Run this with: mongo taskapp schema.js

// Switch to taskapp database
use taskapp;

// Create tasks collection with indexes
db.tasks.createIndex({ "status": 1 });
db.tasks.createIndex({ "assigned_to": 1 });
db.tasks.createIndex({ "priority": 1 });
db.tasks.createIndex({ "created_at": -1 });

// Create activity_logs collection with indexes
db.activity_logs.createIndex({ "user_id": 1, "timestamp": -1 });
db.activity_logs.createIndex({ "action": 1 });
db.activity_logs.createIndex({ "timestamp": -1 });

// Create users collection with indexes
db.users.createIndex({ "username": 1 }, { unique: true });
db.users.createIndex({ "email": 1 }, { unique: true });

print("Database schema initialized successfully!");
print("Collections created: tasks, activity_logs, users");
print("Indexes created for optimal performance");