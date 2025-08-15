<?php
/**
 * Generic endpoint for handling missing AJAX requests
 */

header('Content-Type: application/json');
http_response_code(404);

$requestedFile = $_SERVER['REQUEST_URI'] ?? 'unknown';

echo json_encode([
    'success' => false,
    'message' => 'Endpoint not implemented yet',
    'requested' => $requestedFile,
    'note' => 'This functionality will be available in the next update'
]);
?>
