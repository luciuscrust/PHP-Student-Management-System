<?php

use helpers\JsonHelpers;

require_once __DIR__ . '/../models/UserModel.php';

class UserController
{
    private UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    /**
     * POST /users
     * Auth: admin
     *
     * Body (JSON):
     * {
     *   "role": "teacher" | "admin",
     *   "email": "x@y.com",
     *   "password": "plainPassword123#" (this is an example),
     *   "class_id": 12 // required if role=teacher, if null then user role=admin
     * }
     */

    public function createUser(): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!isset($_SESSION['user'])) {
            JsonHelpers::json(401, ['error' => 'Not authenticated']);
        }

        if (($_SESSION['user']['role'] ?? '') !== 'admin') {
            JsonHelpers::json(403, ['error' => 'Forbidden']);
        }

        $body = JsonHelpers::getBody();

        $role = strtolower(trim((string)($body['role'] ?? '')));
        $email = strtolower(trim((string)($body['email'] ?? '')));
        $password = (string)($body['password'] ?? '');
        $classId = $body['class_id'] ?? null;

        $allowedRoles = ['admin', 'teacher'];
        if (!in_array($role, $allowedRoles, true)) {
            JsonHelpers::json(422, ['error' => 'Invalid role. Allowed: admin, teacher']);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            JsonHelpers::json(422, ['error' => 'Invalid email address']);
        }

        if (!preg_match('/(?=.*[A-Za-z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}/', $password)) {
            JsonHelpers::json(422, [
                'error' => 'Password must be at least 8 characters and include letters, numbers and a symbol.'
            ]);
        }

        if ($role === 'teacher') {
            if ($classId === null || (int)$classId <= 0) {
                JsonHelpers::json(422, ['error' => 'class_id is required for teacher']);
            }

            $classId = (int)$classId;

            if (!$this->userModel->classExists($classId)) {
                JsonHelpers::json(404, ['error' => 'class_id not found']);
            }
        } else {
            $classId = null;
        }

        if ($this->userModel->emailExists($email)) {
            JsonHelpers::json(409, ['error' => 'Email already exists']);
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        if ($hash === false) {
            JsonHelpers::json(500, ['error' => 'Could not hash password']);
        }

        try {
            $created = $this->userModel->createUser($role, $email, $hash, $classId);

            if (empty($created)) {
                JsonHelpers::json(500, ['error' => 'User created but could not be retrieved']);
            }

            JsonHelpers::json(201, [
                'success' => true,
                'user' => $created
            ]);
        } catch (Throwable $e) {
            JsonHelpers::json(500, ['error' => 'Server error creating user']);
        }
    }

    /**
     * DELETE /users?id=123
     * Auth: admin
     * Rules:
     * - admin users cannot be deleted
     * - user cannot delete themself
     */

    public function deleteUser(): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!isset($_SESSION['user'])) {
            JsonHelpers::json(401, ['error' => 'Not authenticated']);
        }

        if (($_SESSION['user']['role'] ?? '') !== 'admin') {
            JsonHelpers::json(403, ['error' => 'Forbidden']);
        }

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            JsonHelpers::json(400, ['error' => 'id is required']);
        }

        $me = (int)($_SESSION['user']['id'] ?? 0);
        if ($me > 0 && $id === $me) {
            JsonHelpers::json(409, ['error' => 'You cannot delete your own account']);
        }

        $role = $this->userModel->getUserRoleById($id);
        if ($role === null) {
            JsonHelpers::json(404, ['error' => 'User not found']);
        }

        if ($role === 'admin') {
            JsonHelpers::json(403, ['error' => 'Admin users cannot be deleted']);
        }

        try {
            $deleted = $this->userModel->deleteUserById($id);
            if (!$deleted) {
                JsonHelpers::json(404, ['error' => 'User not found']);
            }

            JsonHelpers::json(200, ['success' => true]);
        } catch (Throwable $e) {
            JsonHelpers::json(500, ['error' => 'Server error deleting user']);
        }
    }
}
