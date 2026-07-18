<?php
/**
 * @var array $locations  List of locations
 * @var array $wards      List of wards
 */

use Components\Base\Badge;

$pageTitle = 'Bulk Import';

$importTypes = [
    'locations' => [
        'label'       => 'Locations',
        'icon'        => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>',
        'description' => 'Bulk import locations (name only)',
        'columns'     => ['name'],
        'sample'      => ['Main Masjid'],
    ],
    'wards' => [
        'label'       => 'Wards',
        'icon'        => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>',
        'description' => 'Bulk import wards (ward number, status)',
        'columns'     => ['ward_number', 'is_active'],
        'sample'      => ['1', '1'],
    ],
    'members' => [
        'label'       => 'Members',
        'icon'        => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>',
        'description' => 'Bulk import members with location & ward assignment',
        'columns'     => ['name', 'email', 'phone', 'card_number', 'road_number', 'street', 'location_name', 'ward_number', 'monthly_amount'],
        'sample'      => ['Mohamed Ali', 'ali@example.com', '0771234567', '101', '12A', 'Main Street', 'Main Masjid', '1', '500.00'],
    ],
];

$activeType = trim((string) ($_GET['type'] ?? 'members'));
if (!isset($importTypes[$activeType])) {
    $activeType = 'members';
}
?>

<div class="space-y-6 max-w-5xl mx-auto py-2">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white/60 backdrop-blur-md p-6 rounded-2xl border border-slate-200/80 shadow-sm">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900"><?= e($pageTitle) ?></h1>
            <p class="mt-1 text-sm text-slate-500 font-medium">Import locations, wards, and members from CSV files in bulk.</p>
        </div>
    </div>

    <!-- Import Type Tabs -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="flex border-b border-slate-200 bg-slate-50/50" role="tablist">
            <?php foreach ($importTypes as $key => $type):
                $active = $key === $activeType;
                $tabClass = $active
                    ? 'border-b-2 border-primary-600 text-primary-700 bg-white font-semibold'
                    : 'text-slate-500 hover:text-slate-700 hover:bg-white/60';
            ?>
                <a href="<?= BASE_URL ?>/admin/bulk-import?type=<?= $key ?>"
                   class="flex items-center gap-2 px-5 py-3.5 text-sm font-medium transition-all <?= $tabClass ?>">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <?= $type['icon'] ?>
                    </svg>
                    <span><?= e($type['label']) ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="p-6">
            <!-- Active Type Info -->
            <?php $current = $importTypes[$activeType]; ?>
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-slate-800">Import <?= e($current['label']) ?></h2>
                <p class="mt-1 text-sm text-slate-500"><?= e($current['description']) ?></p>
            </div>

            <!-- Step 1: Download Template -->
            <div class="rounded-xl bg-slate-50 border border-slate-200/80 p-5 mb-6">
                <div class="flex items-start gap-4">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary-100 text-primary-700">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="text-sm font-semibold text-slate-800">Step 1: Download Sample CSV Template</h3>
                        <p class="mt-1 text-xs text-slate-500">Download the template, fill in your data, and save as CSV.</p>
                    </div>
                    <a href="<?= BASE_URL ?>/admin/bulk-import/template?type=<?= $activeType ?>"
                       class="shrink-0 inline-flex items-center gap-2 rounded-xl bg-white border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 hover:border-slate-300">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Download CSV
                    </a>
                </div>
            </div>

            <!-- Step 2: Upload & Preview -->
            <div class="rounded-xl bg-slate-50 border border-slate-200/80 p-5 mb-6">
                <div class="flex items-start gap-4">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-100 text-blue-700">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="text-sm font-semibold text-slate-800">Step 2: Upload CSV File</h3>
                        <p class="mt-1 text-xs text-slate-500">Select a CSV file with headers matching the template columns.</p>
                        <div class="mt-3">
                            <input type="file" id="csvFile" accept=".csv,.txt"
                                   class="block w-full text-sm text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100 transition">
                        </div>
                        <div id="fileError" class="mt-2 text-xs font-medium text-red-600 hidden"></div>
                    </div>
                </div>
            </div>

            <!-- Step 3: Preview & Import -->
            <div id="previewSection" class="rounded-xl bg-white border border-slate-200/80 p-5 hidden">
                <div class="flex items-start gap-4 mb-4">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-green-100 text-green-700">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="text-sm font-semibold text-slate-800">Step 3: Preview & Confirm Import</h3>
                        <p class="mt-1 text-xs text-slate-500">
                            <span id="rowCount">0</span> rows detected. Review the data below before importing.
                        </p>
                    </div>
                    <button type="button" id="importBtn" disabled
                            class="shrink-0 inline-flex items-center gap-2 rounded-xl bg-primary-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-primary-600/20 transition-all hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed active:scale-[0.98]">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                        </svg>
                        <span>Import <span id="importCount">0</span> Records</span>
                    </button>
                </div>

                <!-- Preview Table -->
                <div class="overflow-x-auto rounded-xl border border-slate-200">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr id="previewHeader"></tr>
                        </thead>
                        <tbody id="previewBody" class="divide-y divide-slate-100 bg-white"></tbody>
                    </table>
                </div>
                <p id="previewMore" class="mt-2 text-xs text-slate-400 hidden">Showing first 20 rows...</p>
            </div>

            <!-- Result Message -->
            <div id="resultMsg" class="rounded-xl p-4 hidden"></div>
        </div>
    </div>

