// Admin Dashboard JavaScript
document.addEventListener('DOMContentLoaded', function() {
    initializeSidebar();
    initializeDropdowns();
    initializeDataTables();
    loadNotifications();
});

// Sidebar functionality
function initializeSidebar() {
    const sidebarToggle = document.querySelectorAll('.sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');
    
    sidebarToggle.forEach(toggle => {
        toggle.addEventListener('click', function() {
            sidebar.classList.toggle('show');
        });
    });
    
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 768) {
            if (!sidebar.contains(e.target) && !e.target.closest('.sidebar-toggle')) {
                sidebar.classList.remove('show');
            }
        }
    });
}

// Standalone sidebar toggle function for onclick handlers
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    if (sidebar) {
        sidebar.classList.toggle('show');
    }
}

// Dropdown functionality
function initializeDropdowns() {
    document.addEventListener('click', function(e) {
        // Close all dropdowns
        document.querySelectorAll('.dropdown-menu.show').forEach(dropdown => {
            dropdown.classList.remove('show');
        });
    });
}

function toggleDropdown(dropdownId) {
    event.stopPropagation();
    const dropdown = document.getElementById(dropdownId + '-dropdown');
    if (dropdown) {
        dropdown.classList.toggle('show');
    }
}

// Initialize DataTables
function initializeDataTables() {
    if ($.fn.DataTable) {
        $('.data-table').DataTable({
            responsive: true,
            pageLength: 25,
            order: [[0, 'desc']],
            language: {
                search: "Search:",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                paginate: {
                    first: "First",
                    last: "Last",
                    next: "Next",
                    previous: "Previous"
                }
            }
        });
    }
}

// Load notifications
async function loadNotifications() {
    try {
        const response = await fetch('ajax/get-notifications.php');
        const data = await response.json();
        
        const dropdown = document.querySelector('#notifications-dropdown .dropdown-body');
        if (dropdown && data.success) {
            if (data.notifications.length === 0) {
                dropdown.innerHTML = '<div class="empty-state"><p>No notifications</p></div>';
            } else {
                dropdown.innerHTML = data.notifications.map(notification => `
                    <a href="#" onclick="markAsRead(${notification.notification_id})" class="${!notification.is_read ? 'unread' : ''}">
                        <div>
                            <strong>${escapeHtml(notification.title)}</strong>
                            <p>${escapeHtml(notification.message)}</p>
                            <small>${formatTimeAgo(notification.created_at)}</small>
                        </div>
                    </a>
                `).join('');
            }
        }
    } catch (error) {
        console.error('Error loading notifications:', error);
    }
}

// Mark notification as read
async function markAsRead(notificationId) {
    try {
        const response = await fetch('ajax/mark-notification-read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ notification_id: notificationId })
        });
        
        const data = await response.json();
        if (data.success) {
            loadNotifications();
            updateNotificationBadge();
        }
    } catch (error) {
        console.error('Error marking notification as read:', error);
    }
}

// Mark all notifications as read
async function markAllRead() {
    try {
        const response = await fetch('ajax/mark-all-notifications-read.php', {
            method: 'POST'
        });
        
        const data = await response.json();
        if (data.success) {
            loadNotifications();
            updateNotificationBadge();
        }
    } catch (error) {
        console.error('Error marking all notifications as read:', error);
    }
}

// Update notification badge count
async function updateNotificationBadge() {
    try {
        const response = await fetch('ajax/get-notification-count.php');
        const data = await response.json();
        
        const badges = document.querySelectorAll('.notification-badge');
        badges.forEach(badge => {
            if (data.count > 0) {
                badge.textContent = data.count;
                badge.style.display = 'block';
            } else {
                badge.style.display = 'none';
            }
        });
    } catch (error) {
        console.error('Error updating notification badge:', error);
    }
}

// Utility functions
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

