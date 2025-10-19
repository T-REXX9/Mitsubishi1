<?php
/**
 * Date and Time API - PST (Pacific Standard Time)
 * 
 * Provides current date and time in PST timezone
 * Supports GET requests with optional formatting parameters
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle OPTIONS request for CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method Not Allowed. Only GET requests are supported.'
    ]);
    exit;
}

try {
    // Set timezone to PST (Pacific Standard Time)
    // Note: PST is UTC-8, PDT (Pacific Daylight Time) is UTC-7
    // Using America/Los_Angeles automatically handles PST/PDT transitions
    $timezone = new DateTimeZone('America/Los_Angeles');
    $datetime = new DateTime('now', $timezone);
    
    // Get optional format parameter (default: ISO 8601)
    $format = $_GET['format'] ?? 'iso';
    
    // Prepare response data
    $response = [
        'success' => true,
        'timezone' => 'America/Los_Angeles (PST/PDT)',
        'timezone_abbreviation' => $datetime->format('T'), // PST or PDT
        'timezone_offset' => $datetime->format('P'), // e.g., -08:00 or -07:00
        'is_dst' => (bool)$datetime->format('I'), // Daylight Saving Time flag
        'timestamp' => $datetime->getTimestamp(),
    ];
    
    // Add formatted datetime based on requested format
    switch (strtolower($format)) {
        case 'iso':
        case 'iso8601':
            $response['datetime'] = $datetime->format('c'); // ISO 8601 format
            $response['format'] = 'ISO 8601';
            break;
            
        case 'rfc':
        case 'rfc2822':
            $response['datetime'] = $datetime->format('r'); // RFC 2822 format
            $response['format'] = 'RFC 2822';
            break;
            
        case 'sql':
        case 'mysql':
            $response['datetime'] = $datetime->format('Y-m-d H:i:s'); // MySQL datetime format
            $response['format'] = 'SQL/MySQL';
            break;
            
        case 'us':
        case 'american':
            $response['datetime'] = $datetime->format('m/d/Y h:i:s A'); // US format
            $response['format'] = 'US Format';
            break;
            
        case 'full':
        case 'readable':
            $response['datetime'] = $datetime->format('l, F j, Y g:i:s A T'); // Full readable format
            $response['format'] = 'Full Readable';
            break;
            
        case 'custom':
            // Allow custom format via 'pattern' parameter
            $pattern = $_GET['pattern'] ?? 'Y-m-d H:i:s';
            $response['datetime'] = $datetime->format($pattern);
            $response['format'] = 'Custom';
            $response['pattern'] = $pattern;
            break;
            
        case 'all':
            // Return all common formats
            $response['formats'] = [
                'iso8601' => $datetime->format('c'),
                'rfc2822' => $datetime->format('r'),
                'sql' => $datetime->format('Y-m-d H:i:s'),
                'us' => $datetime->format('m/d/Y h:i:s A'),
                'readable' => $datetime->format('l, F j, Y g:i:s A T'),
                'date_only' => $datetime->format('Y-m-d'),
                'time_only' => $datetime->format('H:i:s'),
                'time_12h' => $datetime->format('h:i:s A'),
            ];
            $response['format'] = 'All Formats';
            break;
            
        default:
            // Default to ISO 8601
            $response['datetime'] = $datetime->format('c');
            $response['format'] = 'ISO 8601 (default)';
            break;
    }
    
    // Add separate date and time components for convenience
    if ($format !== 'all') {
        $response['components'] = [
            'date' => $datetime->format('Y-m-d'),
            'time_24h' => $datetime->format('H:i:s'),
            'time_12h' => $datetime->format('h:i:s A'),
            'year' => (int)$datetime->format('Y'),
            'month' => (int)$datetime->format('m'),
            'day' => (int)$datetime->format('d'),
            'hour' => (int)$datetime->format('H'),
            'minute' => (int)$datetime->format('i'),
            'second' => (int)$datetime->format('s'),
            'day_of_week' => $datetime->format('l'),
            'day_of_year' => (int)$datetime->format('z'),
            'week_of_year' => (int)$datetime->format('W'),
        ];
    }
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal Server Error: ' . $e->getMessage()
    ]);
}
exit;

