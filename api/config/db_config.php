<?php


$user_name = "root";
$password = "";
$server = "localhost";
$db_name = "admin_srivaru";


$conn = mysqli_connect($server, $user_name, $password, $db_name);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

function uniqueID($prefix_name, $auto_increment_id)
{

    date_default_timezone_set('Asia/Calcutta');
    $timestamp = date('Y-m-d H:i:s');
    $encryptId = $prefix_name . "" . $timestamp . "" . $auto_increment_id;

    $hashid = md5($encryptId);

    return $hashid;
}
