<?php

include("../../config/db_config.php");
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$output = array();
date_default_timezone_set('Asia/Calcutta');
$today_date = date('Y-m-d');
$data = json_decode(file_get_contents("php://input"));

$daily_data = [];  // Initialize array
$all_wtg_data = []; // Initialize array


if (isset($data->user_id)) {
    $user_id = $data->user_id;
    $filter_date_input = isset($data->filter_date) && !empty($data->filter_date) ? $data->filter_date : date('Y-m-d');
    
    // Validate the filter_date format and ensure it's a real date
    $date = DateTime::createFromFormat('Y-m-d', $filter_date_input);
    $filter_date = DateTime::createFromFormat('d-m-Y', $filter_date_input) ? date('Y-m-d', strtotime($filter_date_input)) : $filter_date_input;

    // Check if the filter_date is in 'd-m-Y' format and convert it to 'Y-m-d'
    if (DateTime::createFromFormat('d-m-Y', $filter_date_input)) {
        $filter_date = date('Y-m-d', strtotime($filter_date_input));
    } else {
        $filter_date = $filter_date_input;
    }

    if (!$filter_date) {
        $output["status"] = 400;
        $output["msg"] = "Invalid filter date.";
        echo json_encode($output, JSON_NUMERIC_CHECK);
        exit;
    }

    // Format the date as 'M-Y' (e.g., Nov-2024)
    $formatted_month_year = date('M-Y', strtotime($filter_date));

    // Check user in customer_group or customer table
    $group_condition = null;
    $sql_check_group = "SELECT * FROM customer_group WHERE BINARY customergroup_uniq_id = ? AND delete_at = '0'";
    $stmt_check_group = $conn->prepare($sql_check_group);
    $stmt_check_group->bind_param("s", $user_id);
    $stmt_check_group->execute();
    $result_check_group = $stmt_check_group->get_result();

    if ($result_check_group->num_rows > 0) {
        $group_condition = "BINARY cg.customergroup_uniq_id = ?";
    } else {
        $sql_check_customer = "SELECT * FROM customer WHERE BINARY customer_unique_id = ? AND delete_at = '0'";
        $stmt_check_customer = $conn->prepare($sql_check_customer);
        $stmt_check_customer->bind_param("s", $user_id);
        $stmt_check_customer->execute();
        $result_check_customer = $stmt_check_customer->get_result();

        if ($result_check_customer->num_rows > 0) {
            $group_condition = "BINARY c.customer_unique_id = ?";
        } else {
            $output["status"] = 400;
            $output["msg"] = "No Data";
            echo json_encode($output, JSON_NUMERIC_CHECK);
            exit;
        }
    }

    $most_recent_date = null;
    $total_hours = 24; // Assuming 24 hours as default total hours per day
    $total_production_sum = 0;
    $total_production_dailysum = 0; // Initialize sum of total production
    $daily_data = [];
    $filter_date_found = false;
    $processed_turbines = [];
    

    $sql = "
    SELECT c.*, cg.*, t.wtg_no, t.loc_no, t.turbine_id, 
           dg.total_production, dg.dg_date, dg.gridfault_overtotal, 
           dg.griddrop_overtotal, dg.error_overtotal
    FROM customer c
    INNER JOIN customer_group cg 
        ON c.customergroupname_id COLLATE utf8mb4_general_ci = cg.customergroup_uniq_id COLLATE utf8mb4_general_ci
        AND cg.delete_at = 0
    LEFT JOIN turbine t 
        ON t.customer_id COLLATE utf8mb4_general_ci = c.customer_unique_id COLLATE utf8mb4_general_ci
        AND t.delete_at = 0
    LEFT JOIN daily_generation dg 
        ON t.turbine_id COLLATE utf8mb4_general_ci = dg.turbine_id COLLATE utf8mb4_general_ci
        AND dg.delete_at = 0
    WHERE $group_condition
    AND c.delete_at = 0  -- Condition for customer table
    AND dg.dg_date = (
        SELECT MAX(dg_inner.dg_date)
        FROM daily_generation dg_inner
        WHERE dg_inner.turbine_id = t.turbine_id
        AND dg_inner.dg_date <= ?
        AND dg_inner.delete_at = 0
    )
    GROUP BY t.wtg_no
    ORDER BY dg.dg_date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $user_id, $filter_date);  // Bind user_id and filter_date
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $data = [];
    $most_recent_date = null;

    // Keeping the rest of the logic the same
    while ($row = $result->fetch_assoc()) {
        // Convert strings to numbers for calculations
        $gridfault_overtotal = (float)$row['gridfault_overtotal'];
        $griddrop_overtotal = (float)$row['griddrop_overtotal'];
        $error_overtotal = (float)$row['error_overtotal'];

        if ($most_recent_date === null) {
            $most_recent_date = $row['dg_date']; // Capture the most recent date
        }
        
        // Check if this row's date matches the most recent date
        if ($row['dg_date'] === $most_recent_date) {
            // Perform calculations even if total_production is zero
            $total_production = (float)$row['total_production'];
            
            // Machine availability calculation
            $row['machine_availability'] = ($total_hours != 0 && ($total_hours - ($gridfault_overtotal + $griddrop_overtotal)) != 0)
                ? number_format((($total_hours - ($gridfault_overtotal + $griddrop_overtotal + $error_overtotal)) / 
                    ($total_hours - ($gridfault_overtotal + $griddrop_overtotal))) * 100, 2)
                : '0.00';

            // Calculate grid availability
            $grid_availability = ($total_hours != 0)
                ? (($total_hours - ($gridfault_overtotal + $griddrop_overtotal)) / $total_hours) * 100
                : 0;

            $row['grid_availability'] = number_format($grid_availability, 2);

            // Add to total production daily sum if total_production is not zero
            if ($total_production != 0) {
                $total_production_dailysum += $total_production;
            }
            
            // Always add the row to the daily_data, regardless of total_production
            $daily_data[] = $row;
        } else {
            // For rows with different dates, set production and availability to 0
            $row['total_production'] = 0; 
            $row['machine_availability'] = '0.00'; 
            $row['grid_availability'] = '0.00'; 
            $daily_data[] = $row;
        }
    }    
}


