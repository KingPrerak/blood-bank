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
        <title>Inventory Management - Blood Bank Management</title>
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
                <h2><i class="fas fa-boxes me-2"></i>Inventory Management</h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Inventory Management</li>
                    </ol>
                </nav>
            </div>
        </div>

        <!-- jQuery and Bootstrap JS for direct access -->
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>';
}

// Get blood groups for filter
try {
    $db = getDB();
    $stmt = $db->query("SELECT id, blood_group FROM blood_groups ORDER BY blood_group");
    $bloodGroups = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Inventory error: " . $e->getMessage());
    $bloodGroups = [];
}
?>

<div class="row mb-4">
    <div class="col-12">
        <h2><i class="fas fa-boxes me-2"></i>Blood Inventory Management</h2>
    </div>
</div>

<!-- Inventory Summary Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 id="total-available">0</h4>
                        <p class="mb-0">Available Units</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-check-circle fa-2x"></i>
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
                        <h4 id="expiring-soon">0</h4>
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
        <div class="card bg-danger text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 id="expired-units">0</h4>
                        <p class="mb-0">Expired Units</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-exclamation-triangle fa-2x"></i>
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
                        <h4 id="issued-units">0</h4>
                        <p class="mb-0">Issued Units</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-share fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Blood Group Wise Inventory -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-chart-bar me-2"></i>Blood Group Wise Inventory</h5>
            </div>
            <div class="card-body">
                <div class="row" id="blood-group-inventory">
                    <!-- Blood group inventory will be loaded here -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters and Search -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-filter me-2"></i>Filters & Search</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <label for="blood-group-filter" class="form-label">Blood Group</label>
                        <select class="form-select" id="blood-group-filter">
                            <option value="">All Blood Groups</option>
                            <?php foreach ($bloodGroups as $group): ?>
                            <option value="<?php echo $group['id']; ?>"><?php echo $group['blood_group']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="component-filter" class="form-label">Component Type</label>
                        <select class="form-select" id="component-filter">
                            <option value="">All Components</option>
                            <option value="Whole Blood">Whole Blood</option>
                            <option value="Red Blood Cells">Red Blood Cells</option>
                            <option value="Plasma">Plasma</option>
                            <option value="Platelets">Platelets</option>
                            <option value="Cryoprecipitate">Cryoprecipitate</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="status-filter" class="form-label">Status</label>
                        <select class="form-select" id="status-filter">
                            <option value="">All Status</option>
                            <option value="available">Available</option>
                            <option value="issued">Issued</option>
                            <option value="expired">Expired</option>
                            <option value="discarded">Discarded</option>
                            <option value="quarantined">Quarantined</option>
                            <option value="testing">Testing</option>
                            <option value="separated">Separated</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="search-input" class="form-label">Search</label>
                        <input type="text" class="form-control" id="search-input" placeholder="Bag number, donor ID...">
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <button type="button" class="btn btn-primary me-2" onclick="applyFilters()">
                            <i class="fas fa-search me-2"></i>Apply Filters
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="clearFilters()">
                            <i class="fas fa-undo me-2"></i>Clear Filters
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Inventory Table -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-list me-2"></i>Blood Inventory</h5>
                <div>
                    <button class="btn btn-warning btn-sm me-2" onclick="markExpiredUnits()">
                        <i class="fas fa-exclamation-triangle me-1"></i>Mark Expired
                    </button>
                    <button class="btn btn-secondary btn-sm me-2" onclick="quarantineManagement()">
                        <i class="fas fa-shield-alt me-1"></i>Quarantine
                    </button>
                    <button class="btn btn-success btn-sm me-2" onclick="qualityControl()">
                        <i class="fas fa-check-circle me-1"></i>Quality Control
                    </button>
                    <button class="btn btn-info btn-sm" onclick="exportInventory()">
                        <i class="fas fa-download me-1"></i>Export
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="inventory-table">
                        <thead>
                            <tr>
                                <th>Bag Number</th>
                                <th>Blood Group</th>
                                <th>Component</th>
                                <th>Volume (ml)</th>
                                <th>Collection Date</th>
                                <th>Expiry Date</th>
                                <th>Donor</th>
                                <th>Storage Location</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="inventory-tbody">
                            <!-- Inventory data will be loaded here -->
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <nav aria-label="Inventory pagination">
                    <ul class="pagination justify-content-center" id="pagination">
                        <!-- Pagination will be loaded here -->
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</div>

<!-- Update Status Modal -->
<div class="modal fade" id="updateStatusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Blood Unit Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="update-status-form">
                    <input type="hidden" id="update-bag-id" name="bag_id">
                    
                    <div class="mb-3">
                        <label for="new-status" class="form-label">New Status</label>
                        <select class="form-select" id="new-status" name="new_status" required>
                            <option value="">Select Status</option>
                            <option value="available">Available</option>
                            <option value="expired">Expired</option>
                            <option value="discarded">Discarded</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="status-reason" class="form-label">Reason</label>
                        <textarea class="form-control" id="status-reason" name="reason" rows="3" 
                                  placeholder="Reason for status change"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="updateBagStatus()">Update Status</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    loadInventoryData();
    loadInventorySummary();
    loadBloodGroupInventory();
});

