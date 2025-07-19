<?php
session_start();
require_once 'includes/functions.php';

// Check if there's test data to export
if (!isset($_SESSION['test_data']) || !isset($_SESSION['participant_info'])) {
    header('Location: index.php');
    exit;
}

// Get export data
$csvContent = exportToExcel();

if ($csvContent === false) {
    $_SESSION['error'] = 'Gagal mengekspor data. Silakan coba lagi.';
    header('Location: index.php');
    exit;
}

// Generate filename
$participantName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $_SESSION['participant_info']['name']);
$timestamp = date('Y-m-d_H-i-s');
$filename = "kraepelin_test_{$participantName}_{$timestamp}.csv";

// Set headers for file download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($csvContent));
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

// Output CSV content
echo "\xEF\xBB\xBF"; // UTF-8 BOM for Excel compatibility
echo $csvContent;
exit;
?>