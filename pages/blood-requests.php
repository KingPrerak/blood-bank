<?php
require_once '../config/config.php';
requireLogin();

// Check if this page is being loaded directly (not via AJAX)
$isDirectAccess = !isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest';
if ($isDirectAccess) {
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Blood Requests - Blood Bank Management</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
        <style>
            body { padding: 20px; background-color: #f8f9fa; }
            .alert { margin: 10px 0; }
        </style>
    </head>
    <body>
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-12">
                <h2><i class="fas fa-hand-holding-medical me-2"></i>Blood Requests</h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Blood Requests</li>
                    </ol>
                </nav>
            </div>
        </div>

        <!-- jQuery and Bootstrap JS for direct access -->
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>';
}

// Get blood groups for dropdown
try {
    $db = getDB();
    $stmt = $db->query("SELECT id, blood_group FROM blood_groups ORDER BY blood_group");
    $bloodGroups = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Blood requests error: " . $e->getMessage());
    $bloodGroups = [];
}
?>

<div class="row mb-4">
    <div class="col-12">
        <h2><i class="fas fa-hand-holding-medical me-2"></i>Blood Requests & Issue</h2>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-plus me-2"></i>New Blood Request</h5>
            </div>
            <div class="card-body">
                <form id="blood-request-form">
                    <!-- Patient Information -->
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label for="patient_name" class="form-label">Patient Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="patient_name" name="patient_name" required>
                        </div>
                        <div class="col-md-4">
                            <label for="patient_age" class="form-label">Age <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="patient_age" name="patient_age" min="1" max="120" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="patient_gender" class="form-label">Gender <span class="text-danger">*</span></label>
                            <select class="form-select" id="patient_gender" name="patient_gender" required>
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="blood_group_id" class="form-label">Blood Group <span class="text-danger">*</span></label>
                            <select class="form-select" id="blood_group_id" name="blood_group_id" required>
                                <option value="">Select Blood Group</option>
                                <?php foreach ($bloodGroups as $group): ?>
                                <option value="<?php echo $group['id']; ?>"><?php echo $group['blood_group']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Blood Requirement -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="component_type" class="form-label">Component Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="component_type" name="component_type" required>
                                <option value="">Select Component</option>
                                <option value="Whole Blood">Whole Blood</option>
                                <option value="Red Blood Cells">Red Blood Cells</option>
                                <option value="Plasma">Plasma</option>
                                <option value="Platelets">Platelets</option>
                                <option value="Cryoprecipitate">Cryoprecipitate</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="units_required" class="form-label">Units Required <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="units_required" name="units_required" min="1" max="10" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="urgency" class="form-label">Urgency <span class="text-danger">*</span></label>
                            <select class="form-select" id="urgency" name="urgency" required>
                                <option value="routine">Routine</option>
                                <option value="urgent">Urgent</option>
                                <option value="emergency">Emergency</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="required_date" class="form-label">Required Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="required_date" name="required_date" 
                                   min="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>

                    <!-- Hospital Information -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="hospital_name" class="form-label">Hospital Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="hospital_name" name="hospital_name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="doctor_name" class="form-label">Doctor Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="doctor_name" name="doctor_name" required>
                        </div>
                    </div>

                    <!-- Contact Information -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="contact_person" class="form-label">Contact Person <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="contact_person" name="contact_person" required>
                        </div>
                        <div class="col-md-6">
                            <label for="contact_phone" class="form-label">Contact Phone <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control" id="contact_phone" name="contact_phone" 
                                   pattern="[0-9]{10}" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="purpose" class="form-label">Purpose/Indication</label>
                        <textarea class="form-control" id="purpose" name="purpose" rows="2" 
                                  placeholder="Medical indication for blood requirement"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-save me-2"></i>Submit Request
                            </button>
                            <button type="reset" class="btn btn-secondary">
                                <i class="fas fa-undo me-2"></i>Reset
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-tint me-2"></i>Quick Issue</h5>
            </div>
            <div class="card-body">
                <form id="quick-issue-form">
                    <div class="row mb-3">
                        <div class="col-12">
                            <label for="request_select" class="form-label">Select Pending Request</label>
                            <select class="form-select" id="request_select" name="request_id">
                                <option value="">Choose a request to issue blood...</option>
                            </select>
                        </div>
                    </div>

                    <div id="request-details" class="d-none mb-3">
                        <div class="alert alert-info">
                            <h6>Request Details:</h6>
                            <div id="request-info"></div>
                        </div>
                    </div>

                    <div id="available-bags" class="d-none mb-3">
                        <label class="form-label">Available Blood Bags:</label>
                        <div id="bags-list"></div>
                    </div>

                    <div class="row mb-3 d-none" id="issue-details">
                        <div class="col-md-6">
                            <label for="received_by" class="form-label">Received By <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="received_by" name="received_by">
                        </div>
                        <div class="col-md-6">
                            <label for="issue_purpose" class="form-label">Issue Purpose</label>
                            <input type="text" class="form-control" id="issue_purpose" name="purpose">
                        </div>
                    </div>

                    <div class="row d-none" id="issue-actions">
                        <div class="col-12">
                            <button type="submit" class="btn btn-success me-2">
                                <i class="fas fa-check me-2"></i>Issue Blood
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="resetIssueForm()">
                                <i class="fas fa-undo me-2"></i>Reset
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <h5><i class="fas fa-chart-pie me-2"></i>Blood Availability</h5>
            </div>
            <div class="card-body" id="blood-availability">
                <!-- Blood availability will be loaded here -->
            </div>
        </div>
    </div>
</div>

<!-- Pending Requests Table -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-list me-2"></i>Pending Blood Requests</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped" id="requests-table">
                        <thead>
                            <tr>
                                <th>Request ID</th>
                                <th>Patient</th>
                                <th>Blood Group</th>
                                <th>Component</th>
                                <th>Units</th>
                                <th>Urgency</th>
                                <th>Required Date</th>
                                <th>Hospital</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="requests-tbody">
                            <!-- Requests will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    loadPendingRequests();
    loadBloodAvailability();
    
    // Set default required date to today
    $('#required_date').val(new Date().toISOString().split('T')[0]);
});

