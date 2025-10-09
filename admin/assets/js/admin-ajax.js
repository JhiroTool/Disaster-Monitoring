/**
 * Admin AJAX Manager
 * Real-time disaster management without page refresh
 */

const AdminAjax = {
    // Base path for AJAX endpoints
    basePath: 'ajax/',
    
    /**
     * Update disaster status
     */
    updateStatus: function(disasterId, newStatus, comments, callback) {
        const formData = new FormData();
        formData.append('disaster_id', disasterId);
        formData.append('status', newStatus);
        if (comments) formData.append('comments', comments);
        
        this.showLoader('Updating status...');
        
        fetch(this.basePath + 'update-disaster-status.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            this.hideLoader();
            if (data.success) {
                this.showAlert('success', data.message);
                if (callback) callback(data);
            } else {
                this.showAlert('error', data.message);
            }
        })
        .catch(error => {
            this.hideLoader();
            this.showAlert('error', 'Network error: ' + error.message);
            console.error('Status update error:', error);
        });
    },
    
    /**
     * Assign disaster to LGU/User
     */
    assignDisaster: function(disasterId, lguId, userId, comments, callback) {
        const formData = new FormData();
        formData.append('disaster_id', disasterId);
        if (lguId) formData.append('lgu_id', lguId);
        if (userId) formData.append('user_id', userId);
        if (comments) formData.append('comments', comments);
        
        this.showLoader('Updating assignment...');
        
        fetch(this.basePath + 'assign-disaster.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            this.hideLoader();
            if (data.success) {
                this.showAlert('success', data.message);
                if (callback) callback(data);
            } else {
                this.showAlert('error', data.message);
            }
        })
        .catch(error => {
            this.hideLoader();
            this.showAlert('error', 'Network error: ' + error.message);
            console.error('Assignment error:', error);
        });
    },
    
    /**
     * Add disaster update
     */
    addUpdate: function(disasterId, updateText, updateType, title, callback) {
        const formData = new FormData();
        formData.append('disaster_id', disasterId);
        formData.append('update_text', updateText);
        if (updateType) formData.append('update_type', updateType);
        if (title) formData.append('title', title);
        
        this.showLoader('Adding update...');
        
        fetch(this.basePath + 'add-disaster-update.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            this.hideLoader();
            if (data.success) {
                this.showAlert('success', data.message);
                if (callback) callback(data);
            } else {
                this.showAlert('error', data.message);
            }
        })
        .catch(error => {
            this.hideLoader();
            this.showAlert('error', 'Network error: ' + error.message);
            console.error('Add update error:', error);
        });
    },
    
    /**
     * Get disaster details
     */
    getDisasterDetails: function(disasterId, callback) {
        this.showLoader('Loading details...');
        
        fetch(this.basePath + `get-disaster-details.php?disaster_id=${disasterId}`)
        .then(response => response.json())
        .then(data => {
            this.hideLoader();
            if (data.success) {
                if (callback) callback(data);
            } else {
                this.showAlert('error', data.message);
            }
        })
        .catch(error => {
            this.hideLoader();
            this.showAlert('error', 'Network error: ' + error.message);
            console.error('Get details error:', error);
        });
    },
    
    /**
     * Get filtered disasters list
     */
    getDisastersList: function(filters, callback) {
        const params = new URLSearchParams(filters);
        
        this.showLoader('Loading disasters...');
        
        fetch(this.basePath + `get-disasters-list.php?${params.toString()}`)
        .then(response => response.json())
        .then(data => {
            this.hideLoader();
            if (data.success) {
                if (callback) callback(data);
            } else {
                this.showAlert('error', data.message);
            }
        })
        .catch(error => {
            this.hideLoader();
            this.showAlert('error', 'Network error: ' + error.message);
            console.error('Get disasters error:', error);
        });
    },
    
    /**
     * Get dashboard statistics
     */
    getDashboardStats: function(callback) {
        fetch(this.basePath + 'get-dashboard-stats.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (callback) callback(data);
            } else {
                console.error('Stats error:', data.message);
            }
        })
        .catch(error => {
            console.error('Get stats error:', error);
        });
    },
    
    /**
     * Auto-refresh dashboard stats
     */
    startDashboardRefresh: function(interval = 30000) {
        // Initial load
        this.refreshDashboard();
        
        // Set up interval
        setInterval(() => {
            this.refreshDashboard();
        }, interval);
    },
    
    /**
     * Refresh dashboard data
     */
    refreshDashboard: function() {
        this.getDashboardStats((data) => {
            const stats = data.data;
            
            // Update stat cards
            this.updateStatCard('total-disasters', stats.total_disasters);
            this.updateStatCard('active-disasters', stats.active_disasters);
            this.updateStatCard('critical-disasters', stats.critical_disasters);
            this.updateStatCard('pending-disasters', stats.pending_disasters);
            this.updateStatCard('completed-today', stats.completed_today);
            this.updateStatCard('new-today', stats.new_today);
            
            // Update recent reports if table exists
            if (document.getElementById('recent-reports-tbody')) {
                this.updateRecentReports(stats.recent_reports);
            }
            
            // Update last refresh time
            const refreshTime = document.getElementById('last-refresh-time');
            if (refreshTime) {
                refreshTime.textContent = new Date().toLocaleTimeString();
            }
        });
    },
    
    /**
     * Update stat card value
     */
    updateStatCard: function(elementId, value) {
        const element = document.getElementById(elementId);
        if (element) {
            // Animate change
            element.classList.add('updating');
            setTimeout(() => {
                element.textContent = value;
                element.classList.remove('updating');
            }, 200);
        }
    },
    
    /**
     * Update recent reports table
     */
    updateRecentReports: function(reports) {
        const tbody = document.getElementById('recent-reports-tbody');
        if (!tbody) return;
        
        tbody.innerHTML = reports.map(report => `
            <tr>
                <td><code>${report.tracking_id}</code></td>
                <td>${this.escapeHtml(report.disaster_name)}</td>
                <td>${this.escapeHtml(report.type_name)}</td>
                <td><span class="badge bg-${this.getSeverityBadge(report.severity_level)}">${report.severity_display}</span></td>
                <td><span class="badge bg-${this.getStatusBadge(report.status)}">${report.status}</span></td>
                <td>${report.time_ago}</td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="AdminAjax.viewDetails(${report.disaster_id})">
                        <i class="fas fa-eye"></i>
                    </button>
                </td>
            </tr>
        `).join('');
    },
    
    /**
     * View disaster details in modal
     */
    viewDetails: function(disasterId) {
        this.getDisasterDetails(disasterId, (data) => {
            const disaster = data.data.disaster;
            const updates = data.data.updates;
            
            // Populate modal (if exists)
            const modal = document.getElementById('disasterDetailsModal');
            if (modal) {
                this.populateDetailsModal(disaster, updates);
                // Show modal (Bootstrap 5)
                const bsModal = new bootstrap.Modal(modal);
                bsModal.show();
            }
        });
    },
    
    /**
     * Populate details modal
     */
    populateDetailsModal: function(disaster, updates) {
        // This is a placeholder - customize based on your modal structure
        const modalBody = document.querySelector('#disasterDetailsModal .modal-body');
        if (!modalBody) return;
        
        modalBody.innerHTML = `
            <div class="disaster-details">
                <h5>${this.escapeHtml(disaster.disaster_name)}</h5>
                <p><strong>Tracking ID:</strong> <code>${disaster.tracking_id}</code></p>
                <p><strong>Type:</strong> ${this.escapeHtml(disaster.type_name)}</p>
                <p><strong>Status:</strong> <span class="badge bg-${this.getStatusBadge(disaster.status)}">${disaster.status}</span></p>
                <p><strong>Location:</strong> ${this.escapeHtml(disaster.city)}, ${this.escapeHtml(disaster.province)}</p>
                <hr>
                <h6>Updates (${updates.length})</h6>
                <div class="updates-list">
                    ${updates.map(update => `
                        <div class="update-item">
                            <small class="text-muted">${update.formatted_date}</small>
                            <p><strong>${update.title}</strong></p>
                            <p>${this.escapeHtml(update.description)}</p>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    },
    
    /**
     * Show loading indicator
     */
    showLoader: function(message = 'Loading...') {
        let loader = document.getElementById('ajax-loader');
        if (!loader) {
            loader = document.createElement('div');
            loader.id = 'ajax-loader';
            loader.className = 'ajax-loader';
            loader.innerHTML = `
                <div class="ajax-loader-content">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2">${message}</p>
                </div>
            `;
            document.body.appendChild(loader);
        }
        loader.querySelector('p').textContent = message;
        loader.style.display = 'flex';
    },
    
    /**
     * Hide loading indicator
     */
    hideLoader: function() {
        const loader = document.getElementById('ajax-loader');
        if (loader) {
            loader.style.display = 'none';
        }
    },
    
    /**
     * Show alert message
     */
    showAlert: function(type, message) {
        // Create alert element
        const alert = document.createElement('div');
        alert.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show ajax-alert`;
        alert.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        // Add to page
        const container = document.querySelector('.container-fluid') || document.body;
        container.insertBefore(alert, container.firstChild);
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            alert.classList.remove('show');
            setTimeout(() => alert.remove(), 150);
        }, 5000);
    },
    
    /**
     * Get severity badge color
     */
    getSeverityBadge: function(severityLevel) {
        if (!severityLevel) return 'secondary';
        const prefix = severityLevel.split('-')[0];
        return {
            'red': 'danger',
            'orange': 'warning',
            'yellow': 'info',
            'green': 'success'
        }[prefix] || 'secondary';
    },
    
    /**
     * Get status badge color
     */
    getStatusBadge: function(status) {
        return {
            'ON GOING': 'warning',
            'IN PROGRESS': 'info',
            'COMPLETED': 'success'
        }[status] || 'secondary';
    },
    
    /**
     * Escape HTML to prevent XSS
     */
    escapeHtml: function(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
};

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    console.log('Admin AJAX Manager initialized');
    
    // Auto-refresh dashboard if on dashboard page
    if (document.body.classList.contains('dashboard-page')) {
        AdminAjax.startDashboardRefresh(30000); // Refresh every 30 seconds
    }
    
    // Setup quick action buttons
    document.querySelectorAll('[data-ajax-action="update-status"]').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const disasterId = this.dataset.disasterId;
            const status = this.dataset.status;
            AdminAjax.updateStatus(disasterId, status, '', () => {
                location.reload(); // or update UI dynamically
            });
        });
    });
});

// Export for use in other scripts
if (typeof window !== 'undefined') {
    window.AdminAjax = AdminAjax;
}
