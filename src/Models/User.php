<?php

declare(strict_types=1);

namespace App\Models;

use DateTimeImmutable;

final class User
{
    public function __construct(
        public readonly int $id,
        public readonly string $username,
        public readonly string $email,
        public readonly DateTimeImmutable $createdAt,
    ) {}

    /**
     * Hydrate a User from a raw PDO FETCH_ASSOC row.
     *
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row["id"],
            username: (string) $row["username"],
            email: (string) $row["email"],
            createdAt: new DateTimeImmutable($row["created_at"]),
        );
    }

    /**
     * Verify plaintext password against hashed password.
     * Never exposed to public; use internally in Auth.
     */
    public static function verifyPassword(
        string $plaintext,
        string $hash,
    ): bool {
        return password_verify($plaintext, $hash);
    }

    /**
     * Hash a plaintext password using Bcrypt.
     * Use when creating/updating user passwords.
     */
    public static function hashPassword(string $plaintext): string
    {
        return password_hash($plaintext, PASSWORD_BCRYPT, [
            'cost' => 12, // Higher cost = slower hashing (better security)
        ]);
    }
}
