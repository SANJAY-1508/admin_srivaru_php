<?php

include("../config/db_config.php");
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json; charset=utf-8');

$output = array();
date_default_timezone_set('Asia/Calcutta');

// Check if POST data is set
if (isset($_POST['user_id']) && isset($_POST['date_of_joining']) && isset($_POST['user_name']) && isset($_POST['mobile_number']) && isset($_POST['role_id']) && isset($_POST['address']) && isset($_POST['login_id']) && isset($_POST['date_of_birth']) && isset($_POST['password'])) {

    // Sanitize input
    $user_id = mysqli_real_escape_string($conn, $_POST['user_id']);
    $date_of_joining = mysqli_real_escape_string($conn, $_POST['date_of_joining']);
    $user_name = mysqli_real_escape_string($conn, $_POST['user_name']);
    $mobile_number = mysqli_real_escape_string($conn, $_POST['mobile_number']);
    $role_id = mysqli_real_escape_string($conn, $_POST['role_id']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $date_of_birth = mysqli_real_escape_string($conn, $_POST['date_of_birth']);
    $login_id = mysqli_real_escape_string($conn, $_POST['login_id']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    $profileImageName = null;
    $signImageName = null;

    // Define the upload directory
    $uploadDir = 'uploads/';  // Use relative path
    
    // Ensure the upload directory exists
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Check and process profile image if it is provided
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        // Store only the image name
        $profileImageName = basename($_FILES['profile_image']['name']);
        $profileImagePath = $profileImageName;
        move_uploaded_file($_FILES['profile_image']['tmp_name'], $profileImagePath);
    }

    // Check and process sign image if it is provided
    if (isset($_FILES['sign_image']) && $_FILES['sign_image']['error'] == 0) {
        // Store only the image name
        $signImageName = basename($_FILES['sign_image']['name']);
        $signImagePath = $signImageName;
        move_uploaded_file($_FILES['sign_image']['tmp_name'], $signImagePath);
    }

    // Check if user exists
    $checkExistsUserSQL = "SELECT * FROM `users` WHERE `user_id` = '$user_id' AND `delete_at` = '0'";
    $checkExistsUser = mysqli_query($conn, $checkExistsUserSQL);
    $checkExistsUserCount = mysqli_num_rows($checkExistsUser);

    if ($checkExistsUserCount > 0) {
        // Prepare the UPDATE SQL query
        $updateUserSQL = "UPDATE `users` SET 
                          `date_of_joining` = '$date_of_joining', 
                          `user_name` = '$user_name', 
                          `mobile_number` = '$mobile_number', 
                          `role_id` = '$role_id', 
                          `address` = '$address', 
                          `date_of_birth` = '$date_of_birth', 
                          `login_id` = '$login_id', 
                          `password` = '$password'";
        
        // Append image names (not full paths) if they are provided
        if ($profileImageName) {
            $updateUserSQL .= ", `profile_image` = '$profileImageName'";
        }
        if ($signImageName) {
            $updateUserSQL .= ", `sign_image` = '$signImageName'";
        }
        
        $updateUserSQL .= " WHERE `user_id` = '$user_id'";

        // Execute the update query
        $result = mysqli_query($conn, $updateUserSQL);

        if ($result) {
            $output['status'] = 200;
            $output['msg'] = "User details updated successfully";
        } else {
            $output['status'] = 400;
            $output['msg'] = "Failed to update user details";
        }
    } else {
        $output['status'] = 404;
        $output['msg'] = "User not found";
    }
} else {
    $output['status'] = 400;
    $output['msg'] = "Required parameters missing";
}

echo json_encode($output, JSON_NUMERIC_CHECK);

?>
