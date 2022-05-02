<?php

print "
</table>
</div>

<hr>
<br>
<nobr>$savePageButton
";

if($pageIndicator) {
    print "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$pageIndicator;
}

if($pageSelector) {
    print "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$pageSelector;
}
    
print "</nobr>";