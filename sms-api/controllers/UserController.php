<?php

require_once __DIR__ . '/../models/UserModel.php';

class UserController
{
    private UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    private function readJsonBody(): array
    {
        $raw = file_get_contents('php://input') ?: '';
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    private function respond(int $code, array $payload): void
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($payload);
        exit;
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
        if (!isset($_SESSION['user'])) $this->respond(401, ['error' => 'Not authenticated']);
        if (($_SESSION['user']['role'] ?? '') !== 'admin') $this->respond(403, ['error' => 'Forbidden']);

        $body = $this->readJsonBody();

        $role = strtolower(trim((string)($body['role'] ?? '')));
        $email = strtolower(trim((string)($body['email'] ?? '')));
        $password = (string)($body['password'] ?? '');
        $classId = $body['class_id'] ?? null;

        $allowedRoles = ['admin', 'teacher'];
        if (!in_array($role, $allowedRoles, true)) {
            $this->respond(422, ['error' => 'Invalid role. Allowed: admin, teacher']);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->respond(422, ['error' => 'Invalid email address']);
        }

        if (!preg_match('/(?=.*[A-Za-z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}/', $password)) {
            $this->respond(422, ['error' => 'Password must be at least 8 characters and include letters, numbers and a symbol.']);
        }

        if ($role === 'teacher') {
            if ($classId === null || (int)$classId <= 0) {
                $this->respond(422, ['error' => 'class_id is required for teacher']);
            }
            $classId = (int)$classId;
            if (!$this->userModel->classExists($classId)) {
                $this->respond(404, ['error' => 'class_id not found']);
            }
        } else {
            $classId = null;
        }

        if ($this->userModel->emailExists($email)) {
            $this->respond(409, ['error' => 'Email already exists']);
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        if ($hash === false) {
            $this->respond(500, ['error' => 'Could not hash password']);
        }

        try {
            $created = $this->userModel->createUser($role, $email, $hash, $classId);

            if (empty($created)) {
                $this->respond(500, ['error' => 'User created but could not be retrieved']);
            }

            $this->respond(201, [
                'success' => true,
                'user' => $created
            ]);
        } catch (Throwable $e) {
            $this->respond(500, ['error' => 'Server error creating user']);
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
        if (!isset($_SESSION['user'])) $this->respond(401, ['error' => 'Not authenticated']);
        if (($_SESSION['user']['role'] ?? '') !== 'admin') $this->respond(403, ['error' => 'Forbidden']);

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) $this->respond(400, ['error' => 'id is required']);

        $me = (int)($_SESSION['user']['id'] ?? 0);
        if ($me > 0 && $id === $me) {
            $this->respond(409, ['error' => 'You cannot delete your own account']);
        }

        $role = $this->userModel->getUserRoleById($id);
        if ($role === null) $this->respond(404, ['error' => 'User not found']);

        if ($role === 'admin') {
            $this->respond(403, ['error' => 'Admin users cannot be deleted']);
        }

        try {
            $deleted = $this->userModel->deleteUserById($id);
            if (!$deleted) $this->respond(404, ['error' => 'User not found']);

            $this->respond(200, ['success' => true]);
        } catch (Throwable $e) {
            $this->respond(500, ['error' => 'Server error deleting user']);
        }
    }
}
