<?php
require_once 'config/config.php';
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
    
} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $totalDonors = $totalUnits = $pendingRequests = $todayDonations = 0;
    $bloodGroupInventory = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/dashboard.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-danger fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="fas fa-tint me-2"></i><?php echo APP_NAME; ?>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="fas fa-home me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" onclick="loadPage('donor-registration')">
                            <i class="fas fa-user-plus me-1"></i>Donor Registration
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" onclick="loadPage('blood-collection')">
                            <i class="fas fa-tint me-1"></i>Blood Collection
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" onclick="loadPage('blood-requests')">
                            <i class="fas fa-hand-holding-medical me-1"></i>Blood Requests
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" onclick="loadPage('inventory')">
                            <i class="fas fa-boxes me-1"></i>Inventory
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" onclick="loadPage('reports')">
                            <i class="fas fa-chart-bar me-1"></i>Reports
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" onclick="loadPage('expired-management')">
                            <i class="fas fa-exclamation-triangle me-1"></i>Expired Management
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" onclick="loadPage('crossmatch-lab')">
                            <i class="fas fa-microscope me-1"></i>Cross-match Lab
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" onclick="loadPage('notifications')">
                            <i class="fas fa-bell me-1"></i>Notifications
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?php echo getCurrentUserName(); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" onclick="loadPage('profile')">
                                <i class="fas fa-user-cog me-2"></i>Profile
                            </a></li>
                            <?php if (isAdmin()): ?>
                            <li><a class="dropdown-item" href="#" onclick="loadPage('user-management')">
                                <i class="fas fa-users-cog me-2"></i>User Management
                            </a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container-fluid" style="margin-top:200px;">
        <div id="main-content">
            <!-- Dashboard Home Content -->
            <div class="row mb-4">
                <div class="col-12">
                    <h2><i class="fas fa-tachometer-alt me-2"></i>Dashboard Overview</h2>
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
                    </div>
                </div>
            </div>
            
            <!-- Blood Group Inventory -->
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-chart-bar me-2"></i>Blood Group Inventory</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php foreach ($bloodGroupInventory as $group): ?>
                                <div class="col-md-3 mb-3">
                                    <div class="text-center p-3 border rounded">
                                        <h4 class="text-danger"><?php echo $group['blood_group']; ?></h4>
                                        <p class="mb-0"><?php echo $group['units']; ?> Units</p>
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
                            <h5><i class="fas fa-exclamation-triangle me-2"></i>Quick Actions</h5>
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
                                <button class="btn btn-danger" onclick="loadPage('expired-management')">
                                    <i class="fas fa-exclamation-triangle me-2"></i>Expired Management
                                </button>
                                <button class="btn btn-secondary" onclick="loadPage('crossmatch-lab')">
                                    <i class="fas fa-microscope me-2"></i>Cross-match Lab
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loading-overlay" class="d-none">
        <div class="spinner-border text-danger" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="assets/js/ajax-path-resolver.js"></script>
    <script src="assets/js/dashboard.js"></script>
</body>
</html>
