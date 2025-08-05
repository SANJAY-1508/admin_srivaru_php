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
$obj = json_decode($json, true);
$output = array();
date_default_timezone_set('Asia/Calcutta');
$timestamp = date('Y-m-d H:i:s');
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$domain = $_SERVER['HTTP_HOST'];
$base_url = $protocol . $domain;

function processImage($base64Data, $prefix)
{
    global $base_url;
    if (!preg_match('/^data:image\/(\w+);base64,/', $base64Data, $type)) {
        return ['error' => "Unsupported file type. Only images are allowed."];
    }
    $fileName = uniqid($prefix . "_") . "." . strtolower($type[1]);
    $filePath = "../Uploads/images/" . $fileName;
    $fileData = preg_replace('/^data:.*;base64,/', '', $base64Data);
    $decodedFile = base64_decode($fileData);
    if ($decodedFile === false) {
        return ['error' => "Base64 decoding failed."];
    }
    $directory = dirname($filePath);
    if (!is_dir($directory)) {
        mkdir($directory, 0777, true);
    }
    if (file_put_contents($filePath, $decodedFile) === false) {
        return ['error' => "Failed to save the image."];
    }
    $cleaned_path = str_replace('../', '', $filePath);
    return ['path' => $base_url . '/' . $cleaned_path];
}

// List CSR Entries
if (isset($obj['search_text']) && (!isset($obj['action']) || $obj['action'] !== 'list_error')) {
    $search_text = $conn->real_escape_string($obj['search_text']);
    $search_terms = array_filter(array_map('trim', explode(' ', $search_text)));

    $conditions = [];
    foreach ($search_terms as $term) {
        $term = $conn->real_escape_string($term);
        $conditions[] = "(csr_no LIKE '%$term%' OR customer_name LIKE '%$term%' OR wtg_no LIKE '%$term%' OR csr_type LIKE '%$term%' OR site_name LIKE '%$term%')";
    }

    $date_conditions = [];
    if (isset($obj['fromDate']) && !empty($obj['fromDate'])) {
        $fromDate = $conn->real_escape_string($obj['fromDate']);
        $date_conditions[] = "csr_entry_date >= '$fromDate'";
    }
    if (isset($obj['toDate']) && !empty($obj['toDate'])) {
        $toDate = $conn->real_escape_string($obj['toDate']);
        $date_conditions[] = "csr_entry_date <= '$toDate'";
    }

    $where_clause = !empty($conditions) ? implode(' AND ', $conditions) : '1=1';
    if (!empty($date_conditions)) {
        $where_clause .= ' AND ' . implode(' AND ', $date_conditions);
    }

    $query = "SELECT * FROM csr_entry 
              WHERE delete_at = 0 
              AND $where_clause
              ORDER BY id DESC";

    error_log("CSR Filter Query: $query");

    $result = $conn->query($query);
    if ($result === false) {
        error_log("CSR query failed: " . $conn->error);
        $output["head"]["code"] = 500;
        $output["head"]["msg"] = "Database error";
        echo json_encode($output, JSON_NUMERIC_CHECK);
        exit();
    }

    $entries = [];
    while ($row = $result->fetch_assoc()) {
        $row['parts_data'] = json_decode($row['parts_data'], true) ?? [];
        $row['error_details'] = json_decode($row['error_details'], true) ?? [];
        $row['employee_sign'] = !empty($row['employee_sign']) ? $base_url . '/' . str_replace('../', '', $row['employee_sign']) : '';
        $row['incharge_operator_sign'] = !empty($row['incharge_operator_sign']) ? $base_url . '/' . str_replace('../', '', $row['incharge_operator_sign']) : '';
        $entries[] = $row;
    }

    $output["head"]["code"] = 200;
    $output["head"]["msg"] = $result->num_rows > 0 ? "Success" : "No records found";
    $output["body"]["csr_entries"] = $entries;
}

