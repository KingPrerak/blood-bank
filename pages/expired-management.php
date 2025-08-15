<?php
require_once '../config/config.php';
requireLogin();

// Get expired and expiring blood units
try {
    $db = getDB();
    
    // Get expired units
    $stmt = $db->query("
        SELECT bi.*, bg.blood_group, CONCAT(d.first_name, ' ', d.last_name) as donor_name,
               DATEDIFF(CURDATE(), bi.expiry_date) as days_expired
        FROM blood_inventory bi
        JOIN blood_groups bg ON bi.blood_group_id = bg.id
        LEFT JOIN donors d ON bi.donor_id = d.id
        WHERE bi.expiry_date < CURDATE() AND bi.status = 'available'
        ORDER BY bi.expiry_date ASC
    ");
    $expiredUnits = $stmt->fetchAll();
    
    // Get expiring soon units (within 7 days)
    $stmt = $db->query("
        SELECT bi.*, bg.blood_group, CONCAT(d.first_name, ' ', d.last_name) as donor_name,
               DATEDIFF(bi.expiry_date, CURDATE()) as days_to_expiry
        FROM blood_inventory bi
        JOIN blood_groups bg ON bi.blood_group_id = bg.id
        LEFT JOIN donors d ON bi.donor_id = d.id
        WHERE bi.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) 
        AND bi.status = 'available'
        ORDER BY bi.expiry_date ASC
    ");
    $expiringSoonUnits = $stmt->fetchAll();
    
    // Get disposal history
    $stmt = $db->query("
        SELECT bd.*, bi.bag_number, bg.blood_group, bi.component_type, u.full_name as disposed_by_name
        FROM blood_disposals bd
        JOIN blood_inventory bi ON bd.bag_id = bi.id
        JOIN blood_groups bg ON bi.blood_group_id = bg.id
        JOIN users u ON bd.disposed_by = u.id
        ORDER BY bd.disposal_date DESC
        LIMIT 50
    ");
    $disposalHistory = $stmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Expired management error: " . $e->getMessage());
    $expiredUnits = [];
    $expiringSoonUnits = [];
    $disposalHistory = [];
}
?>

<div class="row mb-4">
    <div class="col-12">
        <h2><i class="fas fa-exclamation-triangle me-2 text-danger"></i>Expired Blood Management</h2>
    </div>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-danger text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4><?php echo count($expiredUnits); ?></h4>
                        <p class="mb-0">Expired Units</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-times-circle fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4><?php echo count($expiringSoonUnits); ?></h4>
                        <p class="mb-0">Expiring Soon</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-clock fa-2x"></i>
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
                        <h4><?php echo count($disposalHistory); ?></h4>
                        <p class="mb-0">Total Disposed</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-trash fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-secondary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 id="wastage-cost">₹0</h4>
                        <p class="mb-0">Wastage Cost</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-rupee-sign fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Action Buttons -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-cogs me-2"></i>Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <button class="btn btn-danger w-100" onclick="markAllExpired()">
                            <i class="fas fa-exclamation-triangle me-2"></i>Mark All Expired
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-warning w-100" onclick="bulkDispose()">
                            <i class="fas fa-trash-alt me-2"></i>Bulk Dispose
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-info w-100" onclick="generateWastageReport()">
                            <i class="fas fa-chart-line me-2"></i>Wastage Report
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-success w-100" onclick="exportDisposalCertificate()">
                            <i class="fas fa-certificate me-2"></i>Disposal Certificate
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Expired Units Table -->
<?php if (!empty($expiredUnits)): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-danger text-white">
                <h5><i class="fas fa-times-circle me-2"></i>Expired Blood Units (Immediate Action Required)</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="select-all-expired"></th>
                                <th>Bag Number</th>
                                <th>Blood Group</th>
                                <th>Component</th>
                                <th>Volume</th>
                                <th>Expiry Date</th>
                                <th>Days Expired</th>
                                <th>Donor</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($expiredUnits as $unit): ?>
                            <tr class="table-danger">
                                <td><input type="checkbox" class="expired-unit" value="<?php echo $unit['id']; ?>"></td>
                                <td><strong><?php echo htmlspecialchars($unit['bag_number']); ?></strong></td>
                                <td><span class="badge bg-danger"><?php echo $unit['blood_group']; ?></span></td>
                                <td><?php echo $unit['component_type']; ?></td>
                                <td><?php echo $unit['volume_ml']; ?>ml</td>
                                <td class="text-danger"><?php echo formatDate($unit['expiry_date']); ?></td>
                                <td><span class="badge bg-danger"><?php echo $unit['days_expired']; ?> days</span></td>
                                <td><?php echo htmlspecialchars($unit['donor_name'] ?? 'Unknown'); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-danger" onclick="disposeUnit(<?php echo $unit['id']; ?>)">
                                        <i class="fas fa-trash"></i> Dispose
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

<!-- Expiring Soon Units Table -->
<?php if (!empty($expiringSoonUnits)): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-warning text-dark">
                <h5><i class="fas fa-clock me-2"></i>Units Expiring Soon (Within 7 Days)</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Bag Number</th>
                                <th>Blood Group</th>
                                <th>Component</th>
                                <th>Volume</th>
                                <th>Expiry Date</th>
                                <th>Days Remaining</th>
                                <th>Donor</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($expiringSoonUnits as $unit): ?>
                            <tr class="table-warning">
                                <td><strong><?php echo htmlspecialchars($unit['bag_number']); ?></strong></td>
                                <td><span class="badge bg-warning text-dark"><?php echo $unit['blood_group']; ?></span></td>
                                <td><?php echo $unit['component_type']; ?></td>
                                <td><?php echo $unit['volume_ml']; ?>ml</td>
                                <td class="text-warning"><?php echo formatDate($unit['expiry_date']); ?></td>
                                <td><span class="badge bg-warning text-dark"><?php echo $unit['days_to_expiry']; ?> days</span></td>
                                <td><?php echo htmlspecialchars($unit['donor_name'] ?? 'Unknown'); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="prioritizeForIssue(<?php echo $unit['id']; ?>)">
                                        <i class="fas fa-arrow-up"></i> Prioritize
                                    </button>
                                    <button class="btn btn-sm btn-warning" onclick="extendExpiry(<?php echo $unit['id']; ?>)">
                                        <i class="fas fa-calendar-plus"></i> Extend
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

<!-- Disposal History -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-history me-2"></i>Disposal History</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Disposal Date</th>
                                <th>Bag Number</th>
                                <th>Blood Group</th>
                                <th>Component</th>
                                <th>Reason</th>
                                <th>Method</th>
                                <th>Disposed By</th>
                                <th>Certificate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($disposalHistory as $disposal): ?>
                            <tr>
                                <td><?php echo formatDate($disposal['disposal_date']); ?></td>
                                <td><?php echo htmlspecialchars($disposal['bag_number']); ?></td>
                                <td><span class="badge bg-secondary"><?php echo $disposal['blood_group']; ?></span></td>
                                <td><?php echo $disposal['component_type']; ?></td>
                                <td><?php echo ucfirst(str_replace('_', ' ', $disposal['disposal_reason'])); ?></td>
                                <td><?php echo htmlspecialchars($disposal['disposal_method'] ?? 'Standard'); ?></td>
                                <td><?php echo htmlspecialchars($disposal['disposed_by_name']); ?></td>
                                <td>
                                    <?php if ($disposal['disposal_certificate_no']): ?>
                                    <span class="badge bg-success"><?php echo $disposal['disposal_certificate_no']; ?></span>
                                    <?php else: ?>
                                    <span class="badge bg-warning">Pending</span>
                                    <?php endif; ?>
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

<!-- Disposal Modal -->
<div class="modal fade" id="disposalModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Blood Unit Disposal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="disposal-form">
                    <input type="hidden" id="disposal-bag-id" name="bag_id">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="disposal-reason" class="form-label">Disposal Reason <span class="text-danger">*</span></label>
                            <select class="form-select" id="disposal-reason" name="disposal_reason" required>
                                <option value="">Select Reason</option>
                                <option value="expired">Expired</option>
                                <option value="contaminated">Contaminated</option>
                                <option value="damaged">Damaged</option>
                                <option value="quality_failure">Quality Failure</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="disposal-method" class="form-label">Disposal Method <span class="text-danger">*</span></label>
                            <select class="form-select" id="disposal-method" name="disposal_method" required>
                                <option value="">Select Method</option>
                                <option value="incineration">Incineration</option>
                                <option value="autoclave">Autoclave Treatment</option>
                                <option value="chemical_treatment">Chemical Treatment</option>
                                <option value="authorized_vendor">Authorized Vendor</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="disposal-location" class="form-label">Disposal Location</label>
                            <input type="text" class="form-control" id="disposal-location" name="disposal_location">
                        </div>
                        <div class="col-md-6">
                            <label for="certificate-no" class="form-label">Certificate Number</label>
                            <input type="text" class="form-control" id="certificate-no" name="disposal_certificate_no">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="disposal-notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="disposal-notes" name="notes" rows="3"></textarea>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="supervisor-approval" name="require_approval">
                        <label class="form-check-label" for="supervisor-approval">
                            Requires Supervisor Approval
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="confirmDisposal()">
                    <i class="fas fa-trash me-2"></i>Dispose Unit
                </button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    calculateWastageCost();
    
    // Select all expired units
    $('#select-all-expired').on('change', function() {
        $('.expired-unit').prop('checked', $(this).prop('checked'));
    });
});

