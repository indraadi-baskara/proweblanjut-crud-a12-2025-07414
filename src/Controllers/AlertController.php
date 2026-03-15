<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\ItemRepository;

final class AlertController
{
    private readonly ItemRepository $repo;

    public function __construct()
    {
        $this->repo = new ItemRepository();
    }

    public function lowStock(): void
    {
        $items = $this->repo->findLowStock();
        $threshold = $this->repo->getLowStockThreshold();

        if ($this->isAjax()) {
            header("Content-Type: application/json; charset=utf-8");
            echo json_encode(
                [
                    "count" => count($items),
                    "items" => array_map(
                        fn($item) => [
                            "id" => $item->id,
                            "item_name" => $item->itemName,
                            "quantity" => $item->quantity,
                        ],
                        $items,
                    ),
                ],
                JSON_THROW_ON_ERROR,
            );
            return;
        }

        require __DIR__ . "/../../views/partials/alert_banner.php";
    }

    private function isAjax(): bool
    {
        return ($_SERVER["HTTP_X_REQUESTED_WITH"] ?? "") === "XMLHttpRequest";
    }
}
