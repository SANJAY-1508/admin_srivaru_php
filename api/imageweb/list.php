<?php

include("../config/db_config.php");
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json; charset=utf-8');

$output = array();
date_default_timezone_set('Asia/Calcutta');

// SQL query to retrieve all images
$sql = "SELECT `img_id`, `image`, `created_date`, `delete_at` FROM `images` WHERE delete_at=0";
$result = mysqli_query($conn, $sql);

// Check if any records were found
if (mysqli_num_rows($result) > 0) {
    $images = array();
    
    // Loop through each record and add it to the images array
    while ($row = mysqli_fetch_assoc($result)) {
        $images[] = array(
            "img_id" => $row['img_id'],
            "image_url" => $row['image'],  // You can modify this URL as per your server setup
        );
    }

    // Prepare the success response
    $output['status'] = 200;
    $output['msg'] = "Images fetched successfully";
    $output['images'] = $images;
} else {
    // No images found in the database
    $output['status'] = 404;
    $output['msg'] = "No images found";
}

// Output the JSON response
echo json_encode($output, JSON_NUMERIC_CHECK);
