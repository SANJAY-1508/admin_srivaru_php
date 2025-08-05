<?php
include("../config/db_config.php");
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json; charset=utf-8');

$output = array();

// Get the raw POST data (JSON)
$input = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    $output['status'] = 400;
    $output['msg'] = "Invalid JSON data.";
    echo json_encode($output, JSON_NUMERIC_CHECK);
    exit;
}

// Parameters to check in the input
$params = ['pdf_id'];
$error = 0;

// Check for required parameters
foreach ($params as $element) {
    if (!isset($input[$element])) {
        $error = 1;
        break;
    }
}

if ($error == 0) {
    $pdf_id = $input['pdf_id'];  // Get the pdf_id to mark as deleted

    // Check if the PDF exists in the turbine_pdf table
    $check_sql = "SELECT COUNT(*) as count FROM `turbine_pdf` WHERE `pdf_id` = '$pdf_id' AND `delete_at` = '0'";
    $check_result = mysqli_query($conn, $check_sql);

    if ($check_result) {
        $count = mysqli_fetch_assoc($check_result)['count'];

        if ($count > 0) {
            // Mark the PDF as deleted by setting delete_at = 1
            $delete_sql = "UPDATE `turbine_pdf` SET `delete_at` = '1' WHERE `pdf_id` = '$pdf_id'";

            if (mysqli_query($conn, $delete_sql)) {
                $output['status'] = 200;
                $output['msg'] = "PDF marked as deleted successfully.";
            } else {
                $output['status'] = 400;
                $output['msg'] = "Failed to mark PDF as deleted.";
            }
        } else {
            // PDF not found or already deleted
            $output['status'] = 400;
            $output['msg'] = "PDF not found";
        }
    } else {
        // Database query failed
        $output['status'] = 400;
        $output['msg'] = "Failed to check PDF.";
    }
} else {
    // Missing required parameters
    $output['status'] = 400;
    $output['msg'] = "Missing required parameters.";
}

echo json_encode($output, JSON_NUMERIC_CHECK);
?>
