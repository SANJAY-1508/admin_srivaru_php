<?php

include("../config/db_config.php");
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json; charset=utf-8');

$output = array();
date_default_timezone_set('Asia/Calcutta');

// Check if POST data is set
if (isset($_POST['date_of_joining']) && isset($_POST['user_name']) && isset($_POST['mobile_number']) && isset($_POST['role_id']) && isset($_POST['address']) && isset($_POST['login_id']) && isset($_POST['date_of_birth']) && isset($_POST['password'])) {

    // Sanitize input
    $date_of_joining = mysqli_real_escape_string($conn, $_POST['date_of_joining']);
    $user_name = mysqli_real_escape_string($conn, $_POST['user_name']);
    $mobile_number = mysqli_real_escape_string($conn, $_POST['mobile_number']);
    $role_id = mysqli_real_escape_string($conn, $_POST['role_id']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $date_of_birth = mysqli_real_escape_string($conn, $_POST['date_of_birth']);
    $login_id = mysqli_real_escape_string($conn, $_POST['login_id']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    $profileImagePath = null;
    $signImagePath = null;

    // Define the upload directory
    $uploadDir = 'uploads/';
    
    // Ensure the upload directory exists
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Check and process profile image if it is provided
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        // Store only the image name (basename) instead of full path
        $profileImageName = basename($_FILES['profile_image']['name']);
        $profileImagePath = $profileImageName;
        move_uploaded_file($_FILES['profile_image']['tmp_name'], $profileImagePath);
    }

    // Check and process sign image if it is provided
    if (isset($_FILES['sign_image']) && $_FILES['sign_image']['error'] == 0) {
        // Store only the image name (basename) instead of full path
        $signImageName = basename($_FILES['sign_image']['name']);
        $signImagePath =  $signImageName;
        move_uploaded_file($_FILES['sign_image']['tmp_name'], $signImagePath);
    }

    $user_id = uniqid("user", true); 
    // Prepare the INSERT SQL query
    $insertUserSQL = "INSERT INTO `users` (`user_id`,`date_of_joining`, `user_name`, `mobile_number`, `role_id`, `address`, `date_of_birth`, `login_id`, `password`";

    // Append image names (not full path) if they are provided
    if ($profileImagePath) {
        $insertUserSQL .= ", `profile_image`";
    }
    if ($signImagePath) {
        $insertUserSQL .= ", `sign_image`";
    }

    $insertUserSQL .= ") VALUES ('$user_id','$date_of_joining', '$user_name', '$mobile_number', '$role_id', '$address', '$date_of_birth', '$login_id', '$password'";

    // Append image names values if they are provided
    if ($profileImagePath) {
        $insertUserSQL .= ", '$profileImageName'";  // Insert only the image name here
    }
    if ($signImagePath) {
        $insertUserSQL .= ", '$signImageName'";  // Insert only the image name here
    }

    $insertUserSQL .= ")";

    // Execute the insert query
    $result = mysqli_query($conn, $insertUserSQL);

    if ($result) {
        $output['status'] = 200;
        $output['msg'] = "User created successfully";
    } else {
        $output['status'] = 400;
        $output['msg'] = "Failed to create user";
    }
} else {
    $output['status'] = 400;
    $output['msg'] = "Required parameters missing";
}

echo json_encode($output, JSON_NUMERIC_CHECK);

?>
