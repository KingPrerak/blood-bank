<?php
// Handle different include paths
if (file_exists('../config/config.php')) {
    require_once '../config/config.php';
} elseif (file_exists('config/config.php')) {
    require_once 'config/config.php';
} else {
    die('Config file not found');
}
requireLogin();

// Get dashboard statistics
try {
    $db = getDB();
    
    // Total donors
    $stmt = $db->query("SELECT COUNT(*) as total FROM donors WHERE status = 'active'");
    $totalDonors = $stmt->fetch()['total'];
    
    // Total blood units available
    $stmt = $db->query("SELECT COUNT(*) as total FROM blood_inventory WHERE status = 'available' AND expiry_date > CURDATE()");
    $totalUnits = $stmt->fetch()['total'];
    
    // Pending requests
    $stmt = $db->query("SELECT COUNT(*) as total FROM blood_requests WHERE status = 'pending'");
    $pendingRequests = $stmt->fetch()['total'];
    
    // Today's donations
    $stmt = $db->query("SELECT COUNT(*) as total FROM blood_donations WHERE donation_date = CURDATE()");
    $todayDonations = $stmt->fetch()['total'];
    
    // Blood group wise inventory
    $stmt = $db->query("
        SELECT bg.blood_group, COUNT(bi.id) as units 
        FROM blood_groups bg 
        LEFT JOIN blood_inventory bi ON bg.id = bi.blood_group_id 
            AND bi.status = 'available' 
            AND bi.expiry_date > CURDATE()
        GROUP BY bg.id, bg.blood_group 
        ORDER BY bg.blood_group
    ");
    $bloodGroupInventory = $stmt->fetchAll();
    
    // Recent activities
    $stmt = $db->query("
        SELECT al.action, al.details, al.created_at, u.full_name
        FROM activity_logs al
        LEFT JOIN users u ON al.user_id = u.id
        ORDER BY al.created_at DESC
        LIMIT 10
    ");
    $recentActivities = $stmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $totalDonors = $totalUnits = $pendingRequests = $todayDonations = 0;
    $bloodGroupInventory = [];
    $recentActivities = [];
}
?>

<!-- Dashboard Home Content -->
<div class="row mb-4">
    <div class="col-12">
        <h2><i class="fas fa-tachometer-alt me-2"></i>Dashboard Overview</h2>
        <p class="text-muted">Welcome back, <?php echo getCurrentUserName(); ?>! Here's what's happening in your blood bank today.</p>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4><?php echo number_format($totalDonors); ?></h4>
                        <p class="mb-0">Total Donors</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-users fa-2x"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-primary bg-opacity-75">
                <small>Active registered donors</small>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4><?php echo number_format($totalUnits); ?></h4>
                        <p class="mb-0">Available Units</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-tint fa-2x"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-success bg-opacity-75">
                <small>Ready for distribution</small>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4><?php echo number_format($pendingRequests); ?></h4>
                        <p class="mb-0">Pending Requests</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-clock fa-2x"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-warning bg-opacity-75">
                <small>Awaiting approval</small>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4><?php echo number_format($todayDonations); ?></h4>
                        <p class="mb-0">Today's Donations</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-calendar-day fa-2x"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-info bg-opacity-75">
                <small><?php echo date('d M Y'); ?></small>
            </div>
        </div>
    </div>
</div>

<!-- Blood Group Inventory and Quick Actions -->
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-chart-bar me-2"></i>Blood Group Inventory</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($bloodGroupInventory as $group): ?>
                    <div class="col-md-3 mb-3">
                        <div class="text-center p-3 border rounded blood-group-card">
                            <h4 class="text-white"><?php echo $group['blood_group']; ?></h4>
                            <p class="mb-0 text-white"><?php echo $group['units']; ?> Units</p>
                            <?php if ($group['units'] < 5): ?>
                            <small class="text-warning"><i class="fas fa-exclamation-triangle"></i> Low Stock</small>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <button class="btn btn-primary" onclick="loadPage('donor-registration')">
                        <i class="fas fa-user-plus me-2"></i>Register New Donor
                    </button>
                    <button class="btn btn-success" onclick="loadPage('blood-collection')">
                        <i class="fas fa-tint me-2"></i>Record Donation
                    </button>
                    <button class="btn btn-warning" onclick="loadPage('blood-requests')">
                        <i class="fas fa-hand-holding-medical me-2"></i>Blood Request
                    </button>
                    <button class="btn btn-info" onclick="loadPage('inventory')">
                        <i class="fas fa-boxes me-2"></i>View Inventory
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activities and Alerts -->
<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-history me-2"></i>Recent Activities</h5>
            </div>
            <div class="card-body">
                <?php if (empty($recentActivities)): ?>
                    <p class="text-muted">No recent activities found.</p>
                <?php else: ?>
                    <div class="timeline">
                        <?php foreach ($recentActivities as $activity): ?>
                        <div class="timeline-item mb-3">
                            <div class="d-flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-circle text-primary" style="font-size: 0.5rem; margin-top: 0.5rem;"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <div class="fw-bold"><?php echo htmlspecialchars($activity['action']); ?></div>
                                    <?php if ($activity['details']): ?>
                                    <div class="text-muted small"><?php echo htmlspecialchars($activity['details']); ?></div>
                                    <?php endif; ?>
                                    <div class="text-muted small">
                                        <?php echo formatDateTime($activity['created_at']); ?>
                                        <?php if ($activity['full_name']): ?>
                                        by <?php echo htmlspecialchars($activity['full_name']); ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-bell me-2"></i>System Alerts</h5>
            </div>
            <div class="card-body">
                <?php
                // Check for low stock alerts
                $lowStockGroups = array_filter($bloodGroupInventory, function($group) {
                    return $group['units'] < 5;
                });
                
                // Check for expiring units
                try {
                    $stmt = $db->query("
                        SELECT COUNT(*) as count 
                        FROM blood_inventory 
                        WHERE status = 'available' 
                        AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                    ");
                    $expiringUnits = $stmt->fetch()['count'];
                } catch (Exception $e) {
                    $expiringUnits = 0;
                }
                ?>
                
                <?php if (!empty($lowStockGroups)): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Low Stock Alert:</strong> 
                    <?php 
                    $lowStockNames = array_column($lowStockGroups, 'blood_group');
                    echo implode(', ', $lowStockNames);
                    ?> blood group(s) have low inventory.
                </div>
                <?php endif; ?>
                
                <?php if ($expiringUnits > 0): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-clock me-2"></i>
                    <strong>Expiry Alert:</strong> 
                    <?php echo $expiringUnits; ?> blood unit(s) will expire within 7 days.
                </div>
                <?php endif; ?>
                
                <?php if ($pendingRequests > 0): ?>
                <div class="alert alert-info">
                    <i class="fas fa-hand-holding-medical me-2"></i>
                    <strong>Pending Requests:</strong> 
                    <?php echo $pendingRequests; ?> blood request(s) are awaiting approval.
                </div>
                <?php endif; ?>
                
                <?php if (empty($lowStockGroups) && $expiringUnits == 0 && $pendingRequests == 0): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>All Good!</strong> No critical alerts at this time.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
