<?php
// includes/csrf.php
if (session_status() === PHP_SESSION_NONE) session_start();

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_input() {
    $token = csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

/**
 * Validates CSRF token.
 * @param bool $die_on_fail If true, kills script on failure. If false, returns boolean.
 * @return bool
 */
function csrf_validate($die_on_fail = true) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        $is_valid = ($token && isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token));
        if (!$is_valid) {
            if ($die_on_fail) die('CSRF validation failed.');
            return false;
        }
    }
    return true;
}