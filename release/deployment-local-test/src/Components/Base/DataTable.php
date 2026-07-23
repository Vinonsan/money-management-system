<?php
namespace Components\Base;

/**
 * Modern Reusable DataTable with pagination, sorting, and search.
 */
final class DataTable
{
    public static function render(array $config): string
    {
        $columns = $config['columns'] ?? [];
        $rows = $config['rows'] ?? [];
        $total = (int) ($config['total'] ?? 0);
        $page = max(1, (int) ($config['page'] ?? 1));
        $perPage = (int) ($config['perPage'] ?? 10);
        $search = htmlspecialchars($config['search'] ?? '', ENT_QUOTES, 'UTF-8');
        $sortField = $config['sortField'] ?? '';
        $sortDir = $config['sortDir'] ?? 'asc';
        $baseUrl = rtrim($config['baseUrl'] ?? '', '/');
        $emptyMessage = htmlspecialchars($config['emptyMessage'] ?? 'No records found.', ENT_QUOTES, 'UTF-8');
        $perPageOptions = $config['perPageOptions'] ?? [10, 25, 50, 100];
        $totalPages = max(1, (int) ceil($total / $perPage));

        // SVG Icons
        $sortIconAsc = '<svg class="inline h-3.5 w-3.5 text-primary-600 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"/></svg>';
        $sortIconDesc = '<svg class="inline h-3.5 w-3.5 text-primary-600 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>';
        $sortIconNone = '<svg class="inline h-3.5 w-3.5 text-slate-300 opacity-0 group-hover:opacity-100 transition-opacity ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8l5-5 5 5M7 16l5 5 5-5"/></svg>';

        // ─── Search Bar ───────────────────────────────────────────
        $searchHtml = '';
        if (!empty($config['searchable'])) {
            $clearLink = $search
                ? '<a href="' . $baseUrl . '" class="text-xs font-semibold text-slate-400 hover:text-rose-500 transition-colors">Clear</a>'
                : '';
            $searchHtml = <<<HTML
            <div class="p-5 border-b border-slate-100 flex flex-wrap items-center justify-between gap-4 bg-white/50 backdrop-blur-sm">
                <form method="get" class="flex items-center gap-3 w-full sm:w-auto">
                    <div class="relative flex-1 sm:w-80">
                        <svg class="absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input type="text" name="search" value="{$search}"
                            placeholder="Search records..."
                            class="w-full rounded-xl border border-slate-200 bg-slate-50/50 py-2 pl-10 pr-4 text-sm text-slate-700 placeholder-slate-400 transition-all focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-primary-500/10">
                    </div>
                    <button type="submit"
                        class="rounded-xl bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm shadow-primary-600/20 hover:bg-primary-700 active:scale-[0.98] transition-all">
                        Search
                    </button>
                    {$clearLink}
                </form>
            </div>
            HTML;
        }

        // ─── Table Headers ──────────────────────────────────────────────
        $headers = '';
        foreach ($columns as $col) {
            $label = htmlspecialchars($col['label'] ?? '', ENT_QUOTES, 'UTF-8');
            $field = $col['field'] ?? '';
            $sortable = !empty($col['sortable']);
            $width = isset($col['width']) ? ' style="width:' . htmlspecialchars($col['width'], ENT_QUOTES) . '"' : '';
            $align = $col['align'] ?? 'left';
            $textAlign = $align === 'right' ? 'text-right' : ($align === 'center' ? 'text-center' : 'text-left');

            if ($sortable && $field) {
                $nextDir = ($sortField === $field && $sortDir === 'asc') ? 'desc' : 'asc';
                $icon = $sortField === $field
                    ? ($sortDir === 'asc' ? $sortIconAsc : $sortIconDesc)
                    : $sortIconNone;
                $searchParam = $search ? '&search=' . urlencode($config['search']) : '';
                $headers .= <<<HTML
                <th class="px-6 py-4 {$textAlign} text-xs font-bold uppercase tracking-wider text-slate-500 {$width}">
                    <a href="{$baseUrl}?sort={$field}&dir={$nextDir}{$searchParam}"
                       class="group inline-flex items-center hover:text-primary-600 transition-colors">
                        {$label} {$icon}
                    </a>
                </th>
                HTML;
            } else {
                $headers .= <<<HTML
                <th class="px-6 py-4 {$textAlign} text-xs font-bold uppercase tracking-wider text-slate-500 {$width}">
                    {$label}
                </th>
                HTML;
            }
        }

        // ─── Table Rows ─────────────────────────────────────────────────
        $bodyRows = '';
        if (empty($rows)) {
            $colspan = count($columns);
            $bodyRows = <<<HTML
            <tr>
                <td colspan="{$colspan}" class="px-6 py-16 text-center">
                    <div class="flex flex-col items-center justify-center">
                        <div class="h-12 w-12 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 mb-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                        </div>
                        <p class="text-sm font-medium text-slate-500">{$emptyMessage}</p>
                    </div>
                </td>
            </tr>
            HTML;
        } else {
            foreach ($rows as $row) {
                $cells = '';
                foreach ($columns as $col) {
                    $field = $col['field'] ?? null;
                    $format = $col['format'] ?? null;
                    $align = $col['align'] ?? 'left';
                    $textAlign = $align === 'right' ? 'text-right' : ($align === 'center' ? 'text-center' : 'text-left');
                    $width = isset($col['width']) ? ' style="width:' . htmlspecialchars($col['width'], ENT_QUOTES) . '"' : '';

                    if ($field === null && $format !== null) {
                        $value = is_callable($format) ? $format($row) : ($format ?? '');
                    } elseif ($format !== null && is_callable($format)) {
                        $value = $format($row[$field] ?? '');
                    } else {
                        $value = htmlspecialchars((string)($row[$field] ?? ''), ENT_QUOTES, 'UTF-8');
                    }
                    $cells .= <<<HTML
                    <td class="px-6 py-4 {$textAlign} text-sm text-slate-600 font-medium whitespace-nowrap {$width}">{$value}</td>
                    HTML;
                }

                $bodyRows .= <<<HTML
                <tr class="hover:bg-slate-50/80 transition-colors border-b border-slate-100 last:border-0">{$cells}</tr>
                HTML;
            }
        }

        // ─── Pagination ──────────────────────────────────────────────────
        $pageUrl = $baseUrl . '?page=%d' . ($search ? '&search=' . urlencode($config['search']) : '') . ($sortField ? '&sort=' . $sortField . '&dir=' . $sortDir : '');
        $prevDisabled = $page <= 1;
        $nextDisabled = $page >= $totalPages;

        $prevClass = $prevDisabled ? 'opacity-40 cursor-not-allowed text-slate-400' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900';
        $nextClass = $nextDisabled ? 'opacity-40 cursor-not-allowed text-slate-400' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900';

        $pagesHtml = '';
        $startPage = max(1, $page - 1);
        $endPage = min($totalPages, $page + 1);

        for ($i = $startPage; $i <= $endPage; $i++) {
            $isActive = $i === $page;
            $activeClass = $isActive
                ? 'bg-primary-600 text-white font-semibold shadow-sm shadow-primary-600/30'
                : 'text-slate-600 hover:bg-slate-100';
            $pagesHtml .= '<a href="' . sprintf($pageUrl, $i) . '" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-xs transition-all ' . $activeClass . '">' . $i . '</a>';
        }

        $from = ($page - 1) * $perPage + 1;
        $to = min($page * $perPage, $total);

        $perPageOptionsHtml = '';
        foreach ($perPageOptions as $opt) {
            $selected = $opt === $perPage ? ' selected' : '';
            $perPageOptionsHtml .= '<option value="' . $opt . '"' . $selected . '>' . $opt . '</option>';
        }

        $searchQuery = $search ? '&search=' . urlencode($config['search']) : '';
        $sortQuery = $sortField ? '&sort=' . $sortField . '&dir=' . $sortDir : '';
        $prevHref = $prevDisabled ? '#' : sprintf($pageUrl, $page - 1);
        $nextHref = $nextDisabled ? '#' : sprintf($pageUrl, $page + 1);

        $pagination = <<<HTML
        <div class="flex flex-wrap items-center justify-between gap-4 px-6 py-4 border-t border-slate-100 bg-slate-50/30">
            <div class="flex items-center gap-3 text-xs text-slate-500 font-medium">
                <span class="hidden sm:inline">Rows per page</span>
                <select onchange="window.location.href='{$baseUrl}?per_page='+this.value+'{$searchQuery}{$sortQuery}'"
                    class="rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs text-slate-700 font-medium shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/10">
                    {$perPageOptionsHtml}
                </select>
                <span>Showing <strong class="text-slate-700 font-semibold">{$from}</strong> - <strong class="text-slate-700 font-semibold">{$to}</strong> of <strong class="text-slate-700 font-semibold">{$total}</strong></span>
            </div>
            
            <div class="flex items-center gap-1.5">
                <a href="{$prevHref}"
                   class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white shadow-sm transition-all {$prevClass}">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </a>
                {$pagesHtml}
                <a href="{$nextHref}"
                   class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white shadow-sm transition-all {$nextClass}">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>
        </div>
        HTML;

        // ─── Main Output Wrapper ───────────────────────────────────────
        return <<<HTML
        <div class="w-full rounded-2xl border border-slate-200/80 bg-white shadow-xl shadow-slate-100/50 overflow-hidden">
            {$searchHtml}
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-slate-50/80 border-b border-slate-100">
                        <tr>{$headers}</tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        {$bodyRows}
                    </tbody>
                </table>
            </div>
            {$pagination}
        </div>
        HTML;
    }
}