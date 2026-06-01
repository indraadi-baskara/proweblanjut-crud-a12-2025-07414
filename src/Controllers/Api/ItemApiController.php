<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\ApiResponse;
use App\Models\Item;
use App\Repositories\ItemRepository;

final class ItemApiController
{
    private readonly ItemRepository $repo;

    public function __construct()
    {
        $this->repo = new ItemRepository();
    }

    public function list(): void
    {
        Auth::requireAuth();

        $user = Auth::currentUser();
        $page = isset($_GET["page"]) ? (int) $_GET["page"] : 1;
        $search = isset($_GET["search"]) ? trim($_GET["search"]) : "";

        $result = $this->repo->paginate($page, $search, $user->id);
        $threshold = $this->repo->getLowStockThreshold();

        $items = array_map(fn($item) => $this->serializeItem($item, $threshold), $result["items"]);

        ApiResponse::success([
            "items" => $items,
            "pagination" => [
                "current_page" => $result["current_page"],
                "total_pages" => $result["total_pages"],
                "per_page" => $result["per_page"],
                "total" => $result["total"],
            ],
            "low_stock_threshold" => $threshold,
        ], "Items retrieved successfully");
    }

    public function store(): void
    {
        Auth::requireAuth();

        $input = $this->getJsonInput();
        $errors = $this->validate($input);

        if (!empty($errors)) {
            ApiResponse::validation($errors);
        }

        $imagePath = $this->handleImageUploadFromApi($errors);

        if (!empty($errors)) {
            ApiResponse::validation($errors);
        }

        $user = Auth::currentUser();
        $id = $this->repo->create(
            userId: $user->id,
            itemName: trim($input["item_name"]),
            quantity: (int) $input["quantity"],
            price: (float) $input["price"],
            entryDate: $input["entry_date"],
            imagePath: $imagePath,
        );

        ApiResponse::success(
            ["id" => $id],
            "Item created successfully",
            201
        );
    }

    public function update(): void
    {
        Auth::requireAuth();

        $item = $this->resolveItem();
        $input = $this->getJsonInput();
        $errors = $this->validate($input);

        if (!empty($errors)) {
            ApiResponse::validation($errors);
        }

        $imagePath = $this->handleImageUploadFromApi($errors);

        if (!empty($errors)) {
            ApiResponse::validation($errors);
        }

        // Delete old image if new image is being uploaded
        if ($imagePath !== null && $item->imagePath !== null) {
            $this->deleteImageFile($item->imagePath);
        }

        $user = Auth::currentUser();
        $this->repo->update(
            id: $item->id,
            userId: $user->id,
            itemName: trim($input["item_name"]),
            quantity: (int) $input["quantity"],
            price: (float) $input["price"],
            entryDate: $input["entry_date"],
            imagePath: $imagePath,
        );

        ApiResponse::success(null, "Item updated successfully");
    }

    public function delete(): void
    {
        Auth::requireAuth();

        $item = $this->resolveItem();
        $user = Auth::currentUser();

        // Delete image if exists
        if ($item->imagePath !== null) {
            $this->deleteImageFile($item->imagePath);
        }

        $this->repo->delete($item->id, $user->id);

        ApiResponse::success(null, "Item deleted successfully");
    }

    /**
     * Resolve item from JSON input or query parameter.
     */
    private function resolveItem(): Item
    {
        $user = Auth::currentUser();
        $id = match (true) {
            isset($_GET["id"]) => (int) $_GET["id"],
            default => 0,
        };

        if ($id === 0) {
            $input = $this->getJsonInput();
            $id = isset($input["id"]) ? (int) $input["id"] : 0;
        }

        $item = $id > 0 ? $this->repo->findById($id, $user->id) : null;

        if ($item === null) {
            ApiResponse::notFound("Item not found");
        }

        return $item;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, string> $errors
     */
    private function validate(array $data): array
    {
        $errors = [];

        $itemName = trim($data["item_name"] ?? "");
        if ($itemName === "") {
            $errors["item_name"] = "Item name is required.";
        } elseif (mb_strlen($itemName) > 255) {
            $errors["item_name"] = "Item name must be 255 characters or fewer.";
        }

        $quantity = $data["quantity"] ?? "";
        if (!is_numeric($quantity) || (int) $quantity < 0) {
            $errors["quantity"] = "Quantity must be a non-negative number.";
        }

        $price = $data["price"] ?? "";
        if (!is_numeric($price) || (float) $price < 0) {
            $errors["price"] = "Price must be a non-negative number.";
        }

        $entryDate = $data["entry_date"] ?? "";
        if (
            $entryDate === "" ||
            \DateTimeImmutable::createFromFormat("Y-m-d", $entryDate) === false
        ) {
            $errors["entry_date"] =
                "Entry date must be a valid date (YYYY-MM-DD).";
        }

        return $errors;
    }

    /**
     * Parse JSON input from request body.
     * Falls back to $_POST for form-data.
     *
     * @return array<string, mixed>
     */
    private function getJsonInput(): array
    {
        $contentType = $_SERVER["CONTENT_TYPE"] ?? "";

        if (str_contains($contentType, "application/json")) {
            $input = json_decode(file_get_contents("php://input"), true);
            return is_array($input) ? $input : [];
        }

        return $_POST;
    }

    /**
     * Handle image upload from multipart form data.
     * Returns relative path on success, null if no file.
     *
     * @param array<string, string> $errors
     */
    private function handleImageUploadFromApi(array &$errors): ?string
    {
        if (!isset($_FILES["image"]) || $_FILES["image"]["error"] === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        $file = $_FILES["image"];

        if ($file["error"] !== UPLOAD_ERR_OK) {
            $errors["image"] = "File upload failed. Please try again.";
            return null;
        }

        $maxSize = 2 * 1024 * 1024; // 2MB
        if ($file["size"] > $maxSize) {
            $errors["image"] = "File is too large. Maximum size is 2 MB.";
            return null;
        }

        $mimeType = mime_content_type($file["tmp_name"]);
        $allowedMimes = ["image/jpeg", "image/png", "image/webp", "image/gif"];
        if (!in_array($mimeType, $allowedMimes, true)) {
            $errors["image"] = "Invalid file type. Only JPG, PNG, WebP, and GIF are allowed.";
            return null;
        }

        $ext = match ($mimeType) {
            "image/jpeg" => "jpg",
            "image/png" => "png",
            "image/webp" => "webp",
            "image/gif" => "gif",
            default => "jpg",
        };

        $filename = uniqid("item_", true) . "." . $ext;
        $uploadDir = __DIR__ . "/../../public/uploads/items/";

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filePath = $uploadDir . $filename;

        if (!move_uploaded_file($file["tmp_name"], $filePath)) {
            $errors["image"] = "Failed to save file. Please try again.";
            return null;
        }

        return BASE_URL . "/public/uploads/items/" . $filename;
    }

    /**
     * Delete image file from disk.
     */
    private function deleteImageFile(string $imagePath): void
    {
        $relativePath = str_replace(BASE_URL, "", $imagePath);
        $filePath = __DIR__ . "/../../" . ltrim($relativePath, "/");

        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    /**
     * Convert Item model to JSON-serializable array.
     *
     * @return array<string, mixed>
     */
    private function serializeItem(Item $item, int $threshold): array
    {
        return [
            "id" => $item->id,
            "item_name" => $item->itemName,
            "quantity" => $item->quantity,
            "price" => $item->price,
            "entry_date" => $item->entryDate->format("Y-m-d"),
            "image_path" => $item->imagePath,
            "low_stock" => $item->isLowStock($threshold),
        ];
    }
}
