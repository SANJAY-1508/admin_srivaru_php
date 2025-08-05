<?php

include("../config/db_config.php");
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json; charset=utf-8');

$output = array();
date_default_timezone_set('Asia/Calcutta');

// Get current timestamp
$timestamp = time();

// Check if an image is being uploaded
if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {

    // Define the upload directory
    $uploadDir = '../uploads/';

    // Ensure the upload directory exists
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Get the original image name and its extension
    $image_name = basename($_FILES['image']['name']);
    $image_extension = pathinfo($image_name, PATHINFO_EXTENSION);

    // Create a new unique image name using timestamp and original file extension
    $new_image_name = 'img_' . $timestamp . '.' . $image_extension;

    // Generate the full path for the new image name
    $imagePath = $uploadDir . $new_image_name;

    // Move the uploaded image to the designated directory
    if (move_uploaded_file($_FILES['image']['tmp_name'], $imagePath)) {
        // Image upload successful

        // Get current date as created_date
        $created_date = date("Y-m-d H:i:s");

        // Generate a unique image ID
        $img_id = uniqid("img", true);

        // Prepare the SQL query to insert image data into the table
        $insertImageSQL = "INSERT INTO `images` (`img_id`, `image`, `created_date`) 
                           VALUES ('$img_id', '$new_image_name', '$created_date')";

        // Execute the SQL query
        $result = mysqli_query($conn, $insertImageSQL);

        // Check if the image was inserted successfully into the database
        if ($result) {
            $output['status'] = 200;
            $output['msg'] = "Image uploaded and record created successfully";
            $output['img_id'] = $img_id;
        } else {
            $output['status'] = 400;
            $output['msg'] = "Failed to insert image record into database";
        }
    } else {
        // Image upload failed
        $output['status'] = 500;
        $output['msg'] = "Failed to upload image";
    }
} else {
    $output['status'] = 400;
    $output['msg'] = "No image uploaded or an error occurred";
}

echo json_encode($output, JSON_NUMERIC_CHECK);
