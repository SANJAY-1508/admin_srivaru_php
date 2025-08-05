<?php

include("../config/db_config.php");
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json; charset=utf-8');

$output = array();
date_default_timezone_set('Asia/Calcutta');
$timestamp = date('Y-m-d h:i:s');

// Get the raw POST data (JSON)
$input = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    $output['status'] = 400;
    $output['msg'] = "Invalid JSON data.";
    echo json_encode($output, JSON_NUMERIC_CHECK);
    exit;
}

// Check for turbine_id
if (!isset($input['turbine_id'])) {
    $output['status'] = 400;
    $output['msg'] = "Missing turbine_id parameter.";
    echo json_encode($output, JSON_NUMERIC_CHECK);
    exit;
}

$turbine_id = $input['turbine_id'];

// Retrieve other parameters from $input
$date = $input['date'];
$customer_id = $input['customer_id'];
$customername_id = $input['customername_id'];
$wtg_no = $input['wtg_no'];
$loc_no = $input['loc_no'];
$htsc_no = $input['htsc_no'];
$site_id = $input['site_id'];
$location_id = $input['location_id'];
$model_id = $input['model_id'];
$contracttype_id = $input['contracttype_id'];
$dgr_need = $input['dgr_need'];
$capacity = $input['capacity'];
$feeder_voltage = $input['feeder_voltage'];
$feed_name = $input['feed_name'];
$sub_station = $input['sub_station'];
$tower_ht = $input['tower_ht'];
$latitude = $input['latitude'];
$lognitude = $input['lognitude'];
$controler = $input['controler'];
$ctpt_make = $input['ctpt_make'];
$ctpt_sino = $input['ctpt_sino'];
$ctpt_ratio = $input['ctpt_ratio'];
$ctpt_multiplicationfactor = $input['ctpt_multiplicationfactor'];
$transformer_make = $input['transformer_make'];
$transformer_sino = $input['transformer_sino'];
$transformer_ratio = $input['transformer_ratio'];
$energymeter_sino = $input['energymeter_sino'];
$energymeter_ratio = $input['energymeter_ratio'];
$acb_sino = $input['acb_sino'];
$acb_ratio = $input['acb_ratio'];
$apfcpanel_make = $input['apfcpanel_make'];
$apfcpanel_sino = $input['apfcpanel_sino'];
$mainpanel_make = $input['mainpanel_make'];
$mainpanel_sino = $input['mainpanel_sino'];
$gearbox_make = $input['gearbox_make'];
$gearbox_sino = $input['gearbox_sino'];
$generator_make = $input['generator_make'];
$generator_sino = $input['generator_sino'];
$bladeone_make = $input['bladeone_make'];
$bladeone_sino = $input['bladeone_sino'];
$bladeone_classofweight = $input['bladeone_classofweight'];
$bladeone_bladebearing = $input['bladeone_bladebearing'];
$bladetwo_make = $input['bladetwo_make'];
$bladetwo_sino = $input['bladetwo_sino'];
$bladetwo_classofweight = $input['bladetwo_classofweight'];
$bladetwo_bladebearing = $input['bladetwo_bladebearing'];
$bladethree_make = $input['bladethree_make'];
$bladethree_sino = $input['bladethree_sino'];
$bladethree_classofweight = $input['bladethree_classofweight'];
$bladethree_bladebearing = $input['bladethree_bladebearing'];
$hydraunit_make = $input['hydraunit_make'];
$hydraunit_sino = $input['hydraunit_sino'];
$hydramotor_make = $input['hydramotor_make'];
$hydramotor_sino = $input['hydramotor_sino'];
$hydrafiltertype_make = $input['hydrafiltertype_make'];
$hydrafiltertype_sino = $input['hydrafiltertype_sino'];
$propositionalvalve_make = $input['propositionalvalve_make'];
$propositionalvalve_sino = $input['propositionalvalve_sino'];
$incharge_name = $input['incharge_name'];
$incharge_mobile_no = $input['incharge_mobile_no'];
$siteoperator_name = $input['siteoperator_name'];
$siteoperator_mobileno = $input['siteoperator_mobileno'];
/// Check if turbine exists
$check_sql = "SELECT COUNT(*) as count FROM `turbine` WHERE `turbine_id` = '$turbine_id' AND `delete_at` = '0'";
$check_result = mysqli_query($conn, $check_sql);