// List Errors
elseif (isset($obj['action']) && $obj['action'] === 'list_error' && isset($obj['search_text'])) {
    $search_text = $conn->real_escape_string($obj['search_text']);
    $output["body"]["errors"] = [];

    $query = "SELECT * FROM `error` WHERE (`error_code` LIKE '%$search_text%' OR `error_describtion` LIKE '%$search_text%') AND `delete_at` = 0 ORDER BY id DESC";

    error_log("SQL query: " . $query);

    $result = mysqli_query($conn, $query);
    if (!$result) {
        error_log("Error listing errors: " . mysqli_error($conn));
        $output["head"]["code"] = 400;
        $output["head"]["msg"] = "Failed to retrieve errors. Please try again.";
        echo json_encode($output, JSON_NUMERIC_CHECK);
        exit();
    }

    while ($row = mysqli_fetch_assoc($result)) {
        $output["body"]["errors"][] = $row;
    }

    $output["head"]["code"] = 200;
    $output["head"]["msg"] = count($output["body"]["errors"]) > 0 ? "Success" : "No errors found";
    echo json_encode($output, JSON_NUMERIC_CHECK);
    exit();
}

// Create CSR Entry
elseif (isset($obj['csr_no']) && !isset($obj['edit_csr_entry_id'])) {
    $fields = [
        'csr_no' => $conn->real_escape_string($obj['csr_no'] ?? ''),
        'csr_entry_date' => $conn->real_escape_string($obj['csr_entry_date'] ?? ''),
        'customer_id' => $conn->real_escape_string($obj['customer_id'] ?? ''),
        'customer_name' => $conn->real_escape_string($obj['customer_name'] ?? ''),
        'contract_id' => $conn->real_escape_string($obj['contract_id'] ?? ''),
        'contract_type' => $conn->real_escape_string($obj['contract_type'] ?? ''),
        'error_details' => $conn->real_escape_string(json_encode($obj['error_details'] ?? [], JSON_UNESCAPED_SLASHES)),
        'turbine_id' => $conn->real_escape_string($obj['turbine_id'] ?? ''),
        'wtg_no' => $conn->real_escape_string($obj['wtg_no'] ?? ''),
        'loc_no' => $conn->real_escape_string($obj['loc_no'] ?? ''),
        'model_id' => $conn->real_escape_string($obj['model_id'] ?? ''),
        'model_type' => $conn->real_escape_string($obj['model_type'] ?? ''),
        'htsc_no' => $conn->real_escape_string($obj['htsc_no'] ?? ''),
        'capacity' => floatval($obj['capacity'] ?? 0),
        'make' => $conn->real_escape_string($obj['make'] ?? ''),
        'csr_booked_by' => $conn->real_escape_string($obj['csr_booked_by'] ?? ''),
        'csr_booked_by_date' => $conn->real_escape_string($obj['csr_booked_by_date'] ?? ''),
        'csr_booked_by_time' => $conn->real_escape_string($obj['csr_booked_by_time'] ?? ''),
        'nature_of_work' => $conn->real_escape_string($obj['nature_of_work'] ?? ''),
        'system_down' => isset($obj['system_down']) ? (int)$obj['system_down'] : 0,
        'system_down_date' => $conn->real_escape_string($obj['system_down_date'] ?? ''),
        'system_down_time' => $conn->real_escape_string($obj['system_down_time'] ?? ''),
        'work_st_date' => $conn->real_escape_string($obj['work_st_date'] ?? ''),
        'work_st_time' => $conn->real_escape_string($obj['work_st_time'] ?? ''),
        'work_end_date' => $conn->real_escape_string($obj['work_end_date'] ?? ''),
        'work_end_time' => $conn->real_escape_string($obj['work_end_time'] ?? ''),
        'parts_data' => $conn->real_escape_string(json_encode($obj['parts_data'] ?? [], JSON_UNESCAPED_SLASHES)),
        'employee_name' => $conn->real_escape_string($obj['employee_name'] ?? ''),
        'employee_id' => $conn->real_escape_string($obj['employee_id'] ?? ''),
        'incharge_operator_name' => $conn->real_escape_string($obj['incharge_operator_name'] ?? ''),
        'csr_type' => $conn->real_escape_string($obj['csr_type'] ?? ''),
        'site_id' => $conn->real_escape_string($obj['site_id'] ?? ''),
        'site_name' => $conn->real_escape_string($obj['site_name'] ?? ''),
        'customer_feedback' => $conn->real_escape_string($obj['customer_feedback'] ?? '')
    ];

    $required = [
        'csr_no',
        'csr_entry_date',
        'customer_id',
        'customer_name',
        'contract_id',
        'contract_type',
        'turbine_id',
        'wtg_no',
        'loc_no',
        'model_id',
        'model_type',
        'htsc_no',
        'capacity',
        'make',
        'csr_booked_by',
        'csr_booked_by_date',
        'csr_booked_by_time',
        'nature_of_work',
        'system_down_date',
        'system_down_time',
        'work_st_date',
        'work_st_time',
        'work_end_date',
        'work_end_time',
        'employee_name',
        'employee_id',
        'incharge_operator_name'
    ];

    // foreach ($required as $field) {
    //     if (empty($fields[$field]) && $fields[$field] !== '0') {
    //         $output["head"]["code"] = 400;
    //         $output["head"]["msg"] = "Please provide $field.";
    //         echo json_encode($output, JSON_NUMERIC_CHECK);
    //         exit();
    //     }
    // }

    if (empty($obj['parts_data']) || !is_array($obj['parts_data'])) {
        $output["head"]["code"] = 400;
        $output["head"]["msg"] = "Please provide valid parts_data.";
        echo json_encode($output, JSON_NUMERIC_CHECK);
        exit();
    }

    if (empty($obj['employee_sign']) || strpos($obj['employee_sign'], 'data:image') !== 0) {
        $output["head"]["code"] = 400;
        $output["head"]["msg"] = "Please provide a valid employee signature.";
        echo json_encode($output, JSON_NUMERIC_CHECK);
        exit();
    }

    if (empty($obj['incharge_operator_sign']) || strpos($obj['incharge_operator_sign'], 'data:image') !== 0) {
        $output["head"]["code"] = 400;
        $output["head"]["msg"] = "Please provide a valid incharge operator signature.";
        echo json_encode($output, JSON_NUMERIC_CHECK);
        exit();
    }

    $check_sql = "SELECT `id` FROM `csr_entry` WHERE `csr_no` = '{$fields['csr_no']}' AND `delete_at` = 0";
    $check_result = mysqli_query($conn, $check_sql);
    if (mysqli_num_rows($check_result) > 0) {
        $output["head"]["code"] = 400;
        $output["head"]["msg"] = "CSR number already exists.";
        echo json_encode($output, JSON_NUMERIC_CHECK);
        exit();
    }

    $employee_sign = processImage($obj['employee_sign'], 'employee_sign')['path'] ?? '';
    $incharge_operator_sign = processImage($obj['incharge_operator_sign'], 'incharge_operator_sign')['path'] ?? '';

    $employee_sign = str_replace($base_url . '/', '../', $employee_sign);
    $incharge_operator_sign = str_replace($base_url . '/', '../', $incharge_operator_sign);

    $timestamp = date('Y-m-d H:i:s');

    $sql = "INSERT INTO `csr_entry` (
        `csr_no`, `csr_entry_date`, `customer_id`, `customer_name`, `contract_id`, `contract_type`,
        `error_details`, `turbine_id`, `wtg_no`, `loc_no`, `model_id`, `model_type`,
        `htsc_no`, `capacity`, `make`, `csr_booked_by`, `csr_booked_by_date`, `csr_booked_by_time`,
        `nature_of_work`, `system_down`, `system_down_date`, `system_down_time`, `work_st_date`,
        `work_st_time`, `work_end_date`, `work_end_time`, `parts_data`, `employee_name`, `employee_id`,
        `employee_sign`, `incharge_operator_name`, `incharge_operator_sign`, `csr_type`, `site_id`, `site_name`, `customer_feedback`,
        `create_at`, `delete_at`
    ) VALUES (
        '{$fields['csr_no']}', '{$fields['csr_entry_date']}', '{$fields['customer_id']}',
        '{$fields['customer_name']}', '{$fields['contract_id']}', '{$fields['contract_type']}',
        '{$fields['error_details']}', '{$fields['turbine_id']}', '{$fields['wtg_no']}',
        '{$fields['loc_no']}', '{$fields['model_id']}', '{$fields['model_type']}',
        '{$fields['htsc_no']}', '{$fields['capacity']}', '{$fields['make']}',
        '{$fields['csr_booked_by']}', '{$fields['csr_booked_by_date']}', '{$fields['csr_booked_by_time']}',
        '{$fields['nature_of_work']}', '{$fields['system_down']}', '{$fields['system_down_date']}',
        '{$fields['system_down_time']}', '{$fields['work_st_date']}', '{$fields['work_st_time']}',
        '{$fields['work_end_date']}', '{$fields['work_end_time']}', '{$fields['parts_data']}',
        '{$fields['employee_name']}', '{$fields['employee_id']}', '{$employee_sign}',
        '{$fields['incharge_operator_name']}', '{$incharge_operator_sign}', '{$fields['csr_type']}',
        '{$fields['site_id']}', '{$fields['site_name']}', '{$fields['customer_feedback']}', '$timestamp', 0
    )";

    if (mysqli_query($conn, $sql)) {
        $id = mysqli_insert_id($conn);
        $uniqueCsrID = uniqueID('csr_entry', $id);

        $update_sql = "UPDATE `csr_entry` SET `csr_entry_id` = '$uniqueCsrID', `csr_no_id` = '$uniqueCsrID' WHERE `id` = $id";
        mysqli_query($conn, $update_sql);

        $map_sql = "UPDATE `csr_mapping` SET `status` = 'Completed'
                    WHERE JSON_SEARCH(`csr_mapping_data`, 'one', '{$fields['csr_no']}') IS NOT NULL AND `delete_at` = 0";
        mysqli_query($conn, $map_sql);

        $output["head"]["code"] = 200;
        $output["head"]["msg"] = "CSR entry added successfully";
        $output["body"] = array_merge(
            ['id' => $id, 'csr_entry_id' => $uniqueCsrID, 'csr_no_id' => $uniqueCsrID, 'create_at' => $timestamp, 'delete_at' => 0],
            $fields,
            [
                'employee_sign' => $base_url . '/' . str_replace('../', '', $employee_sign),
                'incharge_operator_sign' => $base_url . '/' . str_replace('../', '', $incharge_operator_sign)
            ]
        );
    } else {
        error_log("CSR insertion failed: " . mysqli_error($conn));
        $output["head"]["code"] = 400;
        $output["head"]["msg"] = "Failed to add CSR entry.";
        echo json_encode($output, JSON_NUMERIC_CHECK);
        exit();
    }
}

