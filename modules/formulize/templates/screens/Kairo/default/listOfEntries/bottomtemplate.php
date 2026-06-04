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

    function initFilterToggle() {
        var btn  = document.getElementById('fz-filter-toggle');
        var rows = document.querySelectorAll('.fz-search-row');
        if (!btn) return;
        if (rows.length === 0) { btn.style.display = 'none'; return; }
        btn.setAttribute('aria-pressed', String(!rows[0].hasAttribute('hidden')));
        btn.addEventListener('click', function () {
            var isHidden = rows[0].hasAttribute('hidden');
            rows.forEach(function (row) {
                if (isHidden) { row.removeAttribute('hidden'); }
                else          { row.setAttribute('hidden', ''); }
            });
            btn.setAttribute('aria-pressed', String(isHidden));
        });
    }

    // Read the list's form ids, emitted as data attributes on .fz-list-screen.
    function listFormIds() {
        var screenEl = document.querySelector('.fz-list-screen');
        return {
            fid:  screenEl ? screenEl.getAttribute('data-fz-fid')  : '',
            frid: screenEl ? screenEl.getAttribute('data-fz-frid') : ''
        };
    }

    // Parse goDetails('entry', 'screen') out of a loe-edit-entry onclick attribute.
    function parseGoDetails(link) {
        var result = { entryId: '', sid: '' };
        if (!link) return result;
        var onclick = link.getAttribute('onclick') || '';
        var m = onclick.match(/goDetails\(\s*'([^']*)'\s*(?:,\s*'([^']*)')?/);
        if (m) {
            result.entryId = m[1] || '';
            result.sid = m[2] || '';
        }
        return result;
    }

    function openEntry(entryId, sid) {
        var ids = listFormIds();
        window.formulize.drawer.openEntry({
            fid: ids.fid,
            frid: ids.frid,
            entryId: entryId,
            sid: sid
        });
    }

    function initRowDrawer() {
        document.querySelectorAll('tr.entry-row').forEach(function (row) {
            var link = row.querySelector('.loe-edit-entry');
            if (!link) return;
            row.addEventListener('click', function (e) {
                if (e.target.closest('.fz-cb')) return;
                var d = parseGoDetails(link);
                openEntry(d.entryId, d.sid);
            });
        });

        // Override Formulize's goDetails so the loe-edit-entry onclick opens the drawer
        window.goDetails = function (entryId, screen) {
            openEntry(entryId, screen || '');
        };

        // Override addNew so the Add button opens a blank entry in the drawer
        window.addNew = function () {
            openEntry('', '');
        };

        // After a drawer save, reload the list by submitting its controls form.
        // showLoading() captures the current scroll position and preserves all
        // active filters, sorting, and paging (they live as hidden fields in the
        // controls form), so the refreshed list reflects the change in place.
        window.formulize = window.formulize || {};
        window.formulize.onEntrySaved = function () {
            if (typeof showLoading === 'function') {
                showLoading();
            } else {
                window.location.reload();
            }
        };
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
        initFilterToggle();
        initRowDrawer();
    });
}());
</script>
