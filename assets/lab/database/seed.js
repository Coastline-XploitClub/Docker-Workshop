// MongoDB Seed Data Script - Production-like Data
// Run this with: mongo taskapp seed.js

use taskapp;

// Clear existing data (for fresh setup)
db.tasks.deleteMany({});
db.activity_logs.deleteMany({});
db.users.deleteMany({});

// Insert users (production team members)
db.users.insertMany([
    {
        username: "admin",
        full_name: "System Administrator",
        email: "admin@company.com",
        role: "admin",
        created_at: new Date("2023-01-15T09:00:00Z"),
        last_login: new Date("2024-01-15T08:30:00Z")
    },
    {
        username: "developer",
        full_name: "John Smith",
        email: "john.smith@company.com",
        role: "developer",
        created_at: new Date("2023-02-01T10:15:00Z"),
        last_login: new Date("2024-01-15T09:45:00Z")
    },
    {
        username: "analyst",
        full_name: "Sarah Johnson",
        email: "sarah.johnson@company.com",
        role: "security_analyst",
        created_at: new Date("2023-03-10T11:00:00Z"),
        last_login: new Date("2024-01-15T07:20:00Z")
    },
    {
        username: "manager",
        full_name: "Michael Brown",
        email: "michael.brown@company.com",
        role: "manager",
        created_at: new Date("2023-01-01T08:00:00Z"),
        last_login: new Date("2024-01-14T16:30:00Z")
    }
]);

// Insert production-like tasks
db.tasks.insertMany([
    {
        title: "Update SSL certificates for web servers",
        description: "Renew SSL certificates for all production web servers. Current certificates expire in 30 days. Include load balancer configuration update.",
        status: "pending",
        priority: "high",
        assigned_to: "admin",
        created_at: new Date("2024-01-10T10:00:00Z"),
        updated_at: new Date("2024-01-10T10:00:00Z")
    },
    {
        title: "Security audit of user authentication system",
        description: "Conduct comprehensive security review of the authentication system. Check for vulnerabilities, review access logs, and validate session management.",
        status: "in_progress",
        priority: "critical",
        assigned_to: "analyst",
        created_at: new Date("2024-01-08T09:15:00Z"),
        updated_at: new Date("2024-01-12T14:30:00Z")
    },
    {
        title: "Database performance optimization",
        description: "Analyze slow queries and optimize database performance. MongoDB is showing increased response times during peak hours.",
        status: "in_progress",
        priority: "high",
        assigned_to: "developer",
        created_at: new Date("2024-01-05T11:20:00Z"),
        updated_at: new Date("2024-01-13T16:45:00Z")
    },
    {
        title: "Backup verification and disaster recovery test",
        description: "Verify all backup systems are functioning correctly. Perform disaster recovery test on staging environment.",
        status: "pending",
        priority: "medium",
        assigned_to: "admin",
        created_at: new Date("2024-01-12T13:00:00Z"),
        updated_at: new Date("2024-01-12T13:00:00Z")
    },
    {
        title: "Implement rate limiting on API endpoints",
        description: "Add rate limiting to prevent API abuse. Configure Redis-based rate limiting with appropriate thresholds for different user types.",
        status: "pending",
        priority: "medium",
        assigned_to: "developer",
        created_at: new Date("2024-01-11T15:30:00Z"),
        updated_at: new Date("2024-01-11T15:30:00Z")
    },
    {
        title: "Monthly security compliance report",
        description: "Generate and review monthly security compliance report. Include vulnerability scan results, access reviews, and policy compliance status.",
        status: "completed",
        priority: "low",
        assigned_to: "analyst",
        created_at: new Date("2024-01-01T09:00:00Z"),
        updated_at: new Date("2024-01-07T17:00:00Z")
    },
    {
        title: "Update firewall rules for new application",
        description: "Configure firewall rules for the new customer portal application. Ensure proper segmentation and access controls.",
        status: "completed",
        priority: "medium",
        assigned_to: "admin",
        created_at: new Date("2023-12-28T10:00:00Z"),
        updated_at: new Date("2024-01-03T14:20:00Z")
    },
    {
        title: "Code review for payment processing module",
        description: "Comprehensive security code review for the new payment processing module. Focus on PCI compliance and data protection.",
        status: "in_progress",
        priority: "critical",
        assigned_to: "developer",
        created_at: new Date("2024-01-09T08:00:00Z"),
        updated_at: new Date("2024-01-14T11:15:00Z")
    },
    {
        title: "Incident response drill planning",
        description: "Plan and schedule quarterly incident response drill. Coordinate with all team members and document procedures.",
        status: "pending",
        priority: "low",
        assigned_to: "manager",
        created_at: new Date("2024-01-13T14:00:00Z"),
        updated_at: new Date("2024-01-13T14:00:00Z")
    },
    {
        title: "Patch management for critical vulnerabilities",
        description: "Apply security patches for recently disclosed vulnerabilities in web framework. Schedule maintenance window and test in staging.",
        status: "pending",
        priority: "critical",
        assigned_to: "admin",
        created_at: new Date("2024-01-14T16:00:00Z"),
        updated_at: new Date("2024-01-14T16:00:00Z")
    }
]);

// Insert activity logs (production activity simulation)
db.activity_logs.insertMany([
    {
        user_id: "admin",
        action: "login",
        details: "Successful login from office network",
        ip_address: "192.168.1.100",
        user_agent: "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36",
        timestamp: new Date("2024-01-15T08:30:00Z")
    },
    {
        user_id: "analyst",
        action: "update_task_status",
        details: "Updated task 'Security audit of user authentication system' to in_progress",
        ip_address: "192.168.1.105",
        user_agent: "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36",
        timestamp: new Date("2024-01-12T14:30:00Z")
    },
    {
        user_id: "developer",
        action: "create_task",
        details: "Created task: Database performance optimization",
        ip_address: "192.168.1.102",
        user_agent: "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36",
        timestamp: new Date("2024-01-05T11:20:00Z")
    },
    {
        user_id: "admin",
        action: "file_upload",
        details: "Uploaded file: ssl_certificate_renewal_plan.pdf",
        ip_address: "192.168.1.100",
        user_agent: "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36",
        timestamp: new Date("2024-01-10T10:15:00Z")
    },
    {
        user_id: "manager",
        action: "login",
        details: "Login from VPN connection",
        ip_address: "10.0.1.50",
        user_agent: "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36",
        timestamp: new Date("2024-01-14T16:30:00Z")
    }
]);

print("Seed data inserted successfully!");
print("Users: " + db.users.countDocuments());
print("Tasks: " + db.tasks.countDocuments());
print("Activity Logs: " + db.activity_logs.countDocuments());
print("");
print("Production-like data created:");
print("- 4 team members (admin, developer, analyst, manager)");
print("- 10 realistic tasks with various priorities and statuses");
print("- 5 activity log entries simulating recent system usage");
print("");
print("IMPORTANT: This represents a live production system!");
print("All data must be preserved during Docker migration.");