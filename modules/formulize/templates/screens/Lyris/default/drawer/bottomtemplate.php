<?php

// Lyris's drawer buttons are in the order of its full screen bar (see its
// multiPage bottomtemplate), with Save last, at the right. Change the two together.

print "
<div style='clear: both;'></div>
</div>
</div>
<div class='formulize-drawer-buttons'>
$printableViewButton $saveAndLeaveButton $previousPageButton $closePageButton $nextPageButton $savePageButton
</div>
";
