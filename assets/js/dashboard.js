// Blood Bank Management System - Dashboard JavaScript

$(document).ready(function() {
    // Initialize dashboard
    initializeDashboard();
});

function initializeDashboard() {
    // Set up AJAX defaults
    $.ajaxSetup({
        beforeSend: function() {
            showLoading();
        },
        complete: function() {
            hideLoading();
        },
        error: function(xhr, status, error) {
            hideLoading();
            showAlert('error', 'An error occurred: ' + error);
        }
    });
    
    // Update active nav link
    updateActiveNavLink('dashboard-home');
}

function loadPage(page) {
    // Update active navigation
    updateActiveNavLink(page);

    // Special handling for dashboard home
    if (page === 'dashboard-home') {
        // Reload the current page to show dashboard home
        location.reload();
        return;
    }

    // Load page content via AJAX
    $.ajax({
        url: 'pages/' + page + '.php',
        type: 'GET',
        success: function(response) {
            $('#main-content').html(response).addClass('fade-in');

            // Initialize page-specific functionality
            initializePageFunctions(page);
        },
        error: function(xhr, status, error) {
            console.error('Failed to load page:', page, 'Error:', error);
            showAlert('error', 'Failed to load page content: ' + page);
        }
    });
}

function updateActiveNavLink(page) {
    // Remove active class from all nav links
    $('.nav-link').removeClass('active');
    
    // Add active class to current page link
    $('.nav-link[onclick*="' + page + '"]').addClass('active');
}

function initializePageFunctions(page) {
    switch(page) {
        case 'donor-registration':
            initializeDonorRegistration();
            break;
        case 'blood-collection':
            initializeBloodCollection();
            break;
        case 'blood-requests':
            initializeBloodRequests();
            break;
        case 'inventory':
            initializeInventory();
            break;
        case 'reports':
            initializeReports();
            break;
    }
}

function showLoading() {
    $('#loading-overlay').removeClass('d-none');
}

function hideLoading() {
    $('#loading-overlay').addClass('d-none');
}

