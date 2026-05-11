<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ .'/../config/utility.php';
require_once __DIR__ . '/../models/mdl_attendance.php';

$filters = [
    'search' => trim($_GET['search'] ?? ''),
    'date' => trim($_GET['date'] ??  date('Y-m-d')),
    'membership_type' => $_GET['membership_type'] ?? 'all',
    'pass_type' => $_GET['pass_type'] ?? 'all'
];

$attendanceList = getAttendanceList($pdo, $filters);
$filterOptions = getAttendanceFilterOptions($pdo);

$membershipTypes = [];
$passTypes = [];

foreach ($filterOptions as $option) {
    if (!empty($option['membership_type'])) {
        $membershipTypes[$option['membership_type']] = $option['membership_type'];
    }

    if (!empty($option['pass_type'])) {
        $passTypes[$option['pass_type']] = $option['pass_type'];
    }
}

$columns = [
    'client_name' => 'Client Name',
    // 'contact' => 'Contact',
    'membership_type' => 'Membership Type',
    'pass_type' => 'Pass Type',
    'attendance_date' => 'Date',
    'check_in_time' => 'Check-in Time',
    'training_session_used' => 'Training Session'
];

require_once __DIR__ . '/../views/vw_attendance.php';