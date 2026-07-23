<?php
/**
 * @var int        $year        Selected year
 * @var int        $month       Selected month (0 = all)
 * @var array      $months      Monthly data [1-12 => ['total'=>float, 'txns'=>int]]
 * @var array      $yearlyData  Yearly summaries
 * @var array      $details     Transaction details
 * @var float      $ytdTotal    Year-to-date total
 * @var array      $availYears  Available years
 * @var array      $locations   All active locations
 * @var array      $wards       All active wards
 * @var int        $locationId  Selected location filter
 * @var int        $wardId      Selected ward filter
 */

use Components\Base\Badge;

$pageTitle = 'Reports';
$monthNames = ['', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
?>

<div class="space-y-6 max-w-7xl mx-auto py-2">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white/60 backdrop-blur-md p-6 rounded-2xl border border-slate-200/80 shadow-sm">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900"><?= e($pageTitle) ?></h1>
            <p class="mt-1 text-sm text-slate-500 font-medium">Monthly & yearly collection reports.</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-4">
        <form method="GET" action="<?= BASE_URL ?>/admin/reports" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block mb-1 text-xs font-semibold text-slate-600">Year</label>
                <select name="year"
                    class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 font-medium focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/20">
                    <?php foreach ($availYears as $y): 
                        $yVal = (int) ($y['y'] ?? 0);
                        $sel = $yVal === $year ? ' selected' : '';
                    ?>
                        <option value="<?= $yVal ?>"<?= $sel ?>><?= $yVal ?></option>
                    <?php endforeach; ?>
                    <?php if (empty($availYears)): ?>
                        <option value="<?= date('Y') ?>" selected><?= date('Y') ?></option>
                    <?php endif; ?>
                </select>
            </div>

            <div>
                <label class="block mb-1 text-xs font-semibold text-slate-600">Month</label>
                <select name="month"
                    class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 font-medium focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/20">
                    <option value="0"<?= $month === 0 ? ' selected' : '' ?>>All Months</option>
                    <?php for ($m = 1; $m <= 12; $m++): 
                        $sel = $m === $month ? ' selected' : '';
                    ?>
                        <option value="<?= $m ?>"<?= $sel ?>><?= $monthNames[$m] ?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <div>
                <label class="block mb-1 text-xs font-semibold text-slate-600">Location</label>
                <select name="location_id"
                    class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 font-medium focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/20">
                    <option value="0">All Locations</option>
                    <?php foreach ($locations as $loc): 
                        $id = (int) ($loc['id'] ?? 0);
                        $sel = $id === $locationId ? ' selected' : '';
                    ?>
                        <option value="<?= $id ?>"<?= $sel ?>><?= e($loc['name'] ?? '') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block mb-1 text-xs font-semibold text-slate-600">Ward</label>
                <select name="ward_id"
                    class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 font-medium focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/20">
                    <option value="0">All Wards</option>
                    <?php foreach ($wards as $w): 
                        $id = (int) ($w['id'] ?? 0);
                        $sel = $id === $wardId ? ' selected' : '';
                    ?>
                        <option value="<?= $id ?>"<?= $sel ?>>Ward #<?= htmlspecialchars((string) ($w['ward_number'] ?? ''), ENT_QUOTES) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit"
                class="inline-flex items-center gap-2 rounded-xl bg-primary-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-primary-600/20 transition hover:bg-primary-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                </svg>
                Filter
            </button>

            <a href="<?= BASE_URL ?>/admin/reports"
                class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3.5 py-2 text-sm font-semibold text-slate-600 shadow-sm transition hover:bg-slate-50">
                Reset
            </a>
        </form>
    </div>

    <!-- YTD Summary Card -->
    <div class="bg-gradient-to-br from-primary-700 via-primary-800 to-primary-950 rounded-2xl p-6 shadow-xl">
        <p class="text-sm font-medium text-white/70">Year-to-Date Collection (<?= $year ?>)</p>
        <p class="mt-2 text-4xl font-bold text-white">Rs. <?= number_format($ytdTotal, 2) ?></p>
    </div>

    <!-- Monthly Breakdown -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100">
            <h2 class="text-lg font-semibold text-slate-800">Monthly Breakdown — <?= $year ?></h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Month</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Transactions</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Total Collected</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php 
                    $grandTotal = 0;
                    $grandTxns = 0;
                    foreach ($months as $m => $data): 
                        $grandTotal += $data['total'];
                        $grandTxns += $data['txns'];
                    ?>
                    <tr class="hover:bg-slate-50/50 transition-colors <?= $m === $month ? 'bg-primary-50/50' : '' ?>">
                        <td class="px-4 py-3 font-medium text-slate-800">
                            <a href="<?= BASE_URL ?>/admin/reports?year=<?= $year ?>&month=<?= $m ?>&location_id=<?= $locationId ?>&ward_id=<?= $wardId ?>"
                               class="hover:text-primary-700 transition-colors">
                                <?= $monthNames[$m] ?>
                            </a>
                        </td>
                        <td class="px-4 py-3 text-right text-slate-600"><?= $data['txns'] ?></td>
                        <td class="px-4 py-3 text-right font-semibold text-slate-800">Rs. <?= number_format($data['total'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="bg-slate-100/50 border-t-2 border-slate-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 uppercase">Total</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-700 uppercase"><?= $grandTxns ?></th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-700 uppercase">Rs. <?= number_format($grandTotal, 2) ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Yearly Summary -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100">
            <h2 class="text-lg font-semibold text-slate-800">Yearly Summary</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Year</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Transactions</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Total Collected</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($yearlyData as $yd): 
                        $yVal = (int) ($yd['y'] ?? 0);
                        $yTotal = (float) ($yd['total'] ?? 0);
                        $yTxns = (int) ($yd['txns'] ?? 0);
                    ?>
                    <tr class="hover:bg-slate-50/50 transition-colors <?= $yVal === $year ? 'bg-primary-50/50 font-semibold' : '' ?>">
                        <td class="px-4 py-3 text-slate-800">
                            <a href="<?= BASE_URL ?>/admin/reports?year=<?= $yVal ?>&location_id=<?= $locationId ?>&ward_id=<?= $wardId ?>"
                               class="hover:text-primary-700 transition-colors">
                                <?= $yVal ?>
                            </a>
                        </td>
                        <td class="px-4 py-3 text-right text-slate-600"><?= $yTxns ?></td>
                        <td class="px-4 py-3 text-right font-semibold text-slate-800">Rs. <?= number_format($yTotal, 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Transaction Details -->
    <?php if ($month > 0): ?>
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100">
            <h2 class="text-lg font-semibold text-slate-800">
                Transaction Details — <?= $monthNames[$month] ?> <?= $year ?>
            </h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Member</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Phone</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Card</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Location</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Ward</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Amount</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-slate-600 uppercase">Months</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($details)): ?>
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-sm text-slate-400">No transactions found.</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($details as $d): ?>
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-4 py-2.5 text-slate-600 whitespace-nowrap"><?= e($d['created_at'] ?? '') ?></td>
                        <td class="px-4 py-2.5 font-medium text-slate-800 whitespace-nowrap"><?= e($d['member_name'] ?? '') ?></td>
                        <td class="px-4 py-2.5 text-slate-600 whitespace-nowrap"><?= e($d['phone'] ?? '') ?></td>
                        <td class="px-4 py-2.5 text-slate-600 whitespace-nowrap">
                            <?= !empty($d['card_number']) ? (int) $d['card_number'] : '<span class="text-slate-300">&mdash;</span>' ?>
                        </td>
                        <td class="px-4 py-2.5 text-slate-600 whitespace-nowrap"><?= e($d['location_name'] ?? '') ?: '<span class="text-slate-300">&mdash;</span>' ?></td>
                        <td class="px-4 py-2.5 text-slate-600 whitespace-nowrap">
                            <?= !empty($d['ward_number']) ? 'Ward #' . htmlspecialchars((string) $d['ward_number'], ENT_QUOTES) : '<span class="text-slate-300">&mdash;</span>' ?>
                        </td>
                        <td class="px-4 py-2.5 text-right font-semibold text-slate-800 whitespace-nowrap">Rs. <?= number_format((float) ($d['amount'] ?? 0), 2) ?></td>
                        <td class="px-4 py-2.5 text-center text-slate-600 whitespace-nowrap"><?= (int) ($d['months_covered'] ?? 0) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <?php if (!empty($details)): 
                    $sumAmount = array_sum(array_column($details, 'amount'));
                ?>
                <tfoot class="bg-slate-100/50 border-t-2 border-slate-200">
                    <tr>
                        <th colspan="6" class="px-4 py-3 text-right text-xs font-semibold text-slate-700 uppercase">Total</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-700 uppercase">Rs. <?= number_format($sumAmount, 2) ?></th>
                        <th></th>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>