// Update CSR Entry
elseif (isset($obj['edit_csr_entry_id'])) {
    $edit_id = $conn->real_escape_string($obj['edit_csr_entry_id']);
    $fields = [
        'csr_no' => $conn->real_escape_string($obj['csr_no'] ?? ''),
        'csr_entry_date' => $conn->real_escape_string($obj['csr_entry_date'] ?? ''),
        'customer_id' => $conn->real_escape_string($obj['customer_id'] ?? ''),
        'customer_name' => $conn->real_escape_string($obj['customer_name'] ?? ''),
        'contract_id' => $conn->real_escape_string($obj['contract_id'] ?? ''),
        'contract_type' => $conn->real_escape_string($obj['contract_type'] ?? ''),
        'error_details' => $conn->real_escape_string(json_encode($obj['error_details'] ?? [], JSON_UNESCAPED_SLASHES)),
        'turbine_id' => $conn->real_escape_string($obj['turbine_id'] ?? ''),
        'wtg_no' => $conn->real_escape_string($obj['wtg_no'] ?? ''),
        'loc_no' => $conn->real_escape_string($obj['loc_no'] ?? ''),
        'model_id' => $conn->real_escape_string($obj['model_id'] ?? ''),
        'model_type' => $conn->real_escape_string($obj['model_type'] ?? ''),
        'htsc_no' => $conn->real_escape_string($obj['htsc_no'] ?? ''),
        'capacity' => $conn->real_escape_string($obj['capacity'] ?? ''),
        'make' => $conn->real_escape_string($obj['make'] ?? ''),
        'csr_booked_by' => $conn->real_escape_string($obj['csr_booked_by'] ?? ''),
        'csr_booked_by_date' => $conn->real_escape_string($obj['csr_booked_by_date'] ?? ''),
        'csr_booked_by_time' => $conn->real_escape_string($obj['csr_booked_by_time'] ?? ''),
        'nature_of_work' => $conn->real_escape_string($obj['nature_of_work'] ?? ''),
        'system_down' => isset($obj['system_down']) ? (int)$obj['system_down'] : 0,
        'system_down_date' => $conn->real_escape_string($obj['system_down_date'] ?? ''),
        'system_down_time' => $conn->real_escape_string($obj['system_down_time'] ?? ''),
        'work_st_date' => $conn->real_escape_string($obj['work_st_date'] ?? ''),
        'work_st_time' => $conn->real_escape_string($obj['work_st_time'] ?? ''),
        'work_end_date' => $conn->real_escape_string($obj['work_end_date'] ?? ''),
        'work_end_time' => $conn->real_escape_string($obj['work_end_time'] ?? ''),
        'parts_data' => $conn->real_escape_string(json_encode($obj['parts_data'] ?? [], JSON_UNESCAPED_SLASHES)),
        'incharge_operator_name' => $conn->real_escape_string($obj['incharge_operator_name'] ?? ''),
        'employee_name' => $conn->real_escape_string($obj['employee_name'] ?? ''),
        'employee_id' => $conn->real_escape_string($obj['employee_id'] ?? ''),
        'csr_type' => $conn->real_escape_string($obj['csr_type'] ?? ''),
        'site_id' => $conn->real_escape_string($obj['site_id'] ?? ''),
        'site_name' => $conn->real_escape_string($obj['site_name'] ?? ''),
        'customer_feedback' => $conn->real_escape_string($obj['customer_feedback'] ?? '')
    ];

    $required = [
        'csr_no',
        'csr_entry_date',
        'customer_id',
        'customer_name',
        'contract_id',
        'contract_type',
        'turbine_id',
        'wtg_no',
        'loc_no',
        'model_id',
        'model_type',
        'htsc_no',
        'capacity',
        'make',
        'csr_booked_by',
        'csr_booked_by_date',
        'csr_booked_by_time',
        'nature_of_work',
        'system_down_date',
        'system_down_time',
        'work_st_date',
        'work_st_time',
        'work_end_date',
        'work_end_time',
        'employee_name',
        'employee_id',
        'incharge_operator_name'
    ];

    foreach ($required as $field) {
        if (empty($fields[$field]) && $fields[$field] !== '0') {
            $output["head"]["code"] = 400;
            $output["head"]["msg"] = "Please provide $field.";
            echo json_encode($output, JSON_NUMERIC_CHECK);
            exit();
        }
    }

    if (empty($obj['parts_data']) || !is_array($obj['parts_data'])) {
        $output["head"]["code"] = 400;
        $output["head"]["msg"] = "Please provide parts_data.";
        echo json_encode($output, JSON_NUMERIC_CHECK);
        exit();
    }

    $check_sql = "SELECT `id` FROM `csr_entry` WHERE `csr_no` = '{$fields['csr_no']}' AND `csr_entry_id` != '{$edit_id}' AND `delete_at` = 0";
    $check_result = mysqli_query($conn, $check_sql);
    if (mysqli_num_rows($check_result) > 0) {
        $output["head"]["code"] = 400;
        $output["head"]["msg"] = "CSR number already exists.";
        echo json_encode($output, JSON_NUMERIC_CHECK);
        exit();
    }

    $incharge_operator_sign = '';
    if (!empty($obj['incharge_operator_sign']) && strpos($obj['incharge_operator_sign'], 'data:image') === 0) {
        $imgResult = processImage($obj['incharge_operator_sign'], 'incharge_operator_sign');
        if (isset($imgResult['error'])) {
            $output["head"]["code"] = 400;
            $output["head"]["msg"] = $imgResult['error'];
            echo json_encode($output, JSON_NUMERIC_CHECK);
            exit();
        }
        $incharge_operator_sign = str_replace($base_url . '/', '../', $imgResult['path']);
    } else {
        $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT `incharge_operator_sign` FROM `csr_entry` WHERE `csr_entry_id` = '{$edit_id}'"));
        $incharge_operator_sign = $row['incharge_operator_sign'] ?? '';
    }

    $employee_sign = '';
    if (!empty($obj['employee_sign']) && strpos($obj['employee_sign'], 'data:image') === 0) {
        $imgResult = processImage($obj['employee_sign'], 'employee_sign');
        if (isset($imgResult['error'])) {
            $output["head"]["code"] = 400;
            $output["head"]["msg"] = $imgResult['error'];
            echo json_encode($output, JSON_NUMERIC_CHECK);
            exit();
        }
        $employee_sign = str_replace($base_url . '/', '../', $imgResult['path']);
    } else {
        $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT `employee_sign` FROM `csr_entry` WHERE `csr_entry_id` = '{$edit_id}'"));
        $employee_sign = $row['employee_sign'] ?? '';
    }

    if (empty($employee_sign)) {
        $output["head"]["code"] = 400;
        $output["head"]["msg"] = "Employee signature is required.";
        echo json_encode($output, JSON_NUMERIC_CHECK);
        exit();
    }
    if (empty($incharge_operator_sign)) {
        $output["head"]["code"] = 400;
        $output["head"]["msg"] = "Incharge operator signature is required.";
        echo json_encode($output, JSON_NUMERIC_CHECK);
        exit();
    }

    $update_sql = "UPDATE `csr_entry` SET 
        `csr_no` = '{$fields['csr_no']}',
        `csr_entry_date` = '{$fields['csr_entry_date']}',
        `customer_id` = '{$fields['customer_id']}',
        `customer_name` = '{$fields['customer_name']}',
        `contract_id` = '{$fields['contract_id']}',
        `contract_type` = '{$fields['contract_type']}',
        `error_details` = '{$fields['error_details']}',
        `turbine_id` = '{$fields['turbine_id']}',
        `wtg_no` = '{$fields['wtg_no']}',
        `loc_no` = '{$fields['loc_no']}',
        `model_id` = '{$fields['model_id']}',
        `model_type` = '{$fields['model_type']}',
        `htsc_no` = '{$fields['htsc_no']}',
        `capacity` = '{$fields['capacity']}',
        `make` = '{$fields['make']}',
        `csr_booked_by` = '{$fields['csr_booked_by']}',
        `csr_booked_by_date` = '{$fields['csr_booked_by_date']}',
        `csr_booked_by_time` = '{$fields['csr_booked_by_time']}',
        `nature_of_work` = '{$fields['nature_of_work']}',
        `system_down` = {$fields['system_down']},
        `system_down_date` = '{$fields['system_down_date']}',
        `system_down_time` = '{$fields['system_down_time']}',
        `work_st_date` = '{$fields['work_st_date']}',
        `work_st_time` = '{$fields['work_st_time']}',
        `work_end_date` = '{$fields['work_end_date']}',
        `work_end_time` = '{$fields['work_end_time']}',
        `parts_data` = '{$fields['parts_data']}',
        `incharge_operator_name` = '{$fields['incharge_operator_name']}',
        `employee_name` = '{$fields['employee_name']}',
        `employee_id` = '{$fields['employee_id']}',
        `incharge_operator_sign` = '{$incharge_operator_sign}',
        `employee_sign` = '{$employee_sign}',
        `csr_type` = '{$fields['csr_type']}',
        `site_id` = '{$fields['site_id']}',
        `site_name` = '{$fields['site_name']}',
        `customer_feedback` = '{$fields['customer_feedback']}'
        WHERE `csr_entry_id` = '{$edit_id}'";

    if (mysqli_query($conn, $update_sql)) {
        $output["head"]["code"] = 200;
        $output["head"]["msg"] = "CSR entry updated successfully";
        $output["body"] = array_merge(
            ['csr_entry_id' => $edit_id],
            $fields,
            [
                'incharge_operator_sign' => $base_url . '/' . str_replace('../', '', $incharge_operator_sign),
                'employee_sign' => $base_url . '/' . str_replace('../', '', $employee_sign)
            ]
        );
    } else {
        error_log("CSR update failed: " . mysqli_error($conn));
        $output["head"]["code"] = 400;
        $output["head"]["msg"] = "Failed to update CSR entry.";
        echo json_encode($output, JSON_NUMERIC_CHECK);
        exit();
    }
}

