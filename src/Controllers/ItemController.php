<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Models\Item;
use App\Repositories\ItemRepository;

final class ItemController
{
    private readonly ItemRepository $repo;

    public function __construct()
    {
        $this->repo = new ItemRepository();
    }

    public function index(): void
    {
        Auth::requireAuth();

        $user = Auth::currentUser();
        $page = isset($_GET["page"]) ? (int) $_GET["page"] : 1;
        $search = isset($_GET["search"]) ? trim($_GET["search"]) : "";

        $result = $this->repo->paginate($page, $search, $user->id);
        $lowStock = $this->repo->findLowStock($user->id);
        $threshold = $this->repo->getLowStockThreshold();

        require __DIR__ . "/../../views/items/index.php";
    }

    public function create(): void
    {
        Auth::requireAuth();
        require __DIR__ . "/../../views/items/create.php";
    }

    public function store(): void
    {
        Auth::requireAuth();
        Auth::verifyCsrf();

        $errors = $this->validate($_POST);
        $imagePath = $this->handleImageUpload($errors);

        if (!empty($errors)) {
            $old = $_POST;
            require __DIR__ . "/../../views/items/create.php";
            return;
        }

        $user = Auth::currentUser();
        $id = $this->repo->create(
            userId: $user->id,
            itemName: trim($_POST["item_name"]),
            quantity: (int) $_POST["quantity"],
            price: (float) $_POST["price"],
            entryDate: $_POST["entry_date"],
            imagePath: $imagePath,
        );

        $this->redirect("/?flash=created");
    }

    public function edit(): void
    {
        Auth::requireAuth();
        $item = $this->resolveItem();
        require __DIR__ . "/../../views/items/edit.php";
    }

    public function update(): void
    {
        Auth::requireAuth();
        Auth::verifyCsrf();

        $item = $this->resolveItem();
        $errors = $this->validate($_POST);
        $imagePath = $this->handleImageUpload($errors);

        if (!empty($errors)) {
            $old = $_POST;
            require __DIR__ . "/../../views/items/edit.php";
            return;
        }

        // Delete old image if new image is being uploaded
        if ($imagePath !== null && $item->imagePath !== null) {
            $this->deleteImageFile($item->imagePath);
        }

        $user = Auth::currentUser();
        $this->repo->update(
            id: $item->id,
            userId: $user->id,
            itemName: trim($_POST["item_name"]),
            quantity: (int) $_POST["quantity"],
            price: (float) $_POST["price"],
            entryDate: $_POST["entry_date"],
            imagePath: $imagePath,
        );

        $this->redirect("/?flash=updated");
    }

    public function delete(): void
    {
        Auth::requireAuth();
        Auth::verifyCsrf();

        $user = Auth::currentUser();
        $id = isset($_POST["id"]) ? (int) $_POST["id"] : 0;

        if ($id > 0) {
            $this->repo->delete($id, $user->id);
        }

        $this->redirect("/?flash=deleted");
    }

    /**
     * Resolve ?id= from GET or POST, abort with 404 if not found.
     * Validates ownership by current user.
     */
    private function resolveItem(): Item
    {
        $user = Auth::currentUser();
        $id = match (true) {
            isset($_GET["id"]) => (int) $_GET["id"],
            isset($_POST["id"]) => (int) $_POST["id"],
            default => 0,
        };

        $item = $id > 0 ? $this->repo->findById($id, $user->id) : null;

        if ($item === null) {
            http_response_code(404);
            exit("Item not found.");
        }

        return $item;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, string> $errors  field => message
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
     * Handle image upload from $_FILES["image"].
     * Returns the relative image path on success, null if no file or error.
     * Adds error message to $errors array if validation fails.
     *
     * @param array<string, string> $errors
     */
    private function handleImageUpload(array &$errors): ?string
    {
        // Check if file was uploaded
        if (!isset($_FILES["image"]) || $_FILES["image"]["error"] === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        $file = $_FILES["image"];

        // Validate upload status
        if ($file["error"] !== UPLOAD_ERR_OK) {
            $errors["image"] = "File upload failed. Please try again.";
            return null;
        }

        // Validate file size (max 2MB)
        $maxSize = 2 * 1024 * 1024; // 2MB
        if ($file["size"] > $maxSize) {
            $errors["image"] = "File is too large. Maximum size is 2 MB.";
            return null;
        }

        // Validate MIME type
        $mimeType = mime_content_type($file["tmp_name"]);
        $allowedMimes = ["image/jpeg", "image/png", "image/webp", "image/gif"];
        if (!in_array($mimeType, $allowedMimes, true)) {
            $errors["image"] = "Invalid file type. Only JPG, PNG, WebP, and GIF are allowed.";
            return null;
        }

        // Generate unique filename and move file
        $ext = match ($mimeType) {
            "image/jpeg" => "jpg",
            "image/png" => "png",
            "image/webp" => "webp",
            "image/gif" => "gif",
            default => "jpg",
        };

        $filename = uniqid("item_", true) . "." . $ext;
        $uploadDir = __DIR__ . "/../../public/uploads/items/";

        // Ensure directory exists
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filePath = $uploadDir . $filename;

        if (!move_uploaded_file($file["tmp_name"], $filePath)) {
            $errors["image"] = "Failed to save file. Please try again.";
            return null;
        }

        // Return relative path for database storage
        return BASE_URL . "/public/uploads/items/" . $filename;
    }

    /**
     * Delete an image file from disk.
     * Converts the URL path back to filesystem path.
     */
    private function deleteImageFile(string $imagePath): void
    {
        // Convert URL to filesystem path
        $relativePath = str_replace(BASE_URL, "", $imagePath);
        $filePath = __DIR__ . "/../../" . ltrim($relativePath, "/");

        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    private function redirect(string $url): never
    {
        header("Location: " . BASE_URL . $url);
        exit();
    }
}
