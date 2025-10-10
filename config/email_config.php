<?php
/**
 * Mailgun Email Configuration File
 * Configure your Mailgun API settings here
 */

// Mailgun configuration settings
$mailgun_config = [
    'api_key' => 'your-mailgun-api-key-here',  // Replace with your actual Mailgun API key
    'domain' => 'your-domain.mailgun.org',     // Replace with your Mailgun domain
    'from_email' => 'noreply@mitsubishi-motors.com',
    'from_name' => 'Mitsubishi Motors'
];

/**
 * Instructions for setting up Mailgun:
 * 
 * 1. Sign up for a Mailgun account at https://www.mailgun.com/
 * 2. Verify your domain or use the sandbox domain for testing
 * 3. Get your API key from the Mailgun dashboard
 * 4. Replace the values above with your actual Mailgun credentials
 * 
 * Example configuration:
 * $mailgun_config = [
 *     'api_key' => 'key-1234567890abcdef1234567890abcdef',
 *     'domain' => 'sandboxabcdef1234567890.mailgun.org',
 *     'from_email' => 'noreply@yourdomain.com',
 *     'from_name' => 'Your Company Name'
 * ];
 */
?>