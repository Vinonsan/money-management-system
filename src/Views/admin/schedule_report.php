<?php
/**
 * @var int    $totalScheduled  Total messages scheduled
 * @var int    $sentToday       Messages sent today
 * @var int    $pendingCount    Pending messages
 * @var int    $failedCount     Failed messages
 * @var array  $recentMessages  Recent 50 messages
 * @var array  $upcomingMessages Upcoming scheduled messages
 */
?>
<div class="mx-auto max-w-6xl space-y-6 py-2">

    <!-- Header -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-primary-700 via-primary-800 to-primary-950 p-6 shadow-xl sm:p-8">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(255,255,255,0.08),transparent_60%)]"></div>
        <div class="relative">
            <h1 class="text-2xl font-bold tracking-tight text-white">Schedule Report</h1>
            <p class="mt-1 text-sm font-medium text-white/70">Overview of all scheduled messages and delivery status.</p>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="rounded-xl bg-gradient-to-br from-blue-500 to-blue-700 p-5 shadow-md text-center">
            <p class="text-xs font-semibold text-blue-100 uppercase tracking-wider">Total Scheduled</p>
            <p class="mt-1 text-3xl font-bold text-white"><?= (int) $totalScheduled ?></p>
        </div>
        <div class="rounded-xl bg-gradient-to-br from-green-500 to-green-700 p-5 shadow-md text-center">
            <p class="text-xs font-semibold text-green-100 uppercase tracking-wider">Sent Today</p>
            <p class="mt-1 text-3xl font-bold text-white"><?= (int) $sentToday ?></p>
        </div>
        <div class="rounded-xl bg-gradient-to-br from-amber-500 to-amber-700 p-5 shadow-md text-center">
            <p class="text-xs font-semibold text-amber-100 uppercase tracking-wider">Pending</p>
            <p class="mt-1 text-3xl font-bold text-white"><?= (int) $pendingCount ?></p>
        </div>
        <div class="rounded-xl bg-gradient-to-br from-red-500 to-red-700 p-5 shadow-md text-center">
            <p class="text-xs font-semibold text-red-100 uppercase tracking-wider">Failed</p>
            <p class="mt-1 text-3xl font-bold text-white"><?= (int) $failedCount ?></p>
        </div>
    </div>

    <!-- Upcoming Schedules -->
    <div class="rounded-2xl border border-primary-200/60 bg-white p-6 shadow-sm">
        <div class="flex items-center gap-2 mb-4">
            <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-amber-100 text-amber-700">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </span>
            <h3 class="text-base font-bold text-primary-800">Upcoming Schedules</h3>
        </div>
        <?php if (empty($upcomingMessages)): ?>
            <div class="py-6 text-center">
                <p class="text-sm text-primary-400">No upcoming scheduled messages.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-primary-100">
                            <th class="px-3 py-2 font-semibold text-primary-600">Date</th>
                            <th class="px-3 py-2 font-semibold text-primary-600">Member</th>
                            <th class="px-3 py-2 font-semibold text-primary-600">Phone</th>
                            <th class="px-3 py-2 font-semibold text-primary-600">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($upcomingMessages as $sm): ?>
                            <tr class="border-b border-primary-50 hover:bg-primary-50/50">
                                <td class="px-3 py-2.5 text-primary-800"><?= e($sm['scheduled_date']) ?></td>
                                <td class="px-3 py-2.5 font-medium text-primary-900"><?= e($sm['member_name'] ?? '') ?></td>
                                <td class="px-3 py-2.5 text-primary-600"><?= e($sm['member_phone'] ?? '') ?></td>
                                <td class="px-3 py-2.5">
                                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-700">
                                        Pending
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Recent Activity -->
    <div class="rounded-2xl border border-primary-200/60 bg-white p-6 shadow-sm">
        <div class="flex items-center gap-2 mb-4">
            <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-primary-100 text-primary-700">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
            </span>
            <h3 class="text-base font-bold text-primary-800">Recent Activity</h3>
        </div>
        <?php if (empty($recentMessages)): ?>
            <div class="py-6 text-center">
                <p class="text-sm text-primary-400">No messages scheduled yet.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-primary-100">
                            <th class="px-3 py-2 font-semibold text-primary-600">Date</th>
                            <th class="px-3 py-2 font-semibold text-primary-600">Member</th>
                            <th class="px-3 py-2 font-semibold text-primary-600">Phone</th>
                            <th class="px-3 py-2 font-semibold text-primary-600">Scheduled</th>
                            <th class="px-3 py-2 font-semibold text-primary-600">Status</th>
                            <th class="px-3 py-2 font-semibold text-primary-600">Sent At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentMessages as $sm): ?>
                            <?php
                                $statusColors = [
                                    'pending' => 'bg-amber-100 text-amber-700',
                                    'sent' => 'bg-green-100 text-green-700',
                                    'failed' => 'bg-red-100 text-red-700',
                                ];
                                $color = $statusColors[$sm['status']] ?? 'bg-gray-100 text-gray-700';
                            ?>
                            <tr class="border-b border-primary-50 hover:bg-primary-50/50">
                                <td class="px-3 py-2.5 text-primary-800"><?= e(date('d M Y', strtotime($sm['created_at']))) ?></td>
                                <td class="px-3 py-2.5 font-medium text-primary-900"><?= e($sm['member_name'] ?? '') ?></td>
                                <td class="px-3 py-2.5 text-primary-600"><?= e($sm['member_phone'] ?? '') ?></td>
                                <td class="px-3 py-2.5 text-primary-600"><?= e($sm['scheduled_date']) ?></td>
                                <td class="px-3 py-2.5">
                                    <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium <?= $color ?>">
                                        <?= ucfirst(e($sm['status'])) ?>
                                    </span>
                                </td>
                                <td class="px-3 py-2.5 text-primary-600">
                                    <?= $sm['sent_at'] ? e(date('d M Y H:i', strtotime($sm['sent_at']))) : '-' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</div>