// Delete CSR Entry
elseif (isset($obj['delete_csr_entry_id'])) {
    $delete_id = $conn->real_escape_string($obj['delete_csr_entry_id']);

    if (empty($delete_id)) {
        $output["head"]["code"] = 400;
        $output["head"]["msg"] = "Please provide the CSR entry ID to delete.";
        echo json_encode($output, JSON_NUMERIC_CHECK);
        exit();
    }

    $update_sql = "UPDATE `csr_entry` SET `delete_at` = 1 WHERE `csr_entry_id` = '{$delete_id}'";

    if (mysqli_query($conn, $update_sql)) {
        $output["head"]["code"] = 200;
        $output["head"]["msg"] = "CSR entry deleted successfully";
    } else {
        error_log("CSR deletion failed: " . mysqli_error($conn));
        $output["head"]["code"] = 400;
        $output["head"]["msg"] = "Failed to delete CSR entry.";
    }
}

// Get Turbine Data
elseif (isset($obj['action']) && $obj['action'] === 'get_turbine_data') {
    try {
        $query = "
            SELECT 
                t.turbine_id,
                t.wtg_no,
                t.customer_id,
                t.site_id,
                t.loc_no,
                t.htsc_no,
                t.model_id,
                t.contracttype_id,
                t.capacity,
                t.incharge_name,
                t.siteoperator_name,
                t.ctpt_make,
                c.customer_name,
                sn.site_name,
                m.model_type,
                ct.contract_code
            FROM 
                turbine t
            LEFT JOIN 
                customer c ON t.customer_id = c.customer_unique_id
            LEFT JOIN 
                site sn ON t.site_id = sn.site_id
            LEFT JOIN 
                model m ON t.model_id = m.model_id
            LEFT JOIN 
                contract_type ct ON t.contracttype_id = ct.contract_id
            WHERE 
                t.delete_at = 0
            GROUP BY 
                t.wtg_no
            ORDER BY 
                t.wtg_no
        ";

        $result = $conn->query($query);

        if ($result === false) {
            throw new Exception("Query failed: " . $conn->error);
        }

        $data = array();
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        $output["head"]["code"] = 200;
        $output["head"]["msg"] = "Data retrieved successfully";
        $output["body"] = $data;
    } catch (Exception $e) {
        error_log("Error: " . $e->getMessage());
        $output["head"]["code"] = 400;
        $output["head"]["msg"] = "Failed to retrieve data. Please try again.";
    }
} else {
    $output["head"]["code"] = 400;
    $output["head"]["msg"] = "Parameter mismatch or invalid action";
}

echo json_encode($output, JSON_NUMERIC_CHECK);
$conn->close();
