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
        <title>Donor Registration - Blood Bank Management</title>
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
                <h2><i class="fas fa-user-plus me-2"></i>Donor Registration</h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Donor Registration</li>
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
    
    // Get pending blood requests for replacement donations
    $stmt = $db->query("
        SELECT br.id, br.request_id, br.patient_name, bg.blood_group, br.units_required, br.hospital_name
        FROM blood_requests br 
        JOIN blood_groups bg ON br.blood_group_id = bg.id 
        WHERE br.status = 'pending' 
        ORDER BY br.created_at DESC
    ");
    $pendingRequests = $stmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Donor registration error: " . $e->getMessage());
    $bloodGroups = [];
    $pendingRequests = [];
}
?>

<div class="row mb-4">
    <div class="col-12">
        <h2><i class="fas fa-user-plus me-2"></i>Donor Registration</h2>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-user me-2"></i>Donor Information</h5>
            </div>
            <div class="card-body">
                <form id="donor-registration-form">
                    <!-- Donation Type Selection -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <label class="form-label fw-bold">Donation Type <span class="text-danger">*</span></label>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="donation_type" id="voluntary" value="voluntary" checked>
                                        <label class="form-check-label" for="voluntary">
                                            <i class="fas fa-heart text-success me-2"></i>Voluntary Donation
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="donation_type" id="replacement" value="replacement">
                                        <label class="form-check-label" for="replacement">
                                            <i class="fas fa-exchange-alt text-warning me-2"></i>Replacement Donation
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Replacement Request Selection (Hidden by default) -->
                    <div id="replacement-section" class="d-none mb-3">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Please select the blood request this donation is replacing:
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <label for="replacement_request_id" class="form-label">Select Blood Request</label>
                                <select class="form-select" id="replacement_request_id" name="replacement_request_id">
                                    <option value="">Choose a pending request...</option>
                                    <?php foreach ($pendingRequests as $request): ?>
                                    <option value="<?php echo $request['id']; ?>">
                                        <?php echo $request['request_id']; ?> - <?php echo $request['patient_name']; ?> 
                                        (<?php echo $request['blood_group']; ?>, <?php echo $request['units_required']; ?> units) 
                                        - <?php echo $request['hospital_name']; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Personal Information -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="first_name" name="first_name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="last_name" name="last_name" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="date_of_birth" class="form-label">Date of Birth <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" required>
                        </div>
                        <div class="col-md-4">
                            <label for="gender" class="form-label">Gender <span class="text-danger">*</span></label>
                            <select class="form-select" id="gender" name="gender" required>
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="blood_group_id" class="form-label">Blood Group <span class="text-danger">*</span></label>
                            <select class="form-select" id="blood_group_id" name="blood_group_id" required>
                                <option value="">Select Blood Group</option>
                                <?php foreach ($bloodGroups as $group): ?>
                                <option value="<?php echo $group['id']; ?>"><?php echo $group['blood_group']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Contact Information -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control" id="phone" name="phone" pattern="[0-9]{10}" required>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email">
                        </div>
                    </div>

                    <!-- Address Information -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <label for="address" class="form-label">Address <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="address" name="address" rows="2" required></textarea>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="city" class="form-label">City <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="city" name="city" required>
                        </div>
                        <div class="col-md-4">
                            <label for="state" class="form-label">State <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="state" name="state" required>
                        </div>
                        <div class="col-md-4">
                            <label for="pincode" class="form-label">Pincode <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="pincode" name="pincode" pattern="[0-9]{6}" required>
                        </div>
                    </div>

                    <!-- Emergency Contact -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="emergency_contact_name" class="form-label">Emergency Contact Name</label>
                            <input type="text" class="form-control" id="emergency_contact_name" name="emergency_contact_name">
                        </div>
                        <div class="col-md-6">
                            <label for="emergency_contact_phone" class="form-label">Emergency Contact Phone</label>
                            <input type="tel" class="form-control" id="emergency_contact_phone" name="emergency_contact_phone" pattern="[0-9]{10}">
                        </div>
                    </div>

                    <!-- Medical History -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <label for="medical_history" class="form-label">Medical History</label>
                            <textarea class="form-control" id="medical_history" name="medical_history" rows="3" 
                                      placeholder="Any significant medical conditions, medications, allergies, etc."></textarea>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="row">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-save me-2"></i>Register Donor
                            </button>
                            <button type="button" class="btn btn-success me-2" id="register-and-donate-btn">
                                <i class="fas fa-tint me-2"></i>Register & Donate Now
                            </button>
                            <button type="reset" class="btn btn-secondary">
                                <i class="fas fa-undo me-2"></i>Reset Form
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-info-circle me-2"></i>Donation Guidelines</h5>
            </div>
            <div class="card-body">
                <h6 class="text-primary">Eligibility Criteria:</h6>
                <ul class="list-unstyled">
                    <li><i class="fas fa-check text-success me-2"></i>Age: 18-65 years</li>
                    <li><i class="fas fa-check text-success me-2"></i>Weight: Minimum 50 kg</li>
                    <li><i class="fas fa-check text-success me-2"></i>Hemoglobin: Male ≥12.5g/dl, Female ≥12.0g/dl</li>
                    <li><i class="fas fa-check text-success me-2"></i>Good general health</li>
                    <li><i class="fas fa-check text-success me-2"></i>No donation in last 90 days</li>
                </ul>

                <h6 class="text-warning mt-3">Temporary Deferral:</h6>
                <ul class="list-unstyled">
                    <li><i class="fas fa-times text-warning me-2"></i>Recent illness or fever</li>
                    <li><i class="fas fa-times text-warning me-2"></i>Recent vaccination</li>
                    <li><i class="fas fa-times text-warning me-2"></i>Recent surgery</li>
                    <li><i class="fas fa-times text-warning me-2"></i>Pregnancy/Lactation</li>
                </ul>

                <div class="alert alert-info mt-3">
                    <i class="fas fa-lightbulb me-2"></i>
                    <strong>Tip:</strong> Ensure you have had a good meal and adequate rest before donation.
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Handle donation type change
    $('input[name="donation_type"]').on('change', function() {
        if ($(this).val() === 'replacement') {
            $('#replacement-section').removeClass('d-none');
            $('#replacement_request_id').prop('required', true);
        } else {
            $('#replacement-section').addClass('d-none');
            $('#replacement_request_id').prop('required', false);
        }
    });

    // Handle "Register & Donate Now" button
    $('#register-and-donate-btn').on('click', function() {
        // Set a flag to indicate immediate donation
        $('#donor-registration-form').data('immediate-donation', true);
        $('#donor-registration-form').submit();
    });

    // Handle form submission
    $('#donor-registration-form').on('submit', function(e) {
        e.preventDefault();

        // Validate form
        if (!this.checkValidity()) {
            e.stopPropagation();
            $(this).addClass('was-validated');
            return;
        }

        // Submit form data
        const formData = new FormData(this);
        
        $.ajax({
            url: '../ajax/register_donor.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showAlert('success', response.message);

                    // Check donation type and redirect accordingly
                    const donationType = $('input[name="donation_type"]:checked').val();
                    const immediateDonation = $('#donor-registration-form').data('immediate-donation');

                    if (immediateDonation) {
                        // Store donor info for immediate blood collection
                        sessionStorage.setItem('selectedDonor', JSON.stringify({
                            id: response.database_id,
                            donor_id: response.donor_id,
                            full_name: $('#first_name').val() + ' ' + $('#last_name').val(),
                            can_donate: true
                        }));

                        // Redirect to blood collection immediately
                        setTimeout(function() {
                            loadPage('blood-collection');
                        }, 1500);
                    } else if (donationType === 'replacement') {
                        // Redirect to blood issue page for replacement
                        setTimeout(function() {
                            loadPage('blood-issue');
                        }, 2000);
                    } else {
                        // Redirect to blood collection page for voluntary
                        setTimeout(function() {
                            loadPage('blood-collection');
                        }, 2000);
                    }

                    // Reset form
                    $('#donor-registration-form')[0].reset();
                    $('#replacement-section').addClass('d-none');
                    $('#donor-registration-form').removeData('immediate-donation');
                } else {
                    showAlert('error', response.message);
                }
            },
            error: function() {
                showAlert('error', 'An error occurred while registering the donor.');
            }
        });
    });
});
</script>

<?php if ($isDirectAccess): ?>
<!-- jQuery and Bootstrap JS for direct access -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

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

// Load page function for direct access
function loadPage(page) {
    window.location.href = '../dashboard.php#' + page;
}
</script>

    </div>
    </body>
    </html>
<?php endif; ?>
