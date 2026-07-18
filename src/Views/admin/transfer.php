<div class="mx-auto max-w-2xl space-y-6 py-2">
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-primary-700 via-primary-800 to-primary-950 p-6 shadow-xl sm:p-8">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(255,255,255,0.08),transparent_60%)]"></div>
        <div class="relative">
            <h1 class="text-2xl font-bold tracking-tight text-white">Transfer Admin Ownership</h1>
            <p class="mt-1 text-sm font-medium text-white/70">Securely transfer admin account to another person.</p>
        </div>
    </div>

    <!-- Step Progress -->
    <div class="flex items-center justify-center gap-2 text-sm font-medium" x-data="{}">
        <template x-for="(step, i) in ['Verify Old Admin', 'New Admin Details', 'Confirm']" :key="i">
            <div class="flex items-center gap-2">
                <span :class="'flex h-8 w-8 items-center justify-center rounded-full text-xs font-bold ' + (transferStep >= i ? 'bg-primary-600 text-white' : 'bg-slate-200 text-slate-400')" x-text="i + 1"></span>
                <span :class="'text-xs ' + (transferStep === i ? 'text-primary-700 font-semibold' : 'text-slate-400')" x-text="step"></span>
                <template x-if="i < 2">
                    <svg class="h-4 w-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </template>
            </div>
        </template>
    </div>

    <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm"
         x-data="transferManager()"
         x-init="init()">
        
        <!-- Step 1: Old Admin Verification -->
        <template x-if="transferStep === 0">
            <div class="space-y-5">
                <div class="rounded-xl bg-amber-50 border border-amber-200 p-4 text-sm text-amber-800">
                    <p class="font-semibold">Enter the current admin's phone number to verify.</p>
                    <p class="mt-1 text-xs text-amber-600">An OTP will be sent to their registered phone.</p>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Current Admin Phone *</label>
                    <input type="tel" x-model="oldPhone" placeholder="e.g. 0770000001"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                    <p x-show="error" x-text="error" class="mt-1 text-xs text-red-600"></p>
                </div>
                <div x-show="oldOtpSent" class="space-y-3">
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Enter OTP sent to old admin</label>
                    <input type="text" x-model="oldOtp" placeholder="Enter OTP" maxlength="6"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                <div class="flex items-center gap-3">
                    <button type="button" @click="sendOldOtp()" x-show="!oldOtpSent"
                        class="inline-flex items-center gap-2 rounded-xl bg-primary-600 px-6 py-2.5 text-sm font-semibold text-white shadow-md hover:bg-primary-700">
                        Send OTP
                    </button>
                    <button type="button" @click="verifyOldOtp()" x-show="oldOtpSent"
                        class="inline-flex items-center gap-2 rounded-xl bg-primary-600 px-6 py-2.5 text-sm font-semibold text-white shadow-md hover:bg-primary-700">
                        Verify OTP
                    </button>
                </div>
            </div>
        </template>

        <!-- Step 2: New Admin Details -->
        <template x-if="transferStep === 1">
            <div class="space-y-5">
                <div class="rounded-xl bg-blue-50 border border-blue-200 p-4 text-sm text-blue-800">
                    <p class="font-semibold">Enter the new admin's details.</p>
                    <p class="mt-1 text-xs text-blue-600">An OTP will be sent to verify the new admin's phone.</p>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Full Name *</label>
                    <input type="text" x-model="newName" placeholder="e.g. John Doe"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Phone *</label>
                    <input type="tel" x-model="newPhone" placeholder="e.g. 0770000002"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Email</label>
                    <input type="email" x-model="newEmail" placeholder="e.g. newadmin@example.com"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                <p x-show="error" x-text="error" class="text-xs text-red-600"></p>
                <div x-show="newOtpSent" class="space-y-3">
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Enter OTP sent to new admin</label>
                    <input type="text" x-model="newOtp" placeholder="Enter OTP" maxlength="6"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                <div class="flex items-center gap-3">
                    <button type="button" @click="sendNewOtp()" x-show="!newOtpSent"
                        class="inline-flex items-center gap-2 rounded-xl bg-primary-600 px-6 py-2.5 text-sm font-semibold text-white shadow-md hover:bg-primary-700">
                        Send OTP to New Admin
                    </button>
                    <button type="button" @click="verifyNewOtp()" x-show="newOtpSent"
                        class="inline-flex items-center gap-2 rounded-xl bg-green-600 px-6 py-2.5 text-sm font-semibold text-white shadow-md hover:bg-green-700">
                        Verify &amp; Complete Transfer
                    </button>
                    <button type="button" @click="transferStep = 0; error = ''"
                        class="text-sm text-slate-500 hover:text-slate-700">Back</button>
                </div>
            </div>
        </template>

        <!-- Step 3: Success -->
        <template x-if="transferStep === 2">
            <div class="py-8 text-center space-y-4">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-green-100">
                    <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <h2 class="text-xl font-bold text-slate-900">Transfer Complete!</h2>
                <p class="text-sm text-slate-500">Admin ownership has been transferred successfully.</p>
                <p class="text-xs text-slate-400">SMS notifications sent to super admin, old admin, and new admin.</p>
            </div>
        </template>
    </div>
