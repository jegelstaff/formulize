<?php
/**
* ImpressCMS Adsenses
*
* @copyright	The ImpressCMS Project http://www.impresscms.org/
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @package		Administration
* @since		1.2
* @author		Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
* @version		$Id$
*/

if (! defined ( "ICMS_ROOT_PATH" ))
	die ( "ImpressCMS root path not defined" );

include_once ICMS_ROOT_PATH . "/kernel/icmspersistableobject.php";

class SystemAdsense extends IcmsPersistableObject {

	public $content = false;

	function SystemAdsense(&$handler) {
		$this->IcmsPersistableObject($handler);

		$this->quickInitVar('adsenseid', XOBJ_DTYPE_INT, true);
		$this->quickInitVar('description', XOBJ_DTYPE_TXTAREA, true, _CO_ICMS_ADSENSE_DESCRIPTION, _CO_ICMS_ADSENSE_DESCRIPTION_DSC);
		$this->quickInitVar('client_id', XOBJ_DTYPE_TXTBOX, true, _CO_ICMS_ADSENSE_CLIENT_ID, _CO_ICMS_ADSENSE_CLIENT_ID_DSC);
		$this->quickInitVar('tag', XOBJ_DTYPE_TXTBOX, false, _CO_ICMS_ADSENSE_TAG, _CO_ICMS_ADSENSE_TAG_DSC);
		$this->quickInitVar('format', XOBJ_DTYPE_TXTBOX, true, _CO_ICMS_ADSENSE_FORMAT, _CO_ICMS_ADSENSE_FORMAT_DSC);
		$this->quickInitVar('border_color', XOBJ_DTYPE_TXTBOX, true, _CO_ICMS_ADSENSE_BORDER_COLOR, _CO_ICMS_ADSENSE_BORDER_COLOR_DSC);
		$this->quickInitVar('background_color', XOBJ_DTYPE_TXTBOX, true, _CO_ICMS_ADSENSE_BACKGROUND_COLOR, _CO_ICMS_ADSENSE_BORDER_COLOR_DSC);
		$this->quickInitVar('link_color', XOBJ_DTYPE_TXTBOX, true, _CO_ICMS_ADSENSE_LINK_COLOR, _CO_ICMS_ADSENSE_LINK_COLOR_DSC);
		$this->quickInitVar('url_color', XOBJ_DTYPE_TXTBOX, true, _CO_ICMS_ADSENSE_URL_COLOR, _CO_ICMS_ADSENSE_URL_COLOR_DSC);
		$this->quickInitVar('text_color', XOBJ_DTYPE_TXTBOX, true, _CO_ICMS_ADSENSE_TEXT_COLOR, _CO_ICMS_ADSENSE_TEXT_COLOR_DSC);
		$this->quickInitVar('style', XOBJ_DTYPE_TXTAREA, false, _CO_ICMS_ADSENSE_STYLE, _CO_ICMS_ADSENSE_STYLE_DSC);

		$this->setControl('format', array('handler' => 'adsense','method' => 'getFormats'));
		$this->setControl('border_color', array('name' => 'text','size' => 6,'maxlength' => 6));
		$this->setControl('background_color', array('name' => 'text','size' => 6,'maxlength' => 6));
		$this->setControl('link_color', array('name' => 'text','size' => 6,'maxlength' => 6));
		$this->setControl('url_color', array('name' => 'text','size' => 6,'maxlength' => 6));
		$this->setControl('text_color', array('name' => 'text','size' => 6,'maxlength' => 6));
	}

	function getVar($key, $format = 's') {
		if ($format == 's' && in_array ( $key, array ( ) )) {
			return call_user_func ( array ($this, $key ) );
		}
		return parent::getVar ( $key, $format );
	}

