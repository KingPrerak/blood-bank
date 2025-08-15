<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Blood Collection - Simple</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2><i class="fas fa-tint me-2"></i>Blood Collection - Simple Test</h2>
        
        <!-- Search Section -->
        <div class="card mb-4">
            <div class="card-header">
                <h5><i class="fas fa-search me-2"></i>Search Donor</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <input type="text" class="form-control" id="donor-search" placeholder="Enter donor ID, name, or phone number">
                    </div>
                    <div class="col-md-4">
                        <button type="button" class="btn btn-primary" onclick="searchDonor()">
                            <i class="fas fa-search me-2"></i>Search
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Results Section -->
        <div id="results-section" style="display: none;">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-user me-2"></i>Search Results</h5>
                </div>
                <div class="card-body" id="results-content">
                    <!-- Results will be displayed here -->
                </div>
            </div>
        </div>

        <!-- Blood Collection Form -->
        <div id="collection-section" style="display: none;">
            <div class="card mt-4">
                <div class="card-header">
                    <h5><i class="fas fa-tint me-2"></i>Blood Collection</h5>
                </div>
                <div class="card-body">
                    <form id="collection-form">
                        <input type="hidden" id="selected-donor-id">
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Bag Number</label>
                                <input type="text" class="form-control" id="bag-number" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Component Type</label>
                                <select class="form-select" id="component-type">
                                    <option value="Whole Blood">Whole Blood</option>
                                    <option value="Red Blood Cells">Red Blood Cells</option>
                                    <option value="Plasma">Plasma</option>
                                    <option value="Platelets">Platelets</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Volume (ml)</label>
                                <input type="number" class="form-control" id="volume" value="450">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Collection Date</label>
                                <input type="date" class="form-control" id="collection-date">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Storage Location</label>
                                <select class="form-select" id="storage-location">
                                    <option value="Refrigerator A1">Refrigerator A1</option>
                                    <option value="Refrigerator A2">Refrigerator A2</option>
                                    <option value="Freezer B1">Freezer B1</option>
                                </select>
                            </div>
                        </div>
                        
                        <button type="button" class="btn btn-success" onclick="processCollection()">
                            <i class="fas fa-check me-2"></i>Complete Collection
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Simple alert function
        function showAlert(type, message) {
            const alertClass = type === 'success' ? 'alert-success' : 
                              type === 'error' ? 'alert-danger' : 
                              type === 'warning' ? 'alert-warning' : 'alert-info';
            
            const alertHtml = `<div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>`;
            
            // Remove existing alerts
            document.querySelectorAll('.alert').forEach(alert => alert.remove());
            
            // Add new alert
            document.querySelector('.container').insertAdjacentHTML('afterbegin', alertHtml);
            
            // Auto-dismiss
            setTimeout(() => {
                const alert = document.querySelector('.alert');
                if (alert) alert.remove();
            }, 5000);
        }

        // Search for donor
        function searchDonor() {
            const query = document.getElementById('donor-search').value.trim();
            
            if (query.length < 2) {
                showAlert('warning', 'Please enter at least 2 characters to search.');
                return;
            }

            showAlert('info', 'Searching for donors...');

            $.ajax({
                url: 'ajax/search_donor.php',
                type: 'GET',
                data: { query: query },
                success: function(response) {
                    console.log('Search response:', response);
                    
                    if (response.success) {
                        if (response.donor) {
                            // Single donor found
                            displayDonor(response.donor);
                            showAlert('success', response.message || 'Donor found!');
                        } else if (response.donors && response.donors.length > 0) {
                            // Multiple donors found
                            displayMultipleDonors(response.donors);
                            showAlert('info', `Found ${response.donors.length} donors.`);
                        } else {
                            showAlert('error', 'No donor data received.');
                        }
                    } else {
                        showAlert('error', response.message || 'No donors found.');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Search error:', error);
                    showAlert('error', 'Error searching for donors. Please try again.');
                }
            });
        }

        // Display single donor
        function displayDonor(donor) {
            const html = `
                <div class="row">
                    <div class="col-md-8">
                        <h5>${donor.full_name || (donor.first_name + ' ' + donor.last_name)}</h5>
                        <p><strong>Donor ID:</strong> ${donor.donor_id}</p>
                        <p><strong>Blood Group:</strong> <span class="badge bg-danger">${donor.blood_group}</span></p>
                        <p><strong>Phone:</strong> ${donor.phone}</p>
                        <p><strong>Age:</strong> ${donor.age} years</p>
                        <p><strong>Total Donations:</strong> ${donor.total_donations}</p>
                        <p><strong>Last Donation:</strong> ${donor.last_donation_date || 'Never'}</p>
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-success btn-lg" onclick="proceedToCollection(${donor.id}, '${donor.donor_id}')">
                            <i class="fas fa-tint me-2"></i>Proceed to Collection
                        </button>
                    </div>
                </div>
            `;
            
            document.getElementById('results-content').innerHTML = html;
            document.getElementById('results-section').style.display = 'block';
        }

        // Display multiple donors
        function displayMultipleDonors(donors) {
            let html = '<div class="row">';
            
            donors.forEach(donor => {
                html += `
                    <div class="col-md-6 mb-3">
                        <div class="card">
                            <div class="card-body">
                                <h6>${donor.full_name}</h6>
                                <p class="mb-1"><strong>ID:</strong> ${donor.donor_id}</p>
                                <p class="mb-1"><strong>Blood Group:</strong> <span class="badge bg-danger">${donor.blood_group}</span></p>
                                <p class="mb-1"><strong>Phone:</strong> ${donor.phone}</p>
                                <button class="btn btn-primary btn-sm" onclick="proceedToCollection(${donor.id}, '${donor.donor_id}')">
                                    Select
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            
            document.getElementById('results-content').innerHTML = html;
            document.getElementById('results-section').style.display = 'block';
        }

        // Proceed to collection
        function proceedToCollection(donorId, donorIdText) {
            document.getElementById('selected-donor-id').value = donorId;
            
            // Generate bag number
            const today = new Date();
            const bagNumber = 'BB' + today.getFullYear().toString().substr(-2) + 
                             (today.getMonth() + 1).toString().padStart(2, '0') + 
                             today.getDate().toString().padStart(2, '0') + 
                             Math.floor(Math.random() * 1000).toString().padStart(3, '0');
            
            document.getElementById('bag-number').value = bagNumber;
            document.getElementById('collection-date').value = today.toISOString().split('T')[0];
            
            document.getElementById('collection-section').style.display = 'block';
            document.getElementById('collection-section').scrollIntoView({ behavior: 'smooth' });
            
            showAlert('success', `Ready to collect blood from donor ${donorIdText}`);
        }

        // Process collection
        function processCollection() {
            const donorId = document.getElementById('selected-donor-id').value;
            const bagNumber = document.getElementById('bag-number').value;
            const componentType = document.getElementById('component-type').value;
            const volume = document.getElementById('volume').value;
            const collectionDate = document.getElementById('collection-date').value;
            const storageLocation = document.getElementById('storage-location').value;

            if (!donorId || !bagNumber || !componentType || !volume || !collectionDate) {
                showAlert('error', 'Please fill in all required fields.');
                return;
            }

            showAlert('info', 'Processing blood collection...');

            $.ajax({
                url: 'ajax/process_blood_collection.php',
                type: 'POST',
                data: {
                    donor_id: donorId,
                    bag_number: bagNumber,
                    component_type: componentType,
                    volume_ml: volume,
                    collection_date: collectionDate,
                    storage_location: storageLocation,
                    collection_staff: 'Test User'
                },
                success: function(response) {
                    if (response.success) {
                        showAlert('success', 'Blood collection completed successfully!');
                        
                        // Reset form
                        document.getElementById('collection-form').reset();
                        document.getElementById('collection-section').style.display = 'none';
                        document.getElementById('results-section').style.display = 'none';
                        document.getElementById('donor-search').value = '';
                    } else {
                        showAlert('error', response.message || 'Failed to process collection.');
                    }
                },
                error: function() {
                    showAlert('error', 'Error processing collection. Please try again.');
                }
            });
        }

        // Enter key support
        document.getElementById('donor-search').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchDonor();
            }
        });
    </script>
</body>
</html>
