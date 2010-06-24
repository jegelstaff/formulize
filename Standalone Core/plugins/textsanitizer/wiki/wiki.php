<?php
/**
 * Wiki TextSanitizer plugin
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @author	    Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @since		1.2
 * @package		plugins
 * @version		$Id$
 */
define('WIKI_LINK',	'http://'._LANGCODE.'.wikipedia.org/wiki/%s'); // The link to wiki module
function textsanitizer_wiki(&$ts, $text)
{
	$patterns[] = "/\[\[([^\]]*)\]\]/esU";
	$replacements[] = "wikiLink( '\\1' )";
	return preg_replace($patterns, $replacements, $text);
}
function wikiLink($text)
{
	if ( empty($text) ) return $text;
	$ret = "<a href='".sprintf( WIKI_LINK, $text )."' target='_blank' title=''>".$text."</a>";
	return $ret;
}
function render_wiki($ele_name)
{
    global $xoTheme;
    $dirname = basename(dirname(__FILE__));
    if(isset($xoTheme)){
        $xoTheme->addScript(ICMS_URL.'/plugins/textsanitizer/'.$dirname.'/'.$dirname.'.js', array('type' => 'text/javascript'));
    }
        $code = "<img onclick='javascript:icmsCodeWIKI(\"".$ele_name."\", \"".htmlspecialchars(_ENTERWIKICODE, ENT_QUOTES)."\");' onmouseover='style.cursor=\"pointer\"' src='".ICMS_URL."/plugins/textsanitizer/".basename(dirname(__FILE__))."/wiki.png' alt='wiki' />&nbsp;";
        /**
        * Using this method You can add a file to load your java script informations
        */
        $javascript = 'plugins/textsanitizer/'.basename(dirname(__FILE__)).'/wiki.js';;
         /**
        * Using this method You can add a code to load your java script informations
        */
       /*$javascript = <<<EOH
				function icmsCodeWIKI(id,enterWIKIPhrase){
    				if (enterWIKIPhrase == null) {
    				        enterWIKIPhrase = "Enter The Text To Be WIKI Code:";
    				}
					var text = prompt(enterWIKIPhrase, "");
					var domobj = xoopsGetElementById(id);
					if ( text != null && text != "" ) {
						var pos = text.indexOf(unescape('%00'));
						if(0 < pos){
							text = text.substr(0,pos);
						}
					    var result = "[[" + text + "]]";
					    xoopsInsertText(domobj, result);
					}
					
					domobj.focus();
					}
EOH;*/
        /**
        * Or if you are using a dynamic template:
        */
        
        //if(isset($xoTheme)){$xoTheme->addScript(false, array('type' => 'text/javascript'), $javascript)};

        return array($code, $javascript);
}
function style_wiki(){
$style_info = '';
return $style_info;
}
?>