	function render() {
		global $icms_adsense_handler;
		if ($this->getVar('style', 'n') != '') {
			$ret = '<div style="' . $this->getVar('style', 'n') . '">';
		} else {
			$ret = '<div>';
		}

		$ret .= '<script type="text/javascript"><!--
google_ad_client = "' . $this->getVar('client_id', 'n') . '";
google_ad_width = ' . $icms_adsense_handler->adFormats[$this->getVar('format', 'n')]['width'] . ';
google_ad_height = ' . $icms_adsense_handler->adFormats[$this->getVar('format', 'n')]['height'] . ';
google_ad_format = "' . $this->getVar('format', 'n') . '";
google_ad_type = "text";
google_ad_channel ="";
google_color_border = "' . $this->getVar('border_color', 'n') . '";
google_color_bg = "' . $this->getVar('background_color', 'n') . '";
google_color_link = "' . $this->getVar('link_color', 'n') . '";
google_color_url = "' . $this->getVar('url_color', 'n') . '";
google_color_text = "' . $this->getVar('text_color', 'n') . '";
//--></script>
<script type="text/javascript"
  src="http://pagead2.googlesyndication.com/pagead/show_ads.js">
</script>
</div>';
		return $ret;
	}

	function getXoopsCode() {
		$ret = '[adsense]' . $this->getVar ( 'tag', 'n' ) . '[/adsense]';
		return $ret;
	}

	function getCloneLink() {
		$ret = '<a href="' . ICMS_URL . '/modules/system/admin.php?fct=adsense&amp;op=clone&amp;adsenseid=' . $this->id () . '"><img src="' . ICMS_IMAGES_SET_URL . '/actions/editcopy.png" style="vertical-align: middle;" alt="' . _CO_ICMS_CUSTOMTAG_CLONE . '" title="' . _CO_ICMS_CUSTOMTAG_CLONE . '" /></a>';
		return $ret;
	}

	function emptyString($var) {
		return (strlen ( $var ) > 0);
	}

	function generateTag() {
		$title = rawurlencode ( strtolower ( $this->getVar ( 'description', 'e' ) ) );
		$title = xoops_substr ( $title, 0, 10, '' );
		// Transformation des ponctuations
		//				 Tab	 Space	  !		"		#		%		&		'		(		)		,		/		:		;		<		=		>		?		@		[		\		]		^		{		|		}		~	   .
		$pattern = array ("/%09/", "/%20/", "/%21/", "/%22/", "/%23/", "/%25/", "/%26/", "/%27/", "/%28/", "/%29/", "/%2C/", "/%2F/", "/%3A/", "/%3B/", "/%3C/", "/%3D/", "/%3E/", "/%3F/", "/%40/", "/%5B/", "/%5C/", "/%5D/", "/%5E/", "/%7B/", "/%7C/", "/%7D/", "/%7E/", "/\./" );
		$rep_pat = array ("-", "-", "-", "-", "-", "-100", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-at-", "-", "-", "-", "-", "-", "-", "-", "-", "-" );
		$title = preg_replace ( $pattern, $rep_pat, $title );

		// Transformation des caract�res accentu�s
		//				  �		�		�		�		�		�		�		�		�		�		�		�		�		�		�		�		$pattern = array ("/%B0/", "/%E8/", "/%E9/", "/%EA/", "/%EB/", "/%E7/", "/%E0/", "/%E2/", "/%E4/", "/%EE/", "/%EF/", "/%F9/", "/%FC/", "/%FB/", "/%F4/", "/%F6/" );
		$rep_pat = array ("-", "e", "e", "e", "e", "c", "a", "a", "a", "i", "i", "u", "u", "u", "o", "o" );
		$title = preg_replace ( $pattern, $rep_pat, $title );

		$tableau = explode ( "-", $title ); // Transforme la chaine de caract�res en tableau
		$tableau = array_filter ( $tableau, array ($this, "emptyString" ) ); // Supprime les chaines vides du tableau
		$title = implode ( "-", $tableau ); // Transforme un tableau en chaine de caract�res s�par� par un tiret


		$title = $title . time ();
		$title = md5 ( $title );
		return $title;
	}

	function getAdsenseName() {
		$ret = $this->getVar ( 'description' );
		return $ret;
	}
}

class SystemAdsenseHandler extends IcmsPersistableObjectHandler {

	public $adFormats;
	public $adFormatsList;
	public $objects=false;

