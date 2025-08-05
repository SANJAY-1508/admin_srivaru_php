<?php

include("../config/db_config.php");
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json; charset=utf-8');

$output = array();
date_default_timezone_set('Asia/Calcutta');

// Check if 'img_id' and image file are provided
if (isset($_POST['img_id']) && isset($_FILES['image']) && $_FILES['image']['error'] == 0) {

    // Sanitize the image ID
    $img_id = mysqli_real_escape_string($conn, $_POST['img_id']);

    // Define the upload directory
    $uploadDir = '../uploads/';

    // Ensure the upload directory exists
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Get the original image name and extension
    $image_name = basename($_FILES['image']['name']);
    $image_extension = pathinfo($image_name, PATHINFO_EXTENSION);

    // Create a new unique image name using timestamp to prevent filename conflicts
    $new_image_name = 'img_' . time() . '.' . $image_extension;

    // Set the new image path
    $newImagePath = $uploadDir . $new_image_name;

    // Move the uploaded file to the target directory
    if (move_uploaded_file($_FILES['image']['tmp_name'], $newImagePath)) {
        
        // Update the image path in the database
        $updateQuery = "UPDATE `images` SET `image` = '$new_image_name' WHERE `img_id` = '$img_id'";
        $result = mysqli_query($conn, $updateQuery);

        if ($result) {
            $output['status'] = 200;
            $output['msg'] = "Image updated successfully";
        } else {
            $output['status'] = 500;
            $output['msg'] = "Failed to update image in the database";
        }
    } else {
        $output['status'] = 500;
        $output['msg'] = "Failed to upload the new image";
    }

} else {
    // Missing required parameters or file upload error
    $output['status'] = 400;
    $output['msg'] = "Image ID and image file are required";
}

echo json_encode($output, JSON_NUMERIC_CHECK);
