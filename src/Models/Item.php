<?php

declare(strict_types=1);

namespace App\Models;

use DateTimeImmutable;

final class Item
{
    public function __construct(
        public readonly int $id,
        public readonly string $itemName,
        public readonly int $quantity,
        public readonly float $price,
        public readonly DateTimeImmutable $entryDate,
        public readonly ?string $imagePath = null,
    ) {}

    /**
     * Hydrate an Item from a raw PDO FETCH_ASSOC row.
     *
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row["id"],
            itemName: (string) $row["item_name"],
            quantity: (int) $row["quantity"],
            price: (float) $row["price"],
            entryDate: new DateTimeImmutable($row["entry_date"]),
            imagePath: isset($row["image_path"]) ? (string) $row["image_path"] : null,
        );
    }

    public function isLowStock(int $threshold): bool
    {
        return $this->quantity <= $threshold;
    }

    /**
     * Serialize back to an array for insert / update bindings.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            "item_name" => $this->itemName,
            "quantity" => $this->quantity,
            "price" => $this->price,
            "entry_date" => $this->entryDate->format("Y-m-d"),
            "image_path" => $this->imagePath,
        ];
    }
}