// Assuming $conn is your database connection and $user_id and $filter_date are already defined
// $sql_daily = "
//     SELECT c.*, cg.*, t.wtg_no, t.loc_no, t.turbine_id, dg.total_production, dg.dg_date,
//        dg.gridfault_overtotal, dg.griddrop_overtotal, dg.error_overtotal
//     FROM customer c
//     INNER JOIN customer_group cg 
//         ON c.customergroupname_id COLLATE utf8mb4_general_ci = cg.customergroup_uniq_id COLLATE utf8mb4_general_ci
//     LEFT JOIN turbine t 
//         ON t.customer_id COLLATE utf8mb4_general_ci = c.customer_unique_id COLLATE utf8mb4_general_ci
//     LEFT JOIN daily_generation dg 
//         ON t.turbine_id COLLATE utf8mb4_general_ci = dg.turbine_id COLLATE utf8mb4_general_ci
//     WHERE $group_condition 
//       AND c.delete_at = '0'
//       AND cg.delete_at = '0'
//       AND t.delete_at = '0'
//       AND dg.delete_at = '0'
//       AND dg.dg_date = (
//           SELECT MAX(dg2.dg_date)
//           FROM daily_generation dg2
//           WHERE dg2.turbine_id COLLATE utf8mb4_general_ci = t.turbine_id COLLATE utf8mb4_general_ci
//             AND dg2.dg_date <= ? 
//             AND dg.delete_at = '0'
//       )
//     ORDER BY t.wtg_no, dg.dg_date ASC;
// ";

// $stmt_daily = $conn->prepare($sql_daily);
// $stmt_daily->bind_param("ss", $user_id, $filter_date); // Ensure consistent date format
// $stmt_daily->execute();
// $result_daily = $stmt_daily->get_result();