</div>

<script>
const IMPORT_TYPE = '<?= $activeType ?>';

document.getElementById('csvFile').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const errorEl = document.getElementById('fileError');
    const previewSection = document.getElementById('previewSection');
    errorEl.classList.add('hidden');
    previewSection.classList.add('hidden');

    if (!file) return;

    // Validate file type
    const ext = file.name.split('.').pop().toLowerCase();
    if (!['csv', 'txt'].includes(ext)) {
        showError('Please select a CSV file.');
        return;
    }

    const reader = new FileReader();
    reader.onload = function(evt) {
        const text = evt.target.result;
        parseCSV(text);
    };
    reader.onerror = function() {
        showError('Failed to read file.');
    };
    reader.readAsText(file);
});

function parseCSV(text) {
    // Split lines, handling possible line breaks in quoted fields
    const lines = [];
    let current = '';
    let inQuote = false;

    for (let i = 0; i < text.length; i++) {
        const ch = text[i];
        if (ch === '"') {
            inQuote = !inQuote;
        } else if (ch === '\n' && !inQuote) {
            if (current.trim()) lines.push(current);
            current = '';
        } else if (ch === '\r' && !inQuote) {
            // skip carriage return
        } else {
            current += ch;
        }
    }
    if (current.trim()) lines.push(current);

    if (lines.length < 2) {
        showError('CSV file must have a header row and at least one data row.');
        return;
    }

    // Parse header
    const headers = parseCSVLine(lines[0]);
    const requiredCols = getRequiredColumns(IMPORT_TYPE);

    // Validate required columns exist
    const missing = requiredCols.filter(c => !headers.some(h => h.trim().toLowerCase() === c.toLowerCase()));
    if (missing.length > 0) {
        showError('Missing required columns: ' + missing.join(', '));
        return;
    }

    // Parse data rows
    const rows = [];
    for (let i = 1; i < lines.length; i++) {
        const values = parseCSVLine(lines[i]);
        if (values.length === 0 || values.every(v => v.trim() === '')) continue;
        const row = {};
        headers.forEach((h, idx) => {
            row[h.trim().toLowerCase()] = (values[idx] || '').trim();
        });
        rows.push(row);
    }

    if (rows.length === 0) {
        showError('No data rows found in the CSV file.');
        return;
    }

    // Show preview
    showPreview(headers, rows);
}

function parseCSVLine(line) {
    const result = [];
    let current = '';
    let inQuote = false;

    for (let i = 0; i < line.length; i++) {
        const ch = line[i];
        if (ch === '"') {
            if (inQuote && i + 1 < line.length && line[i + 1] === '"') {
                current += '"';
                i++;
            } else {
                inQuote = !inQuote;
            }
        } else if (ch === ',' && !inQuote) {
            result.push(current);
            current = '';
        } else {
            current += ch;
        }
    }
    result.push(current);
    return result;
}

