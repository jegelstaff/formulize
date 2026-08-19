<?php
print "<div class='currentViewList'>";
print "<div class='currentViewList-caption'><b>" . $buttonText . "</b></div>";
print "<div class='currentViewList-list'><SELECT name=currentview id=currentview size=1 onchange=\"javascript:change_view(this.form, '$pickgroups', '$endstandard');\">\n";
print $viewoptions;
print "\n</SELECT></div>\n";
if(!$loadviewname AND strstr($currentview, ",") AND !$loadOnlyView) {
    print "<div class='currentViewList-button'><input type=button name=pickdiffgroup value='" . _formulize_DE_PICKDIFFGROUP . "' onclick=\"javascript:showPop('" . XOOPS_URL . "/modules/formulize/include/changescope.php?fid=$fid&frid=$frid&scope=$currentview');\"></input></div>";
}
print "</div>";
