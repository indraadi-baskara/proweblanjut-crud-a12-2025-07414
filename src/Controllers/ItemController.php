<?php

declare(strict_types=1);

namespace App\Controllers;

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
        $page = isset($_GET["page"]) ? (int) $_GET["page"] : 1;
        $search = isset($_GET["search"]) ? trim($_GET["search"]) : "";

        $result = $this->repo->paginate($page, $search);
        $lowStock = $this->repo->findLowStock();
        $threshold = $this->repo->getLowStockThreshold();

        require __DIR__ . "/../../views/items/index.php";
    }

    public function create(): void
    {
        require __DIR__ . "/../../views/items/create.php";
    }

    public function store(): void
    {
        $this->verifyCsrf();

        $errors = $this->validate($_POST);

        if (!empty($errors)) {
            $old = $_POST;
            require __DIR__ . "/../../views/items/create.php";
            return;
        }

        $id = $this->repo->create(
            itemName: trim($_POST["item_name"]),
            quantity: (int) $_POST["quantity"],
            price: (float) $_POST["price"],
            entryDate: $_POST["entry_date"],
        );

        $this->redirect("/?flash=created");
    }

    public function edit(): void
    {
        $item = $this->resolveItem();
        require __DIR__ . "/../../views/items/edit.php";
    }

    public function update(): void
    {
        $this->verifyCsrf();

        $item = $this->resolveItem();
        $errors = $this->validate($_POST);

        if (!empty($errors)) {
            $old = $_POST;
            require __DIR__ . "/../../views/items/edit.php";
            return;
        }

        $this->repo->update(
            id: $item->id,
            itemName: trim($_POST["item_name"]),
            quantity: (int) $_POST["quantity"],
            price: (float) $_POST["price"],
            entryDate: $_POST["entry_date"],
        );

        $this->redirect("/?flash=updated");
    }

    public function delete(): void
    {
        $this->verifyCsrf();

        $id = isset($_POST["id"]) ? (int) $_POST["id"] : 0;

        if ($id > 0) {
            $this->repo->delete($id);
        }

        $this->redirect("/?flash=deleted");
    }

    /**
     * Resolve ?id= from GET or POST, abort with 404 if not found.
     */
    private function resolveItem(): Item
    {
        $id = match (true) {
            isset($_GET["id"]) => (int) $_GET["id"],
            isset($_POST["id"]) => (int) $_POST["id"],
            default => 0,
        };

        $item = $id > 0 ? $this->repo->findById($id) : null;

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

    private function verifyCsrf(): void
    {
        $token = $_POST["csrf_token"] ?? "";

        if (
            empty($_SESSION["csrf_token"]) ||
            !hash_equals($_SESSION["csrf_token"], $token)
        ) {
            http_response_code(403);
            exit("Invalid CSRF token.");
        }
    }

    private function redirect(string $url): never
    {
        header("Location: " . $url);
        exit();
    }
}
