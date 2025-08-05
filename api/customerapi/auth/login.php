<?php

include ("../../config/db_config.php");
session_start();

header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json; charset=utf-8');

$json = file_get_contents('php://input');
$obj = json_decode($json);
$output = array();
date_default_timezone_set('Asia/Calcutta');
$timestamp = date('Y-m-d');

if (isset($obj->user_id) && isset($obj->password)) {
    $user_id = $obj->user_id;
    $password = $obj->password;
    if (!empty($user_id) && !empty($password)) {
        $query = "SELECT * FROM `customer` WHERE `delete_at` = '0' AND `customer_user_id`='$user_id' AND `password`='$password'";
        $result = $conn->query($query);

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $_SESSION['user_id'] = $user_id;
            $output["status"] = "Success";
            $output["data"] = $user;
        } else {
            $query = "SELECT customergroup_uniq_id AS customer_unique_id FROM `customer_group` WHERE `delete_at` = '0' AND `customergroup_id`='$user_id' AND `customer_password`='$password'";

            $result = $conn->query($query);
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                $_SESSION['user_id'] = $user_id;
                $output["status"] = "Success";
                $output["data"] = $user;
            } else {
                $output["status"] = "Failed";
                $output["msg"] = "Invalid username or password";
            }
        }
    } else {
        $output["status"] = "Failed";
        $output["msg"] = "Login ID or password is empty";
    }
} else {
    $output["status"] = "Failed";
    $output["msg"] = "Login ID or password is not provided";
}

echo json_encode($output, JSON_NUMERIC_CHECK);
