<?php

// Include database configuration file
include ("../config/db_config.php");

// Initialize output array
$output = array();

// Ensure content type and CORS headers are set
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json; charset=utf-8');

// Check for incoming JSON data
$json = file_get_contents('php://input');
$obj = json_decode($json);

// Default timezone
date_default_timezone_set('Asia/Calcutta');

// Initialize arrays to store sanitized customer names and wtg_no
$sanitized_customer_names = array();
$sanitized_wtg_no = array();

// Check if required data is present in JSON input
if (isset($obj->customer_name)) {
    // Sanitize and prepare customer names
    if (is_array($obj->customer_name)) {
        foreach ($obj->customer_name as $customer_name) {
            // Escape user input to prevent SQL injection
            $sanitized_customer_names[] = $conn->real_escape_string($customer_name);
        }
    } else {
        // If single customer_name provided, sanitize it
        $sanitized_customer_names[] = $conn->real_escape_string($obj->customer_name);
    }
}

if (isset($obj->wtg_no)) {
    // Sanitize and prepare wtg_no
    if (is_array($obj->wtg_no)) {
        foreach ($obj->wtg_no as $wtg_no) {
            // Escape user input to prevent SQL injection
            $sanitized_wtg_no[] = $conn->real_escape_string($wtg_no);
        }
    } else {
        // If single wtg_no provided, sanitize it
        $sanitized_wtg_no[] = $conn->real_escape_string($obj->wtg_no);
    }
}

// Prepare the IN clauses for SQL query
$in_clause_customer_name = count($sanitized_customer_names) > 0 ? "'" . implode("', '", $sanitized_customer_names) . "'" : "";
$in_clause_wtg_no = count($sanitized_wtg_no) > 0 ? "'" . implode("', '", $sanitized_wtg_no) . "'" : "";

try {
    // If customer_name is empty and wtg_no is given, throw an error
    if (!$in_clause_customer_name && $in_clause_wtg_no) {
        $output['error'] = 'Invalid input: customer_name must be provided if wtg_no is given.';
    } else {
        // Base query to fetch data
        $sql = "SELECT c.customer_name, t.wtg_no FROM turbine t
                INNER JOIN customer c ON t.customername_id = c.customer_unique_id";

        // Add conditions based on input
        $conditions = array();
        if ($in_clause_customer_name) {
            $conditions[] = "c.customer_name IN ($in_clause_customer_name)";
        }
        if ($in_clause_wtg_no) {
            $conditions[] = "t.wtg_no IN ($in_clause_wtg_no)";
        }
        if (count($conditions) > 0) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        // Execute query
        $result = $conn->query($sql);

        // Check if results were found
        if ($result->num_rows > 0) {
            $data = array();
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            $output['data'] = $data;
        } else {
            $output['error'] = 'No data found for the given customer_name(s) and wtg_no(s).';
        }
    }
} catch (Exception $e) {
    $output['error'] = 'Database error: ' . $e->getMessage();
}

// Output JSON response
echo json_encode($output, JSON_NUMERIC_CHECK);