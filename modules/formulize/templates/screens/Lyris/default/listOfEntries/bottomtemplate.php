<?php

print "
  <div class='fz-list__footer'>
    <div class='fz-list__footer-start'>$saveButton $numberOfEntries $toggleRepeatData</div>
    <div class='fz-list__footer-end'>$pageNavControls</div>
  </div>

</div><!-- /.fz-list-screen -->
";

if ($messageText) {
    print "<script>alert(" . json_encode($messageText) . ");</script>";
}

?>

<script>
// Generic panel system: buttons with [data-fz-panel="id"] toggle panels by ID.
// All toggleable panels must carry the fz-panel class.
document.addEventListener('click', function (e) {
    var trigger = e.target.closest('[data-fz-panel]');
    var inPanel = e.target.closest('.fz-panel');
    if (trigger) {
        var panelId = trigger.getAttribute('data-fz-panel');
        var panel   = document.getElementById(panelId);
        if (!panel) return;
        var opening = !panel.classList.contains('open');
        document.querySelectorAll('.fz-panel.open').forEach(function (p) { p.classList.remove('open'); });
        if (opening) { panel.classList.add('open'); }
    } else if (!inPanel) {
        document.querySelectorAll('.fz-panel.open').forEach(function (p) { p.classList.remove('open'); });
    }
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
    var panel = document.getElementById('fz-view-panel');
    if (panel) { panel.classList.remove('open'); }
    showLoading();
}

(function () {
    function updateSelectionBar() {
        var checked = document.querySelectorAll('.formulize_selection_checkbox:checked');
        var bar     = document.getElementById('fz-selection-bar');
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
    var FILTERS_KEY = 'fz-filters-shown';

    function getScreenId() {
        var screenEl = document.querySelector('.fz-list-screen');
        var sid = screenEl ? screenEl.getAttribute('data-fz-sid') : '';
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
        var btn  = document.getElementById('fz-filter-toggle');
        var rows = document.querySelectorAll('.fz-search-row');
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
    });
}());
</script>
