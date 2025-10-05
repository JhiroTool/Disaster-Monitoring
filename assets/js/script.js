// =====================================================
// iMSafe Disaster Monitoring System - JavaScript
// =====================================================

// API Configuration
const API_BASE_URL = '/Disaster-Monitoring/api/';

document.addEventListener('DOMContentLoaded', function() {
    
    // Load disaster types for the dropdown
    loadDisasterTypes();
    
    // Mobile Navigation Toggle
    const hamburger = document.getElementById('hamburger');
    const navMenu = document.getElementById('nav-menu');
    
    if (hamburger && navMenu) {
        hamburger.addEventListener('click', function() {
            hamburger.classList.toggle('active');
            navMenu.classList.toggle('active');
        });
    }
    
    // Close mobile menu when clicking on a link
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        link.addEventListener('click', () => {
            if (hamburger && navMenu) {
                hamburger.classList.remove('active');
                navMenu.classList.remove('active');
            }
        });
    });

    const dropdowns = document.querySelectorAll('.nav-dropdown');

    const closeDropdown = (dropdown) => {
        if (!dropdown) return;
        dropdown.classList.remove('open');
        const toggle = dropdown.querySelector('.nav-dropdown-toggle');
        if (toggle) {
            toggle.setAttribute('aria-expanded', 'false');
        }
    };

    const closeAllDropdowns = () => {
        dropdowns.forEach(dropdown => closeDropdown(dropdown));
    };

    dropdowns.forEach(dropdown => {
        const toggle = dropdown.querySelector('.nav-dropdown-toggle');
        const menu = dropdown.querySelector('.nav-dropdown-menu');

        if (!toggle || !menu) {
            return;
        }

        toggle.setAttribute('aria-expanded', 'false');

        toggle.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();

            const isOpen = dropdown.classList.contains('open');
            closeAllDropdowns();

            if (!isOpen) {
                dropdown.classList.add('open');
                toggle.setAttribute('aria-expanded', 'true');
            } else {
                toggle.setAttribute('aria-expanded', 'false');
            }
        });

        const dropdownItems = dropdown.querySelectorAll('.nav-dropdown-item');
        dropdownItems.forEach(item => {
            item.addEventListener('click', () => {
                closeDropdown(dropdown);
                if (hamburger && navMenu) {
                    hamburger.classList.remove('active');
                    navMenu.classList.remove('active');
                }
            });
        });
    });

    document.addEventListener('click', (event) => {
        dropdowns.forEach(dropdown => {
            if (!dropdown.contains(event.target)) {
                closeDropdown(dropdown);
            }
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeAllDropdowns();
        }
    });
    
    // Smooth Scrolling for Navigation Links
    const scrollLinks = document.querySelectorAll('a[href^="#"]');
    scrollLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href').substring(1);
            const targetSection = document.getElementById(targetId);
            
            if (targetSection) {
                const navHeight = document.querySelector('.navbar').offsetHeight;
                const targetPosition = targetSection.offsetTop - navHeight;
                
                window.scrollTo({
                    top: targetPosition,
                    behavior: 'smooth'
                });
            }
        });
    });
    
    // Active Navigation Link Updates
    const sections = document.querySelectorAll('section[id]');
    const navLinksAll = document.querySelectorAll('.nav-link[href^="#"]');
    
    function updateActiveNav() {
        const scrollPosition = window.scrollY;
        const navHeight = document.querySelector('.navbar').offsetHeight;
        
        sections.forEach(section => {
            const sectionTop = section.offsetTop - navHeight - 100;
            const sectionBottom = sectionTop + section.offsetHeight;
            const sectionId = section.getAttribute('id');
            
            if (scrollPosition >= sectionTop && scrollPosition < sectionBottom) {
                navLinksAll.forEach(link => {
                    link.classList.remove('active');
                    if (link.getAttribute('href') === `#${sectionId}`) {
                        link.classList.add('active');
                    }
                });
            }
        });
    }
    
    // Navbar Scroll Effect
    function handleNavbarScroll() {
        const navbar = document.querySelector('.navbar');
        if (window.scrollY > 50) {
            navbar.style.background = 'rgba(255, 255, 255, 0.98)';
            navbar.style.backdropFilter = 'blur(20px)';
        } else {
            navbar.style.background = 'rgba(255, 255, 255, 0.95)';
            navbar.style.backdropFilter = 'blur(20px)';
        }
    }
    
    // Scroll Event Listeners
    window.addEventListener('scroll', () => {
        updateActiveNav();
        handleNavbarScroll();
    });
    
    // Emergency Form Handler
    const emergencyForm = document.getElementById('emergencyForm');
    if (emergencyForm) {
        emergencyForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Get form data
            const formData = new FormData(this);
            
            // Get selected needs (checkboxes)
            const selectedNeeds = [];
            const needsCheckboxes = this.querySelectorAll('input[name="needs[]"]:checked');
            needsCheckboxes.forEach(checkbox => {
                selectedNeeds.push(checkbox.value);
            });
            
            // Get selected situation assessment directly
            const selectedSituation = formData.get('severity');
            
            const reportData = {
                disaster_type: formData.get('disaster_type'),
                severity: selectedSituation,
                severity_display: getImpactLevelText(selectedSituation),
                location: formData.get('location'),
                phone: formData.get('phone'),
                description: formData.get('description'),
                reporter_name: formData.get('reporter_name') || 'Anonymous Reporter',
                alternate_contact: formData.get('alternate_contact'),
                landmark: formData.get('landmark'),
                people_affected: formData.get('people_affected'),
                current_situation: formData.get('current_situation'),
                immediate_needs: selectedNeeds,
                tracking_id: generateTrackingId(),
                timestamp: new Date().toISOString(),
                status: 'pending'
            };
            
            // Validate required fields (address, phone, disaster type, severity are required)
            if (!reportData.disaster_type || !selectedSituation || 
                !reportData.location || !reportData.phone) {
                showNotification('Please fill in all required fields: Disaster Type, Current Situation, Address, and Contact Number.', 'error');
                return;
            }
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
            submitBtn.disabled = true;
            
            // Submit to database
            submitEmergencyReport(reportData)
                .then(response => {
                    if (response.success) {
                        // Show success message with tracking ID
                        showNotification(`Emergency report submitted successfully! Your tracking ID is: ${response.tracking_id}. You will receive SMS confirmation shortly.`, 'success');
                        
                        // Reset form
                        this.reset();
                        
                        // Show detailed report tracking information
                        showReportTrackingInfo({
                            ...reportData,
                            tracking_id: response.tracking_id,
                            disaster_id: response.disaster_id
                        });
                    } else {
                        throw new Error(response.message || 'Submission failed');
                    }
                })
                .catch(error => {
                    console.error('Submission error:', error);
                    showNotification(`Error submitting report: ${error.message}. Please try again.`, 'error');
                })
                .finally(() => {
                    // Reset button
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                });
        });
    }
    
    // Contact Form Handler
    const contactForm = document.getElementById('contactForm');
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
            submitBtn.disabled = true;
            
            // Simulate API call
            setTimeout(() => {
                showNotification('Message sent successfully! We will get back to you soon.', 'success');
                this.reset();
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 1500);
        });
    }
    
    // Notification System
    function showNotification(message, type = 'info') {
        // Remove existing notifications
        const existingNotification = document.querySelector('.notification');
        if (existingNotification) {
            existingNotification.remove();
        }
        
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas ${getNotificationIcon(type)}"></i>
                <span>${message}</span>
                <button class="notification-close" onclick="this.parentElement.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        
        // Add styles
        notification.style.cssText = `
            position: fixed;
            top: 100px;
            right: 20px;
            z-index: 9999;
            background: ${getNotificationColor(type)};
            color: white;
            padding: 1rem;
            border-radius: 0.75rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            max-width: 400px;
            animation: slideIn 0.3s ease-out;
        `;
        
        // Add to page
        document.body.appendChild(notification);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 5000);
    }
    
    function getNotificationIcon(type) {
        switch(type) {
            case 'success': return 'fa-check-circle';
            case 'error': return 'fa-exclamation-circle';
            case 'warning': return 'fa-exclamation-triangle';
            default: return 'fa-info-circle';
        }
    }
    
    function getNotificationColor(type) {
        switch(type) {
            case 'success': return 'linear-gradient(135deg, #10b981, #059669)';
            case 'error': return 'linear-gradient(135deg, #ef4444, #dc2626)';
            case 'warning': return 'linear-gradient(135deg, #f59e0b, #d97706)';
            default: return 'linear-gradient(135deg, #3b82f6, #1d4ed8)';
        }
    }
    
    // Show Report Tracking Information
    function showReportTrackingInfo(reportData) {
        const trackingId = generateTrackingId();
        const modal = document.createElement('div');
        modal.className = 'tracking-modal';
        modal.innerHTML = `
            <div class="modal-overlay" onclick="this.parentElement.remove()"></div>
            <div class="modal-content">
                <div class="modal-header">
                    <h3><i class="fas fa-clipboard-check"></i> Report Submitted Successfully</h3>
                    <button class="modal-close" onclick="this.parentElement.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="tracking-info">
                        <div class="tracking-id">
                            <strong>Tracking ID:</strong> <code>${trackingId}</code>
                            <button class="copy-btn" onclick="copyToClipboard('${trackingId}')">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                        <div class="report-summary">
                            <p><strong>Disaster Type:</strong> ${getDisasterTypeText(reportData.disaster_type)}</p>
                            <p><strong>Current Situation:</strong> ${reportData.severity_display}</p>
                            <p><strong>Location:</strong> ${reportData.location}</p>
                        </div>
                        <div class="response-timeline">
                            <h4>Expected Response Timeline:</h4>
                            <div class="timeline-item">
                                <i class="fas fa-user-check"></i>
                                <span>LGU Assignment: Within 5 minutes</span>
                            </div>
                            <div class="timeline-item">
                                <i class="fas fa-clock"></i>
                                <span>Acknowledgment: ${getAcknowledgmentTime(reportData.severity)}</span>
                            </div>
                            <div class="timeline-item">
                                <i class="fas fa-ambulance"></i>
                                <span>Response: ${getResponseTime(reportData.severity)}</span>
                            </div>
                        </div>
                        <div class="emergency-contacts">
                            <h4>Emergency Contacts:</h4>
                            <p><i class="fas fa-phone"></i> Emergency Hotline: <strong>911</strong></p>
                            <p><i class="fas fa-envelope"></i> Support Email: support@imsafe.gov.ph</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" onclick="this.parentElement.parentElement.parentElement.remove()">
                        OK, Got it
                    </button>
                </div>
            </div>
        `;
        
        // Add modal styles
        modal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
        `;
        
        document.body.appendChild(modal);
    }
    
    // Helper Functions
    function generateTrackingId() {
        const date = new Date();
        const year = date.getFullYear().toString().substr(-2);
        const month = (date.getMonth() + 1).toString().padStart(2, '0');
        const day = date.getDate().toString().padStart(2, '0');
        const random = Math.random().toString(36).substr(2, 6).toUpperCase();
        return `EM${year}${month}${day}-${random}`;
    }
    
    function getDisasterTypeText(type) {
        const types = {
            'earthquake': 'Earthquake',
            'flood': 'Flood',
            'fire': 'Fire',
            'landslide': 'Landslide',
            'typhoon': 'Typhoon',
            'volcanic': 'Volcanic Activity',
            'storm': 'Storm/Heavy Rain',
            'drought': 'Drought',
            'other': 'Other Emergency'
        };
        return types[type] || type;
    }
    
    function getUserSeverityText(severity) {
        const severityTexts = {
            'minor': 'Minor - Minimal damage or impact',
            'moderate': 'Moderate - Some damage, limited impact',
            'serious': 'Serious - Significant damage or risk',
            'critical': 'Critical - Severe damage, immediate danger',
            'catastrophic': 'Catastrophic - Extreme damage, life-threatening'
        };
        return severityTexts[severity] || severity;
    }
    
    function getAcknowledgmentTime(severity) {
        // Use internal admin assessment for timing, not user input
        const impactLevel = severity.split('-')[0];
        if (impactLevel === 'red') return 'Within 2 hours';
        if (impactLevel === 'orange') return 'Within 6 hours';
        return 'Within 12 hours';
    }
    
    function getResponseTime(severity) {
        // Use internal admin assessment for timing, not user input
        const impactLevel = severity.split('-')[0];
        if (impactLevel === 'red') return 'Within 24 hours';
        if (impactLevel === 'orange') return 'Within 36 hours';
        return 'Within 48 hours';
    }
    
    function getImpactLevelText(severity) {
        const impactMap = {
            'green-1': '✅ Favorable circumstances',
            'green-2': '🏠 Intact homes & accessible roads',
            'green-3': '⚡ Functional power & supplies',
            'green-4': '💧 No flooding or major damage',
            'green-5': '🔧 Rebuilt infrastructure',
            'orange-1': '⚠️ Moderate problems',
            'orange-2': '🏗️ Minor structural damage',
            'orange-3': '🛣️ Partially accessible roads',
            'orange-4': '📦 Limited supplies & sporadic outages',
            'orange-5': '🌊 Minor floods & safety issues',
            'red-1': '🚨 Critical situations',
            'red-2': '💥 Heavy devastation',
            'red-3': '⚡ Widespread power loss',
            'red-4': '❌ Resource unavailability',
            'red-5': '🔒 Significant security problems'
        };
        return impactMap[severity] || severity;
    }
    
    // Map user severity to admin assessment system
    function mapSeverityToAssessment(userSeverity) {
        const severityMap = {
            'minor': {
                code: 'green-4',
                text: '💧 No flooding or major damage'
            },
            'moderate': {
                code: 'orange-1',
                text: '⚠️ Moderate problems'
            },
            'serious': {
                code: 'orange-2',
                text: '🏗️ Minor structural damage'
            },
            'critical': {
                code: 'red-1',
                text: '🚨 Critical situations'
            },
            'catastrophic': {
                code: 'red-2',
                text: '💥 Heavy devastation'
            }
        };
        
        return severityMap[userSeverity] || {
            code: 'orange-1',
            text: '⚠️ Moderate problems'
        };
    }
    
    // Copy to Clipboard Function
    window.copyToClipboard = function(text) {
        navigator.clipboard.writeText(text).then(() => {
            showNotification('Tracking ID copied to clipboard!', 'success');
        });
    };
    
    // Intersection Observer for Animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-in');
            }
        });
    }, observerOptions);
    
    // Observe elements for animation
    const animatedElements = document.querySelectorAll('.feature-card, .timeline-item, .about-feature');
    animatedElements.forEach(el => {
        observer.observe(el);
    });
    
    // Auto-update stats (demo)
    function updateStats() {
        const statNumbers = document.querySelectorAll('.stat-number');
        statNumbers.forEach(stat => {
            if (stat.textContent.includes('%')) {
                // Animate percentage
                const target = parseInt(stat.textContent);
                animateNumber(stat, 0, target, 2000, '%');
            }
        });
    }
    
    function animateNumber(element, start, end, duration, suffix = '') {
        const range = end - start;
        const increment = range / (duration / 16);
        let current = start;
        
        const timer = setInterval(() => {
            current += increment;
            if (current >= end) {
                current = end;
                clearInterval(timer);
            }
            element.textContent = Math.floor(current) + suffix;
        }, 16);
    }
    
    // Initialize stats animation when page loads
    setTimeout(updateStats, 1000);
    
    // Real-time clock for emergency reports
    function updateTime() {
        const timeElements = document.querySelectorAll('.current-time');
        const now = new Date();
        const timeString = now.toLocaleTimeString('en-US', {
            hour12: false,
            hour: '2-digit',
            minute: '2-digit'
        });
        timeElements.forEach(el => el.textContent = timeString);
    }
    
    setInterval(updateTime, 1000);
    updateTime();
    
    // Phone number formatting
    const phoneInputs = document.querySelectorAll('input[type="tel"]');
    phoneInputs.forEach(input => {
        input.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.startsWith('63')) {
                value = '+' + value;
            } else if (value.startsWith('9') && value.length <= 10) {
                value = '+63' + value;
            }
            e.target.value = value;
        });
    });
    
    // Initialize tooltips (if needed)
    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    tooltipElements.forEach(el => {
        el.addEventListener('mouseenter', function() {
            // Tooltip implementation can be added here
        });
    });
    
    console.log('iMSafe System initialized successfully!');
});

// Additional CSS for animations and modal
const additionalStyles = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    .animate-in {
        animation: fadeInUp 0.6s ease-out;
    }
    
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .notification-content {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .notification-close {
        background: none;
        border: none;
        color: white;
        cursor: pointer;
        padding: 0.25rem;
        border-radius: 0.25rem;
        margin-left: auto;
    }
    
    .notification-close:hover {
        background: rgba(255, 255, 255, 0.2);
    }
    
    .modal-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(5px);
    }
    
    .modal-content {
        position: relative;
        background: white;
        border-radius: 1rem;
        max-width: 500px;
        width: 90%;
        max-height: 80vh;
        overflow-y: auto;
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
    }
    
    .modal-header {
        padding: 1.5rem 2rem 1rem;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .modal-header h3 {
        margin: 0;
        color: #1f2937;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .modal-close {
        background: none;
        border: none;
        color: #6b7280;
        cursor: pointer;
        padding: 0.5rem;
        border-radius: 0.375rem;
        font-size: 1.25rem;
    }
    
    .modal-close:hover {
        background: #f3f4f6;
        color: #374151;
    }
    
    .modal-body {
        padding: 1rem 2rem;
    }
    
    .tracking-id {
        background: #f8fafc;
        padding: 1rem;
        border-radius: 0.5rem;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .tracking-id code {
        background: #e2e8f0;
        padding: 0.25rem 0.5rem;
        border-radius: 0.25rem;
        font-family: 'Courier New', monospace;
        font-weight: bold;
        color: #1e293b;
    }
    
    .copy-btn {
        background: #3b82f6;
        border: none;
        color: white;
        padding: 0.375rem 0.75rem;
        border-radius: 0.375rem;
        cursor: pointer;
        font-size: 0.875rem;
    }
    
    .copy-btn:hover {
        background: #2563eb;
    }
    
    .report-summary {
        margin-bottom: 1.5rem;
    }
    
    .report-summary p {
        margin-bottom: 0.5rem;
        color: #374151;
    }
    
    .response-timeline {
        margin-bottom: 1.5rem;
    }
    
    .response-timeline h4 {
        margin-bottom: 1rem;
        color: #1f2937;
    }
    
    .response-timeline .timeline-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 0.75rem;
        color: #374151;
    }
    
    .response-timeline .timeline-item i {
        color: #3b82f6;
        width: 20px;
    }
    
    .emergency-contacts h4 {
        margin-bottom: 1rem;
        color: #1f2937;
    }
    
    .emergency-contacts p {
        margin-bottom: 0.5rem;
        color: #374151;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .emergency-contacts i {
        color: #ef4444;
        width: 16px;
    }
    
    .modal-footer {
        padding: 1rem 2rem 1.5rem;
        border-top: 1px solid #e5e7eb;
        text-align: center;
    }
`;

// Inject additional styles
const styleSheet = document.createElement('style');
styleSheet.textContent = additionalStyles;
document.head.appendChild(styleSheet);

// =====================================================
// DATABASE CONNECTION FUNCTIONS
// =====================================================

/**
 * Load disaster types from database
 */
async function loadDisasterTypes() {
    try {
        const response = await fetch(API_BASE_URL + 'get_disaster_types.php');
        const result = await response.json();
        
        if (result.success && result.data) {
            const disasterTypeSelect = document.getElementById('disaster_type');
            if (disasterTypeSelect) {
                // Clear existing options except the first one
                while (disasterTypeSelect.children.length > 1) {
                    disasterTypeSelect.removeChild(disasterTypeSelect.lastChild);
                }
                
                // Add disaster types from database
                result.data.forEach(type => {
                    const option = document.createElement('option');
                    option.value = type.type_id;
                    option.textContent = type.type_name;
                    disasterTypeSelect.appendChild(option);
                });
            }
        }
    } catch (error) {
        console.error('Error loading disaster types:', error);
        // Fallback to default options if database fails
    }
}

/**
 * Submit emergency report to database
 */
async function submitEmergencyReport(reportData) {
    try {
        // Map form data to database format
        const dbData = {
            disaster_type: reportData.disaster_type,
            disaster_name: reportData.description.substring(0, 100), // First 100 chars as name
            severity_level: reportData.severity,
            address: reportData.location,
            city: extractCityFromAddress(reportData.location),
            state: 'Philippines',
            reporter_name: reportData.reporter_name,
            reporter_phone: reportData.phone,
            alternate_contact: reportData.alternate_contact,
            landmark: reportData.landmark,
            people_affected: reportData.people_affected,
            current_situation: reportData.current_situation,
            description: reportData.description,
            immediate_needs: reportData.immediate_needs,
            latitude: null, // Can be added with geolocation
            longitude: null
        };
        
        const response = await fetch(API_BASE_URL + 'submit_report.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(dbData)
        });
        
        const result = await response.json();
        
        if (!response.ok) {
            throw new Error(result.message || 'Network error');
        }
        
        return result;
        
    } catch (error) {
        console.error('Database submission error:', error);
        throw error;
    }
}

/**
 * Extract city from address string (simple implementation)
 */
function extractCityFromAddress(address) {
    // Simple city extraction - look for common Philippine city indicators
    const cityPatterns = [
        /(\w+)\s+City/i,
        /City\s+of\s+(\w+)/i,
        /(\w+)\s*,\s*\w+$/i
    ];
    
    for (const pattern of cityPatterns) {
        const match = address.match(pattern);
        if (match) {
            return match[1];
        }
    }
    
    // Default fallback
    return address.split(',')[0].trim();
}