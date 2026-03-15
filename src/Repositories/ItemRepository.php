<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Item;
use PDO;

final class ItemRepository
{
    private readonly PDO $pdo;

    private const int LOW_STOCK_THRESHOLD = 5;
    private const int PER_PAGE = 10;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getPdo();
    }

    /**
     * Paginated list with optional search across item_name.
     *
     * @return array{ items: Item[], total: int, per_page: int, current_page: int, total_pages: int }
     */
    public function paginate(int $page = 1, string $search = ""): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * self::PER_PAGE;
        $like = "%" . $search . "%";

        $countSql = "SELECT COUNT(*) FROM items WHERE item_name LIKE :search";
        $countStmt = $this->pdo->prepare($countSql);
        $countStmt->execute([":search" => $like]);
        $total = (int) $countStmt->fetchColumn();

        $sql = <<<SQL
            SELECT id, item_name, quantity, price, entry_date
            FROM items
            WHERE item_name LIKE :search
            ORDER BY entry_date DESC, id DESC
            LIMIT :limit OFFSET :offset
        SQL;

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(":search", $like, PDO::PARAM_STR);
        $stmt->bindValue(":limit", self::PER_PAGE, PDO::PARAM_INT);
        $stmt->bindValue(":offset", $offset, PDO::PARAM_INT);
        $stmt->execute();

        $items = array_map(
            fn(array $row) => Item::fromRow($row),
            $stmt->fetchAll(),
        );

        return [
            "items" => $items,
            "total" => $total,
            "per_page" => self::PER_PAGE,
            "current_page" => $page,
            "total_pages" => (int) ceil($total / self::PER_PAGE),
        ];
    }

    public function findById(int $id): ?Item
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, item_name, quantity, price, entry_date FROM items WHERE id = :id LIMIT 1",
        );
        $stmt->execute([":id" => $id]);
        $row = $stmt->fetch();

        return $row !== false ? Item::fromRow($row) : null;
    }

    /**
     * @return Item[]
     */
    public function findLowStock(): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, item_name, quantity, price, entry_date
             FROM items WHERE quantity <= :threshold ORDER BY quantity ASC',
        );
        $stmt->execute([":threshold" => self::LOW_STOCK_THRESHOLD]);

        return array_map(
            fn(array $row) => Item::fromRow($row),
            $stmt->fetchAll(),
        );
    }

    public function create(
        string $itemName,
        int $quantity,
        float $price,
        string $entryDate,
    ): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO items (item_name, quantity, price, entry_date)
             VALUES (:item_name, :quantity, :price, :entry_date)',
        );

        $stmt->execute([
            ":item_name" => $itemName,
            ":quantity" => $quantity,
            ":price" => $price,
            ":entry_date" => $entryDate,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(
        int $id,
        string $itemName,
        int $quantity,
        float $price,
        string $entryDate,
    ): bool {
        $stmt = $this->pdo->prepare(
            'UPDATE items
             SET item_name = :item_name, quantity = :quantity,
                 price = :price, entry_date = :entry_date
             WHERE id = :id',
        );

        return $stmt->execute([
            ":id" => $id,
            ":item_name" => $itemName,
            ":quantity" => $quantity,
            ":price" => $price,
            ":entry_date" => $entryDate,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM items WHERE id = :id");
        return $stmt->execute([":id" => $id]);
    }

    public function getLowStockThreshold(): int
    {
        return self::LOW_STOCK_THRESHOLD;
    }
}
