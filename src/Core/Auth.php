<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\User;
use App\Repositories\UserRepository;

final class Auth
{
    private static ?User $currentUser = null;
    private static bool $initialized = false;

    // Session + cookie constants
    private const SESSION_USER_KEY = "auth_user_id";
    private const COOKIE_REMEMBER = "remember_token";
    private const COOKIE_EXPIRY = 86400 * 30; // 30 days in seconds

    /**
     * Initialize auth on page boot.
     * Call this early in index.php before routing.
     */
    public static function initialize(): void
    {
        if (self::$initialized) {
            return;
        }

        // Configure session security for XAMPP
        self::configureSession();

        // Try to restore user from session
        if (isset($_SESSION[self::SESSION_USER_KEY])) {
            $repo = new UserRepository();
            $user = $repo->findById((int) $_SESSION[self::SESSION_USER_KEY]);
            if ($user !== null) {
                self::$currentUser = $user;
                self::$initialized = true;
                return;
            }
        }

        // No active session, try remember-me cookie
        if (isset($_COOKIE[self::COOKIE_REMEMBER])) {
            $repo = new UserRepository();
            $result = $repo->verifyRememberToken($_COOKIE[self::COOKIE_REMEMBER]);

            if ($result !== null) {
                self::$currentUser = $result["user"];
                $_SESSION[self::SESSION_USER_KEY] = self::$currentUser->id;
                // Update cookie with new rotated token
                self::setRememberCookie($result["newToken"]);
                self::$initialized = true;
                return;
            } else {
                // Invalid token, clear cookie
                self::clearRememberCookie();
            }
        }

        self::$initialized = true;
    }

    /**
     * Authenticate user with username + password.
     * Optionally set remember-me cookie.
     *
     * @return true if authentication succeeded
     */
    public static function authenticate(
        string $username,
        string $password,
        bool $rememberMe = false,
    ): bool {
        $repo = new UserRepository();
        $row = $repo->findByUsername($username);

        if ($row === null) {
            return false;
        }

        if (!User::verifyPassword($password, (string) $row["password_hash"])) {
            return false;
        }

        // Password valid, log in
        $user = new User(
            id: (int) $row["id"],
            username: (string) $row["username"],
            email: (string) $row["email"],
            createdAt: new \DateTimeImmutable((string) $row["created_at"]),
        );

        self::$currentUser = $user;
        $_SESSION[self::SESSION_USER_KEY] = $user->id;

        // Optional remember-me
        if ($rememberMe) {
            $token = $repo->createRememberToken($user->id);
            self::setRememberCookie($token);
        }

        return true;
    }

    /**
     * Logout current user (clear session + cookies).
     */
    public static function logout(): void
    {
        self::$currentUser = null;
        if (isset($_SESSION[self::SESSION_USER_KEY])) {
            unset($_SESSION[self::SESSION_USER_KEY]);
        }
        self::clearRememberCookie();
        session_destroy();
    }

    /**
     * Get currently authenticated user, or null if not logged in.
     */
    public static function currentUser(): ?User
    {
        return self::$currentUser;
    }

    /**
     * Check if user is authenticated.
     */
    public static function isAuthenticated(): bool
    {
        return self::$currentUser !== null;
    }

    /**
     * Require authentication. Redirect to login if not authenticated.
     * Call at start of protected controller actions.
     */
    public static function requireAuth(): void
    {
        if (!self::isAuthenticated()) {
            header("Location: " . BASE_URL . "/auth/login");
            exit;
        }
    }

    /**
     * Verify CSRF token (use in form submissions).
     * Throws exception if token missing or invalid.
     */
    public static function verifyCsrf(): void
    {
        $token = $_POST["csrf_token"] ?? "";

        if (empty($token) || $token !== ($_SESSION["csrf_token"] ?? "")) {
            http_response_code(403);
            exit("CSRF token validation failed.");
        }
    }

    // =====================================================================
    // Private helpers
    // =====================================================================

    private static function configureSession(): void
    {
        // Set session cookie parameters for security
        // These are XAMPP-friendly (secure=false for localhost)
        ini_set("session.cookie_httponly", "1");
        ini_set("session.cookie_samesite", "Strict");
        // Only set secure flag in production (when using HTTPS)
        // ini_set("session.cookie_secure", "1");

        // Session lifetime: 1 hour
        ini_set("session.gc_maxlifetime", "3600");
    }

    private static function setRememberCookie(string $token): void
    {
        setcookie(
            name: self::COOKIE_REMEMBER,
            value: $token,
            expires_at: time() + self::COOKIE_EXPIRY,
            path: "/",
            secure: false, // XAMPP localhost is HTTP
            httponly: true, // Cannot access via JS
            samesite: "Strict",
        );
    }

    private static function clearRememberCookie(): void
    {
        setcookie(
            name: self::COOKIE_REMEMBER,
            value: "",
            expires_at: time() - 3600,
            path: "/",
            secure: false,
            httponly: true,
            samesite: "Strict",
        );
    }
}
