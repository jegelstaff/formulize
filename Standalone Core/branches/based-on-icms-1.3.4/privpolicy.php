<?php
/**
 * Privacy policy display page
 *
 * This page displays the privacy policy of the site
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package		core
 * @since		1.0
 * @author		m0nty_
 * @version		$Id: privpolicy.php 11072 2011-03-14 15:52:14Z m0nty_ $
 */

$xoopsOption['pagetype'] = 'privpolicy';
include 'mainfile.php';
if ($icmsConfigUser['priv_dpolicy'] == false) {
	redirect_header('index.php', 2, _US_NOPERMISS);
}

$xoopsOption['template_main'] = 'system_privpolicy.html';
include ICMS_ROOT_PATH.'/header.php';

$xoopsTpl->assign('priv_poltype', 'page');
$priv = str_replace('{X_SITEURL}', ICMS_URL.'/', $icmsConfigUser['priv_policy']);
$priv = str_replace('{X_SITENAME}', $icmsConfig['sitename'], $priv);
$priv = icms_core_DataFilter::checkVar($priv, 'html', 'output');
$xoopsTpl->assign('priv_policy', $priv);
$xoopsTpl->assign('lang_privacy_policy', _PRV_PRIVACY_POLICY);

include ICMS_ROOT_PATH.'/footer.php';