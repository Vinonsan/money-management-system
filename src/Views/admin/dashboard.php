<!-- Stats Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm hover:shadow-md transition">
        <p class="text-sm font-medium text-gray-500">Total Users</p>
        <p class="mt-2 text-3xl font-bold text-gray-900">1,234</p>
        <p class="mt-1 text-xs text-green-600">+12% from last month</p>
    </div>
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm hover:shadow-md transition">
        <p class="text-sm font-medium text-gray-500">Revenue</p>
        <p class="mt-2 text-3xl font-bold text-gray-900">$45,678</p>
        <p class="mt-1 text-xs text-green-600">+8% from last month</p>
    </div>
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm hover:shadow-md transition">
        <p class="text-sm font-medium text-gray-500">Orders</p>
        <p class="mt-2 text-3xl font-bold text-gray-900">567</p>
        <p class="mt-1 text-xs text-red-600">-3% from last month</p>
    </div>
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm hover:shadow-md transition">
        <p class="text-sm font-medium text-gray-500">Active Now</p>
        <p class="mt-2 text-3xl font-bold text-gray-900">23</p>
        <p class="mt-1 text-xs text-gray-500">Currently online</p>
    </div>
</div>

<!-- Recent Activity Table -->
<div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
    <div class="border-b border-gray-100 px-6 py-4">
        <h3 class="text-sm font-bold text-gray-800">Recent Activity</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 text-left">
                    <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase">User</th>
                    <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase">Action</th>
                    <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase">Date</th>
                    <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">John Doe</td>
                    <td class="px-6 py-4">Created invoice #1234</td>
                    <td class="px-6 py-4 text-gray-500">2 mins ago</td>
                    <td class="px-6 py-4"><span class="inline-flex rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-700">Completed</span></td>
                </tr>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">Jane Smith</td>
                    <td class="px-6 py-4">Updated user profile</td>
                    <td class="px-6 py-4 text-gray-500">15 mins ago</td>
                    <td class="px-6 py-4"><span class="inline-flex rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-semibold text-yellow-700">Pending</span></td>
                </tr>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">Bob Wilson</td>
                    <td class="px-6 py-4">Deleted product #567</td>
                    <td class="px-6 py-4 text-gray-500">1 hour ago</td>
                    <td class="px-6 py-4"><span class="inline-flex rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-700">Failed</span></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>