function formatTimeAgo(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffInSeconds = Math.floor((now - date) / 1000);
    
    if (diffInSeconds < 60) {
        return 'Just now';
    } else if (diffInSeconds < 3600) {
        const minutes = Math.floor(diffInSeconds / 60);
        return `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
    } else if (diffInSeconds < 86400) {
        const hours = Math.floor(diffInSeconds / 3600);
        return `${hours} hour${hours > 1 ? 's' : ''} ago`;
    } else {
        const days = Math.floor(diffInSeconds / 86400);
        return `${days} day${days > 1 ? 's' : ''} ago`;
    }
}

// Form validation
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;
    
    let isValid = true;
    const requiredFields = form.querySelectorAll('[required]');
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            showFieldError(field, 'This field is required');
            isValid = false;
        } else {
            clearFieldError(field);
        }
    });
    
    return isValid;
}

function showFieldError(field, message) {
    clearFieldError(field);
    
    field.classList.add('error');
    const errorDiv = document.createElement('div');
    errorDiv.className = 'field-error';
    errorDiv.textContent = message;
    field.parentNode.appendChild(errorDiv);
}

function clearFieldError(field) {
    field.classList.remove('error');
    const existingError = field.parentNode.querySelector('.field-error');
    if (existingError) {
        existingError.remove();
    }
}

// Show toast notifications
function showToast(message, type = 'info', duration = 5000) {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <i class="fas fa-${getToastIcon(type)}"></i>
        <span>${escapeHtml(message)}</span>
        <button onclick="this.parentElement.remove()" class="toast-close">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    // Create toast container if it doesn't exist
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }
    
    container.appendChild(toast);
    
    // Auto remove after duration
    setTimeout(() => {
        if (toast.parentElement) {
            toast.remove();
        }
    }, duration);
}

function getToastIcon(type) {
    const icons = {
        success: 'check-circle',
        error: 'exclamation-triangle',
        warning: 'exclamation-triangle',
        info: 'info-circle'
    };
    return icons[type] || 'info-circle';
}

// Confirmation dialogs
function showConfirmation(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

// AJAX helper functions
async function makeRequest(url, options = {}) {
    const defaultOptions = {
        headers: {
            'Content-Type': 'application/json',
        }
    };
    
    const finalOptions = { ...defaultOptions, ...options };
    
    try {
        const response = await fetch(url, finalOptions);
        const data = await response.json();
        return data;
    } catch (error) {
        console.error('Request failed:', error);
        throw error;
    }
}

// Auto-refresh functionality
let autoRefreshInterval;

function startAutoRefresh(interval = 30000) {
    autoRefreshInterval = setInterval(() => {
        // Refresh current page data
        if (typeof refreshPageData === 'function') {
            refreshPageData();
        }
    }, interval);
}

function stopAutoRefresh() {
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
        autoRefreshInterval = null;
    }
}

// Search functionality
function initializeSearch(inputId, tableId) {
    const searchInput = document.getElementById(inputId);
    const table = document.getElementById(tableId);
    
    if (searchInput && table) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = table.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    }
}

// Export functionality
function exportTable(tableId, filename = 'export.csv') {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    rows.forEach(row => {
        const cols = row.querySelectorAll('td, th');
        const rowData = Array.from(cols).map(col => {
            return '"' + col.textContent.replace(/"/g, '""') + '"';
        });
        csv.push(rowData.join(','));
    });
    
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    a.click();
    
    window.URL.revokeObjectURL(url);
}

// Print functionality
function printPage() {
    window.print();
}

// File upload preview
function previewFile(input, previewId) {
    const file = input.files[0];
    const preview = document.getElementById(previewId);
    
    if (file && preview) {
        const reader = new FileReader();
        reader.onload = function(e) {
            if (file.type.startsWith('image/')) {
                preview.innerHTML = `<img src="${e.target.result}" alt="Preview" style="max-width: 200px; max-height: 200px;">`;
            } else {
                preview.innerHTML = `<p>File selected: ${file.name}</p>`;
            }
        };
        reader.readAsDataURL(file);
    }
}

// Initialize tooltips (if using a tooltip library)
function initializeTooltips() {
    // Add tooltip initialization here if using a library like Popper.js
}

// Initialize the application
function initializeApp() {
    initializeSidebar();
    initializeDropdowns();
    initializeDataTables();
    loadNotifications();
    startAutoRefresh();
}

// Call initialization when DOM is loaded
document.addEventListener('DOMContentLoaded', initializeApp);