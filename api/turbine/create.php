<?php

include("../config/db_config.php");
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json; charset=utf-8');

$output = array();
date_default_timezone_set('Asia/Calcutta');
$timestamp = date('Y-m-d h:i:s');
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Get the raw POST data (JSON)
$input = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    $output['status'] = 400;
    $output['msg'] = "Invalid JSON data.";
    echo json_encode($output, JSON_NUMERIC_CHECK);
    exit;
}

$required_param = "wtg_no";
$params = [
    // 'date', 'customer_id', 'customername_id', 'wtg_no', 'loc_no', 'htsc_no', 'site_id', 'location_id', 'model_id',
    // 'contracttype_id', 'dgr_need', 'capacity', 'feeder_voltage', 'feed_name', 'sub_station', 'tower_ht', 'latitude',
    // 'lognitude', 'controler', 'ctpt_make', 'ctpt_sino', 'ctpt_ratio', 'ctpt_multiplicationfactor', 'transformer_make',
    // 'transformer_sino', 'transformer_ratio', 'energymeter_sino', 'energymeter_ratio', 'acb_sino', 'acb_ratio', 
    // 'apfcpanel_make', 'apfcpanel_sino', 'mainpanel_make', 'mainpanel_sino', 'gearbox_make', 'gearbox_sino',
    // 'generator_make', 'generator_sino', 'bladeone_make', 'bladeone_sino', 'bladeone_classofweight', 'bladeone_bladebearing',
    // 'bladetwo_make', 'bladetwo_sino', 'bladetwo_classofweight', 'bladetwo_bladebearing', 'bladethree_make', 
    // 'bladethree_sino', 'bladethree_classofweight', 'bladethree_bladebearing', 'hydraunit_make', 'hydraunit_sino', 
    // 'hydramotor_make', 'hydramotor_sino', 'hydrafiltertype_make', 'hydrafiltertype_sino', 'propositionalvalve_make', 
    // 'propositionalvalve_sino', 'incharge_name', 'incharge_mobile_no', 'siteoperator_name', 'siteoperator_mobileno','pdf_files'
];
$error = 0;

// Check for required parameters in $input
foreach ($params as $element) {
    if (!isset($input[$element])) {
        $error = 1;
        break;
    }
}

if ($error == 0) {
    // Retrieve values from $input
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

    $check_sql = "SELECT COUNT(*) as count FROM `turbine` WHERE `wtg_no` = '$wtg_no' AND `delete_at` = '0'";
    
    $check_result = mysqli_query($conn, $check_sql);

    if (!$check_result) {
        $output['status'] = 400;
        $output['msg'] = "Failed to check WTG_no";
        $output['sql_error'] = mysqli_error($conn); // Capture the specific SQL error
        echo json_encode($output);  // Return the error message as JSON
        exit;  // Stop further execution
    }
    
    
    $count = mysqli_fetch_assoc($check_result)['count'];
    
    if ($count == 0) {
        // Proceed with inserting new turbine data as the wtg_no is unique
        $turbine_id = uniqid('turbine_'); // Prefix 'turbine_' to the unique ID.
        
        $sql = "INSERT INTO `turbine`(
            `turbine_id`, `date`, `customer_id`, `customername_id`, `wtg_no`, `loc_no`, `htsc_no`, `site_id`, 
            `location_id`, `model_id`, `contracttype_id`, `dgr_need`, `capacity`, `feeder_voltage`, 
            `feed_name`, `sub_station`, `tower_ht`, `latitude`, `lognitude`, `controler`, `ctpt_make`, 
            `ctpt_sino`, `ctpt_ratio`, `ctpt_multiplicationfactor`, `transformer_make`, `transformer_sino`, 
            `transformer_ratio`, `energymeter_sino`, `energymeter_ratio`, `acb_sino`, `acb_ratio`, 
            `apfcpanel_make`, `apfcpanel_sino`, `mainpanel_make`, `mainpanel_sino`, `gearbox_make`, 
            `gearbox_sino`, `generator_make`, `generator_sino`, `bladeone_make`, `bladeone_sino`, 
            `bladeone_classofweight`, `bladeone_bladebearing`, `bladetwo_make`, `bladetwo_sino`, 
            `bladetwo_classofweight`, `bladetwo_bladebearing`, `bladethree_make`, `bladethree_sino`, 
            `bladethree_classofweight`, `bladethree_bladebearing`, `hydraunit_make`, `hydraunit_sino`, 
            `hydramotor_make`, `hydramotor_sino`, `hydrafiltertype_make`, `hydrafiltertype_sino`, 
            `propositionalvalve_make`, `propositionalvalve_sino`, `incharge_name`, `incharge_mobile_no`, 
            `siteoperator_name`, `siteoperator_mobileno`, `delete_at`, `created_date`
        ) 
        VALUES (
            '$turbine_id', '$date', '$customer_id', '$customername_id', '$wtg_no', '$loc_no', '$htsc_no', '$site_id', 
            '$location_id', '$model_id', '$contracttype_id', '$dgr_need', '$capacity', '$feeder_voltage', 
            '$feed_name', '$sub_station', '$tower_ht', '$latitude', '$lognitude', '$controler', '$ctpt_make', 
            '$ctpt_sino', '$ctpt_ratio', '$ctpt_multiplicationfactor', '$transformer_make', '$transformer_sino', 
            '$transformer_ratio', '$energymeter_sino', '$energymeter_ratio', '$acb_sino', '$acb_ratio', 
            '$apfcpanel_make', '$apfcpanel_sino', '$mainpanel_make', '$mainpanel_sino', '$gearbox_make', 
            '$gearbox_sino', '$generator_make', '$generator_sino', '$bladeone_make', '$bladeone_sino', 
            '$bladeone_classofweight', '$bladeone_bladebearing', '$bladetwo_make', '$bladetwo_sino', 
            '$bladetwo_classofweight', '$bladetwo_bladebearing', '$bladethree_make', '$bladethree_sino', 
            '$bladethree_classofweight', '$bladethree_bladebearing', '$hydraunit_make', '$hydraunit_sino', 
            '$hydramotor_make', '$hydramotor_sino', '$hydrafiltertype_make', '$hydrafiltertype_sino', 
            '$propositionalvalve_make', '$propositionalvalve_sino', '$incharge_name', '$incharge_mobile_no', 
            '$siteoperator_name', '$siteoperator_mobileno', '0', '$timestamp'
        )";
    
        if (mysqli_query($conn, $sql)) {
            $output['status'] = 200;
            $output['msg'] = "Turbine and PDFs Added Successfully";
        } else {
            $output['status'] = 400;
            $output['msg'] = "Failed to add Turbine";
            $output['sql_error'] = mysqli_error($conn); // Capture SQL error
            echo json_encode($output);
        }
    } else {
        $output['status'] = 400;
        $output['msg'] = "WTG_no already exists or database error";
    }
} else {
    // Query error for checking WTG_no
    $output['status'] = 400;
    $output['msg'] = "Failed to check WTG_no";
}


echo json_encode($output, JSON_NUMERIC_CHECK);