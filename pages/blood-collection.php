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
        <title>Blood Collection - Blood Bank Management</title>
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
                <h2><i class="fas fa-tint me-2"></i>Blood Collection</h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Blood Collection</li>
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
    error_log("Blood collection error: " . $e->getMessage());
    $bloodGroups = [];
}
?>

<div class="row mb-4">
    <div class="col-12">
        <h2><i class="fas fa-tint me-2"></i>Blood Collection</h2>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-search me-2"></i>Donor Lookup</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-8">
                        <label for="donor-lookup" class="form-label">Search Donor</label>
                        <input type="text" class="form-control" id="donor-lookup" 
                               placeholder="Enter Donor ID, Phone Number, or Name">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">&nbsp;</label>
                        <button type="button" class="btn btn-primary d-block w-100" onclick="searchDonor()">
                            <i class="fas fa-search me-2"></i>Search
                        </button>
                    </div>
                </div>
                
                <!-- Donor Information Display -->
                <div id="donor-info-section" class="d-none">
                    <div class="alert alert-success">
                        <h6><i class="fas fa-user me-2"></i>Donor Found & Eligible</h6>
                        <div class="row">
                            <div class="col-md-3">
                                <strong>Name:</strong><br>
                                <span id="donor-name">-</span>
                            </div>
                            <div class="col-md-2">
                                <strong>Blood Group:</strong><br>
                                <span id="donor-blood-group" class="badge bg-danger">-</span>
                            </div>
                            <div class="col-md-2">
                                <strong>Age:</strong><br>
                                <span id="donor-age">-</span>
                            </div>
                            <div class="col-md-3">
                                <strong>Last Donation:</strong><br>
                                <span id="donor-last-donation">-</span>
                            </div>
                            <div class="col-md-2">
                                <strong>Total Donations:</strong><br>
                                <span id="donor-total-donations">-</span>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12">
                                <button type="button" class="btn btn-primary me-2" onclick="proceedToMedicalScreening()">
                                    <i class="fas fa-stethoscope me-2"></i>Proceed to Medical Screening
                                </button>
                                <button type="button" class="btn btn-success me-2" onclick="proceedDirectlyToCollection()">
                                    <i class="fas fa-tint me-2"></i>Skip to Blood Collection
                                </button>
                                <button type="button" class="btn btn-secondary" onclick="clearDonorSelection()">
                                    <i class="fas fa-times me-2"></i>Clear Selection
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Medical Screening Form -->
        <div class="card mt-4" id="medical-screening-card" style="display: none;">
            <div class="card-header">
                <h5><i class="fas fa-stethoscope me-2"></i>Medical Screening</h5>
            </div>
            <div class="card-body">
                <form id="medical-screening-form">
                    <input type="hidden" id="selected-donor-id" name="donor_id">
                    
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label for="weight" class="form-label">Weight (kg) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="weight" name="weight" 
                                   min="40" max="150" step="0.1" required>
                        </div>
                        <div class="col-md-3">
                            <label for="hemoglobin" class="form-label">Hemoglobin (g/dl) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="hemoglobin" name="hemoglobin" 
                                   min="8" max="20" step="0.1" required>
                        </div>
                        <div class="col-md-3">
                            <label for="blood_pressure" class="form-label">Blood Pressure <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="blood_pressure" name="blood_pressure" 
                                   placeholder="120/80" required>
                        </div>
                        <div class="col-md-3">
                            <label for="temperature" class="form-label">Temperature (°F) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="temperature" name="temperature" 
                                   min="95" max="105" step="0.1" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="pulse_rate" class="form-label">Pulse Rate (bpm) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="pulse_rate" name="pulse_rate" 
                                   min="50" max="120" required>
                        </div>
                        <div class="col-md-8">
                            <label for="medical_officer" class="form-label">Medical Officer Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="medical_officer" name="medical_officer" required>
                        </div>
                    </div>

                    <!-- Pre-donation Screening Questions -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Pre-donation Screening</label>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="feeling_well" name="screening[]" value="feeling_well">
                                    <label class="form-check-label" for="feeling_well">
                                        Feeling well today
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="adequate_sleep" name="screening[]" value="adequate_sleep">
                                    <label class="form-check-label" for="adequate_sleep">
                                        Had adequate sleep
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="had_meal" name="screening[]" value="had_meal">
                                    <label class="form-check-label" for="had_meal">
                                        Had proper meal
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="no_alcohol" name="screening[]" value="no_alcohol">
                                    <label class="form-check-label" for="no_alcohol">
                                        No alcohol in last 24 hours
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="no_medication" name="screening[]" value="no_medication">
                                    <label class="form-check-label" for="no_medication">
                                        No medication (except vitamins)
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="no_illness" name="screening[]" value="no_illness">
                                    <label class="form-check-label" for="no_illness">
                                        No recent illness/fever
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="pre_donation_notes" class="form-label">Additional Notes</label>
                        <textarea class="form-control" id="pre_donation_notes" name="pre_donation_notes" rows="2"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <button type="submit" class="btn btn-success me-2">
                                <i class="fas fa-check me-2"></i>Approve for Donation
                            </button>
                            <button type="button" class="btn btn-warning me-2" onclick="deferDonor()">
                                <i class="fas fa-clock me-2"></i>Defer Donor
                            </button>
                            <button type="button" class="btn btn-danger" onclick="rejectDonor()">
                                <i class="fas fa-times me-2"></i>Reject Donor
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Blood Collection Form -->
        <div class="card mt-4" id="blood-collection-card" style="display: none;">
            <div class="card-header">
                <h5><i class="fas fa-tint me-2"></i>Blood Collection Details</h5>
            </div>
            <div class="card-body">
                <form id="blood-collection-form">
                    <input type="hidden" id="collection-donor-id" name="donor_id">
                    <input type="hidden" id="donation-id" name="donation_id">
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="bag_number" class="form-label">Bag Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="bag_number" name="bag_number" required>
                        </div>
                        <div class="col-md-4">
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
                        <div class="col-md-4">
                            <label for="volume_ml" class="form-label">Volume (ml) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="volume_ml" name="volume_ml" 
                                   min="200" max="500" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="collection_date" class="form-label">Collection Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="collection_date" name="collection_date" 
                                   value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="storage_location" class="form-label">Storage Location</label>
                            <input type="text" class="form-control" id="storage_location" name="storage_location" 
                                   placeholder="e.g., Refrigerator A1">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="post_donation_instructions" class="form-label">Post-donation Instructions</label>
                        <textarea class="form-control" id="post_donation_instructions" name="post_donation_instructions" rows="3"
                                  placeholder="Instructions given to donor after donation"></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="adverse_reactions" class="form-label">Adverse Reactions (if any)</label>
                        <textarea class="form-control" id="adverse_reactions" name="adverse_reactions" rows="2"
                                  placeholder="Any adverse reactions during or after donation"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <button type="submit" class="btn btn-success me-2">
                                <i class="fas fa-save me-2"></i>Complete Collection
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

    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-info-circle me-2"></i>Collection Guidelines</h5>
            </div>
            <div class="card-body">
                <h6 class="text-primary">Pre-donation Checks:</h6>
                <ul class="list-unstyled">
                    <li><i class="fas fa-check text-success me-2"></i>Verify donor identity</li>
                    <li><i class="fas fa-check text-success me-2"></i>Check eligibility criteria</li>
                    <li><i class="fas fa-check text-success me-2"></i>Medical screening</li>
                    <li><i class="fas fa-check text-success me-2"></i>Consent form signed</li>
                </ul>

                <h6 class="text-warning mt-3">Normal Ranges:</h6>
                <ul class="list-unstyled">
                    <li><strong>Weight:</strong> ≥50 kg</li>
                    <li><strong>Hemoglobin:</strong> M≥12.5, F≥12.0 g/dl</li>
                    <li><strong>BP:</strong> 100-180/60-100 mmHg</li>
                    <li><strong>Temperature:</strong> 97-99°F</li>
                    <li><strong>Pulse:</strong> 60-100 bpm</li>
                </ul>

                <div class="alert alert-warning mt-3">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Important:</strong> Ensure all safety protocols are followed during collection.
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <h5><i class="fas fa-clock me-2"></i>Recent Collections</h5>
            </div>
            <div class="card-body" id="recent-collections">
                <!-- Recent collections will be loaded here -->
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    loadRecentCollections();
    
    // Auto-generate bag number
    generateBagNumber();
});

