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
                if (!mkdir($upload_dir, 0777, true)) {
                    $output['status'] = 400;
                    $output['msg'] = "Failed to create upload directory.";
                    echo json_encode($output, JSON_NUMERIC_CHECK);
                    exit;
                }
            }

            // Process each PDF file in the input
            foreach ($pdf_files as $pdf) {
                $pdf_id = mysqli_real_escape_string($conn, $pdf['pdf_id']); // Existing PDF ID
                $file_name = basename(mysqli_real_escape_string($conn, $pdf['fileName']));
                $pdf_date = mysqli_real_escape_string($conn, $pdf['pdf_date']);
                $base64_pdf = $pdf['pdfBase64']; // Base64 encoded PDF content

                // Remove the "data:application/pdf;base64," prefix if present
                if (strpos($base64_pdf, 'data:application/pdf;base64,') === 0) {
                    $base64_pdf = str_replace('data:application/pdf;base64,', '', $base64_pdf);
                }

                // Validate the base64 encoding before decoding
                if (base64_decode($base64_pdf, true) === false) {
                    $output['status'] = 400;
                    $output['msg'] = "Invalid base64 encoding.";
                    echo json_encode($output, JSON_NUMERIC_CHECK);
                    exit;
                }

                // Decode the base64 PDF content
                $pdf_data = base64_decode($base64_pdf);

                // Define the file path to store the PDF
                $file_path = $upload_dir . $pdf_id . "_" . $file_name;

                // Check if the provided pdf_id exists in the database before attempting update
                $check_pdf_sql = "SELECT COUNT(*) as count FROM `turbine_pdf` WHERE `pdf_id` = '$pdf_id' AND `wtg_no` = '$wtg_no'";
                $check_pdf_result = mysqli_query($conn, $check_pdf_sql);
                $pdf_exists = mysqli_fetch_assoc($check_pdf_result)['count'];

                if ($pdf_exists > 0) {
                    // Save the PDF to the "uploads" directory
                    if (file_put_contents($file_path, $pdf_data)) {
                        // Update the PDF details in the database (storing file path)
                        $update_sql = "UPDATE `turbine_pdf` 
                                       SET `file_name` = '$file_name', `pdf_date` = '$pdf_date', `pdf_data` = '$file_path' 
                                       WHERE `pdf_id` = '$pdf_id' AND `wtg_no` = '$wtg_no'";

                        // Execute the update query
                        if (mysqli_query($conn, $update_sql)) {
                            $output['status'] = 200;
                            $output['msg'] = "PDF updated successfully.";
                        } else {
                            $output['status'] = 400;
                            $output['msg'] = "Failed to update PDF in the database.";
                        }
                    } else {
                        $output['status'] = 400;
                        $output['msg'] = "Failed to save updated PDF to the server.";
                    }
                } else {
                    // The provided pdf_id doesn't exist
                    $output['status'] = 400;
                    $output['msg'] = "PDF with the provided pdf_id does not exist.";
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
