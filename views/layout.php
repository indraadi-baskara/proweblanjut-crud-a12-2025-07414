<?php declare(strict_types=1) ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory System</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style type="text/tailwindcss">
        @theme {
            --font-sans: 'DM Sans', sans-serif;
            --font-mono: 'DM Mono', monospace;
            --color-brand-50:  #f0fdf4;
            --color-brand-100: #dcfce7;
            --color-brand-600: #16a34a;
            --color-brand-700: #15803d;
            --color-brand-900: #14532d;
        }

        body {
            font-family: var(--font-sans);
        }
    </style>
</head>
<body class="bg-zinc-950 text-zinc-100 min-h-screen">

    <!-- Top nav -->
    <header class="border-b border-zinc-800 bg-zinc-900/80 backdrop-blur-sm sticky top-0 z-10">
        <div class="max-w-6xl mx-auto px-6 h-14 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="w-2 h-2 rounded-full bg-brand-600 shadow-[0_0_8px_#16a34a]"></span>
                <span class="font-semibold tracking-tight text-sm">Inventory System</span>
            </div>
            <span class="text-xs text-zinc-500 font-mono"><?= date(
                "d M Y",
            ) ?></span>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-6 py-10">

        <!-- Page header -->
        <div class="mb-8">
            <h1 class="text-2xl font-semibold tracking-tight">Items</h1>
            <p class="text-sm text-zinc-400 mt-1">
                <?= count($items) ?> total item<?= count($items) !== 1
     ? "s"
     : "" ?>
            </p>
        </div>

        <!-- Low stock alert banner -->
        <?php $lowStockItems = array_filter(
            $items,
            fn($item) => $item->isLowStock(5),
        ); ?>
        <?php if (!empty($lowStockItems)): ?>
        <div class="mb-6 flex items-start gap-3 bg-amber-950/60 border border-amber-800/60 text-amber-300 rounded-xl px-4 py-3 text-sm">
            <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
            </svg>
            <span>
                <strong class="font-semibold text-amber-200">Low stock warning</strong> —
                <?= implode(
                    ", ",
                    array_map(
                        fn($i) => htmlspecialchars(
                            $i->itemName,
                            ENT_QUOTES,
                            "UTF-8",
                        ),
                        $lowStockItems,
                    ),
                ) ?>
                <?= count($lowStockItems) === 1 ? "is" : "are" ?> running low.
            </span>
        </div>
        <?php endif; ?>

        <!-- Table card -->
        <div class="rounded-2xl border border-zinc-800 overflow-hidden bg-zinc-900">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-800">
                        <th class="text-left px-5 py-3.5 text-xs font-medium text-zinc-500 uppercase tracking-widest w-12">#</th>
                        <th class="text-left px-5 py-3.5 text-xs font-medium text-zinc-500 uppercase tracking-widest">Item name</th>
                        <th class="text-right px-5 py-3.5 text-xs font-medium text-zinc-500 uppercase tracking-widest">Quantity</th>
                        <th class="text-right px-5 py-3.5 text-xs font-medium text-zinc-500 uppercase tracking-widest">Price</th>
                        <th class="text-left px-5 py-3.5 text-xs font-medium text-zinc-500 uppercase tracking-widest">Entry date</th>
                        <th class="text-left px-5 py-3.5 text-xs font-medium text-zinc-500 uppercase tracking-widest">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-800/70">
                    <?php foreach ($items as $item): ?>
                    <tr class="hover:bg-zinc-800/40 transition-colors duration-150 group">
                        <td class="px-5 py-4 font-mono text-xs text-zinc-600"><?= $item->id ?></td>
                        <td class="px-5 py-4 font-medium text-zinc-100">
                            <?= htmlspecialchars(
                                $item->itemName,
                                ENT_QUOTES,
                                "UTF-8",
                            ) ?>
                        </td>
                        <td class="px-5 py-4 text-right font-mono text-zinc-300">
                            <?= number_format($item->quantity) ?>
                        </td>
                        <td class="px-5 py-4 text-right font-mono text-zinc-300">
                            Rp <?= number_format($item->price, 0, ",", ".") ?>
                        </td>
                        <td class="px-5 py-4 text-zinc-400">
                            <?= $item->entryDate->format("d M Y") ?>
                        </td>
                        <td class="px-5 py-4">
                            <?php if ($item->isLowStock(5)): ?>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-amber-950/80 text-amber-400 border border-amber-800/50">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                                    Low stock
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-brand-900/40 text-brand-600 border border-brand-700/30">
                                    <span class="w-1.5 h-1.5 rounded-full bg-brand-600"></span>
                                    OK
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </main>

</body>
</html>
