<?php

declare(strict_types=1);

require_once __DIR__ . "/src/Core/Autoloader.php";

use App\Core\Autoloader;
use App\Core\Database;
use App\Models\Item;

Autoloader::register(__DIR__ . "/src");

// Fetch all items directly — repository comes next commits
$pdo = Database::getInstance()->getPdo();
$rows = $pdo->query("SELECT * FROM items ORDER BY entry_date DESC")->fetchAll();
$items = array_map(fn(array $row) => Item::fromRow($row), $rows);

require __DIR__ . "/views/layout.php";
