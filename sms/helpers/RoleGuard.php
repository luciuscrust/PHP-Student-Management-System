<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * This is a healper function that guards a page by role.
 *
 * @param string|array $allowedRoles Role or roles allowed to view the page
 * @param string $redirect Where to send unauthorized users (if empty, they will be redirected to their dashboard through the login page)
 */

function requireRole(string|array $allowedRoles, string $redirect = "http://localhost/PHP-Student-Management-System/sms/index.php"): void
{
    if (empty($_SESSION['user']) || empty($_SESSION['user']['role'])) {
        header("Location: $redirect");
        exit;
    }

    $allowedRoles = (array) $allowedRoles;
    $userRole = $_SESSION['user']['role'];

    if (!in_array($userRole, $allowedRoles, true)) {
        header("Location: $redirect");
        exit;
    }
}
