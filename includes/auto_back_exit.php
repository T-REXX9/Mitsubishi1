<?php
// Auto-appended script to intercept back navigation and exit site for authenticated users

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

// Apply strict no-store caching headers for PHP responses
if (!headers_sent()) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
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
         'if(history.replaceState){ history.replaceState(null, document.title, location.href); }\n' .
         'if(history.pushState){ history.pushState(null, document.title, location.href); }\n' .
         'window.addEventListener("popstate", function(){\n' .
         '  if(pushed){ location.reload(); return; }\n' .
         '  pushed=true; if(history.pushState){ history.pushState(null, document.title, location.href); }\n' .
         '});\n' .
         'window.addEventListener("pageshow", function(e){\n' .
         '  var navType = (performance && performance.getEntriesByType) ? (function(){var n=performance.getEntriesByType("navigation"); return n && n[0] ? n[0].type : null;})() : null;\n' .
         '  if((e && e.persisted) || navType === "back_forward"){ location.reload(); }\n' .
         '});\n' .
         '}catch(e){/* noop */}})();</script>';
}
