<?php declare(strict_types=1);
$pageTitle = "Register";
$old = $old ?? [];
$errors = $errors ?? [];
require __DIR__ . "/../layout.php";
require __DIR__ . "/../layout/header.php";
?>

<div class="min-h-screen flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-md">
        <div class="mb-8">
            <h1 class="text-3xl font-semibold tracking-tight mb-2">Create account</h1>
            <p class="text-sm text-zinc-400">Register to start managing your inventory</p>
        </div>

        <form method="POST" action="<?= BASE_URL ?>/auth/store" class="space-y-5">
            <?php csrf(); ?>

            <!-- General auth error (like duplicate username) -->
            <?php if (isset($errors["auth"])): ?>
                <div class="p-4 rounded-xl bg-red-950/40 border border-red-800/60 text-red-300 text-sm">
                    <?= htmlspecialchars($errors["auth"], ENT_QUOTES, "UTF-8") ?>
                </div>
            <?php endif; ?>

            <!-- Username field -->
            <div>
                <label for="username" class="block text-xs font-medium text-zinc-400 mb-1.5">
                    Username
                </label>
                <div class="text-xs text-zinc-500 mb-1.5">3-50 characters (letters, numbers, _, -)</div>
                <input
                    type="text"
                    id="username"
                    name="username"
                    value="<?= old("username", null, $old) ?>"
                    placeholder="johndoe"
                    autocomplete="username"
                    class="w-full bg-zinc-900 border rounded-xl px-4 py-2.5 text-sm text-zinc-100
                           placeholder-zinc-600 focus:outline-none transition-colors
                           <?= isset($errors["username"]) ? "border-red-700 focus:border-red-600" : "border-zinc-800 focus:border-zinc-600" ?>"
                >
                <?php if (isset($errors["username"])): ?>
                    <p class="mt-1.5 text-xs text-red-400"><?= htmlspecialchars($errors["username"], ENT_QUOTES, "UTF-8") ?></p>
                <?php endif; ?>
            </div>

            <!-- Email field -->
            <div>
                <label for="email" class="block text-xs font-medium text-zinc-400 mb-1.5">
                    Email
                </label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?= old("email", null, $old) ?>"
                    placeholder="john@example.com"
                    autocomplete="email"
                    class="w-full bg-zinc-900 border rounded-xl px-4 py-2.5 text-sm text-zinc-100
                           placeholder-zinc-600 focus:outline-none transition-colors
                           <?= isset($errors["email"]) ? "border-red-700 focus:border-red-600" : "border-zinc-800 focus:border-zinc-600" ?>"
                >
                <?php if (isset($errors["email"])): ?>
                    <p class="mt-1.5 text-xs text-red-400"><?= htmlspecialchars($errors["email"], ENT_QUOTES, "UTF-8") ?></p>
                <?php endif; ?>
            </div>

            <!-- Password field -->
            <div>
                <label for="password" class="block text-xs font-medium text-zinc-400 mb-1.5">
                    Password
                </label>
                <div class="text-xs text-zinc-500 mb-1.5">
                    At least 8 characters with uppercase, number, and special character
                </div>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="••••••••"
                    autocomplete="new-password"
                    class="w-full bg-zinc-900 border rounded-xl px-4 py-2.5 text-sm text-zinc-100
                           placeholder-zinc-600 focus:outline-none transition-colors
                           <?= isset($errors["password"]) ? "border-red-700 focus:border-red-600" : "border-zinc-800 focus:border-zinc-600" ?>"
                >
                <?php if (isset($errors["password"])): ?>
                    <p class="mt-1.5 text-xs text-red-400"><?= htmlspecialchars($errors["password"], ENT_QUOTES, "UTF-8") ?></p>
                <?php endif; ?>
            </div>

            <!-- Confirm password field -->
            <div>
                <label for="confirm_password" class="block text-xs font-medium text-zinc-400 mb-1.5">
                    Confirm password
                </label>
                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    placeholder="••••••••"
                    autocomplete="new-password"
                    class="w-full bg-zinc-900 border rounded-xl px-4 py-2.5 text-sm text-zinc-100
                           placeholder-zinc-600 focus:outline-none transition-colors
                           <?= isset($errors["confirm_password"]) ? "border-red-700 focus:border-red-600" : "border-zinc-800 focus:border-zinc-600" ?>"
                >
                <?php if (isset($errors["confirm_password"])): ?>
                    <p class="mt-1.5 text-xs text-red-400"><?= htmlspecialchars($errors["confirm_password"], ENT_QUOTES, "UTF-8") ?></p>
                <?php endif; ?>
            </div>

            <!-- Submit button -->
            <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-2.5 px-4 rounded-xl transition-colors text-sm">
                Create account
            </button>
        </form>

        <!-- Login link -->
        <div class="mt-6 text-center">
            <p class="text-xs text-zinc-400">
                Already have an account?
                <a href="<?= BASE_URL ?>/auth/login" class="text-green-400 hover:text-green-300 font-medium transition-colors">
                    Sign in here
                </a>
            </p>
        </div>
    </div>
</div>

<?php require __DIR__ . "/../layout/footer.php"; ?>
