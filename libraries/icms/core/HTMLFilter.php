<?php
/**
 * Class to Clean & Filter HTML for various uses.
 * Class uses external HTML Purifier for filtering.
 *
 * @category	ICMS
 * @package		Core
 * @since		1.3
 * @author		vaughan montgomery (vaughan@impresscms.org)
 * @author		ImpressCMS Project
 * @copyright	(c) 2007-2010 The ImpressCMS Project - www.impresscms.org
 * @version		$Id: HTMLFilter.php 20729 2011-01-27 22:23:00Z m0nty_ $
**/
/**
 *
 * HTML Purifier filters
 *
 * @category	ICMS
 * @package		Core
 *
 */
class icms_core_HTMLFilter extends icms_core_DataFilter {

	/**
	 * variable used by HTML Filter Library
	 **/
	public $purifier;

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * Access the only instance of this class
	 * @return      object
	 * @static      $instance
	 * @staticvar   object
	 **/
	public static function getInstance() {
		static $instance;
		if (!isset($instance)) {
			$instance = new self();
		}
		return $instance;
	}

// ----- Public Functions -----

	/**
	 * Gets the selected HTML Filter & filters the content
	 * @param    string  $html    input to be cleaned
	 * @TODO	allow the webmasters to select which HTML Filter they want to use such as
	 *			HTMLPurifier, HTMLLawed etc, for now we just have HTMLPurifier.
	 * @return   string
	 **/
	public function filterHTML($html) {
		$icmsConfigPurifier = icms::$config->getConfigsByCat(ICMS_CONF_PURIFIER);
		if ($icmsConfigPurifier['enable_purifier'] !== 0) {
			ICMS_PLUGINS_PATH;
			require_once ICMS_LIBRARIES_PATH . '/htmlpurifier/HTMLPurifier.standalone.php';
			require_once ICMS_LIBRARIES_PATH . '/htmlpurifier/HTMLPurifier.autoload.php';
			if ($icmsConfigPurifier['purifier_Filter_ExtractStyleBlocks'] !== 0) {
				require_once ICMS_PLUGINS_PATH . '/csstidy/class.csstidy.php';
			}
			// get the Config Data
			$icmsPurifyConf = self::getHTMLFilterConfig();
			// uncomment for specific config debug info
			//parent::filterDebugInfo('icmsPurifyConf', $icmsPurifyConf);

			$purifier = new HTMLPurifier($icmsPurifyConf);
			$html = $purifier->purify($html);
		}
		return $html;
	}

// ----- Private Functions -----

	/*
	 * Get list of current custom Filters & return them as objects in array
	 * Custom Filters are located in libraries/htmlpurifier/standalone/HTMLPurifier/Filter/
	 *
	 * @return	object	array list of filter objects
	 */
	private function getCustomFilterList() {
		$dirPath = ICMS_LIBRARIES_PATH . '/htmlpurifier/standalone/HTMLPurifier/Filter/';
		$icmsConfigPurifier = icms::$config->getConfigsByCat(ICMS_CONF_PURIFIER);
		if ($icmsConfigPurifier['purifier_Filter_AllowCustom'] !== 0) {
			$filterList = array();

			$fileList = icms_core_Filesystem::getFileList($dirPath, '', array('php'), true);
			unset($fileList['ExtractStyleBlocks.php'], $fileList['YouTube.php']);
			$fileList = array_values($fileList);

			foreach ($fileList as &$val) {
				$val = "HTMLPurifier_Filter_".substr($val, 0,strrpos($val,'.'));
				$newObject = new $val;
				$filterList[] = $newObject;
			}
		} else {
			$filterList = '';
		}

		return $filterList;
	}
	
