<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Models\User;
use App\Repositories\UserRepository;

final class AuthController
{
    private readonly UserRepository $repo;

    public function __construct()
    {
        $this->repo = new UserRepository();
    }

    /**
     * GET /auth/login - Show login form
     */
    public function login(): void
    {
        if (Auth::isAuthenticated()) {
            header("Location: " . BASE_URL . "/");
            exit;
        }

        $errors = [];
        require __DIR__ . "/../../views/auth/login.php";
    }

    /**
     * POST /auth/auth - Process login form
     */
    public function auth(): void
    {
        if (Auth::isAuthenticated()) {
            header("Location: " . BASE_URL . "/");
            exit;
        }

        Auth::verifyCsrf();

        $username = trim($_POST["username"] ?? "");
        $password = $_POST["password"] ?? "";
        $rememberMe = isset($_POST["remember_me"]);

        $errors = [];

        if ($username === "") {
            $errors["username"] = "Username is required.";
        }

        if ($password === "") {
            $errors["password"] = "Password is required.";
        }

        if (!empty($errors)) {
            require __DIR__ . "/../../views/auth/login.php";
            return;
        }

        // Try to authenticate
        if (Auth::authenticate($username, $password, $rememberMe)) {
            header("Location: " . BASE_URL . "/");
            exit;
        }

        // Authentication failed
        $errors["auth"] = "Invalid username or password.";
        require __DIR__ . "/../../views/auth/login.php";
    }

    /**
     * GET /auth/register - Show registration form
     */
    public function register(): void
    {
        if (Auth::isAuthenticated()) {
            header("Location: " . BASE_URL . "/");
            exit;
        }

        $errors = [];
        $old = [];
        require __DIR__ . "/../../views/auth/register.php";
    }

    /**
     * POST /auth/store - Process registration form
     */
    public function store(): void
    {
        if (Auth::isAuthenticated()) {
            header("Location: " . BASE_URL . "/");
            exit;
        }

        Auth::verifyCsrf();

        $username = trim($_POST["username"] ?? "");
        $email = trim($_POST["email"] ?? "");
        $password = $_POST["password"] ?? "";
        $confirmPassword = $_POST["confirm_password"] ?? "";

        $errors = $this->validateRegistration($username, $email, $password, $confirmPassword);

        if (!empty($errors)) {
            $old = [
                "username" => $username,
                "email" => $email,
            ];
            require __DIR__ . "/../../views/auth/register.php";
            return;
        }

        // Create user
        try {
            $passwordHash = User::hashPassword($password);
            $this->repo->create($username, $email, $passwordHash);

            // Auto-login after registration
            Auth::authenticate($username, $password, true);

            header("Location: " . BASE_URL . "/");
            exit;
        } catch (\RuntimeException $e) {
            $errors["auth"] = $e->getMessage();
            $old = [
                "username" => $username,
                "email" => $email,
            ];
            require __DIR__ . "/../../views/auth/register.php";
        }
    }

    /**
     * POST /auth/logout - Logout user
     */
    public function logout(): void
    {
        Auth::verifyCsrf();
        Auth::logout();
        header("Location: " . BASE_URL . "/auth/login");
        exit;
    }

    /**
     * Validate registration data.
     *
     * @param string $username
     * @param string $email
     * @param string $password
     * @param string $confirmPassword
     * @return array<string, string> errors
     */
    private function validateRegistration(
        string $username,
        string $email,
        string $password,
        string $confirmPassword,
    ): array {
        $errors = [];

        // Username validation
        if ($username === "") {
            $errors["username"] = "Username is required.";
        } elseif (!preg_match("/^[a-zA-Z0-9_-]{3,50}$/", $username)) {
            $errors["username"] = "Username must be 3-50 characters (alphanumeric, _, -)";
        }

        // Email validation
        if ($email === "") {
            $errors["email"] = "Email is required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors["email"] = "Invalid email format.";
        }

        // Password validation (strong: 8+, uppercase, number, symbol)
        if ($password === "") {
            $errors["password"] = "Password is required.";
        } elseif (mb_strlen($password) < 8) {
            $errors["password"] = "Password must be at least 8 characters.";
        } elseif (!preg_match("/[A-Z]/", $password)) {
            $errors["password"] = "Password must contain at least one uppercase letter (A-Z).";
        } elseif (!preg_match("/[0-9]/", $password)) {
            $errors["password"] = "Password must contain at least one number (0-9).";
        } elseif (!preg_match("/[!@#$%^&*()_+\-=\[\]{};:'\"\\|,.<>\/?]/", $password)) {
            $errors["password"] = "Password must contain at least one special character (!@#$%^&*...).";
        }

        // Confirm password
        if ($confirmPassword === "") {
            $errors["confirm_password"] = "Confirm password is required.";
        } elseif ($password !== $confirmPassword) {
            $errors["confirm_password"] = "Passwords do not match.";
        }

        // Check if username/email already exists
        if (empty($errors["username"]) && empty($errors["email"])) {
            if ($this->repo->existsByUsernameOrEmail($username, $email)) {
                $errors["auth"] = "Username or email already registered.";
            }
        }

        return $errors;
    }
}
