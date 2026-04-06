<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\User;
use PDO;
use DateTimeImmutable;

final class UserRepository
{
    private readonly PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getPdo();
    }

    /**
     * Find user by username (for login).
     */
    public function findByUsername(string $username): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, username, email, password_hash, created_at FROM users WHERE username = :username LIMIT 1",
        );
        $stmt->execute([":username" => $username]);
        $row = $stmt->fetch();

        return $row !== false ? (array) $row : null;
    }

    /**
     * Find user by ID.
     */
    public function findById(int $id): ?User
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, username, email, created_at FROM users WHERE id = :id LIMIT 1",
        );
        $stmt->execute([":id" => $id]);
        $row = $stmt->fetch();

        return $row !== false ? User::fromRow($row) : null;
    }

    /**
     * Create a new user.
     *
     * @throws \RuntimeException if username/email already exists
     */
    public function create(
        string $username,
        string $email,
        string $passwordHash,
    ): int {
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO users (username, email, password_hash)
                 VALUES (:username, :email, :password_hash)',
            );

            $stmt->execute([
                ":username" => $username,
                ":email" => $email,
                ":password_hash" => $passwordHash,
            ]);

            return (int) $this->pdo->lastInsertId();
        } catch (\PDOException $e) {
            // Catch duplicate key errors
            if ($e->getCode() === "23000") {
                throw new \RuntimeException(
                    "Username or email already exists.",
                    previous: $e,
                );
            }
            throw $e;
        }
    }

    /**
     * Check if username or email exists (for registration validation).
     */
    public function existsByUsernameOrEmail(string $username, string $email): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM users WHERE username = :username OR email = :email",
        );
        $stmt->execute([":username" => $username, ":email" => $email]);

        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Create a remember-me token for persistent login.
     * Returns the plain token (store in cookie). Token_hash is stored in DB.
     *
     * @param int $userId User ID
     * @param int $validDays Token validity in days (default: 30)
     * @return string Plain token (to set in cookie)
     */
    public function createRememberToken(int $userId, int $validDays = 30): string
    {
        $plainToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $plainToken);
        $expiresAt = (new DateTimeImmutable())->modify("+{$validDays} days");

        $stmt = $this->pdo->prepare(
            'INSERT INTO remember_tokens (user_id, token_hash, expires_at)
             VALUES (:user_id, :token_hash, :expires_at)',
        );

        $stmt->execute([
            ":user_id" => $userId,
            ":token_hash" => $tokenHash,
            ":expires_at" => $expiresAt->format('Y-m-d H:i:s'),
        ]);

        return $plainToken;
    }

    /**
     * Verify a remember-me token and return the associated user.
     * Validates expiry and deletes expired tokens.
     * Rotates valid tokens (creates new one, deletes old one).
     *
     * @return array{user: User, newToken: string}|null
     */
    public function verifyRememberToken(string $plainToken): ?array
    {
        $tokenHash = hash('sha256', $plainToken);

        $stmt = $this->pdo->prepare(
            'SELECT rt.id, rt.user_id, rt.expires_at,
                    u.id as uid, u.username, u.email, u.created_at
             FROM remember_tokens rt
             JOIN users u ON rt.user_id = u.id
             WHERE rt.token_hash = :token_hash LIMIT 1',
        );

        $stmt->execute([":token_hash" => $tokenHash]);
        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        // Check expiry
        $expiresAt = new DateTimeImmutable($row["expires_at"]);
        if ($expiresAt < new DateTimeImmutable()) {
            // Token expired, delete it
            $deleteStmt = $this->pdo->prepare(
                "DELETE FROM remember_tokens WHERE id = :id",
            );
            $deleteStmt->execute([":id" => (int) $row["id"]]);

            return null;
        }

        // Token is valid. Rotate it: delete old, create new.
        $deleteStmt = $this->pdo->prepare(
            "DELETE FROM remember_tokens WHERE id = :id",
        );
        $deleteStmt->execute([":id" => (int) $row["id"]]);

        $newToken = $this->createRememberToken((int) $row["user_id"]);

        // Reconstruct user object
        $user = new User(
            id: (int) $row["uid"],
            username: (string) $row["username"],
            email: (string) $row["email"],
            createdAt: new DateTimeImmutable($row["created_at"]),
        );

        return [
            "user" => $user,
            "newToken" => $newToken,
        ];
    }

    /**
     * Clean up expired remember tokens (maintenance).
     */
    public function cleanExpiredTokens(): void
    {
        $stmt = $this->pdo->prepare(
            "DELETE FROM remember_tokens WHERE expires_at < NOW()",
        );
        $stmt->execute();
    }
}
