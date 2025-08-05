<?php

include ("../config/db_config.php");
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Content-Type");

header('Content-Type: application/json; charset=utf-8');

$json = file_get_contents('php://input');
$obj = json_decode($json);
$output = array();


date_default_timezone_set('Asia/Calcutta');
$timestamp = date('Y-m-d h:i:s');


if (isset($obj->dg_date) && isset($obj->turbine_id) && isset($obj->location_no) && isset($obj->gen_zero) && isset($obj->gen_one) && isset($obj->gen_two) && isset($obj->total_production) && isset($obj->gen_onehrs) && isset($obj->gen_twohrs) && isset($obj->gen_hourtotal) && isset($obj->kwh_imp) && isset($obj->kwh_exp) && isset($obj->kvarh_imp) && isset($obj->kvarh_exp) && isset($obj->errorcode) && isset($obj->error_overtotal) && isset($obj->errormaintenance) && isset($obj->maintenance_overtotal) && isset($obj->errorgridfault) && isset($obj->gridfault_overtotal) && isset($obj->errorgriddrop) && isset($obj->griddrop_overtotal) && isset($obj->overtotal_hours) && isset($obj->remarks)) {
    $dg_date = $obj->dg_date;
    $turbine_id = $obj->turbine_id;
    $location_no = $obj->location_no;
    $gen_zero = $obj->gen_zero;
    $gen_one = $obj->gen_one;
    $gen_two = $obj->gen_two;
    $total_production = $obj->total_production;
    $gen_onehrs = $obj->gen_onehrs;
    $gen_twohrs = $obj->gen_twohrs;
    $gen_hourtotal = $obj->gen_hourtotal;
    $kwh_imp = $obj->kwh_imp;
    $kwh_exp = $obj->kwh_exp;
    $kvarh_imp = $obj->kvarh_imp;
    $kvarh_exp = $obj->kvarh_exp;
    $errorcode = $obj->errorcode;
    $error_overtotal = $obj->error_overtotal;
    $errormaintenance = $obj->errormaintenance;
    $maintenance_overtotal = $obj->maintenance_overtotal;
    $errorgridfault = $obj->errorgridfault;
    $gridfault_overtotal = $obj->gridfault_overtotal;
    $errorgriddrop = $obj->errorgriddrop;
    $griddrop_overtotal = $obj->griddrop_overtotal;
    $overtotal_hours = $obj->overtotal_hours;
    $remarks = $obj->remarks;

    if (!is_array($errorcode)) {
        $output["status"] = 400;
        $output["msg"] = "Error Code parameter must be an array";
        echo json_encode($output, JSON_NUMERIC_CHECK);
        exit;
    }

    if (!is_array($errormaintenance)) {
        $output["status"] = 400;
        $output["msg"] = "Error Maintenance parameter must be an array";
        echo json_encode($output, JSON_NUMERIC_CHECK);
        exit;
    }

    if (!is_array($errorgridfault)) {
        $output["status"] = 400;
        $output["msg"] = "Error Gridfault parameter must be an array";
        echo json_encode($output, JSON_NUMERIC_CHECK);
        exit;
    }

    if (!is_array($errorgriddrop)) {
        $output["status"] = 400;
        $output["msg"] = "Error Griddrop parameter must be an array";
        echo json_encode($output, JSON_NUMERIC_CHECK);
        exit;
    }

    foreach ($errorcode as $eacherror_code) {
        if (!isset($eacherror_code->error_id) || !isset($eacherror_code->error_describtion) || !isset($eacherror_code->error_from) || !isset($eacherror_code->error_to) || !isset($eacherror_code->error_total)) {
            $output["status"] = 400;
            $output["msg"] = "Error Code details";
            echo json_encode($output, JSON_NUMERIC_CHECK);
            exit;
        }
    }

    foreach ($errormaintenance as $each_errormaintenance) {
        if (!isset($each_errormaintenance->maintenance_id) || !isset($each_errormaintenance->maintenance_name) || !isset($each_errormaintenance->maintenance_from) || !isset($each_errormaintenance->maintenance_to) || !isset($each_errormaintenance->maintenance_total)) {
            $output["status"] = 400;
            $output["msg"] = "Error Maintenance details";
            echo json_encode($output, JSON_NUMERIC_CHECK);
            exit;
        }
    }

    foreach ($errorgridfault as $each_errorgridfault) {
        if (!isset($each_errorgridfault->gridfault_id) || !isset($each_errorgridfault->gridfault_name) || !isset($each_errorgridfault->gridfault_from) || !isset($each_errorgridfault->gridfault_to) || !isset($each_errorgridfault->gridfault_total)) {
            $output["status"] = 400;
            $output["msg"] = "Error Gridfault details";
            echo json_encode($output, JSON_NUMERIC_CHECK);
            exit;
        }
    }

    foreach ($errorgriddrop as $each_errorgriddrop) {
        if (!isset($each_errorgriddrop->griddrop_id) || !isset($each_errorgriddrop->griddrop_name) || !isset($each_errorgriddrop->griddrop_from) || !isset($each_errorgriddrop->griddrop_to) || !isset($each_errorgriddrop->griddrop_total)) {
            $output["status"] = 400;
            $output["msg"] = "Error Griddrop details";
            echo json_encode($output, JSON_NUMERIC_CHECK);
            exit;
        }
    }
    $errorcode = json_encode($errorcode);
    $errormaintenance = json_encode($errormaintenance);
    $errorgridfault = json_encode($errorgridfault);
    $errorgriddrop = json_encode($errorgriddrop);

    // Check if turbine_id already exists for the given dg_date
    $check_sql = "SELECT COUNT(*) as count FROM `daily_generation` WHERE `dg_date` = '$dg_date' AND `turbine_id` = '$turbine_id'AND `delete_at`=0";
    $check_result = mysqli_query($conn, $check_sql);
    $row = mysqli_fetch_assoc($check_result);

    if ($row['count'] > 0) {
        $output["status"] = 400;
        $output["msg"] = "Turbine ID already exists";
    } else {
        if (!empty($turbine_id)) {
            $sql = "INSERT INTO `daily_generation`(`dg_date`,`turbine_id`, `location_no`, `gen_zero`,`gen_one`, `gen_two`, `total_production`, `gen_onehrs`, `gen_twohrs`,`gen_hourtotal`, `kwh_imp`, `kwh_exp`, `kvarh_imp`, `kvarh_exp`, `errorcode`,`error_overtotal`, `errormaintenance`, `maintenance_overtotal`, `errorgridfault`, `gridfault_overtotal`, `errorgriddrop`, `griddrop_overtotal`, `overtotal_hours`, `remarks`, `delete_at`, `created_date`)
                    VALUES ('$dg_date','$turbine_id','$location_no','$gen_zero','$gen_one','$gen_two','$total_production','$gen_onehrs','$gen_twohrs','$gen_hourtotal','$kwh_imp','$kwh_exp','$kvarh_imp','$kvarh_exp','$errorcode','$error_overtotal','$errormaintenance','$maintenance_overtotal','$errorgridfault','$gridfault_overtotal','$errorgriddrop','$griddrop_overtotal','$overtotal_hours','$remarks','0','$timestamp')";
            $result = mysqli_query($conn, $sql);
            if ($result) {
                $id = $conn->insert_id;
                $unquid_id = uniqueID("daily_generation", $id);
                $sql = "UPDATE `daily_generation` SET `daily_generation_id`='$unquid_id' WHERE `id`='$id'";
                $result = mysqli_query($conn, $sql);
                if ($result) {
                    $output['status'] = 200;
                    $output['msg'] = "Daily Generation Added Successfully";
                } else {
                    $output['status'] = 400;
                    $output['msg'] = "Daily Generation Not Added";
                }
            } else {
                $output['status'] = 400;
                $output['msg'] = "Daily Generation Not Added";
            }
        } else {
            $output["status"] = 400;
            $output["msg"] = "Some Parameter is Empty";
        }
    }
} else {
    $output["status"] = 400;
    $output["msg"] = "Parameter is Mismatch";
}

echo json_encode($output, JSON_NUMERIC_CHECK);