// Handle blood request form submission
$('#blood-request-form').on('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    $.ajax({
        url: '../ajax/submit_blood_request.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                showAlert('success', response.message);
                $('#blood-request-form')[0].reset();
                $('#required_date').val(new Date().toISOString().split('T')[0]);
                loadPendingRequests();
                loadBloodAvailability();
            } else {
                showAlert('error', response.message);
            }
        }
    });
});

// Handle request selection for quick issue
$('#request_select').on('change', function() {
    const requestId = $(this).val();
    if (requestId) {
        loadRequestDetails(requestId);
    } else {
        $('#request-details, #available-bags, #issue-details, #issue-actions').addClass('d-none');
    }
});

// Handle quick issue form submission
$('#quick-issue-form').on('submit', function(e) {
    e.preventDefault();
    
    const selectedBags = [];
    $('input[name="selected_bags[]"]:checked').each(function() {
        selectedBags.push($(this).val());
    });
    
    if (selectedBags.length === 0) {
        showAlert('warning', 'Please select at least one blood bag to issue.');
        return;
    }
    
    const formData = new FormData(this);
    selectedBags.forEach(bagId => {
        formData.append('selected_bags[]', bagId);
    });
    
    $.ajax({
        url: '../ajax/issue_blood.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                showAlert('success', response.message);
                resetIssueForm();
                loadPendingRequests();
                loadBloodAvailability();
            } else {
                showAlert('error', response.message);
            }
        }
    });
});

