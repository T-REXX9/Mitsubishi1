<?php
/**
 * PhilSMS API configuration.
 *
 * Loads API token from environment variables for security.
 * Set PHILSMS_API_TOKEN in your .env file.
 */

// Load environment variables if not already loaded
$env_loader_path = __DIR__ . '/../includes/utils/EnvLoader.php';
if (file_exists($env_loader_path)) {
    require_once $env_loader_path;
    EnvLoader::load();
}

// Get API token from environment variable
$api_token = getenv('PHILSMS_API_TOKEN');

// Fallback for backward compatibility (not recommended)
if (!$api_token) {
    $api_token = '2727|bb03dgKcJI26H18Ai1to1TRIU0MBUxUly21xjXoQ';
    error_log('WARNING: PhilSMS API token not found in .env file. Using hardcoded fallback. Please set PHILSMS_API_TOKEN in .env');
}

return [
    'api_token' => $api_token,
    // Optional default sender ID (alphanumeric, typically 3-11 chars). If empty, 'PhilSMS' will be used.
    'default_sender_id' => 'PhilSMS',
];