function markAllExpired() {
    if (confirm('This will mark all units past their expiry date as expired. Continue?')) {
        $.ajax({
            url: '../ajax/mark_all_expired.php',
            type: 'POST',
            success: function(response) {
                if (response.success) {
                    showAlert('success', response.message);
                    location.reload();
                } else {
                    showAlert('error', response.message);
                }
            }
        });
    }
}

function disposeUnit(bagId) {
    $('#disposal-bag-id').val(bagId);
    $('#disposalModal').modal('show');
}

function confirmDisposal() {
    const formData = new FormData($('#disposal-form')[0]);
    
    $.ajax({
        url: '../ajax/dispose_blood_unit.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                showAlert('success', response.message);
                $('#disposalModal').modal('hide');
                location.reload();
            } else {
                showAlert('error', response.message);
            }
        }
    });
}

function bulkDispose() {
    const selectedUnits = $('.expired-unit:checked').map(function() {
        return $(this).val();
    }).get();
    
    if (selectedUnits.length === 0) {
        showAlert('warning', 'Please select units to dispose.');
        return;
    }
    
    if (confirm(`Are you sure you want to dispose ${selectedUnits.length} selected units?`)) {
        $.ajax({
            url: '../ajax/bulk_dispose.php',
            type: 'POST',
            data: { bag_ids: selectedUnits },
            success: function(response) {
                if (response.success) {
                    showAlert('success', response.message);
                    location.reload();
                } else {
                    showAlert('error', response.message);
                }
            }
        });
    }
}

function prioritizeForIssue(bagId) {
    $.ajax({
        url: '../ajax/prioritize_bag.php',
        type: 'POST',
        data: { bag_id: bagId },
        success: function(response) {
            if (response.success) {
                showAlert('success', 'Blood unit prioritized for immediate issue.');
            } else {
                showAlert('error', response.message);
            }
        }
    });
}

function calculateWastageCost() {
    $.ajax({
        url: '../ajax/calculate_wastage_cost.php',
        type: 'GET',
        success: function(response) {
            if (response.success) {
                $('#wastage-cost').text('₹' + response.cost.toLocaleString());
            }
        }
    });
}

function generateWastageReport() {
    window.open('../ajax/generate_wastage_report.php', '_blank');
}

function exportDisposalCertificate() {
    window.open('../ajax/export_disposal_certificate.php', '_blank');
}
</script>
