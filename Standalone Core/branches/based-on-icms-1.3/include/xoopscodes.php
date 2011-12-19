<?php
/**
 * The functions that take care of BB Codes
 *
 * @copyright	http://www.xoops.org/ The XOOPS Project
 * @copyright	XOOPS_copyrights.txt
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license	LICENSE.txt
 * @package	core
 * @since	XOOPS
 * @author	http://www.xoops.org The XOOPS Project
 * @author	modified by UnderDog <underdog@impresscms.org>
 * @version	$Id: xoopscodes.php 22138 2011-07-30 22:45:31Z skenow $
 */

/*
 * displayes xoopsCode buttons and target textarea to which xoopscodes are inserted
 * $textarea_id is a unique id of the target textarea
 */
function xoopsCodeTarea($textarea_id, $cols=60, $rows=15, $suffix=null)
{
	$hiddentext = isset($suffix) ? 'xoopsHiddenText'.trim($suffix) : 'xoopsHiddenText';
	//Hack for url, email ...., the anchor is for having a link on [_More...]
	echo "<a name='moresmiley'></a><img src='".ICMS_URL."/images/url.gif' alt='url' onmouseover='style.cursor=\"pointer\"' onclick='xoopsCodeUrl(\"$textarea_id\", \"".htmlspecialchars(_ENTERURL, ENT_QUOTES)."\", \"".htmlspecialchars(_ENTERWEBTITLE, ENT_QUOTES)."\");'/>&nbsp;<img src='".ICMS_URL."/images/email.gif' alt='email' onmouseover='style.cursor=\"pointer\"' onclick='xoopsCodeEmail(\"$textarea_id\", \"".htmlspecialchars(_ENTEREMAIL, ENT_QUOTES)."\");' />&nbsp;<img src='".ICMS_URL."/images/imgsrc.gif' alt='imgsrc' onmouseover='style.cursor=\"pointer\"' onclick='xoopsCodeImg(\"$textarea_id\", \"".htmlspecialchars(_ENTERIMGURL, ENT_QUOTES)."\", \"".htmlspecialchars(_ENTERIMGPOS, ENT_QUOTES)."\", \"".htmlspecialchars(_IMGPOSRORL, ENT_QUOTES)."\", \"".htmlspecialchars(_ERRORIMGPOS, ENT_QUOTES)."\");' />&nbsp;<img src='".ICMS_URL."/images/image.gif' alt='image' onmouseover='style.cursor=\"pointer\"' onclick='openWithSelfMain(\"".ICMS_URL."/imagemanager.php?target=".$textarea_id."\",\"imgmanager\",800,430);' />&nbsp;<img src='".ICMS_URL."/images/code.gif' alt='code' onmouseover='style.cursor=\"pointer\"' onclick='xoopsCodeCode(\"$textarea_id\", \"".htmlspecialchars(_ENTERCODE, ENT_QUOTES)."\");' />&nbsp;<img src='".ICMS_URL."/images/quote.gif' alt='quote' onmouseover='style.cursor=\"pointer\"' onclick='xoopsCodeQuote(\"$textarea_id\");'/><br />\n";

	$sizearray = array("xx-small", "x-small", "small", "medium", "large", "x-large", "xx-large");
	echo "<select id='".$textarea_id."Size' onchange='setVisible(\"xoopsHiddenText\");setElementSize(\"".$hiddentext."\",this.options[this.selectedIndex].value);'>\n";
	echo "<option value='SIZE'>"._SIZE."</option>\n";
	foreach ( $sizearray as $size) {
		echo "<option value='$size'>$size</option>\n";
	}
	echo "</select>\n";

	$fontarray = array("Arial", "Courier", "Georgia", "Helvetica", "Impact", "Verdana");
	echo "<select id='".$textarea_id."Font' onchange='setVisible(\"xoopsHiddenText\");setElementFont(\"".$hiddentext."\",this.options[this.selectedIndex].value);'>\n";
	echo "<option value='FONT'>"._FONT."</option>\n";
	foreach ( $fontarray as $font) {
		echo "<option value='$font'>$font</option>\n";
	}
	echo "</select>\n";

	$colorarray = array("00", "33", "66", "99", "CC", "FF");
	echo "<select id='".$textarea_id."Color' onchange='setVisible(\"xoopsHiddenText\");setElementColor(\"".$hiddentext."\",this.options[this.selectedIndex].value);'>\n";
	echo "<option value='COLOR'>"._COLOR."</option>\n";
	foreach ( $colorarray as $color1) {
		foreach ( $colorarray as $color2) {
			foreach ( $colorarray as $color3) {
				echo "<option value='".$color1.$color2.$color3."' style='background-color:#".$color1.$color2.$color3.";color:#".$color1.$color2.$color3.";'>#".$color1.$color2.$color3."</option>\n";
			}
		}
	}
	echo "</select><span id='".$hiddentext."'>"._EXAMPLE."</span>\n";

	echo "<br />\n";
	//Hack smilies move for bold, italic ...
	$areacontent = isset( $GLOBALS[$textarea_id] ) ? $GLOBALS[$textarea_id] : '';
	echo "<img src='".ICMS_URL."/images/bold.gif' alt='bold' onmouseover='style.cursor=\"hand\"' onclick='setVisible(\"".$hiddentext."\");makeBold(\"".$hiddentext."\");' />&nbsp;<img src='".ICMS_URL."/images/italic.gif' alt='italic' onmouseover='style.cursor=\"hand\"' onclick='setVisible(\"".$hiddentext."\");makeItalic(\"".$hiddentext."\");' />&nbsp;<img src='".ICMS_URL."/images/underline.gif' alt='underline' onmouseover='style.cursor=\"hand\"' onclick='setVisible(\"".$hiddentext."\");makeUnderline(\"".$hiddentext."\");'/>&nbsp;<img src='".ICMS_URL."/images/linethrough.gif' alt='linethrough' onmouseover='style.cursor=\"hand\"' onclick='setVisible(\"".$hiddentext."\");makeLineThrough(\"".$hiddentext."\");' /></a>&nbsp;<input type='text' id='".$textarea_id."Addtext' size='20' />&nbsp;<input type='button' onclick='xoopsCodeText(\"$textarea_id\", \"".$hiddentext."\", \"".htmlspecialchars(_ENTERTEXTBOX, ENT_QUOTES)."\")' value='"._ADD."' /><br /><br /><textarea id='".$textarea_id."' name='".$textarea_id."' cols='$cols' rows='$rows'>".$areacontent."</textarea><br />\n";
	//Fin du hack
}

