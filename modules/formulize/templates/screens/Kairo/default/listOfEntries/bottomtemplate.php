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

    function openEntryDrawer(url) {
        if (!window.formulizeDrawer) return;
        var sep = url.indexOf('?') >= 0 ? '&' : '?';
        var iframeUrl = url + sep + 'fz_inline=1';

        window.formulizeDrawer.open({ title: '' });

        var bodyEl = document.querySelector('.js-drawer-body');
        if (!bodyEl) return;
        bodyEl.innerHTML = '';

        var iframe = document.createElement('iframe');
        iframe.src = iframeUrl;
        iframe.className = 'fz-drawer__iframe';
        iframe.setAttribute('title', 'Entry detail');

        var initialLoad = true;
        iframe.addEventListener('load', function () {
            if (initialLoad) {
                initialLoad = false;
                try {
                    var h1 = iframe.contentDocument.querySelector('h1');
                    var titleEl = document.querySelector('.js-drawer-title');
                    if (h1 && titleEl) titleEl.textContent = h1.textContent.trim();
                } catch (e) {}
                return;
            }
            // Any subsequent navigation (save / cancel) → close and refresh list
            window.formulizeDrawer.close();
            window.location.reload();
        });

        bodyEl.appendChild(iframe);
    }

    function initRowDrawer() {
        document.querySelectorAll('tr.entry-row').forEach(function (row) {
            var link = row.querySelector('.loe-edit-entry');
            if (!link || !link.href) return;
            row.addEventListener('click', function (e) {
                if (e.target.closest('.fz-cb')) return;
                openEntryDrawer(link.href);
            });
        });

        // Override Formulize's goDetails so the loe-edit-entry onclick also opens the drawer
        window.goDetails = function (entryId) {
            var selector = '.loe-edit-entry[onclick*="goDetails(\'' + entryId + '\')"]';
            var link = document.querySelector(selector);
            if (link && link.href) { openEntryDrawer(link.href); }
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
            openEntryDrawer(url);
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
