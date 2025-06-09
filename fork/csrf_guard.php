<?php
// csrf_guard.php

if (session_status() == PHP_SESSION_NONE) {
    session_start(); // Ensure session is started
}

/**
 * Generates or retrieves a CSRF token.
 * @return string The CSRF token.
 */
function get_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validates a CSRF token.
 * @param string $token The token from the form.
 * @return bool True if valid, false otherwise.
 */
function validate_csrf_token($token) {
    if (isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token)) {
        // Optional: Regenerate token after successful validation for one-time use
        // $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); 
        return true;
    }
    return false;
}

/**
 * Outputs a hidden input field with the CSRF token.
 */
function csrf_input_field() {
    $token = get_csrf_token();
    echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

/**
 * Verifies the CSRF token from POST data. Dies if invalid.
 * Call this at the beginning of scripts that process POST requests.
 */
function verify_csrf_or_die() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (!validate_csrf_token($token)) {
            // Log the attempt (optional)
            // error_log("CSRF token validation failed for user: " . ($_SESSION['user_id'] ?? 'guest') . " from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
            die("❌ Invalid CSRF token. Request blocked.");
        }
    }
}
?>
