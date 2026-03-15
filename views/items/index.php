<?php declare(strict_types=1);
$pageTitle = "Inventory";
require __DIR__ . "/../layout.php";
require __DIR__ . "/../layout/header.php";
?>

<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight">Items</h1>
        <p class="text-sm text-zinc-400 mt-1"><?= $result[
            "total"
        ] ?> total item<?= $result["total"] !== 1 ? "s" : "" ?></p>
    </div>
</div>

<?php require __DIR__ . "/../partials/alert_banner.php"; ?>

<form method="GET" action="<?= BASE_URL ?>/" class="mb-5" id="search-form">
    <div class="relative">
        <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-500 pointer-events-none"
             fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
        </svg>
        <input
            type="search"
            name="search"
            id="search-input"
            value="<?= htmlspecialchars($search ?? "", ENT_QUOTES, "UTF-8") ?>"
            placeholder="Search items…"
            autocomplete="off"
            class="w-full bg-zinc-900 border border-zinc-800 rounded-xl pl-10 pr-4 py-2.5 text-sm text-zinc-100
                   placeholder-zinc-600 focus:outline-none focus:border-zinc-600 transition-colors"
        >
    </div>
</form>

<div id="table-wrapper">
    <?php require __DIR__ . "/../partials/table.php"; ?>
</div>

<script>
(function () {
    const input   = document.getElementById('search-input');
    const wrapper = document.getElementById('table-wrapper');
    let   timer;
    if (!input || !wrapper) return;
    input.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(function () {
            const q   = encodeURIComponent(input.value.trim());
            const url = '<?= BASE_URL ?>/?search=' + q;
            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.text())
                .then(html => { wrapper.innerHTML = html; })
                .catch(() => {});
            history.replaceState(null, '', url);
        }, 300);
    });
})();
</script>

<?php require __DIR__ . "/../layout/footer.php"; ?>
