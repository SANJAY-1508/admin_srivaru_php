<?php
include ("../config/db_config.php");

// Allow CORS headers
header('Access-Control-Allow-Origin: *');  // Change * to a specific domain if necessary
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');  // Allow necessary methods
header('Access-Control-Allow-Headers: Content-Type, Authorization');  // Allow necessary headers
header('Access-Control-Allow-Credentials: true');  // If credentials like cookies need to be passed
header('Content-Type: application/json; charset=utf-8');

// Handle preflight (OPTIONS) request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

$json = file_get_contents('php://input');
$obj = json_decode($json);
$output = array();

date_default_timezone_set('Asia/Calcutta');
$timestamp = date('Y-m-d h:i:s');

// Check if required parameters are provided
if (isset($obj->id) && isset($obj->password) && isset($obj->new_password)) {
    $password = $obj->password;
    $new_password = $obj->new_password;
    $id = $obj->id;  // Use 'id' as a common parameter for both customer and customer group

    // First, check if it's a customer ID
    $sql = "SELECT c.*, cg.customergroup_uniq_id 
            FROM `customer` c
            LEFT JOIN `customer_group` cg 
            ON c.customergroupname_id = cg.customergroup_uniq_id 
            WHERE c.customer_unique_id = ? AND c.delete_at = '0' 
            COLLATE utf8mb4_unicode_ci";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    // If it's a customer ID
    if ($user) {
        // Check if the password matches
        if ($password !== $user['password']) {
            http_response_code(400);  // Set HTTP response code to 400 for errors
            $output["status"] = 400;
            $output["msg"] = "Invalid current password for customer.";
            echo json_encode($output, JSON_NUMERIC_CHECK);
            exit;
        }

        // Update password in the customer table
        $sql = "UPDATE `customer` SET `password` = ? WHERE `customer_unique_id` = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $new_password, $id);
        $result = mysqli_stmt_execute($stmt);

        if ($result) {
            http_response_code(200);  // Set HTTP response code to 200 for success
            $output["status"] = 200;
            $output["msg"] = "Customer password updated successfully.";
        } else {
            http_response_code(400);  // Set HTTP response code to 400 for errors
            $output["status"] = 400;
            $output["msg"] = "Failed to update customer password.";
        }
    } else {
        // If no customer found, check if it's a customer group ID
        $sql = "SELECT * FROM `customer_group` 
                WHERE `customergroup_uniq_id` = ? 
                COLLATE utf8mb4_unicode_ci";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $group = mysqli_fetch_assoc($result);

        if (!$group) {
            http_response_code(400);  // Set HTTP response code to 400 for errors
            $output["status"] = 400;
            $output["msg"] = "Invalid customer group ID.";
            echo json_encode($output, JSON_NUMERIC_CHECK);
            exit;
        }

        // Check if the password matches
        if ($password !== $group['customer_password']) {
            http_response_code(400);  // Set HTTP response code to 400 for errors
            $output["status"] = 400;
            $output["msg"] = "Invalid current password for customer group.";
            echo json_encode($output, JSON_NUMERIC_CHECK);
            exit;
        }

        // Update password in the customer group table
        $sql = "UPDATE `customer_group` SET `customer_password` = ? WHERE `customergroup_uniq_id` = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $new_password, $id);
        $result = mysqli_stmt_execute($stmt);

        if ($result) {
            http_response_code(200);  // Set HTTP response code to 200 for success
            $output["status"] = 200;
            $output["msg"] = "Customer group password updated successfully.";
        } else {
            http_response_code(400);  // Set HTTP response code to 400 for errors
            $output["status"] = 400;
            $output["msg"] = "Failed to update customer group password.";
        }
    }
} else {
    // Missing required parameters
    http_response_code(400);  // Set HTTP response code to 400 for errors
    $output["status"] = 400;
    $output["msg"] = "Missing required parameters.";
}

echo json_encode($output, JSON_NUMERIC_CHECK);
?>