	/**
	 * Gets Custom Purifier configurations ** this function will improve in time **
	 * @return  array    $icmsPurifierConf
	 **/
	protected function getHTMLFilterConfig() {
		$icmsConfigPurifier = icms::$config->getConfigsByCat(ICMS_CONF_PURIFIER);

		$icmsPurifierConf = array(
            'HTML.DefinitionID' => $icmsConfigPurifier['purifier_HTML_DefinitionID'],
            'HTML.DefinitionRev' => $icmsConfigPurifier['purifier_HTML_DefinitionRev'],
            'HTML.Doctype' => $icmsConfigPurifier['purifier_HTML_Doctype'],
            'HTML.AllowedElements' => $icmsConfigPurifier['purifier_HTML_AllowedElements'],
            'HTML.AllowedAttributes' => $icmsConfigPurifier['purifier_HTML_AllowedAttributes'],
            'HTML.ForbiddenElements' => $icmsConfigPurifier['purifier_HTML_ForbiddenElements'],
            'HTML.ForbiddenAttributes' => $icmsConfigPurifier['purifier_HTML_ForbiddenAttributes'],
            'HTML.MaxImgLength' => $icmsConfigPurifier['purifier_HTML_MaxImgLength'],
            'HTML.TidyLevel' => $icmsConfigPurifier['purifier_HTML_TidyLevel'],
            'HTML.SafeEmbed' => $icmsConfigPurifier['purifier_HTML_SafeEmbed'],
            'HTML.SafeObject' => $icmsConfigPurifier['purifier_HTML_SafeObject'],
            'HTML.Attr.Name.UseCDATA' => $icmsConfigPurifier['purifier_HTML_AttrNameUseCDATA'],
			'HTML.FlashAllowFullScreen' => $icmsConfigPurifier['purifier_HTML_FlashAllowFullScreen'],
            'Output.FlashCompat' => $icmsConfigPurifier['purifier_Output_FlashCompat'],
            'CSS.DefinitionRev' => $icmsConfigPurifier['purifier_CSS_DefinitionRev'],
            'CSS.AllowImportant' => $icmsConfigPurifier['purifier_CSS_AllowImportant'],
            'CSS.AllowTricky' => $icmsConfigPurifier['purifier_CSS_AllowTricky'],
            'CSS.AllowedProperties' => $icmsConfigPurifier['purifier_CSS_AllowedProperties'],
            'CSS.MaxImgLength' => $icmsConfigPurifier['purifier_CSS_MaxImgLength'],
            'CSS.Proprietary' => $icmsConfigPurifier['purifier_CSS_Proprietary'],
            'AutoFormat.AutoParagraph' => $icmsConfigPurifier['purifier_AutoFormat_AutoParagraph'],
            'AutoFormat.DisplayLinkURI' => $icmsConfigPurifier['purifier_AutoFormat_DisplayLinkURI'],
            'AutoFormat.Linkify' => $icmsConfigPurifier['purifier_AutoFormat_Linkify'],
            'AutoFormat.PurifierLinkify' => $icmsConfigPurifier['purifier_AutoFormat_PurifierLinkify'],
            'AutoFormat.Custom' => $icmsConfigPurifier['purifier_AutoFormat_Custom'],
            'AutoFormat.RemoveEmpty' => $icmsConfigPurifier['purifier_AutoFormat_RemoveEmpty'],
            'AutoFormat.RemoveEmpty.RemoveNbsp' => $icmsConfigPurifier['purifier_AutoFormat_RemoveEmptyNbsp'],
            'AutoFormat.RemoveEmpty.RemoveNbsp.Exceptions' => $icmsConfigPurifier['purifier_AutoFormat_RemoveEmptyNbspExceptions'],
            'Core.EscapeNonASCIICharacters' => $icmsConfigPurifier['purifier_Core_EscapeNonASCIICharacters'],
            'Core.HiddenElements' => $icmsConfigPurifier['purifier_Core_HiddenElements'],
			'Core.NormalizeNewlines' => $icmsConfigPurifier['purifier_Core_NormalizeNewlines'],
            'Core.RemoveInvalidImg' => $icmsConfigPurifier['purifier_Core_RemoveInvalidImg'],
            'Core.Encoding' => _CHARSET,
            'Cache.DefinitionImpl' => 'Serializer',
            'Cache.SerializerPath' => ICMS_TRUST_PATH . '/cache/htmlpurifier',
            'URI.Host' => $icmsConfigPurifier['purifier_URI_Host'],
            'URI.Base' => $icmsConfigPurifier['purifier_URI_Base'],
            'URI.Disable' => $icmsConfigPurifier['purifier_URI_Disable'],
            'URI.DisableExternal' => $icmsConfigPurifier['purifier_URI_DisableExternal'],
            'URI.DisableExternalResources' => $icmsConfigPurifier['purifier_URI_DisableExternalResources'],
            'URI.DisableResources' => $icmsConfigPurifier['purifier_URI_DisableResources'],
            'URI.MakeAbsolute' => $icmsConfigPurifier['purifier_URI_MakeAbsolute'],
            'URI.HostBlacklist' => $icmsConfigPurifier['purifier_URI_HostBlacklist'],
            'URI.AllowedSchemes' => $icmsConfigPurifier['purifier_URI_AllowedSchemes'],
            'URI.DefinitionID' => $icmsConfigPurifier['purifier_URI_DefinitionID'],
            'URI.DefinitionRev' => $icmsConfigPurifier['purifier_URI_DefinitionRev'],
            'URI.AllowedSchemes' => $icmsConfigPurifier['purifier_URI_AllowedSchemes'],
            'Attr.AllowedFrameTargets' => $icmsConfigPurifier['purifier_Attr_AllowedFrameTargets'],
            'Attr.AllowedRel' => $icmsConfigPurifier['purifier_Attr_AllowedRel'],
            'Attr.AllowedClasses' => $icmsConfigPurifier['purifier_Attr_AllowedClasses'],
            'Attr.ForbiddenClasses' => $icmsConfigPurifier['purifier_Attr_ForbiddenClasses'],
            'Attr.DefaultInvalidImage' => $icmsConfigPurifier['purifier_Attr_DefaultInvalidImage'],
            'Attr.DefaultInvalidImageAlt' => $icmsConfigPurifier['purifier_Attr_DefaultInvalidImageAlt'],
            'Attr.DefaultImageAlt' => $icmsConfigPurifier['purifier_Attr_DefaultImageAlt'],
            'Attr.ClassUseCDATA' => $icmsConfigPurifier['purifier_Attr_ClassUseCDATA'],
            'Attr.IDPrefix' => $icmsConfigPurifier['purifier_Attr_IDPrefix'],
            'Attr.EnableID' => $icmsConfigPurifier['purifier_Attr_EnableID'],
            'Attr.IDPrefixLocal' => $icmsConfigPurifier['purifier_Attr_IDPrefixLocal'],
            'Attr.IDBlacklist' => $icmsConfigPurifier['purifier_Attr_IDBlacklist'],
            'Filter.ExtractStyleBlocks.Escaping' => $icmsConfigPurifier['purifier_Filter_ExtractStyleBlocks_Escaping'],
            'Filter.ExtractStyleBlocks.Scope' => $icmsConfigPurifier['purifier_Filter_ExtractStyleBlocks_Scope'],
            'Filter.ExtractStyleBlocks' => $icmsConfigPurifier['purifier_Filter_ExtractStyleBlocks'],
            'Filter.YouTube' => $icmsConfigPurifier['purifier_Filter_YouTube'],
            'Filter.Custom' => self::getCustomFilterList(),
		);
		return parent::cleanArray($icmsPurifierConf);
	}
}