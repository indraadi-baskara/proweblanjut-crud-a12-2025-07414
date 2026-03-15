<?php
declare(strict_types=1);

// -------------------------------------------------------------------------
// Enable for debugging purpose
// -------------------------------------------------------------------------

// ini_set("display_errors", "1");
// ini_set("display_startup_errors", "1");
// error_reporting(E_ALL);

require_once __DIR__ . "/src/Core/Autoloader.php";

use App\Core\Autoloader;
use App\Controllers\ItemController;
use App\Controllers\SearchController;
use App\Controllers\AlertController;

Autoloader::register(__DIR__ . "/src");

// Boot session for CSRF
session_start();
define("BASE_URL", "/proweblanjut-crud-a12-2025-07414");

// Generate CSRF token once per session
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// -------------------------------------------------------------------------
// Request context
// -------------------------------------------------------------------------

$method = $_SERVER["REQUEST_METHOD"];
$uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

// Strip subfolder prefix when running under /proweblanjut-crud-a12-2025-07414/ on XAMPP
$base = "/proweblanjut-crud-a12-2025-07414";
if (str_starts_with($uri, $base)) {
    $uri = substr($uri, strlen($base));
}

$uri = "/" . trim($uri, "/");

// -------------------------------------------------------------------------
// Router
// -------------------------------------------------------------------------

$item = new ItemController();
$search = new SearchController();
$alert = new AlertController();

match (true) {
    // Create form
    $method === "GET" && $uri === "/items/create" => $item->create(),
    // Store new item
    $method === "POST" && $uri === "/items/store" => $item->store(),
    // Edit form
    $method === "GET" && $uri === "/items/edit" => $item->edit(),
    // Update existing item
    $method === "POST" && $uri === "/items/update" => $item->update(),
    // Delete item
    $method === "POST" && $uri === "/items/delete" => $item->delete(),
    // Low-stock alert (partial or JSON)
    $method === "GET" && $uri === "/alerts/low-stock" => $alert->lowStock(),
    // Index / search (same view, SearchController handles both)
    $method === "GET" && $uri === "/" => $search->search(),
    // 404 fallback
    default => (static function () {
        http_response_code(404);
        require __DIR__ . "/views/404.php";
    })(),
};
