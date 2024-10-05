<?php
require_once '../../../mainfile.php';

icms::$logger->disableLogger();
while(ob_get_level()) {
    ob_end_clean();
}

require_once 'functions.php';
require_once 'elementdisplay.php';

include XOOPS_ROOT_PATH.'/header.php'; // need to initialize the theme object even though we're not displaying a full page

$entry_id = intval($_GET['entry_id']); 
$subformElementId = intval($_GET['subformElementId']);
$element_handler = xoops_getmodulehandler('elements','formulize');
$subformElement = $element_handler->get($subformElementId);
$ele_value = $subformElement->getVar('ele_value');
$elementsToDraw = explode(",", $ele_value[1]);
$firstElementToDrawObject = $element_handler->get($elementsToDraw[0]);
$subformFormId = $firstElementToDrawObject->getVar('id_form');
$criteria = new CriteriaCompo();
$criteria->add(new Criteria('ele_id', "(".$ele_value[1].")", "IN"));
$criteria->setSort('ele_order');
$criteria->setOrder('ASC');
$elements = $element_handler->getObjects($criteria,$subformFormId,true); // true makes the keys of the returned array be the element ids

$markup = "";
foreach($elements as $thisele=>$elementObject) {
    if($thisele) { 
        $unsetDisabledFlag = false;
        if(in_array($thisele, explode(',',$subformElement->ele_value['disabledelements']))) {
            $unsetDisabledFlag = !isset($GLOBALS['formulize_forceElementsDisabled']);
            $GLOBALS['formulize_forceElementsDisabled'] = true;
        }
        ob_start();
        // critical that we *don't* ask for displayElement to return the element object, since this way the validation logic is passed back through the global space also (ugh).  Otherwise, no validation logic possible for subforms.
        $renderResult = displayElement('', $thisele, $entry_id); 
        $col_two_temp = trim(ob_get_contents());
        ob_end_clean();
        if($unsetDisabledFlag) { unset($GLOBALS['formulize_forceElementsDisabled']); }
        if($col_two_temp OR $renderResult == "rendered" OR $renderResult == "rendered-disabled") { // only draw in a cell if there actually is an element rendered (some elements might be rendered as nothing (such as derived values)
            $textAlign = "";
            if(is_numeric($col_two_temp)) {
                $col_two_temp = formulize_numberFormat($col_two_temp, $thisele);
                $textAlign = " right-align-text";
            }
            $markup .= "<td class='formulize_subform_".$thisele."$textAlign'>$col_two_temp</td>\n";
        } else {
            $markup .= "<td>******</td>";
        }
    }
}
print $markup;
