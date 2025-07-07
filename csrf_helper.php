<?php
// csrf_helper.php

/**
 * Generate a new CSRF token and store it in the session.
 * Always call this before rendering a form.
 * @return string The generated CSRF token.
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // 32 bytes for a 64-char hex string
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate the submitted CSRF token against the one in the session.
 * Call this at the beginning of your form processing script (e.g., submit.php, process_login.php).
 * @param string $submitted_token The token received from the form.
 * @return bool True if tokens match and are valid, false otherwise.
 */
function validate_csrf_token($submitted_token) {
    if (empty($submitted_token) || empty($_SESSION['csrf_token'])) {
        return false; // No token submitted or no token in session
    }

    // Compare the submitted token with the session token
    if (hash_equals($_SESSION['csrf_token'], $submitted_token)) {
        // Token is valid, now invalidate it to prevent reuse (optional, but recommended for single-use tokens)
        // If your forms can be submitted multiple times on the same page load without refresh,
        // you might remove this line or regenerate the token AFTER successful validation.
        unset($_SESSION['csrf_token']);
        return true;
    }

    return false; // Tokens do not match
}

/**
 * Get the current CSRF token from the session.
 * Use this to embed the token in forms.
 * @return string|null The current CSRF token, or null if not set.
 */
function get_csrf_token() {
    return $_SESSION['csrf_token'] ?? null;
}
?>