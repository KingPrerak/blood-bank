/**
 * AJAX Path Resolver
 * Automatically resolves correct AJAX paths for both dashboard and direct access modes
 */

// Function to get the correct AJAX URL based on current context
function getAjaxUrl(endpoint) {
    // Remove any leading slashes or ../
    endpoint = endpoint.replace(/^(\.\.\/|\/)?ajax\//, '');
    
    // Check if we're in a subdirectory (pages/)
    const currentPath = window.location.pathname;
    const isInPagesDir = currentPath.includes('/pages/');
    
    // Return appropriate path
    if (isInPagesDir) {
        return '../ajax/' + endpoint;
    } else {
        return 'ajax/' + endpoint;
    }
}

// Override jQuery ajax to automatically fix URLs
(function($) {
    const originalAjax = $.ajax;
    
    $.ajax = function(options) {
        // If URL contains ajax/, fix the path
        if (options.url && options.url.includes('ajax/')) {
            options.url = getAjaxUrl(options.url);
        }
        
        return originalAjax.call(this, options);
    };
})(jQuery);

// Helper function for consistent AJAX calls
function makeAjaxCall(endpoint, options = {}) {
    const defaultOptions = {
        url: getAjaxUrl(endpoint),
        type: 'GET',
        dataType: 'json',
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            if (typeof showAlert === 'function') {
                showAlert('error', 'An error occurred while processing your request.');
            }
        }
    };
    
    return $.ajax($.extend(defaultOptions, options));
}

// Export for global use
window.getAjaxUrl = getAjaxUrl;
window.makeAjaxCall = makeAjaxCall;
