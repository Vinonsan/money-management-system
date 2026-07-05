<section class="py-20">
    <div class="max-w-4xl mx-auto px-6">
        <div class="text-center">
            <p class="text-blue-600 font-bold uppercase tracking-widest text-xs">Contact</p>
            <h1 class="mt-2 text-4xl lg:text-5xl font-black text-gray-900">Get In Touch</h1>
            <p class="mt-4 text-gray-500">Have a question? We would love to hear from you.</p>
        </div>
        <div class="mt-12 grid grid-cols-1 lg:grid-cols-2 gap-12">
            <!-- Form -->
            <div>
                <form method="POST" action="<?= BASE_URL ?>/contact" class="space-y-5">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-gray-600">Full Name</label>
                        <input type="text" name="name" required class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200" placeholder="John Doe">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-gray-600">Email</label>
                        <input type="email" name="email" required class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200" placeholder="john@example.com">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-gray-600">Message</label>
                        <textarea name="message" rows="4" required class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200" placeholder="Your message..."></textarea>
                    </div>
                    <button type="submit" class="w-full rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-3 transition">Send Message</button>
                </form>
            </div>
            <!-- Info -->
            <div class="space-y-6">
                <div class="flex gap-4">
                    <div class="w-12 h-12 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center shrink-0">@</div>
                    <div>
                        <p class="font-bold text-gray-900">Email</p>
                        <p class="text-sm text-gray-500">info@example.com</p>
                    </div>
                </div>
                <div class="flex gap-4">
                    <div class="w-12 h-12 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center shrink-0">#</div>
                    <div>
                        <p class="font-bold text-gray-900">Phone</p>
                        <p class="text-sm text-gray-500">+1 (555) 000-0000</p>
                    </div>
                </div>
                <div class="flex gap-4">
                    <div class="w-12 h-12 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center shrink-0">📍</div>
                    <div>
                        <p class="font-bold text-gray-900">Address</p>
                        <p class="text-sm text-gray-500">123 Main Street, City</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>