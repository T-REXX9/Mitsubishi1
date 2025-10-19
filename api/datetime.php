<?php
/**
 * Date and Time API
 * Provides current date and time in Philippine Standard Time (PST/PHT)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Set timezone to Philippine Standard Time
date_default_timezone_set('Asia/Manila');

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            $action = $_GET['action'] ?? 'current';
            
            switch ($action) {
                case 'current':
                    getCurrentDateTime();
                    break;
                case 'date':
                    getCurrentDate();
                    break;
                case 'time':
                    getCurrentTime();
                    break;
                case 'timestamp':
                    getCurrentTimestamp();
                    break;
                default:
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Invalid action. Available actions: current, date, time, timestamp'
                    ]);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'message' => 'Method not allowed. Only GET requests are supported.'
            ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error',
        'error' => $e->getMessage()
    ]);
}

/**
 * Get current date and time in various formats
 */
function getCurrentDateTime() {
    $now = new DateTime('now', new DateTimeZone('Asia/Manila'));
    
    echo json_encode([
        'success' => true,
        'data' => [
            'datetime' => $now->format('Y-m-d H:i:s'),
            'date' => $now->format('Y-m-d'),
            'time' => $now->format('H:i:s'),
            'time_12hr' => $now->format('h:i:s A'),
            'timestamp' => $now->getTimestamp(),
            'timezone' => 'Asia/Manila (PST/PHT)',
            'timezone_offset' => $now->format('P'),
            'day_of_week' => $now->format('l'),
            'month' => $now->format('F'),
            'year' => $now->format('Y'),
            'formatted' => [
                'full' => $now->format('l, F j, Y g:i:s A'),
                'short' => $now->format('M j, Y g:i A'),
                'iso8601' => $now->format('c')
            ]
        ]
    ]);
}

/**
 * Get current date only
 */
function getCurrentDate() {
    $now = new DateTime('now', new DateTimeZone('Asia/Manila'));
    
    echo json_encode([
        'success' => true,
        'data' => [
            'date' => $now->format('Y-m-d'),
            'formatted' => [
                'full' => $now->format('l, F j, Y'),
                'short' => $now->format('M j, Y'),
                'day' => $now->format('d'),
                'month' => $now->format('m'),
                'year' => $now->format('Y')
            ],
            'day_of_week' => $now->format('l'),
            'day_of_week_short' => $now->format('D'),
            'timezone' => 'Asia/Manila (PST/PHT)'
        ]
    ]);
}

/**
 * Get current time only
 */
function getCurrentTime() {
    $now = new DateTime('now', new DateTimeZone('Asia/Manila'));
    
    echo json_encode([
        'success' => true,
        'data' => [
            'time_24hr' => $now->format('H:i:s'),
            'time_12hr' => $now->format('h:i:s A'),
            'hour_24' => $now->format('H'),
            'hour_12' => $now->format('h'),
            'minute' => $now->format('i'),
            'second' => $now->format('s'),
            'am_pm' => $now->format('A'),
            'timezone' => 'Asia/Manila (PST/PHT)',
            'timezone_offset' => $now->format('P')
        ]
    ]);
}

/**
 * Get current Unix timestamp
 */
function getCurrentTimestamp() {
    $now = new DateTime('now', new DateTimeZone('Asia/Manila'));
    
    echo json_encode([
        'success' => true,
        'data' => [
            'timestamp' => $now->getTimestamp(),
            'milliseconds' => round(microtime(true) * 1000),
            'timezone' => 'Asia/Manila (PST/PHT)',
            'datetime' => $now->format('Y-m-d H:i:s')
        ]
    ]);
}

