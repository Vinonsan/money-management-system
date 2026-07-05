<section class="py-20">
    <div class="max-w-3xl mx-auto px-6">
        <div class="text-center">
            <h1 class="text-4xl lg:text-5xl font-black text-gray-900">Book a Service</h1>
            <p class="mt-4 text-gray-500">Fill in the details and we will get back to you.</p>
        </div>
        <form method="POST" action="<?= BASE_URL ?>/booking" class="mt-12 space-y-5 bg-white border border-gray-200 rounded-2xl p-8 shadow-sm">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label class="mb-1 block text-xs font-semibold text-gray-600">Name</label>
                    <input type="text" name="name" required class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200" placeholder="Your name">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-gray-600">Email</label>
                    <input type="email" name="email" required class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200" placeholder="your@email.com">
                </div>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-gray-600">Date</label>
                <input type="date" name="date" required class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-gray-600">Notes</label>
                <textarea name="notes" rows="3" class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200" placeholder="Any special requests..."></textarea>
            </div>
            <button type="submit" class="w-full rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-3 transition">Submit Booking</button>
        </form>
    </div>
</section>