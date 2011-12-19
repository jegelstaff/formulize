<?php
/**
 * Administration of banners, mainfile
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @package		Administration
 * @subpackage	Banners
 * @version		SVN: $Id: main.php 21368 2011-03-30 00:21:29Z skenow $
 */

if (!is_object(icms::$user) 
	|| !is_object($icmsModule) 
	|| !icms::$user->isAdmin($icmsModule->getVar('mid'))) 
{
	exit('Access Denied');
}
include_once ICMS_MODULES_PATH . '/system/admin/banners/banners.php';
$allowedHTML = array('htmlcode');
/*
 * valid POST variables
 * (str) 	name
 * (int) 	cid
 * (url) 	imageurl
 * (int) 	imptotal
 * (int) 	htmlbanner
 * (html) 	htmlcode
 * (str) 	contact
 * (email) 	email
 * (str) 	login
 * (str) 	passwd
 * (str) 	extrainfo
 * (int) 	bid
 * (url) 	clickurl
 * (str) 	op
 * (int) 	impadded
 * 
 * valid GET variables
 * op:	BannersAdmin, BannersAdd, BannerAddClient, BannerFinishDelete, 
 * 		BannerFinishDelete2, BannerEdit, BannerChange, BannerClientDelete, 
 * 		BannerClientDelete2, BannerDelete, BannerDelete2, BannerClientEdit,
 * 		BannerClientChange
 * bid
 * cid
 * fct	banners
 */
/* This is an example of how to use the icms_core_DataFilter::checkVarArray method on input 
 * Specify all the get/post variables you will be expecting and their type
 * Optionally, use the options for the type for further control
 * You can leave out any variables that will be strings and then use the 3rd parameter
 * to apply the default string validation. If you set the $strict parameter to TRUE, though, 
 * you will discard any variables not explicitly found in the filter array
 */ 
$filter_post = array(
	'name'			=> 'str',
	'cid' 			=> 'int',
	'imageurl' 		=> 'url',
	'imptotal' 		=> 'int',
	'htmlbanner' 	=> 'int',
	'htmlcode' 		=> 'html',
	'contact' 		=> 'str',
	'email' 		=> array('email', 'options' => array(0, 1)),
	'login'			=> 'str',
	'passwd' 		=> 'str',
	'extrainfo' 	=> 'str',
	'bid' 			=> 'int',
	'clickurl' 		=> 'url',
	'op'			=> 'str',
	'impadded' 		=> 'int',
	'fct'			=> 'str',
);
$filter_get = array(
	'bid' => 'int',
	'cid' => 'int',
	'fct' => 'str',
	'op'  => 'str',
);

$name = $imageurl = $htmlcode = $contact = '';
$email = $login = $passwd = $extrainfo = $clickurl = $op = '';
$bid = $cid = $imptotal = $htmlbanner = $impadded = 0;

if (!empty($_POST)) { 
	$clean_POST = icms_core_DataFilter::checkVarArray($_POST, $filter_post, FALSE);
	extract($clean_POST);
}
if (!empty($_GET)) { 
	$clean_GET = icms_core_DataFilter::checkVarArray($_GET, $filter_get, FALSE);
	extract($clean_GET);
}

