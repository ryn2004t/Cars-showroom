<?php
/**
 * CSRF Protection Implementation
 * Simple and secure CSRF token management
 */

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Generate CSRF token
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Generate CSRF input field for forms
 */
function csrf_input_field() {
    $token = generate_csrf_token();
    echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Verify CSRF token - returns boolean
 */
function verify_csrf_token() {
    if (!isset($_SESSION['csrf_token']) || !isset($_POST['csrf_token'])) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

/**
 * Verify CSRF token with error message (legacy support)
 */
function verify_csrf_token_or_die() {
    if (!verify_csrf_token()) {
        echo "<p style='color:red;'>⛔ CSRF token mismatch. Try reloading the page.</p>";
        exit;
    }
    return true;
}

/**
 * Get CSRF token (for AJAX requests)
 */
function get_csrf_token() {
    return generate_csrf_token();
}

/**
 * Refresh CSRF token (call after successful form submission)
 */
function refresh_csrf_token() {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}
?>