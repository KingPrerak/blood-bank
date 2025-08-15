<?php
require_once '../config/config.php';
requireLogin();
?>

<div class="row mb-4">
    <div class="col-12">
        <h2><i class="fas fa-chart-bar me-2"></i>Reports & Analytics</h2>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-calendar me-2"></i>Report Period</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="date-from" class="form-label">From Date</label>
                    <input type="date" class="form-control" id="date-from" value="<?php echo date('Y-m-01'); ?>">
                </div>
                <div class="mb-3">
                    <label for="date-to" class="form-label">To Date</label>
                    <input type="date" class="form-control" id="date-to" value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="d-grid">
                    <button class="btn btn-primary" onclick="generateReports()">
                        <i class="fas fa-chart-line me-2"></i>Generate Reports
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-download me-2"></i>Quick Reports</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <button class="btn btn-outline-primary w-100" onclick="generateReport('donations')">
                            <i class="fas fa-tint me-2"></i>Donations Report
                        </button>
                    </div>
                    <div class="col-md-6 mb-3">
                        <button class="btn btn-outline-success w-100" onclick="generateReport('inventory')">
                            <i class="fas fa-boxes me-2"></i>Inventory Report
                        </button>
                    </div>
                    <div class="col-md-6 mb-3">
                        <button class="btn btn-outline-warning w-100" onclick="generateReport('requests')">
                            <i class="fas fa-hand-holding-medical me-2"></i>Requests Report
                        </button>
                    </div>
                    <div class="col-md-6 mb-3">
                        <button class="btn btn-outline-info w-100" onclick="generateReport('donors')">
                            <i class="fas fa-users me-2"></i>Donors Report
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row" id="reports-section" style="display: none;">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-chart-bar me-2"></i>Report Results</h5>
                <button class="btn btn-sm btn-success" onclick="exportReport()">
                    <i class="fas fa-file-excel me-1"></i>Export to Excel
                </button>
            </div>
            <div class="card-body">
                <div id="report-content">
                    <!-- Report content will be loaded here -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function generateReports() {
    const dateFrom = $('#date-from').val();
    const dateTo = $('#date-to').val();
    
    if (!dateFrom || !dateTo) {
        showAlert('warning', 'Please select both from and to dates.');
        return;
    }
    
    if (new Date(dateFrom) > new Date(dateTo)) {
        showAlert('warning', 'From date cannot be later than to date.');
        return;
    }
    
    // Generate all reports
    $.ajax({
        url: '../ajax/generate_comprehensive_report.php',
        type: 'POST',
        data: {
            date_from: dateFrom,
            date_to: dateTo
        },
        success: function(response) {
            if (response.success) {
                $('#report-content').html(response.html);
                $('#reports-section').show();
            } else {
                showAlert('error', response.message);
            }
        }
    });
}

function generateReport(type) {
    const dateFrom = $('#date-from').val();
    const dateTo = $('#date-to').val();
    
    if (!dateFrom || !dateTo) {
        showAlert('warning', 'Please select date range first.');
        return;
    }
    
    $.ajax({
        url: '../ajax/generate_specific_report.php',
        type: 'POST',
        data: {
            type: type,
            date_from: dateFrom,
            date_to: dateTo
        },
        success: function(response) {
            if (response.success) {
                $('#report-content').html(response.html);
                $('#reports-section').show();
            } else {
                showAlert('error', response.message);
            }
        }
    });
}

function exportReport() {
    const dateFrom = $('#date-from').val();
    const dateTo = $('#date-to').val();
    
    if (!dateFrom || !dateTo) {
        showAlert('warning', 'Please generate a report first.');
        return;
    }
    
    // Create form and submit for download
    const form = $('<form>', {
        method: 'POST',
        action: '../ajax/export_report.php'
    });
    
    form.append($('<input>', {
        type: 'hidden',
        name: 'date_from',
        value: dateFrom
    }));
    
    form.append($('<input>', {
        type: 'hidden',
        name: 'date_to',
        value: dateTo
    }));
    
    $('body').append(form);
    form.submit();
    form.remove();
}
</script>
