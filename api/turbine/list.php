<?php

include("../config/db_config.php");

header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json; charset=utf-8');

$json = file_get_contents('php://input');
$obj = json_decode($json);
$output = array();

if (isset($obj->search_text)) {
    $search_text = $obj->search_text;

    // Prepare the SQL query
    $query = "
    SELECT t.*, c.*
    FROM turbine t
    INNER JOIN customer c ON t.customername_id = c.customer_unique_id
    WHERE t.delete_at = '0'
    AND (
        t.customername_id LIKE ? OR 
        c.customer_name LIKE ? OR
        t.wtg_no LIKE ?
    )
    ORDER BY t.id DESC
    ";

    // Prepare the statement
    $stmt = $conn->prepare($query);

    // Add wildcards for LIKE search if the search_text is not empty
    $search_param = "%" . $search_text . "%";

    // Bind parameters: 'ss' for the search_text and wildcards
    $stmt->bind_param('sss', $search_param, $search_param,$search_param);

    $stmt->execute();
    $result = $stmt->get_result();

    // Initialize an empty array to store turbines data
    $turbines = array();

    // Check if any rows are returned
    if ($result->num_rows > 0) {
        $output['status'] = 200;
        $output['msg'] = 'Success';

        while ($row = $result->fetch_assoc()) {
            // Decode the 'contact' and 'ship_address' fields if necessary
            $row['contact'] = json_decode($row['contact'], true);
            $row['ship_address'] = json_decode($row['ship_address'], true);

            $wtg_no = $row['wtg_no'];  // Get the unique wtg_no
            // Initialize the turbine entry if not already set
            if (!isset($turbines[$wtg_no])) {
                $turbines[$wtg_no] = $row;
                $turbines[$wtg_no]['pdf_files'] = array(); // Initialize PDF files array
            }

            // Fetch PDF data from the turbine_pdf table for this wtg_no
            $pdf_query = "SELECT file_name, pdf_date, pdf_data,pdf_id FROM turbine_pdf WHERE wtg_no = ? AND delete_at = 0";
            $pdf_stmt = $conn->prepare($pdf_query);
            $pdf_stmt->bind_param('s', $wtg_no);
            $pdf_stmt->execute();
            $pdf_result = $pdf_stmt->get_result();

            while ($pdf_row = $pdf_result->fetch_assoc()) {
                // Check if this row has a PDF file and add it with additional details
                if (!empty($pdf_row['file_name'])) { // Assuming file_name contains the file name/path
                    $pdf_data = array(
                        'fileName' => $pdf_row['file_name'],  // Get the file name/path
                        'pdf_date' => $pdf_row['pdf_date'],  // Assuming pdf_date contains the date
                        'pdf_id' => $pdf_row['pdf_id'],
                        'file' => base64_encode(file_get_contents($pdf_row['pdf_data']))  // Get the base64 encoded content of the PDF
                    );
                    $turbines[$wtg_no]['pdf_files'][] = $pdf_data;  // Add the PDF data to the array
                }
            }
            
        }

        // Format the final output with turbines data
        $output['data']['turbine'] = array_values($turbines);  // Convert associative array to indexed array
    } else {
        $output["status"] = 200;
        $output["msg"] = "No Data";
        $output["data"]["turbine"] = [];
    }

    $stmt->close();
    $pdf_stmt->close();
}

// Return the final JSON response
echo json_encode($output, JSON_NUMERIC_CHECK);
?>
