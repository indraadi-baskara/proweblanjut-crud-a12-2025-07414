<?php declare(strict_types=1);
$pageTitle = "Edit item";
$old = $old ?? [];
$errors = $errors ?? [];
require __DIR__ . "/../layout.php";
require __DIR__ . "/../layout/header.php";
?>

<div class="mb-6">
    <a href="<?= BASE_URL ?>/" class="inline-flex items-center gap-1.5 text-xs text-zinc-500 hover:text-zinc-300 transition-colors mb-4">
        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
        </svg>
        Back
    </a>
    <h1 class="text-2xl font-semibold tracking-tight">Edit item</h1>
    <p class="text-sm text-zinc-500 mt-1 font-mono">#<?= $item->id ?></p>
</div>

<div class="max-w-lg mx-auto">
    <form method="POST" action="<?= BASE_URL ?>/items/update" enctype="multipart/form-data" class="space-y-5">
        <?php csrf(); ?>
        <input type="hidden" name="id" value="<?= $item->id ?>">

        <?php
        $fields = [
            [
                "name" => "item_name",
                "label" => "Item name",
                "type" => "text",
                "current" => $item->itemName,
            ],
            [
                "name" => "quantity",
                "label" => "Quantity",
                "type" => "number",
                "current" => $item->quantity,
            ],
            [
                "name" => "price",
                "label" => "Price (Rp)",
                "type" => "number",
                "current" => $item->price,
            ],
            [
                "name" => "entry_date",
                "label" => "Entry date",
                "type" => "date",
                "current" => $item->entryDate->format("Y-m-d"),
            ],
        ];
        foreach ($fields as $f):
            $hasError = isset($errors[$f["name"]]); ?>
        <div>
            <label for="<?= $f[
                "name"
            ] ?>" class="block text-xs font-medium text-zinc-400 mb-1.5">
                <?= $f["label"] ?>
            </label>
            <input
                type="<?= $f["type"] ?>"
                id="<?= $f["name"] ?>"
                name="<?= $f["name"] ?>"
                value="<?= old($f["name"], $f["current"], $old) ?>"
                <?= $f["type"] === "number" ? 'min="0" step="any"' : "" ?>
                class="w-full bg-zinc-900 border rounded-xl px-4 py-2.5 text-sm text-zinc-100
                       placeholder-zinc-600 focus:outline-none transition-colors
                       <?= $hasError
                           ? "border-red-700 focus:border-red-600"
                           : "border-zinc-800 focus:border-zinc-600" ?>"
            >
            <?php if ($hasError): ?>
                <p class="mt-1.5 text-xs text-red-400"><?= htmlspecialchars(
                    $errors[$f["name"]],
                    ENT_QUOTES,
                    "UTF-8",
                ) ?></p>
            <?php endif; ?>
        </div>
        <?php
        endforeach;
        ?>

        <!-- Image fieldset -->
        <fieldset class="border border-zinc-800 rounded-xl p-4">
            <legend class="text-sm font-medium text-zinc-300 px-2">Gambar Barang</legend>

            <!-- Current image preview -->
            <?php if ($item->imagePath !== null): ?>
                <div class="mt-3 mb-4">
                    <p class="text-xs font-medium text-zinc-400 mb-2">Gambar saat ini</p>
                    <img src="<?= htmlspecialchars($item->imagePath, ENT_QUOTES, 'UTF-8') ?>"
                         alt="<?= htmlspecialchars($item->itemName, ENT_QUOTES, 'UTF-8') ?>"
                         class="w-32 h-32 object-cover rounded-lg border border-zinc-700">
                </div>
            <?php endif; ?>

            <div class="mt-3">
                <label for="image" class="block text-xs font-medium text-zinc-400 mb-2">
                    <?php if ($item->imagePath !== null): ?>
                        Ganti gambar (opsional)
                    <?php else: ?>
                        Pilih gambar
                    <?php endif; ?>
                </label>
                <input
                    type="file"
                    id="image"
                    name="image"
                    accept="image/*"
                    class="block w-full text-sm text-zinc-400
                           file:mr-4 file:py-2.5 file:px-4
                           file:rounded-xl file:border-0
                           file:text-sm file:font-medium
                           file:bg-zinc-800 file:text-zinc-200
                           file:cursor-pointer
                           hover:file:bg-zinc-700 file:transition-colors"
                >
                <p class="mt-2 text-xs text-zinc-500">
                    <?php if ($item->imagePath !== null): ?>
                        Opsional. Biarkan kosong jika tidak ingin mengubah gambar. JPG, PNG, WebP, atau GIF — maks. 2 MB
                    <?php else: ?>
                        Opsional. JPG, PNG, WebP, atau GIF — maks. 2 MB
                    <?php endif; ?>
                </p>
                <?php if (isset($errors["image"])): ?>
                    <p class="mt-1.5 text-xs text-red-400"><?= htmlspecialchars(
                        $errors["image"],
                        ENT_QUOTES,
                        "UTF-8",
                    ) ?></p>
                <?php endif; ?>
            </div>
        </fieldset>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit"
                    class="bg-green-600 hover:bg-green-700 text-white text-sm font-medium px-5 py-2.5 rounded-xl transition-colors">
                Update item
            </button>
            <a href="<?= BASE_URL ?>/" class="text-sm text-zinc-500 hover:text-zinc-300 transition-colors">
                Cancel
            </a>
        </div>
    </form>
</div>

<?php require __DIR__ . "/../layout/footer.php"; ?>