// if ($result_daily->num_rows > 0) {
//     while ($row = $result_daily->fetch_assoc()) {
//         // Extract necessary data from the row
//         $dg_date = isset($row['dg_date']) ? date('Y-m-d', strtotime($row['dg_date'])) : null;
//         $total_production = floatval($row['total_production'] ?? 0);
//         $turbine_id = $row['wtg_no'];

//         // Ensure the turbine is only processed once
//         if (in_array($turbine_id, $processed_turbines)) {
//             continue;
//         }
//         $processed_turbines[] = $turbine_id;

//         // Check if this is the most recent date
//         if ($most_recent_date === null || $dg_date > $most_recent_date) {
//             $most_recent_date = $dg_date;
//         }

//         // Initialize grid fault, drop, and error values
//         $gridfault_overtotal_decimal = !empty($row['gridfault_overtotal']) ? floatval($row['gridfault_overtotal']) : 0;
//         $griddrop_overtotal_decimal = !empty($row['griddrop_overtotal']) ? floatval($row['griddrop_overtotal']) : 0;
//         $error_overtotal_decimal = !empty($row['error_overtotal']) ? floatval($row['error_overtotal']) : 0;

//         // If the date is not the most recent one, set total production to 0
//         if ($dg_date !== $most_recent_date) {
//             $total_production = 0;
//             $row['machine_availability'] = 0;
//             $row['grid_availability'] = 0;
//         } else {
//             if ($total_production > 0 && $dg_date === $most_recent_date) {
//                 // Ensure that total_hours_decimal is non-zero
//                 if ($total_hours_decimal > 0) {
//                     // Calculate availability percentages
//                     $row['machine_availability'] = number_format((($total_hours_decimal - ($gridfault_overtotal_decimal + $griddrop_overtotal_decimal + $error_overtotal_decimal)) / $total_hours_decimal) * 100, 2);
//                     $row['grid_availability'] = number_format((($total_hours_decimal - ($gridfault_overtotal_decimal + $griddrop_overtotal_decimal)) / $total_hours_decimal) * 100, 2);
//                 } else {
//                     $row['machine_availability'] = 0;
//                     $row['grid_availability'] = 0;
//                 }
//             }
//         }

//         // Update the production for the row
//         $row['total_production'] = $total_production;
//         $row['machine_availability'] = isset($row['machine_availability']) ? $row['machine_availability'] : 0;
//         $row['grid_availability'] = isset($row['grid_availability']) ? $row['grid_availability'] : 0;

//         // Add the total production for this turbine to the sum
//         $total_production_dailysum += $total_production;

//         // Store the row in the daily data array
//         $daily_data[] = $row;
//     }

//     // Handle case where no matching filter_date is found
//     if (!$filter_date_found) {
//         foreach ($daily_data as &$data) {
//             if ($data['dg_date'] === $most_recent_date) {
//                 if ($total_hours_decimal > 0 && $data['total_production'] > 0) {
//                     $data['machine_availability'] = number_format((($total_hours_decimal - ($gridfault_overtotal_decimal + $griddrop_overtotal_decimal + $error_overtotal_decimal)) / $total_hours_decimal) * 100, 2);
//                     $data['grid_availability'] = number_format((($total_hours_decimal - ($gridfault_overtotal_decimal + $griddrop_overtotal_decimal)) / $total_hours_decimal) * 100, 2);
//                 } else {
//                     $data['machine_availability'] = 0;
//                     $data['grid_availability'] = 0;
//                 }
//                 $data['total_production'] = floatval($data['total_production']);
//             } else {
//                 $data['total_production'] = 0;
//                 $data['machine_availability'] = 0;
//                 $data['grid_availability'] = 0;
//             }
//         }
//     }
// }

    





// Step 1: Get the filter_date and convert it to 'Y-m-d' format for the exact date
$start_date = date('Y-m-01', strtotime($filter_date));  // First day of the month
$end_date = date('Y-m-d', strtotime($filter_date));     // End date up to filter_date

