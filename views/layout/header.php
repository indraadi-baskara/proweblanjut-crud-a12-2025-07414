<?php declare(strict_types=1);
use App\Core\Auth;

$flash = $_GET["flash"] ?? "";
$flashConfig = match ($flash) {
    "created" => [
        "bg" => "bg-green-950/60 border-green-800/60 text-green-300",
        "dot" => "bg-green-400",
        "msg" => "Item created successfully.",
    ],
    "updated" => [
        "bg" => "bg-blue-950/60 border-blue-800/60 text-blue-300",
        "dot" => "bg-blue-400",
        "msg" => "Item updated successfully.",
    ],
    "deleted" => [
        "bg" => "bg-red-950/60 border-red-800/60 text-red-300",
        "dot" => "bg-red-400",
        "msg" => "Item deleted successfully.",
    ],
    default => null,
};

$currentUser = Auth::currentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? "Inventory System" ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['DM Sans', 'sans-serif'],
                        mono: ['DM Mono', 'monospace'],
                    },
                    colors: {
                        brand: {
                            50:  '#f0fdf4',
                            100: '#dcfce7',
                            600: '#16a34a',
                            700: '#15803d',
                            900: '#14532d',
                        }
                    }
                }
            }
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'DM Sans', sans-serif; }
    </style>
</head>
<body class="bg-zinc-950 text-zinc-100 min-h-screen">

    <!-- Nav -->
    <header class="border-b border-zinc-800 bg-zinc-900/80 backdrop-blur-sm sticky top-0 z-10">
        <div class="max-w-6xl mx-auto px-6 h-14 flex items-center justify-between">
            <a href="<?= BASE_URL ?>/" class="flex items-center gap-3 hover:opacity-80 transition-opacity">
                <span class="w-2 h-2 rounded-full bg-green-600 shadow-lg"></span>
                <span class="font-semibold tracking-tight text-sm">Inventory System</span>
            </a>
            <div class="flex items-center gap-4">
                <span class="text-xs text-zinc-500 font-mono hidden sm:block"><?= date(
                    "d M Y",
                ) ?></span>

                <?php if ($currentUser): ?>
                    <!-- Add item button -->
                    <a href="<?= BASE_URL ?>/items/create"
                       class="inline-flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white text-xs font-medium px-3.5 py-2 rounded-lg transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                        </svg>
                        Add item
                    </a>

                    <!-- User menu -->
                    <div class="relative group">
                        <button class="flex items-center gap-2 px-3 py-2 text-xs text-zinc-300 hover:text-white transition-colors">
                            <span class="w-2 h-2 rounded-full bg-green-500"></span>
                            <span class="hidden sm:inline max-w-[100px] truncate"><?= htmlspecialchars($currentUser->username, ENT_QUOTES, "UTF-8") ?></span>
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
                            </svg>
                        </button>
                        <div class="absolute right-0 mt-1 w-48 bg-zinc-800 border border-zinc-700 rounded-lg shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all">
                            <div class="px-4 py-3 border-b border-zinc-700">
                                <p class="text-xs text-zinc-400">Logged in as</p>
                                <p class="text-sm font-medium text-zinc-100"><?= htmlspecialchars($currentUser->username, ENT_QUOTES, "UTF-8") ?></p>
                                <p class="text-xs text-zinc-500"><?= htmlspecialchars($currentUser->email, ENT_QUOTES, "UTF-8") ?></p>
                            </div>
                            <form method="POST" action="<?= BASE_URL ?>/auth/logout" class="p-2">
                                <?php csrf(); ?>
                                <button type="submit" class="w-full text-left px-3 py-2 text-xs text-red-400 hover:bg-zinc-700 rounded transition-colors">
                                    Sign out
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-6 py-10">

        <!-- Flash message -->
        <?php if ($flashConfig): ?>
        <div class="mb-6 flex items-center gap-3 <?= $flashConfig[
            "bg"
        ] ?> border rounded-xl px-4 py-3 text-sm">
            <span class="w-1.5 h-1.5 rounded-full <?= $flashConfig[
                "dot"
            ] ?> shrink-0"></span>
            <?= $flashConfig["msg"] ?>
        </div>
        <?php endif; ?>
