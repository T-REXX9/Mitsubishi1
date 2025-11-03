<!-- Mobile Responsiveness Fix - Optimized 2025 Standards -->
<!-- Consolidated & optimized from all mobile fix files -->
<!-- Features: Em-based breakpoints, fluid typography, reduced !important usage -->

<!-- Optimized Mobile Responsive CSS -->
<link rel="stylesheet" href="<?php echo isset($css_path) ? $css_path : '../css/'; ?>mobile-responsive-optimized.css">

<!-- Mobile Responsive JavaScript -->
<script src="<?php echo isset($js_path) ? $js_path : '../js/'; ?>mobile-responsive-fix.js" defer></script>

<!-- Inline critical mobile fixes for immediate effect -->
<style>
html, body {
  overflow-x: hidden !important;
  max-width: 100vw;
}
* { box-sizing: border-box; }
</style>
