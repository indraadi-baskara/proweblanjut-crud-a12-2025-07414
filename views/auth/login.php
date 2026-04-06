<?php declare(strict_types=1);
$pageTitle = "Login";
$old = $old ?? [];
$errors = $errors ?? [];
require __DIR__ . "/../layout.php";
require __DIR__ . "/../layout/header.php";
?>

<div class="min-h-screen flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-md">
        <div class="mb-8">
            <h1 class="text-3xl font-semibold tracking-tight mb-2">Welcome back</h1>
            <p class="text-sm text-zinc-400">Sign in to your account to manage inventory</p>
        </div>

        <form method="POST" action="<?= BASE_URL ?>/auth/auth" class="space-y-5">
            <?php csrf(); ?>

            <!-- General auth error -->
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
                <input
                    type="text"
                    id="username"
                    name="username"
                    value="<?= old("username", null, $old) ?>"
                    placeholder="admin"
                    autocomplete="username"
                    class="w-full bg-zinc-900 border rounded-xl px-4 py-2.5 text-sm text-zinc-100
                           placeholder-zinc-600 focus:outline-none transition-colors
                           <?= isset($errors["username"]) ? "border-red-700 focus:border-red-600" : "border-zinc-800 focus:border-zinc-600" ?>"
                >
                <?php if (isset($errors["username"])): ?>
                    <p class="mt-1.5 text-xs text-red-400"><?= htmlspecialchars($errors["username"], ENT_QUOTES, "UTF-8") ?></p>
                <?php endif; ?>
            </div>

            <!-- Password field -->
            <div>
                <label for="password" class="block text-xs font-medium text-zinc-400 mb-1.5">
                    Password
                </label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="••••••••"
                    autocomplete="current-password"
                    class="w-full bg-zinc-900 border rounded-xl px-4 py-2.5 text-sm text-zinc-100
                           placeholder-zinc-600 focus:outline-none transition-colors
                           <?= isset($errors["password"]) ? "border-red-700 focus:border-red-600" : "border-zinc-800 focus:border-zinc-600" ?>"
                >
                <?php if (isset($errors["password"])): ?>
                    <p class="mt-1.5 text-xs text-red-400"><?= htmlspecialchars($errors["password"], ENT_QUOTES, "UTF-8") ?></p>
                <?php endif; ?>
            </div>

            <!-- Remember me -->
            <div class="flex items-center gap-2">
                <input
                    type="checkbox"
                    id="remember_me"
                    name="remember_me"
                    value="1"
                    class="w-4 h-4 rounded accent-green-600 cursor-pointer"
                >
                <label for="remember_me" class="text-xs text-zinc-400 cursor-pointer">
                    Remember me for 30 days
                </label>
            </div>

            <!-- Submit button -->
            <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-2.5 px-4 rounded-xl transition-colors text-sm">
                Sign in
            </button>
        </form>

        <!-- Register link -->
        <div class="mt-6 text-center">
            <p class="text-xs text-zinc-400">
                Don't have an account?
                <a href="<?= BASE_URL ?>/auth/register" class="text-green-400 hover:text-green-300 font-medium transition-colors">
                    Register here
                </a>
            </p>
        </div>

        <!-- Demo info -->
        <div class="mt-8 p-4 rounded-xl bg-blue-950/30 border border-blue-800/40 text-blue-300 text-xs space-y-1">
            <p class="font-medium">Demo credentials:</p>
            <p>Username: <code class="text-white font-mono">admin</code></p>
            <p>Password: <code class="text-white font-mono">Admin@12345</code></p>
        </div>
    </div>
</div>

<?php require __DIR__ . "/../layout/footer.php"; ?>