function loadInventoryData(page = 1) {
    const filters = {
        blood_group: $('#blood-group-filter').val(),
        component: $('#component-filter').val(),
        status: $('#status-filter').val(),
        search: $('#search-input').val(),
        page: page
    };
    
    $.ajax({
        url: '../ajax/get_inventory.php',
        type: 'GET',
        data: filters,
        success: function(response) {
            if (response.success) {
                $('#inventory-tbody').html(response.table_rows);
                $('#pagination').html(response.pagination);
            } else {
                showAlert('error', response.message);
            }
        }
    });
}

function loadInventorySummary() {
    $.ajax({
        url: '../ajax/get_inventory_summary.php',
        type: 'GET',
        success: function(response) {
            if (response.success) {
                $('#total-available').text(response.summary.available);
                $('#expiring-soon').text(response.summary.expiring_soon);
                $('#expired-units').text(response.summary.expired);
                $('#issued-units').text(response.summary.issued);
            }
        }
    });
}

function loadBloodGroupInventory() {
    $.ajax({
        url: '../ajax/get_blood_group_inventory.php',
        type: 'GET',
        success: function(response) {
            $('#blood-group-inventory').html(response);
        }
    });
}

function applyFilters() {
    loadInventoryData(1);
}

function clearFilters() {
    $('#blood-group-filter').val('');
    $('#component-filter').val('');
    $('#status-filter').val('');
    $('#search-input').val('');
    loadInventoryData(1);
}

function showUpdateStatusModal(bagId, currentStatus) {
    $('#update-bag-id').val(bagId);
    $('#new-status').val('');
    $('#status-reason').val('');
    $('#updateStatusModal').modal('show');
}

function updateBagStatus() {
    const formData = new FormData($('#update-status-form')[0]);
    
    $.ajax({
        url: '../ajax/update_bag_status.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                showAlert('success', response.message);
                $('#updateStatusModal').modal('hide');
                loadInventoryData();
                loadInventorySummary();
                loadBloodGroupInventory();
            } else {
                showAlert('error', response.message);
            }
        }
    });
}

function markExpiredUnits() {
    if (confirm('This will mark all units past their expiry date as expired. Continue?')) {
        $.ajax({
            url: '../ajax/mark_expired_units.php',
            type: 'POST',
            success: function(response) {
                if (response.success) {
                    showAlert('success', response.message);
                    loadInventoryData();
                    loadInventorySummary();
                    loadBloodGroupInventory();
                } else {
                    showAlert('error', response.message);
                }
            }
        });
    }
}

function exportInventory() {
    const filters = {
        blood_group: $('#blood-group-filter').val(),
        component: $('#component-filter').val(),
        status: $('#status-filter').val(),
        search: $('#search-input').val()
    };
    
    // Create form and submit for download
    const form = $('<form>', {
        method: 'POST',
        action: '../ajax/export_inventory.php'
    });
    
    $.each(filters, function(key, value) {
        form.append($('<input>', {
            type: 'hidden',
            name: key,
            value: value
        }));
    });
    
    $('body').append(form);
    form.submit();
    form.remove();
}

function discardBag(bagId) {
    const reason = prompt('Please enter the reason for discarding this blood bag:');
    if (reason) {
        $.ajax({
            url: '../ajax/discard_bag.php',
            type: 'POST',
            data: {
                bag_id: bagId,
                reason: reason
            },
            success: function(response) {
                if (response.success) {
                    showAlert('success', response.message);
                    loadInventoryData();
                    loadInventorySummary();
                    loadBloodGroupInventory();
                } else {
                    showAlert('error', response.message);
                }
            }
        });
    }
}

// Auto-refresh every 5 minutes
setInterval(function() {
    loadInventorySummary();
    loadBloodGroupInventory();
}, 300000);
</script>

<?php if ($isDirectAccess): ?>
<script>
// Alert function for direct access
function showAlert(type, message) {
    const alertClass = type === 'success' ? 'alert-success' :
                      type === 'error' ? 'alert-danger' :
                      type === 'warning' ? 'alert-warning' : 'alert-info';

    const alertHtml = `<div class="alert ${alertClass} alert-dismissible fade show" role="alert">
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>`;

    $('.alert').remove();
    $('body').prepend(alertHtml);
    setTimeout(() => $('.alert').alert('close'), 5000);
}

function loadPage(page) {
    window.location.href = '../dashboard.php#' + page;
}
</script>

    </div>
    </body>
    </html>
<?php endif; ?>
