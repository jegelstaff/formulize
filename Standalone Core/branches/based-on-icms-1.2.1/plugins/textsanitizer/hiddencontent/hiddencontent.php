<?php
/**
 * Hidden Content TextSanitizer plugin
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @author	    Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @since		1.2
 * @package		plugins
 * @version		$Id$
 */
function textsanitizer_hiddencontent(&$ts, $text)
{
        	$patterns[] = "/\[hide](.*)\[\/hide\]/sU";
			if(!empty($_SESSION['xoopsUserId']) && $_SESSION['xoopsUserId'])
			{$replacements[] = _HIDDENC.'<div class="icmsHidden">\\1</div>';}
			else{$replacements[] = _HIDDENC.'<div class="icmsHidden">'._HIDDENTEXT.'</div>';}
	return preg_replace($patterns, $replacements, $text);
}
function render_hiddencontent($ele_name)
{
    global $xoTheme;
    $javascript='';
    $dirname = basename(dirname(__FILE__));
    if(isset($xoTheme)){
        $xoTheme->addScript(ICMS_URL.'/plugins/textsanitizer/'.$dirname.'/'.$dirname.'.js', array('type' => 'text/javascript'));
    }
        $code = "<img onclick='javascript:icmsCodeHidden(\"".$ele_name."\", \"".htmlspecialchars(_ENTERHIDDEN, ENT_QUOTES)."\");' onmouseover='style.cursor=\"pointer\"' src='".ICMS_URL."/images/hide.gif' alt='hide' />&nbsp;";
        //$javascript = 'plugins/textsanitizer/'.basename(dirname(__FILE__)).'/hiddencontent.js';;
        return array($code, $javascript);
}
/**
 * You can use a function like this to add css information
 */
 
/*
function style_hiddencontent(){
$style_info = '.icmsHidden { background-color: #FAFAFA; color: #444; font-size: .9em; line-height: 1.2em; text-align: justify; border: #c2cdd6 1px dashed;}';
return $style_info;
}
*/
?>