<?php

// Lyris puts the action bar's buttons on the right and the page meta on the
// left, matching the right drawer's footer (issue #121 item 4). The meta is
// emitted before the buttons so that DOM order, keyboard focus order and
// visual order all agree — the alignment itself is done in the theme's CSS
// (`#multipage-controls`), not by reversing the flex direction here.
//
// Save comes last, at the far right. The drawer's footer follows the same order,
// because Lyris's drawer toptemplate names it in data-formulize-button-order, so
// change the two together.

print "
<div style='clear: both;'></div>
</div>
</div>
</div>

<div id='multipage-controls'>
$pageIndicator $pageSelector $previousPageButton $closePageButton $nextPageButton $savePageButton
</div>
";
