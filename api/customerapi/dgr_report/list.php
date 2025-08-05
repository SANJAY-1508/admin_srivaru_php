<?php
// Include database configuration file
include("../../config/db_config.php");

// Enable CORS and set JSON content type
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json; charset=utf-8');

// Retrieve JSON input from the request
$json = file_get_contents('php://input');
$obj = json_decode($json);
$output = array();

// Default timezone and timestamp
date_default_timezone_set('Asia/Calcutta');
$timestamp = date('Y-m-d');

// Validate inputs
if (
    isset($obj->wtg_no)
    && isset($obj->report)
    && isset($obj->from_date)
    && isset($obj->to_date)
    && isset($obj->user_id)
    && isset($obj->customer_unique_id)
) {
    $report = $obj->report;
    $from_date = $obj->from_date;
    $to_date = $obj->to_date;
    $user_id = $obj->user_id;
    $customer_unique_id = $obj->customer_unique_id;

    if (strtotime($from_date) === false || strtotime($to_date) === false) {
        $output["status"] = 400;
        $output["msg"] = "Invalid date format";
        echo json_encode($output, JSON_NUMERIC_CHECK);
        exit;
    }

    // Initialize flags to determine the user type (customer or customer group)
    $is_customer_group = false;
    $is_customer = false;

    // Check if user_id exists in customer_group table
    $sql_check_group = "SELECT * FROM customer_group WHERE BINARY customergroup_uniq_id COLLATE utf8mb4_general_ci = ?";
    $stmt_check_group = $conn->prepare($sql_check_group);
    $stmt_check_group->bind_param("s", $user_id);
    $stmt_check_group->execute();
    $result_check_group = $stmt_check_group->get_result();

    if ($result_check_group->num_rows > 0) {
        $is_customer_group = true;
    } else {
        // Check if user_id exists in customer table
        $sql_check_customer = "SELECT * FROM customer WHERE BINARY customer_unique_id COLLATE utf8mb4_general_ci = ? AND delete_at = '0'";
        $stmt_check_customer = $conn->prepare($sql_check_customer);
        $stmt_check_customer->bind_param("s", $user_id);
        $stmt_check_customer->execute();
        $result_check_customer = $stmt_check_customer->get_result();

        if ($result_check_customer->num_rows > 0) {
            $is_customer = true;
        } else {
            $output["status"] = 404;
            $output["msg"] = "User ID not found in customer_group or customer table";
            echo json_encode($output, JSON_NUMERIC_CHECK);
            exit;
        }
    }

    // Handle wtg_no input
    $wtg_no = isset($obj->wtg_no) ? (array) $obj->wtg_no : array();
    $in_clause_wtg_no = !empty($wtg_no) ? implode(',', array_map('quote', $wtg_no)) : '';

    // Handle customer_unique_id input
    $customer_unique_id = isset($obj->customer_unique_id) ? (array) $obj->customer_unique_id : array();
    $in_clause_customer_ids = !empty($customer_unique_id) ? implode(',', array_map('quote', $customer_unique_id)) : '';

    // Construct the SQL query to get data based on the report type
    if ($is_customer_group) {
        $sql_select = "
        SELECT 
            c.*, 
            t.*,
            s.*,
            " . ($report == 'daily' ? "DATE_FORMAT(dg.dg_date, '%Y-%m-%d') AS day" : ($report == 'monthly' ? "DATE_FORMAT(dg.dg_date, '%b-%Y') AS month" : "CASE 
    WHEN MONTH(dg.dg_date) >= 4 
    THEN CONCAT(YEAR(dg.dg_date), '-', YEAR(dg.dg_date) + 1)
    ELSE CONCAT(YEAR(dg.dg_date) - 1, '-', YEAR(dg.dg_date))
END AS year")) . ",
            " . ($report == 'daily' ? "
             dg.kwh_exp,
            dg.kwh_imp,
            dg.gen_zero,
            dg.gen_one,
            dg.dg_date,
            dg.gen_two,
            dg.gen_onehrs,
            dg.gen_twohrs,
            dg.gen_hourtotal,
            dg.kvarh_exp,
            dg.kvarh_imp,
            dg.total_production,
            dg.error_overtotal,
            dg.griddrop_overtotal,
            dg.gridfault_overtotal,
                        dg.errormaintenance,
            dg.errorcode,
dg.errorgriddrop,
dg.errorgridfault
            " : "
            COUNT(DISTINCT dg.dg_date) AS data_days,
            SUM(dg.kwh_exp) AS kwh_exp,
            SUM(dg.kwh_imp) AS kwh_imp,
            SUM(dg.gen_zero) AS gen_zero,
            SUM(dg.gen_one) AS gen_one,
            SUM(dg.gen_two) AS gen_two,
            SUM(dg.gen_onehrs) AS gen_onehrs,
            SUM(dg.gen_twohrs) AS gen_twohrs,
            SUM(dg.gen_hourtotal) AS gen_hourtotal,
            SUM(dg.kvarh_exp) AS kvarh_exp,
            SUM(dg.kvarh_imp) AS kvarh_imp,
            SUM(dg.total_production) AS total_production,
            SUM(dg.error_overtotal) AS error_overtotal,
            SUM(dg.griddrop_overtotal) AS griddrop_overtotal,
            SUM(dg.gridfault_overtotal) AS gridfault_overtotal,
            SUM(dg.maintenance_overtotal) AS maintenance_overtotal,
            dg.errormaintenance,
            dg.errorcode,
dg.errorgriddrop,
dg.errorgridfault
            ") . "
        FROM 
            customer_group cg
            INNER JOIN customer c ON cg.customergroup_uniq_id COLLATE utf8mb4_general_ci = c.customergroupname_id COLLATE utf8mb4_general_ci
            INNER JOIN turbine t ON c.customer_unique_id COLLATE utf8mb4_general_ci = t.customer_id COLLATE utf8mb4_general_ci
            INNER JOIN daily_generation dg ON t.turbine_id = dg.turbine_id
            INNER JOIN site s ON t.site_id = s.site_id
        WHERE 
            dg.delete_at = '0' 
            AND c.delete_at = '0' 
            AND t.delete_at = '0' 
            AND s.delete_at = '0'
            AND dg.dg_date BETWEEN ? AND ?
            AND cg.customergroup_uniq_id COLLATE utf8mb4_general_ci = ?
            " . (!empty($in_clause_customer_ids) ? " AND c.customer_unique_id IN (" . $in_clause_customer_ids . ")" : "") . "
            " . (!empty($in_clause_wtg_no) ? " AND t.wtg_no IN (" . $in_clause_wtg_no . ")" : "") . "
        " . ($report == 'monthly' ? " GROUP BY DATE_FORMAT(dg.dg_date, '%b-%Y'), c.customer_unique_id, t.wtg_no ORDER BY dg.dg_date ASC" : ($report == 'yearly' ? " GROUP BY CASE WHEN MONTH(dg.dg_date) >= 4 THEN CONCAT(YEAR(dg.dg_date), '-', YEAR(dg.dg_date) + 1) ELSE CONCAT(YEAR(dg.dg_date) - 1, '-', YEAR(dg.dg_date)) END, c.customer_unique_id, t.wtg_no ORDER BY dg.dg_date ASC" : " ORDER BY dg.dg_date ASC"));
    } elseif ($is_customer) {
        $sql_select = "
        SELECT 
            c.*, 
            t.*,
            s.*,
            " . ($report == 'daily' ? "DATE_FORMAT(dg.dg_date, '%Y-%m-%d') AS day" : ($report == 'monthly' ? "DATE_FORMAT(dg.dg_date, '%b-%Y') AS month" : "CASE 
    WHEN MONTH(dg.dg_date) >= 4 
    THEN CONCAT(YEAR(dg.dg_date), '-', YEAR(dg.dg_date) + 1)
    ELSE CONCAT(YEAR(dg.dg_date) - 1, '-', YEAR(dg.dg_date))
END AS year")) . ",
            " . ($report == 'daily' ? "
            dg.kwh_exp,
            dg.kwh_imp,
            dg.dg_date,
            dg.gen_zero,
            dg.gen_one,
            dg.gen_two,
            dg.gen_onehrs,
            dg.gen_twohrs,
            dg.gen_hourtotal,
            dg.kvarh_exp,
            dg.kvarh_imp,
            dg.total_production,
            dg.error_overtotal,
            dg.griddrop_overtotal,
            dg.gridfault_overtotal,
                        dg.errormaintenance,
            dg.errorcode,
dg.errorgriddrop,
dg.errorgridfault
            " : "
            COUNT(DISTINCT dg.dg_date) AS data_days,
            SUM(dg.kwh_exp) AS kwh_exp,
            SUM(dg.kwh_imp) AS kwh_imp,
            SUM(dg.gen_zero) AS gen_zero,
            SUM(dg.gen_one) AS gen_one,
            SUM(dg.gen_two) AS gen_two,
            SUM(dg.gen_onehrs) AS gen_onehrs,
            SUM(dg.gen_twohrs) AS gen_twohrs,
            SUM(dg.gen_hourtotal) AS gen_hourtotal,
            SUM(dg.kvarh_exp) AS kvarh_exp,
            SUM(dg.kvarh_imp) AS kvarh_imp,
            SUM(dg.total_production) AS total_production,
            SUM(dg.error_overtotal) AS error_overtotal,
            SUM(dg.griddrop_overtotal) AS griddrop_overtotal,
            SUM(dg.gridfault_overtotal) AS gridfault_overtotal,
            SUM(dg.maintenance_overtotal) AS maintenance_overtotal,
                        dg.errormaintenance,
            dg.errorcode,
dg.errorgriddrop,
dg.errorgridfault
            ") . "
        FROM 
            customer c
            INNER JOIN turbine t ON c.customer_unique_id COLLATE utf8mb4_general_ci = t.customer_id COLLATE utf8mb4_general_ci
            INNER JOIN daily_generation dg ON t.turbine_id = dg.turbine_id
            INNER JOIN site s ON t.site_id = s.site_id
        WHERE 
            dg.delete_at = '0' 
            AND c.delete_at = '0' 
            AND t.delete_at = '0' 
            AND s.delete_at = '0'
            AND dg.dg_date BETWEEN ? AND ?
            AND c.customer_unique_id COLLATE utf8mb4_general_ci = ?
            " . (!empty($in_clause_customer_ids) ? " AND c.customer_unique_id IN (" . $in_clause_customer_ids . ")" : "") . "
            " . (!empty($in_clause_wtg_no) ? " AND t.wtg_no IN (" . $in_clause_wtg_no . ")" : "") . "
        " . ($report == 'monthly' ? " GROUP BY DATE_FORMAT(dg.dg_date, '%b-%Y'), c.customer_unique_id, t.wtg_no ORDER BY dg.dg_date ASC" : ($report == 'yearly' ? " GROUP BY CASE WHEN MONTH(dg.dg_date) >= 4 THEN CONCAT(YEAR(dg.dg_date), '-', YEAR(dg.dg_date) + 1) ELSE CONCAT(YEAR(dg.dg_date) - 1, '-', YEAR(dg.dg_date)) END, c.customer_unique_id, t.wtg_no ORDER BY dg.dg_date ASC" : " ORDER BY dg.dg_date ASC"));
    } else {
        $output["status"] = 404;
        $output["msg"] = "User ID not found in customer_group or customer table";
        echo json_encode($output, JSON_NUMERIC_CHECK);
        exit;
    }

    // Prepare and execute the SQL statement
    $stmt = $conn->prepare($sql_select);
    $stmt->bind_param("sss", $from_date, $to_date, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = array();
    $total_hours = 0;

    // Process the results
    while ($row = $result->fetch_assoc()) {
        // Calculate total_hours as a numeric value
        if ($report == 'daily') {
            // For daily report, set total_hours to 24 for each day
            $total_hours = 24;
        } elseif ($report == 'monthly' || $report == 'yearly') {
            // For monthly and yearly reports, multiply data_days by 24 to get total_hours
            $total_hours = isset($row['data_days']) ? ($row['data_days'] * 24) : 0;
        }

        // Store total_hours as a numeric value in the row
        $row['total_hours'] = $total_hours;

        // Calculate error_overtotal, griddrop_overtotal, and gridfault_overtotal
        // Existing variables
        $error_overtotal = isset($row['error_overtotal']) ? (float) $row['error_overtotal'] : 0;
        $griddrop_overtotal = isset($row['griddrop_overtotal']) ? (float) $row['griddrop_overtotal'] : 0;
        $gridfault_overtotal = isset($row['gridfault_overtotal']) ? (float) $row['gridfault_overtotal'] : 0;
        $maintenance_overtotal = isset($row['maintenance_overtotal']) ? (float) $row['maintenance_overtotal'] : 0;
        $kwh_exp = isset($row['kwh_exp']) ? (float) $row['kwh_exp'] : 0;
        $kvarh_imp = isset($row['kvarh_imp']) ? (float) $row['kvarh_imp'] : 0;
        $kwh_imp = isset($row['kwh_imp']) ? (float) $row['kwh_imp'] : 0;
        $total_production = isset($row['total_production']) ? (float) $row['total_production'] : 0;

        // New variables (assuming these are calculated or extracted similarly)
        $gen_one = isset($row['gen_one']) ? (float) $row['gen_one'] : 0;
        $gen_two = isset($row['gen_two']) ? (float) $row['gen_two'] : 0;
        $kvarh_exp = isset($row['kvarh_exp']) ? (float) $row['kvarh_exp'] : 0;
        $gen_onehrs = isset($row['gen_onehrs']) ? (float) $row['gen_onehrs'] : 0;
        $gen_twohrs = isset($row['gen_twohrs']) ? (float) $row['gen_twohrs'] : 0;
        $gen_hourtotal = isset($row['gen_hourtotal']) ? (float) $row['gen_hourtotal'] : 0;

        // Convert the calculated values to hours and store them in the row
        $row['error_overtotal'] = $error_overtotal . ':00';
        $row['gridfault_overtotal'] = $gridfault_overtotal . ':00';
        $row['griddrop_overtotal'] = $griddrop_overtotal . ':00';
        $row['maintenance_overtotal'] = $maintenance_overtotal . ':00';
        $row['kvarh_imp'] = $kvarh_imp;
        $row['kwh_exp'] = $kwh_exp;
        $row['kwh_imp'] = $kwh_imp;
        $row['total_production'] = $total_production;
        $row['kvarh_exp'] = $kvarh_exp . ':00';
        $row['total_hours'] = $total_hours . ':00';
        $row['gen_one'] = $gen_one;
        $row['gen_two'] = $gen_two;
        $row['kvarh_exp'] = $kvarh_exp;
        $row['gen_onehrs'] = $gen_onehrs . ':00';
        $row['gen_twohrs'] = $gen_twohrs . ':00';
        $row['gen_hourtotal'] = $gen_hourtotal . ':00';


        // Calculate turbine_ok_hrs
        $turbine_ok_hrs = $total_hours - ($error_overtotal + $griddrop_overtotal + $gridfault_overtotal);
        $row['turbine_ok_hrs'] = $turbine_ok_hrs . '0:00';
        $net_exp = $kwh_exp - $kwh_imp;
        $row['net_exp'] = $net_exp;
        $maintenance = ($turbine_ok_hrs != 0)
            ? round(($total_production / $turbine_ok_hrs) * $maintenance_overtotal, 2)
            : 0;

        $breakdown = ($turbine_ok_hrs != 0)
            ? round(($total_production / $turbine_ok_hrs) * $error_overtotal, 2)
            : 0;

        $grid_fault = ($turbine_ok_hrs != 0)
            ? round(($total_production / $turbine_ok_hrs) * $gridfault_overtotal, 2)
            : 0;

        $grid_down = ($turbine_ok_hrs != 0)
            ? round(($total_production / $turbine_ok_hrs) * $griddrop_overtotal, 2)
            : 0;

        // Convert breakdown and maintenance to 'HH:MM' format
        $row['breakdown'] = $breakdown;
        $row['Maintenance'] = $maintenance;
        $row['grid_fault'] = $grid_fault;
        $row['grid_down'] = $grid_down;


        // Calculate power factor
        $row['power_factor'] = ($kwh_exp != 0 || $kvarh_imp != 0)
            ? number_format($kwh_exp / sqrt(pow($kwh_exp, 2) + pow($kvarh_imp, 2)), 2)
            : '0.00';

        $breakdown_per = ($turbine_ok_hrs != 0) ? round(($breakdown * 100), 2) : '0.00';
        $maintenance_per = ($turbine_ok_hrs != 0) ? round(($maintenance * 100), 2) : '0.00';
        $grid_fault_per = ($turbine_ok_hrs != 0) ? round(($grid_fault * 100), 2) : '0.00';
        $grid_down_per = ($turbine_ok_hrs != 0) ? round(($grid_down * 100), 2) : '0.00';
        $row['breakdown_per'] = ($turbine_ok_hrs != 0)
            ? round(($breakdown * 100), 2)
            : 0;

        $row['maintenance_per'] = ($turbine_ok_hrs != 0)
            ? round(($maintenance * 100), 2)
            : '0.00';

        $row['grid_fault_per'] = ($turbine_ok_hrs != 0)
            ? round(($grid_fault * 100), 2)
            : '0.00';

        $row['grid_down_per'] = ($turbine_ok_hrs != 0)
            ? round(($grid_down * 100), 2)
            : '0.00';

        $row['breakdown_per'] = ($breakdown_per);
        $row['maintenance_per'] = ($maintenance_per);
        $row['grid_fault_per'] = ($grid_fault_per);
        $row['grid_down_per'] = ($grid_down_per);
        // Calculate machine availability
        $row['machine_availability'] = ($total_hours != 0 && ($total_hours - ($gridfault_overtotal + $griddrop_overtotal)) != 0)
            ? number_format((($total_hours - ($gridfault_overtotal + $griddrop_overtotal + $error_overtotal)) /
                ($total_hours - ($gridfault_overtotal + $griddrop_overtotal))) * 100, 2)
            : '0.00';

        // Calculate grid availability
        $grid_availability = ($total_hours != 0)
            ? (($total_hours - ($gridfault_overtotal + $griddrop_overtotal)) / $total_hours) * 100
            : 0;

        $row['grid_availability'] = number_format($grid_availability, 2);

        // Calculate diff between panel and EB
        $diff_panel_vs_eb = ($gen_one + $gen_two != 0)
            ? ($kwh_exp - ($gen_one + $gen_two))
            : 0;

        $row['diff_panel_vs_eb'] = $diff_panel_vs_eb;

        $row['diff_panel_eb_per'] = ($gen_one + $gen_two != 0)
            ? number_format(($diff_panel_vs_eb / ($gen_one + $gen_two)) * 100, 2)
            : '0.00';

        // Calculate generation grid percentage
        $row['generation_grid'] = ($total_production != 0 && $grid_availability != 0)
            ? number_format(($total_production / $grid_availability) * 100, 2)
            : '0.00';

        // Calculate kvarh percentages
        $row['kvarh_imp_per'] = ($kwh_exp != 0 && $kvarh_imp != 0)
            ? number_format(($kvarh_imp / $kwh_exp) * 100, 2)
            : '0.00';

        $row['kvarh_exp_per'] = ($kwh_exp != 0 && $kvarh_exp != 0)
            ? number_format(($kvarh_exp / $kwh_exp) * 100, 2)
            : '0.00';

        // Calculate lull hours
        $lull_hrs = ($turbine_ok_hrs != 0)
            ? ($turbine_ok_hrs - ($gen_onehrs + $gen_twohrs + timeToDecimal($maintenance_overtotal)))
            : 0;

        $row['lull_hrs'] = decimalToTime($lull_hrs);

        // Calculate $line_hrs
        $row['line_hrs'] = ($total_hours != 0)
            ? number_format($total_hours - $griddrop_overtotal, 2)
            : '0.00';

        // Define $line_hrs variable if not already defined
        $line_hrs = isset($row['line_hrs']) ? $row['line_hrs'] : '0.00';
        $row['line_hrs'] = decimalToTime($line_hrs);

        // Calculate $row['line_ok_hrs']
        $row['line_ok_hrs'] = ($total_hours != 0)
            ? number_format(max($total_hours - $line_hrs, 0), 2)
            : '0.00';
        $line_ok_hrs = isset($row['line_ok_hrs']) ? $row['line_ok_hrs'] : '0.00';

        $row['line_ok_hrs'] = decimalToTime($line_ok_hrs);


        // Calculate rating
        $row['rating'] = ($total_production != 0 && $gen_hourtotal != 0)
            ? number_format($total_production / $gen_hourtotal, 2)
            : 0;

        // Calculate availability impact
        $row['availablity_impact'] = (($total_hours - ($gridfault_overtotal + $griddrop_overtotal)) != 0)
            ? number_format(($error_overtotal / ($total_hours - ($gridfault_overtotal + $griddrop_overtotal))) * 100, 2)
            : '0.00';
        $row['contact'] = isset($row['contact']) ? json_decode($row['contact']) : null;
        $row['ship_address'] = isset($row['ship_address']) ? json_decode($row['ship_address']) : null;
        $row['errorcode'] = isset($row['errorcode']) ? json_decode($row['errorcode']) : null;
        $row['errormaintenance'] = isset($row['errormaintenance']) ? json_decode($row['errormaintenance']) : null;
        $row['errorgridfault'] = isset($row['errorgridfault']) ? json_decode($row['errorgridfault']) : null;
        $row['errorgriddrop'] = isset($row['errorgriddrop']) ? json_decode($row['errorgriddrop']) : null;
        $data[] = $row;
    }



    $output["status"] = 200;
    $output["data"] = $data;
    echo json_encode($output, JSON_NUMERIC_CHECK);

} else {
    $output["status"] = 400;
    $output["msg"] = "Invalid inputs";
    echo json_encode($output, JSON_NUMERIC_CHECK);
    exit;
}

// Close database connection
$conn->close();
function decimalToTime($decimal)
{
    $hours = floor($decimal);
    $minutes = floor(($decimal - $hours) * 60);
    $seconds = round((((($decimal - $hours) * 60) - $minutes) * 60));

    return sprintf("%02d:%02d", $hours, $minutes);
}

function timeToDecimal($time)
{
    // Check if the time format is correct
    if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $time)) {
        list($hours, $minutes, $seconds) = explode(':', $time);
        return $hours + ($minutes / 60) + ($seconds / 3600);
    } else {
        // Handle incorrect format
        return 0;
    }
}

function quote($value)
{
    global $conn;
    return "'" . $conn->real_escape_string($value) . "'";
}
?>