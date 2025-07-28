class TaskManagerApp {
    constructor() {
        this.currentFilter = 'all';
        this.tasks = [];
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadTasks();
        this.loadUserStats();
        this.updateLastUpdated();
        
        // Simulate production environment with periodic updates
        setInterval(() => {
            this.loadTasks(false); // Silent refresh
            this.loadUserStats();
        }, 30000); // Every 30 seconds
    }

    bindEvents() {
        // New task form
        document.getElementById('new-task-form').addEventListener('submit', (e) => {
            e.preventDefault();
            this.createTask();
        });

        // File upload form  
        document.getElementById('upload-form').addEventListener('submit', (e) => {
            e.preventDefault();
            this.uploadFile();
        });

        // Filter buttons
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.setFilter(e.target.dataset.status);
            });
        });
    }

    async createTask() {
        const form = document.getElementById('new-task-form');
        const formData = new FormData(form);
        
        const taskData = {
            title: formData.get('title'),
            description: formData.get('description'),
            priority: formData.get('priority'),
            assigned_to: formData.get('assigned_to')
        };

        try {
            const response = await fetch('/api/tasks', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(taskData)
            });

            const result = await response.json();
            
            if (result.success) {
                form.reset();
                this.loadTasks();
                this.loadUserStats();
                this.showNotification('Task created successfully!', 'success');
            } else {
                this.showNotification('Failed to create task: ' + result.error, 'error');
            }
        } catch (error) {
            this.showNotification('Network error: ' + error.message, 'error');
        }
    }

    async loadTasks(showLoading = true) {
        if (showLoading) {
            document.getElementById('tasks-container').innerHTML = '<div class="loading">Loading tasks...</div>';
        }

        try {
            const response = await fetch('/api/tasks');
            const result = await response.json();
            
            if (result.success) {
                this.tasks = result.data;
                this.renderTasks();
            } else {
                document.getElementById('tasks-container').innerHTML = '<div class="error">Failed to load tasks</div>';
            }
        } catch (error) {
            document.getElementById('tasks-container').innerHTML = '<div class="error">Network error loading tasks</div>';
        }
    }

    async updateTaskStatus(taskId, status) {
        try {
            const response = await fetch(`/api/tasks/${taskId}/status`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ status })
            });

            const result = await response.json();
            
            if (result.success) {
                this.loadTasks();
                this.loadUserStats();
                this.showNotification('Task status updated!', 'success');
            } else {
                this.showNotification('Failed to update task: ' + result.error, 'error');
            }
        } catch (error) {
            this.showNotification('Network error: ' + error.message, 'error');
        }
    }

    async deleteTask(taskId) {
        if (!confirm('Are you sure you want to delete this task?')) {
            return;
        }

        try {
            const response = await fetch(`/api/tasks/${taskId}`, {
                method: 'DELETE'
            });

            const result = await response.json();
            
            if (result.success) {
                this.loadTasks();
                this.loadUserStats();
                this.showNotification('Task deleted!', 'success');
            } else {
                this.showNotification('Failed to delete task: ' + result.error, 'error');
            }
        } catch (error) {
            this.showNotification('Network error: ' + error.message, 'error');
        }
    }

    async loadUserStats() {
        const userId = document.getElementById('current-user').textContent;
        
        try {
            const response = await fetch(`/api/users/${userId}/stats`);
            const result = await response.json();
            
            if (result.success) {
                const stats = result.data;
                document.getElementById('total-tasks').textContent = stats.total || 0;
                document.getElementById('pending-tasks').textContent = stats.pending || 0;
                document.getElementById('progress-tasks').textContent = stats.in_progress || 0;
                document.getElementById('completed-tasks').textContent = stats.completed || 0;
            }
        } catch (error) {
            console.error('Failed to load user stats:', error);
        }
    }

    async uploadFile() {
        const form = document.getElementById('upload-form');
        const formData = new FormData(form);
        const statusDiv = document.getElementById('upload-status');

        try {
            const response = await fetch('/api/upload', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            
            if (result.success) {
                statusDiv.className = 'success';
                statusDiv.textContent = `File uploaded successfully: ${result.data.filename}`;
                statusDiv.style.display = 'block';
                form.reset();
            } else {
                statusDiv.className = 'error';
                statusDiv.textContent = 'Upload failed: ' + result.error;
                statusDiv.style.display = 'block';
            }
        } catch (error) {
            statusDiv.className = 'error';
            statusDiv.textContent = 'Upload error: ' + error.message;
            statusDiv.style.display = 'block';
        }

        setTimeout(() => {
            statusDiv.style.display = 'none';
        }, 5000);
    }

    setFilter(status) {
        this.currentFilter = status;
        
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        
        document.querySelector(`[data-status="${status}"]`).classList.add('active');
        
        this.renderTasks();
    }

    renderTasks() {
        const container = document.getElementById('tasks-container');
        
        let filteredTasks = this.tasks;
        if (this.currentFilter !== 'all') {
            filteredTasks = this.tasks.filter(task => task.status === this.currentFilter);
        }

        if (filteredTasks.length === 0) {
            container.innerHTML = '<div class="loading">No tasks found</div>';
            return;
        }

        const tasksHtml = filteredTasks.map(task => `
            <div class="task-item">
                <div class="task-header">
                    <div class="task-title">${this.escapeHtml(task.title)}</div>
                    <div class="task-meta">
                        <span class="priority-badge priority-${task.priority}">${task.priority}</span>
                        <span class="status-badge status-${task.status}">${task.status.replace('_', ' ')}</span>
                    </div>
                </div>
                <div class="task-description">${this.escapeHtml(task.description)}</div>
                <div class="task-info">
                    <small>Assigned to: ${task.assigned_to} | Created: ${task.created_at}</small>
                </div>
                <div class="task-actions">
                    ${task.status === 'pending' ? `<button class="btn-small btn-warning" onclick="app.updateTaskStatus('${task.id}', 'in_progress')">Start</button>` : ''}
                    ${task.status === 'in_progress' ? `<button class="btn-small btn-success" onclick="app.updateTaskStatus('${task.id}', 'completed')">Complete</button>` : ''}
                    ${task.status !== 'completed' ? `<button class="btn-small btn-danger" onclick="app.deleteTask('${task.id}')">Delete</button>` : ''}
                </div>
            </div>
        `).join('');

        container.innerHTML = tasksHtml;
    }

    showNotification(message, type) {
        // Simple notification system
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 5px;
            color: white;
            font-weight: 500;
            z-index: 1000;
            background: ${type === 'success' ? '#28a745' : '#dc3545'};
            animation: slideIn 0.3s ease;
        `;

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.remove();
        }, 3000);
    }

    updateLastUpdated() {
        document.getElementById('last-updated').textContent = new Date().toLocaleString();
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Add CSS for notifications
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
`;
document.head.appendChild(style);

// Initialize app
const app = new TaskManagerApp();