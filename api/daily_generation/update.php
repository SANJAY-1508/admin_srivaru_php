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

// Check if all required parameters are set
if (
    isset($obj->daily_generation_id) &&
    isset($obj->dg_date) &&
    isset($obj->turbine_id) &&
    isset($obj->location_no) &&
    isset($obj->gen_zero) &&
    isset($obj->gen_one) &&
    isset($obj->gen_two) &&
    isset($obj->total_production) &&
    isset($obj->gen_onehrs) &&
    isset($obj->gen_twohrs) &&
    isset($obj->gen_hourtotal) &&
    isset($obj->kwh_imp) &&
    isset($obj->kwh_exp) &&
    isset($obj->kvarh_imp) &&
    isset($obj->kvarh_exp) &&
    isset($obj->error_overtotal) &&
    isset($obj->errormaintenance) &&
    isset($obj->maintenance_overtotal) &&
    isset($obj->errorgridfault) &&
    isset($obj->gridfault_overtotal) &&
    isset($obj->errorgriddrop) &&
    isset($obj->griddrop_overtotal) &&
    isset($obj->errorcode) &&
    isset($obj->overtotal_hours) &&
    isset($obj->remarks)
) {
    // Extract parameters from the JSON object
    $daily_generation_id = $obj->daily_generation_id;
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
    $errorcode = json_encode($obj->errorcode);
    $error_overtotal = $obj->error_overtotal;
    $errormaintenance = json_encode($obj->errormaintenance);
    $maintenance_overtotal = $obj->maintenance_overtotal;
    $errorgridfault = json_encode($obj->errorgridfault);
    $gridfault_overtotal = $obj->gridfault_overtotal;
    $errorgriddrop = json_encode($obj->errorgriddrop);
    $griddrop_overtotal = $obj->griddrop_overtotal;
    $overtotal_hours = $obj->overtotal_hours;
    $remarks = $obj->remarks;

    // Check if daily_generation_id is not empty
    if (!empty($daily_generation_id)) {
        // Construct SQL query
        $update_query = "UPDATE `daily_generation` SET 
            `dg_date`='$dg_date',
            `turbine_id`='$turbine_id',
            `location_no`='$location_no',
            `gen_zero`='$gen_zero',
            `gen_one`='$gen_one',
            `gen_two`='$gen_two',
            `total_production`='$total_production',
            `gen_onehrs`='$gen_onehrs',
            `gen_twohrs`='$gen_twohrs',
            `gen_hourtotal`='$gen_hourtotal',
            `kwh_imp`='$kwh_imp',
            `kwh_exp`='$kwh_exp',
            `kvarh_imp`='$kvarh_imp',
            `kvarh_exp`='$kvarh_exp',
            `errorcode`='$errorcode',
            `error_overtotal`='$error_overtotal',
            `errormaintenance`='$errormaintenance',
            `maintenance_overtotal`='$maintenance_overtotal',
            `errorgridfault`='$errorgridfault',
            `gridfault_overtotal`='$gridfault_overtotal',
            `errorgriddrop`='$errorgriddrop',
            `griddrop_overtotal`='$griddrop_overtotal',
            `overtotal_hours`='$overtotal_hours',
            `remarks`='$remarks'
            WHERE `daily_generation_id`='$daily_generation_id'";

        // Execute the query
        $update_result = mysqli_query($conn, $update_query);

        // Check if update was successful
        if ($update_result) {
            $output['status'] = 200;
            $output['msg'] = "Daily generation updated successfully";
        } else {
            $output['status'] = 500;
            $output['msg'] = "Error updating daily generation: " . mysqli_error($conn);
        }
    } else {
        $output['status'] = 400;
        $output['msg'] = "Parameter 'daily_generation_id' is empty";
    }
} else {
    $output['status'] = 400;
    $output['msg'] = "Parameter mismatch or missing parameters";
}

echo json_encode($output, JSON_NUMERIC_CHECK);
