<?php
include("../config/db_config.php");
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET,POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit();
}
header('Content-Type: application/json; charset=utf-8');

$json = file_get_contents('php://input');
$obj = json_decode($json);
$output = array();
date_default_timezone_set('Asia/Calcutta');
$timestamp = date('Y-m-d H:i:s');
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$domain = $_SERVER['HTTP_HOST'];
$base_url = $protocol . $domain;

// <<<<<<<<<<===================== List Parts =====================>>>>>>>>>>
if (isset($obj->search_text)) {
    $search_text = $conn->real_escape_string($obj->search_text);
    $search_terms = array_filter(array_map('trim', explode(' ', $search_text)));

    $conditions = [];
    foreach ($search_terms as $term) {
        $term = $conn->real_escape_string($term);
        $conditions[] = "(`part_no` LIKE '%$term%' OR `type_and_classification` LIKE '%$term%' OR `uom` LIKE '%$term%')";
    }

    $where_clause = !empty($conditions) ? '(' . implode(' AND ', $conditions) . ')' : '1=1';

    $sql = "SELECT `id`, `parts_id`, `type_and_classification`, `part_no`, `description`, 
                   `technical_description`, `uom`, `part_img`, `amc`, `non_amc`, `create_at`, `delete_at`
            FROM `parts` 
            WHERE `delete_at` = 0 
            AND $where_clause
            ORDER BY `id` ASC";

    // Log for debugging if needed
    error_log("SQL query: " . $sql);

    $result = mysqli_query($conn, $sql);
    if (!$result) {
        error_log("Parts query failed: " . mysqli_error($conn));
        $output["head"]["code"] = 500;
        $output["head"]["msg"] = "Database error: " . mysqli_error($conn);
        echo json_encode($output, JSON_NUMERIC_CHECK);
        exit();
    }

    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $row['part_img'] = json_decode($row['part_img'], true) ?? [];
            $full_img_urls = [];
            foreach ($row['part_img'] as $img_path) {
                // Remove ../ safely
                $cleaned_path = str_replace('../', '', $img_path);
                if (!empty($cleaned_path)) {
                    $full_url = $base_url . '/' . $cleaned_path;
                    $full_img_urls[] = $full_url;
                }
            }
            $row['part_img'] = $full_img_urls;

            $output["head"]["code"] = 200;
            $output["head"]["msg"] = "Success";
            $output["body"]["parts"][] = $row;
        }
    } else {
        $output["head"]["code"] = 200;
        $output["head"]["msg"] = "No records found";
        $output["body"]["parts"] = [];
    }

}