// Set the character set for the connection
$conn->set_charset("utf8mb4");

// Capitalize the first letter of the month part
// Step 2: Check if data exists for the provided filter_date month
$month_year = strtolower(date('M-Y', strtotime($filter_date)));  // Format as dec-2024
$month_year = ucwords($month_year);

$sql_check_month = "
SELECT 1 
FROM daily_generation dg
LEFT JOIN turbine t ON dg.turbine_id = t.turbine_id 
    AND t.delete_at = '0'  -- Ensure turbine is not deleted
LEFT JOIN customer c ON t.customer_id = c.customer_unique_id COLLATE utf8mb4_general_ci 
    AND c.delete_at = '0'  -- Ensure customer is not deleted
WHERE c.customer_unique_id COLLATE utf8mb4_general_ci = ? 
  AND dg.dg_date BETWEEN ? AND ?
  AND dg.delete_at = '0'  -- Ensure daily_generation is not deleted
LIMIT 1;
";

$stmt_check_month = $conn->prepare($sql_check_month);
$stmt_check_month->bind_param("sss", $user_id, $start_date, $end_date);
$stmt_check_month->execute();
$stmt_check_month->store_result();

// Step 3: If no data exists for the provided month, find the most recent month with data
if ($stmt_check_month->num_rows == 0) {
    // No data for the provided date range, fetch the most recent month with data
    $sql_recent_month = "
    SELECT DATE_FORMAT(dg.dg_date, '%Y-%m') AS recent_month
    FROM daily_generation dg
    LEFT JOIN turbine t ON dg.turbine_id = t.turbine_id 
        AND t.delete_at = '0'  -- Ensure turbine is not deleted
    LEFT JOIN customer c ON t.customer_id = c.customer_unique_id COLLATE utf8mb4_general_ci 
        AND c.delete_at = '0'  -- Ensure customer is not deleted
    WHERE c.customer_unique_id COLLATE utf8mb4_general_ci = ? 
      AND dg.delete_at = '0'  -- Ensure daily_generation is not deleted
    ORDER BY dg.dg_date DESC
    LIMIT 1;
    ";

    $stmt_recent_month = $conn->prepare($sql_recent_month);
    $stmt_recent_month->bind_param("s", $user_id);
    $stmt_recent_month->execute();
    $result_recent_month = $stmt_recent_month->get_result();
    $row_recent_month = $result_recent_month->fetch_assoc();
    $stmt_recent_month->close();

    if (!empty($row_recent_month['recent_month'])) {
        // Convert most recent month with first letter in caps
        $month_year = strtolower(date('M-Y', strtotime($row_recent_month['recent_month'])));
        $month_year = ucwords($month_year);  // Capitalize the first letter of the month

        // Update $start_date and $end_date to reflect the most recent month with data
        $start_date = date('Y-m-01', strtotime($row_recent_month['recent_month']));
        $end_date = date('Y-m-t', strtotime($row_recent_month['recent_month']));
    } else {
        // No data at all, handle this case (return or show a message)
        echo "No data available for the provided month or any recent month.";
        exit;
    }
}

$stmt_check_month->close();

// Step 4: Adjust SQL query to retrieve only data for the selected or most recent month up to the specified day
$sql_all_wtg_no = "
SELECT c.*, cg.*, t.wtg_no, t.loc_no, dg.dg_date,
    SUM(CASE WHEN dg.dg_date BETWEEN ? AND ? THEN dg.total_production ELSE 0 END) AS total_production,
    DATE_FORMAT(dg.dg_date, '%Y-%m') AS month,
    SUM(dg.gridfault_overtotal) AS gridfault_overtotal,
    SUM(dg.griddrop_overtotal) AS griddrop_overtotal,
    SUM(dg.error_overtotal) AS error_overtotal
FROM customer c 
INNER JOIN customer_group cg 
    ON c.customergroupname_id COLLATE utf8mb4_general_ci = cg.customergroup_uniq_id COLLATE utf8mb4_general_ci
    AND cg.delete_at = '0'  -- Ensure customer_group is not deleted
