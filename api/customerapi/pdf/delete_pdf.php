<?php

include("../../config/db_config.php");
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json; charset=utf-8');

// Read incoming JSON input
$json = file_get_contents('php://input');
$obj = json_decode($json);
$output = array();

if (!empty($obj->pdf_id)) {
    $pdf_id = $obj->pdf_id;

    // Check if the pdf_id exists
    $stmt = $conn->prepare("SELECT pdf_id FROM pdf WHERE pdf_id = ? AND delete_at = 0");
    $stmt->bind_param("s", $pdf_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        // If no matching pdf_id is found
        $output["status"] = 404;
        $output["msg"] = "PDF file not found or already deleted";
        echo json_encode($output);
        exit;
    }

    // Soft-delete the PDF by setting delete_at = 1
    $stmt = $conn->prepare("UPDATE pdf SET delete_at = 1 WHERE pdf_id = ?");
    $stmt->bind_param("s", $pdf_id);

    if ($stmt->execute()) {
        $output["status"] = 200;
        $output["msg"] = "PDF file successfully marked as deleted";
    } else {
        $output["status"] = 500;
        $output["msg"] = "Failed to delete the PDF file";
    }
} else {
    $output["status"] = 400;
    $output["msg"] = "Invalid input parameters";
}

echo json_encode($output, JSON_NUMERIC_CHECK);

?>