</div>

<script>
function transferManager() {
    return {
        transferStep: 0,
        oldPhone: '',
        oldOtp: '',
        oldOtpSent: false,
        newName: '',
        newPhone: '',
        newEmail: '',
        newOtp: '',
        newOtpSent: false,
        error: '',

        init() {
            this.transferStep = 0;
        },

        sendOldOtp() {
            this.error = '';
            if (!this.oldPhone.trim()) { this.error = 'Enter old admin phone number.'; return; }
            fetch(BASE_URL + '/admin/transfer/send-old-otp', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ phone: this.oldPhone, _csrf: '<?= e($_SESSION['csrf_token'] ?? '') ?>' }),
            })
            .then(r => r.json())
            .then(r => {
                if (r.success) { this.oldOtpSent = true; showToast(r.message); }
                else { this.error = r.error || 'Failed to send OTP.'; }
            })
            .catch(() => { this.error = 'Network error.'; });
        },

        verifyOldOtp() {
            this.error = '';
            if (!this.oldOtp.trim()) { this.error = 'Enter OTP.'; return; }
            fetch(BASE_URL + '/admin/transfer/verify-old-otp', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ otp: this.oldOtp, _csrf: '<?= e($_SESSION['csrf_token'] ?? '') ?>' }),
            })
            .then(r => r.json())
            .then(r => {
                if (r.success) { this.transferStep = 1; showToast(r.message); }
                else { this.error = r.error || 'Invalid OTP.'; }
            })
            .catch(() => { this.error = 'Network error.'; });
        },

        sendNewOtp() {
            this.error = '';
            if (!this.newName.trim()) { this.error = 'Enter new admin name.'; return; }
            if (!this.newPhone.trim()) { this.error = 'Enter new admin phone.'; return; }
            fetch(BASE_URL + '/admin/transfer/send-new-otp', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name: this.newName, phone: this.newPhone, email: this.newEmail, _csrf: '<?= e($_SESSION['csrf_token'] ?? '') ?>' }),
            })
            .then(r => r.json())
            .then(r => {
                if (r.success) { this.newOtpSent = true; showToast(r.message); }
                else { this.error = r.error || 'Failed to send OTP.'; }
            })
            .catch(() => { this.error = 'Network error.'; });
        },

        verifyNewOtp() {
            this.error = '';
            if (!this.newOtp.trim()) { this.error = 'Enter OTP.'; return; }
            fetch(BASE_URL + '/admin/transfer/verify-new-otp', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ otp: this.newOtp, _csrf: '<?= e($_SESSION['csrf_token'] ?? '') ?>' }),
            })
            .then(r => r.json())
            .then(r => {
                if (r.success) { this.transferStep = 2; showToast(r.message); }
                else { this.error = r.error || 'Invalid OTP.'; }
            })
            .catch(() => { this.error = 'Network error.'; });
        },
    };
}
</script>