LEFT JOIN turbine t 
    ON t.customer_id COLLATE utf8mb4_general_ci = c.customer_unique_id COLLATE utf8mb4_general_ci
    AND t.delete_at = '0'  -- Ensure turbine is not deleted
LEFT JOIN daily_generation dg 
    ON t.turbine_id = dg.turbine_id 
    AND dg.dg_date BETWEEN ? AND ?
    AND dg.delete_at = '0'  -- Ensure daily_generation is not deleted
WHERE c.customer_unique_id COLLATE utf8mb4_general_ci = ? 
  AND c.delete_at = '0'  -- Ensure customer is not deleted
GROUP BY t.wtg_no
ORDER BY month ASC;
";

// Prepare and execute the query
$stmt_all_wtg_no = $conn->prepare($sql_all_wtg_no);
$stmt_all_wtg_no->bind_param("sssss", $start_date, $end_date, $start_date, $end_date, $user_id);
$stmt_all_wtg_no->execute();
$result_all_wtg_no = $stmt_all_wtg_no->get_result();

// Initialize the array to hold the turbine data and the total production sum
$all_wtg_data = [];
$total_production_monthlysum = 0;

if ($result_all_wtg_no->num_rows > 0) {
    while ($row = $result_all_wtg_no->fetch_assoc()) {
        // Accumulate total production only for the target range of dates
        if ($row['month'] == date('Y-m', strtotime($month_year))) {
            $total_production_monthlysum += $row['total_production'];
        }

        // Collect the turbine data
        $all_wtg_data[] = $row;
    }
} else {
    echo "No data available for the selected date range.";
}

$stmt_all_wtg_no->close();





// Initialize current year and other variables
$current_year = date('Y');
$start_date = null;  // Initialize the start date
$end_date = date('Y-m-d');  // Default end date is today

// Get the current month
$current_month = date('m');

// Adjust current year for April to April fiscal year
if ($current_month < 4) {
    $current_year -= 1;  // If the current month is before April, the fiscal year starts from the previous year
}

// Check if filter_date is provided
if (!empty($filter_date_input)) {
    // Parse the filter date from the input
    $filter_date = DateTime::createFromFormat('Y-m-d', $filter_date_input);
    if ($filter_date) {
        $current_year = $filter_date->format('Y');  // Use the year from the filter date
        if ($filter_date->format('m') < 4) {
            $current_year -= 1;  // Adjust the year if the filter date is before April
        }
        $start_date = "{$current_year}-04-01";  // Start from April 1st of the filter date's year
        $end_date = $filter_date->format('Y-m-d');  // Set the end date to the filter date
    } else {
        // Return an error if the filter date format is invalid
        $output["status"] = 400;
        $output["msg"] = "Invalid date format. Use yyyy-mm-dd.";
        echo json_encode($output);
        exit;
    }
} else {
    // If no filter date provided, default to current fiscal year (April to April)
    $start_date = "{$current_year}-04-01";  // Default to April 1st of the current fiscal year
}

// Calculate the next year (used in SQL query if necessary)
$next_year = $current_year + 1;

