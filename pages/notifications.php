<?php
require_once '../config/config.php';
requireLogin();

// Get notifications for current user
try {
    $db = getDB();
    
    // Get unread notifications
    $stmt = $db->prepare("
        SELECT * FROM notifications 
        WHERE (user_id = ? OR user_id IS NULL) AND is_read = FALSE
        ORDER BY priority DESC, created_at DESC
    ");
    $stmt->execute([getCurrentUserId()]);
    $unreadNotifications = $stmt->fetchAll();
    
    // Get all notifications (last 50)
    $stmt = $db->prepare("
        SELECT * FROM notifications 
        WHERE (user_id = ? OR user_id IS NULL)
        ORDER BY created_at DESC
        LIMIT 50
    ");
    $stmt->execute([getCurrentUserId()]);
    $allNotifications = $stmt->fetchAll();
    
    // Get notification counts by type
    $stmt = $db->prepare("
        SELECT type, COUNT(*) as count 
        FROM notifications 
        WHERE (user_id = ? OR user_id IS NULL) AND is_read = FALSE
        GROUP BY type
    ");
    $stmt->execute([getCurrentUserId()]);
    $notificationCounts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
} catch (Exception $e) {
    error_log("Notifications error: " . $e->getMessage());
    $unreadNotifications = [];
    $allNotifications = [];
    $notificationCounts = [];
}
?>

<div class="row mb-4">
    <div class="col-12">
        <h2><i class="fas fa-bell me-2"></i>Notifications & Alerts</h2>
    </div>
</div>

<!-- Notification Summary Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-danger text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4><?php echo $notificationCounts['expiry_alert'] ?? 0; ?></h4>
                        <p class="mb-0">Expiry Alerts</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-clock fa-2x"></i>
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
                        <h4><?php echo $notificationCounts['low_stock'] ?? 0; ?></h4>
                        <p class="mb-0">Low Stock Alerts</p>
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
                        <h4><?php echo $notificationCounts['critical_request'] ?? 0; ?></h4>
                        <p class="mb-0">Critical Requests</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-hand-holding-medical fa-2x"></i>
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
                        <h4><?php echo count($unreadNotifications); ?></h4>
                        <p class="mb-0">Total Unread</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-bell fa-2x"></i>
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
                <h5><i class="fas fa-cogs me-2"></i>Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <button class="btn btn-primary w-100" onclick="markAllAsRead()">
                            <i class="fas fa-check-double me-2"></i>Mark All Read
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-warning w-100" onclick="generateExpiryAlerts()">
                            <i class="fas fa-clock me-2"></i>Generate Expiry Alerts
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-info w-100" onclick="checkLowStock()">
                            <i class="fas fa-boxes me-2"></i>Check Low Stock
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-success w-100" onclick="notificationSettings()">
                            <i class="fas fa-cog me-2"></i>Settings
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Unread Notifications -->
<?php if (!empty($unreadNotifications)): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-warning text-dark">
                <h5><i class="fas fa-bell me-2"></i>Unread Notifications (<?php echo count($unreadNotifications); ?>)</h5>
            </div>
            <div class="card-body">
                <?php foreach ($unreadNotifications as $notification): ?>
                <div class="alert alert-<?php echo getPriorityClass($notification['priority']); ?> alert-dismissible fade show" role="alert">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <h6 class="alert-heading">
                                <i class="<?php echo getTypeIcon($notification['type']); ?> me-2"></i>
                                <?php echo htmlspecialchars($notification['title']); ?>
                                <span class="badge bg-<?php echo getPriorityClass($notification['priority']); ?> ms-2">
                                    <?php echo ucfirst($notification['priority']); ?>
                                </span>
                            </h6>
                            <p class="mb-1"><?php echo htmlspecialchars($notification['message']); ?></p>
                            <small class="text-muted">
                                <?php echo formatDateTime($notification['created_at']); ?>
                                <?php if ($notification['action_required']): ?>
                                <span class="badge bg-danger ms-2">Action Required</span>
                                <?php endif; ?>
                            </small>
                        </div>
                        <div class="flex-shrink-0 ms-3">
                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                    onclick="markAsRead(<?php echo $notification['id']; ?>)">
                                <i class="fas fa-check"></i> Mark Read
                            </button>
                            <?php if ($notification['action_url']): ?>
                            <a href="<?php echo htmlspecialchars($notification['action_url']); ?>" 
                               class="btn btn-sm btn-primary ms-1">
                                <i class="fas fa-external-link-alt"></i> Take Action
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- All Notifications -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-list me-2"></i>All Notifications</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Title</th>
                                <th>Message</th>
                                <th>Priority</th>
                                <th>Date/Time</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allNotifications as $notification): ?>
                            <tr class="<?php echo $notification['is_read'] ? '' : 'table-warning'; ?>">
                                <td>
                                    <i class="<?php echo getTypeIcon($notification['type']); ?> me-2"></i>
                                    <?php echo ucfirst(str_replace('_', ' ', $notification['type'])); ?>
                                </td>
                                <td><?php echo htmlspecialchars($notification['title']); ?></td>
                                <td>
                                    <?php 
                                    $message = htmlspecialchars($notification['message']);
                                    echo strlen($message) > 50 ? substr($message, 0, 50) . '...' : $message;
                                    ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo getPriorityClass($notification['priority']); ?>">
                                        <?php echo ucfirst($notification['priority']); ?>
                                    </span>
                                </td>
                                <td><?php echo formatDateTime($notification['created_at']); ?></td>
                                <td>
                                    <?php if ($notification['is_read']): ?>
                                    <span class="badge bg-success">Read</span>
                                    <?php else: ?>
                                    <span class="badge bg-warning">Unread</span>
                                    <?php endif; ?>
                                    
                                    <?php if ($notification['action_required']): ?>
                                    <span class="badge bg-danger">Action Required</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!$notification['is_read']): ?>
                                    <button class="btn btn-sm btn-outline-primary" 
                                            onclick="markAsRead(<?php echo $notification['id']; ?>)">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <?php endif; ?>
                                    
                                    <?php if ($notification['action_url']): ?>
                                    <a href="<?php echo htmlspecialchars($notification['action_url']); ?>" 
                                       class="btn btn-sm btn-primary">
                                        <i class="fas fa-external-link-alt"></i>
                                    </a>
                                    <?php endif; ?>
                                    
                                    <button class="btn btn-sm btn-outline-danger" 
                                            onclick="deleteNotification(<?php echo $notification['id']; ?>)">
                                        <i class="fas fa-trash"></i>
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

<!-- Notification Settings Modal -->
<div class="modal fade" id="settingsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Notification Settings</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="notification-settings-form">
                    <div class="mb-3">
                        <label class="form-label">Email Notifications</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="email-expiry" name="email_expiry">
                            <label class="form-check-label" for="email-expiry">
                                Expiry Alerts
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="email-low-stock" name="email_low_stock">
                            <label class="form-check-label" for="email-low-stock">
                                Low Stock Alerts
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="email-critical" name="email_critical">
                            <label class="form-check-label" for="email-critical">
                                Critical Requests
                            </label>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="expiry-alert-days" class="form-label">Expiry Alert Days</label>
                        <input type="number" class="form-control" id="expiry-alert-days" name="expiry_alert_days" 
                               min="1" max="30" value="7">
                        <small class="form-text text-muted">Days before expiry to show alerts</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="low-stock-threshold" class="form-label">Low Stock Threshold</label>
                        <input type="number" class="form-control" id="low-stock-threshold" name="low_stock_threshold" 
                               min="1" max="50" value="5">
                        <small class="form-text text-muted">Minimum units before low stock alert</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveNotificationSettings()">
                    <i class="fas fa-save me-2"></i>Save Settings
                </button>
            </div>
        </div>
    </div>
</div>

<?php
function getPriorityClass($priority) {
    switch ($priority) {
        case 'critical': return 'danger';
        case 'high': return 'warning';
        case 'medium': return 'info';
        case 'low': return 'secondary';
        default: return 'secondary';
    }
}

function getTypeIcon($type) {
    switch ($type) {
        case 'expiry_alert': return 'fas fa-clock text-warning';
        case 'low_stock': return 'fas fa-exclamation-triangle text-warning';
        case 'critical_request': return 'fas fa-hand-holding-medical text-danger';
        case 'system_alert': return 'fas fa-cog text-info';
        case 'disposal_reminder': return 'fas fa-trash text-secondary';
        default: return 'fas fa-bell text-primary';
    }
}
?>

<script>
function markAsRead(notificationId) {
    $.ajax({
        url: '../ajax/mark_notification_read.php',
        type: 'POST',
        data: { notification_id: notificationId },
        success: function(response) {
            if (response.success) {
                location.reload();
            } else {
                showAlert('error', response.message);
            }
        }
    });
}

function markAllAsRead() {
    if (confirm('Mark all notifications as read?')) {
        $.ajax({
            url: '../ajax/mark_all_notifications_read.php',
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

function deleteNotification(notificationId) {
    if (confirm('Delete this notification?')) {
        $.ajax({
            url: '../ajax/delete_notification.php',
            type: 'POST',
            data: { notification_id: notificationId },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    showAlert('error', response.message);
                }
            }
        });
    }
}

function generateExpiryAlerts() {
    $.ajax({
        url: '../ajax/generate_expiry_alerts.php',
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

function checkLowStock() {
    $.ajax({
        url: '../ajax/check_low_stock.php',
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

function notificationSettings() {
    $('#settingsModal').modal('show');
}

function saveNotificationSettings() {
    const formData = new FormData($('#notification-settings-form')[0]);
    
    $.ajax({
        url: '../ajax/save_notification_settings.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                showAlert('success', response.message);
                $('#settingsModal').modal('hide');
            } else {
                showAlert('error', response.message);
            }
        }
    });
}
</script>
