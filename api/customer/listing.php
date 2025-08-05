<?php

include ("../config/db_config.php");
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json; charset=utf-8');

$json = file_get_contents('php://input');
$obj = json_decode($json);
$output = array();

date_default_timezone_set('Asia/Calcutta');
$timestamp = date('Y-m-d');

// Initialize conditions and parameters
$conditions = ["c.`delete_at` = 0"];
$parameters = [];
$param_types = "";

if (isset($obj->search_text) && !empty($obj->search_text)) {
    $search_text = $obj->search_text;
    $conditions[] = "(c.`customer_name` LIKE ? OR c.`city` LIKE ?)";
    $parameters[] = '%' . $search_text . '%';
    $parameters[] = '%' . $search_text . '%';
    $param_types .= 'ss';
}

if (isset($obj->city) && !empty($obj->city)) {
    $city = $obj->city;
    $conditions[] = "c.`city` LIKE ?";
    $parameters[] = '%' . $city . '%';
    $param_types .= 's';
}

// Construct the SQL query selecting only customer_unique_id and customer_name
$query = "SELECT c.`customer_unique_id`, c.`customer_name` 
          FROM `customer` c 
          WHERE " . implode(' AND ', $conditions);
$query .= " ORDER BY c.`id` DESC"; // Sorting by id in descending order

// Prepare and execute the query
$stmt = $conn->prepare($query);
if ($parameters) {
    // Dynamically bind parameters
    $stmt->bind_param($param_types, ...$parameters);
}
$stmt->execute();
$result = $stmt->get_result();

// Process the results
if ($result->num_rows > 0) {
    $output["status"] = 200;
    $output["msg"] = "Success";
    $output["data"]["customer"] = array();

    while ($row = $result->fetch_assoc()) {
        $output["data"]["customer"][] = array(
            "customer_unique_id" => $row['customer_unique_id'],
            "customer_name" => $row['customer_name']
        );
    }
} else {
    $output["status"] = 200;
    $output["msg"] = "No Data";
    $output["data"]["customer"] = [];
}

$stmt->close();
$conn->close();

echo json_encode($output, JSON_NUMERIC_CHECK);
