<?php

// Lyris puts the action bar's buttons on the right and the page meta on the
// left, matching the right drawer's footer (issue #121 item 4). The meta is
// emitted before the buttons so that DOM order, keyboard focus order and
// visual order all agree — the alignment itself is done in the theme's CSS
// (`#multipage-controls`), not by reversing the flex direction here.
//
// Save is the last button in the bar (issue #1055), so the primary action sits at
// the far right. The right drawer's footer (drawer.js renderEntryFooter) puts it
// last too, so the two surfaces keep the same order.

print "
<div style='clear: both;'></div>
</div>
</div>
</div>

<div id='multipage-controls'>
$pageIndicator $pageSelector $previousPageButton $closePageButton $nextPageButton $savePageButton
</div>
";