function loadPendingRequests() {
    $.ajax({
        url: '../ajax/get_blood_requests.php',
        type: 'GET',
        success: function(response) {
            $('#requests-tbody').html(response.table_rows);
            
            // Update request select dropdown
            let options = '<option value="">Choose a request to issue blood...</option>';
            response.requests.forEach(function(request) {
                options += `<option value="${request.id}">${request.request_id} - ${request.patient_name} (${request.blood_group}, ${request.units_required} units)</option>`;
            });
            $('#request_select').html(options);
        }
    });
}

function loadRequestDetails(requestId) {
    $.ajax({
        url: '../ajax/get_request_details.php',
        type: 'GET',
        data: { request_id: requestId },
        success: function(response) {
            if (response.success) {
                $('#request-info').html(response.details_html);
                $('#request-details').removeClass('d-none');
                
                if (response.available_bags.length > 0) {
                    let bagsHtml = '';
                    response.available_bags.forEach(function(bag) {
                        bagsHtml += `
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="selected_bags[]" value="${bag.id}" id="bag_${bag.id}">
                                <label class="form-check-label" for="bag_${bag.id}">
                                    ${bag.bag_number} - ${bag.component_type} (${bag.volume_ml}ml) - Exp: ${bag.expiry_date}
                                </label>
                            </div>
                        `;
                    });
                    $('#bags-list').html(bagsHtml);
                    $('#available-bags').removeClass('d-none');
                    $('#issue-details, #issue-actions').removeClass('d-none');
                } else {
                    $('#bags-list').html('<p class="text-warning">No compatible blood bags available.</p>');
                    $('#available-bags').removeClass('d-none');
                    $('#issue-details, #issue-actions').addClass('d-none');
                }
            }
        }
    });
}

function loadBloodAvailability() {
    $.ajax({
        url: '../ajax/get_blood_availability.php',
        type: 'GET',
        success: function(response) {
            $('#blood-availability').html(response);
        }
    });
}

function resetIssueForm() {
    $('#quick-issue-form')[0].reset();
    $('#request-details, #available-bags, #issue-details, #issue-actions').addClass('d-none');
}

function approveRequest(requestId) {
    if (confirm('Are you sure you want to approve this request?')) {
        $.ajax({
            url: '../ajax/approve_request.php',
            type: 'POST',
            data: { request_id: requestId },
            success: function(response) {
                if (response.success) {
                    showAlert('success', response.message);
                    loadPendingRequests();
                } else {
                    showAlert('error', response.message);
                }
            }
        });
    }
}

function cancelRequest(requestId) {
    const reason = prompt('Please enter the reason for cancellation:');
    if (reason) {
        $.ajax({
            url: '../ajax/cancel_request.php',
            type: 'POST',
            data: { request_id: requestId, reason: reason },
            success: function(response) {
                if (response.success) {
                    showAlert('success', response.message);
                    loadPendingRequests();
                } else {
                    showAlert('error', response.message);
                }
            }
        });
    }
}
</script>

<?php if ($isDirectAccess): ?>
<script>
// Alert function for direct access
function showAlert(type, message) {
    try {
        const alertClass = type === 'success' ? 'alert-success' :
                          type === 'error' ? 'alert-danger' :
                          type === 'warning' ? 'alert-warning' : 'alert-info';

        const alertHtml = `<div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>`;

        // Remove existing alerts safely
        try {
            document.querySelectorAll('.alert').forEach(alert => alert.remove());
        } catch (e) {
            console.log('No existing alerts to remove');
        }

        // Add new alert at the top
        const targetElement = document.querySelector('body') || document.querySelector('.container');
        if (targetElement) {
            targetElement.insertAdjacentHTML('afterbegin', alertHtml);
        }

        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            try {
                const alertToRemove = document.querySelector('.alert');
                if (alertToRemove) {
                    alertToRemove.remove();
                }
            } catch (e) {
                console.log('Alert already removed');
            }
        }, 5000);

    } catch (error) {
        console.error('Error showing alert:', error);
        console.log('Alert message:', type, message);
    }
}

function loadPage(page) {
    window.location.href = '../dashboard.php#' + page;
}
</script>

    </div>
    </body>
    </html>
<?php endif; ?>
