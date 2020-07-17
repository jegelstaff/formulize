<?php
if (file_exists('../../../../../../../mainfile.php')) include_once '../../../../../../../mainfile.php';
if (file_exists('../../../../../../mainfile.php')) include_once '../../../../../../mainfile.php';
if (file_exists('../../../../../mainfile.php')) include_once '../../../../../mainfile.php';
if (file_exists('../../../../mainfile.php')) include_once '../../../../mainfile.php';
if (file_exists('../../../mainfile.php')) include_once '../../../mainfile.php';
if (file_exists('../../mainfile.php')) include_once '../../mainfile.php';
if (file_exists('../mainfile.php')) include_once '../mainfile.php';
if (!defined('ICMS_ROOT_PATH')) die("ImpressCMS root path not defined");


//only site users can access this file or if multilanguage is enabled
        //$easiestml_exist = false;
        $easiestml_exist = ($icmsConfigMultilang['ml_enable'] == '1' && defined('EASIESTML_LANGS') && defined('EASIESTML_LANGNAMES'));
if (is_object(icms::$user) && $easiestml_exist) {
    function langDropdown()
    {
        // check THE EASIEST MULTILANGUAGE HACK
        $easiestml_exist = false;
        $easiestml_exist = ($icmsConfigMultilang['ml_enable'] == '1' && defined('EASIESTML_LANGS') && defined('EASIESTML_LANGNAMES'));


        // if "THE EASIEST MULTILANGUAGE HACK" by GIJOE is applied... (The hack can be found at http://www.peak.ne.jp/xoops/)
        if ($easiestml_exist) {
            $easiestml_langs = explode( ',' , EASIESTML_LANGS ) ;
            $langnames = explode( ',' , EASIESTML_LANGNAMES ) ;
    
            $lang_options = '' ;
    
            foreach ($easiestml_langs as $l => $lang )
                $lang_options .= '<option value="'.$lang.'">'.$langnames[$l].'</option>' ;

            $javascript = "onChange=\"document.forms[0].langfield.value = this.value;\"";
            echo "<select name=\"mlanguages\" ".$javascript." style=\"width:200px\">";
            echo "<option value=\" selected\">{#icmsmlcontent_dlg.sellang}</option>";
            echo "".$lang_options."";
            echo "</select>";
        // if "Xlanguage" module is installed...
        } else {
            $javascript = "onChange=\"document.forms[0].langfield.value = this.value;\"";
            echo "<input type=\"text\" size=\"2\" name=\"mlanguages\" ".$javascript." //>";
        }
    }
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>{#icmsmlcontent_dlg.title}</title>
<script type="text/javascript" src="../../tiny_mce_popup.js"></script>
<script type="text/javascript" src="js/icmsmlcontent.js"></script>
</head>

<body>
<form onsubmit="IcmsmlcontentDialog.insertMLC();return false;" action="#">
<input type="hidden" name="langfield" id="langfield" value="" />
<table border="0" cellpadding="0" cellspacing="0" width="100%">
<tr>
    <td align="center" valign="middle">
    <table border="0" cellpadding="4" cellspacing="0">
    <tr><td class="title">{#icmsmlcontent_dlg.subtitle}</td></tr>
    <tr><td class="title"><?php langDropdown(); ?></td></tr>
    <tr><td nowrap="nowrap"><textarea name="mltext" type="text" id="mltext" value="" style="width: 370px;height:220px; vertical-align: middle;"></textarea></td></tr>
    <tr><td align="right">
        <input type="button" name="insert" id="insert" value="{#insert}" onclick="IcmsmlcontentDialog.insertMLC();" />
        <input type="button" name="cancel" id="cancel" value="{#cancel}" onclick="tinyMCEPopup.close();" />
    </td></tr>
    </table>
    </td>
</tr>
</table>
</form>
</body>
</html><?php
} else {
    die(_NOPERM);
}
?>