// <<<<<<<<<<===================== Create or Update Parts =====================>>>>>>>>>>
elseif (isset($obj->edit_parts_id)) {
    $edit_id = $conn->real_escape_string($obj->edit_parts_id);
    $type_and_classification = $conn->real_escape_string($obj->type_and_classification ?? '');
    $part_no = $conn->real_escape_string($obj->part_no ?? '');
    $description = $conn->real_escape_string($obj->description ?? '');
    $technical_description = $conn->real_escape_string($obj->technical_description ?? '');
    $uom = $conn->real_escape_string($obj->uom ?? '');
    $amc = isset($obj->amc) && $obj->amc !== "" ? floatval($obj->amc) : 0.00;
    $non_amc = isset($obj->non_amc) && $obj->non_amc !== "" ? floatval($obj->non_amc) : 0.00;
    $part_img = isset($obj->part_img) ? $obj->part_img : null;

    // Validate required fields
    if (
        empty($type_and_classification) ||
        empty($part_no) ||
        empty($description) ||
        empty($technical_description) ||
        empty($uom) ||
        empty($part_img)
    ) {
        $output["head"]["code"] = 400;
        $output["head"]["msg"]  = "Please provide all required fields.";
        echo json_encode($output, JSON_NUMERIC_CHECK);
        exit();
    }

    // Check if part_no exists in another record
    $checkSql = "SELECT `id` FROM `parts` WHERE `part_no` = '$part_no' AND `delete_at` = 0 AND `parts_id` != '$edit_id'";
    $checkResult = mysqli_query($conn, $checkSql);
    if (!$checkResult) {
        error_log("Query failed: " . mysqli_error($conn));
        $output["head"]["code"] = 500;
        $output["head"]["msg"]  = "Internal error. Please contact support.";
        echo json_encode($output, JSON_NUMERIC_CHECK);
        exit();
    }

    if (mysqli_num_rows($checkResult) > 0) {
        $output["head"]["code"] = 400;
        $output["head"]["msg"]  = "Part number already exists.";
        echo json_encode($output, JSON_NUMERIC_CHECK);
        exit();
    }

    $imgPaths = [];
    $update_img = false;

    if (!empty($part_img)) {
        if (is_string($part_img)) {
            $imgArray = [(object)['data' => $part_img]];
        } elseif (is_array($part_img)) {
            $imgArray = $part_img;
        } else {
            $imgArray = null;
        }

        if (!empty($imgArray)) {
            foreach ($imgArray as $base64File) {
                if (!isset($base64File->data) || !is_string($base64File->data)) {
                    break;
                }

                $fileData = $base64File->data;
                $fileName = uniqid("part_img_");

                if (!preg_match('/^data:image\/(\w+);base64,/', $fileData, $type)) {
                    break;
                }

                $fileName .= "." . strtolower($type[1]);
                $filePath = "../uploads/images/" . $fileName;

                $fileData = preg_replace('/^data:.*;base64,/', '', $fileData);
                $decodedFile = base64_decode($fileData);
                if ($decodedFile === false) {
                    break;
                }

                $directory = dirname($filePath);
                if (!is_dir($directory)) {
                    mkdir($directory, 0777, true);
                }

                if (file_put_contents($filePath, $decodedFile) === false) {
                    break;
                }

                $imgPaths[] = $filePath;
            }

            if (!empty($imgPaths)) {
                $update_img = true;
            }
        }
    }

    if ($update_img) {
        $imgJson = $conn->real_escape_string(json_encode($imgPaths, JSON_UNESCAPED_SLASHES));

        $updateSql = "UPDATE `parts` SET 
            `type_and_classification` = '$type_and_classification',
            `part_no` = '$part_no',
            `description` = '$description',
            `technical_description` = '$technical_description',
            `uom` = '$uom',
            `amc` = '$amc',
            `non_amc` = '$non_amc',
            `part_img` = '$imgJson'
            WHERE `parts_id` = '$edit_id'";
    } else {
        $updateSql = "UPDATE `parts` SET 
            `type_and_classification` = '$type_and_classification',
            `part_no` = '$part_no',
            `description` = '$description',
            `technical_description` = '$technical_description',
            `uom` = '$uom',
            `amc` = '$amc',
            `non_amc` = '$non_amc'
            WHERE `parts_id` = '$edit_id'";
    }

    if (mysqli_query($conn, $updateSql)) {
        $output["head"]["code"] = 200;
        $output["head"]["msg"]  = "Part updated successfully";
        $output["head"]["data"] = $obj;
    } else {
        error_log("Parts update failed: " . mysqli_error($conn));
        $output["head"]["code"] = 400;
        $output["head"]["msg"]  = "Failed to update. Please try again.";
    }

    echo json_encode($output, JSON_NUMERIC_CHECK);
    exit();
}
 elseif (isset($obj->part_no)) {
    $type_and_classification = isset($obj->type_and_classification) ? $conn->real_escape_string($obj->type_and_classification) : '';
    $part_no                 = isset($obj->part_no) ? $conn->real_escape_string($obj->part_no) : '';
    $description             = isset($obj->description) ? $conn->real_escape_string($obj->description) : '';
    $technical_description   = isset($obj->technical_description) ? $conn->real_escape_string($obj->technical_description) : '';
    $uom                     = isset($obj->uom) ? $conn->real_escape_string($obj->uom) : '';
    $amc                     = isset($obj->amc) && $obj->amc !== "" ? floatval($obj->amc) : 0.00;
    $non_amc                 = isset($obj->non_amc) && $obj->non_amc !== "" ? floatval($obj->non_amc) : 0.00;
    $part_img                = isset($obj->part_img) ? $obj->part_img : '';

    // Validate required fields
    if (
        empty($type_and_classification) ||
        empty($part_no) ||
        empty($description) ||
        empty($technical_description) ||
        empty($uom) ||
        empty($part_img)
    ) {
        $output["head"]["code"] = 400;
        $output["head"]["msg"]  = "Please provide all required fields.";
        echo json_encode($output, JSON_NUMERIC_CHECK);
        exit();
    }

    // Validate numeric fields
    if (!is_numeric($amc) || $amc < 0 || !is_numeric($non_amc) || $non_amc < 0) {
        $output["head"]["code"] = 400;
        $output["head"]["msg"]  = "AMC and Non-AMC amounts must be non-negative numbers.";
        echo json_encode($output, JSON_NUMERIC_CHECK);
        exit();
    }

    // Check if part_no already exists
    $checkSql = "SELECT `id` FROM `parts` WHERE `part_no` = '$part_no' AND `delete_at` = 0";
    $checkResult = mysqli_query($conn, $checkSql);
    if (!$checkResult) {
        error_log("Query failed: " . mysqli_error($conn));
        $output["head"]["code"] = 500;
        $output["head"]["msg"]  = "Internal error. Please contact support.";
        echo json_encode($output, JSON_NUMERIC_CHECK);
        exit();
    }

    if (mysqli_num_rows($checkResult) > 0) {
        $output["head"]["code"] = 400;
        $output["head"]["msg"]  = "Part number already exists.";
        echo json_encode($output, JSON_NUMERIC_CHECK);
        exit();
    }

    // Process images
    $imgPaths = [];
    if (is_string($part_img)) {
        $imgArray = [(object)['data' => $part_img]];
    } elseif (is_array($part_img)) {
        $imgArray = $part_img;
    } else {
        $output["head"]["code"] = 400;
        $output["head"]["msg"]  = "Part image must be a Base64 string or an array of Base64 strings.";
        echo json_encode($output, JSON_NUMERIC_CHECK);
        exit();
    }

    foreach ($imgArray as $base64File) {
        if (!isset($base64File->data) || !is_string($base64File->data)) {
            $output["head"]["code"] = 400;
            $output["head"]["msg"]  = "Invalid file format. Expected Base64 encoded string.";
            echo json_encode($output, JSON_NUMERIC_CHECK);
            exit();
        }

        $fileData = $base64File->data;
        $fileName = uniqid("part_img_");

        if (preg_match('/^data:image\/(\w+);base64,/', $fileData, $type)) {
            $fileName .= "." . strtolower($type[1]);
            $filePath = "../uploads/images/" . $fileName;
        } else {
            $output["head"]["code"] = 400;
            $output["head"]["msg"]  = "Unsupported file type. Only images are allowed.";
            echo json_encode($output, JSON_NUMERIC_CHECK);
            exit();
        }

        $fileData    = preg_replace('/^data:.*;base64,/', '', $fileData);
        $decodedFile = base64_decode($fileData);
        if ($decodedFile === false) {
            $output["head"]["code"] = 400;
            $output["head"]["msg"]  = "Base64 decoding failed.";
            echo json_encode($output, JSON_NUMERIC_CHECK);
            exit();
        }

        $directory = dirname($filePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        if (file_put_contents($filePath, $decodedFile) === false) {
            $output["head"]["code"] = 400;
            $output["head"]["msg"]  = "Failed to save the image.";
            echo json_encode($output, JSON_NUMERIC_CHECK);
            exit();
        }

        $imgPaths[] = $filePath;
    }

    $imgJson   = json_encode($imgPaths, JSON_UNESCAPED_SLASHES);
    $timestamp = date('Y-m-d H:i:s');

    // Insert part (base query style)
    $insertSql = "INSERT INTO `parts` (
        `type_and_classification`, `part_no`, `description`, 
        `technical_description`, `uom`, `amc`, `non_amc`, 
        `part_img`, `create_at`, `delete_at`
    ) VALUES (
        '$type_and_classification', '$part_no', '$description', 
        '$technical_description', '$uom', '$amc', '$non_amc', 
        '$imgJson', '$timestamp', 0
    )";

    if (mysqli_query($conn, $insertSql)) {
        $id = mysqli_insert_id($conn);

        $uniquePartsID = uniqueID('parts', $id);

        $updateSql = "UPDATE `parts` SET `parts_id` = '$uniquePartsID' WHERE `id` = $id";
        mysqli_query($conn, $updateSql);

        $output["head"]["code"] = 200;
        $output["head"]["msg"]  = "Part added successfully";
        $output["body"] = [
            "id"                      => $id,
            "parts_id"                => $uniquePartsID,
            "type_and_classification" => $type_and_classification,
            "part_no"                => $part_no,
            "description"            => $description,
            "technical_description"  => $technical_description,
            "uom"                    => $uom,
            "amc"                    => $amc,
            "non_amc"                => $non_amc,
            "part_img"               => $imgJson,
            "create_at"              => $timestamp,
            "delete_at"              => 0
        ];
    } else {
        error_log("Parts insertion failed: " . mysqli_error($conn));
        $output["head"]["code"] = 400;
        $output["head"]["msg"]  = "Failed to add. Please try again.";
    }

    echo json_encode($output, JSON_NUMERIC_CHECK);
    exit();
}