function searchDonor() {
    const query = $('#donor-lookup').val().trim();
    if (query.length < 2) {
        showAlert('warning', 'Please enter at least 2 characters to search.');
        return;
    }

    // Show loading indicator
    $('#donor-lookup').prop('disabled', true);
    showAlert('info', 'Searching for donors...');

    $.ajax({
        url: '../ajax/search_donor.php',
        type: 'GET',
        data: { query: query },
        success: function(response) {
            console.log('Search response:', response); // Debug log

            // Handle both single donor (response.donor) and multiple donors (response.donors) formats
            if (response.success) {
                if (response.donor) {
                    // Single donor response format
                    const donor = response.donor;
                    displayDonorInfo(donor);
                    showAlert('success', response.message || `Donor found: ${donor.full_name || donor.first_name + ' ' + donor.last_name}`);
                } else if (response.donors && response.donors.length > 0) {
                    // Multiple donors response format
                    if (response.donors.length === 1) {
                        const donor = response.donors[0];
                        displayDonorInfo(donor);

                        if (donor.can_donate) {
                            showAlert('success', `Donor found and eligible for donation: ${donor.full_name}`);
                        } else {
                            showAlert('warning', `Donor found but not eligible: ${donor.reason || 'Unknown reason'}`);
                        }
                    } else {
                        // Multiple donors found, show selection modal
                        showDonorSelectionModal(response.donors);
                        showAlert('info', `Found ${response.donors.length} donors. Please select one.`);
                    }
                } else {
                    showAlert('error', 'No donor data received from server.');
                    clearDonorSelection();
                }
            } else {
                showAlert('error', response.message || 'No donors found matching your search.');
                clearDonorSelection();
            }
        },
        error: function(xhr, status, error) {
            console.error('Search error:', error); // Debug log
            showAlert('error', 'Error searching for donors. Please try again.');
        },
        complete: function() {
            $('#donor-lookup').prop('disabled', false);
        }
    });
}

