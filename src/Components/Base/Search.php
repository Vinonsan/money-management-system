<?php
namespace Components\Base;

/**
 * Live Search component with optional filter-by dropdown.
 *
 * Features:
 *   - Real-time search as you type (debounced)
 *   - Optional "filter by" select dropdown
 *   - Works with DataTable via URL query params
 *
 * Usage:
 *   echo Search::render([
 *       'value'   => $search,
 *       'filters' => [
 *           ''     => 'All Fields',
 *           'name' => 'Street Name',
 *           'city' => 'City',
 *       ],
 *       'selectedFilter' => $filterBy ?? '',
 *       'placeholder' => 'Search locations...',
 *       'baseUrl'  => '/admin/locations',
 *   ]);
 */
final class Search
{
    public static function render(array $opts = []): string
    {
        $value     = htmlspecialchars($opts['value'] ?? '', ENT_QUOTES, 'UTF-8');
        $filters   = $opts['filters'] ?? [];
        $selectedFilter = $opts['selectedFilter'] ?? '';
        $placeholder = htmlspecialchars($opts['placeholder'] ?? 'Search...', ENT_QUOTES, 'UTF-8');
        $baseUrl   = rtrim($opts['baseUrl'] ?? '', '/');

        $hasFilters = !empty($filters);

        // ─── Filter dropdown ────────────────────────────────────────────
        $filterHtml = '';
        if ($hasFilters) {
            $filterOptions = '';
            foreach ($filters as $key => $label) {
                $keyEnc = htmlspecialchars((string)$key, ENT_QUOTES, 'UTF-8');
                $labelEnc = htmlspecialchars((string)$label, ENT_QUOTES, 'UTF-8');
                $sel = (string)$key === $selectedFilter ? ' selected' : '';
                $filterOptions .= '<option value="' . $keyEnc . '"' . $sel . '>' . $labelEnc . '</option>';
            }

            $filterHtml = <<<HTML
            <div class="relative">
                <select id="search-filter"
                    class="h-full rounded-l-lg border border-r-0 border-slate-300 bg-slate-50 px-3 py-2.5 text-sm font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-emerald-600 appearance-none cursor-pointer"
                    style="min-width: 130px;">
                    {$filterOptions}
                </select>
                <div class="pointer-events-none absolute right-2 top-1/2 -translate-y-1/2 text-slate-400">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                    </svg>
                </div>
            </div>
            HTML;
        }

        $roundedLeft = $hasFilters ? '' : 'rounded-l-lg';
        $borderLeft = $hasFilters ? '' : 'border';

        $initValue = $value;
        $filterInit = $selectedFilter;

        $hasSearch = $value !== '';

        $roundedInput = $hasFilters ? '' : 'rounded-l-lg';
        $borderInput = $hasFilters ? '' : 'border';

        return <<<HTML
        <div class="search-component flex items-center justify-between gap-3" x-data="liveSearch('{$baseUrl}', '{$initValue}', '')">
            <div class="relative flex-1 max-w-md">
                <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text"
                    x-ref="searchInput"
                    x-model="query"
                    @input.debounce.300ms="search()"
                    @keydown.enter.prevent="search()"
                    placeholder="{$placeholder}"
                    class="w-full rounded-lg border border-slate-300 bg-white py-2.5 pl-10 pr-3 text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600 transition">
            </div>
            <button type="button" @click="resetSearch()"
                class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 hover:text-slate-800">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Reset
            </button>
        </div>

        <script>
        document.addEventListener('alpine:init', () => {
            if (window.__liveSearchRegistered) return;
            window.__liveSearchRegistered = true;

            Alpine.data('liveSearch', (baseUrl, initQuery, initFilter) => ({
                query: initQuery || '',
                baseUrl: baseUrl,
                init() {
                    this.query = initQuery || '';
                    if (initFilter) {
                        const sel = document.getElementById('search-filter');
                        if (sel) sel.value = initFilter;
                    }
                    // Sync input value on page load
                    if (this.\$refs.searchInput) {
                        this.\$refs.searchInput.value = this.query;
                    }
                },
                search() {
                    const filter = document.getElementById('search-filter');
                    const filterVal = filter ? filter.value : '';
                    let url = this.baseUrl + '?search=' + encodeURIComponent(this.query);
                    if (filterVal) url += '&filter=' + encodeURIComponent(filterVal);
                    window.location.href = url;
                },
                resetSearch() {
                    window.location.href = this.baseUrl;
                }
            }));
        });
        </script>
        HTML;
    }
}
