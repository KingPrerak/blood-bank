/**
 * Enhanced Blood Collection System
 * Real-world blood collection process with comprehensive workflow
 */

// Auto-set current date and time
function initializeCollectionForm() {
    const now = new Date();
    const today = now.toISOString().split('T')[0];
    const currentTime = now.toTimeString().split(' ')[0].substring(0, 5);
    
    $('#collection_date').val(today);
    $('#collection_time').val(currentTime);
    
    // Generate bag number automatically
    generateBagNumber();
}

// Generate unique bag number
function generateBagNumber() {
    const prefix = 'BB';
    const date = new Date().toISOString().slice(0, 10).replace(/-/g, '');
    const random = Math.floor(Math.random() * 10000).toString().padStart(4, '0');
    const bagNumber = `${prefix}${date}${random}`;
    $('#bag_number').val(bagNumber);
}

// Update expiry date based on component type
function updateExpiryDate() {
    const componentType = $('#component_type').val();
    const collectionDate = new Date($('#collection_date').val());
    
    if (!collectionDate || !componentType) return;
    
    let expiryDays = 0;
    switch (componentType) {
        case 'Whole Blood':
            expiryDays = 35;
            $('#volume_ml').val(450);
            break;
        case 'Red Blood Cells':
            expiryDays = 42;
            $('#volume_ml').val(350);
            break;
        case 'Plasma':
            expiryDays = 365;
            $('#volume_ml').val(250);
            break;
        case 'Platelets':
            expiryDays = 5;
            $('#volume_ml').val(300);
            break;
        case 'Cryoprecipitate':
            expiryDays = 365;
            $('#volume_ml').val(200);
            break;
    }
    
    const expiryDate = new Date(collectionDate);
    expiryDate.setDate(expiryDate.getDate() + expiryDays);
    $('#expiry_date').val(expiryDate.toISOString().split('T')[0]);
}

// Validate all pre-collection checkboxes
function validatePreCollection() {
    const requiredChecks = [
        'consent_verified', 'identity_verified', 'questionnaire_completed',
        'arm_inspection', 'equipment_sterile', 'donor_comfortable'
    ];
    
    for (let check of requiredChecks) {
        if (!$(`#${check}`).is(':checked')) {
            showAlert('warning', `Please complete: ${$(`label[for="${check}"]`).text()}`);
            return false;
        }
    }
    return true;
}

// Validate all post-collection checkboxes
function validatePostCollection() {
    const requiredChecks = [
        'bleeding_stopped', 'donor_stable', 'refreshments_offered',
        'instructions_given', 'contact_info_updated'
    ];
    
    for (let check of requiredChecks) {
        if (!$(`#${check}`).is(':checked')) {
            showAlert('warning', `Please complete: ${$(`label[for="${check}"]`).text()}`);
            return false;
        }
    }
    return true;
}

// Save collection as draft
function saveAsDraft() {
    const formData = $('#blood-collection-form').serialize() + '&status=draft';
    
    makeAjaxCall('save_collection_draft.php', {
        type: 'POST',
        data: formData,
        success: function(response) {
            if (response.success) {
                showAlert('info', 'Collection saved as draft. You can complete it later.');
            } else {
                showAlert('error', response.message);
            }
        }
    });
}

