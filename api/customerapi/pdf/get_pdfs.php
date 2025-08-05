<?php

include("../../config/db_config.php");
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json; charset=utf-8');

// Read incoming JSON input
$json = file_get_contents('php://input');
$obj = json_decode($json);
$output = array();

if (!empty($obj->turbine_number)) {
    $turbine_number = $obj->turbine_number;

    // Check if the turbine_number exists
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

    // Fetch the list of PDFs for the provided turbine_number
    $stmt = $conn->prepare("SELECT pdf_id, pdfFile FROM pdf WHERE turbine_number = ? AND delete_at = 0");
    $stmt->bind_param("s", $turbine_number);
    $stmt->execute();
    $result = $stmt->get_result();

    $pdfList = array();
    while ($row = $result->fetch_assoc()) {
        $pdfList[] = array(
            'pdf_id' => $row['pdf_id'],
            'pdfFile' => $row['pdfFile'],
        );
    }

    if (!empty($pdfList)) {
        $output["status"] = 200;
        $output["pdfs"] = $pdfList;
    } else {
        $output["status"] = 404;
        $output["msg"] = "No PDFs found for the given turbine number";
    }

} else {
    // If no turbine_number is provided, fetch all PDFs
    $stmt = $conn->prepare("SELECT pdf_id, turbine_number, pdfFile FROM pdf WHERE delete_at = 0");
    $stmt->execute();
    $result = $stmt->get_result();

    $pdfList = array();
    while ($row = $result->fetch_assoc()) {
        $pdfList[] = array(
            'pdf_id' => $row['pdf_id'],
            'turbine_number' => $row['turbine_number'],
            'pdfFile' => $row['pdfFile'],
        );
    }

    if (!empty($pdfList)) {
        $output["status"] = 200;
        $output["pdfs"] = $pdfList;
    } else {
        $output["status"] = 404;
        $output["msg"] = "No PDFs found";
    }
}

echo json_encode($output, JSON_NUMERIC_CHECK);

?>