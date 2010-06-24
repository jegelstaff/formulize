<?php
/**
 * CSS Highlighter TextSanitizer plugin
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @author	    Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @since		1.2
 * @package		plugins
 * @version		$Id$
 */
function textsanitizer_syntaxhighlightcss(&$ts, $text)
{
	$patterns[] = "/\[code_css](.*)\[\/code_css\]/esU";
	$replacements[] = "textsanitizer_geshi_css_highlight( '\\1' )";
	return preg_replace($patterns, $replacements, $text);
}
function textsanitizer_geshi_css_highlight( $source )
{
	if ( !@include_once ICMS_LIBRARIES_PATH . '/geshi/geshi.php' ) return false;
	$source = MyTextSanitizer::undoHtmlSpecialChars($source);

    // Create the new GeSHi object, passing relevant stuff
    $geshi = new GeSHi($source, 'css');
    // Enclose the code in a <div>
    $geshi->set_header_type(GESHI_HEADER_NONE);

	// Sets the proper encoding charset other than "ISO-8859-1"
    $geshi->set_encoding(_CHARSET);

	$geshi->set_link_target ( "_blank" );

    // Parse the code
    $code = $geshi->parse_code();
	$code = "<div class=\"icmsCodeCss\"><code>".$code."</code></div>";
    return $code;
}
function render_syntaxhighlightcss($ele_name)
{
    global $xoTheme;
    $javascript='';
    $dirname = basename(dirname(__FILE__));
    if(isset($xoTheme)){
        $xoTheme->addScript(ICMS_URL.'/plugins/textsanitizer/'.$dirname.'/'.$dirname.'.js', array('type' => 'text/javascript'));
    }
        $code = "<img onclick='javascript:icmsCodeCSS(\"".$ele_name."\", \"".htmlspecialchars(_ENTERCSSCODE, ENT_QUOTES)."\");' onmouseover='style.cursor=\"pointer\"' src='".ICMS_URL."/plugins/textsanitizer/".basename(dirname(__FILE__))."/css.png' alt='css' />&nbsp;";
        return array($code, $javascript);
}
function style_syntaxhighlightcss(){
$style_info = '';
return $style_info;
}
?>