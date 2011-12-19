<?php
/**
 * Creates a form with selectable timezone
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @category	ICMS
 * @package		Form
 * @subpackage	Elements
 * @version		SVN: $Id: Timezone.php 20501 2010-12-07 17:41:03Z skenow $
 */

defined('ICMS_ROOT_PATH') or die("ImpressCMS root path not defined");

/**
 * A select box with timezones
 *
 * @category	ICMS
 * @package     Form
 * @subpackage  Elements
 * @author	    Kazumi Ono	<onokazu@xoops.org>
 */
class icms_form_elements_select_Timezone extends icms_form_elements_Select {

	/**
	 * Constructor
	 *
	 * @param	string	$caption
	 * @param	string	$name
	 * @param	mixed	$value	Pre-selected value (or array of them).
	 * 							Legal values are "-12" to "12" with some ".5"s strewn in ;-)
	 * @param	int		$size	Number of rows. "1" makes a drop-down-box.
	 */
	public function __construct($caption, $name, $value = null, $size = 1) {
		parent::__construct($caption, $name, $value, $size);
		$this->addOptionArray(self::getTimeZoneList());
	}

	/**
	 * Create an array of timezones, translated by the local language files
	 */
	static public function getTimeZoneList() {
		icms_loadLanguageFile('core', 'timezone');
		$time_zone_list = array (
			"-12" => _TZ_GMTM12,
			"-11" => _TZ_GMTM11,
			"-10" => _TZ_GMTM10,
			"-9" => _TZ_GMTM9,
			"-8" => _TZ_GMTM8,
			"-7" => _TZ_GMTM7,
			"-6" => _TZ_GMTM6,
			"-5" => _TZ_GMTM5,
			"-4" => _TZ_GMTM4,
			"-3.5" => _TZ_GMTM35,
			"-3" => _TZ_GMTM3,
			"-2" => _TZ_GMTM2,
			"-1" => _TZ_GMTM1,
			"0" => _TZ_GMT0,
			"1" => _TZ_GMTP1,
			"2" => _TZ_GMTP2,
			"3" => _TZ_GMTP3,
			"3.5" => _TZ_GMTP35,
			"4" => _TZ_GMTP4,
			"4.5" => _TZ_GMTP45,
			"5" => _TZ_GMTP5,
			"5.5" => _TZ_GMTP55,
			"6" => _TZ_GMTP6,
			"7" => _TZ_GMTP7,
			"8" => _TZ_GMTP8,
			"9" => _TZ_GMTP9,
			"9.5" => _TZ_GMTP95,
			"10" => _TZ_GMTP10,
			"11" => _TZ_GMTP11,
			"12" => _TZ_GMTP12
		);
		return $time_zone_list;
	}

}