// <<<<<<<<<<===================== Delete Parts =====================>>>>>>>>>>  
elseif (isset($obj->delete_parts_id)) {
    $delete_parts_id = $conn->real_escape_string($obj->delete_parts_id);

    if (!empty($delete_parts_id)) {
        $stmt = $conn->prepare("UPDATE `parts` SET `delete_at` = 1 WHERE `parts_id` = ?");
        if (!$stmt) {
            error_log("Prepare failed (delete): " . $conn->error);
            $output["head"]["code"] = 500;
            $output["head"]["msg"]  = "Internal error. Please contact support.";
            echo json_encode($output, JSON_NUMERIC_CHECK);
            exit();
        }

        $stmt->bind_param("s", $delete_parts_id);
        if ($stmt->execute()) {
            $output["head"]["code"] = 200;
            $output["head"]["msg"]  = "Part deleted successfully";
        } else {
            error_log("Parts deletion failed: " . $stmt->error);
            $output["head"]["code"] = 400;
            $output["head"]["msg"]  = "Failed to delete. Please try again.";
        }
        $stmt->close();
    } else {
        $output["head"]["code"] = 400;
        $output["head"]["msg"]  = "Please provide the parts ID to delete.";
    }
} else {
    $output["head"]["code"] = 400;
    $output["head"]["msg"] = "Parameter mismatch";
}

echo json_encode($output, JSON_NUMERIC_CHECK);
