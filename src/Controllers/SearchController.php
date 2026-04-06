<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Repositories\ItemRepository;

final class SearchController
{
    private readonly ItemRepository $repo;

    public function __construct()
    {
        $this->repo = new ItemRepository();
    }

    public function search(): void
    {
        Auth::requireAuth();

        $user = Auth::currentUser();
        $search = isset($_GET["search"]) ? trim($_GET["search"]) : "";
        $page = isset($_GET["page"]) ? max(1, (int) $_GET["page"]) : 1;

        // Clamp search length — no need to hit the DB with a 10k char string
        if (mb_strlen($search) > 100) {
            $search = mb_substr($search, 0, 100);
        }

        $result = $this->repo->paginate($page, $search, $user->id);
        $lowStock = $this->repo->findLowStock($user->id);
        $threshold = $this->repo->getLowStockThreshold();

        // Partial render for fetch()-based live search,
        // full page render for plain GET fallback
        if ($this->isAjax()) {
            require __DIR__ . "/../../views/partials/table.php";
            return;
        }

        require __DIR__ . "/../../views/items/index.php";
    }

    private function isAjax(): bool
    {
        return ($_SERVER["HTTP_X_REQUESTED_WITH"] ?? "") === "XMLHttpRequest";
    }
}
