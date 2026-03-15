<?php declare(strict_types=1) ?>

<!-- Table -->
<div class="rounded-2xl border border-zinc-800 overflow-hidden bg-zinc-900" id="items-table">
    <?php if (empty($result["items"])): ?>
        <div class="py-20 text-center text-zinc-500 text-sm">
            No items found<?= $result["search"] ?? "" !== ""
                ? ' for "' .
                    htmlspecialchars(
                        $result["search"] ?? "",
                        ENT_QUOTES,
                        "UTF-8",
                    ) .
                    '"'
                : "" ?>.
        </div>
    <?php else: ?>
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-zinc-800">
                <th class="text-left px-5 py-3.5 text-xs font-medium text-zinc-500 uppercase tracking-widest w-12">#</th>
                <th class="text-left px-5 py-3.5 text-xs font-medium text-zinc-500 uppercase tracking-widest">Item name</th>
                <th class="text-right px-5 py-3.5 text-xs font-medium text-zinc-500 uppercase tracking-widest">Quantity</th>
                <th class="text-right px-5 py-3.5 text-xs font-medium text-zinc-500 uppercase tracking-widest">Price</th>
                <th class="text-left px-5 py-3.5 text-xs font-medium text-zinc-500 uppercase tracking-widest">Entry date</th>
                <th class="text-left px-5 py-3.5 text-xs font-medium text-zinc-500 uppercase tracking-widest">Status</th>
                <th class="text-left px-5 py-3.5 text-xs font-medium text-zinc-500 uppercase tracking-widest">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-zinc-800/70">
            <?php foreach ($result["items"] as $item): ?>
            <tr class="hover:bg-zinc-800/40 transition-colors duration-150">
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
                    <?php if ($item->isLowStock($threshold)): ?>
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
                <td class="px-5 py-4">
                    <div class="flex items-center gap-3">
                        <a href="/proweblanjut-crud-a12-2025-07414/items/edit?id=<?= $item->id ?>"
                           class="text-xs text-zinc-400 hover:text-zinc-100 transition-colors">
                            Edit
                        </a>
                        <form method="POST" action="/proweblanjut-crud-a12-2025-07414/items/delete"
                              onsubmit="return confirm('Delete <?= htmlspecialchars(
                                  $item->itemName,
                                  ENT_QUOTES,
                                  "UTF-8",
                              ) ?>?')">
                            <?php echo '<input type="hidden" name="csrf_token" value="' .
                                htmlspecialchars(
                                    $_SESSION["csrf_token"],
                                    ENT_QUOTES,
                                    "UTF-8",
                                ) .
                                '">'; ?>
                            <input type="hidden" name="id" value="<?= $item->id ?>">
                            <button type="submit"
                                    class="text-xs text-red-500 hover:text-red-400 transition-colors">
                                Delete
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<!-- Pagination -->
<?php if ($result["total_pages"] > 1): ?>
<div class="mt-5 flex items-center justify-between text-xs text-zinc-500">
    <span>
        Showing
        <?= ($result["current_page"] - 1) * $result["per_page"] + 1 ?>–<?= min(
    $result["current_page"] * $result["per_page"],
    $result["total"],
) ?>
        of <?= $result["total"] ?> items
    </span>
    <div class="flex items-center gap-1">
        <?php for ($p = 1; $p <= $result["total_pages"]; $p++): ?>
            <?php
            $searchParam =
                isset($search) && $search !== ""
                    ? "&search=" . urlencode($search)
                    : "";
            $isActive = $p === $result["current_page"];
            ?>
            <a href="/proweblanjut-crud-a12-2025-07414/?page=<?= $p .
                $searchParam ?>"
               class="px-3 py-1.5 rounded-lg font-mono transition-colors
                      <?= $isActive
                          ? "bg-zinc-700 text-zinc-100"
                          : "hover:bg-zinc-800 text-zinc-500 hover:text-zinc-300" ?>">
                <?= $p ?>
            </a>
        <?php endfor; ?>
    </div>
</div>
<?php endif; ?>
