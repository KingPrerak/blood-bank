<?php
require_once '../config/config.php';
requireLogin();

// Get pending crossmatch requests
try {
    $db = getDB();
    
    // Get pending crossmatch tests
    $stmt = $db->query("
        SELECT cm.*, br.request_id, br.patient_name, br.patient_age, br.patient_gender,
               bg_patient.blood_group as patient_blood_group,
               bi.bag_number, bg_bag.blood_group as bag_blood_group, bi.component_type,
               br.hospital_name, br.doctor_name
        FROM cross_matching cm
        JOIN blood_requests br ON cm.request_id = br.id
        JOIN blood_groups bg_patient ON br.blood_group_id = bg_patient.id
        JOIN blood_inventory bi ON cm.bag_id = bi.id
        JOIN blood_groups bg_bag ON bi.blood_group_id = bg_bag.id
        WHERE cm.result = 'pending'
        ORDER BY cm.created_at DESC
    ");
    $pendingTests = $stmt->fetchAll();
    
    // Get completed crossmatch tests (last 50)
    $stmt = $db->query("
        SELECT cm.*, br.request_id, br.patient_name, 
               bg_patient.blood_group as patient_blood_group,
               bi.bag_number, bg_bag.blood_group as bag_blood_group,
               u.full_name as performed_by_name
        FROM cross_matching cm
        JOIN blood_requests br ON cm.request_id = br.id
        JOIN blood_groups bg_patient ON br.blood_group_id = bg_patient.id
        JOIN blood_inventory bi ON cm.bag_id = bi.id
        JOIN blood_groups bg_bag ON bi.blood_group_id = bg_bag.id
        LEFT JOIN users u ON cm.performed_by = u.username
        WHERE cm.result != 'pending'
        ORDER BY cm.crossmatch_date DESC, cm.crossmatch_time DESC
        LIMIT 50
    ");
    $completedTests = $stmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Crossmatch lab error: " . $e->getMessage());
    $pendingTests = [];
    $completedTests = [];
}
?>

<div class="row mb-4">
    <div class="col-12">
        <h2><i class="fas fa-microscope me-2"></i>Cross-matching Laboratory</h2>
    </div>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4><?php echo count($pendingTests); ?></h4>
                        <p class="mb-0">Pending Tests</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-clock fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 id="compatible-today">0</h4>
                        <p class="mb-0">Compatible Today</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-check-circle fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-danger text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 id="incompatible-today">0</h4>
                        <p class="mb-0">Incompatible Today</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-times-circle fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 id="total-tests">0</h4>
                        <p class="mb-0">Total Tests</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-vial fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <button class="btn btn-primary w-100" onclick="newCrossmatchTest()">
                            <i class="fas fa-plus me-2"></i>New Crossmatch Test
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-info w-100" onclick="bloodCompatibilityChart()">
                            <i class="fas fa-chart-bar me-2"></i>Compatibility Chart
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-success w-100" onclick="generateLabReport()">
                            <i class="fas fa-file-medical me-2"></i>Lab Report
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-warning w-100" onclick="qualityControlCheck()">
                            <i class="fas fa-shield-alt me-2"></i>Quality Control
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Pending Crossmatch Tests -->
<?php if (!empty($pendingTests)): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-warning text-dark">
                <h5><i class="fas fa-clock me-2"></i>Pending Crossmatch Tests</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Request ID</th>
                                <th>Patient Details</th>
                                <th>Patient Blood Group</th>
                                <th>Bag Number</th>
                                <th>Bag Blood Group</th>
                                <th>Component</th>
                                <th>Hospital</th>
                                <th>Sample ID</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingTests as $test): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($test['request_id']); ?></strong></td>
                                <td>
                                    <?php echo htmlspecialchars($test['patient_name']); ?><br>
                                    <small class="text-muted"><?php echo $test['patient_age']; ?>Y, <?php echo $test['patient_gender']; ?></small>
                                </td>
                                <td><span class="badge bg-primary"><?php echo $test['patient_blood_group']; ?></span></td>
                                <td><strong><?php echo htmlspecialchars($test['bag_number']); ?></strong></td>
                                <td><span class="badge bg-danger"><?php echo $test['bag_blood_group']; ?></span></td>
                                <td><?php echo $test['component_type']; ?></td>
                                <td><?php echo htmlspecialchars($test['hospital_name']); ?></td>
                                <td><?php echo htmlspecialchars($test['patient_sample_id'] ?? 'Not provided'); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-success" onclick="performCrossmatch(<?php echo $test['id']; ?>)">
                                        <i class="fas fa-microscope"></i> Test
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Completed Tests -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-check-circle me-2"></i>Completed Crossmatch Tests</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Date/Time</th>
                                <th>Request ID</th>
                                <th>Patient</th>
                                <th>Patient BG</th>
                                <th>Bag Number</th>
                                <th>Bag BG</th>
                                <th>Major CM</th>
                                <th>Minor CM</th>
                                <th>Antibody Screen</th>
                                <th>Final Result</th>
                                <th>Performed By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($completedTests as $test): ?>
                            <tr>
                                <td>
                                    <?php echo formatDate($test['crossmatch_date']); ?><br>
                                    <small class="text-muted"><?php echo date('H:i', strtotime($test['crossmatch_time'])); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($test['request_id']); ?></td>
                                <td><?php echo htmlspecialchars($test['patient_name']); ?></td>
                                <td><span class="badge bg-primary"><?php echo $test['patient_blood_group']; ?></span></td>
                                <td><?php echo htmlspecialchars($test['bag_number']); ?></td>
                                <td><span class="badge bg-danger"><?php echo $test['bag_blood_group']; ?></span></td>
                                <td>
                                    <?php 
                                    $class = $test['major_crossmatch'] === 'compatible' ? 'bg-success' : 
                                            ($test['major_crossmatch'] === 'incompatible' ? 'bg-danger' : 'bg-warning');
                                    ?>
                                    <span class="badge <?php echo $class; ?>"><?php echo ucfirst($test['major_crossmatch']); ?></span>
                                </td>
                                <td>
                                    <?php 
                                    $class = $test['minor_crossmatch'] === 'compatible' ? 'bg-success' : 
                                            ($test['minor_crossmatch'] === 'incompatible' ? 'bg-danger' : 'bg-warning');
                                    ?>
                                    <span class="badge <?php echo $class; ?>"><?php echo ucfirst($test['minor_crossmatch']); ?></span>
                                </td>
                                <td>
                                    <?php 
                                    $class = $test['antibody_screening'] === 'negative' ? 'bg-success' : 
                                            ($test['antibody_screening'] === 'positive' ? 'bg-danger' : 'bg-warning');
                                    ?>
                                    <span class="badge <?php echo $class; ?>"><?php echo ucfirst($test['antibody_screening']); ?></span>
                                </td>
                                <td>
                                    <?php 
                                    $class = $test['result'] === 'compatible' ? 'bg-success' : 
                                            ($test['result'] === 'incompatible' ? 'bg-danger' : 'bg-warning');
                                    ?>
                                    <span class="badge <?php echo $class; ?>"><?php echo ucfirst($test['result']); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($test['performed_by_name'] ?? $test['performed_by']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Crossmatch Test Modal -->
<div class="modal fade" id="crossmatchModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Perform Crossmatch Test</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="crossmatch-form">
                    <input type="hidden" id="crossmatch-id" name="crossmatch_id">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="patient-sample-id" class="form-label">Patient Sample ID</label>
                            <input type="text" class="form-control" id="patient-sample-id" name="patient_sample_id">
                        </div>
                        <div class="col-md-6">
                            <label for="performed-by" class="form-label">Performed By <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="performed-by" name="performed_by" 
                                   value="<?php echo getCurrentUserName(); ?>" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="major-crossmatch" class="form-label">Major Crossmatch <span class="text-danger">*</span></label>
                            <select class="form-select" id="major-crossmatch" name="major_crossmatch" required>
                                <option value="">Select Result</option>
                                <option value="compatible">Compatible</option>
                                <option value="incompatible">Incompatible</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="minor-crossmatch" class="form-label">Minor Crossmatch <span class="text-danger">*</span></label>
                            <select class="form-select" id="minor-crossmatch" name="minor_crossmatch" required>
                                <option value="">Select Result</option>
                                <option value="compatible">Compatible</option>
                                <option value="incompatible">Incompatible</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="antibody-screening" class="form-label">Antibody Screening <span class="text-danger">*</span></label>
                            <select class="form-select" id="antibody-screening" name="antibody_screening" required>
                                <option value="">Select Result</option>
                                <option value="negative">Negative</option>
                                <option value="positive">Positive</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="verified-by" class="form-label">Verified By</label>
                            <input type="text" class="form-control" id="verified-by" name="verified_by">
                        </div>
                        <div class="col-md-6">
                            <label for="final-result" class="form-label">Final Result <span class="text-danger">*</span></label>
                            <select class="form-select" id="final-result" name="result" required>
                                <option value="">Select Final Result</option>
                                <option value="compatible">Compatible</option>
                                <option value="incompatible">Incompatible</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="crossmatch-notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="crossmatch-notes" name="notes" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveCrossmatchResult()">
                    <i class="fas fa-save me-2"></i>Save Result
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Blood Compatibility Chart Modal -->
<div class="modal fade" id="compatibilityModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Blood Compatibility Chart</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-dark">
                            <tr>
                                <th rowspan="2">Recipient Blood Group</th>
                                <th colspan="8">Donor Blood Group</th>
                            </tr>
                            <tr>
                                <th>O-</th><th>O+</th><th>A-</th><th>A+</th><th>B-</th><th>B+</th><th>AB-</th><th>AB+</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>O-</strong></td>
                                <td class="bg-success text-white">✓</td>
                                <td class="bg-danger text-white">✗</td>
                                <td class="bg-danger text-white">✗</td>
                                <td class="bg-danger text-white">✗</td>
                                <td class="bg-danger text-white">✗</td>
                                <td class="bg-danger text-white">✗</td>
                                <td class="bg-danger text-white">✗</td>
                                <td class="bg-danger text-white">✗</td>
                            </tr>
                            <tr>
                                <td><strong>O+</strong></td>
                                <td class="bg-success text-white">✓</td>
                                <td class="bg-success text-white">✓</td>
                                <td class="bg-danger text-white">✗</td>
                                <td class="bg-danger text-white">✗</td>
                                <td class="bg-danger text-white">✗</td>
                                <td class="bg-danger text-white">✗</td>
                                <td class="bg-danger text-white">✗</td>
                                <td class="bg-danger text-white">✗</td>
                            </tr>
                            <tr>
                                <td><strong>A-</strong></td>
                                <td class="bg-success text-white">✓</td>
                                <td class="bg-danger text-white">✗</td>
                                <td class="bg-success text-white">✓</td>
                                <td class="bg-danger text-white">✗</td>
                                <td class="bg-danger text-white">✗</td>
                                <td class="bg-danger text-white">✗</td>
                                <td class="bg-danger text-white">✗</td>
                                <td class="bg-danger text-white">✗</td>
                            </tr>
                            <tr>
                                <td><strong>A+</strong></td>
                                <td class="bg-success text-white">✓</td>
                                <td class="bg-success text-white">✓</td>
                                <td class="bg-success text-white">✓</td>
                                <td class="bg-success text-white">✓</td>
                                <td class="bg-danger text-white">✗</td>
                                <td class="bg-danger text-white">✗</td>
                                <td class="bg-danger text-white">✗</td>
                                <td class="bg-danger text-white">✗</td>
                            </tr>
                            <tr>
                                <td><strong>B-</strong></td>
                                <td class="bg-success text-white">✓</td>
                                <td class="bg-danger text-white">✗</td>
                                <td class="bg-danger text-white">✗</td>
                                <td class="bg-danger text-white">✗</td>
                                <td class="bg-success text-white">✓</td>
                                <td class="bg-danger text-white">✗</td>
                                <td class="bg-danger text-white">✗</td>
                                <td class="bg-danger text-white">✗</td>
                            </tr>
                            <tr>
                                <td><strong>B+</strong></td>
                                <td class="bg-success text-white">✓</td>
                                <td class="bg-success text-white">✓</td>
                                <td class="bg-danger text-white">✗</td>
                                <td class="bg-danger text-white">✗</td>
                                <td class="bg-success text-white">✓</td>
                                <td class="bg-success text-white">✓</td>
                                <td class="bg-danger text-white">✗</td>
                                <td class="bg-danger text-white">✗</td>
                            </tr>
                            <tr>
                                <td><strong>AB-</strong></td>
                                <td class="bg-success text-white">✓</td>
                                <td class="bg-danger text-white">✗</td>
                                <td class="bg-success text-white">✓</td>
                                <td class="bg-danger text-white">✗</td>
                                <td class="bg-success text-white">✓</td>
                                <td class="bg-danger text-white">✗</td>
                                <td class="bg-success text-white">✓</td>
                                <td class="bg-danger text-white">✗</td>
                            </tr>
                            <tr>
                                <td><strong>AB+</strong></td>
                                <td class="bg-success text-white">✓</td>
                                <td class="bg-success text-white">✓</td>
                                <td class="bg-success text-white">✓</td>
                                <td class="bg-success text-white">✓</td>
                                <td class="bg-success text-white">✓</td>
                                <td class="bg-success text-white">✓</td>
                                <td class="bg-success text-white">✓</td>
                                <td class="bg-success text-white">✓</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    <p><strong>Legend:</strong></p>
                    <p><span class="badge bg-success">✓</span> Compatible - Safe to transfuse</p>
                    <p><span class="badge bg-danger">✗</span> Incompatible - Do not transfuse</p>
                    <p><small class="text-muted">Note: This chart shows ABO-Rh compatibility. Always perform crossmatching before transfusion.</small></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    loadTodayStats();
});

