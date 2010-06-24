<?php
/**
 * Youtube TextSanitizer plugin
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @author	    Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @since		1.2
 * @package		plugins
 * @version		$Id$
 */
function textsanitizer_wmp(&$ts, $text)
{
	$patterns[] = "/\[wmp=(['\"]?)([^\"']*),([^\"']*)\\1]([^\"]*)\[\/wmp\]/sU";
        $rp  = "<object classid=\"clsid:6BF52A52-394A-11D3-B153-00C04F79FAA6\" id=\"WindowsMediaPlayer\" width=\"\\2\" height=\"\\3\">\n";
        $rp .= "<param name=\"URL\" value=\"\\4\">\n";
        $rp .= "<param name=\"AutoStart\" value=\"0\">\n";
        $rp .= "<embed autostart=\"0\" src=\"\\4\" type=\"video/x-ms-wmv\" width=\"\\2\" height=\"\\3\" controls=\"ImageWindow\" console=\"cons\"> </embed>";
        $rp .= "</object>\n";
	$replacements[] = $rp;
	return preg_replace($patterns, $replacements, $text);
}
function render_wmp($ele_name)
{
    global $xoTheme;
    $javascript='';
    $dirname = basename(dirname(__FILE__));
    if(isset($xoTheme)){
        $xoTheme->addScript(ICMS_URL.'/plugins/textsanitizer/'.$dirname.'/'.$dirname.'.js', array('type' => 'text/javascript'));
    }
        $code = "<img onclick='javascript:icmsCodeWmp(\"".$ele_name."\", \"".htmlspecialchars(_ENTERMEDIAURL, ENT_QUOTES)."\", \"".htmlspecialchars(_ENTERHEIGHT, ENT_QUOTES)."\", \"".htmlspecialchars(_ENTERWIDTH, ENT_QUOTES)."\");' onmouseover='style.cursor=\"pointer\"' src='".ICMS_URL."/plugins/textsanitizer/".basename(dirname(__FILE__))."/wmp.gif' alt='hide' />&nbsp;";

        return array($code, $javascript);
}
?>