function showAlert(type, message, container = '#main-content') {
    try {
        const alertClass = type === 'error' ? 'alert-danger' :
                          type === 'success' ? 'alert-success' :
                          type === 'warning' ? 'alert-warning' : 'alert-info';

        const icon = type === 'error' ? 'fas fa-exclamation-triangle' :
                    type === 'success' ? 'fas fa-check-circle' :
                    type === 'warning' ? 'fas fa-exclamation-circle' : 'fas fa-info-circle';

        const alertHtml = `
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                <i class="${icon} me-2"></i>${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;

        // Remove existing alerts first
        try {
            $(container + ' .alert').remove();
        } catch (e) {
            console.log('No existing alerts to remove');
        }

        // Add new alert
        if ($(container).length > 0) {
            $(container).prepend(alertHtml);
        } else {
            // Fallback to body if container doesn't exist
            $('body').prepend(alertHtml);
        }

        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            try {
                // Use remove() instead of alert('close') to avoid Bootstrap dependency issues
                $('.alert').fadeOut(300, function() {
                    $(this).remove();
                });
            } catch (e) {
                // Fallback to simple remove
                $('.alert').remove();
            }
        }, 5000);

    } catch (error) {
        console.error('Error showing alert:', error);
        console.log('Alert message:', type, message);
    }
}

function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-IN');
}

function formatDateTime(dateTimeString) {
    const date = new Date(dateTimeString);
    return date.toLocaleString('en-IN');
}

function validateForm(formId) {
    const form = document.getElementById(formId);
    return form.checkValidity();
}

function resetForm(formId) {
    document.getElementById(formId).reset();
}

// Donor Registration Functions
function initializeDonorRegistration() {
    // Initialize donor search if element exists
    if ($('#donor-search').length) {
        $('#donor-search').on('input', function() {
            searchDonors($(this).val());
        });
    }

    // Initialize donation type change handler if element exists
    if ($('input[name="donation_type"]').length) {
        $('input[name="donation_type"]').on('change', function() {
            handleDonationTypeChange($(this).val());
        });
    }
}

function searchDonors(query) {
    if (query.length < 3) return;

    $.ajax({
        url: 'ajax/search_donors.php',
        type: 'GET',
        data: { query: query },
        success: function(response) {
            displayDonorSearchResults(response);
        },
        error: function() {
            console.log('Search donors endpoint not available');
        }
    });
}

function handleDonationTypeChange(type) {
    if (type === 'replacement') {
        $('#replacement-section').removeClass('d-none');
        loadPendingRequests();
    } else {
        $('#replacement-section').addClass('d-none');
    }
}

function loadPendingRequests() {
    if ($('#replacement-requests').length) {
        $.ajax({
            url: 'ajax/get_pending_requests.php',
            type: 'GET',
            success: function(response) {
                $('#replacement-requests').html(response);
            },
            error: function() {
                console.log('Pending requests endpoint not available');
            }
        });
    }
}

// Blood Collection Functions
function initializeBloodCollection() {
    // Initialize donor lookup
    $('#donor-lookup').on('input', function() {
        lookupDonor($(this).val());
    });
    
    // Initialize medical screening form
    initializeMedicalScreening();
}

function lookupDonor(donorId) {
    if (donorId.length < 3) return;
    
    $.ajax({
        url: 'ajax/lookup_donor.php',
        type: 'GET',
        data: { donor_id: donorId },
        success: function(response) {
            if (response.success) {
                populateDonorInfo(response.donor);
            } else {
                clearDonorInfo();
            }
        }
    });
}

function populateDonorInfo(donor) {
    $('#donor-name').text(donor.full_name);
    $('#donor-blood-group').text(donor.blood_group);
    $('#donor-last-donation').text(donor.last_donation_date || 'Never');
    $('#donor-info-section').removeClass('d-none');
}

function clearDonorInfo() {
    $('#donor-info-section').addClass('d-none');
}

function initializeMedicalScreening() {
    // Add medical screening validation
    $('#medical-screening-form').on('submit', function(e) {
        e.preventDefault();
        processMedicalScreening();
    });
}

function processMedicalScreening() {
    const formData = new FormData($('#medical-screening-form')[0]);
    
    $.ajax({
        url: 'ajax/process_medical_screening.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                if (response.eligible) {
                    proceedToCollection();
                } else {
                    showDeferralMessage(response.reason);
                }
            } else {
                showAlert('error', response.message);
            }
        }
    });
}

// Blood Requests Functions
function initializeBloodRequests() {
    // Initialize request form
    $('#blood-request-form').on('submit', function(e) {
        e.preventDefault();
        submitBloodRequest();
    });
    
    // Load pending requests
    loadBloodRequests();
}

function submitBloodRequest() {
    const formData = new FormData($('#blood-request-form')[0]);
    
    $.ajax({
        url: 'ajax/submit_blood_request.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                showAlert('success', 'Blood request submitted successfully.');
                resetForm('blood-request-form');
                loadBloodRequests();
            } else {
                showAlert('error', response.message);
            }
        }
    });
}

function loadBloodRequests() {
    $.ajax({
        url: 'ajax/get_blood_requests.php',
        type: 'GET',
        success: function(response) {
            $('#blood-requests-table').html(response);
        }
    });
}

// Inventory Functions
function initializeInventory() {
    loadInventoryData();
    
    // Initialize filters
    $('#blood-group-filter, #status-filter').on('change', function() {
        loadInventoryData();
    });
}

function loadInventoryData() {
    const filters = {
        blood_group: $('#blood-group-filter').val(),
        status: $('#status-filter').val()
    };
    
    $.ajax({
        url: 'ajax/get_inventory.php',
        type: 'GET',
        data: filters,
        success: function(response) {
            $('#inventory-table').html(response);
        }
    });
}

// Reports Functions
function initializeReports() {
    // Initialize date pickers
    $('.date-picker').datepicker({
        format: 'dd-mm-yyyy',
        autoclose: true
    });
    
    // Initialize report generation
    $('.generate-report').on('click', function() {
        const reportType = $(this).data('report');
        generateReport(reportType);
    });
}

function generateReport(type) {
    const dateFrom = $('#date-from').val();
    const dateTo = $('#date-to').val();
    
    if (!dateFrom || !dateTo) {
        showAlert('warning', 'Please select date range for the report.');
        return;
    }
    
    $.ajax({
        url: 'ajax/generate_report.php',
        type: 'POST',
        data: {
            type: type,
            date_from: dateFrom,
            date_to: dateTo
        },
        success: function(response) {
            if (response.success) {
                displayReport(response.data);
            } else {
                showAlert('error', response.message);
            }
        }
    });
}

function displayReport(data) {
    $('#report-content').html(data);
    $('#report-section').removeClass('d-none');
}
