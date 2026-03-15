<?php declare(strict_types=1);

if (!function_exists("csrf")) {
    function csrf(): void
    {
        echo '<input type="hidden" name="csrf_token" value="' .
            htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8") .
            '">';
    }
}

if (!function_exists("old")) {
    function old(string $field, mixed $current = null, array $old = []): string
    {
        $value = $old[$field] ?? ($current ?? "");
        return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
    }
}