	function SystemAdsenseHandler($db) {
		$this->IcmsPersistableObjectHandler ( $db, 'adsense', 'adsenseid', 'tag', 'description', 'system' );
		$this->adFormats = array();
		$this->adFormatsList = array();

		$this->adFormats['728x90_as']['caption'] = '728 X 90 Leaderboard';
		$this->adFormats['728x90_as']['width'] = 728;
		$this->adFormats['728x90_as']['height'] = 90;
		$this->adFormatsList['728x90_as'] = $this->adFormats['728x90_as']['caption'];

		$this->adFormats['468x60_as']['caption'] = '468 X 60 Banner';
		$this->adFormats['468x60_as']['width'] = 468;
		$this->adFormats['468x60_as']['height'] = 60;
		$this->adFormatsList['468x60_as'] = $this->adFormats['468x60_as']['caption'];

		$this->adFormats['234x60_as']['caption'] = '234 X 60 Half Banner';
		$this->adFormats['234x60_as']['width'] = 234;
		$this->adFormats['234x60_as']['height'] = 60;
		$this->adFormatsList['234x60_as'] = $this->adFormats['234x60_as']['caption'];

		$this->adFormats['120x600_as']['caption'] = '120 X 600 Skyscraper';
		$this->adFormats['120x600_as']['width'] = 120;
		$this->adFormats['120x600_as']['height'] = 600;
		$this->adFormatsList['120x600_as'] = $this->adFormats['120x600_as']['caption'];

		$this->adFormats['160x600_as']['caption'] = '160 X 600 Wide Skyscraper';
		$this->adFormats['160x600_as']['width'] = 160;
		$this->adFormats['160x600_as']['height'] = 600;
		$this->adFormatsList['160x600_as'] = $this->adFormats['160x600_as']['caption'];

		$this->adFormats['120x240_as']['caption'] = '120 X 240 Vertical Banner';
		$this->adFormats['120x240_as']['width'] = 120;
		$this->adFormats['120x240_as']['height'] = 240;
		$this->adFormatsList['120x240_as'] = $this->adFormats['120x240_as']['caption'];

		$this->adFormats['336x280_as']['caption'] = '336 X 280 Large Rectangle';
		$this->adFormats['336x280_as']['width'] = 136;
		$this->adFormats['336x280_as']['height'] = 280;
		$this->adFormatsList['336x280_as'] = $this->adFormats['336x280_as']['caption'];

		$this->adFormats['300x250_as']['caption'] = '300 X 250 Medium Rectangle';
		$this->adFormats['300x250_as']['width'] = 300;
		$this->adFormats['300x250_as']['height'] = 250;
		$this->adFormatsList['300x250_as'] = $this->adFormats['300x250_as']['caption'];

		$this->adFormats['250x250_as']['caption'] = '250 X 250 Square';
		$this->adFormats['250x250_as']['width'] = 250;
		$this->adFormats['250x250_as']['height'] = 250;
		$this->adFormatsList['250x250_as'] = $this->adFormats['250x250_as']['caption'];

		$this->adFormats['200x200_as']['caption'] = '200 X 200 Small Square';
		$this->adFormats['200x200_as']['width'] = 200;
		$this->adFormats['200x200_as']['height'] = 200;
		$this->adFormatsList['200x200_as'] = $this->adFormats['200x200_as']['caption'];

		$this->adFormats['180x150_as']['caption'] = '180 X 150 Small Rectangle';
		$this->adFormats['180x150_as']['width'] = 180;
		$this->adFormats['180x150_as']['height'] = 150;
		$this->adFormatsList['180x150_as'] = $this->adFormats['180x150_as']['caption'];

		$this->adFormats['125x125_as']['caption'] = '125 X 125 Button';
		$this->adFormats['125x125_as']['width'] = 125;
		$this->adFormats['125x125_as']['height'] = 125;
		$this->adFormatsList['125x125_as'] = $this->adFormats['125x125_as']['caption'];
		//$this->addPermission ( 'view', _CO_ICMS_CUSTOMTAG_PERMISSION_VIEW, _CO_ICMS_CUSTOMTAG_PERMISSION_VIEW_DSC );
	}

	function getFormats() {
		return $this->adFormatsList;
	}

	function beforeSave(&$obj) {
		if ($obj->getVar('tag') == '') {
			$obj->setVar('tag', $title  = $obj->generateTag());
		}

		return true;
	}

	function getAdsensesByTag() {
		if (!$this->objects) {
			$adsensesObj = $this->getObjects(null, true);
			$ret = array();
			foreach($adsensesObj as $adsenseObj) {
				$ret[$adsenseObj->getVar('tag')] = $adsenseObj;
			}
			$this->objects = $ret;
		}
		return $this->objects;
	}
}

?>