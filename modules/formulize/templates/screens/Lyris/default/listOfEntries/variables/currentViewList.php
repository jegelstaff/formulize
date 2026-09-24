<?php
$changeScopeUrl = htmlspecialchars(XOOPS_URL . "/modules/formulize/include/changescope.php?fid=$fid&frid=$frid&scope=$currentview");

print "
<div class='lyris-view-switcher'>
  <input type='hidden' name='currentview' id='currentview' value='" . htmlspecialchars($currentview) . "'>
  <button type='button' class='fz-btn fz-btn--ghost fz-btn--icon' id='lyris-view-toggle' data-lyris-panel='lyris-view-panel' aria-label='Switch view' title='Switch view'>
    <svg width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' aria-hidden='true'>
      <circle cx='6' cy='15' r='4'/>
      <circle cx='18' cy='15' r='4'/>
      <path d='M6 11V7'/>
      <path d='M18 11V7'/>
      <path d='M6 7h12'/>
      <path d='M11 12h2'/>
    </svg>
  </button>
  <div id='lyris-view-panel' class='lyris-view-switcher__panel lyris-panel'>";

foreach ($viewitems as $item) {
    switch ($item['type']) {
        case 'group':
            print "<div class='lyris-pop__group'>" . htmlspecialchars($item['label']) . "</div>";
            break;
        case 'item':
            $activeClass = $item['selected'] ? " lyris-pop__item--active" : "";
            $isStandard  = $item['standard'] ? 'true' : 'false';
            $value       = htmlspecialchars($item['value'], ENT_QUOTES);
            $label       = htmlspecialchars($item['label']);
            print "<button type='button' class='lyris-pop__item$activeClass' onclick=\"fzSelectView('$value', $isStandard)\">$label</button>";
            break;
        case 'popup':
            $label = htmlspecialchars($item['label']);
            print "<button type='button' class='lyris-pop__item' onclick=\"document.getElementById('lyris-view-panel').classList.remove('open'); showPop('$changeScopeUrl')\">$label</button>";
            break;
        case 'disabled':
            $label = htmlspecialchars($item['label']);
            print "<div class='lyris-pop__item lyris-pop__item--disabled'>$label</div>";
            break;
    }
}

// "Pick different group" — shown on a genuine multi-group scope to allow changing the groups
if (!$loadviewname && strstr($currentview, ',') && !$loadOnlyView) {
    $label = htmlspecialchars(_formulize_DE_PICKDIFFGROUP);
    print "<div class='lyris-pop__sep'></div>";
    print "<button type='button' class='lyris-pop__item' onclick=\"document.getElementById('lyris-view-panel').classList.remove('open'); showPop('$changeScopeUrl')\">$label</button>";
}

print "
  </div>
</div>";
