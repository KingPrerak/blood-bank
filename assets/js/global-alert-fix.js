/**
 * Global Alert Fix
 * Overrides all alert function conflicts and provides a universal showAlert function
 */

(function() {
    'use strict';
    
    // Store original console methods
    const originalError = console.error;
    const originalLog = console.log;
    
    // Enhanced error handling
    window.addEventListener('error', function(event) {
        if (event.message && event.message.includes('Cannot read properties of null')) {
            console.log('Caught and handled null property error:', event.message);
            event.preventDefault();
            return true;
        }
    });
    
    // Universal showAlert function that works everywhere
    function universalShowAlert(type, message, container) {
        try {
            // Determine alert class
            const alertClass = type === 'success' ? 'alert-success' :
                              type === 'error' ? 'alert-danger' :
                              type === 'warning' ? 'alert-warning' : 'alert-info';
            
            // Determine icon
            const icon = type === 'error' ? 'fas fa-exclamation-triangle' :
                        type === 'success' ? 'fas fa-check-circle' :
                        type === 'warning' ? 'fas fa-exclamation-circle' : 'fas fa-info-circle';
            
            // Create alert HTML
            const alertHtml = `
                <div class="alert ${alertClass} alert-dismissible fade show" role="alert" style="margin-bottom: 15px;">
                    <i class="${icon} me-2"></i>${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
            
            // Remove existing alerts safely
            try {
                const existingAlerts = document.querySelectorAll('.alert');
                existingAlerts.forEach(alert => {
                    if (alert && alert.parentNode) {
                        alert.remove();
                    }
                });
            } catch (e) {
                // Ignore errors when removing alerts
            }
            
            // Determine target container
            let targetElement = null;
            
            if (container) {
                targetElement = document.querySelector(container);
            }
            
            if (!targetElement) {
                // Try common containers in order of preference
                const containers = [
                    '#main-content',
                    '.container-fluid',
                    '.container',
                    'main',
                    'body'
                ];
                
                for (const containerSelector of containers) {
                    targetElement = document.querySelector(containerSelector);
                    if (targetElement) break;
                }
            }
            
            // Add new alert
            if (targetElement) {
                targetElement.insertAdjacentHTML('afterbegin', alertHtml);
            } else {
                // Fallback: create a temporary container
                const tempContainer = document.createElement('div');
                tempContainer.style.position = 'fixed';
                tempContainer.style.top = '20px';
                tempContainer.style.right = '20px';
                tempContainer.style.zIndex = '9999';
                tempContainer.style.maxWidth = '400px';
                tempContainer.innerHTML = alertHtml;
                document.body.appendChild(tempContainer);
                
                // Remove temp container after alert is dismissed
                setTimeout(() => {
                    if (tempContainer && tempContainer.parentNode) {
                        tempContainer.remove();
                    }
                }, 6000);
            }
            
            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                try {
                    const alertToRemove = document.querySelector('.alert');
                    if (alertToRemove && alertToRemove.parentNode) {
                        // Fade out effect
                        alertToRemove.style.transition = 'opacity 0.3s ease';
                        alertToRemove.style.opacity = '0';
                        
                        setTimeout(() => {
                            if (alertToRemove && alertToRemove.parentNode) {
                                alertToRemove.remove();
                            }
                        }, 300);
                    }
                } catch (e) {
                    // Ignore errors during auto-dismiss
                }
            }, 5000);
            
            // Log success
            console.log('Alert displayed successfully:', type, message);
            
        } catch (error) {
            // Fallback to console if all else fails
            console.error('Alert system error:', error);
            console.log('Fallback alert:', type, message);
            
            // Try simple browser alert as last resort
            try {
                alert(`${type.toUpperCase()}: ${message}`);
            } catch (e) {
                // Even browser alert failed, just log
                console.log('All alert methods failed. Message:', type, message);
            }
        }
    }
    
    // Override global showAlert function
    window.showAlert = universalShowAlert;
    
    // Also create a backup function
    window.safeShowAlert = universalShowAlert;
    
    // Override jQuery alert method to prevent errors
    if (window.jQuery) {
        const originalJQueryAlert = jQuery.fn.alert;
        jQuery.fn.alert = function(action) {
            try {
                if (originalJQueryAlert && typeof originalJQueryAlert === 'function') {
                    return originalJQueryAlert.call(this, action);
                }
            } catch (e) {
                // If Bootstrap alert fails, just remove the element
                if (action === 'close') {
                    this.each(function() {
                        if (this && this.parentNode) {
                            this.remove();
                        }
                    });
                }
            }
            return this;
        };
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Global alert fix initialized');
        });
    } else {
        console.log('Global alert fix initialized');
    }
    
    // Export for module systems
    if (typeof module !== 'undefined' && module.exports) {
        module.exports = universalShowAlert;
    }
    
})();