function showDonorSelectionModal(donors) {
    let modalHtml = `
        <div class="modal fade" id="donorSelectionModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Select Donor</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Donor ID</th>
                                        <th>Name</th>
                                        <th>Blood Group</th>
                                        <th>Phone</th>
                                        <th>Last Donation</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>`;

    donors.forEach(donor => {
        const statusBadge = donor.can_donate ?
            '<span class="badge bg-success">Eligible</span>' :
            '<span class="badge bg-warning">Not Eligible</span>';

        modalHtml += `
            <tr>
                <td><strong>${donor.donor_id}</strong></td>
                <td>${donor.full_name}</td>
                <td><span class="badge bg-danger">${donor.blood_group}</span></td>
                <td>${donor.phone}</td>
                <td>${donor.last_donation_date}</td>
                <td>${statusBadge}</td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="selectDonor(${donor.id}, '${donor.donor_id}', '${donor.full_name}', '${donor.blood_group}', ${donor.age}, '${donor.last_donation_date}', ${donor.total_donations}, ${donor.can_donate})">
                        Select
                    </button>
                </td>
            </tr>`;
    });

    modalHtml += `
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>`;

    // Remove existing modal if any
    $('#donorSelectionModal').remove();

    // Add modal to body and show
    $('body').append(modalHtml);
    $('#donorSelectionModal').modal('show');
}

function selectDonor(id, donorId, fullName, bloodGroup, age, lastDonation, totalDonations, canDonate) {
    const donor = {
        id: id,
        donor_id: donorId,
        full_name: fullName,
        first_name: fullName.split(' ')[0],
        last_name: fullName.split(' ').slice(1).join(' '),
        blood_group: bloodGroup,
        age: age,
        last_donation_date: lastDonation,
        total_donations: totalDonations,
        can_donate: canDonate
    };

    displayDonorInfo(donor);
    $('#medical-screening-card').show();
    $('#selected-donor-id').val(id);
    $('#donorSelectionModal').modal('hide');

    if (!canDonate) {
        showAlert('warning', 'This donor is not currently eligible for donation. Please check the eligibility criteria.');
    }
}