switch($op) {
	default:
	case 'BannersAdmin':
		BannersAdmin();
		break;

	case 'BannersAdd':
		if (!icms::$security->check()) {
			redirect_header('admin.php?fct=banners&amp;op=BannersAdmin#top', 3, implode('<br />', icms::$security->getErrors()));
		}
		if ($cid <= 0) {redirect_header('admin.php?fct=banners&amp;op=BannersAdmin#top');}
		$db =& icms_db_Factory::instance();
		$newid = $db->genId($db->prefix('banner') . '_bid_seq');
		$sql = sprintf("INSERT INTO %s (bid,
										cid,
										imptotal,
										impmade,
										clicks,
										imageurl,
										clickurl,
										date,
										htmlbanner,
										htmlcode
										) VALUES ('%d', '%d', '%d', '1', '0', %s, %s, '%d', '%d', %s)",
						$db->prefix('banner'),
						(int) $newid,
						$cid,
						$imptotal,
						$db->quoteString(icms_core_DataFilter::stripSlashesGPC($imageurl)),
						$db->quoteString(icms_core_DataFilter::stripSlashesGPC($clickurl)),
						time(),
						$htmlbanner,
						$db->quoteString(icms_core_DataFilter::stripSlashesGPC($htmlcode))
					);
		$db->query($sql);
		redirect_header('admin.php?fct=banners&amp;op=BannersAdmin#top', 1, _AM_DBUPDATED);
		break;

	case 'BannerAddClient':
		if (!icms::$security->check()) {
			redirect_header('admin.php?fct=banners&amp;op=BannersAdmin#top', 3, implode('<br />', icms::$security->getErrors()));
		}
		$db =& icms_db_Factory::instance();
		$newid = $db->genId(icms::$xoopsDB->prefix('bannerclient') . '_cid_seq');
		$sql = sprintf("INSERT INTO %s (cid, name, contact, email, login, passwd, extrainfo) VALUES ('%d', %s, %s, %s, %s, %s, %s)",
						$db->prefix("bannerclient"),
						(int) $newid,
						$db->quoteString(icms_core_DataFilter::stripSlashesGPC($name)),
						$db->quoteString(icms_core_DataFilter::stripSlashesGPC($contact)),
						$db->quoteString(icms_core_DataFilter::stripSlashesGPC($email)),
						$db->quoteString(icms_core_DataFilter::stripSlashesGPC($login)),
						$db->quoteString(icms_core_DataFilter::stripSlashesGPC($passwd)),
						$db->quoteString(icms_core_DataFilter::stripSlashesGPC($extrainfo))
					);
		$db->query($sql);
		redirect_header('admin.php?fct=banners&amp;op=BannersAdmin#top', 1, _AM_DBUPDATED);
		break;

	case 'BannerFinishDelete':
		icms_cp_header();
		icms_core_Message::confirm(array('op' => 'BannerFinishDelete2', 'bid' => (int) $bid, 'fct' => 'banners'), 'admin.php', _AM_SUREDELE);
		icms_cp_footer();
		break;

	case 'BannerFinishDelete2':
		if ($bid <= 0 | !icms::$security->check()) {
			redirect_header('admin.php?fct=banners&amp;op=BannersAdmin#top', 3, implode('<br />', icms::$security->getErrors()));
		}
		$db =& icms_db_Factory::instance();
		$sql = sprintf('DELETE FROM %s WHERE bid = %u', $db->prefix('bannerfinish'), $bid);
		$db->query($sql);
		redirect_header('admin.php?fct=banners&amp;op=BannersAdmin#top', 1, _AM_DBUPDATED);
		break;

	case 'BannerDelete':
		if ($bid > 0) {BannerDelete($bid);}
		break;

	case 'BannerDelete2':
		if ($bid <= 0 | !icms::$security->check()) {
			redirect_header('admin.php?fct=banners&amp;op=BannersAdmin#top', 3, implode('<br />', icms::$security->getErrors()));
		}
		$db =& icms_db_Factory::instance();
		$sql = sprintf('DELETE FROM %s WHERE bid = %u', $db->prefix('banner'), $bid);
		$db->query($sql);
		redirect_header('admin.php?fct=banners&amp;op=BannersAdmin#top', 1, _AM_DBUPDATED);
		break;

	case 'BannerEdit':
		if ($bid > 0) {BannerEdit($bid);}
		break;

	case 'BannerChange':
		if (($cid <= 0 || $bid <= 0) | !icms::$security->check()) {
			redirect_header('admin.php?fct=banners&amp;op=BannersAdmin#top', 3, implode('<br />', icms::$security->getErrors()));
		}
		$db =& icms_db_Factory::instance();
		$sql = sprintf("UPDATE %s SET cid = '%d',
										imptotal = '%d',
										imageurl = %s,
										clickurl = %s,
										htmlbanner = '%d',
										htmlcode = %s WHERE bid = '%d'",
						$db->prefix('banner'),
						$cid,
						$imptotal + $impadded,
						$db->quoteString(icms_core_DataFilter::stripSlashesGPC($imageurl)),
						$db->quoteString(icms_core_DataFilter::stripSlashesGPC($clickurl)),
						$htmlbanner,
						$db->quoteString(icms_core_DataFilter::stripSlashesGPC($htmlcode)),
						$bid
					);
		$db->query($sql);
		redirect_header('admin.php?fct=banners&amp;op=BannersAdmin#top', 1, _AM_DBUPDATED);
		break;

	case 'BannerClientDelete':
		if ($cid > 0) {BannerClientDelete($cid);}
		break;

	case 'BannerClientDelete2':
		$db =& icms_db_Factory::instance();
		if ($cid <= 0 | !icms::$security->check()) {
			redirect_header('admin.php?fct=banners&amp;op=BannersAdmin#top', 3, implode('<br />', icms::$security->getErrors()));
		}
		$sql = sprintf("DELETE FROM %s WHERE cid = '%u'", $db->prefix('banner'), $cid);
		$db->query($sql);
		$sql = sprintf("DELETE FROM %s WHERE cid = '%u'", $db->prefix('bannerclient'), $cid);
		$db->query($sql);
		redirect_header('admin.php?fct=banners&amp;op=BannersAdmin#top', 1, _AM_DBUPDATED);
		break;

	case 'BannerClientEdit':
		if ($cid > 0) {BannerClientEdit($cid);}
		break;

	case 'BannerClientChange':
		if ($cid <= 0 | !icms::$security->check()) {
			redirect_header('admin.php?fct=banners&amp;op=BannersAdmin#top', 3, implode('<br />', icms::$security->getErrors()));
		}
		$db =& icms_db_Factory::instance();
		$sql = sprintf("UPDATE %s SET name = %s, contact = %s, email = %s, login = %s, passwd = %s, extrainfo = %s WHERE cid = '%d'",
			$db->prefix("bannerclient"),
			$db->quoteString(icms_core_DataFilter::stripSlashesGPC($name)),
			$db->quoteString(icms_core_DataFilter::stripSlashesGPC($contact)),
			$db->quoteString(icms_core_DataFilter::stripSlashesGPC($email)),
			$db->quoteString(icms_core_DataFilter::stripSlashesGPC($login)),
			$db->quoteString(icms_core_DataFilter::stripSlashesGPC($passwd)),
			$db->quoteString(icms_core_DataFilter::stripSlashesGPC($extrainfo)),
			$cid
		);
		$db->query($sql);
		redirect_header('admin.php?fct=banners&amp;op=BannersAdmin#top', 1, _AM_DBUPDATED);
		break;

}

