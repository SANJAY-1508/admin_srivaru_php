<?php

include ("../config/db_config.php");
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header('Content-Type: application/json; charset=utf-8');


$json = file_get_contents('php://input');
$obj = json_decode($json,true);
$output = array();


date_default_timezone_set('Asia/Calcutta');
$timestamp = date('Y-m-d h:i:s');

if (isset($obj["excel_data"])) {
    $bulk_data = $obj["excel_data"];

    foreach ($bulk_data as $row) {
        $dg_date = $row["dg_date"];
        $turbine_id = $row["turbine_id"];
        // Check if turbine_id already exists for the given dg_date
        $check_sql = "SELECT COUNT(*) as count FROM `daily_generation` WHERE `dg_date` = '$dg_date' AND `turbine_id` = '$turbine_id' AND `delete_at`=0";
        $check_result = mysqli_query($conn, $check_sql);
        $row_result = mysqli_fetch_assoc($check_result);


        date_default_timezone_set('Asia/Calcutta');
        $dg_date = $row['dg_date'];
        $turbine_id = $row['turbine_id'];
        $location_no = $row['location_no'];
        $gen_zero = $row['gen_zero'];
        $gen_one = $row['gen_one'];
        $gen_two = $row['gen_two'];
        $total_production = $row['total_production'];
        $gen_onehrs = $row['gen_onehrs'];
        $gen_twohrs = $row['gen_twohrs'];
        $gen_hourtotal = $row['gen_hourtotal'];
        $kwh_imp = $row['kwh_imp'];
        $kwh_exp = $row['kwh_exp'];
        $kvarh_imp = $row['kvarh_imp'];
        $kvarh_exp = $row['kvarh_exp'];
        $errorcode = json_encode($row['errorcode']); // if array
        $error_overtotal = $row['error_overtotal'];
        $errormaintenance = json_encode($row['errormaintenance']);
        $maintenance_overtotal = $row['maintenance_overtotal'];
        $errorgridfault = json_encode($row['errorgridfault']);
        $gridfault_overtotal = $row['gridfault_overtotal'];
        $errorgriddrop = json_encode($row['errorgriddrop']);
        $griddrop_overtotal = $row['griddrop_overtotal'];
        $overtotal_hours = $row['overtotal_hours'];
        $remarks = $row['remarks'];
        $timestamp = date('Y-m-d H:i:s');

        if ($row_result['count'] > 0) {
            // $output["status"] = 400;
            // $output["msg"] = "Turbine ID already exists";
            // echo json_encode($output, JSON_NUMERIC_CHECK);
            // exit;

            $update_sql = "UPDATE `daily_generation` SET 
                `location_no` = '$location_no',
                `gen_zero` = '$gen_zero',
                `gen_one` = '$gen_one',
                `gen_two` = '$gen_two',
                `total_production` = '$total_production',
                `gen_onehrs` = '$gen_onehrs',
                `gen_twohrs` = '$gen_twohrs',
                `gen_hourtotal` = '$gen_hourtotal',
                `kwh_imp` = '$kwh_imp',
                `kwh_exp` = '$kwh_exp',
                `kvarh_imp` = '$kvarh_imp',
                `kvarh_exp` = '$kvarh_exp',
                `errorcode` = '$errorcode',
                `error_overtotal` = '$error_overtotal',
                `errormaintenance` = '$errormaintenance',
                `maintenance_overtotal` = '$maintenance_overtotal',
                `errorgridfault` = '$errorgridfault',
                `gridfault_overtotal` = '$gridfault_overtotal',
                `errorgriddrop` = '$errorgriddrop',
                `griddrop_overtotal` = '$griddrop_overtotal',
                `overtotal_hours` = '$overtotal_hours',
                `remarks` = '$remarks',
                `created_date` = '$timestamp'
            WHERE `dg_date` = '$dg_date' AND `turbine_id` = '$turbine_id' AND `delete_at` = 0";

            mysqli_query($conn, $update_sql);

        }else{

            $sql = "INSERT INTO `daily_generation`(`dg_date`,`turbine_id`, `location_no`, `gen_zero`,`gen_one`, `gen_two`, `total_production`, `gen_onehrs`, `gen_twohrs`,`gen_hourtotal`, `kwh_imp`, `kwh_exp`, `kvarh_imp`, `kvarh_exp`, `errorcode`,`error_overtotal`, `errormaintenance`, `maintenance_overtotal`, `errorgridfault`, `gridfault_overtotal`, `errorgriddrop`, `griddrop_overtotal`, `overtotal_hours`, `remarks`, `delete_at`, `created_date`)
                    VALUES ('$dg_date','$turbine_id','$location_no','$gen_zero','$gen_one','$gen_two','$total_production','$gen_onehrs','$gen_twohrs','$gen_hourtotal','$kwh_imp','$kwh_exp','$kvarh_imp','$kvarh_exp','$errorcode','$error_overtotal','$errormaintenance','$maintenance_overtotal','$errorgridfault','$gridfault_overtotal','$errorgriddrop','$griddrop_overtotal','$overtotal_hours','$remarks','0','$timestamp')";

            $result = mysqli_query($conn, $sql);
            if ($result) {
                $id = $conn->insert_id;
                $unquid_id = uniqueID("daily_generation", $id);
                $sql = "UPDATE `daily_generation` SET `daily_generation_id`='$unquid_id' WHERE `id`='$id'";
                $result = mysqli_query($conn, $sql);
                if ($result) {
                    
                } else {
                    $output['status'] = 400;
                    $output['msg'] = "Daily Generation Not Added";
                }
            } else {
                $output['status'] = 400;
                $output['msg'] = "Daily Generation Not Added";
            }

        }
        
    }
    
}
$output['status'] = 200;
$output['msg'] = "Daily Generation Created Successfuly";

echo json_encode($output, JSON_NUMERIC_CHECK);