<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blood Collection - Final Working Version</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid mt-3">
        <div class="row">
            <div class="col-12">
                <h2><i class="fas fa-tint me-2 text-danger"></i>Blood Collection - Final Working Version</h2>
                <p class="text-muted">Search for donors and complete blood collection without JavaScript errors</p>
            </div>
        </div>

        <!-- Search Section -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5><i class="fas fa-search me-2"></i>Search Donor</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <input type="text" class="form-control" id="donor-search" 
                               placeholder="Enter donor ID, name, or phone number" 
                               value="prerak">
                    </div>
                    <div class="col-md-4">
                        <button type="button" class="btn btn-primary w-100" onclick="searchDonor()">
                            <i class="fas fa-search me-2"></i>Search Donor
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Results Section -->
        <div id="results-section" style="display: none;">
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5><i class="fas fa-user-check me-2"></i>Donor Found</h5>
                </div>
                <div class="card-body" id="results-content">
                    <!-- Results will be displayed here -->
                </div>
            </div>
        </div>

        <!-- Blood Collection Form -->
        <div id="collection-section" style="display: none;">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h5><i class="fas fa-tint me-2"></i>Blood Collection Details</h5>
                </div>
                <div class="card-body">
                    <form id="collection-form">
                        <input type="hidden" id="selected-donor-id">
                        
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label class="form-label">Bag Number</label>
                                <input type="text" class="form-control" id="bag-number" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Component Type</label>
                                <select class="form-select" id="component-type">
                                    <option value="Whole Blood">Whole Blood</option>
                                    <option value="Red Blood Cells">Red Blood Cells</option>
                                    <option value="Plasma">Plasma</option>
                                    <option value="Platelets">Platelets</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Volume (ml)</label>
                                <input type="number" class="form-control" id="volume" value="450">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Collection Date</label>
                                <input type="date" class="form-control" id="collection-date">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Storage Location</label>
                                <select class="form-select" id="storage-location">
                                    <option value="Refrigerator A1">Refrigerator A1 (2-6°C)</option>
                                    <option value="Refrigerator A2">Refrigerator A2 (2-6°C)</option>
                                    <option value="Freezer B1">Freezer B1 (-18°C)</option>
                                    <option value="Freezer B2">Freezer B2 (-18°C)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Collection Staff</label>
                                <input type="text" class="form-control" id="collection-staff" value="Medical Staff">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-12">
                                <button type="button" class="btn btn-success btn-lg me-2" onclick="processCollection()">
                                    <i class="fas fa-check-circle me-2"></i>Complete Blood Collection
                                </button>
                                <button type="button" class="btn btn-secondary" onclick="resetForm()">
                                    <i class="fas fa-undo me-2"></i>Reset Form
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Bulletproof alert function
        function showAlert(type, message) {
            console.log('Alert:', type, message);
            
            try {
                const alertClass = type === 'success' ? 'alert-success' : 
                                  type === 'error' ? 'alert-danger' : 
                                  type === 'warning' ? 'alert-warning' : 'alert-info';
                
                const icon = type === 'success' ? 'check-circle' : 
                            type === 'error' ? 'exclamation-triangle' : 
                            type === 'warning' ? 'exclamation-circle' : 'info-circle';
                
                const alertHtml = `
                    <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                        <i class="fas fa-${icon} me-2"></i>${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `;
                
                // Remove existing alerts
                const existingAlerts = document.querySelectorAll('.alert');
                existingAlerts.forEach(alert => {
                    if (alert && alert.parentNode) {
                        alert.remove();
                    }
                });
                
                // Add new alert
                const container = document.querySelector('.container-fluid');
                if (container) {
                    container.insertAdjacentHTML('afterbegin', alertHtml);
                }
                
                // Auto-dismiss
                setTimeout(() => {
                    const alertToRemove = document.querySelector('.alert');
                    if (alertToRemove && alertToRemove.parentNode) {
                        alertToRemove.style.opacity = '0';
                        setTimeout(() => {
                            if (alertToRemove && alertToRemove.parentNode) {
                                alertToRemove.remove();
                            }
                        }, 300);
                    }
                }, 5000);
                
            } catch (error) {
                console.error('Alert error:', error);
                console.log('Message:', message);
            }
        }

        // Search for donor
        function searchDonor() {
            const query = document.getElementById('donor-search').value.trim();
            
            if (query.length < 2) {
                showAlert('warning', 'Please enter at least 2 characters to search.');
                return;
            }

            showAlert('info', 'Searching for donors...');

            fetch('ajax/search_donor.php?query=' + encodeURIComponent(query))
                .then(response => response.json())
                .then(data => {
                    console.log('Search response:', data);
                    
                    if (data.success && data.donor) {
                        displayDonor(data.donor);
                        showAlert('success', data.message || 'Donor found and eligible for donation!');
                    } else {
                        showAlert('error', data.message || 'No donors found matching your search.');
                        document.getElementById('results-section').style.display = 'none';
                    }
                })
                .catch(error => {
                    console.error('Search error:', error);
                    showAlert('error', 'Error searching for donors. Please try again.');
                });
        }

        // Display donor information
        function displayDonor(donor) {
            console.log('Displaying donor:', donor);
            
            const html = `
                <div class="row">
                    <div class="col-md-8">
                        <h4 class="text-primary">${donor.first_name} ${donor.last_name}</h4>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Donor ID:</strong> <span class="badge bg-primary">${donor.donor_id}</span></p>
                                <p><strong>Blood Group:</strong> <span class="badge bg-danger fs-6">${donor.blood_group}</span></p>
                                <p><strong>Phone:</strong> ${donor.phone}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Age:</strong> ${donor.age} years</p>
                                <p><strong>Total Donations:</strong> ${donor.total_donations || 0}</p>
                                <p><strong>Last Donation:</strong> ${donor.last_donation_date || 'Never'}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 text-end">
                        <button class="btn btn-danger btn-lg" onclick="proceedToCollection(${donor.id}, '${donor.donor_id}')">
                            <i class="fas fa-tint me-2"></i>Proceed to Collection
                        </button>
                    </div>
                </div>
            `;
            
            document.getElementById('results-content').innerHTML = html;
            document.getElementById('results-section').style.display = 'block';
        }

        // Proceed to blood collection
        function proceedToCollection(donorId, donorIdText) {
            document.getElementById('selected-donor-id').value = donorId;
            
            // Generate bag number
            const today = new Date();
            const bagNumber = 'BB' + 
                today.getFullYear().toString().substr(-2) + 
                (today.getMonth() + 1).toString().padStart(2, '0') + 
                today.getDate().toString().padStart(2, '0') + 
                Math.floor(Math.random() * 10000).toString().padStart(4, '0');
            
            document.getElementById('bag-number').value = bagNumber;
            document.getElementById('collection-date').value = today.toISOString().split('T')[0];
            
            document.getElementById('collection-section').style.display = 'block';
            document.getElementById('collection-section').scrollIntoView({ behavior: 'smooth' });
            
            showAlert('success', `Ready to collect blood from donor ${donorIdText}`);
        }

        // Process blood collection
        function processCollection() {
            const donorId = document.getElementById('selected-donor-id').value;
            const bagNumber = document.getElementById('bag-number').value;
            const componentType = document.getElementById('component-type').value;
            const volume = document.getElementById('volume').value;
            const collectionDate = document.getElementById('collection-date').value;
            const storageLocation = document.getElementById('storage-location').value;
            const collectionStaff = document.getElementById('collection-staff').value;

            if (!donorId || !bagNumber || !componentType || !volume || !collectionDate) {
                showAlert('error', 'Please fill in all required fields.');
                return;
            }

            showAlert('info', 'Processing blood collection...');

            const formData = new FormData();
            formData.append('donor_id', donorId);
            formData.append('bag_number', bagNumber);
            formData.append('component_type', componentType);
            formData.append('volume_ml', volume);
            formData.append('collection_date', collectionDate);
            formData.append('storage_location', storageLocation);
            formData.append('collection_staff', collectionStaff);

            fetch('ajax/process_blood_collection.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', 'Blood collection completed successfully!');
                    resetForm();
                } else {
                    showAlert('error', data.message || 'Failed to process collection.');
                }
            })
            .catch(error => {
                console.error('Collection error:', error);
                showAlert('error', 'Error processing collection. Please try again.');
            });
        }

        // Reset form
        function resetForm() {
            document.getElementById('collection-form').reset();
            document.getElementById('collection-section').style.display = 'none';
            document.getElementById('results-section').style.display = 'none';
            document.getElementById('donor-search').value = '';
            showAlert('info', 'Form reset. You can search for another donor.');
        }

        // Enter key support
        document.getElementById('donor-search').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchDonor();
            }
        });

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Blood collection page loaded successfully');
            showAlert('info', 'Blood collection system ready. Search for a donor to begin.');
        });
    </script>
</body>
</html>
