<?php
include("../config/db_config.php");
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json; charset=utf-8');

$output = array();
date_default_timezone_set('Asia/Calcutta');
$timestamp = date('Y-m-d h:i:s');

// Get the raw POST data (JSON)
$input = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    $output['status'] = 400;
    $output['msg'] = "Invalid JSON data.";
    echo json_encode($output, JSON_NUMERIC_CHECK);
    exit;
}

// Parameters to check in the input
$params = ['wtg_no', 'pdf_files'];
$error = 0;

// Check for required parameters
foreach ($params as $element) {
    if (!isset($input[$element])) {
        $error = 1;
        break;
    }
}

if ($error == 0) {
    $wtg_no = $input['wtg_no'];
    $pdf_files = $input['pdf_files']; // Array of PDFs

    // Check if the wtg_no exists in the turbine table
    $check_sql = "SELECT COUNT(*) as count FROM `turbine` WHERE `wtg_no` = '$wtg_no' AND `delete_at` = '0'";
    $check_result = mysqli_query($conn, $check_sql);

    if ($check_result) {
        $count = mysqli_fetch_assoc($check_result)['count'];

        if ($count > 0) {
            // Make sure the "uploads" folder exists
            $upload_dir = "../uploads/";
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);  // Create the directory if it doesn't exist
            }

            // Process each PDF file in the input
            foreach ($pdf_files as $pdf) {
                $pdf_id = uniqid('pdf_'); // Generate unique PDF ID
                $file_name = basename(mysqli_real_escape_string($conn, $pdf['fileName'])); // Safely handle the file name
                $pdf_date = mysqli_real_escape_string($conn, $pdf['pdf_date']);
                $base64_pdf = $pdf['file']; // Base64 encoded PDF content with data URI

                // Remove the "data:application/pdf;base64," prefix if present
                if (strpos($base64_pdf, 'data:application/pdf;base64,') === 0) {
                    $base64_pdf = str_replace('data:application/pdf;base64,', '', $base64_pdf);
                }

                // Validate and decode the base64 PDF content
                if (base64_decode($base64_pdf, true) === false) {
                    $output['status'] = 400;
                    $output['msg'] = "Invalid base64 encoding.";
                    echo json_encode($output, JSON_NUMERIC_CHECK);
                    exit;
                }

                $pdf_data = base64_decode($base64_pdf);

                // Define the file path to store the PDF
                $file_path = $upload_dir . $pdf_id . "_" . $file_name;

                // Save the PDF to the "uploads" directory
                if (file_put_contents($file_path, $pdf_data)) {
                    // Insert the PDF details into the database (storing file path)
                    $insert_sql = "INSERT INTO `turbine_pdf`(`pdf_id`, `wtg_no`, `file_name`, `pdf_date`, `pdf_data`) 
                                   VALUES ('$pdf_id', '$wtg_no', '$file_name', '$pdf_date', '$file_path')
                                   ON DUPLICATE KEY UPDATE `file_name` = '$file_name', `pdf_date` = '$pdf_date', `pdf_data` = '$file_path'";

                    // Execute the insert query
                    if (mysqli_query($conn, $insert_sql)) {
                        $output['status'] = 200;
                        $output['msg'] = "PDF uploaded and associated with WTG_no successfully.";
                    } else {
                        $output['status'] = 400;
                        $output['msg'] = "Failed to upload PDF to database.";
                    }
                } else {
                    $output['status'] = 400;
                    $output['msg'] = "Failed to save PDF to the server.";
                }
            }
        } else {
            // WTG_no not found
            $output['status'] = 400;
            $output['msg'] = "WTG_no not found.";
        }
    } else {
        // Database query failed
        $output['status'] = 400;
        $output['msg'] = "Failed to check WTG_no.";
    }
} else {
    // Missing required parameters
    $output['status'] = 400;
    $output['msg'] = "Missing required parameters.";
}

echo json_encode($output, JSON_NUMERIC_CHECK);
?>
