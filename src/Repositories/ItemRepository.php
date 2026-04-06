<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Item;
use PDO;

final class ItemRepository
{
    private readonly PDO $pdo;

    private const LOW_STOCK_THRESHOLD = 5;
    private const PER_PAGE = 10;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getPdo();
    }

    /**
     * Paginated list with optional search across item_name.
     * Optionally filter by user_id for multi-user setup.
     *
     * @return array{ items: Item[], total: int, per_page: int, current_page: int, total_pages: int }
     */
    public function paginate(int $page = 1, string $search = "", ?int $userId = null): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * self::PER_PAGE;
        $like = "%" . $search . "%";

        // Build WHERE clause
        $whereConditions = ["item_name LIKE :search"];
        $params = [":search" => $like];

        if ($userId !== null) {
            $whereConditions[] = "user_id = :user_id";
            $params[":user_id"] = $userId;
        }

        $whereClause = implode(" AND ", $whereConditions);

        $countSql = "SELECT COUNT(*) FROM items WHERE {$whereClause}";
        $countStmt = $this->pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $sql = <<<SQL
            SELECT id, item_name, quantity, price, entry_date
            FROM items
            WHERE {$whereClause}
            ORDER BY entry_date DESC, id DESC
            LIMIT :limit OFFSET :offset
        SQL;

        $stmt = $this->pdo->prepare($sql);
        $params[":limit"] = self::PER_PAGE;
        $params[":offset"] = $offset;
        foreach ($params as $key => $value) {
            if ($key === ":limit" || $key === ":offset") {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $value, PDO::PARAM_STR);
            }
        }
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

    /**
     * Find item by ID. Optionally validate ownership by user_id.
     */
    public function findById(int $id, ?int $userId = null): ?Item
    {
        $whereConditions = ["id = :id"];
        $params = [":id" => $id];

        if ($userId !== null) {
            $whereConditions[] = "user_id = :user_id";
            $params[":user_id"] = $userId;
        }

        $whereClause = implode(" AND ", $whereConditions);

        $stmt = $this->pdo->prepare(
            "SELECT id, item_name, quantity, price, entry_date FROM items WHERE {$whereClause} LIMIT 1",
        );
        $stmt->execute($params);
        $row = $stmt->fetch();

        return $row !== false ? Item::fromRow($row) : null;
    }

    /**
     * @return Item[]
     */
    public function findLowStock(?int $userId = null): array
    {
        $whereConditions = ["quantity <= :threshold"];
        $params = [":threshold" => self::LOW_STOCK_THRESHOLD];

        if ($userId !== null) {
            $whereConditions[] = "user_id = :user_id";
            $params[":user_id"] = $userId;
        }

        $whereClause = implode(" AND ", $whereConditions);

        $stmt = $this->pdo->prepare(
            "SELECT id, item_name, quantity, price, entry_date
             FROM items WHERE {$whereClause} ORDER BY quantity ASC",
        );
        $stmt->execute($params);

        return array_map(
            fn(array $row) => Item::fromRow($row),
            $stmt->fetchAll(),
        );
    }

    public function create(
        int $userId,
        string $itemName,
        int $quantity,
        float $price,
        string $entryDate,
    ): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO items (user_id, item_name, quantity, price, entry_date)
             VALUES (:user_id, :item_name, :quantity, :price, :entry_date)',
        );

        $stmt->execute([
            ":user_id" => $userId,
            ":item_name" => $itemName,
            ":quantity" => $quantity,
            ":price" => $price,
            ":entry_date" => $entryDate,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Update item. Validates ownership by user_id.
     */
    public function update(
        int $id,
        int $userId,
        string $itemName,
        int $quantity,
        float $price,
        string $entryDate,
    ): bool {
        $stmt = $this->pdo->prepare(
            'UPDATE items
             SET item_name = :item_name, quantity = :quantity,
                 price = :price, entry_date = :entry_date
             WHERE id = :id AND user_id = :user_id',
        );

        return $stmt->execute([
            ":id" => $id,
            ":user_id" => $userId,
            ":item_name" => $itemName,
            ":quantity" => $quantity,
            ":price" => $price,
            ":entry_date" => $entryDate,
        ]);
    }

    /**
     * Delete item. Validates ownership by user_id.
     */
    public function delete(int $id, int $userId): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM items WHERE id = :id AND user_id = :user_id");
        return $stmt->execute([":id" => $id, ":user_id" => $userId]);
    }

    public function getLowStockThreshold(): int
    {
        return self::LOW_STOCK_THRESHOLD;
    }
}
