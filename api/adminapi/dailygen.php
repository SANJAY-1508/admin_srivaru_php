<?php

include("../config/db_config.php");
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json; charset=utf-8');

$json = file_get_contents('php://input');
$obj = json_decode($json);
$output = array();

date_default_timezone_set('Asia/Calcutta');
$timestamp = date('Y-m-d');

// Initialize variables for total sums
$totalProductionToday = null;
$totalProductionThisMonth = 0;
$totalProductionThisYear = 0;

// Query to sum total_production for today
$queryToday = "SELECT SUM(total_production) as total_today FROM daily_generation WHERE DATE(dg_date) = '$timestamp' AND delete_at=0";
$resultToday = $conn->query($queryToday);

if ($resultToday && $resultToday->num_rows > 0) {
    $rowToday = $resultToday->fetch_assoc();
    $totalProductionToday = $rowToday['total_today'];
}

/// If no data for today, get the most recent date and sum the total_production for that date
if ($totalProductionToday === null) {
    // Get the most recent date
    $queryRecentDate = "SELECT dg_date FROM daily_generation WHERE dg_date < '$timestamp' AND delete_at=0 ORDER BY dg_date DESC LIMIT 1";
    $resultRecentDate = $conn->query($queryRecentDate);
    if ($resultRecentDate && $resultRecentDate->num_rows > 0) {
        $rowRecentDate = $resultRecentDate->fetch_assoc();
        $recentDate = $rowRecentDate['dg_date']; // Get the most recent date

        // Debugging: log the recentDate to check if it works
        error_log("Recent date found: " . $recentDate);

        // Sum the total_production for the most recent date
        $queryRecentSum = "SELECT SUM(total_production) as total_recent FROM daily_generation WHERE dg_date = '$recentDate' AND delete_at=0";
        $resultRecentSum = $conn->query($queryRecentSum);
        if ($resultRecentSum && $resultRecentSum->num_rows > 0) {
            $rowRecentSum = $resultRecentSum->fetch_assoc();
            $totalProductionToday = $rowRecentSum['total_recent'];
            $timestamp = $recentDate; // Update timestamp to the most recent date
        }
    } else {
        // Debugging: log if no recent date is found
        error_log("No recent date found in daily_generation.");
        $recentDate = "No recent date available"; // Set a fallback value
    }
}

// Query to sum total_production for the current month based on the updated timestamp
$queryMonth = "SELECT SUM(total_production) as total_month FROM daily_generation WHERE (YEAR(dg_date) = YEAR('$timestamp') AND MONTH(dg_date) = MONTH('$timestamp')) AND delete_at=0";
$resultMonth = $conn->query($queryMonth);
if ($resultMonth && $resultMonth->num_rows > 0) {
    $rowMonth = $resultMonth->fetch_assoc();
    $totalProductionThisMonth = $rowMonth['total_month'];
}

// Set totalProductionThisMonth to 0 if null or no production
if ($totalProductionThisMonth === null) {
    $totalProductionThisMonth = 0;
}

$filterDate = $totalProductionToday !== null ? $timestamp : $recentDate;

$date = new DateTime($recentDate);

// Determine fiscal year range
$currentMonth = (int)$date->format('m');
$currentYear = (int)$date->format('Y');
if ($currentMonth >= 4) {
    // Fiscal year starts in the current year
    $fiscalYearStart = $currentYear;
    $fiscalYearEnd = $currentYear + 1;
} else {
    // Fiscal year starts in the previous year
    $fiscalYearStart = $currentYear - 1;
    $fiscalYearEnd = $currentYear;
}

$yearRange = $fiscalYearStart . '-' . $fiscalYearEnd;

$fiscalYearStartTmp = $fiscalYearStart . '-04-01';
$fiscalYearEndTmp = $fiscalYearEnd.'-03-31';
// Query to sum total_production for the current year (fiscal year range)
$queryYear = "SELECT SUM(total_production) as total_year FROM daily_generation WHERE STR_TO_DATE(dg_date, '%Y-%m-%d') BETWEEN '$fiscalYearStartTmp' AND '$fiscalYearEndTmp' AND delete_at=0";
$resultYear = $conn->query($queryYear);
if ($resultYear && $resultYear->num_rows > 0) {
    $rowYear = $resultYear->fetch_assoc();
    $totalProductionThisYear = $rowYear['total_year'];
}

$output = [
    "status" => 200,
    "msg" => 'success',
    "today" => [
        "date" => $totalProductionToday !== null ? $timestamp : $recentDate, // Use $timestamp if today’s data exists, otherwise use $recentDate
        "total_production" => $totalProductionToday
    ],
    "month" => [
        "month" => date('F Y', strtotime($timestamp)), // Full month name and year based on updated timestamp
        "total_production" => $totalProductionThisMonth
    ],
    "year" => [
        "year" => $yearRange, // Fiscal year range (e.g., 2024-2025)
        "total_production" => $totalProductionThisYear
    ]
];

echo json_encode($output, JSON_NUMERIC_CHECK);

?>
