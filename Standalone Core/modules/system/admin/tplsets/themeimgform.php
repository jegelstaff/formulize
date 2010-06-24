<?php
/**
* Administration of template sets, theme file
*
* Longer description about this page
*
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license	LICENSE.txt
* @package	Administration
* @since	XOOPS
* @author	http://www.xoops.org The XOOPS Project
* @author	modified by UnderDog <underdog@impresscms.org>
* @version	$Id: themeimgform.php 8626 2009-04-21 03:56:17Z skenow $
*/

$image_handler =& xoops_gethandler('imageset', 'imagesetimg');
$criteria = new CriteriaCompo(new Criteria('tplset_name', $tplset));
// skin image sets have reference ID 0
$criteria->add(new Criteria('imgset_refid', 0));
$imgs =& $image_handler->getObjects($criteria);
$icount = count($imgs);
if ($tplset != 'default') {
	if ($icount > 0) {
		echo '<form action="admin.php" method="post" enctype="multipart/form-data"><table width="100%" class="outer" cellspacing="1"><tr><th colspan="3">'._MD_EDITSKINIMG.'</th></tr>';
		for ($i = 0; $i < $icount; $i++) {
			echo '<tr><td rowspan="3" valign="middle" align="center" class="odd"><img src="admin.php?fct=tplsets&amp;op=showimage&amp;id='.$imgs[$i]->getVar('imgsetimg_id').'" alt="" /></td><td class="head">'._MD_IMGFILE.'</td><td class="even">'.$imgs[$i]->getVar('imgsetimg_file').'</td></tr><tr><td class="head">'._MD_IMGNEWFILE.'</td><td class="even"><input type="file" name="imgfiles['.$imgs[$i]->getVar('imgsetimg_id').']" /></td></tr><tr><td class="head">'._MD_IMGDELETE.'</td><td class="even"><input type="checkbox" name="imgdelete['.$imgs[$i]->getVar('imgsetimg_id').']" value="1" /><input type="hidden" name="imgids[]" value="'.$imgs[$i]->getVar('imgsetimg_id').'" /></td></tr>';
		}
		echo '<tr class="foot"><td colspan="3" align="center"><input type="hidden" name="tplset" value="'.$tplset.'" /><input type="hidden" name="op" value="updateimage" /><input type="hidden" name="fct" value="tplsets" /><input type="hidden" name="imgset" value="'.$imgs[0]->getVar('imgsetimg_imgset').'" /><input type="submit" name="imgsubmit" value="'._SUBMIT.'" /></td></tr></table></form>';
	}
	echo '<form action="admin.php" method="post" enctype="multipart/form-data"><table width="100%" class="outer" cellspacing="1"><tr><th colspan="3">'._MD_ADDSKINIMG.'</th></tr>';
	echo '<tr><td class="head">'._MD_IMGNEWFILE.'</td><td class="even"><input type="file" name="imgfile" /></td></tr>';
	echo '<tr><td class="head">&nbsp;</td><td class="even"><input type="hidden" name="tplset" value="'.$tplset.'" /><input type="hidden" name="op" value="addimage" /><input type="hidden" name="fct" value="tplsets" /><input type="submit" name="imgsubmit" value="'._SUBMIT.'" /><input type="hidden" name="imgset" value="';
	if ($icount > 0) {
		echo $imgs[0]->getVar('imgsetimg_imgset');
	}
	echo '" /></td></tr></table></form>';
} else {
	echo '<table width="100%" class="outer" cellspacing="1"><tr><th colspan="3">'._MD_SKINIMGS.'</th></tr>';
	for ($i = 0; $i < $icount; $i++) {
		echo '<tr><td valign="middle" align="center" class="odd"><img src="admin.php?fct=tplsets&amp;op=showimage&amp;id='.$imgs[$i]->getVar('imgsetimg_id').'" alt="" /></td><td class="head">'._MD_IMGFILE.'</td><td class="even">'.$imgs[$i]->getVar('imgsetimg_file').'</td></tr>';
	}
	echo '</table>';
}
?>