/**
 * AJAX Disaster Report Tracking
 * Real-time tracking without page refresh
 */

// Debounce function to avoid too many requests
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Check disaster report via AJAX
function checkDisasterReport(trackingId, callback) {
    // Validate tracking ID format (optional)
    if (!trackingId || trackingId.trim() === '') {
        if (callback) callback({ success: false, message: 'Please enter a tracking ID.' });
        return;
    }

    // Show loading indicator
    const submitBtn = document.getElementById('trackSubmitBtn');
    const resultDiv = document.getElementById('trackingResult');
    
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Searching...';
    }

    // Make AJAX request
    fetch('ajax/check_disaster_report.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `tracking_id=${encodeURIComponent(trackingId)}`
    })
    .then(response => response.json())
    .then(data => {
        if (callback) callback(data);
        
        // Display results
        displayTrackingResult(data);
        
        // Reset button
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-search"></i> Track Report';
        }
    })
    .catch(error => {
        console.error('Error checking disaster report:', error);
        
        if (resultDiv) {
            resultDiv.innerHTML = `
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-circle"></i>
                    <strong>Error:</strong> Unable to check report. Please try again.
                </div>
            `;
        }
        
        // Reset button
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-search"></i> Track Report';
        }
        
        if (callback) callback({ success: false, message: 'Network error occurred.' });
    });
}

