<?php
// Pagination controls for the Kairo list footer. Receives the page state from
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
$pageStatusFormat = defined('_AM_FORMULIZE_LOE_PAGE_X_OF_Y') ? _AM_FORMULIZE_LOE_PAGE_X_OF_Y : 'Page %s of %s';
$statusFirstPlaceholder = strpos($pageStatusFormat, '%s');
$statusLabel = $statusFirstPlaceholder !== false ? substr($pageStatusFormat, 0, $statusFirstPlaceholder) : '';
$statusNumbersFormat = $statusFirstPlaceholder !== false ? substr($pageStatusFormat, $statusFirstPlaceholder) : $pageStatusFormat;
$statusNumbers = sprintf($statusNumbersFormat, $displayCurrentPage, $displayTotalPages);
print "<span class='fz-pagination__status'><span class='fz-pagination__status-label'>$statusLabel</span>$statusNumbers</span>";

if($hasNext) {
    print "<button type='button' class='fz-btn fz-btn--ghost fz-btn--icon fz-btn--sm' aria-label='"._AM_FORMULIZE_LOE_NEXT."' title='"._AM_FORMULIZE_LOE_NEXT."' onclick=\"$jsFunction('$nextStart');return false;\">$chevRight</button>";
} else {
    print "<button type='button' class='fz-btn fz-btn--ghost fz-btn--icon fz-btn--sm' disabled aria-disabled='true'>$chevRight</button>";
}

print "</div>"; // .fz-pagination__nav
print "</div>"; // .fz-pagination
