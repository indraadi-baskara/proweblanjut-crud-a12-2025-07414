<?php

declare(strict_types=1);

namespace App\Core;

final class ApiResponse
{
    public static function success(mixed $data = null, string $message = "Success", int $code = 200): never
    {
        http_response_code($code);
        header("Content-Type: application/json; charset=utf-8");
        echo json_encode([
            "success" => true,
            "data" => $data,
            "message" => $message,
            "errors" => null,
        ], JSON_THROW_ON_ERROR);
        exit();
    }

    public static function error(string $message, array $errors = [], int $code = 400): never
    {
        http_response_code($code);
        header("Content-Type: application/json; charset=utf-8");
        echo json_encode([
            "success" => false,
            "data" => null,
            "message" => $message,
            "errors" => !empty($errors) ? $errors : null,
        ], JSON_THROW_ON_ERROR);
        exit();
    }

    public static function notFound(string $message = "Resource not found"): never
    {
        self::error($message, [], 404);
    }

    public static function unauthorized(string $message = "Unauthorized"): never
    {
        self::error($message, [], 401);
    }

    public static function validation(array $errors): never
    {
        self::error("Validation failed", $errors, 422);
    }
}
