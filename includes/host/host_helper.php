<?php
/**
 * Host Helper - Utility functions for handling URLs with ngrok support
 * 
 * This file provides functions to detect the current host environment
 * and generate appropriate URLs whether running locally or through ngrok.
 */

/**
 * Get the base URL for the application
 * 
 * @return string The base URL including protocol and host
 */
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    
    // Get the path to the application root
    $appPath = dirname(dirname($_SERVER['SCRIPT_NAME']));
    $appPath = $appPath === '\\' || $appPath === '/' ? '' : $appPath;
    
    return $protocol . $host . $appPath;
}

/**
 * Check if the application is being accessed through ngrok
 * 
 * @return bool True if accessed through ngrok, false otherwise
 */
function isNgrokRequest() {
    return strpos($_SERVER['HTTP_HOST'], 'ngrok') !== false;
}

/**
 * Generate a URL for an asset
 * 
 * @param string $path The relative path to the asset
 * @return string The full URL to the asset
 */
function getAssetUrl($path) {
    // Remove leading slash if present
    $path = ltrim($path, '/');
    
    return getBaseUrl() . '/' . $path;
}

/**
 * Generate a URL for a page
 * 
 * @param string $page The relative path to the page
 * @return string The full URL to the page
 */
function getPageUrl($page) {
    // Remove leading slash if present
    $page = ltrim($page, '/');
    
    return getBaseUrl() . '/pages/' . $page;
}

/**
 * Get the current environment information
 * 
 * @return array Information about the current environment
 */
function getEnvironmentInfo() {
    return [
        'base_url' => getBaseUrl(),
        'is_ngrok' => isNgrokRequest(),
        'server_name' => $_SERVER['SERVER_NAME'],
        'http_host' => $_SERVER['HTTP_HOST'],
        'script_name' => $_SERVER['SCRIPT_NAME'],
        'request_uri' => $_SERVER['REQUEST_URI']
    ];
}
