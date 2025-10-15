<?php
// General helper functions

// Format date in Latvian
function formatLatvianDate($date) {
    $months = [
        1 => 'janvāris', 2 => 'februāris', 3 => 'marts', 4 => 'aprīlis',
        5 => 'maijs', 6 => 'jūnijs', 7 => 'jūlijs', 8 => 'augusts',
        9 => 'septembris', 10 => 'oktobris', 11 => 'novembris', 12 => 'decembris'
    ];
    
    $timestamp = is_numeric($date) ? $date : strtotime($date);
    $day = date('j', $timestamp);
    $month = $months[(int)date('n', $timestamp)];
    $year = date('Y', $timestamp);
    
    return "{$day}. {$month} {$year}";
}

// Format time in Latvian
function formatLatvianTime($datetime) {
    $timestamp = is_numeric($datetime) ? $datetime : strtotime($datetime);
    return date('H:i', $timestamp);
}

// Get relative time (e.g., "pirms 2 stundām")
function getRelativeTime($datetime) {
    $timestamp = is_numeric($datetime) ? $datetime : strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) return 'tikko';
    if ($diff < 3600) return 'pirms ' . floor($diff / 60) . ' min';
    if ($diff < 86400) return 'pirms ' . floor($diff / 3600) . ' h';
    if ($diff < 604800) return 'pirms ' . floor($diff / 86400) . ' d';
    
    return formatLatvianDate($timestamp);
}

// Validate email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Validate password strength
function isValidPassword($password) {
    return strlen($password) >= 8;
}

// Send JSON response
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Error response
function errorResponse($message, $status = 400) {
    jsonResponse(['success' => false, 'message' => $message], $status);
}

// Success response
function successResponse($data, $message = null) {
    $response = ['success' => true];
    if ($message) $response['message'] = $message;
    $response = array_merge($response, $data);
    jsonResponse($response);
}