// Display tracking result
function displayTrackingResult(data) {
    const resultDiv = document.getElementById('trackingResult');
    if (!resultDiv) return;

    if (!data.success || !data.exists) {
        // Show error message
        resultDiv.innerHTML = `
            <div class="alert alert-warning" role="alert">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Not Found:</strong> ${data.message || 'No report found with this tracking ID.'}
            </div>
        `;
        return;
    }

    // Success - display disaster information
    const disaster = data.data.disaster;
    const updates = data.data.updates || [];
    
    // Parse immediate needs
    const immediateNeeds = disaster.immediate_needs_array || [];
    const needsLabels = {
        'medical_assistance': 'Medical Assistance',
        'food_water': 'Food & Water',
        'shelter': 'Shelter',
        'rescue': 'Rescue',
        'evacuation': 'Evacuation',
        'electricity_restoration': 'Electricity',
        'communication_restoration': 'Communication',
        'transportation': 'Transportation',
        'security': 'Security'
    };
    
    const needsHTML = immediateNeeds.length > 0 
        ? immediateNeeds.map(need => `<span class="badge bg-info">${needsLabels[need] || need}</span>`).join(' ')
        : '<span class="text-muted">None specified</span>';

    // Build updates timeline
    let updatesHTML = '';
    if (updates.length > 0) {
        updatesHTML = `
            <div class="card mt-3">
                <div class="card-header">
                    <i class="fas fa-history"></i> Updates Timeline (${updates.length})
                </div>
                <div class="card-body">
                    <div class="timeline">
        `;
        
        updates.forEach(update => {
            const badgeClass = update.user_role === 'admin' ? 'bg-danger' : 'bg-primary';
            updatesHTML += `
                <div class="timeline-item">
                    <div class="timeline-marker ${badgeClass}"></div>
                    <div class="timeline-content">
                        <div class="timeline-header">
                            <span class="badge ${badgeClass}">${update.user_name || 'System'}</span>
                            <small class="text-muted">${update.formatted_update_date}</small>
                        </div>
                        <p class="mb-0">${update.update_text || ''}</p>
                    </div>
                </div>
            `;
        });
        
        updatesHTML += `
                    </div>
                </div>
            </div>
        `;
    }

    // Store update count for auto-refresh comparison
    lastUpdateCount = updates.length;
    
    // Build result HTML
    resultDiv.innerHTML = `
        <div class="alert alert-success" role="alert">
            <i class="fas fa-check-circle"></i>
            <strong>Found!</strong> Report details loaded successfully.
        </div>

        <div class="card mt-3">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-exclamation-triangle"></i> 
                    ${disaster.disaster_name || 'Disaster Report'}
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Tracking ID:</strong> <code>${disaster.tracking_id}</code></p>
                        <p><strong>Type:</strong> ${disaster.type_name || 'N/A'}</p>
                        <p><strong>Severity:</strong> 
                            <span class="badge bg-${disaster.severity_color}">${disaster.severity_display || disaster.severity_level}</span>
                        </p>
                        <p><strong>Priority:</strong> 
                            <span class="badge bg-${disaster.priority_color}">${disaster.priority ? disaster.priority.toUpperCase() : 'N/A'}</span>
                        </p>
                        <p><strong>Status:</strong> 
                            <span class="badge ${disaster.status === 'COMPLETED' ? 'bg-success' : disaster.status === 'IN PROGRESS' ? 'bg-info' : 'bg-warning'}">
                                ${disaster.status_display}
                            </span>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Location:</strong> ${disaster.city || ''}, ${disaster.province || ''}</p>
                        <p><strong>Address:</strong> ${disaster.address || 'N/A'}</p>
                        ${disaster.landmark ? `<p><strong>Landmark:</strong> ${disaster.landmark}</p>` : ''}
                        <p><strong>Reported:</strong> ${disaster.formatted_date}</p>
                        <p><strong>Time Elapsed:</strong> ${disaster.hours_elapsed} hours ago</p>
                    </div>
                </div>

                ${disaster.description ? `
                    <hr>
                    <p><strong>Description:</strong></p>
                    <p>${disaster.description}</p>
                ` : ''}

                ${disaster.current_situation ? `
                    <p><strong>Current Situation:</strong></p>
                    <p>${disaster.current_situation}</p>
                ` : ''}

                <hr>
                <p><strong>Immediate Needs:</strong></p>
                <p>${needsHTML}</p>

                ${disaster.assigned_lgu_id ? `
                    <hr>
                    <div class="alert alert-info">
                        <strong><i class="fas fa-building"></i> Assigned to:</strong> ${disaster.lgu_name || 'LGU'}
                        ${disaster.lgu_phone ? `<br><strong>Contact:</strong> ${disaster.lgu_phone}` : ''}
                        ${disaster.assigned_user_name ? `<br><strong>Handler:</strong> ${disaster.assigned_user_name}` : ''}
                    </div>
                ` : `
                    <div class="alert alert-warning">
                        <i class="fas fa-clock"></i> This report is pending assignment to an LGU.
                    </div>
                `}

                ${disaster.image_path ? `
                    <hr>
                    <p><strong>Attached Image:</strong></p>
                    <img src="${disaster.image_path}" alt="Disaster Image" class="img-fluid rounded" style="max-height: 300px;">
                ` : ''}
            </div>
        </div>

        ${updatesHTML}

        <div class="d-flex gap-2 mt-3">
            <button class="btn btn-primary" onclick="window.disasterTracking.check('${disaster.tracking_id}')">
                <i class="fas fa-sync"></i> Refresh Now
            </button>
        </div>
    `;

    // Scroll to result
    resultDiv.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// Real-time tracking ID validation
function validateTrackingId(trackingId) {
    // Expected format: DM20250927-XXXXXX (DM + Date + Hyphen + 6 chars)
    const pattern = /^DM\d{8}-[A-F0-9]{6}$/i;
    return pattern.test(trackingId);
}

// Initialize tracking form
document.addEventListener('DOMContentLoaded', function() {
    const trackingForm = document.getElementById('trackingForm');
    const trackingInput = document.getElementById('trackingIdInput');
    const validationFeedback = document.getElementById('trackingValidation');

    if (trackingInput) {
        // Real-time validation as user types (debounced)
        trackingInput.addEventListener('input', debounce(function() {
            const value = this.value.trim();
            
            if (value === '') {
                this.classList.remove('is-valid', 'is-invalid');
                if (validationFeedback) validationFeedback.textContent = '';
                return;
            }

            if (validateTrackingId(value)) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
                if (validationFeedback) {
                    validationFeedback.className = 'valid-feedback';
                    validationFeedback.textContent = 'Valid tracking ID format';
                }
            } else {
                this.classList.remove('is-valid');
                this.classList.add('is-invalid');
                if (validationFeedback) {
                    validationFeedback.className = 'invalid-feedback';
                    validationFeedback.textContent = 'Invalid format. Expected: DM20250927-XXXXXX';
                }
            }
        }, 300));
    }

    if (trackingForm) {
        trackingForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const trackingId = trackingInput ? trackingInput.value.trim() : '';
            
            if (trackingId) {
                checkDisasterReport(trackingId);
            } else {
                const resultDiv = document.getElementById('trackingResult');
                if (resultDiv) {
                    resultDiv.innerHTML = `
                        <div class="alert alert-warning" role="alert">
                            <i class="fas fa-exclamation-triangle"></i>
                            Please enter a tracking ID.
                        </div>
                    `;
                }
            }
        });
    }

    // Auto-track if tracking ID in URL
    const urlParams = new URLSearchParams(window.location.search);
    const autoTrackingId = urlParams.get('tracking_id');
    if (autoTrackingId && trackingInput) {
        trackingInput.value = autoTrackingId;
        setTimeout(() => checkDisasterReport(autoTrackingId), 500);
    }
});

