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
function showMoreActionButtons() {
    var panel = document.getElementById('more-action-buttons');
    if (panel) { panel.classList.toggle('open'); }
}

// Close the more-actions dropdown when clicking outside it
document.addEventListener('click', function (e) {
    var panel = document.getElementById('more-action-buttons');
    if (!panel || !panel.classList.contains('open')) return;
    var wrap = panel.closest('.fz-list__more-wrap');
    if (wrap && !wrap.contains(e.target)) {
        panel.classList.remove('open');
    }
});

// View switcher — toggle and outside-click close
(function () {
    var toggle = document.getElementById('fz-view-toggle');
    if (toggle) {
        toggle.addEventListener('click', function (e) {
            e.stopPropagation();
            var panel = document.getElementById('fz-view-panel');
            if (panel) { panel.classList.toggle('open'); }
        });
    }

    document.addEventListener('click', function (e) {
        var panel = document.getElementById('fz-view-panel');
        if (!panel || !panel.classList.contains('open')) return;
        var wrap = panel.closest('.fz-view-switcher');
        if (wrap && !wrap.contains(e.target)) {
            panel.classList.remove('open');
        }
    });
}());

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

    function initRowDrawer() {
        document.querySelectorAll('tr.entry-row').forEach(function (row) {
            var link = row.querySelector('.loe-edit-entry');
            if (!link || !link.href) return;
            row.addEventListener('click', function (e) {
                if (e.target.closest('.fz-cb')) return;
                window.formulize.drawer.openEntry(link.href);
            });
        });

        // Override Formulize's goDetails so the loe-edit-entry onclick also opens the drawer
        window.goDetails = function (entryId) {
            var selector = '.loe-edit-entry[onclick*="goDetails(\'' + entryId + '\')"]';
            var link = document.querySelector(selector);
            if (link && link.href) { window.formulize.drawer.openEntry(link.href); }
        };

        // Override addNew so the Add button opens the drawer instead of submitting the form
        window.addNew = function () {
            var base = document.querySelector('.loe-edit-entry');
            var url;
            if (base && base.href) {
                url = base.href.replace(/([?&])ve=[^&]+/, '$1ve=addnew');
                if (url === base.href) {
                    url += (base.href.indexOf('?') >= 0 ? '&' : '?') + 've=addnew';
                }
            } else {
                var params = new URLSearchParams(window.location.search);
                params.set('ve', 'addnew');
                url = window.location.pathname + '?' + params.toString();
            }
            window.formulize.drawer.openEntry(url);
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
