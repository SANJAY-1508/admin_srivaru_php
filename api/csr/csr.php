<?php
include("../config/db_config.php");
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit();
}

$json = file_get_contents('php://input');
error_log("Raw JSON input: " . $json);
$obj = json_decode($json, true);
$output = array();
date_default_timezone_set('Asia/Calcutta');
$timestamp = date('Y-m-d H:i:s');

// <<<<<<<<<<===================== List CSRs =====================>>>>>>>>>>
if (isset($obj['get_users'])) {
    $search_text = $conn->real_escape_string($obj['search_text'] ?? '');
    $sql = "SELECT `id`, `user_id`, `date_of_joining`, `user_name`, `mobile_number`, 
                   `address`, `date_of_birth`, `role_id`, `login_id`, `password`, 
                   `profile_image`, `sign_image`, `delete_at`, `created_date`
            FROM `users`
            WHERE `delete_at` = 0
            AND (`user_name` LIKE '%$search_text%' OR `mobile_number` LIKE '%$search_text%')
            ORDER BY `id` ASC";

    $result = $conn->query($sql);
    if ($result === false) {
        $output["head"]["code"] = 500;
        $output["head"]["msg"] = "Database error";
        echo json_encode($output, JSON_NUMERIC_CHECK);
        exit();
    }

    $output["body"]["users"] = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output["body"]["users"][] = $row;
        }
        $output["head"]["code"] = 200;
        $output["head"]["msg"] = "Success";
    } else {
        $output["head"]["code"] = 200;
        $output["head"]["msg"] = "No records found";
        $output["body"]["users"] = [];
    }
} elseif (isset($obj['search_text'])) {
    $search_text = $conn->real_escape_string($obj['search_text']);
    $search_terms = array_filter(array_map('trim', explode(' ', $search_text)));

    $conditions = [];
    foreach ($search_terms as $term) {
        $term = $conn->real_escape_string($term);
        $conditions[] = "(`weg_no` LIKE '%$term%' OR `csr_no` LIKE '%$term%' OR `csr_type` LIKE '%$term%' OR `csr_booked_by` LIKE '%$term%')";
    }

    $where_clause = !empty($conditions) ? '(' . implode(' AND ', $conditions) . ')' : '1=1';

    $sql = "SELECT *
            FROM `csr`
            WHERE `delete_at` = 0
            AND $where_clause
            ORDER BY `id` ASC";

    error_log("SQL query: " . $sql);

    $result = $conn->query($sql);
    if ($result === false) {
        $output["head"]["code"] = 500;
        $output["head"]["msg"] = "Database error: " . $conn->error;
        echo json_encode($output, JSON_NUMERIC_CHECK);
        exit();
    }

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output["head"]["code"] = 200;
            $output["head"]["msg"] = "Success";
            $output["body"]["csrs"][] = $row;
        }
    } else {
        $output["head"]["code"] = 200;
        $output["head"]["msg"] = "No records found";
        $output["body"]["csrs"] = [];
    }
}
// <<<<<<<<<<===================== Create CSR =====================>>>>>>>>>>
elseif (isset($obj['csr_no']) && !isset($obj['edit_csr_id'])) {
    $weg_no = isset($obj['weg_no']) ? $conn->real_escape_string($obj['weg_no']) : '';
    $csr_type = isset($obj['csr_type']) ? $conn->real_escape_string($obj['csr_type']) : '';
    $system_down = isset($obj['system_down']) ? (int)$obj['system_down'] : 0;
    $csr_no = isset($obj['csr_no']) ? $conn->real_escape_string($obj['csr_no']) : '';
    $csr_date = isset($obj['csr_date']) ? $conn->real_escape_string($obj['csr_date']) : '';
    $csr_booked_by = isset($obj['csr_booked_by']) ? $conn->real_escape_string($obj['csr_booked_by']) : '';
    $customer_id = isset($obj['customer_id']) ? $conn->real_escape_string($obj['customer_id']) : '';
    $customer_name = isset($obj['customer_name']) ? $conn->real_escape_string($obj['customer_name']) : '';
    $contract_id = isset($obj['contract_id']) ? $conn->real_escape_string($obj['contract_id']) : '';
    $contract_type = isset($obj['contract_type']) ? $conn->real_escape_string($obj['contract_type']) : '';
    $loc_no = isset($obj['loc_no']) ? $conn->real_escape_string($obj['loc_no']) : '';
    $model_id = isset($obj['model_id']) ? $conn->real_escape_string($obj['model_id']) : '';
    $model_type = isset($obj['model_type']) ? $conn->real_escape_string($obj['model_type']) : '';
    $htsc_no = isset($obj['htsc_no']) ? $conn->real_escape_string($obj['htsc_no']) : '';
    $capacity = isset($obj['capacity']) ? $conn->real_escape_string($obj['capacity']) : '';
    $make = isset($obj['make']) ? $conn->real_escape_string($obj['make']) : '';
    $turbine_id = isset($obj['turbine_id']) ? $conn->real_escape_string($obj['turbine_id']) : '';
    $site_name = isset($obj['site_name']) ? $conn->real_escape_string($obj['site_name']) : '';
     $site_id = isset($obj['site_id']) ? $conn->real_escape_string($obj['site_id']) : '';

    // Validate required fields
    if (empty($weg_no) || empty($csr_type) || empty($csr_no) || empty($csr_date)) {
        $output["head"]["code"] = 400;
        $output["head"]["msg"] = "Please provide all required fields.";
        echo json_encode($output, JSON_NUMERIC_CHECK);
        exit();
    }

    // Check if csr_no already exists
    $sql = "SELECT `id` FROM `csr` WHERE `csr_no` = '$csr_no' AND `delete_at` = 0";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $output["head"]["code"] = 400;
        $output["head"]["msg"] = "CSR number already exists.";
        echo json_encode($output, JSON_NUMERIC_CHECK);
        exit();
    }

    $sql = "INSERT INTO `csr` (
        `weg_no`, `csr_type`, `system_down`, `csr_no`, `csr_date`, `csr_booked_by`, 
        `customer_id`, `customer_name`, `contract_id`, `contract_type`, `loc_no`, 
        `model_id`, `model_type`, `htsc_no`, `capacity`, `make`, `turbine_id`,`site_id`, `site_name`, 
        `create_at`, `delete_at`
    ) VALUES (
        '$weg_no', '$csr_type', $system_down, '$csr_no', '$csr_date', '$csr_booked_by', 
        '$customer_id', '$customer_name', '$contract_id', '$contract_type', '$loc_no', 
        '$model_id', '$model_type', '$htsc_no', '$capacity', '$make', '$turbine_id','$site_id', '$site_name', 
        '$timestamp', 0
    )";

    if ($conn->query($sql)) {
        $id = $conn->insert_id;
        $uniqueCsrID = uniqueID('csr', $id);
        $sql = "UPDATE `csr` SET `csr_id` = '$uniqueCsrID' WHERE `id` = $id";
        $conn->query($sql);

        $output["head"]["code"] = 200;
        $output["head"]["msg"] = "CSR added successfully";
        $output["body"] = [
            "id" => $id,
            "csr_id" => $uniqueCsrID,
            "weg_no" => $weg_no,
            "csr_type" => $csr_type,
            "system_down" => $system_down,
            "csr_no" => $csr_no,
            "csr_date" => $csr_date,
            "csr_booked_by" => $csr_booked_by,
            "customer_id" => $customer_id,
            "customer_name" => $customer_name,
            "contract_id" => $contract_id,
            "contract_type" => $contract_type,
            "loc_no" => $loc_no,
            "model_id" => $model_id,
            "model_type" => $model_type,
            "htsc_no" => $htsc_no,
            "capacity" => $capacity,
            "make" => $make,
            "turbine_id" => $turbine_id,
              "site_id" => $site_id,
            "site_name" => $site_name,
            "create_at" => $timestamp,
            "delete_at" => 0
        ];
    } else {
        $output["head"]["code"] = 400;
        $output["head"]["msg"] = "Failed to add. Please try again.";
        echo json_encode($output, JSON_NUMERIC_CHECK);
        exit();
    }
}
// <<<<<<<<<<===================== Update CSR =====================>>>>>>>>>>
elseif (isset($obj['edit_csr_id'])) {
    $edit_id = $conn->real_escape_string($obj['edit_csr_id']);
    $weg_no = $conn->real_escape_string($obj['weg_no'] ?? '');
    $csr_type = $conn->real_escape_string($obj['csr_type'] ?? '');
    $system_down = isset($obj['system_down']) ? (int)$obj['system_down'] : 0;
    $csr_no = $conn->real_escape_string($obj['csr_no'] ?? '');
    $csr_date = $conn->real_escape_string($obj['csr_date'] ?? '');
    $csr_booked_by = $conn->real_escape_string($obj['csr_booked_by'] ?? '');
    $customer_id = $conn->real_escape_string($obj['customer_id'] ?? '');
    $customer_name = $conn->real_escape_string($obj['customer_name'] ?? '');
    $contract_id = $conn->real_escape_string($obj['contract_id'] ?? '');
    $contract_type = $conn->real_escape_string($obj['contract_type'] ?? '');
    $loc_no = $conn->real_escape_string($obj['loc_no'] ?? '');
    $model_id = $conn->real_escape_string($obj['model_id'] ?? '');
    $model_type = $conn->real_escape_string($obj['model_type'] ?? '');
    $htsc_no = $conn->real_escape_string($obj['htsc_no'] ?? '');
    $capacity = $conn->real_escape_string($obj['capacity'] ?? '');
    $make = $conn->real_escape_string($obj['make'] ?? '');
    $turbine_id = $conn->real_escape_string($obj['turbine_id'] ?? '');
    $site_id = $conn->real_escape_string($obj['site_id'] ?? '');
    $site_name = $conn->real_escape_string($obj['site_name'] ?? '');

    // Validate required fields
    if (empty($weg_no) || empty($csr_type) || empty($csr_no) || empty($csr_date)) {
        $output["head"]["code"] = 400;
        $output["head"]["msg"] = "Please provide all required fields.";
        echo json_encode($output, JSON_NUMERIC_CHECK);
        exit();
    }

    // Check if csr_no exists in another record
    $sql = "SELECT `id` FROM `csr` WHERE `csr_no` = '$csr_no' AND `delete_at` = 0 AND `csr_id` != '$edit_id'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $output["head"]["code"] = 400;
        $output["head"]["msg"] = "CSR number already exists.";
        echo json_encode($output, JSON_NUMERIC_CHECK);
        exit();
    }

    $sql = "UPDATE `csr` SET 
        `weg_no`='$weg_no', `csr_type`='$csr_type', `system_down`=$system_down, `csr_no`='$csr_no', 
        `csr_date`='$csr_date', `csr_booked_by`='$csr_booked_by', `customer_id`='$customer_id', 
        `customer_name`='$customer_name', `contract_id`='$contract_id', `contract_type`='$contract_type', 
        `loc_no`='$loc_no', `model_id`='$model_id', `model_type`='$model_type', `htsc_no`='$htsc_no', 
        `capacity`='$capacity', `make`='$make', `turbine_id`='$turbine_id',`site_id`='$site_id', `site_name`='$site_name'
        WHERE `csr_id`='$edit_id'";

    if ($conn->query($sql)) {
        $output["head"]["code"] = 200;
        $output["head"]["msg"] = "CSR updated successfully";
        $output["head"]["data"] = $obj;
    } else {
        $output["head"]["code"] = 400;
        $output["head"]["msg"] = "Failed to update. Please try again.";
    }
}
// <<<<<<<<<<===================== Delete CSR =====================>>>>>>>>>>
elseif (isset($obj['delete_csr_id'])) {
    $delete_csr_id = $conn->real_escape_string($obj['delete_csr_id']);

    if (!empty($delete_csr_id)) {
        $sql = "UPDATE `csr` SET `delete_at` = 1 WHERE `csr_id` = '$delete_csr_id'";
        if ($conn->query($sql)) {
            $output["head"]["code"] = 200;
            $output["head"]["msg"] = "CSR deleted successfully";
        } else {
            $output["head"]["code"] = 400;
            $output["head"]["msg"] = "Failed to delete. Please try again.";
        }
    } else {
        $output["head"]["code"] = 400;
        $output["head"]["msg"] = "Please provide the CSR ID to delete.";
    }
}
// <<<<<<<<<<===================== Get Turbines =====================>>>>>>>>>>
elseif (isset($obj['get_turbines'])) {
    $sql = "SELECT t.wtg_no, s.short_code
            FROM turbine t
            LEFT JOIN site s ON t.site_id = s.site_id
            WHERE t.delete_at = 0 AND s.delete_at = 0
            ORDER BY s.short_code, t.wtg_no ASC";

    $result = $conn->query($sql);

    if ($result === false) {
        $output["head"]["code"] = 500;
        $output["head"]["msg"] = "Database error";
        echo json_encode($output, JSON_NUMERIC_CHECK);
        exit();
    }

    // Group data by short_code
    $groupedData = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $short_code = $row["short_code"] ?? "";
            if (!isset($groupedData[$short_code])) {
                $groupedData[$short_code] = [];
            }
            $groupedData[$short_code][] = $row["wtg_no"];
        }

        // Format output
        foreach ($groupedData as $short_code => $wtg_nos) {
            $output["head"]["code"] = 200;
            $output["head"]["msg"] = "Success";
            $output["body"]["data"][] = [
                "short_code" => $short_code,
                "wtg_no" => $wtg_nos
            ];
        }
    } else {
        $output["head"]["code"] = 200;
        $output["head"]["msg"] = "No records found";
        $output["body"]["data"] = [];
    }
}
// <<<<<<<<<<===================== Get Users =====================>>>>>>>>>>
else {
    $output["head"]["code"] = 400;
    $output["head"]["msg"] = "Parameter mismatch";
}

echo json_encode($output, JSON_NUMERIC_CHECK);