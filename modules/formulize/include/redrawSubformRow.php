<?php
require_once '../../../mainfile.php';
ob_end_clean();
require_once 'functions.php';
require_once 'elementdisplay.php';

include XOOPS_ROOT_PATH.'/header.php'; // need to initialize the theme object even though we're not displaying a full page

$entry_id = intval($_GET['entry_id']); 
$subformElementId = intval($_GET['subformElementId']);
$element_handler = xoops_getmodulehandler('elements','formulize');
$subformElement = $element_handler->get($subformElementId);
$ele_value = $subformElement->getVar('ele_value');
$elementsToDraw = explode(",", $ele_value[1]);

$markup = "";
foreach($elementsToDraw as $thisele) {
    if($thisele) { 
        ob_start();
        // critical that we *don't* ask for displayElement to return the element object, since this way the validation logic is passed back through the global space also (ugh).  Otherwise, no validation logic possible for subforms.
        $renderResult = displayElement('', $thisele, $entry_id); 
        $col_two_temp = ob_get_contents();
        ob_end_clean();
        if($col_two_temp OR $renderResult == "rendered") { // only draw in a cell if there actually is an element rendered (some elements might be rendered as nothing (such as derived values)
            $markup .= "<td>$col_two_temp</td>\n";
        } else {
            $markup .= "<td>******</td>";
        }
    }
}
print $markup;
