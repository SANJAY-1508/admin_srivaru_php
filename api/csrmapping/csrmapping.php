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

// <<<<<<<<<<===================== List CSR Mappings =====================>>>>>>>>>>
if (isset($obj['search_text'])) {
    $search_text = $conn->real_escape_string($obj['search_text']);
    $sql = "SELECT *
            FROM `csr_mapping`
            WHERE `delete_at` = 0
            AND (
                `csr_mapping_id` LIKE '%$search_text%' 
                OR `csr_mapping_data` LIKE '%$search_text%' 
            )
            ORDER BY `id` ASC";

    $result = $conn->query($sql);
    if ($result === false) {
        error_log("CSR Mapping query failed: " . $conn->error);
        $output["head"]["code"] = 500;
        $output["head"]["msg"] = "Database error";
        echo json_encode($output, JSON_NUMERIC_CHECK);
        exit();
    }

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Decode csr_mapping_data, ensuring it's an array
            $decoded_data = json_decode($row['csr_mapping_data'], true);
            $row['csr_mapping_data'] = is_array($decoded_data) ? $decoded_data : [];
            $output["head"]["code"] = 200;
            $output["head"]["msg"] = "Success";
            $output["body"]["mappings"][] = $row;
        }
    } else {
        $output["head"]["code"] = 200;
        $output["head"]["msg"] = "No records found";
        $output["body"]["mappings"] = [];
    }
}
// <<<<<<<<<<===================== Create or Update CSR Mapping =====================>>>>>>>>>>
elseif (isset($obj['edit_csr_mapping_id'])) {
    $edit_id = $conn->real_escape_string($obj['edit_csr_mapping_id']);
    $csr_mapping_data = json_encode($obj['csr_mapping_data'] ?? [], JSON_UNESCAPED_SLASHES);

    // Validate required fields
    if (empty($csr_mapping_data)) {
        $output["head"]["code"] = 400;
        $output["head"]["msg"] = "Please provide csr_mapping_data.";
        echo json_encode($output, JSON_NUMERIC_CHECK);
        exit();
    }

    // Check if the record exists
    $sql = "SELECT `id` FROM `csr_mapping` WHERE `csr_mapping_id` = '$edit_id' AND `delete_at` = 0";
    $result = $conn->query($sql);
    if ($result->num_rows === 0) {
        $output["head"]["code"] = 404;
        $output["head"]["msg"] = "CSR Mapping not found.";
        echo json_encode($output, JSON_NUMERIC_CHECK);
        exit();
    }

    // Check for duplicate csr_mapping_id in other records (optional, as it’s already in your code)
    $sql = "SELECT `id` FROM `csr_mapping` WHERE `csr_mapping_id` = '$edit_id' AND `delete_at` = 0 AND `csr_mapping_id` != '$edit_id'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $output["head"]["code"] = 400;
        $output["head"]["msg"] = "CSR Mapping ID already exists.";
        echo json_encode($output, JSON_NUMERIC_CHECK);
        exit();
    }

    $sql = "UPDATE `csr_mapping` SET 
            `csr_mapping_data`='$csr_mapping_data'
            WHERE `csr_mapping_id`='$edit_id'";

    if ($conn->query($sql)) {
        $output["head"]["code"] = 200;
        $output["head"]["msg"] = "CSR Mapping updated successfully";
        $output["head"]["data"] = [
            'edit_csr_mapping_id' => $edit_id,
            'csr_mapping_data' => json_decode($csr_mapping_data, true)
        ];
    } else {
        error_log("CSR Mapping update failed: " . $conn->error);
        $output["head"]["code"] = 400;
        $output["head"]["msg"] = "Failed to update. Please try again.";
    }
} elseif (isset($obj['csr_mapping_data'])) {
    $csr_mapping_data = json_encode($obj['csr_mapping_data'] ?? [], JSON_UNESCAPED_SLASHES);

    // Validate required fields
    if (empty($csr_mapping_data)) {
        $output["head"]["code"] = 400;
        $output["head"]["msg"] = "Please provide csr_mapping_data.";
        echo json_encode($output, JSON_NUMERIC_CHECK);
        exit();
    }

    // Extract csr_no from csr_mapping_data
    $csr_no = $conn->real_escape_string($obj['csr_mapping_data'][0]['csr_no'] ?? '');
    if (empty($csr_no)) {
        $output["head"]["code"] = 400;
        $output["head"]["msg"] = "CSR number is required in csr_mapping_data.";
        echo json_encode($output, JSON_NUMERIC_CHECK);
        exit();
    }

    // Check if csr_no exists in csr table and is Unmapping
    $sql = "SELECT `id` FROM `csr` WHERE `csr_no` = '$csr_no' AND `status` = 'Unmapping' AND `delete_at` = 0";
    $result = $conn->query($sql);
    if ($result->num_rows === 0) {
        $output["head"]["code"] = 400;
        $output["head"]["msg"] = "CSR number does not exist or is already mapped.";
        echo json_encode($output, JSON_NUMERIC_CHECK);
        exit();
    }

    // Start transaction to ensure atomicity
    $conn->begin_transaction();

    try {
        // Insert into csr_mapping
        $sql = "INSERT INTO `csr_mapping` (
            `csr_mapping_data`, `create_at`, `delete_at`
        ) VALUES (
            '$csr_mapping_data', '$timestamp', 0
        )";

        if (!$conn->query($sql)) {
            throw new Exception("CSR Mapping creation failed: " . $conn->error);
        }

        $id = $conn->insert_id;
        $uniqueMappingID = uniqueID('csr_mapping', $id);

        // Update csr_mapping_id
        $sql = "UPDATE `csr_mapping` SET `csr_mapping_id` = '$uniqueMappingID' WHERE `id` = $id";
        if (!$conn->query($sql)) {
            throw new Exception("CSR Mapping ID update failed: " . $conn->error);
        }

        // Update csr table to set csr_mapping to Mapping
        $sql = "UPDATE `csr` SET `status` = 'Mapping' WHERE `csr_no` = '$csr_no' AND `delete_at` = 0";
        if (!$conn->query($sql)) {
            throw new Exception("CSR table update failed: " . $conn->error);
        }

        // Commit transaction
        $conn->commit();

        $output["head"]["code"] = 200;
        $output["head"]["msg"] = "CSR Mapping added successfully";
        $output["body"] = [
            "id" => $id,
            "csr_mapping_id" => $uniqueMappingID,
            "csr_mapping_data" => json_decode($csr_mapping_data, true),
            "create_at" => $timestamp,
            "delete_at" => 0
        ];
    } catch (Exception $e) {
        $conn->rollback();
        error_log("CSR Mapping creation error: " . $e->getMessage());
        $output["head"]["code"] = 400;
        $output["head"]["msg"] = "Failed to add CSR Mapping. Please try again.";
        echo json_encode($output, JSON_NUMERIC_CHECK);
        exit();
    }
}
// <<<<<<<<<<===================== Delete CSR Mapping =====================>>>>>>>>>>
elseif (isset($obj['delete_csr_mapping_id'])) {
    $delete_csr_mapping_id = $conn->real_escape_string($obj['delete_csr_mapping_id']);

    if (!empty($delete_csr_mapping_id)) {
        $sql = "UPDATE `csr_mapping` SET `delete_at` = 1 WHERE `csr_mapping_id` = '$delete_csr_mapping_id'";
        if ($conn->query($sql)) {
            $output["head"]["code"] = 200;
            $output["head"]["msg"] = "CSR Mapping deleted successfully";
        } else {
            error_log("CSR Mapping deletion failed: " . $conn->error);
            $output["head"]["code"] = 400;
            $output["head"]["msg"] = "Failed to delete. Please try again.";
        }
    } else {
        $output["head"]["code"] = 400;
        $output["head"]["msg"] = "Please provide the CSR Mapping ID to delete.";
    }
} else {
    $output["head"]["code"] = 400;
    $output["head"]["msg"] = "Parameter mismatch";
}

echo json_encode($output, JSON_NUMERIC_CHECK);
$conn->close();