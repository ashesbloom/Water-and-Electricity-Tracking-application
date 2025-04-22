<?php
// Define base path if not already defined globally
if (!defined('BASE_URL_PATH')) {
    // Use @ to suppress errors if already defined
    @define('BASE_URL_PATH', '/tracker'); 
}
?>
<footer id="app-footer" class="scroll-animate scroll-animate-init mt-12 py-4 text-light-text-secondary dark:text-dark-text-secondary border-t border-light-border dark:border-dark-border overflow-hidden">
    <div class="animated-svg-container h-8 mb-2">
        <svg width="100%" height="100%" viewBox="0 0 400 30" preserveAspectRatio="none">
            <path class="animated-path" d="M0,15 L80,15 L90,5 L100,25 L110,15 L290,15 L300,5 L310,25 L320,15 L400,15"
                  stroke-width="1.5" fill="none"
                  stroke-dasharray="950"
                  stroke-dashoffset="950" 
                  stroke-linecap="round" 
                  stroke-linejoin="round" 
            />
        </svg>
    </div>
    <p class="footer-end-text text-center text-xs">
        You have reached the end of the page
    </p>
</footer>
<script src="<?php echo BASE_URL_PATH; ?>/JavaScripts/partials_script.js"></script> 
