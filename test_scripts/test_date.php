<?php
// Test script to verify date functionality for inquiry.php

echo "<h1>Date Functionality Test</h1>";
echo "<style>body{font-family:Arial;margin:20px;}.ok{color:green;}.info{color:blue;}.warning{color:orange;}</style>";

// Test 1: Default timezone
echo "<h2>Test 1: Default Timezone</h2>";
echo "<p class='info'>Default timezone: " . date_default_timezone_get() . "</p>";

// Test 2: Set Philippines timezone and display date
echo "<h2>Test 2: Philippines Timezone</h2>";
date_default_timezone_set('Asia/Manila');
echo "<p class='ok'>✓ Timezone set to: " . date_default_timezone_get() . "</p>";
echo "<p>Current date (server-side): " . date('F j, Y') . "</p>";
echo "<p>Current time (server-side): " . date('F j, Y g:i:s A') . "</p>";

// Test 3: JavaScript date test
echo "<h2>Test 3: JavaScript Date (Client-side)</h2>";
echo "<p>JavaScript date: <span id='js-date'>Loading...</span></p>";
echo "<p>JavaScript time: <span id='js-time'>Loading...</span></p>";

// Test 4: Comparison
echo "<h2>Test 4: Server vs Client Comparison</h2>";
echo "<p>Server date: <strong>" . date('F j, Y') . "</strong></p>";
echo "<p>Client date: <strong><span id='client-date'>Loading...</span></strong></p>";

?>

<script>
// JavaScript date test
function updateDates() {
    // Set timezone to Philippines (UTC+8)
    const now = new Date();
    const philippinesOffset = 8 * 60; // UTC+8 in minutes
    const utc = now.getTime() + (now.getTimezoneOffset() * 60000);
    const philippinesTime = new Date(utc + (philippinesOffset * 60000));
    
    // Format date as "Month Day, Year"
    const dateOptions = { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric',
        timeZone: 'Asia/Manila'
    };
    
    const timeOptions = { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric',
        hour: 'numeric',
        minute: 'numeric',
        second: 'numeric',
        hour12: true,
        timeZone: 'Asia/Manila'
    };
    
    const formattedDate = philippinesTime.toLocaleDateString('en-US', dateOptions);
    const formattedTime = philippinesTime.toLocaleDateString('en-US', timeOptions);
    
    // Update displays
    document.getElementById('js-date').textContent = formattedDate;
    document.getElementById('js-time').textContent = formattedTime;
    document.getElementById('client-date').textContent = formattedDate;
}

// Update dates when page loads
document.addEventListener('DOMContentLoaded', updateDates);

// Update every second for demonstration
setInterval(updateDates, 1000);
</script>

<p><a href="pages/inquiry.php">Test Inquiry Page</a></p>