// Check if there's a customer group (check `result_check_group`)
if ($result_check_group->num_rows > 0) {
    // Query for customer group, using a date range between start and end dates
    $sql = "SELECT c.*, cg.*, t.wtg_no, t.loc_no, SUM(dg.total_production) AS total_production
FROM customer c 
INNER JOIN customer_group cg 
    ON BINARY c.customergroupname_id = BINARY cg.customergroup_uniq_id 
    AND cg.delete_at = '0'  -- Ensure customer_group is not deleted
LEFT JOIN turbine t 
    ON t.customer_id = c.customer_unique_id 
    AND t.delete_at = '0'  -- Ensure turbine is not deleted
LEFT JOIN daily_generation dg 
    ON dg.turbine_id = t.turbine_id 
    AND dg.dg_date BETWEEN ? AND ?
    AND dg.delete_at = '0'  -- Ensure daily_generation is not deleted
WHERE BINARY cg.customergroup_uniq_id = ? 
  AND c.delete_at = '0'  -- Ensure customer is not deleted
GROUP BY t.wtg_no;
";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $start_date, $end_date, $user_id);  // Bind both start_date and end_date
} else {
    // Check if the user_id exists in the customer table
    $sql_check_customer = "SELECT * FROM customer WHERE BINARY customer_unique_id = ? AND delete_at = '0'";
    $stmt_check_customer = $conn->prepare($sql_check_customer);
    $stmt_check_customer->bind_param("s", $user_id);
    $stmt_check_customer->execute();
    $result_check_customer = $stmt_check_customer->get_result();

    if ($result_check_customer->num_rows > 0) {
        // Fetch customer data directly if user_id is in the customer table
        $sql = "SELECT c.customer_name, t.wtg_no, t.loc_no, SUM(dg.total_production) AS total_production
FROM customer c
LEFT JOIN turbine t 
    ON t.customer_id = c.customer_unique_id 
    AND t.delete_at = '0'  -- Ensure turbine is not deleted
LEFT JOIN daily_generation dg 
    ON dg.turbine_id = t.turbine_id 
    AND dg.dg_date BETWEEN ? AND ?  -- Date range filter
    AND dg.delete_at = '0'  -- Ensure daily_generation is not deleted
WHERE BINARY c.customer_unique_id = ? 
  AND c.delete_at = '0'  -- Ensure customer is not deleted
GROUP BY t.wtg_no;
";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $start_date, $end_date, $user_id);  // Bind both start_date and end_date
    } else {
        // Return an error if no customer data is found
        $output["status"] = 400;
        $output["msg"] = "No Data";
        $output["data"] = [];
        echo json_encode($output, JSON_NUMERIC_CHECK);
        exit;
    }
}

// Execute the query
$stmt->execute();
$sqlresult = $stmt->get_result();

// Initialize variables for processing results
$customer_data = array();
$total_production_sum = 0;  // Initialize the total production sum

// Process the result set
while ($row = $sqlresult->fetch_assoc()) {
    $customer_name = $row['customer_name'];

    // Accumulate the total production sum
    if (isset($row['total_production'])) {
        $total_production_sum += $row['total_production'];
    }

    // Group by customer if requested
    if (isset($data->group_by_customer) && $data->group_by_customer) {
        if (!isset($customer_data[$customer_name])) {
            $customer_data[$customer_name] = $row;
            $customer_data[$customer_name]['wtg_no'] = array();
        }
        if (isset($row['wtg_no'])) {
            $customer_data[$customer_name]['wtg_no'][] = $row['wtg_no'];
        }
    } else {
        // Otherwise, output the data directly
        $output["data"][] = $row;
    }
}

// If grouped by customer, reformat the data into a customer-specific structure
if (isset($data->group_by_customer) && $data->group_by_customer) {
    $output["data"] = array_values($customer_data);
}

// Ensure $daily_data is defined if used
if (isset($daily_data)) {
    $output["daily_data"] = $daily_data;
} else {
    $output["daily_data"] = []; // Default value if daily_data is not defined
}

// Prepare output data
$output = [
    'filter_date' => $filter_date,
    'most_recent_date' => $most_recent_date,
    "month"=>$month_year,
    "daily_production"=>$total_production_dailysum,
    "monthly_production"=>$total_production_monthlysum,
    "yearly_production" => $total_production_sum,
    "current_year" => $current_year . '-' . $next_year,
    "status" => 200,
    "msg" => "success",
    "today_date" => date('Y-m-d'), // Define today_date
    "daily_data" => $daily_data,
    "monthly" => $all_wtg_data, // Result data
];

} else {
    $output["status"] = 400;
    $output["msg"] = "User ID is required.";
}
echo json_encode($output, JSON_NUMERIC_CHECK);