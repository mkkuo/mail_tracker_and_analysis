<?php
// error_handler.php

// Flag to determine if running in development or production
// For simplicity, we'll use a constant. In a real app, this might come from an environment variable.
define('APP_ENV_DEV', true); // Set to false for production

/**
 * Custom exception handler.
 * Logs the error and displays a generic message or detailed info based on APP_ENV_DEV.
 */
function custom_exception_handler($exception) {
    // Ensure content type is HTML for proper error display
    if (!headers_sent()) {
        header('Content-Type: text/html; charset=UTF-8');
    }

    error_log("Uncaught Exception: " . $exception->getMessage() . 
              " in " . $exception->getFile() . ":" . $exception->getLine() . 
              "
Stack trace:
" . $exception->getTraceAsString());

    // Prevent outputting further content if headers already sent or in middle of output
    if (ob_get_level() > 0) {
        ob_end_clean(); // Clear any existing output buffers
    }

    if (APP_ENV_DEV) {
        // Display detailed error in development
        echo "<h1>Application Error (Development Mode)</h1>";
        echo "<p>An unexpected error occurred. Details:</p>";
        echo "<pre style='background-color: #f8f9fa; border: 1px solid #dee2e6; padding: 15px; border-radius: 4px; white-space: pre-wrap; word-wrap: break-word;'>";
        echo "<strong>Message:</strong> " . htmlspecialchars($exception->getMessage()) . "
";
        echo "<strong>File:</strong> " . htmlspecialchars($exception->getFile()) . ":" . $exception->getLine() . "
";
        echo "<strong>Stack trace:</strong>
" . htmlspecialchars($exception->getTraceAsString());
        echo "</pre>";
    } else {
        // Display generic message in production
        echo "<h1>Application Error</h1>";
        echo "<p>An unexpected error occurred. Please try again later.</p>";
        // Optionally, provide a reference number that can be correlated with logs.
        // $error_reference = uniqid('ERR_');
        // error_log("Error Reference: " . $error_reference . " for exception: " . $exception->getMessage());
        // echo "<p>Error Reference: " . $error_reference . "</p>";
    }
    exit; // Stop script execution
}

/**
 * Custom error handler.
 * Converts errors to ErrorExceptions and throws them to be caught by the exception handler.
 * Only handles errors based on error_reporting level.
 */
function custom_error_handler($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        // This error code is not included in error_reporting
        return false; // Let PHP's standard error handler handle it
    }
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}

// Set the custom handlers
set_exception_handler('custom_exception_handler');
set_error_handler('custom_error_handler');

// Optional: Register a shutdown function to catch fatal errors (if not caught by error/exception handlers)
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR])) {
        // Manually call the exception handler for fatal errors
        // Create a new ErrorException to pass to the handler
        // Check if headers have been sent to prevent errors during error display itself
        if (!headers_sent()) {
             // If custom_exception_handler has not already been triggered (e.g. via exit)
            custom_exception_handler(new ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']));
        } else {
            // Fallback if headers are already sent, just log it
            error_log("Fatal error (headers already sent): Type: " . $error['type'] . " - Message: " . $error['message'] . " in " . $error['file'] . ":" . $error['line']);
        }
    }
});

// Ensure errors are reported so they can be handled (good for development)
// In production, error_reporting should be set to a level that doesn't display errors to users,
// but our handlers will take care of user-facing messages.
error_reporting(E_ALL); 
ini_set('display_errors', APP_ENV_DEV ? '1' : '0'); // Let handler control display in prod
ini_set('display_startup_errors', APP_ENV_DEV ? '1' : '0'); // Also for startup errors
ini_set('log_errors', '1'); // Ensure errors are logged regardless
// Consider setting ini_set('error_log', '/app/php_error.log'); if not configured in php.ini and if /app is writable by the server
// For this environment, errors will go to stderr/stdout of the php process, which is usually captured by the server logs.
?>
