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
    function updateSelectionBar() {
        var checked = document.querySelectorAll('.formulize_selection_checkbox:checked');
        var bar     = document.getElementById('fz-selection-bar');
        var countEl = document.querySelector('.js-selection-count');
        if (!bar) return;
        if (countEl) countEl.textContent = checked.length + ' selected';
        if (checked.length > 0) {
            bar.removeAttribute('hidden');
        } else {
            bar.setAttribute('hidden', '');
        }
        // Sync aria-selected on each row for CSS highlight
        document.querySelectorAll('.formulize_selection_checkbox').forEach(function (cb) {
            var row = cb.closest('tr');
            if (row) row.setAttribute('aria-selected', cb.checked ? 'true' : 'false');
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        // Unbind Formulize's default handler that opens the more-actions panel on checkbox click.
        // The selection bar handles checkbox selection state for the Kairo theme.
        if (typeof jQuery !== 'undefined') {
            jQuery('.formulize_selection_checkbox').off('click');
        }
        document.addEventListener('change', function (e) {
            if (e.target.classList.contains('formulize_selection_checkbox')) {
                updateSelectionBar();
            }
        });
    });
}());
</script>
