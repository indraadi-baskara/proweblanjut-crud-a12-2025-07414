<?php

declare(strict_types=1);

/**
 * Image Handler with GD-based Resizing & Caching
 *
 * Usage:
 *   /image.php?path=items/item_123.jpg&size=48
 *   /image.php?path=items/item_123.jpg&size=200
 *
 * Supported sizes: 48, 200 (predefined)
 * Images are cached in public/uploads/cache/
 */

// Allowed sizes (predefined)
$allowedSizes = [48, 200];

// Get parameters
$requestPath = $_GET["path"] ?? "";
$requestSize = isset($_GET["size"]) ? (int) $_GET["size"] : 0;

// Validate parameters
if (empty($requestPath) || !in_array($requestSize, $allowedSizes, true)) {
    http_response_code(400);
    die("Invalid parameters.");
}

// Security: Prevent directory traversal
if (strpos($requestPath, "..") !== false || strpos($requestPath, "//") !== false) {
    http_response_code(403);
    die("Access denied.");
}

// Build paths
$uploadsDir = __DIR__ . "/uploads/";
$originalPath = $uploadsDir . $requestPath;
$cacheDir = $uploadsDir . "cache/";
$cachePath = $cacheDir . $requestSize . "_" . basename($requestPath);

// Validate original file exists
if (!file_exists($originalPath) || !is_file($originalPath)) {
    http_response_code(404);
    die("Image not found.");
}

// Ensure cache directory exists
if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0755, true);
}

// Check if cached version exists
if (file_exists($cachePath)) {
    serveImage($cachePath);
    exit;
}

// Load original image
$mimeType = mime_content_type($originalPath);
$allowedMimes = ["image/jpeg", "image/png", "image/webp", "image/gif"];

if (!in_array($mimeType, $allowedMimes, true)) {
    http_response_code(415);
    die("Unsupported image type.");
}

// Load image based on mime type
$image = match ($mimeType) {
    "image/jpeg" => @imagecreatefromjpeg($originalPath),
    "image/png" => @imagecreatefrompng($originalPath),
    "image/webp" => @imagecreatefromwebp($originalPath),
    "image/gif" => @imagecreatefromgif($originalPath),
    default => false,
};

if ($image === false) {
    http_response_code(500);
    die("Failed to load image.");
}

// Get original dimensions
$origWidth = imagesx($image);
$origHeight = imagesy($image);

// Calculate new dimensions (maintain aspect ratio)
$size = $requestSize;
$newWidth = $size;
$newHeight = $size;

// Resize image using GD
$resized = imagescale($image, $newWidth, $newHeight, IMG_BILINEAR);

if ($resized === false) {
    imagedestroy($image);
    http_response_code(500);
    die("Failed to resize image.");
}

// Save to cache based on original format
$saveSuccess = false;
switch ($mimeType) {
    case "image/jpeg":
        $saveSuccess = imagejpeg($resized, $cachePath, 85); // 85% quality
        break;
    case "image/png":
        $saveSuccess = imagepng($resized, $cachePath, 9); // Max compression
        break;
    case "image/webp":
        $saveSuccess = imagewebp($resized, $cachePath, 85);
        break;
    case "image/gif":
        $saveSuccess = imagegif($resized, $cachePath);
        break;
}

imagedestroy($image);
imagedestroy($resized);

if (!$saveSuccess) {
    http_response_code(500);
    die("Failed to save cached image.");
}

// Serve the cached image
serveImage($cachePath);

/**
 * Helper: Serve image with appropriate headers
 */
function serveImage(string $filePath): void
{
    $mimeType = mime_content_type($filePath);
    $fileSize = filesize($filePath);
    $lastModified = filemtime($filePath);

    header("Content-Type: {$mimeType}");
    header("Content-Length: {$fileSize}");
    header("Cache-Control: public, max-age=31536000"); // 1 year
    header("Last-Modified: " . gmdate("r", $lastModified));
    header("ETag: \"" . md5_file($filePath) . "\"");

    // Handle If-Modified-Since (304 Not Modified)
    if (
        isset($_SERVER["HTTP_IF_MODIFIED_SINCE"]) &&
        strtotime($_SERVER["HTTP_IF_MODIFIED_SINCE"]) >= $lastModified
    ) {
        http_response_code(304);
        exit;
    }

    readfile($filePath);
}
