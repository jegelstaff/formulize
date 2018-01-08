<?php
/**
 * Input validation and processing, BB code conversion, Smiley conversion
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @category	ICMS
 * @package		Core
 * @subpackage	Textsanitizer
 * @author		Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @version		SVN: $Id: Textsanitizer.php 22529 2011-09-02 19:55:40Z phoenyx $
 */

/**
 * Class to "clean up" text for various uses
 *
 * <b>Singleton</b>
 *
 * @category	ICMS
 * @package		Core
 * @subpackage	Textsanitizer
 *
 * @author		Kazumi Ono 	<onokazu@xoops.org>
 * @author		Goghs Cheng
 * @copyright	(c) 2000-2003 The Xoops Project - www.xoops.org
 */
class icms_core_Textsanitizer {
	/**
	 * @public	array
	 */
	public $displaySmileys = array();
	/**
	 * @public	array
	 */
	public $allSmileys = array();
	/**
	 *
	 */
	public $censorConf;
	/**
	 * Constructor of this class
	 * Gets allowed html tags from admin config settings
	 * <br> should not be allowed since nl2br will be used
	 * when storing data.
	 *
	 * @todo Sofar, this does nuttin' ;-)
	 **/
	public function __construct() {
	}

	/**
	 * Access the only instance of this class
	 *
	 * @return   object
	 *
	 * @static
	 * @staticvar   object
	 */
	static public function getInstance() {
		static $instance;
		if (!isset($instance)) {
			$instance = new icms_core_Textsanitizer();
		}
		return $instance;
	}

