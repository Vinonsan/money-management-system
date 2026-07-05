<!-- Hero Section -->
<section class="bg-gradient-to-br from-blue-50 via-white to-white">
    <div class="max-w-6xl mx-auto px-6 py-24 lg:py-32">
        <div class="max-w-3xl">
            <p class="text-blue-600 font-bold uppercase tracking-widest text-xs mb-4">Welcome</p>
            <h1 class="text-5xl lg:text-6xl font-black text-gray-900 leading-tight">
                Build Something <span class="text-blue-600">Amazing</span>
            </h1>
            <p class="mt-6 text-lg text-gray-600 max-w-2xl leading-relaxed">
                A clean, modern PHP application structure with component-based architecture, 
                Tailwind CSS, and Alpine.js. Ready for your next project.
            </p>
            <div class="mt-10 flex flex-wrap gap-4">
                <a href="<?= BASE_URL ?>/about" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-bold px-8 py-4 rounded-xl shadow-lg shadow-blue-600/20 transition-all hover:-translate-y-0.5">
                    Get Started &rarr;
                </a>
                <a href="<?= BASE_URL ?>/contact" class="inline-flex items-center gap-2 bg-white hover:bg-gray-50 border border-gray-300 text-gray-700 font-semibold px-8 py-4 rounded-xl transition-all hover:-translate-y-0.5">
                    Contact Us
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Feature Cards -->
<section class="py-20 bg-white">
    <div class="max-w-6xl mx-auto px-6">
        <h2 class="text-3xl font-black text-center text-gray-900">Why This Structure?</h2>
        <p class="mt-4 text-gray-500 text-center max-w-2xl mx-auto">Organized, scalable, and developer-friendly.</p>
        <div class="mt-12 grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="bg-white p-8 rounded-2xl border border-gray-200 shadow-sm hover:shadow-lg transition">
                <div class="w-12 h-12 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center text-xl font-bold">1</div>
                <h3 class="mt-5 text-lg font-bold text-gray-900">Component-Based</h3>
                <p class="mt-2 text-sm text-gray-500">Reusable UI components organized in Base, Modal, and Drawer folders.</p>
            </div>
            <div class="bg-white p-8 rounded-2xl border border-gray-200 shadow-sm hover:shadow-lg transition">
                <div class="w-12 h-12 rounded-xl bg-green-100 text-green-600 flex items-center justify-center text-xl font-bold">2</div>
                <h3 class="mt-5 text-lg font-bold text-gray-900">Layout System</h3>
                <p class="mt-2 text-sm text-gray-500">Auth and App layouts with sidebar, nav, and child view inclusion.</p>
            </div>
            <div class="bg-white p-8 rounded-2xl border border-gray-200 shadow-sm hover:shadow-lg transition">
                <div class="w-12 h-12 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center text-xl font-bold">3</div>
                <h3 class="mt-5 text-lg font-bold text-gray-900">Global DB</h3>
                <p class="mt-2 text-sm text-gray-500">Singleton Database model with fetch, fetchAll, execute, insert helpers.</p>
            </div>
        </div>
    </div>
</section>