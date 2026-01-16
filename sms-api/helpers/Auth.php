<?php

namespace helpers;

require_once __DIR__ . '/JsonHelpers.php';

class Auth
{
    private static function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function user(): ?array
    {
        self::startSession();
        return $_SESSION['user'] ?? null;
    }

    public static function requireLogin(): array
    {
        $user = self::user();

        if (!$user) {
            JsonHelpers::json(401, [
                'success' => false,
                'message' => 'Not authenticated.'
            ]);
        }

        return $user;
    }

    /**
     * @param string|string[] $roles Allowed role(s)
     */

    public static function requireRole(string|array $roles): array
    {
        $user = self::requireLogin();

        $allowed = is_array($roles) ? $roles : [$roles];

        if (!in_array($user['role'], $allowed, true)) {
            JsonHelpers::json(403, [
                'success' => false,
                'message' => 'Forbidden: insufficient permissions.'
            ]);
        }

        return $user;
    }
}
