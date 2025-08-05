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

    // Prepare the SQL query to fetch only wtg_no and loc_no
    $query = "
    SELECT t.wtg_no, t.loc_no,t.turbine_id,t.customername_id
    FROM turbine t
    WHERE t.delete_at = '0'
    AND (
        t.wtg_no LIKE ? OR 
        t.loc_no LIKE ?
    )
    ORDER BY t.id DESC
    ";

    // Prepare the statement
    $stmt = $conn->prepare($query);

    // Add wildcards for LIKE search if the search_text is not empty
    $search_param = "%" . $search_text . "%";

    // Bind parameters: 'ss' for the search_text and wildcards
    $stmt->bind_param('ss', $search_param, $search_param);

    $stmt->execute();
    $result = $stmt->get_result();

    // Initialize an empty array to store turbines data
    $turbines = array();

    // Check if any rows are returned
    if ($result->num_rows > 0) {
        $output['status'] = 200;
        $output['msg'] = 'Success';

        while ($row = $result->fetch_assoc()) {
            // Store only wtg_no and loc_no
            $turbines[] = array(
                'turbine_id' => $row['turbine_id'],
                'customername_id' => $row['customername_id'],
                'wtg_no' => $row['wtg_no'],
                'loc_no' => $row['loc_no']
            );
        }

        // Format the final output with turbines data
        $output['data']['turbine'] = $turbines;
    } else {
        $output["status"] = 200;
        $output["msg"] = "No Data";
        $output["data"]["turbine"] = [];
    }

    $stmt->close();
}

echo json_encode($output, JSON_NUMERIC_CHECK);