/*
 * Displays smilie image buttons used to insert smilie codes to a target textarea in a form
 * $textarea_id is a unique of the target textarea
 */
function xoopsSmilies($textarea_id)
{
	$smiles =& icms_core_DataFilter::getSmileys();
	if (empty($smileys)) {
		if ($result = icms::$xoopsDB->query("SELECT * FROM ".$db->prefix('smiles')." WHERE display='1'")) {
			while ($smiles = icms::$xoopsDB->fetchArray($result)) {
				//hack smilies move for the smilies !!
				echo "<img src='".ICMS_UPLOAD_URL."/".htmlspecialchars($smiles['smile_url'])."' border='0' onmouseover='style.cursor=\"hand\"' alt='' onclick='xoopsCodeSmilie(\"".$textarea_id."_tarea\", \" ".$smiles['code']." \");' />";
				//fin du hack
			}
		}
	} else {
		$count = count($smiles);
		for ($i = 0; $i < $count; $i++) {
			if ($smiles[$i]['display'] == 1) {
				//hack bis
				echo "<img src='".ICMS_UPLOAD_URL."/".icms_core_DataFilter::htmlSpecialChars($smiles['smile_url'])."' border='0' alt='' onclick='xoopsCodeSmilie(\"".$textarea_id."_tarea\", \" ".$smiles[$i]['code']." \");' onmouseover='style.cursor=\"hand\"' />";
				//fin du hack
			}
		}
	}
	//hack for more
	echo "&nbsp;[<a href='#moresmiley' onmouseover='style.cursor=\"hand\"' alt='' onclick='openWithSelfMain(\"".ICMS_URL."/misc.php?action=showpopups&amp;type=smilies&amp;target=".$textarea_id."_tarea\",\"smilies\",300,475);'>"._MORE."</a>]";
}  //fin du hack