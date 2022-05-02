<?php

print "
</table>
</div>

<hr>
<br>
<nobr>$previousPageButton &nbsp;&nbsp;&nbsp; $savePageButton &nbsp;&nbsp;&nbsp; $nextPageButton
";

if($pageIndicator) {
    print "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$pageIndicator;
}

if($pageSelector) {
    print "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$pageSelector;
}
    
print "</nobr>";