function performCrossmatch(crossmatchId) {
    $('#crossmatch-id').val(crossmatchId);
    $('#crossmatchModal').modal('show');
}

function saveCrossmatchResult() {
    const formData = new FormData($('#crossmatch-form')[0]);
    
    $.ajax({
        url: '../ajax/save_crossmatch_result.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                showAlert('success', response.message);
                $('#crossmatchModal').modal('hide');
                location.reload();
            } else {
                showAlert('error', response.message);
            }
        }
    });
}

function newCrossmatchTest() {
    // Redirect to blood requests page to initiate new crossmatch
    loadPage('blood-requests');
}

function bloodCompatibilityChart() {
    $('#compatibilityModal').modal('show');
}

function loadTodayStats() {
    $.ajax({
        url: '../ajax/get_crossmatch_stats.php',
        type: 'GET',
        success: function(response) {
            if (response.success) {
                $('#compatible-today').text(response.stats.compatible_today);
                $('#incompatible-today').text(response.stats.incompatible_today);
                $('#total-tests').text(response.stats.total_tests);
            }
        }
    });
}

function generateLabReport() {
    window.open('../ajax/generate_crossmatch_report.php', '_blank');
}

function qualityControlCheck() {
    showAlert('info', 'Quality control check initiated. Please verify all equipment calibration and reagent expiry dates.');
}
</script>