function displayDonorInfo(donor) {
    console.log('Displaying donor info:', donor); // Debug log

    $('#donor-name').text(donor.full_name || (donor.first_name + ' ' + donor.last_name));
    $('#donor-blood-group').text(donor.blood_group || 'Unknown');
    $('#donor-age').text((donor.age || 'Unknown') + (donor.age ? ' years' : ''));
    $('#donor-last-donation').text(donor.last_donation_date || 'Never');
    $('#donor-total-donations').text(donor.total_donations || '0');

    // Store donor data for later use
    window.currentDonor = donor;

    // Show donor info section
    $('#donor-info-section').removeClass('d-none');

    // Show success message
    showAlert('success', `Donor found: ${donor.full_name || (donor.first_name + ' ' + donor.last_name)} (${donor.blood_group || 'Unknown'}) - Ready for collection!`);
}

// Proceed to medical screening
function proceedToMedicalScreening() {
    if (!window.currentDonor) {
        showAlert('error', 'No donor selected. Please search for a donor first.');
        return;
    }

    $('#selected-donor-id').val(window.currentDonor.id);
    $('#medical-screening-card').show();

    // Scroll to medical screening section
    $('#medical-screening-card')[0].scrollIntoView({ behavior: 'smooth' });

    showAlert('info', 'Please complete the medical screening before blood collection.');
}

// Skip directly to blood collection (for returning donors with recent screening)
function proceedDirectlyToCollection() {
    if (!window.currentDonor) {
        showAlert('error', 'No donor selected. Please search for a donor first.');
        return;
    }

    if (!window.currentDonor.can_donate) {
        showAlert('error', 'This donor is not eligible for donation at this time.');
        return;
    }

    // Confirm skip
    if (!confirm('Are you sure you want to skip medical screening? This should only be done for donors with recent valid screening.')) {
        return;
    }

    $('#collection-donor-id').val(window.currentDonor.id);
    $('#blood-collection-card').show();

    // Auto-fill some collection data
    initializeCollectionForm();

    // Scroll to collection section
    $('#blood-collection-card')[0].scrollIntoView({ behavior: 'smooth' });

    showAlert('success', 'Proceeding directly to blood collection. Please complete all required fields.');
}

// Clear donor selection
function clearDonorSelection() {
    window.currentDonor = null;
    $('#donor-info-section').addClass('d-none');
    $('#medical-screening-card').hide();
    $('#blood-collection-card').hide();
    $('#donor-lookup').val('');

    showAlert('info', 'Donor selection cleared. You can search for another donor.');
}

function generateBagNumber() {
    const today = new Date();
    const year = today.getFullYear().toString().substr(-2);
    const month = (today.getMonth() + 1).toString().padStart(2, '0');
    const day = today.getDate().toString().padStart(2, '0');
    const random = Math.floor(Math.random() * 9999).toString().padStart(4, '0');

    const bagNumber = 'BB' + year + month + day + random;
    $('#bag_number').val(bagNumber);
    return bagNumber;
}

// Initialize collection form with default values
function initializeCollectionForm() {
    const now = new Date();
    const today = now.toISOString().split('T')[0];
    const currentTime = now.toTimeString().split(' ')[0].substring(0, 5);

    // Set current date and time
    $('#collection_date').val(today);
    $('#collection_time').val(currentTime);

    // Generate unique bag number
    generateBagNumber();

    // Set default values
    $('#volume_ml').val(450);
    $('#component_type').val('Whole Blood');

    // Calculate expiry date (35 days for whole blood)
    const expiryDate = new Date(now);
    expiryDate.setDate(expiryDate.getDate() + 35);
    $('#expiry_date').val(expiryDate.toISOString().split('T')[0]);

    // Set current user as collection staff if available
    $('#collection_staff').val('<?php echo getCurrentUserName(); ?>');
}

// Update expiry date based on component type
function updateExpiryDate() {
    const componentType = $('#component_type').val();
    const collectionDate = new Date($('#collection_date').val());

    if (!collectionDate || !componentType) return;

    let expiryDays = 35; // Default for whole blood
    let defaultVolume = 450;

    switch (componentType) {
        case 'Whole Blood':
            expiryDays = 35;
            defaultVolume = 450;
            break;
        case 'Red Blood Cells':
            expiryDays = 42;
            defaultVolume = 350;
            break;
        case 'Plasma':
            expiryDays = 365;
            defaultVolume = 250;
            break;
        case 'Platelets':
            expiryDays = 5;
            defaultVolume = 300;
            break;
    }

    const expiryDate = new Date(collectionDate);
    expiryDate.setDate(expiryDate.getDate() + expiryDays);
    $('#expiry_date').val(expiryDate.toISOString().split('T')[0]);
    $('#volume_ml').val(defaultVolume);
}