// Enhanced blood collection form submission
function submitBloodCollection(event) {
    event.preventDefault();
    
    // Validate pre-collection checklist
    if (!validatePreCollection()) {
        return false;
    }
    
    // Validate post-collection checklist
    if (!validatePostCollection()) {
        return false;
    }
    
    // Validate required fields
    const requiredFields = ['bag_number', 'component_type', 'volume_ml', 'collection_date', 'collection_time', 'storage_location', 'collection_staff'];
    for (let field of requiredFields) {
        if (!$(`#${field}`).val()) {
            showAlert('error', `Please fill in: ${$(`label[for="${field}"]`).text()}`);
            return false;
        }
    }
    
    // Validate volume based on component type
    const volume = parseInt($('#volume_ml').val());
    const componentType = $('#component_type').val();
    
    if (componentType === 'Whole Blood' && (volume < 405 || volume > 495)) {
        showAlert('warning', 'Whole blood volume should be 450ml ± 10% (405-495ml)');
        return false;
    }
    
    const formData = $('#blood-collection-form').serialize() + '&status=completed';
    
    makeAjaxCall('process_blood_collection.php', {
        type: 'POST',
        data: formData,
        success: function(response) {
            if (response.success) {
                showAlert('success', 'Blood collection completed successfully!');
                
                // Print collection label
                if (confirm('Would you like to print the blood bag label?')) {
                    printBloodBagLabel(response.collection_data);
                }
                
                // Reset form and load recent collections
                resetCollectionForm();
                loadRecentCollections();
                
                // Update donor's last donation date
                updateDonorLastDonation();
                
            } else {
                showAlert('error', response.message);
            }
        }
    });
}

// Print blood bag label
function printBloodBagLabel(collectionData) {
    const printWindow = window.open('', '_blank');
    const labelHtml = `
        <html>
        <head>
            <title>Blood Bag Label</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .label { border: 2px solid #000; padding: 15px; width: 300px; }
                .header { text-align: center; font-weight: bold; font-size: 16px; margin-bottom: 10px; }
                .field { margin: 5px 0; }
                .barcode { text-align: center; font-family: monospace; font-size: 12px; margin: 10px 0; }
            </style>
        </head>
        <body>
            <div class="label">
                <div class="header">BLOOD BANK LABEL</div>
                <div class="field"><strong>Bag Number:</strong> ${collectionData.bag_number}</div>
                <div class="field"><strong>Blood Group:</strong> ${collectionData.blood_group}</div>
                <div class="field"><strong>Component:</strong> ${collectionData.component_type}</div>
                <div class="field"><strong>Volume:</strong> ${collectionData.volume_ml}ml</div>
                <div class="field"><strong>Collection Date:</strong> ${collectionData.collection_date}</div>
                <div class="field"><strong>Expiry Date:</strong> ${collectionData.expiry_date}</div>
                <div class="field"><strong>Storage:</strong> ${collectionData.storage_location}</div>
                <div class="barcode">||||| ${collectionData.bag_number} |||||</div>
            </div>
            <script>window.print(); window.close();</script>
        </body>
        </html>
    `;
    
    printWindow.document.write(labelHtml);
    printWindow.document.close();
}

// Reset collection form
function resetCollectionForm() {
    $('#blood-collection-form')[0].reset();
    $('#blood-collection-card').hide();
    $('#medical-screening-card').hide();
    $('#donor-info-section').addClass('d-none');
    
    // Uncheck all checkboxes
    $('input[type="checkbox"]').prop('checked', false);
    
    // Clear donor lookup
    $('#donor-lookup').val('');
    
    initializeCollectionForm();
}

// Update donor's last donation date
function updateDonorLastDonation() {
    const donorId = $('#collection-donor-id').val();
    if (donorId) {
        makeAjaxCall('update_donor_last_donation.php', {
            type: 'POST',
            data: { donor_id: donorId },
            success: function(response) {
                console.log('Donor last donation date updated');
            }
        });
    }
}

// Initialize when document is ready
$(document).ready(function() {
    initializeCollectionForm();
    
    // Bind form submission
    $('#blood-collection-form').on('submit', submitBloodCollection);
    
    // Auto-update expiry date when component type changes
    $('#component_type').on('change', updateExpiryDate);
    
    // Auto-update expiry date when collection date changes
    $('#collection_date').on('change', updateExpiryDate);
});

// Export functions for global use
window.generateBagNumber = generateBagNumber;
window.updateExpiryDate = updateExpiryDate;
window.saveAsDraft = saveAsDraft;
window.resetCollectionForm = resetCollectionForm;
