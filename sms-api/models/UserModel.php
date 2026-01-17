<?php

require_once __DIR__ . '/../config/db.php';

class UserModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function emailExists(string $email): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        return (bool)$stmt->fetchColumn();
    }

    public function classExists(int $classId): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM classes WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $classId]);
        return (bool)$stmt->fetchColumn();
    }

    /**
     * Creates a user. Returns the created user row (without password_hash for security).
     */

    public function createUser(string $role, string $email, string $passwordHash, ?int $classId): array
    {
        $sql = "INSERT INTO users (role, email, password_hash, class_id)
                VALUES (:role, :email, :password_hash, :class_id)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':role'          => $role,
            ':email'         => $email,
            ':password_hash' => $passwordHash,
            ':class_id'      => $classId,
        ]);

        $id = (int)$this->db->lastInsertId();

        return $this->getUserById($id);
    }

    public function getUserById(int $id): array
    {
        $sql = "SELECT id, role, email, class_id FROM users WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) return [];
        $row['id'] = (int)$row['id'];
        $row['class_id'] = $row['class_id'] !== null ? (int)$row['class_id'] : null;

        return $row;
    }

    public function getUserRoleById(int $id): ?string
    {
        $stmt = $this->db->prepare("SELECT role FROM users WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $role = $stmt->fetchColumn();
        return $role !== false ? (string)$role : null;
    }

    public function deleteUserById(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }
}
