<?php
$changeScopeUrl = htmlspecialchars(XOOPS_URL . "/modules/formulize/include/changescope.php?fid=$fid&frid=$frid&scope=$currentview");

// The name of the active view, shown on the switcher button beside the title.
// It is the selected item in the list, or the loaded view's name when nothing
// in the list is selected.
$activeViewLabel = '';
foreach ($viewitems as $item) {
    if ($item['type'] == 'item' && !empty($item['selected'])) {
        $activeViewLabel = $item['label'];
        break;
    }
}
if ($activeViewLabel === '' && $loadviewname) {
    $activeViewLabel = stripslashes($loadviewname);
}
$activeViewLabel = htmlspecialchars($activeViewLabel, ENT_QUOTES);
// The name is hidden on narrow screens, so the button's accessible name carries it too.
$toggleLabel = 'Switch view' . ($activeViewLabel !== '' ? ": $activeViewLabel" : '');

print "
<div class='lyris-view-switcher'>
  <input type='hidden' name='currentview' id='currentview' value='" . htmlspecialchars($currentview) . "'>
  <button type='button' class='fz-btn fz-btn--ghost lyris-view-switcher__toggle' id='lyris-view-toggle' popovertarget='lyris-view-panel' style='anchor-name: --lyris-view-panel' aria-label='$toggleLabel' title='$toggleLabel'>
    <svg class='lyris-view-switcher__icon' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' aria-hidden='true'>
      <rect x='3' y='3' width='7' height='9' rx='1'/>
      <rect x='14' y='3' width='7' height='5' rx='1'/>
      <rect x='14' y='12' width='7' height='9' rx='1'/>
      <rect x='3' y='16' width='7' height='5' rx='1'/>
    </svg>
    <span class='lyris-view-switcher__label'>$activeViewLabel</span>
    <svg class='lyris-view-switcher__chevron' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' aria-hidden='true'><path d='m6 9 6 6 6-6'/></svg>
  </button>
  <div id='lyris-view-panel' class='fz-popover fz-menu lyris-view-switcher__menu' popover style='position-anchor: --lyris-view-panel'>";

foreach ($viewitems as $item) {
    switch ($item['type']) {
        case 'group':
            print "<p class='fz-popover__title'>" . htmlspecialchars($item['label']) . "</p>";
            break;
        case 'item':
            $activeAttr  = $item['selected'] ? " aria-current='true'" : "";
            $isStandard  = $item['standard'] ? 'true' : 'false';
            $value       = htmlspecialchars($item['value'], ENT_QUOTES);
            $label       = htmlspecialchars($item['label']);
            print "<button type='button' class='fz-menu__item'$activeAttr onclick=\"fzSelectView('$value', $isStandard)\">$label</button>";
            break;
        case 'popup':
            $label = htmlspecialchars($item['label']);
            print "<button type='button' class='fz-menu__item' onclick=\"document.getElementById('lyris-view-panel').hidePopover(); showPop('$changeScopeUrl')\">$label</button>";
            break;
        case 'disabled':
            $label = htmlspecialchars($item['label']);
            print "<button type='button' class='fz-menu__item' disabled>$label</button>";
            break;
    }
}

// "Pick different group" — shown on a genuine multi-group scope to allow changing the groups
if (!$loadviewname && strstr($currentview, ',') && !$loadOnlyView) {
    $label = htmlspecialchars(_formulize_DE_PICKDIFFGROUP);
    print "<hr class='fz-menu__sep'>";
    print "<button type='button' class='fz-menu__item' onclick=\"document.getElementById('lyris-view-panel').hidePopover(); showPop('$changeScopeUrl')\">$label</button>";
}

print "
  </div>
</div>";
