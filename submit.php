<?php
// Set headers for CORS and JSON response
// IMPORTANT: For production, replace '*' with your actual domain for better security.
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

/**
 * Handles incoming application data via POST request, validates it, and saves it as a unique JSON file.
 */

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method Not Allowed. Only POST requests are accepted."]);
    exit();
}

// 1. Get the raw JSON POST data
$data = file_get_contents("php://input");

// Decode the JSON data into a PHP array
$application_data = json_decode($data, true);

// Basic validation: ensure data was received and decoded properly
if (empty($application_data) || !is_array($application_data)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Invalid or empty JSON data received in the request body."]);
    exit();
}

// --- FILE SAVING LOGIC ---

// Define the directory where applications will be saved.
$save_directory = 'applications_data/';

// Create the directory if it doesn't exist
if (!is_dir($save_directory)) {
    // Attempt to create the directory with full permissions recursively
    if (!mkdir($save_directory, 0777, true)) {
        http_response_code(500);
        // ENHANCED ERROR MESSAGE
        echo json_encode([
            "success" => false, 
            "message" => "FATAL ERROR: Could not create save directory: '{$save_directory}'. Please ensure the PHP server has WRITE permissions for the parent folder."
        ]);
        exit();
    }
}

// Generate a unique filename
$applicant_name = 'unknown_applicant';

if (isset($application_data['full_names']) && is_string($application_data['full_names'])) {
    $name_safe = preg_replace('/[^A-Za-z0-9_\- ]/', '', $application_data['full_names']);
    $name_safe = str_replace(' ', '_', $name_safe);
    
    if (!empty($name_safe)) {
        $applicant_name = $name_safe;
    }
}

$timestamp = date('Ymd_His');
$filename = $save_directory . $timestamp . '_' . $applicant_name . '.json';

// Prepare the final JSON content
$json_content = json_encode($application_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

if ($json_content === false) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "JSON encoding failed: " . json_last_error_msg()]);
    exit();
}

// Write the data to the file
if (file_put_contents($filename, $json_content) !== false) {
    // Success response
    http_response_code(200);
    echo json_encode([
        "success" => true,
        "message" => "Application saved successfully.",
        "filename" => basename($filename)
    ]);
} else {
    // Failure response during file writing
    http_response_code(500);
    // ENHANCED ERROR MESSAGE
    echo json_encode([
        "success" => false,
        "message" => "File Write Error: Failed to write data to file. Check if the PHP script has WRITE permissions to the existing '{$save_directory}' folder."
    ]);
}
?>