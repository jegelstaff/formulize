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

(function () {
    // Selection bar: show over the titlebar when rows are checked
    function updateSelectionBar() {
        var checked = document.querySelectorAll('.formulize_selection_checkbox:checked');
        var bar      = document.getElementById('fz-selection-bar');
        var titlebar = document.querySelector('.fz-list__titlebar');
        var countEl  = document.querySelector('.js-selection-count');
        if (!bar) return;
        if (countEl) countEl.textContent = checked.length + ' selected';
        if (checked.length > 0) {
            bar.removeAttribute('hidden');
            if (titlebar) titlebar.setAttribute('hidden', '');
        } else {
            bar.setAttribute('hidden', '');
            if (titlebar) titlebar.removeAttribute('hidden');
        }
        document.querySelectorAll('.formulize_selection_checkbox').forEach(function (cb) {
            var row = cb.closest('tr');
            if (row) row.setAttribute('aria-selected', cb.checked ? 'true' : 'false');
        });
    }

    // Filter toggle: show/hide the search row
    function initFilterToggle() {
        var btn = document.getElementById('fz-filter-toggle');
        if (!btn) return;
        btn.addEventListener('click', function () {
            var rows = document.querySelectorAll('.fz-search-row');
            var isHidden = rows.length > 0 && rows[0].hasAttribute('hidden');
            rows.forEach(function (row) {
                if (isHidden) { row.removeAttribute('hidden'); }
                else          { row.setAttribute('hidden', ''); }
            });
            btn.setAttribute('aria-pressed', String(isHidden));
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
        initFilterToggle();
    });
}());
</script>
