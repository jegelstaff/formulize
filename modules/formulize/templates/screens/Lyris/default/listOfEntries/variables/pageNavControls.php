<?php
// Pagination controls for the Lyris list footer. Receives the page state from
// formulize_LOEbuildPageNav: currentPage, totalPages, numberPerPage, totalEntries,
// pageStart, firstEntry, lastEntry, pageStarts (page=>record offset), jsFunction
// ('pageJump', called with a record-start offset), and entriesPerPageSelector (the
// core <select name="formulize_entriesPerPage">, reused as-is so its sync/reload
// behaviour is preserved — we only restyle it via CSS).

$prevStart = $pageStart - $numberPerPage;
$nextStart = $pageStart + $numberPerPage;
$hasPrev   = $currentPage > 1;
$hasNext   = $currentPage < $totalPages;

// an empty result set reports 0 pages; show it as a single page so the indicator
// reads "Page 1 of 1" rather than "Page 1 of 0".
$displayCurrentPage = max(1, $currentPage);
$displayTotalPages  = max(1, $totalPages);

$chevLeft  = "<svg width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' aria-hidden='true'><path d='m15 18-6-6 6-6'/></svg>";
$chevRight = "<svg width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' aria-hidden='true'><path d='m9 18 6-6-6-6'/></svg>";

print "<div class='fz-pagination'>";

// rows-per-page selector (core markup, restyled)
if($entriesPerPageSelector) {
    print "<span class='fz-pagination__perpage'>$entriesPerPageSelector</span>";
}

print "<div class='fz-pagination__nav'>";

if($hasPrev) {
    print "<button type='button' class='fz-btn fz-btn--ghost fz-btn--icon fz-btn--sm' aria-label='"._AM_FORMULIZE_LOE_PREVIOUS."' title='"._AM_FORMULIZE_LOE_PREVIOUS."' onclick=\"$jsFunction('$prevStart');return false;\">$chevLeft</button>";
} else {
    print "<button type='button' class='fz-btn fz-btn--ghost fz-btn--icon fz-btn--sm' disabled aria-disabled='true'>$chevLeft</button>";
}

// Split the leading label ("Page ") into its own span so it can be hidden on
// small screens, leaving just "X of Y". The label is whatever precedes the first
// placeholder in the language string.
// On mobile the label is hidden; a separate "pg" abbreviation span is shown
// instead so the number reads "pg 2 / 5" rather than a bare "2 / 5" that
// could be mistaken for the result count.
$pageStatusFormat = defined('_AM_FORMULIZE_LOE_PAGE_X_OF_Y') ? _AM_FORMULIZE_LOE_PAGE_X_OF_Y : 'Page %s of %s';
$statusFirstPlaceholder = strpos($pageStatusFormat, '%s');
$statusLabel = $statusFirstPlaceholder !== false ? substr($pageStatusFormat, 0, $statusFirstPlaceholder) : '';
$statusNumbersFormat = $statusFirstPlaceholder !== false ? substr($pageStatusFormat, $statusFirstPlaceholder) : $pageStatusFormat;
$statusNumbers = sprintf($statusNumbersFormat, $displayCurrentPage, $displayTotalPages);
print "<span class='fz-pagination__status'><span class='fz-pagination__status-label'>$statusLabel</span><span class='fz-pagination__status-pg'>pg </span>$statusNumbers</span>";

// Desktop-only numbered page links (Anari-style). Hidden on mobile via CSS
// (.fz-pagination__pages is display:none below 769px), where the compact
// "X of Y" status above is shown instead. Uses the pageStarts map
// (page=>record offset) the backend already provides — no backend changes.
if($totalPages > 1) {
    // Build a windowed sequence of page numbers: first, last, and current ±2,
    // with null markers standing in for gaps (rendered as an ellipsis). This
    // keeps the row short even when there are many pages.
    $window = 2;
    $pages = array();
    for($p = 1; $p <= $displayTotalPages; $p++) {
        if($p == 1 || $p == $displayTotalPages || abs($p - $displayCurrentPage) <= $window) {
            $pages[] = $p;
        }
    }
    // Insert null between non-consecutive kept pages to signal an ellipsis gap.
    $sequence = array();
    $prev = null;
    foreach($pages as $p) {
        if($prev !== null && $p - $prev > 1) {
            $sequence[] = null;
        }
        $sequence[] = $p;
        $prev = $p;
    }

    print "<div class='fz-pagination__pages'>";
    foreach($sequence as $p) {
        if($p === null) {
            print "<span class='fz-pagination__ellipsis' aria-hidden='true'>&hellip;</span>";
            continue;
        }
        if($p == $displayCurrentPage) {
            print "<span class='fz-btn fz-btn--sm fz-pagination__page fz-pagination__page--active' aria-current='page'>$p</span>";
        } else {
            $offset = isset($pageStarts[$p]) ? intval($pageStarts[$p]) : 0;
            print "<button type='button' class='fz-btn fz-btn--ghost fz-btn--sm fz-pagination__page' aria-label='"._AM_FORMULIZE_LOE_ONPAGE." $p' onclick=\"$jsFunction('$offset');return false;\">$p</button>";
        }
    }
    print "</div>"; // .fz-pagination__pages
}

if($hasNext) {
    print "<button type='button' class='fz-btn fz-btn--ghost fz-btn--icon fz-btn--sm' aria-label='"._AM_FORMULIZE_LOE_NEXT."' title='"._AM_FORMULIZE_LOE_NEXT."' onclick=\"$jsFunction('$nextStart');return false;\">$chevRight</button>";
} else {
    print "<button type='button' class='fz-btn fz-btn--ghost fz-btn--icon fz-btn--sm' disabled aria-disabled='true'>$chevRight</button>";
}

print "</div>"; // .fz-pagination__nav

print "</div>"; // .fz-pagination
