<?php
include("../config/db_config.php");
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit();
}

header('Content-Type: application/json; charset=utf-8');

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['parts']) || !is_array($data['parts'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid parts data.']);
    exit;
}

// Required fields
$requiredFields = ['part_no', 'type_and_classification', 'description', 'technical_description'];
$failedRecords = [];

foreach ($data['parts'] as $index => $part) {
    foreach ($requiredFields as $field) {
        if (empty($part[$field])) {
            $failedRecords[] = [
                'index' => $index + 1,
                'missing_field' => $field,
                'part_data' => $part
            ];
            continue 2; // Skip this part and go to next
        }
    }

    // Sanitize inputs (basic)
    $part_no = mysqli_real_escape_string($conn, $part['part_no']);
    $type = mysqli_real_escape_string($conn, $part['type_and_classification']);
    $desc = mysqli_real_escape_string($conn, $part['description']);
    $tech_desc = mysqli_real_escape_string($conn, $part['technical_description']);
    $uom = mysqli_real_escape_string($conn, $part['uom']);
    $amc = isset($part['amc']) ? mysqli_real_escape_string($conn, $part['amc']) : '';
    $non_amc = isset($part['non_amc']) ? mysqli_real_escape_string($conn, $part['non_amc']) : '';

    $query = "
        INSERT INTO parts (part_no, type_and_classification, description, technical_description, uom, amc, non_amc)
        VALUES ('$part_no', '$type', '$desc', '$tech_desc', '$uom', '$amc', '$non_amc')
    ";

    mysqli_query($conn, $query);
}

if (!empty($failedRecords)) {
    echo json_encode([
        'status' => 'partial_success',
        'message' => 'Some records failed validation.',
        'failed_records' => $failedRecords
    ]);
    exit;
}

echo json_encode(['status' => 'success', 'message' => 'All parts uploaded successfully.']);
?>
