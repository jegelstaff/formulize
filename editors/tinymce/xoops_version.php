<?php
/**
* XoopsEditors class TinyMCE editor, version file
*
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license	LICENSE.txt
* @package	xoopseditors
* @since	XOOPS
* @author	http://www.xoops.org The XOOPS Project
* @author	modified by UnderDog <underdog@impresscms.org>
* @version	$Id: xoops_version.php 8566 2009-04-11 12:52:08Z icmsunderdog $
*/

// ImpressCMS - Nekro!

$editorversion['name'] = "TinyMCE WYSIWYG Editor";
$editorversion['version'] = 1.0;
$editorversion['license'] = "GPL see LICENSE";
$editorversion['dirname'] = "tinymce";

$editorversion['class'] = "XoopsFormTinymce";
$editorversion['file'] = "formtinymce.php";

// The next lines has not been removed to be studied.

#######################################
# TinyEditor for Xoops by ralf57
#	Version: 0.5
#######################################

//automatically load editor's plugins > v0.5
//be sure to also add the related lang constants to /modules/tinyeditor/your_language/modinfo.php
//when adding new plugins

/*$path = XOOPS_ROOT_PATH."/modules/tinyeditor/editor/plugins";
if ($handle = opendir($path)) {
   while (false !== ($file = readdir($handle))) {
       if (is_dir($path."/".$file) && $file != "." && $file != "..") {
			$name = strtoupper($file);
			$name = "_MI_TINYPLG".$name;
			$plugins_array[$name] = $file;
       }
   }
   closedir($handle);
}

$modversion['name'] = _MI_TINY_NAME;
$modversion['version'] = 0.5;
$modversion['description'] = _MI_TINY_DESC;
$modversion['credits'] = _MI_TINY_CRED;
$modversion['author'] = _MI_TINY_AUTH;
$modversion['license'] = _MI_TINY_LICENCE;
$modversion['official'] = 0;
$modversion['image'] = "images/tinyeditor.png" ;
$modversion['help'] = "";
$modversion['dirname'] = "tinyeditor";

// Admin
$modversion['hasAdmin'] = 1;
$modversion['adminindex'] = "admin/index.php";
$modversion['adminmenu'] = "admin/menu.php";

$modversion['hasComments'] = 0;
$modversion['hasNotification'] = 0;

// Config categories
$modversion['configcat'][1]['nameid'] = 'tiny_settings';
$modversion['configcat'][1]['name'] = '_MI_CAT_TINYSETTINGS';
$modversion['configcat'][1]['description'] = '__MI_CAT_TINYSETTINGS_DESC';

$modversion['configcat'][2]['nameid'] = 'tiny_manager';
$modversion['configcat'][2]['name'] = '_MI_CAT_TINYMNGR';
$modversion['configcat'][2]['description'] = '_MI_CAT_TINYMNGR_DESC';

//Config options
$modversion['config'][1]['name'] = 'tinytheme';
$modversion['config'][1]['title'] = '_MI_TINYTHEME';
$modversion['config'][1]['description'] = '_MI_TINYTHEMEDESC';
$modversion['config'][1]['formtype'] = 'select';
$modversion['config'][1]['valuetype'] = 'text';
$modversion['config'][1]['default'] = "default";
$modversion['config'][1]['options'] = array('_MI_TINYTHEMEDEF' => 'default','_MI_TINYTHEMEADV' => 'advanced','_MI_TINYTHEMESIMP' => 'simple');
$modversion['config'][1]['category'] = 'tiny_settings';

$modversion['config'][2]['name'] = 'tinylang';
$modversion['config'][2]['title'] = '_MI_TINYLANG';
$modversion['config'][2]['description'] = '_MI_TINYLANGDESC';
$modversion['config'][2]['formtype'] = 'textbox';
$modversion['config'][2]['valuetype'] = 'text';
$modversion['config'][2]['default'] = "en";
$modversion['config'][2]['category'] = 'tiny_settings';

$modversion['config'][3]['name'] = 'tinycss';
$modversion['config'][3]['title'] = '_MI_TINYCSS';
$modversion['config'][3]['description'] = '_MI_TINYCSSDESC';
$modversion['config'][3]['formtype'] = 'textbox';
$modversion['config'][3]['valuetype'] = 'text';
$modversion['config'][3]['default'] = "";
$modversion['config'][3]['category'] = 'tiny_settings';

$modversion['config'][4]['name'] = 'tinyforcebr';
$modversion['config'][4]['title'] = '_MI_TINYFORCEBR';
$modversion['config'][4]['description'] = '_MI_TINYFORCEBRDESC';
$modversion['config'][4]['formtype'] = 'yesno';
$modversion['config'][4]['valuetype'] = 'int';
$modversion['config'][4]['default'] = 0;
$modversion['config'][4]['category'] = 'tiny_settings';

$modversion['config'][5]['name'] = 'tinyforcep';
$modversion['config'][5]['title'] = '_MI_TINYFORCEP';
$modversion['config'][5]['description'] = '_MI_TINYFORCEPDESC';
$modversion['config'][5]['formtype'] = 'yesno';
$modversion['config'][5]['valuetype'] = 'int';
$modversion['config'][5]['default'] = 1;
$modversion['config'][5]['category'] = 'tiny_settings';

$modversion['config'][6]['name'] = 'tinyrelurls';
$modversion['config'][6]['title'] = '_MI_TINYRELURLS';
$modversion['config'][6]['description'] = '_MI_TINYRELURLSDESC';
$modversion['config'][6]['formtype'] = 'yesno';
$modversion['config'][6]['valuetype'] = 'int';
$modversion['config'][6]['default'] = 1;
$modversion['config'][6]['category'] = 'tiny_settings';

$modversion['config'][7]['name'] = 'tinyremhost';
$modversion['config'][7]['title'] = '_MI_TINYREMHOST';
$modversion['config'][7]['description'] = '_MI_TINYREMHOSTDESC';
$modversion['config'][7]['formtype'] = 'yesno';
$modversion['config'][7]['valuetype'] = 'int';
$modversion['config'][7]['default'] = 0;
$modversion['config'][7]['category'] = 'tiny_settings';

$modversion['config'][8]['name'] = 'tinyrtldir';
$modversion['config'][8]['title'] = '_MI_TINYRTLDIR';
$modversion['config'][8]['description'] = '_MI_TINYRTLDIRDESC';
$modversion['config'][8]['formtype'] = 'yesno';
$modversion['config'][8]['valuetype'] = 'int';
$modversion['config'][8]['default'] = 0;
$modversion['config'][8]['category'] = 'tiny_settings';

$modversion['config'][9]['name'] = 'tinytbloc';
$modversion['config'][9]['title'] = '_MI_TINYTBLOC';
$modversion['config'][9]['description'] = '_MI_TINYTBLOCDESC';
$modversion['config'][9]['formtype'] = 'select';
$modversion['config'][9]['valuetype'] = 'text';
$modversion['config'][9]['default'] = "bottom";
$modversion['config'][9]['options'] = array('_MI_TINYTBLOCBOT' => 'bottom','_MI_TINYTBLOCTOP' => 'top');
$modversion['config'][9]['category'] = 'tiny_settings';

$modversion['config'][10]['name'] = 'tinypathloc';
$modversion['config'][10]['title'] = '_MI_TINYPATHLOC';
$modversion['config'][10]['description'] = '_MI_TINYPATHLOCDESC';
$modversion['config'][10]['formtype'] = 'select';
$modversion['config'][10]['valuetype'] = 'text';
$modversion['config'][10]['default'] = "none";
$modversion['config'][10]['options'] = array('_MI_TINYPATHLOCBOT' => 'bottom','_MI_TINYPATHLOCTOP' => 'top','_MI_TINYPATHLOCNO' => 'none');
$modversion['config'][10]['category'] = 'tiny_settings';

$modversion['config'][11]['name'] = 'tinyplugs';
$modversion['config'][11]['title'] = '_MI_TINYPLUGS';
$modversion['config'][11]['description'] = '_MI_TINYPLUGSDESC';
$modversion['config'][11]['formtype'] = 'select_multi';
$modversion['config'][11]['valuetype'] = 'array';
$modversion['config'][11]['default'] = array( 'advlink' , 'advhr' , 'emotions' , 'insertdatetime' , 'preview' , 'imgmanager' , 'searchreplace' , 'xquotecode' );
$modversion['config'][11]['options'] = $plugins_array;
$modversion['config'][11]['category'] = 'tiny_settings';

$modversion['config'][12]['name'] = 'tinybuts1';
$modversion['config'][12]['title'] = '_MI_TINYBUTS1';
$modversion['config'][12]['description'] = '_MI_TINYBUTS1DESC';
$modversion['config'][12]['formtype'] = 'texbox';
$modversion['config'][12]['valuetype'] = 'text';
$modversion['config'][12]['default'] = "fontselect , fontsizeselect , formatselect , styleselect";
$modversion['config'][12]['category'] = 'tiny_settings';

$modversion['config'][13]['name'] = 'tinybuts2';
$modversion['config'][13]['title'] = '_MI_TINYBUTS2';
$modversion['config'][13]['description'] = '_MI_TINYBUTS2DESC';
$modversion['config'][13]['formtype'] = 'textbox';
$modversion['config'][13]['valuetype'] = 'text';
$modversion['config'][13]['default'] = "bold , italic , underline , strikethrough , separator , justifyleft , justifycenter , justifyright , justifyfull , separator , cut , copy , paste , bullist , numlist , indent , outdent";
$modversion['config'][13]['category'] = 'tiny_settings';

$modversion['config'][14]['name'] = 'tinybuts3';
$modversion['config'][14]['title'] = '_MI_TINYBUTS3';
$modversion['config'][14]['description'] = '_MI_TINYBUTS3DESC';
$modversion['config'][14]['formtype'] = 'textbox';
$modversion['config'][14]['valuetype'] = 'text';
$modversion['config'][14]['default'] = "undo , redo , separator , sub , sup , forecolor , backcolor , removeformat , separator , link , unlink , anchor , image , cleanup , hr , charmap , separator , visualaid , code , help , xquote , xcode";
$modversion['config'][14]['category'] = 'tiny_settings';

$modversion['config'][15]['name'] = 'tinyplugdate';
$modversion['config'][15]['title'] = '_MI_TINYPLUGDATE';
$modversion['config'][15]['description'] = '_MI_TINYPLUGDATEDESC';
$modversion['config'][15]['formtype'] = 'texbox';
$modversion['config'][15]['valuetype'] = 'text';
$modversion['config'][15]['default'] = "%Y-%m-%d";
$modversion['config'][15]['category'] = 'tiny_settings';

$modversion['config'][16]['name'] = 'tinyplugtime';
$modversion['config'][16]['title'] = '_MI_TINYPLUGTIME';
$modversion['config'][16]['description'] = '_MI_TINYPLUGTIMEDESC';
$modversion['config'][16]['formtype'] = 'texbox';
$modversion['config'][16]['valuetype'] = 'text';
$modversion['config'][16]['default'] = "%H:%M:%S";
$modversion['config'][16]['category'] = 'tiny_settings';

$modversion['config'][17]['name'] = 'tinymgruploads';
$modversion['config'][17]['title'] = '_MI_TINYMGRUPLOADS';
$modversion['config'][17]['description'] = '_MI_TINYMGRUPLOADSDESC';
$modversion['config'][17]['formtype'] = 'texbox';
$modversion['config'][17]['valuetype'] = 'text';
$modversion['config'][17]['default'] = "/uploads";
$modversion['config'][17]['category'] = 'tiny_manager';

$modversion['config'][18]['name'] = 'tinymgrpersdir';
$modversion['config'][18]['title'] = '_MI_TINYMGRPERSDIR';
$modversion['config'][18]['description'] = '_MI_TINYMGRPERSDIRDESC';
$modversion['config'][18]['formtype'] = 'yesno';
$modversion['config'][18]['valuetype'] = 'int';
$modversion['config'][18]['default'] = 0;
$modversion['config'][18]['category'] = 'tiny_manager';

$modversion['config'][19]['name'] = 'tinymgrquota';
$modversion['config'][19]['title'] = '_MI_TINYMGRQUOTA';
$modversion['config'][19]['description'] = '_MI_TINYMGRQUOTADESC';
$modversion['config'][19]['formtype'] = 'texbox';
$modversion['config'][19]['valuetype'] = 'int';
$modversion['config'][19]['default'] = 5120000;
$modversion['config'][19]['category'] = 'tiny_manager';

$modversion['config'][20]['name'] = 'tinymgrimglib';
$modversion['config'][20]['title'] = '_MI_TINYMGRIMGLIB';
$modversion['config'][20]['description'] = '_MI_TINYMGRPIMGLIBDESC';
$modversion['config'][20]['formtype'] = 'select';
$modversion['config'][20]['valuetype'] = 'text';
$modversion['config'][20]['default'] = "GD";
$modversion['config'][20]['options'] = array('_MI_TINYMGRIMGLIBGD' => 'GD','_MI_TINYMGRIMGLIBIM' => 'IM','_MI_TINYMGRIMGLIBNET' => 'NetPBM');
$modversion['config'][20]['category'] = 'tiny_manager';

$modversion['config'][21]['name'] = 'tinymgrimglibpath';
$modversion['config'][21]['title'] = '_MI_TINYMGRIMGLIBPATH';
$modversion['config'][21]['description'] = '_MI_TINYMGRIMGLIBPATHDESC';
$modversion['config'][21]['formtype'] = 'texbox';
$modversion['config'][21]['valuetype'] = 'text';
$modversion['config'][21]['default'] = "/path/to/IM/or/NetPBM";
$modversion['config'][21]['category'] = 'tiny_manager';

$modversion['config'][22]['name'] = 'tinymgrthuwidth';
$modversion['config'][22]['title'] = '_MI_TINYMGRTHUWIDTH';
$modversion['config'][22]['description'] = '_MI_TINYMGRTHUWIDTHDESC';
$modversion['config'][22]['formtype'] = 'texbox';
$modversion['config'][22]['valuetype'] = 'int';
$modversion['config'][22]['default'] = 96;
$modversion['config'][22]['category'] = 'tiny_manager';

$modversion['config'][23]['name'] = 'tinymgrthuheight';
$modversion['config'][23]['title'] = '_MI_TINYMGRTHUHEIGHT';
$modversion['config'][23]['description'] = '_MI_TINYMGRTHUHEIGHTDESC';
$modversion['config'][23]['formtype'] = 'texbox';
$modversion['config'][23]['valuetype'] = 'int';
$modversion['config'][23]['default'] = 96;
$modversion['config'][23]['category'] = 'tiny_manager';

$modversion['config'][24]['name'] = 'tinymgrthupref';
$modversion['config'][24]['title'] = '_MI_TINYMGRTHUPREF';
$modversion['config'][24]['description'] = '_MI_TINYMGRTHUPREFDESC';
$modversion['config'][24]['formtype'] = 'texbox';
$modversion['config'][24]['valuetype'] = 'text';
$modversion['config'][24]['default'] = ".thumb_";
$modversion['config'][24]['category'] = 'tiny_manager';

$modversion['config'][25]['name'] = 'tinymgrthudir';
$modversion['config'][25]['title'] = '_MI_TINYMGRTHUDIR';
$modversion['config'][25]['description'] = '_MI_TINYMGRTHUDIRDESC';
$modversion['config'][25]['formtype'] = 'texbox';
$modversion['config'][25]['valuetype'] = 'text';
$modversion['config'][25]['default'] = ".thumbs";
$modversion['config'][25]['category'] = 'tiny_manager';

$modversion['config'][26]['name'] = 'tinymgrvalidimg';
$modversion['config'][26]['title'] = '_MI_TINYMGRVALIDIMG';
$modversion['config'][26]['description'] = '_MI_TINYMGRVALIDIMGDESC';
$modversion['config'][26]['formtype'] = 'yesno';
$modversion['config'][26]['valuetype'] = 'int';
$modversion['config'][26]['default'] = 1;
$modversion['config'][26]['category'] = 'tiny_manager';

$modversion['config'][27]['name'] = 'tinymgrdefthumb';
$modversion['config'][27]['title'] = '_MI_TINYMGRDEFTHUMB';
$modversion['config'][27]['description'] = '_MI_TINYMGRDEFTHUMBDESC';
$modversion['config'][27]['formtype'] = 'texbox';
$modversion['config'][27]['valuetype'] = 'text';
$modversion['config'][27]['default'] = "img/default.gif";
$modversion['config'][27]['category'] = 'tiny_manager';

$modversion['config'][28]['name'] = 'tinymgrresqual';
$modversion['config'][28]['title'] = '_MI_TINYMGRRESQUAL';
$modversion['config'][28]['description'] = '_MI_TINYMGRRESQUALDESC';
$modversion['config'][28]['formtype'] = 'texbox';
$modversion['config'][28]['valuetype'] = 'int';
$modversion['config'][28]['default'] = 100;
$modversion['config'][28]['category'] = 'tiny_manager';

$modversion['config'][29]['name'] = 'tinymgrmaxwidth';
$modversion['config'][29]['title'] = '_MI_TINYMGRMAXWIDTH';
$modversion['config'][29]['description'] = '_MI_TINYMGRMAXWIDTHDESC';
$modversion['config'][29]['formtype'] = 'texbox';
$modversion['config'][29]['valuetype'] = 'int';
$modversion['config'][29]['default'] = 500;
$modversion['config'][29]['category'] = 'tiny_manager';

$modversion['config'][30]['name'] = 'tinymgrmaxheight';
$modversion['config'][30]['title'] = '_MI_TINYMGRMAXHEIGHT';
$modversion['config'][30]['description'] = '_MI_TINYMGRMAXHEIGHTDESC';
$modversion['config'][30]['formtype'] = 'texbox';
$modversion['config'][30]['valuetype'] = 'int';
$modversion['config'][30]['default'] = 500;
$modversion['config'][30]['category'] = 'tiny_manager';

$modversion['config'][31]['name'] = 'tinymgrtemppref';
$modversion['config'][31]['title'] = '_MI_TINYMGRTEMPPREF';
$modversion['config'][31]['description'] = '_MI_TINYMGRTEMPREFDESC';
$modversion['config'][31]['formtype'] = 'texbox';
$modversion['config'][31]['valuetype'] = 'text';
$modversion['config'][31]['default'] = ".editor_";
$modversion['config'][31]['category'] = 'tiny_manager';

$modversion['config'][32]['name'] = 'tinymgrdircreat';
$modversion['config'][32]['title'] = '_MI_TINYMGRDIRCREAT';
$modversion['config'][32]['description'] = '_MI_TINYMGRDIRCREATDESC';
$modversion['config'][32]['formtype'] = 'group_multi';
$modversion['config'][32]['valuetype'] = 'array';
$modversion['config'][32]['default'] = '1 2 3';
$modversion['config'][32]['category'] = 'tiny_manager';

$modversion['config'][33]['name'] = 'tinymgrdirdel';
$modversion['config'][33]['title'] = '_MI_TINYMGRDIRDEL';
$modversion['config'][33]['description'] = '_MI_TINYMGRDIRDELDESC';
$modversion['config'][33]['formtype'] = 'group_multi';
$modversion['config'][33]['valuetype'] = 'array';
$modversion['config'][33]['default'] = '1 2 3';
$modversion['config'][33]['category'] = 'tiny_manager';

$modversion['config'][34]['name'] = 'tinymgralluploads';
$modversion['config'][34]['title'] = '_MI_TINYMGRALLUPLOADS';
$modversion['config'][34]['description'] = '_MI_TINYMGRALLUPLOADSDESC';
$modversion['config'][34]['formtype'] = 'group_multi';
$modversion['config'][34]['valuetype'] = 'array';
$modversion['config'][34]['default'] = '1 2 3';
$modversion['config'][34]['category'] = 'tiny_manager';

$modversion['config'][35]['name'] = 'tinymgrimgdel';
$modversion['config'][35]['title'] = '_MI_TINYMGRIMGDEL';
$modversion['config'][35]['description'] = '_MI_TINYMGRIMGDELDESC';
$modversion['config'][35]['formtype'] = 'group_multi';
$modversion['config'][35]['valuetype'] = 'array';
$modversion['config'][35]['default'] = '1 2 3';
$modversion['config'][35]['category'] = 'tiny_manager';

$modversion['config'][36]['name'] = 'tinymgrimgedit';
$modversion['config'][36]['title'] = '_MI_TINYMGRIMGEDIT';
$modversion['config'][36]['description'] = '_MI_TINYMGRIMGEDITDESC';
$modversion['config'][36]['formtype'] = 'group_multi';
$modversion['config'][36]['valuetype'] = 'array';
$modversion['config'][36]['default'] = '1 2 3';
$modversion['config'][36]['category'] = 'tiny_manager';
*/
?>