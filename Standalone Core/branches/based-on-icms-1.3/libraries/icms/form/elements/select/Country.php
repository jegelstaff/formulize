<?php
/**
 * Creates a form attribute which is able to select a country
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @category	ICMS
 * @package		Form
 * @subpackage	Elements
 * @version		$Id: Country.php 22591 2011-09-07 18:42:59Z mcdonald3072 $
 */

defined('ICMS_ROOT_PATH') or die("ImpressCMS root path not defined");

/**
 * A select field with countries
 *
 * @category	ICMS
 * @package     Form
 * @subpackage  Elements
 *
 * @author	    Kazumi Ono	<onokazu@xoops.org>
 */
class icms_form_elements_select_Country extends icms_form_elements_Select {
	/**
	 * Constructor
	 *
	 * @param	string	$caption	Caption
	 * @param	string	$name       "name" attribute
	 * @param	mixed	$value	    Pre-selected value (or array of them).
	 *                              Legal are all 2-letter country codes (in capitals).
	 * @param	int		$size	    Number or rows. "1" makes a drop-down-list
	 */
	public function __construct($caption, $name, $value = null, $size = 1) {
		parent::__construct($caption, $name, $value, $size);
		$this->addOptionArray(self::getCountryList());
	}

	/**
	 * Gets list of countries
	 *
	 * @return  array	 $country_list   list of countries
	 */
	static public function getCountryList() {
		icms_loadLanguageFile('core', 'countries');
		$country_list = array (
			""   => "-",
			"AD" => _COUNTRY_AD,
			"AE" => _COUNTRY_AE,
			"AF" => _COUNTRY_AF,
			"AG" => _COUNTRY_AG,
			"AI" => _COUNTRY_AI,
			"AL" => _COUNTRY_AL,
			"AM" => _COUNTRY_AM,
			"AN" => _COUNTRY_AN,
			"AO" => _COUNTRY_AO,
			"AQ" => _COUNTRY_AQ,
			"AR" => _COUNTRY_AR,
			"AS" => _COUNTRY_AS,
			"AT" => _COUNTRY_AT,
			"AU" => _COUNTRY_AU,
			"AW" => _COUNTRY_AW,
			"AX" => _COUNTRY_AX,
			"AZ" => _COUNTRY_AZ,
			"BA" => _COUNTRY_BA,
			"BB" => _COUNTRY_BB,
			"BD" => _COUNTRY_BD,
			"BE" => _COUNTRY_BE,
			"BF" => _COUNTRY_BF,
			"BG" => _COUNTRY_BG,
			"BH" => _COUNTRY_BH,
			"BI" => _COUNTRY_BI,
			"BJ" => _COUNTRY_BJ,
			"BL" => _COUNTRY_BL,
			"BM" => _COUNTRY_BM,
			"BN" => _COUNTRY_BN,
			"BO" => _COUNTRY_BO,
			"BQ" => _COUNTRY_BQ,
			"BR" => _COUNTRY_BR,
			"BS" => _COUNTRY_BS,
			"BT" => _COUNTRY_BT,
			"BV" => _COUNTRY_BV,
			"BW" => _COUNTRY_BW,
			"BY" => _COUNTRY_BY,
			"BZ" => _COUNTRY_BZ,
			"CA" => _COUNTRY_CA,
			"CC" => _COUNTRY_CC,
			"CD" => _COUNTRY_CD,
			"CF" => _COUNTRY_CF,
			"CG" => _COUNTRY_CG,
			"CH" => _COUNTRY_CH,
			"CI" => _COUNTRY_CI,
			"CK" => _COUNTRY_CK,
			"CL" => _COUNTRY_CL,
			"CM" => _COUNTRY_CM,
			"CN" => _COUNTRY_CN,
			"CO" => _COUNTRY_CO,
			"CR" => _COUNTRY_CR,
		//	"CS" => _COUNTRY_CS,	transitionally reserved
			"CU" => _COUNTRY_CU,
			"CV" => _COUNTRY_CV,
			"CX" => _COUNTRY_CX,
			"CY" => _COUNTRY_CY,
			"CZ" => _COUNTRY_CZ,
			"DE" => _COUNTRY_DE,
			"DJ" => _COUNTRY_DJ,
			"DK" => _COUNTRY_DK,
			"DM" => _COUNTRY_DM,
			"DO" => _COUNTRY_DO,
			"DZ" => _COUNTRY_DZ,
			"EC" => _COUNTRY_EC,
			"EE" => _COUNTRY_EE,
			"EG" => _COUNTRY_EG,
			"EH" => _COUNTRY_EH,
			"ER" => _COUNTRY_ER,
			"ES" => _COUNTRY_ES,
			"ET" => _COUNTRY_ET,
			"FI" => _COUNTRY_FI,
			"FJ" => _COUNTRY_FJ,
			"FK" => _COUNTRY_FK,
			"FM" => _COUNTRY_FM,
			"FO" => _COUNTRY_FO,
			"FR" => _COUNTRY_FR,
		//	"FX" => _COUNTRY_FX,	exceptionally reserved
			"GA" => _COUNTRY_GA,
			"GB" => _COUNTRY_GB,
			"GD" => _COUNTRY_GD,
			"GE" => _COUNTRY_GE,
			"GF" => _COUNTRY_GF,
			"GG" => _COUNTRY_GG,
			"GH" => _COUNTRY_GH,
			"GI" => _COUNTRY_GI,
			"GL" => _COUNTRY_GL,
			"GM" => _COUNTRY_GM,
			"GN" => _COUNTRY_GN,
			"GP" => _COUNTRY_GP,
			"GQ" => _COUNTRY_GQ,
			"GR" => _COUNTRY_GR,
			"GS" => _COUNTRY_GS,
			"GT" => _COUNTRY_GT,
			"GU" => _COUNTRY_GU,
			"GW" => _COUNTRY_GW,
			"GY" => _COUNTRY_GY,
			"HK" => _COUNTRY_HK,
			"HM" => _COUNTRY_HM,
			"HN" => _COUNTRY_HN,
			"HR" => _COUNTRY_HR,
			"HT" => _COUNTRY_HT,
			"HU" => _COUNTRY_HU,
			"ID" => _COUNTRY_ID,
			"IE" => _COUNTRY_IE,
			"IL" => _COUNTRY_IL,
			"IM" => _COUNTRY_IM,
			"IN" => _COUNTRY_IN,
			"IO" => _COUNTRY_IO,
			"IQ" => _COUNTRY_IQ,
			"IR" => _COUNTRY_IR,
			"IS" => _COUNTRY_IS,
			"IT" => _COUNTRY_IT,
			"JE" => _COUNTRY_JE,
			"JM" => _COUNTRY_JM,
			"JO" => _COUNTRY_JO,
			"JP" => _COUNTRY_JP,
			"KE" => _COUNTRY_KE,
			"KG" => _COUNTRY_KG,
			"KH" => _COUNTRY_KH,
			"KI" => _COUNTRY_KI,
			"KM" => _COUNTRY_KM,
			"KN" => _COUNTRY_KN,
			"KP" => _COUNTRY_KP,
			"KR" => _COUNTRY_KR,
			"KW" => _COUNTRY_KW,
			"KY" => _COUNTRY_KY,
			"KZ" => _COUNTRY_KZ,
			"LA" => _COUNTRY_LA,
			"LB" => _COUNTRY_LB,
			"LC" => _COUNTRY_LC,
			"LI" => _COUNTRY_LI,
			"LK" => _COUNTRY_LK,
			"LR" => _COUNTRY_LR,
			"LS" => _COUNTRY_LS,
			"LT" => _COUNTRY_LT,
			"LU" => _COUNTRY_LU,
			"LV" => _COUNTRY_LV,
			"LY" => _COUNTRY_LY,
			"MA" => _COUNTRY_MA,
			"MC" => _COUNTRY_MC,
			"MD" => _COUNTRY_MD,
			"ME" => _COUNTRY_ME,
			"MF" => _COUNTRY_MF,
			"MG" => _COUNTRY_MG,
			"MH" => _COUNTRY_MH,
			"MK" => _COUNTRY_MK,
			"ML" => _COUNTRY_ML,
			"MM" => _COUNTRY_MM,
			"MN" => _COUNTRY_MN,
			"MO" => _COUNTRY_MO,
			"MP" => _COUNTRY_MP,
			"MQ" => _COUNTRY_MQ,
			"MR" => _COUNTRY_MR,
			"MS" => _COUNTRY_MS,
			"MT" => _COUNTRY_MT,
			"MU" => _COUNTRY_MU,
			"MV" => _COUNTRY_MV,
			"MW" => _COUNTRY_MW,
			"MX" => _COUNTRY_MX,
			"MY" => _COUNTRY_MY,
			"MZ" => _COUNTRY_MZ,
			"NA" => _COUNTRY_NA,
			"NC" => _COUNTRY_NC,
			"NE" => _COUNTRY_NE,
			"NF" => _COUNTRY_NF,
			"NG" => _COUNTRY_NG,
			"NI" => _COUNTRY_NI,
			"NL" => _COUNTRY_NL,
			"NO" => _COUNTRY_NO,
			"NP" => _COUNTRY_NP,
			"NR" => _COUNTRY_NR,
		//	"NT" => _COUNTRY_NT,	transitionally reserved
			"NU" => _COUNTRY_NU,
			"NZ" => _COUNTRY_NZ,
			"OM" => _COUNTRY_OM,
			"PA" => _COUNTRY_PA,
			"PE" => _COUNTRY_PE,
			"PF" => _COUNTRY_PF,
			"PG" => _COUNTRY_PG,
			"PH" => _COUNTRY_PH,
			"PK" => _COUNTRY_PK,
			"PL" => _COUNTRY_PL,
			"PM" => _COUNTRY_PM,
			"PN" => _COUNTRY_PN,
			"PR" => _COUNTRY_PR,
			"PS" => _COUNTRY_PS,
			"PT" => _COUNTRY_PT,
			"PW" => _COUNTRY_PW,
			"PY" => _COUNTRY_PY,
			"QA" => _COUNTRY_QA,
			"RE" => _COUNTRY_RE,
			"RO" => _COUNTRY_RO,
			"RS" => _COUNTRY_RS,
			"RU" => _COUNTRY_RU,
			"RW" => _COUNTRY_RW,
			"SA" => _COUNTRY_SA,
			"SB" => _COUNTRY_SB,
			"SC" => _COUNTRY_SC,
			"SD" => _COUNTRY_SD,
			"SE" => _COUNTRY_SE,
			"SG" => _COUNTRY_SG,
			"SH" => _COUNTRY_SH,
			"SI" => _COUNTRY_SI,
			"SJ" => _COUNTRY_SJ,
			"SK" => _COUNTRY_SK,
			"SL" => _COUNTRY_SL,
			"SM" => _COUNTRY_SM,
			"SN" => _COUNTRY_SN,
			"SO" => _COUNTRY_SO,
			"SR" => _COUNTRY_SR,
			"SS" => _COUNTRY_SS,
			"ST" => _COUNTRY_ST,
		//	"SU" => _COUNTRY_SU,	exceptionally reserved
			"SV" => _COUNTRY_SV,
			"SX" => _COUNTRY_SX,
			"SY" => _COUNTRY_SY,
			"SZ" => _COUNTRY_SZ,
			"TC" => _COUNTRY_TC,
			"TD" => _COUNTRY_TD,
			"TF" => _COUNTRY_TF,
			"TG" => _COUNTRY_TG,
			"TH" => _COUNTRY_TH,
			"TJ" => _COUNTRY_TJ,
			"TK" => _COUNTRY_TK,
			"TL" => _COUNTRY_TL,
			"TM" => _COUNTRY_TM,
			"TN" => _COUNTRY_TN,
			"TO" => _COUNTRY_TO,
		//	"TP" => _COUNTRY_TP,	transitionally reserved
			"TR" => _COUNTRY_TR,
			"TT" => _COUNTRY_TT,
			"TV" => _COUNTRY_TV,
			"TW" => _COUNTRY_TW,
			"TZ" => _COUNTRY_TZ,
			"UA" => _COUNTRY_UA,
			"UG" => _COUNTRY_UG,
			"UK" => _COUNTRY_UK,	//  Not listed in ISO 3166
			"UM" => _COUNTRY_UM,
			"US" => _COUNTRY_US,
			"UY" => _COUNTRY_UY,
			"UZ" => _COUNTRY_UZ,
			"VA" => _COUNTRY_VA,
			"VC" => _COUNTRY_VC,
			"VE" => _COUNTRY_VE,
			"VG" => _COUNTRY_VG,
			"VI" => _COUNTRY_VI,
			"VN" => _COUNTRY_VN,
			"VU" => _COUNTRY_VU,
			"WF" => _COUNTRY_WF,
			"WS" => _COUNTRY_WS,
			"YE" => _COUNTRY_YE,
			"YT" => _COUNTRY_YT,
		//	"YU" => _COUNTRY_YU,	transitionally reserved
			"ZA" => _COUNTRY_ZA,
			"ZM" => _COUNTRY_ZM,
		//	"ZR" => _COUNTRY_ZR,	transitionally reserved
			"ZW" => _COUNTRY_ZW
		);
		asort($country_list);
		reset($country_list);
		return $country_list;
	}
	
}

