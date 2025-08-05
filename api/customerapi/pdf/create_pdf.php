<?php

include("../../config/db_config.php");
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json; charset=utf-8');

$json = file_get_contents('php://input');
$obj = json_decode($json);
$output = array();

if (!empty($obj->turbine_number) && !empty($obj->pdfFiles)) {
    $turbine_number = $obj->turbine_number;
    $pdfFile = $obj->pdfFiles; // Single base64 PDF string

    // Check if the turbine_number exists in the turbine table
    $stmt = $conn->prepare("SELECT wtg_no FROM turbine WHERE wtg_no = ?");
    $stmt->bind_param("s", $turbine_number);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        // If no matching turbine_number is found
        $output["status"] = 404;
        $output["msg"] = "Turbine number not found";
        echo json_encode($output);
        exit;
    }

    $uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;
    if (!is_dir($uploadDir)) {
        echo "Creating uploads directory: $uploadDir\n"; // Debugging line
        if (!mkdir($uploadDir, 0777, true)) {
            $output['status'] = 500;
            $output['msg'] = "Failed to create upload directory: " . error_get_last()['message'];
            echo json_encode($output);
            exit;
        }
    }

    date_default_timezone_set('Asia/Calcutta');
    $timestamp = date('Y-m-d H:i:s');

    // Generate unique file name and path for the single PDF
    $pdf_id = uniqid();
    $pdfFilename = $pdf_id . ".pdf";
    $pdfFilePath = $uploadDir . $pdfFilename;

    // Decode base64 to binary data and save the PDF to the server
    $pdfDecoded = base64_decode($pdfFile);
    if (file_put_contents($pdfFilePath, $pdfDecoded) === false) {
        $output['status'] = 500;
        $output['msg'] = "Error saving PDF file to server";
        echo json_encode($output);
        exit;
    }

    // Save file metadata into the database without base64
    $stmt = $conn->prepare("INSERT INTO pdf (pdf_id, turbine_number, pdfFile, create_date, delete_at) VALUES (?, ?, ?, ?, 0)");
    $stmt->bind_param("siss", $pdf_id, $turbine_number, $pdfFilename, $timestamp);

    if (!$stmt->execute()) {
        $output['status'] = 500;
        $output['msg'] = "Error saving PDF metadata to database";
        echo json_encode($output);
        exit;
    }

    $output["status"] = 200;
    $output["msg"] = "PDF successfully uploaded and saved";
} else {
    $output["status"] = 400;
    $output["msg"] = "Invalid input parameters";
}

echo json_encode($output, JSON_NUMERIC_CHECK);
?>