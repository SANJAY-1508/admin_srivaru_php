 <?php

// include ("../config/db_config.php");
// header('Access-Control-Allow-Origin: *');
// header("Access-Control-Allow-Headers: Content-Type");
// header('Content-Type: application/json; charset=utf-8');

// $json = file_get_contents('php://input');
// $obj = json_decode($json);
// $output = array();
// date_default_timezone_set('Asia/Calcutta');
// $timestamp = date('Y-m-d');

// if (isset($obj->login_id) && isset($obj->password)) {
//     $login_id = $obj->login_id;
//     $password = $obj->password;

//     if (!empty($login_id) && !empty($password)) {
//         $result = $conn->query("SELECT * FROM `users` WHERE `login_id`='$login_id' AND `password`='$password' AND `delete_at`=0");
//         if ($result->num_rows > 0) {
//             $user = $result->fetch_assoc();
//             $output["status"] = "Success";
//             $output["data"] = $user;
//         } else {
//             $output["status"] = "Failed";
//             $output["msg"] = "Invalid username or password";
//         }
//     } else {
//         $output["status"] = "Failed";
//         $output["msg"] = "Login ID or password is empty";
//     }
// } else {
//     $output["status"] = "Failed";
//     $output["msg"] = "Login ID or password is not provided";
// }

// echo json_encode($output, JSON_NUMERIC_CHECK);


include("../config/db_config.php");
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json; charset=utf-8');

$json = file_get_contents('php://input');
$obj = json_decode($json);
$output = array();
date_default_timezone_set('Asia/Calcutta');
$timestamp = date('Y-m-d');

if (isset($obj->login_id) && isset($obj->password)) {
    $login_id = $obj->login_id;
    $password = $obj->password;

    if (!empty($login_id) && !empty($password)) {
        $query = "SELECT u.*, r.role_name 
                  FROM `users` u 
                  LEFT JOIN `role` r ON u.role_id = r.role_id 
                  WHERE u.login_id='$login_id' AND u.password='$password' AND u.delete_at=0";
        $result = $conn->query($query);
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $output["status"] = "Success";
            $output["data"] = $user;
        } else {
            $output["status"] = "Failed";
            $output["msg"] = "Invalid username or password";
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