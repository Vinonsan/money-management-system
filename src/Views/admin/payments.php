<?php
use Components\Base\Badge;
use Components\Base\DataTable;
use Components\Base\Input;

/**
 * @var array $locations List of active locations
 * @var array $wards     List of wards
 */

$pageTitle = 'Payments';
?>

<div class="mx-auto max-w-7xl space-y-6 py-2"
     x-data="paymentManager()">

    <div class="flex flex-col justify-between gap-4 rounded-2xl border border-slate-200/80 bg-white/60 p-6 shadow-sm backdrop-blur-md sm:flex-row sm:items-center">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900"><?= e($pageTitle) ?></h1>
            <p class="mt-1 text-sm font-medium text-slate-500">Record and manage user payments.</p>
        </div>
    </div>

    <!-- Search User -->
    <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm">
        <div class="relative max-w-md">
            <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input type="text" x-model="searchQuery" @input.debounce.400ms="searchUsers()"
                @keydown.enter.prevent="searchUsers()"
                placeholder="Search by name or card number..."
                class="w-full rounded-lg border border-slate-200 bg-white py-2.5 pl-9 pr-3 text-sm text-slate-700 placeholder-slate-400 transition-all focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/10">
        </div>

        <!-- Search Results -->
        <div x-show="searchResults.length > 0" class="mt-3">
            <div class="space-y-1.5 max-h-60 overflow-y-auto">
                <template x-for="u in searchResults" :key="u.id">
                    <div @click="selectUser(u)"
                        class="flex items-center justify-between gap-4 p-3 rounded-xl border border-slate-200 cursor-pointer transition hover:border-emerald-300 hover:bg-emerald-50/50"
                        :class="selectedUser?.id === u.id ? 'border-emerald-400 bg-emerald-50 ring-2 ring-emerald-200' : ''">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-slate-800" x-text="u.name"></p>
                            <p class="text-xs text-slate-500">
                                <span x-text="u.card_number ? 'Card: ' + u.card_number + ' | ' : ''"></span>
                                <span x-text="u.phone"></span>
                                <span x-text="u.location_name ? ' | ' + u.location_name : ''"></span>
                                <span x-text="u.ward_number ? ' | Ward #' + u.ward_number : ''"></span>
                            </p>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="text-sm font-bold text-emerald-700" x-text="'Rs. ' + parseFloat(u.monthly_amount || 0).toFixed(2)"></p>
                            <p class="text-xs text-slate-400">/month</p>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <div x-show="searchResults.length === 0 && searched && !selectedUser" class="mt-3 text-center py-4 text-sm text-slate-400">
            No users found.
        </div>
    </div>

    <!-- Payment Info & Form -->
    <div x-show="selectedUser" x-cloak class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm">

        <!-- User summary -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
            <div class="rounded-xl bg-slate-50 p-3 border border-slate-100">
                <p class="text-xs text-slate-500 font-medium">Monthly Amount</p>
                <p class="text-lg font-bold text-slate-800" x-text="'Rs. ' + parseFloat(selectedUser?.monthly_amount || 0).toFixed(2)"></p>
            </div>
            <div class="rounded-xl bg-slate-50 p-3 border border-slate-100">
                <p class="text-xs text-slate-500 font-medium">Last Paid</p>
                <p class="text-lg font-bold text-slate-800" x-text="paymentInfo?.last_payment?.to_month ? formatDate(paymentInfo.last_payment.to_month) : 'Never'"></p>
            </div>
            <div class="rounded-xl bg-slate-50 p-3 border border-slate-100">
                <p class="text-xs text-slate-500 font-medium">Next Due</p>
                <p class="text-lg font-bold" x-text="paymentInfo?.next_due_month ? formatDate(paymentInfo.next_due_month) : '-'"
                   :class="paymentInfo?.next_due_month ? (isOverdue(paymentInfo.next_due_month) ? 'text-red-600' : 'text-emerald-600') : ''"></p>
            </div>
            <div class="rounded-xl bg-slate-50 p-3 border border-slate-100">
                <p class="text-xs text-slate-500 font-medium">Total Paid</p>
                <p class="text-lg font-bold text-emerald-700" x-text="'Rs. ' + parseFloat(paymentInfo?.total_paid || 0).toFixed(2)"></p>
            </div>
        </div>

        <!-- Payment form -->
        <div class="max-w-lg space-y-4">
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Amount Received (Rs)</label>
                <input type="number" x-model="paymentAmount" @input.debounce.500ms="calculate()" @keydown.enter.prevent="calculate()"
                    min="0" step="0.01" placeholder="e.g. 5000"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-800 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/10">
            </div>

            <!-- Calculation preview -->
            <div x-show="calculation" x-cloak class="rounded-xl bg-emerald-50 border border-emerald-200 p-4 space-y-2">
                <p class="text-sm font-semibold text-emerald-800">Payment Breakdown</p>
                <div class="text-sm text-emerald-700 space-y-1">
                    <p><span class="font-medium">From:</span> <span x-text="formatDate(calculation?.from)"></span></p>
                    <p><span class="font-medium">To:</span> <span x-text="formatDate(calculation?.to)"></span></p>
                    <p><span class="font-medium">Months Covered:</span> <span x-text="calculation?.months"></span></p>
                    <p x-show="calculation?.extra > 0">
                        <span class="font-medium">Extra Amount:</span>
                        <span class="text-amber-600 font-semibold" x-text="'Rs. ' + parseFloat(calculation?.extra || 0).toFixed(2) + ' (advance/due)'"></span>
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="button" @click="submitPayment()" :disabled="!calculation || !paymentAmount"
                    class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-6 py-2.5 text-sm font-semibold text-white shadow-md shadow-emerald-600/20 transition hover:bg-emerald-700 disabled:opacity-40 disabled:cursor-not-allowed">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                    Record Payment
                </button>
                <button type="button" @click="resetForm()" class="text-sm font-semibold text-slate-500 hover:text-slate-700 transition">
                    Clear
                </button>
            </div>
        </div>
    </div>

    <!-- Payment History -->
    <div x-show="selectedUser" x-cloak class="rounded-2xl border border-slate-200/80 bg-white shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100">
            <h3 class="text-base font-bold text-slate-900">Payment History</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-slate-50/80 border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-3 text-xs font-bold uppercase tracking-wider text-slate-500">Date</th>
                        <th class="px-6 py-3 text-xs font-bold uppercase tracking-wider text-slate-500">Amount</th>
                        <th class="px-6 py-3 text-xs font-bold uppercase tracking-wider text-slate-500">From</th>
                        <th class="px-6 py-3 text-xs font-bold uppercase tracking-wider text-slate-500">To</th>
                        <th class="px-6 py-3 text-xs font-bold uppercase tracking-wider text-slate-500">Months</th>
                        <th class="px-6 py-3 text-xs font-bold uppercase tracking-wider text-slate-500">Extra</th>
                        <th class="px-6 py-3 text-xs font-bold uppercase tracking-wider text-slate-500">Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <template x-for="pmt in paymentHistory" :key="pmt.id">
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="px-6 py-3 text-sm text-slate-600" x-text="formatDate(pmt.created_at)"></td>
                            <td class="px-6 py-3 text-sm font-semibold text-slate-800" x-text="'Rs. ' + parseFloat(pmt.amount).toFixed(2)"></td>
                            <td class="px-6 py-3 text-sm text-slate-600" x-text="formatDate(pmt.from_month)"></td>
                            <td class="px-6 py-3 text-sm text-slate-600" x-text="formatDate(pmt.to_month)"></td>
                            <td class="px-6 py-3 text-sm text-slate-600" x-text="pmt.months_covered"></td>
                            <td class="px-6 py-3 text-sm" x-text="parseFloat(pmt.extra_amount) > 0 ? 'Rs. ' + parseFloat(pmt.extra_amount).toFixed(2) : '-'"
                                :class="parseFloat(pmt.extra_amount) > 0 ? 'text-amber-600 font-semibold' : 'text-slate-400'"></td>
                            <td class="px-6 py-3 text-sm text-slate-500" x-text="pmt.notes || '-'"></td>
                        </tr>
                    </template>
                    <tr x-show="paymentHistory.length === 0">
                        <td colspan="7" class="px-6 py-10 text-center text-sm text-slate-400">No payment history yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
const monthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];

function formatDate(dateStr) {
    if (!dateStr) return '-';
    const d = new Date(dateStr + 'T00:00:00');
    if (isNaN(d.getTime())) return dateStr;
    const day = String(d.getDate()).padStart(2, '0');
    const month = monthNames[d.getMonth()];
    const year = d.getFullYear();
    return day + '-' + month + '-' + year;
}

function isOverdue(dateStr) {
    if (!dateStr) return false;
    const d = new Date(dateStr + 'T00:00:00');
    if (isNaN(d.getTime())) return false;
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    return d < today;
}

function paymentManager() {
    return {
        searchQuery: '',
        searched: false,
        searchResults: [],
        selectedUser: null,
        paymentInfo: null,
        paymentAmount: '',
        calculation: null,
        paymentHistory: [],

        searchUsers() {
            if (!this.searchQuery.trim()) return;
            this.searched = true;
            fetch(BASE_URL + '/admin/payments/search-user', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ query: this.searchQuery, _csrf: '<?= e($_SESSION['csrf_token'] ?? '') ?>' }),
            })
            .then(r => r.json())
            .then(r => {
                if (r.success) this.searchResults = r.users || [];
            })
            .catch(() => {});
        },

        selectUser(user) {
            this.selectedUser = user;
            this.searchResults = [];
            this.searched = false;
            this.paymentAmount = '';
            this.paymentNotes = '';
            this.calculation = null;
            this.loadPaymentInfo();
            this.loadPaymentHistory();
        },

        loadPaymentInfo() {
            if (!this.selectedUser) return;
            fetch(BASE_URL + '/admin/payments/user-info', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: this.selectedUser.id, _csrf: '<?= e($_SESSION['csrf_token'] ?? '') ?>' }),
            })
            .then(r => r.json())
            .then(r => {
                if (r.success) {
                    this.paymentInfo = r.data;
                    this.paymentHistory = r.history || [];
                }
            })
            .catch(() => {});
        },

        loadPaymentHistory() {
            if (!this.selectedUser) return;
            // Payment history comes from the same user-info endpoint now
            this.loadPaymentInfo();
        },

        calculate() {
            if (!this.selectedUser || !this.paymentAmount || parseFloat(this.paymentAmount) <= 0) {
                this.calculation = null;
                return;
            }
            fetch(BASE_URL + '/admin/payments/calculate', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    user_id: this.selectedUser.id,
                    amount: parseFloat(this.paymentAmount),
                    _csrf: '<?= e($_SESSION['csrf_token'] ?? '') ?>',
                }),
            })
            .then(r => r.json())
            .then(r => { if (r.success) this.calculation = r.calculation; })
            .catch(() => {});
        },

        submitPayment() {
            if (!this.selectedUser || !this.calculation) return;
            const amount = parseFloat(this.paymentAmount);
            if (!amount || amount <= 0) { showToast('Enter a valid amount.', 'warning'); return; }

            fetch(BASE_URL + '/admin/payments/create', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    user_id: this.selectedUser.id,
                    amount: amount,
                    _csrf: '<?= e($_SESSION['csrf_token'] ?? '') ?>',
                }),
            })
            .then(r => r.json())
            .then(r => {
                if (r.success) {
                    showToast('Payment recorded successfully!');
                    if (r.sms_sent) {
                        setTimeout(() => showToast('SMS sent successfully!'), 500);
                    }
                    window.location.reload();
                } else {
                    showToast(r.error || 'Failed to record payment.', 'error');
                }
            })
            .catch(() => showToast('Network error.', 'error'));
        },

        resetForm() {
            this.selectedUser = null;
            this.searchResults = [];
            this.searched = false;
            this.searchQuery = '';
            this.paymentAmount = '';
            this.paymentNotes = '';
            this.calculation = null;
            this.paymentInfo = null;
            this.paymentHistory = [];
        }
    };
}
</script>
