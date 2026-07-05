<!-- src/Views/auth/login.php -->
<form method="POST" action="<?= BASE_URL ?>/login" class="space-y-5">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
    <div>
        <label class="mb-1 block text-xs font-semibold text-gray-600">Email</label>
        <input type="email" name="email" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200" placeholder="you@example.com">
    </div>
    <div>
        <label class="mb-1 block text-xs font-semibold text-gray-600">Password</label>
        <input type="password" name="password" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200" placeholder="Enter password">
    </div>
    <button type="submit" class="w-full rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 transition">Sign In</button>
    <p class="text-center text-xs text-gray-500">
        No account? <a href="<?= BASE_URL ?>/register" class="text-blue-600 hover:underline">Create one</a>
    </p>
</form>