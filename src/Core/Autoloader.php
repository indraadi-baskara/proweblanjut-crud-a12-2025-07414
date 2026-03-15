<?php

declare(strict_types=1);

namespace App\Core;

final class Autoloader
{
    public static function register(string $srcPath): void
    {
        spl_autoload_register(function (string $class) use ($srcPath): void {
            // Only handle App\ namespace
            if (!str_starts_with($class, "App\\")) {
                return;
            }

            $relative = substr($class, strlen("App\\"));
            $file =
                $srcPath .
                DIRECTORY_SEPARATOR .
                str_replace("\\", DIRECTORY_SEPARATOR, $relative) .
                ".php";

            if (file_exists($file)) {
                require $file;
            }
        });
    }
}
