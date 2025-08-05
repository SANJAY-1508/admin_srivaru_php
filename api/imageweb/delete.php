<?php

include("../config/db_config.php");
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json; charset=utf-8');

$output = array();
date_default_timezone_set('Asia/Calcutta');

// Check if 'img_id' is provided for the record to mark as deleted
if (isset($_POST['img_id'])) {

    // Sanitize input
    $img_id = mysqli_real_escape_string($conn, $_POST['img_id']);

    // Set the 'delete_at' field to 1 to mark it as deleted
    $deleteAt = 1; // You can change this value if you want to use a specific marker

    // Update query to mark the image as deleted
    $updateQuery = "UPDATE `images` SET `delete_at` = '$deleteAt' WHERE `img_id` = '$img_id'";

    // Execute the query
    $result = mysqli_query($conn, $updateQuery);

    if ($result) {
        $output['status'] = 200;
        $output['msg'] = "Image marked for deletion";
    } else {
        $output['status'] = 500;
        $output['msg'] = "Failed to mark image for deletion";
    }

} else {
    // img_id not provided
    $output['status'] = 400;
    $output['msg'] = "Image ID is required";
}

echo json_encode($output, JSON_NUMERIC_CHECK);

?>