function getRequiredColumns(type) {
    switch (type) {
        case 'locations': return ['name'];
        case 'wards': return ['ward_number'];
        case 'members': return ['name', 'phone'];
        default: return [];
    }
}

function showError(msg) {
    const errorEl = document.getElementById('fileError');
    errorEl.textContent = msg;
    errorEl.classList.remove('hidden');
    document.getElementById('previewSection').classList.add('hidden');
}

function showPreview(headers, rows) {
    const previewSection = document.getElementById('previewSection');
    previewSection.classList.remove('hidden');

    document.getElementById('rowCount').textContent = rows.length;
    document.getElementById('importCount').textContent = rows.length;

    const importBtn = document.getElementById('importBtn');
    importBtn.disabled = false;

    // Render header
    const headerRow = document.getElementById('previewHeader');
    headerRow.innerHTML = headers.map(h =>
        '<th class="px-4 py-2.5 text-left text-xs font-semibold text-slate-600 uppercase whitespace-nowrap">' + escapeHtml(h.trim()) + '</th>'
    ).join('');

    // Render body (max 20 rows)
    const maxPreview = Math.min(rows.length, 20);
    const body = document.getElementById('previewBody');
    body.innerHTML = '';
    for (let i = 0; i < maxPreview; i++) {
        const row = rows[i];
        const tr = document.createElement('tr');
        tr.className = i % 2 === 0 ? 'bg-white' : 'bg-slate-50/50';
        tr.innerHTML = headers.map(h => {
            const val = escapeHtml(row[h.trim().toLowerCase()] || '');
            return '<td class="px-4 py-2 text-xs text-slate-700 whitespace-nowrap max-w-[200px] truncate" title="' + val + '">' + val + '</td>';
        }).join('');
        body.appendChild(tr);
    }

    document.getElementById('previewMore').classList.toggle('hidden', rows.length <= 20);

    // Store rows for import
    importBtn.dataset.rows = JSON.stringify(rows);
}

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

document.getElementById('importBtn').addEventListener('click', function() {
    const btn = this;
    const rows = JSON.parse(btn.dataset.rows || '[]');
    if (rows.length === 0) return;

    btn.disabled = true;
    btn.innerHTML = '<svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> Importing...';

    const resultMsg = document.getElementById('resultMsg');
    resultMsg.className = 'rounded-xl p-4 hidden';

    fetch(BASE_URL + '/admin/bulk-import/process', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({
            import_type: IMPORT_TYPE,
            rows: rows,
            _csrf: '<?= e($_SESSION['csrf_token'] ?? '') ?>',
        }),
    })
    .then(r => r.json())
    .then(r => {
        resultMsg.classList.remove('hidden');
        if (r.success) {
            resultMsg.className = 'rounded-xl p-4 bg-green-50 border border-green-200 text-green-800 text-sm font-medium';
            resultMsg.innerHTML = '<div class="flex items-center gap-2"><svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> ' + escapeHtml(r.message || 'Import completed!') + '</div>';
            // Reset file input and preview
            document.getElementById('csvFile').value = '';
            document.getElementById('previewSection').classList.add('hidden');
        } else {
            resultMsg.className = 'rounded-xl p-4 bg-red-50 border border-red-200 text-red-800 text-sm font-medium';
            resultMsg.innerHTML = '<div class="flex items-center gap-2"><svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> ' + escapeHtml(r.error || 'Import failed.') + '</div>';
            btn.disabled = false;
            btn.innerHTML = 'Import ' + rows.length + ' Records';
        }
    })
    .catch(() => {
        resultMsg.classList.remove('hidden');
        resultMsg.className = 'rounded-xl p-4 bg-red-50 border border-red-200 text-red-800 text-sm font-medium';
        resultMsg.innerHTML = '<div class="flex items-center gap-2"><svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> Network error. Please try again.</div>';
        btn.disabled = false;
        btn.innerHTML = 'Import ' + rows.length + ' Records';
    });
});
</script>
