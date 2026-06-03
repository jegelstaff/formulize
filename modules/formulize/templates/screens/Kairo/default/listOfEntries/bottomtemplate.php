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

// View switcher dropdown
(function () {
    function buildViewPanel() {
        var sel    = document.getElementById('currentview');
        var panel  = document.getElementById('fz-view-panel');
        var toggle = document.getElementById('fz-view-toggle');
        if (!sel || !panel || !toggle) return;

        panel.innerHTML = '';
        var hadItems = false;

        Array.from(sel.options).forEach(function (opt) {
            var val  = opt.value;
            var text = opt.text.replace(/^[ \s]+/, '').trim();

            if (!val) {
                // Section label
                if (hadItems) {
                    var sep = document.createElement('div');
                    sep.className = 'fz-pop__sep';
                    panel.appendChild(sep);
                }
                var lbl = document.createElement('div');
                lbl.className = 'fz-pop__group';
                lbl.textContent = text;
                panel.appendChild(lbl);
                hadItems = false;
            } else {
                hadItems = true;
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'fz-pop__item' + (opt.selected ? ' fz-pop__item--active' : '');
                btn.textContent = text;
                btn.dataset.value = val;
                btn.addEventListener('click', function () {
                    sel.value = val;
                    panel.classList.remove('open');
                    sel.dispatchEvent(new Event('change'));
                });
                panel.appendChild(btn);
            }
        });

        // "Pick different group" action appended by PHP when applicable
        if (window.__fzPickDiffGroup) {
            var sep2 = document.createElement('div');
            sep2.className = 'fz-pop__sep';
            panel.appendChild(sep2);
            var scopeBtn = document.createElement('button');
            scopeBtn.type = 'button';
            scopeBtn.className = 'fz-pop__item';
            scopeBtn.textContent = window.__fzPickDiffGroup.label;
            scopeBtn.addEventListener('click', function () {
                panel.classList.remove('open');
                showPop(window.__fzPickDiffGroup.url);
            });
            panel.appendChild(scopeBtn);
        }

        toggle.addEventListener('click', function (e) {
            e.stopPropagation();
            panel.classList.toggle('open');
        });
    }

    // Close view panel when clicking outside it
    document.addEventListener('click', function (e) {
        var panel = document.getElementById('fz-view-panel');
        if (!panel || !panel.classList.contains('open')) return;
        var wrap = panel.closest('.fz-view-switcher');
        if (wrap && !wrap.contains(e.target)) {
            panel.classList.remove('open');
        }
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', buildViewPanel);
    } else {
        buildViewPanel();
    }
}());

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
