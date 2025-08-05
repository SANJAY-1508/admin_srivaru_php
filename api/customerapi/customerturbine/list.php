<?php

include("../../config/db_config.php");
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json; charset=utf-8');

// Decode incoming JSON data
$json = file_get_contents('php://input');
$obj = json_decode($json);

$output = array();
if (isset($obj->user_id)) {
    $user_id = $obj->user_id;

    // Check if the user_id exists in the customer_group table
    $sql_check_group = "SELECT * FROM customer_group WHERE BINARY customergroup_uniq_id = ? AND delete_at = '0'";
    $stmt_check_group = $conn->prepare($sql_check_group);
    $stmt_check_group->bind_param("s", $user_id);
    $stmt_check_group->execute();
    $result_check_group = $stmt_check_group->get_result();

    if ($result_check_group->num_rows > 0) {
        // User is in the customer_group table
        $group_condition = "BINARY cg.customergroup_uniq_id = ?";
    } else {
        // Check if the user_id exists in the customer table
        $sql_check_customer = "SELECT * FROM customer WHERE BINARY customer_unique_id = ? AND delete_at = '0'";
        $stmt_check_customer = $conn->prepare($sql_check_customer);
        $stmt_check_customer->bind_param("s", $user_id);
        $stmt_check_customer->execute();
        $result_check_customer = $stmt_check_customer->get_result();

        if ($result_check_customer->num_rows > 0) {
            // User is in the customer table
            $group_condition = "BINARY c.customer_unique_id = ?";
        } else {
            // No valid user_id found
            $output["status"] = 400;
            $output["msg"] = "No Data";
            $output["data"] = [];
            echo json_encode($output, JSON_NUMERIC_CHECK);
            exit;
        }
    }

    $sql_fetch_data = "
    SELECT 
        c.*, 
        cg.*, 
        t.*, 
        s.*, 
        m.*, 
        ct.*, 
        l.* 
    FROM 
        customer c 
    LEFT JOIN customer_group cg 
        ON CONVERT(cg.customergroup_uniq_id USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(c.customergroupname_id USING utf8mb4) COLLATE utf8mb4_unicode_ci
    LEFT JOIN turbine t 
        ON CONVERT(t.customer_id USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(c.customer_unique_id USING utf8mb4) COLLATE utf8mb4_unicode_ci
    LEFT JOIN site s 
        ON CONVERT(t.site_id USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(s.site_id USING utf8mb4) COLLATE utf8mb4_unicode_ci
    LEFT JOIN model m 
        ON CONVERT(t.model_id USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(m.model_id USING utf8mb4) COLLATE utf8mb4_unicode_ci
    LEFT JOIN contract_type ct 
        ON CONVERT(t.contracttype_id USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(ct.contract_id USING utf8mb4) COLLATE utf8mb4_unicode_ci
    LEFT JOIN location l
        ON CONVERT(t.location_id USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(l.location_id USING utf8mb4) COLLATE utf8mb4_unicode_ci
    WHERE 
        CONVERT($group_condition USING utf8mb4) COLLATE utf8mb4_unicode_ci AND 
        c.delete_at = '0' AND 
        cg.delete_at = '0' AND
        t.delete_at = '0' AND
        s.delete_at = '0' AND
        m.delete_at = '0' AND
        ct.delete_at = '0' AND 
        l.delete_at = '0' 
";

    // Prepare and bind parameters based on the determined group condition
    $stmt_fetch_data = $conn->prepare($sql_fetch_data);
    $stmt_fetch_data->bind_param("s", $user_id);
    $stmt_fetch_data->execute();
    $result_fetch_data = $stmt_fetch_data->get_result();

    // Check if data exists and format the response
    if ($result_fetch_data->num_rows > 0) {
        $data = [];
        $turbines = [];
        
        while ($row = $result_fetch_data->fetch_assoc()) {
            $wtg_no = $row['wtg_no'];  // Get the unique wtg_no

            // Initialize the turbine entry if not already set
            if (!isset($turbines[$wtg_no])) {
                $turbines[$wtg_no] = $row;
                $turbines[$wtg_no]['pdf_files'] = array(); // Initialize PDF files array
            }

            // Fetch PDF data from the turbine_pdf table for this wtg_no
            $pdf_query = "SELECT file_name, pdf_date, pdf_data, pdf_id FROM turbine_pdf WHERE wtg_no = ? AND delete_at = 0";
            $pdf_stmt = $conn->prepare($pdf_query);
            $pdf_stmt->bind_param('s', $wtg_no);
            $pdf_stmt->execute();
            $pdf_result = $pdf_stmt->get_result();

            while ($pdf_row = $pdf_result->fetch_assoc()) {
                // Check if this row has a PDF file and add it with additional details
                
                if (!empty($pdf_row['file_name'])) {
                    $pdf_data = array(
                        'fileName' => $pdf_row['file_name'],  // Get the file name/path
                        'pdf_date' => $pdf_row['pdf_date'],  // Assuming pdf_date contains the date
                        'pdf_id' => $pdf_row['pdf_id'],
                        'file' => base64_encode($pdf_row['pdf_data'])  // Get the base64 encoded content of the PDF
                    );
                    $turbines[$wtg_no]['pdf_files'][] = $pdf_data;  // Add the PDF data to the array
                }
            }
            $pdf_stmt->close();
        }

        // Format the final output with turbines data
        $output["status"] = 200;
        $output["msg"] = "Success";
        $output['data'] = array_values($turbines);  // Convert associative array to indexed array
    } else {
        $output["status"] = 400;
        $output["msg"] = "No Data";
        $output["data"] = [];
    }

    echo json_encode($output, JSON_NUMERIC_CHECK);
} else {
    // Handle missing user_id
    $output["status"] = 400;
    $output["msg"] = "User ID not provided";
    $output["data"] = [];
    echo json_encode($output, JSON_NUMERIC_CHECK);
}

?>
