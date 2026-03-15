<?php declare(strict_types=1);
$pageTitle = "404 — Not Found";
require __DIR__ . "/layout.php";
require __DIR__ . "/layout/header.php";
?>

<div class="flex flex-col items-center justify-center py-32 text-center">
    <p class="font-mono text-6xl font-medium text-zinc-700 mb-4">404</p>
    <p class="text-zinc-400 text-sm mb-8">The page you're looking for doesn't exist.</p>
    <a href="<?= BASE_URL ?>/"
       class="text-xs font-medium text-green-600 hover:text-green-500 underline underline-offset-4 transition-colors">
        Back to inventory
    </a>
</div>

<?php require __DIR__ . "/layout/footer.php"; ?>
