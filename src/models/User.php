<?php

class User
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function findById(int $id): array|false
    {
        $stmt = $this->db->prepare(
            "SELECT id, username, email, is_verified, notify_comments,
                    created_at
             FROM users
             WHERE id = :id"
        );

        $stmt->execute(['id' => $id]);

        return $stmt->fetch();
    }

    public function findByEmail(string $email): array|false
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM users WHERE email = :email"
        );

        $stmt->execute(['email' => $email]);

        return $stmt->fetch();
    }

    public function findByUsername(string $username): array|false
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM users WHERE username = :username"
        );

        $stmt->execute(['username' => $username]);

        return $stmt->fetch();
    }

    public function create(
        string $username,
        string $email,
        string $passwordHash
    ): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO users (username, email, password)
             VALUES (:username, :email, :password)"
        );

        $stmt->execute([
            'username' => $username,
            'email' => $email,
            'password' => $passwordHash
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function updateUsername(int $id, string $username): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE users SET username = :username WHERE id = :id"
        );

        return $stmt->execute([
            'username' => $username,
            'id' => $id
        ]);
    }

    public function updateEmail(int $id, string $email): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE users SET email = :email,
                             is_verified = FALSE
             WHERE id = :id"
        );

        return $stmt->execute([
            'email' => $email,
            'id' => $id
        ]);
    }

    public function updatePassword(int $id, string $passwordHash): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE users SET password = :password WHERE id = :id"
        );

        return $stmt->execute([
            'password' => $passwordHash,
            'id' => $id
        ]);
    }

    public function updateNotificationPreference(
        int $id,
        bool $enabled
    ): bool {
        $stmt = $this->db->prepare(
            "UPDATE users
             SET notify_comments = :enabled
             WHERE id = :id"
        );

        return $stmt->execute([
            'enabled' => $enabled,
            'id' => $id
        ]);
    }

    public function createToken(
        int $userId,
        string $tokenHash,
        string $type,
        string $expiresAt
    ): bool {
        // Remove existing tokens of the same type for this user.
        $delete = $this->db->prepare(
            "DELETE FROM tokens WHERE user_id = :user_id AND type = :type"
        );

        $delete->execute([
            'user_id' => $userId,
            'type' => $type
        ]);

        $stmt = $this->db->prepare(
            "INSERT INTO tokens (user_id, token_hash, type, expires_at)
            VALUES (:user_id, :token_hash, :type, :expires_at)"
        );

        return $stmt->execute([
            'user_id' => $userId,
            'token_hash' => $tokenHash,
            'type' => $type,
            'expires_at' => $expiresAt
        ]);
    }

    public function findValidToken(
        string $tokenHash,
        string $type
    ): array|false {
        $stmt = $this->db->prepare(
            "SELECT * FROM tokens
            WHERE token_hash = :token_hash
            AND type = :type
            AND expires_at > NOW()"
        );

        $stmt->execute([
            'token_hash' => $tokenHash,
            'type' => $type
        ]);

        return $stmt->fetch();
    }

    public function deleteToken(int $tokenId): bool
    {
        $stmt = $this->db->prepare(
            "DELETE FROM tokens WHERE id = :id"
        );

        return $stmt->execute(['id' => $tokenId]);
    }

    public function verifyUser(int $id): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE users SET is_verified = TRUE WHERE id = :id"
        );

        return $stmt->execute(['id' => $id]);
    }

    public function updatePasswordById(
        int $id,
        string $passwordHash
    ): bool {
        return $this->updatePassword($id, $passwordHash);
    }

    public function findPasswordHashById(int $id): string|false
    {
        $stmt = $this->db->prepare(
            "SELECT password FROM users WHERE id = :id"
        );

        $stmt->execute(['id' => $id]);

        $row = $stmt->fetch();

        return $row ? $row['password'] : false;
    }
}
