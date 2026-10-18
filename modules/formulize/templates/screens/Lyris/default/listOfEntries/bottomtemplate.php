<?php

print "
  <div class='lyris-list__footer'>
    <div class='lyris-list__footer-start'>$saveButton $numberOfEntries $toggleRepeatData</div>
    <div class='lyris-list__footer-end'>$pageNavControls</div>
  </div>

</div><!-- /.lyris-list-screen -->
";

if ($messageText) {
    print "<script>alert(" . json_encode($messageText) . ");</script>";
}

?>

<script>
// The view switcher and More actions are Formulize UI popovers with menus (fz-popover fz-menu): their buttons open
// and close them, and the browser closes them when something else is clicked or Esc is pressed. Using an item closes
// its menu too, which the browser leaves to the page.
function fzCloseLyrisPanels() {
    document.querySelectorAll('.lyris-list-screen .fz-menu:popover-open').forEach(function (menu) { menu.hidePopover(); });
}
document.addEventListener('click', function (e) {
    var item = e.target.closest('.lyris-list-screen .fz-menu :is(.fz-menu__item, input[type="button"], input[type="submit"])');
    if (item) { fzCloseLyrisPanels(); }
});

// Selects a view: updates the hidden currentview input and submits the form.
// isStandard: true for mine/group/all/scope views; false for saved/published views.
function fzSelectView(value, isStandard) {
    var form = window.document.controls;
    form.currentview.value = value;
    form.loadreport.value  = 1;
    if (isStandard && form.lockcontrols.value == 1) {
        form.resetview.value  = 1;
        form.curviewid.value  = '';
    }
    form.lockcontrols.value = 0;
    fzCloseLyrisPanels();
    showLoading();
}

(function () {
    function updateSelectionBar() {
        var checked = document.querySelectorAll('.formulize_selection_checkbox:checked');
        var bar     = document.getElementById('lyris-selection-bar');
        var countEl = document.querySelector('.js-selection-count');
        if (!bar) return;
        if (countEl) countEl.textContent = checked.length + ' selected';
        if (checked.length > 0) {
            bar.classList.add('is-active');
        } else {
            bar.classList.remove('is-active');
        }
        document.querySelectorAll('.formulize_selection_checkbox').forEach(function (cb) {
            var row = cb.closest('tr');
            if (row) row.setAttribute('aria-selected', cb.checked ? 'true' : 'false');
        });
    }

    // Persisted filter-row visibility, keyed by screen id, in a single
    // localStorage object: { "<sid>": true|false }. Absent/corrupt state
    // falls back to the rendered default (filters hidden).
    var FILTERS_KEY = 'lyris-filters-shown';

    function getScreenId() {
        var screenEl = document.querySelector('.lyris-list-screen');
        var sid = screenEl ? screenEl.getAttribute('data-lyris-sid') : '';
        return (sid && sid !== '0') ? sid : '';
    }

    function readFiltersState() {
        try {
            return JSON.parse(localStorage.getItem(FILTERS_KEY)) || {};
        } catch (e) {
            return {};
        }
    }

    function writeFilterState(sid, shown) {
        if (!sid) return;
        try {
            var state = readFiltersState();
            state[sid] = shown;
            localStorage.setItem(FILTERS_KEY, JSON.stringify(state));
        } catch (e) { /* ignore */ }
    }

    function setRows(rows, shown) {
        rows.forEach(function (row) {
            if (shown) { row.removeAttribute('hidden'); }
            else       { row.setAttribute('hidden', ''); }
        });
    }

    function initFilterToggle() {
        var btn  = document.getElementById('lyris-filter-toggle');
        var rows = document.querySelectorAll('.lyris-search-row');
        if (!btn) return;
        if (rows.length === 0) { btn.style.display = 'none'; return; }

        var sid = getScreenId();
        // Apply any remembered state for this screen; otherwise keep the default.
        if (sid) {
            var stored = readFiltersState()[sid];
            if (stored !== undefined) { setRows(rows, stored); }
        }

        btn.setAttribute('aria-pressed', String(!rows[0].hasAttribute('hidden')));
        btn.addEventListener('click', function () {
            var willShow = rows[0].hasAttribute('hidden');
            setRows(rows, willShow);
            btn.setAttribute('aria-pressed', String(willShow));
            writeFilterState(sid, willShow);
        });
    }

    // The sticky filter row sits just below the sticky column-header row. Headers can wrap onto
    // several lines (fixed pixel width columns), so track the header row's real height in
    // --lyris-thead-height rather than assuming its 36px single-line height.
    function initStickyFilterOffset() {
        var table = document.querySelector('.lyris-list-table');
        var headerRow = table ? table.querySelector('thead tr:first-child') : null;
        if (!headerRow || headerRow.classList.contains('lyris-search-row') || typeof ResizeObserver === 'undefined') return;
        new ResizeObserver(function () {
            table.style.setProperty('--lyris-thead-height', headerRow.getBoundingClientRect().height + 'px');
        }).observe(headerRow);
    }

    // "Hide repeating data" switch: the list template marks each cell whose contents
    // match the cell above with .same-contents-as-prior-cell; the switch blanks them all
    // by toggling one class on the table.
    function initRepeatDataToggle() {
        var toggle = document.getElementById('toggleRepeatData');
        var table  = document.querySelector('.lyris-list-table');
        if (!toggle || !table) return;
        toggle.addEventListener('change', function () {
            table.classList.toggle('lyris-list-table--hide-repeats', toggle.checked);
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        // Unbind Formulize's default checkbox→panel handler; selection bar handles it instead.
        if (typeof jQuery !== 'undefined') {
            jQuery('.formulize_selection_checkbox').off('click');
        }
        document.addEventListener('change', function (e) {
            if (e.target.classList.contains('formulize_selection_checkbox')) {
                updateSelectionBar();
            }
        });
        // Select all / Clear selection (un)check the boxes programmatically, which
        // fires no change events, so recompute the bar after those clicks run.
        document.addEventListener('click', function (e) {
            if (e.target.id === 'formulize_selectAllButton' || e.target.id === 'formulize_clearSelectButton') {
                setTimeout(updateSelectionBar, 0);
            }
        });
        initFilterToggle();
        initStickyFilterOffset();
        initRepeatDataToggle();
    });
}());
</script>