	/**
	 * Get the smileys
	 *
	 * @param	bool	$all
	 * @return   array
	 */
	public function getSmileys($all = false) {
		icms_core_Debug::setDeprecated('icms_core_DataFilter::getSmileys', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return icms_core_DataFilter::getSmileys($all);
	}

	/**
	 * Replace emoticons in the message with smiley images
	 *
	 * @param	string  $message
	 * @return   string
	 */
	public function smiley($message) {
		icms_core_Debug::setDeprecated('icms_core_DataFilter::smiley', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return icms_core_DataFilter::smiley($message);
	}

	/**
	 * Make links in the text clickable
	 *
	 * @param   string  $text
	 * @return  string
	 **/
	public function makeClickable(&$text) {
		icms_core_Debug::setDeprecated('icms_core_DataFilter::makeClickable', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return icms_core_DataFilter::makeClickable($text);
	}

	/**
	 * Replace XoopsCodes with their equivalent HTML formatting
	 *
	 * @param   string  $text
	 * @param   bool	$allowimage Allow images in the text?
	 *				  On FALSE, uses links to images.
	 * @return  string
	 **/
	public function xoopsCodeDecode(&$text, $allowimage = 1) {
		icms_core_Debug::setDeprecated('icms_core_DataFilter::codeDecode', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return icms_core_DataFilter::codeDecode($text, $allowimage);
	}

	/**
	 * Filters out invalid strings included in URL, if any
	 *
	 * @param   array  $matches
	 * @return  string
	 */
	public function _filterImgUrl($matches) {
		if ($this->checkUrlString($matches[2])) {
			return $matches[0];
		} else {
			return '';
		}
	}

	/**
	 * Checks if invalid strings are included in URL
	 *
	 * @param   string  $text
	 * @return  bool
	 */
	public function checkUrlString($text) {
		// Check control code
		if (preg_match("/[\0-\31]/", $text)) {
			return false;
		}
		// check black pattern(deprecated)
		return !preg_match("/^(javascript|vbscript|about):/i", $text);
	}

	/**
	 * Convert linebreaks to <br /> tags
	 *
	 * @param	string  $text
	 * @return   string
	 */
	public function nl2Br($text) {
		icms_core_Debug::setDeprecated('icms_core_DataFilter::nl2Br', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return icms_core_DataFilter::nl2Br($text);
	}

	/**
	 * Note: magic_quotes_gpc and magic_quotes_runtime are deprecated as of PHP5.3.0
	 *
	 * Add slashes to the text if magic_quotes_gpc is turned off.
	 *
	 * @param   string  $text
	 * @return  string
	 **/
	public function addSlashes($text) {
		icms_core_Debug::setDeprecated('icms_core_DataFilter::addSlashes', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return icms_core_DataFilter::addSlashes($text);
	}

	/**
	 * Note: magic_quotes_gpc and magic_quotes_runtime are deprecated as of PHP5.3.0
	 *
	 * if magic_quotes_gpc is on, strip back slashes
	 *
	 * @param	string  $text
	 * @return   string
	 **/
	public function stripSlashesGPC($text) {
		icms_core_Debug::setDeprecated('icms_core_DataFilter::stripSlashesGPC', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return icms_core_DataFilter::stripSlashesGPC($text);
	}

	/**
	 * for displaying data in html textbox forms
	 *
	 * @param	string  $text
	 * @return   string
	 **/
	public function htmlSpecialChars($text) {
		icms_core_Debug::setDeprecated('icms_core_DataFilter::htmlSpecialchars', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return icms_core_DataFilter::htmlSpecialChars($text);
	}

	/**
	 * Reverses {@link htmlSpecialChars()}
	 *
	 * @param   string  $text
	 * @return  string
	 **/
	static public function undoHtmlSpecialChars($text) {
		icms_core_Debug::setDeprecated('icms_core_DataFilter::undoHtmlSpecialChars', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return icms_core_DataFilter::undoHtmlSpecialChars($text);
	}

	public function icms_htmlEntities($text) {
		icms_core_Debug::setDeprecated('icms_core_DataFilter::htmlEntities', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return icms_core_DataFilter::htmlEntities($text);
	}

	/**
	 * Filters textarea form data in DB for display
	 *
	 * @param   string  $text
	 * @param   bool	$html   allow html?
	 * @param   bool	$smiley allow smileys?
	 * @param   bool	$xcode  allow xoopscode?
	 * @param   bool	$image  allow inline images?
	 * @param   bool	$br	 convert linebreaks?
	 * @return  string
	 **/
	public function displayTarea($text, $html = 0, $smiley = 1, $xcode = 1, $image = 1, $br = 1) {
		// Before this can be deprecated, the events for dispalyTarea need to be added, first
		//icms_core_Debug::setDeprecated('icms_core_DataFilter::checkVar - type = text or html, $options1 = input or output', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		icms::$preload->triggerEvent('beforeDisplayTarea', array(&$text, $html, $smiley, $xcode, $image, $br));

		if ($html != 1) {
			$text = icms_core_DataFilter::htmlSpecialChars($text);
		}

		$text = icms_core_DataFilter::codePreConv($text, $xcode);
		$text = icms_core_DataFilter::makeClickable($text);
		if ($smiley != 0) {
			$text = icms_core_DataFilter::smiley($text);
		}
		if ($xcode != 0) {
			if ($image != 0) {
				$text = icms_core_DataFilter::codeDecode($text);
			} else {
				$text = icms_core_DataFilter::codeDecode($text, 0);
			}
		}
		$config_handler = icms::handler('icms_config');
		$icmsConfigPurifier = $config_handler->getConfigsByCat(ICMS_CONF_PURIFIER);
		if ($br !== 0 || ($html !== 0 && $icmsConfigPurifier['enable_purifier'] !== 1)) {
			$text = icms_core_DataFilter::nl2Br($text);
		}
		$text = icms_core_DataFilter::codeConv($text, $xcode, $image);

		if ($html != 0 && $icmsConfigPurifier['enable_purifier'] !== 0) {
			$text = icms_core_DataFilter::checkVar($text, 'html');
		}

		// ################# Preload Trigger afterDisplayTarea ##############
		icms::$preload->triggerEvent('afterDisplayTarea', array(&$text, $html, $smiley, $xcode, $image, $br));
		return $text;
	}

	/**
	 * Filters textarea form data submitted for preview
	 *
	 * @param   string  $text
	 * @param   bool	$html   allow html?
	 * @param   bool	$smiley allow smileys?
	 * @param   bool	$xcode  allow xoopscode?
	 * @param   bool	$image  allow inline images?
	 * @param   bool	$br	 convert linebreaks?
	 * @return  string
	 **/
	public function previewTarea($text, $html = 0, $smiley = 1, $xcode = 1, $image = 1, $br = 1) {
		 /* @deprecated Use icms_core_DataFilter::checkVar, instead - the events for previewTarea need to be added, first */
		//icms_core_Debug::setDeprecated('icms_core_DataFilter::checkVar - type = text or html, $options1 = input', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		icms::$preload->triggerEvent('beforePreviewTarea', array(&$text, $html, $smiley, $xcode, $image, $br));

		$text = icms_core_DataFilter::stripSlashesGPC($text);
		if ($html != 1) {
			$text = icms_core_DataFilter::htmlSpecialChars($text);
		}

		$text = icms_core_DataFilter::codePreConv($text, $xcode);
		$text = icms_core_DataFilter::makeClickable($text);
		if ($smiley != 0) {
			$text = icms_core_DataFilter::smiley($text);
		}
		if ($xcode != 0) {
			if ($image != 0) {
				$text = icms_core_DataFilter::codeDecode($text);
			} else {
				$text = icms_core_DataFilter::codeDecode($text, 0);
			}
		}
		$config_handler = icms::handler('icms_config');
		$icmsConfigPurifier = $config_handler->getConfigsByCat(ICMS_CONF_PURIFIER);
		if ($br !== 0 || ($html !== 0 && $icmsConfigPurifier['enable_purifier'] !== 1)) {
			$text = icms_core_DataFilter::nl2Br($text);
		}
		$text = icms_core_DataFilter::codeConv($text, $xcode, $image);

		if ($html != 0 && $icmsConfigPurifier['enable_purifier'] !== 0) {
			$text = icms_core_DataFilter::checkVar($text, 'html');
		}

		icms::$preload->triggerEvent('afterPreviewTarea', array(&$text, $html, $smiley, $xcode, $image, $br));

		return $text;
	}

	/**
	 * Replaces banned words in a string with their replacements
	 *
	 * @param   string $text
	 * @return  string
	 *
	 **/
	public function censorString(&$text) {
		icms_core_Debug::setDeprecated('icms_core_DataFilter::censorString', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return icms_core_DataFilter::censorString($text);
	}

	/**#@+
	 * Sanitizing of [code] tag
	 */
	public function codePreConv($text, $xcode = 1) {
		icms_core_Debug::setDeprecated('icms_core_DataFilter::codePreConv', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return icms_core_DataFilter::codePreConv($text, $xcode);
	}

	/**
	 * Converts text to xcode
	 *
	 * @param	 string	$text	 Text to convert
	 * @param	 int	   $xcode	Is the code Xcode?
	 * @param	 int	   $image	configuration for the purifier
	 * @return	string	$text	 the converted text
	 */
	public function codeConv($text, $xcode = 1, $image = 1) {
		icms_core_Debug::setDeprecated('icms_core_DataFilter::codeConv', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return icms_core_DataFilter::codeConv($text, $xcode, $image);
	}

	/**
	 * Sanitizes decoded string
	 *
	 * @param   string	$str	  String to sanitize
	 * @param   string	$image	Is the string an image
	 * @return  string	$str	  The sanitized decoded string
	 */
	public function codeSanitizer($str, $image = 1) {
		icms_core_Debug::setDeprecated('icms_core_DataFilter::codeSanitizer', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return icms_core_DataFilter::codeSanitizer($str, $image);
	}

	##################### Deprecated Methods ######################

	/**
	 * @deprecated Use displayTarea, instead
	 * @todo Remove in version 1.4 - there are no other occurrences in the core
	 * Enter description here ...
	 * @param unknown_type $text
	 * @param unknown_type $allowhtml
	 * @param unknown_type $smiley
	 * @param unknown_type $bbcode
	 */
	function sanitizeForDisplay($text, $allowhtml = 0, $smiley = 1, $bbcode = 1) {
		icms_core_Debug::setDeprecated('icms_core_DataFilter::checkVar - type = text or html, $options1 = output', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		if ($allowhtml == 0)
		{
			$text = icms_core_DataFilter::htmlSpecialChars($text);
		} else {
			$text = icms_core_DataFilter::makeClickable($text);
		}
		if ($smiley == 1)
		{
			$text = icms_core_DataFilter::smiley($text);
		}
		if ($bbcode == 1)
		{
			$text = icms_core_DataFilter::codeDecode($text);
		}
		$text = icms_core_DataFilter::nl2Br($text);
		return $text;
	}

	/**
	 * @deprecated Use displayTarea, instead
	 * @todo	Remove in version 1.4 - there are no other occurrences in the core
	 * Enter description here ...
	 * @param $text
	 * @param $allowhtml
	 * @param $smiley
	 * @param $bbcode
	 */
	function sanitizeForPreview($text, $allowhtml = 0, $smiley = 1, $bbcode = 1) {
		icms_core_Debug::setDeprecated('icms_core_DataFilter->checkVar - type = text or html, options1 = input', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		$text = $this->oopsStripSlashesGPC($text);
		if ($allowhtml == 0)
		{
			$text = icms_core_DataFilter::htmlSpecialChars($text);
		} else {
			$text = icms_core_DataFilter::makeClickable($text);
		}
		if ($smiley == 1)
		{
			$text = icms_core_DataFilter::smiley($text);
		}
		if ($bbcode == 1)
		{
			$text = icms_core_DataFilter::codeDecode($text);
		}
		$text = icms_core_DataFilter::nl2Br($text);
		return $text;
	}

	/**
	 * @deprecated Use addSlashes, instead
	 * @todo Remove in version 1.4 - there are no other occurrences in the core
	 * Enter description here ...
	 * @param unknown_type $text
	 */
	function makeTboxData4Save($text) {
		icms_core_Debug::setDeprecated('icms_core_DataFilter::addSlashes', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return icms_core_DataFilter::addSlashes($text);
	}

	/**
	 * @deprecated Use htmlSpecialChars, instead
	 * @todo Remove this in version 1.4 - there are no other occurrences in the core
	 * Enter description here ...
	 * @param $text
	 * @param $smiley
	 */
	function makeTboxData4Show($text, $smiley=0) {
		icms_core_Debug::setDeprecated('icms_core_DataFilter::htmlSpecialChars', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return icms_core_DataFilter::htmlSpecialChars($text);
	}

	/**
	 * @deprecated Use htmlSpecialChars, instead
	 * @todo Remove this in version 1.4 - there are no other occurrences in the core
	 * Enter description here ...
	 * @param $text
	 */
	function makeTboxData4Edit($text) {
		icms_core_Debug::setDeprecated('icms_core_Datafilter::htmlSpecialChars', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return icms_core_DataFilter::htmlSpecialChars($text);
	}

	/**
	 * @deprecated Use stripSlashesGPC, htmlSpecialChars
	 * @todo Remove in version 1.4
	 * Enter description here ...
	 * @param $text
	 * @param $smiley
	 */
	function makeTboxData4Preview($text, $smiley=0) {
		icms_core_Debug::setDeprecated('icms_core_DataFilter::htmlSpecialChars and icms_core_DataFilter::stripSlashesGPC', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		$text = icms_core_DataFilter::stripSlashesGPC($text);
		return icms_core_DataFilter::htmlSpecialChars($text);
	}

	/**
	 * @deprecated Use stripSlashesGPC, htmlSpecialChars
	 * @todo 	Remove this in version 1.4
	 * Enter description here ...
	 * @param unknown_type $text
	 */
	function makeTboxData4PreviewInForm($text) {
		icms_core_Debug::setDeprecated('icms_core_DataFilter::htmlSpecialChars and icms_core_DataFilter::stripSlashesGPC', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		$text = icms_core_DataFilter::stripSlashesGPC($text);
		return icms_core_DataFilter::htmlSpecialChars($text);
	}

	/**
	 * @deprecated Use addSlashes, instead
	 * @todo Remove in version 1.4 - there are no other occurrences in the core
	 * Enter description here ...
	 * @param $text
	 */
	function makeTareaData4Save($text) {
		icms_core_Debug::setDeprecated('icms_core_DataFilter::addSlashes', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return icms_core_DataFilter::addSlashes($text);
	}

	/**
	 * @deprecated Use displayTarea, instead
	 * @todo	Remove in version 1.4 - there are no other occurences in the core
	 * Enter description here ...
	 * @param unknown_type $text
	 * @param unknown_type $html
	 * @param unknown_type $smiley
	 * @param unknown_type $xcode
	 */
	function makeTareaData4Show(&$text, $html=0, $smiley=1, $xcode=1) {
		$text = $this->displayTarea($text, $html, $smiley, $xcode);
		return $text;
	}

	/**
	 * @deprecated Use htmlSpecialChars, instead
	 * @todo Remove in version 1.4 - there are no other occurrences in the core
	 * Enter description here ...
	 * @param $text
	 */
	function makeTareaData4Edit($text) {
		icms_core_Debug::setDeprecated('icms_core_DataFilter::htmlSpecialChars', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return icms_core_DataFilter::htmlSpecialChars($text);
	}
	/**
	 * @deprecated	Use previewTarea, instead
	 * @todo		Remove this in version 1.4 - no other occurrences in the core
	 *
	 * @param unknown_type $text
	 * @param unknown_type $html
	 * @param unknown_type $smiley
	 * @param unknown_type $xcode
	 */
	function makeTareaData4Preview(&$text, $html=0, $smiley=1, $xcode=1) {
		$text = $this->previewTarea($text, $html, $smiley, $xcode);
		return $text;
	}

	/**
	 *
	 * @deprecated	icms_core_DataFilter::checkVar - type = text
	 * @todo		Remove this in version 1.4
	 * 
	 * @param str	$text
	 */
	function makeTareaData4PreviewInForm($text) {
		icms_core_Debug::setDeprecated('icms_core_DataFilter::checkVar - type = text, options1 = input', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		$text = icms_core_DataFilter::stripSlashesGPC($text);
		return icms_core_DataFilter::htmlSpecialChars($text);
	}

	/**
	 * @deprecated	Use htmlSpecialChars, instead
	 * @todo		Remove this in version 1.4 - no other occurrences in the core
	 * @param 		$text
	 */
	function makeTareaData4InsideQuotes($text) {
		icms_core_Debug::setDeprecated('icms_core_DataFilter::htmlSpecialChars', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return icms_core_DataFilter::htmlSpecialChars($text);
	}

	/**
	 * Note: magic_quotes_gpc and magic_quotes_runtime are deprecated as of PHP 5.3.0
	 *
	 * @deprecated	Use stripSlashesGPC, instead
	 * @todo 		Remove in version 1.4 - there are no other occurrences in the core
	 * Enter description here ...
	 * @param $text
	 */
	function oopsStripSlashesGPC($text) {
		icms_core_Debug::setDeprecated('icms_core_DataFilter::stripSlashesGPC', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return icms_core_DataFilter::stripSlashesGPC($text);
	}

	/**
	 * Note: magic_quotes_gpc and magic_quotes_runtime are deprecated as of PHP 5.3.0
	 *
	 * @deprecated	Use stripSlashesGPC, instead.
	 * @todo		Remove this in version 1.4 - there are no other occurrences in the core
	 * Enter description here ...
	 * @param unknown_type $text
	 */
	function oopsStripSlashesRT($text) {
		icms_core_Debug::setDeprecated('icms_core_DataFilter::stripSlashesGPC', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return icms_core_DataFilter::stripSlashesGPC($text);
	}

	/**
	 * @deprecated Use addSlashes, instead
	 * @todo Remove in version 1.4 - there are no other occurrences in the core
	 * Enter description here ...
	 * @param $text
	 */
	function oopsAddSlashes($text) {
		icms_core_Debug::setDeprecated('icms_core_DataFilter::addSlashes', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return icms_core_DataFilter::addSlashes($text);
	}

	/**
	 * @deprecated Use htmlSpecialChars, instead
	 * @todo Remove in version 1.4 - there are no other occurrences in the core
	 * Enter description here ...
	 * @param $text
	 */
	function oopsHtmlSpecialChars($text) {
		icms_core_Debug::setDeprecated('icms_core_Datafilter::htmlSpecialChars', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return icms_core_Datafilter::htmlSpecialChars($text);
	}

	/**
	 * @deprecated use nl2br, instead
	 * @todo Remove in version 1.4 - there are no other occurrences in the core
	 * Enter description here ...
	 * @param $text
	 */
	function oopsNl2Br($text) {
		icms_core_Debug::setDeprecated('icms_core_DataFilter::nl2br', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return icms_core_DataFilter::nl2br($text);
	}
	/**#@-*/
	##################### Deprecated Methods ######################

	/**
	 * This function gets allowed plugins from DB and loads them in the sanitizer
	 * @param	int	 $id			 ID of the config
	 * @param	bool	$withoptions	load the config's options now?
	 * @return	object  reference to the {@link icms_config_Item_Object}
	 */
	public function icmsCodeDecode_extended($text, $allowimage = 1) {
		icms_core_Debug::setDeprecated('icms_core_DataFilter::codeDecode_extended', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return icms_core_DataFilter::codeDecode_extended($text, $allowimage);
	}

	/**
	 * Starts HTML Purifier (from icms.htmlpurifier class)
	 *
	 * @param	 string	$name	 Name of the extension to load
	 * @return	bool
	 */
	public function icmsloadExtension($name) {
		icms_core_Debug::setDeprecated('icms_core_DataFilter::loadExtension', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return icms_core_DataFilter::loadExtension($name);
	}

	/**
	 * Executes file with a certain extension using call_user_func_array
	 *
	 * @param	 string	$name	 Name of the file to load
	 * @param	 string	$text	 Text to show if the function doesn't exist
	 * @return	array	 the return of the called function
	 */
	public function icmsExecuteExtension($name, $text) {
		icms_core_Debug::setDeprecated('icms_core_DataFilter::executeExtension', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return icms_core_DataFilter::executeExtension($name, $text);
	}

	/**
	 * Syntaxhighlight the code
	 *
	 * @param	 string	$text	 purifies (lightly) and then syntax highlights the text
	 * @return	string	$text	 the syntax highlighted text
	 */
	public function textsanitizer_syntaxhighlight(&$text) {
		icms_core_Debug::setDeprecated('icms_core_DataFilter::textsanitizer_syntaxhighlight', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return icms_core_DataFilter::textsanitizer_syntaxhighlight($text);
	}

	/**
	 * Syntaxhighlight the code using PHP highlight
	 *
	 * @param	 string	$text	 Text to highlight
	 * @return	string	$buffer   the highlighted text
	 */
	public function textsanitizer_php_highlight($text) {
		icms_core_Debug::setDeprecated('icms_core_DataFilter::textsanitizer_php_highlight', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return icms_core_DataFilter::textsanitizer_php_highlight($text);
	}

	/**
	 * Syntaxhighlight the code using Geshi highlight
	 *
	 * @param	 string	$text	 The text to highlight
	 * @return	string	$code	 the highlighted text
	 */
	public function textsanitizer_geshi_highlight($text) {
		icms_core_Debug::setDeprecated('icms_core_DataFilter::textsanitizer_geshi_highlight', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return icms_core_DataFilter::textsanitizer_geshi_highlight($text);
	}
}
