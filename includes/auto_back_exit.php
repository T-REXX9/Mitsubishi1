<?php
// Auto-appended script to intercept back navigation and exit site for authenticated users

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

// Only inject for HTML responses and when a user session exists
$isHtml = true;
foreach (headers_list() as $h) {
    if (stripos($h, 'Content-Type:') === 0 && stripos($h, 'text/html') === false) {
        $isHtml = false;
        break;
    }
}

if (!empty($_SESSION['user_id']) && $isHtml) {
    echo '<script>(function(){try{\n' .
         'var pushed=false;\n' .
         'history.replaceState && history.replaceState(null, document.title, location.href);\n' .
         'history.pushState && history.pushState(null, document.title, location.href);\n' .
         'window.addEventListener("popstate", function(){\n' .
         '  if(pushed){ window.location.href = "about:blank"; return; }\n' .
         '  pushed=true; history.pushState(null, document.title, location.href);\n' .
         '});\n' .
         'window.addEventListener("pageshow", function(e){ if(e.persisted){ window.location.href="about:blank"; } });\n' .
         '}catch(e){/* noop */}})();</script>';
}
?>