if ($check_result) {
$count = mysqli_fetch_assoc($check_result)['count'];

if ($count > 0) {
    // Perform update
    $sql = "UPDATE `turbine` SET
        `date` = '$date', 
        `customer_id` = '$customer_id', 
        `customername_id` = '$customername_id',
        `wtg_no` = '$wtg_no',
        `loc_no` = '$loc_no', 
        `htsc_no` = '$htsc_no', 
        `site_id` = '$site_id', 
        `location_id` = '$location_id', 
        `model_id` = '$model_id', 
        `contracttype_id` = '$contracttype_id', 
        `dgr_need` = '$dgr_need', 
        `capacity` = '$capacity', 
        `feeder_voltage` = '$feeder_voltage', 
        `feed_name` = '$feed_name', 
        `sub_station` = '$sub_station', 
        `tower_ht` = '$tower_ht', 
        `latitude` = '$latitude', 
        `lognitude` = '$lognitude', 
        `controler` = '$controler', 
        `ctpt_make` = '$ctpt_make', 
        `ctpt_sino` = '$ctpt_sino', 
        `ctpt_ratio` = '$ctpt_ratio', 
        `ctpt_multiplicationfactor` = '$ctpt_multiplicationfactor', 
        `transformer_make` = '$transformer_make', 
        `transformer_sino` = '$transformer_sino', 
        `transformer_ratio` = '$transformer_ratio', 
        `energymeter_sino` = '$energymeter_sino', 
        `energymeter_ratio` = '$energymeter_ratio', 
        `acb_sino` = '$acb_sino', 
        `acb_ratio` = '$acb_ratio', 
        `apfcpanel_make` = '$apfcpanel_make', 
        `apfcpanel_sino` = '$apfcpanel_sino', 
        `mainpanel_make` = '$mainpanel_make', 
        `mainpanel_sino` = '$mainpanel_sino', 
        `gearbox_make` = '$gearbox_make', 
        `gearbox_sino` = '$gearbox_sino', 
        `generator_make` = '$generator_make', 
        `generator_sino` = '$generator_sino', 
        `bladeone_make` = '$bladeone_make', 
        `bladeone_sino` = '$bladeone_sino', 
        `bladeone_classofweight` = '$bladeone_classofweight', 
        `bladeone_bladebearing` = '$bladeone_bladebearing', 
        `bladetwo_make` = '$bladetwo_make', 
        `bladetwo_sino` = '$bladetwo_sino', 
        `bladetwo_classofweight` = '$bladetwo_classofweight', 
        `bladetwo_bladebearing` = '$bladetwo_bladebearing', 
        `bladethree_make` = '$bladethree_make', 
        `bladethree_sino` = '$bladethree_sino', 
        `bladethree_classofweight` = '$bladethree_classofweight', 
        `bladethree_bladebearing` = '$bladethree_bladebearing', 
        `hydraunit_make` = '$hydraunit_make', 
        `hydraunit_sino` = '$hydraunit_sino', 
        `hydramotor_make` = '$hydramotor_make', 
        `hydramotor_sino` = '$hydramotor_sino', 
        `hydrafiltertype_make` = '$hydrafiltertype_make', 
        `hydrafiltertype_sino` = '$hydrafiltertype_sino', 
        `propositionalvalve_make` = '$propositionalvalve_make', 
        `propositionalvalve_sino` = '$propositionalvalve_sino', 
        `incharge_name` = '$incharge_name', 
        `incharge_mobile_no` = '$incharge_mobile_no', 
        `siteoperator_name` = '$siteoperator_name', 
        `siteoperator_mobileno` = '$siteoperator_mobileno'
        WHERE `turbine_id` = '$turbine_id'";

    $result = mysqli_query($conn, $sql);

    if ($result) {
        $output['status'] = 200;
        $output['msg'] = "Turbine data updated successfully.";
    } else {
        $output['status'] = 500;
        $output['msg'] = "Error updating turbine data: " . mysqli_error($conn);
    }
} else {
    $output['status'] = 404;
    $output['msg'] = "Turbine not found.";
}
} else {
$output['status'] = 500;
$output['msg'] = "Error checking turbine existence: " . mysqli_error($conn);
}

echo json_encode($output, JSON_NUMERIC_CHECK);

?>
