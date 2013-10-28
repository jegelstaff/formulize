<?php
/**
 * Administration of smilies, main functions file
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @package		System
 * @subpackage	Smilies
 * @todo		Extract HTML and CSS to a template
 * @version		SVN: $Id: smilies.php 22486 2011-08-31 03:39:49Z blauer-fisch $
 */

if (!is_object(icms::$user) || !is_object($icmsModule) || !icms::$user->isAdmin($icmsModule->getVar('mid'))) {
	exit("Access Denied");
}

/**
 * Logic and rendering for Smilies administration
 * 
 */
function SmilesAdmin() {
	$db =& icms_db_Factory::instance();
	$url_smiles = ICMS_UPLOAD_URL;
	icms_cp_header();
	echo '<div class="CPbigTitle" style="background-image: url(' . ICMS_MODULES_URL . '/system/admin/smilies/images/smilies_big.png)">' . _AM_SMILESCONTROL . '</div><br />';

	if ($getsmiles = $db->query("SELECT * FROM " . $db->prefix("smiles"))) {
		if (($numsmiles = $db->getRowsNum($getsmiles)) == "0") {
			//EMPTY
		} else {
			echo '<form action="admin.php" method="post"><table width="100%" class="outer" cellpadding="4" cellspacing="1">'
				. "<tr align='center'><th align='" . _GLOBAL_LEFT . "'>" ._AM_CODE . "</th>"
				. "<th>" ._AM_SMILIE . "</th>"
				. "<th>" . _AM_SMILEEMOTION . "</th>"
				. "<th>" ._AM_DISPLAYF . "</th>"
				. "<th>" . _AM_ACTION . "</th>"
				. "</tr>\n";
			$i = 0;
			while ($smiles = $db->fetchArray($getsmiles)) {
				if ($i % 2 == 0) {
					$class = 'even';
				} else {
					$class= 'odd';
				}
				$smiles['code'] = icms_core_DataFilter::htmlSpecialChars($smiles['code']);
				$smiles['smile_url'] = icms_core_DataFilter::htmlSpecialChars($smiles['smile_url']);
				$smiles['smile_emotion'] = icms_core_DataFilter::htmlSpecialChars($smiles['emotion']);
				echo "<tr align='center' class='$class'>"
					. "<td align='" . _GLOBAL_LEFT . "'>" . $smiles['code'] . "</td>"
					. "<td><img src='" . $url_smiles . "/" . $smiles['smile_url'] . "' alt='' /></td>"
					. '<td>' . $smiles['smile_emotion'] . '</td>'
					. '<td><input type="hidden" name="smile_id[' . $i . ']" value="' . $smiles['id'] . '" /><input type="hidden" name="old_display[' . $i . ']" value="' . $smiles['display'] . '" /><input type="checkbox" value="1" name="smile_display[' . $i . ']"';
				if ($smiles['display'] == 1) {
					echo ' checked="checked"';
				}
				echo " /></td><td><a href='admin.php?fct=smilies&amp;op=SmilesEdit&amp;id=" . $smiles['id'] . "'><img src='". ICMS_IMAGES_SET_URL . "/actions/edit.png' alt=" . _EDIT . " title=" . _EDIT . " /></a>&nbsp;"
					. "<a href='admin.php?fct=smilies&amp;op=SmilesDel&amp;id=" . $smiles['id'] . "'><img src='". ICMS_IMAGES_SET_URL . "/actions/editdelete.png' alt=" . _DELETE . " title=" . _DELETE . " /></a></td>"
					. "</tr>\n";
				$i++;
			}
			echo '<tr><td class="foot" colspan="5" align="center">'
				. '<input type="hidden" name="op" value="SmilesUpdate" /><input type="hidden" name="fct" value="smilies" />' 
				. icms::$security->getTokenHTML() 
				. '<input type="submit" value="' . _SUBMIT . '" /></tr></table></form>';
		}
	} else {
		echo _AM_CNRFTSD;
	}
	$smiles['smile_code'] = '';
	$smiles['smile_url'] = 'blank.gif';
	$smiles['smile_desc'] = '';
	$smiles['smile_display'] = 1;
	$smiles['smile_form'] = _AM_ADDSMILE;
	$smiles['op'] = 'SmilesAdd';
	$smiles['id'] = '';
	echo "<br />";
	include ICMS_MODULES_PATH . '/system/admin/smilies/smileform.php';
	$smile_form->display();
	icms_cp_footer();
}

/**
 * Logic and rendering for editing a smilie
 * 
 * @param int $id
 */
function SmilesEdit($id) {
	$db =& icms_db_Factory::instance();
	icms_cp_header();
	echo '<a href="admin.php?fct=smilies">' . _AM_SMILESCONTROL .'</a>&nbsp;<span style="font-weight:bold;">&raquo;&raquo;</span>&nbsp;' . _AM_EDITSMILE . '<br /><br />';
	if ($getsmiles = $db->query("SELECT * FROM " . $db->prefix("smiles") . " WHERE id = '". (int) $id . "'")) {
		$numsmiles = $db->getRowsNum($getsmiles);
		if ($numsmiles == 0) {
			//EMPTY
		} else {
			if ($smiles = $db->fetchArray($getsmiles)) {
				$smiles['smile_code'] = icms_core_DataFilter::htmlSpecialChars($smiles['code']);
				$smiles['smile_url'] = icms_core_DataFilter::htmlSpecialChars($smiles['smile_url']);
				$smiles['smile_desc'] = icms_core_DataFilter::htmlSpecialChars($smiles['emotion']);
				$smiles['smile_display'] = $smiles['display'];
				$smiles['smile_form'] = _AM_EDITSMILE;
				$smiles['op'] = 'SmilesSave';
				include ICMS_MODULES_PATH . '/system/admin/smilies/smileform.php';
				$smile_form->addElement(new icms_form_elements_Hidden('old_smile', $smiles['smile_url']));
				$smile_form->display();
			}
		}
	} else {
		echo _AM_CNRFTSD;
	}
	icms_cp_footer();
}