// Real-time auto-refresh functionality
let autoRefreshInterval = null;
let currentTrackingId = null;
let lastUpdateCount = 0;

/**
 * Start auto-refresh for a tracking ID
 * Checks for updates every 30 seconds
 */
function startAutoRefresh(trackingId, intervalSeconds = 30) {
    // Stop any existing refresh
    stopAutoRefresh();
    
    currentTrackingId = trackingId;
    
    // Create refresh indicator
    const resultDiv = document.getElementById('trackingResult');
    if (resultDiv && !document.getElementById('autoRefreshIndicator')) {
        const indicator = document.createElement('div');
        indicator.id = 'autoRefreshIndicator';
        indicator.className = 'alert alert-info mt-2 d-flex justify-content-between align-items-center';
        indicator.innerHTML = `
            <span>
                <i class="fas fa-sync-alt fa-spin"></i> 
                Auto-refreshing every ${intervalSeconds} seconds
            </span>
            <button class="btn btn-sm btn-outline-secondary" onclick="window.disasterTracking.stopAutoRefresh()">
                <i class="fas fa-stop"></i> Stop
            </button>
        `;
        resultDiv.insertAdjacentElement('afterbegin', indicator);
    }
    
    // Set up interval
    autoRefreshInterval = setInterval(() => {
        silentCheckForUpdates(trackingId);
    }, intervalSeconds * 1000);
    
    console.log(`Auto-refresh started for ${trackingId} (every ${intervalSeconds}s)`);
}

/**
 * Stop auto-refresh
 */
function stopAutoRefresh() {
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
        autoRefreshInterval = null;
        currentTrackingId = null;
        
        // Remove indicator
        const indicator = document.getElementById('autoRefreshIndicator');
        if (indicator) {
            indicator.remove();
        }
        
        console.log('Auto-refresh stopped');
    }
}

/**
 * Silently check for updates without showing loading indicator
 */
function silentCheckForUpdates(trackingId) {
    fetch('ajax/check_disaster_report.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `tracking_id=${encodeURIComponent(trackingId)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.exists) {
            const newUpdateCount = data.data.update_count || 0;
            
            // Check if there are new updates
            if (newUpdateCount > lastUpdateCount) {
                console.log(`New updates detected: ${newUpdateCount - lastUpdateCount} new update(s)`);
                
                // Show notification
                showUpdateNotification(newUpdateCount - lastUpdateCount);
                
                // Update display
                displayTrackingResult(data);
                
                lastUpdateCount = newUpdateCount;
            } else {
                // Just update the last check timestamp
                updateLastCheckedTime();
            }
        }
    })
    .catch(error => {
        console.error('Auto-refresh error:', error);
        // Don't stop auto-refresh on error, just log it
    });
}

/**
 * Show notification when new updates are detected
 */
function showUpdateNotification(count) {
    const resultDiv = document.getElementById('trackingResult');
    if (!resultDiv) return;
    
    // Create notification
    const notification = document.createElement('div');
    notification.className = 'alert alert-success alert-dismissible fade show';
    notification.innerHTML = `
        <strong><i class="fas fa-bell"></i> New Update!</strong> 
        ${count} new update(s) added to this report.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    resultDiv.insertAdjacentElement('afterbegin', notification);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 5000);
    
    // Play notification sound (if browser allows)
    try {
        const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+Dyvm==');
        audio.volume = 0.3;
        audio.play().catch(() => {}); // Ignore if autoplay is blocked
    } catch (e) {
        // Silently fail if audio doesn't work
    }
}

/**
 * Update last checked timestamp
 */
function updateLastCheckedTime() {
    const indicator = document.getElementById('autoRefreshIndicator');
    if (indicator) {
        const now = new Date();
        const timeString = now.toLocaleTimeString();
        
        // Update or add timestamp
        let timeEl = indicator.querySelector('.last-check-time');
        if (!timeEl) {
            timeEl = document.createElement('small');
            timeEl.className = 'last-check-time text-muted ms-2';
            indicator.querySelector('span').appendChild(timeEl);
        }
        timeEl.textContent = `(Last checked: ${timeString})`;
    }
}

// Export for use in other scripts
if (typeof window !== 'undefined') {
    window.disasterTracking = {
        check: checkDisasterReport,
        validate: validateTrackingId
    };
}