// Handle medical screening form submission
$('#medical-screening-form').on('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    $.ajax({
        url: '../ajax/process_medical_screening.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                if (response.eligible) {
                    showAlert('success', 'Donor approved for donation.');
                    $('#blood-collection-card').show();
                    $('#collection-donor-id').val($('#selected-donor-id').val());
                    $('#donation-id').val(response.donation_id);
                } else {
                    showAlert('warning', 'Donor deferred: ' + response.reason);
                }
            } else {
                showAlert('error', response.message);
            }
        }
    });
});

// Handle blood collection form submission
$('#blood-collection-form').on('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    $.ajax({
        url: '../ajax/process_blood_collection.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                showAlert('success', 'Blood collection completed successfully.');
                
                // Reset forms and hide sections
                $('#medical-screening-form')[0].reset();
                $('#blood-collection-form')[0].reset();
                $('#donor-lookup').val('');
                $('#donor-info-section').addClass('d-none');
                $('#medical-screening-card').hide();
                $('#blood-collection-card').hide();
                
                // Reload recent collections
                loadRecentCollections();
                
                // Generate new bag number for next collection
                generateBagNumber();
            } else {
                showAlert('error', response.message);
            }
        }
    });
});

function deferDonor() {
    const reason = prompt('Please enter the reason for deferral:');
    if (reason) {
        // Process deferral
        $.ajax({
            url: '../ajax/defer_donor.php',
            type: 'POST',
            data: {
                donor_id: $('#selected-donor-id').val(),
                reason: reason
            },
            success: function(response) {
                if (response.success) {
                    showAlert('warning', 'Donor has been deferred.');
                    resetForms();
                } else {
                    showAlert('error', response.message);
                }
            }
        });
    }
}

function rejectDonor() {
    const reason = prompt('Please enter the reason for rejection:');
    if (reason) {
        // Process rejection
        $.ajax({
            url: '../ajax/reject_donor.php',
            type: 'POST',
            data: {
                donor_id: $('#selected-donor-id').val(),
                reason: reason
            },
            success: function(response) {
                if (response.success) {
                    showAlert('error', 'Donor has been rejected.');
                    resetForms();
                } else {
                    showAlert('error', response.message);
                }
            }
        });
    }
}

function resetForms() {
    $('#medical-screening-form')[0].reset();
    $('#donor-lookup').val('');
    $('#donor-info-section').addClass('d-none');
    $('#medical-screening-card').hide();
    $('#blood-collection-card').hide();
}

function loadRecentCollections() {
    $.ajax({
        url: '../ajax/get_recent_collections.php',
        type: 'GET',
        success: function(response) {
            $('#recent-collections').html(response);
        }
    });
}

// Check for selected donor from registration
function checkSelectedDonor() {
    const selectedDonor = sessionStorage.getItem('selectedDonor');
    if (selectedDonor) {
        try {
            const donor = JSON.parse(selectedDonor);
            displayDonorInfo(donor);
            sessionStorage.removeItem('selectedDonor');

            // Show success message
            showAlert('success', `Donor ${donor.donor_id} (${donor.full_name}) is ready for blood collection!`);
        } catch (e) {
            console.error('Error parsing selected donor:', e);
        }
    }
}

// Call checkSelectedDonor when page loads
$(document).ready(function() {
    checkSelectedDonor();
});
</script>

<?php if ($isDirectAccess): ?>
<!-- jQuery and Bootstrap JS for direct access -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/global-alert-fix.js"></script>

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

        // Safely remove existing alerts
        try {
            const existingAlerts = document.querySelectorAll('.alert');
            existingAlerts.forEach(alert => alert.remove());
        } catch (e) {
            console.log('No existing alerts to remove');
        }

        // Add new alert at the top
        const targetElement = document.querySelector('body') || document.querySelector('.container-fluid');
        if (targetElement) {
            targetElement.insertAdjacentHTML('afterbegin', alertHtml);
        } else {
            console.log('Alert:', type, message);
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
