<?php
/**
* IcmsMetagen
*
* Containing the class to manage meta informations of IcmsPersistableObject
*
* @copyright	The ImpressCMS Project http://www.impresscms.org/
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @package		IcmsPersistableObject
* @since		1.1
* @author		marcan <marcan@impresscms.org>
* @version		$Id: icmsmetagen.php 8768 2009-05-16 22:48:26Z pesianstranger $
*/

if (!defined("ICMS_ROOT_PATH")) {
die("ImpressCMS root path not defined");
}

/**
 * Generates META tags
 * 
 * @package IcmsPersistableObject
 */
class IcmsMetagen
{
	/** @var object */
	var $_myts;
	/** @var string */
	var $_title;
	/** @var string */
	var $_original_title;
	/** @var string */
	var $_keywords;
	/** @var string */
	var $_meta_description;
	/** @var string */
	var $_categoryPath;
	/** @var string */
	var $_description;
	/** @var int */
	var $_minChar = 4;
	
	/**
	 * Constructor for IcmsMetagen
	 * 
	 * @param string $title Page title
	 * @param string $keywords List of meta keywords
	 * @param string $description Meta description
	 * @param string $categoryPath
	 *
	 */
	function IcmsMetagen($title, $keywords=false, $description=false, $categoryPath=false)
	{
		$this->_myts = MyTextSanitizer::GetInstance();
		$this->setCategoryPath($categoryPath);
		$this->setTitle($title);
		$this->setDescription($description);

		if (!$keywords) {
			$keywords = $this->createMetaKeywords();
		}

/*		$myts = MyTextSanitizer::getInstance();
		if (method_exists($myts, 'formatForML')) {
			$keywords = $myts->formatForML($keywords);
			$description = $myts->formatForML($description);
		}
*/
		$this->setKeywords($keywords);

	}

	/**
 	* Return true if the string is length > 0
 	*
 	* @author psylove
 	*
 	* @var string String to test 
	* @return boolean
 	*/
	function emptyString($var)
	{
   		return (strlen($var) > 0);
	}

	/**
	 * Create a title for the short_url field of an article
	 *
	 * @author psylove
	 *
	 * @var string $title title of the article
	 * @var string $withExt do we add an html extension or not
	 * @return string sort_url for the article
	 */
	function generateSeoTitle($title='', $withExt=true) {
	    // Transformation de la chaine en minuscule
	    // Codage de la chaine afin d'�viter les erreurs 500 en cas de caract�res impr�vus
	    $title   = rawurlencode(strtolower($title));

	    // Transformation des ponctuations
	    //                 Tab     Space      !        "        #        %        &        '        (        )        ,        /        :        ;        <        =        >        ?        @        [        \        ]        ^        {        |        }        ~       .
	    $pattern = array("/%09/", "/%20/", "/%21/", "/%22/", "/%23/", "/%25/", "/%26/", "/%27/", "/%28/", "/%29/", "/%2C/", "/%2F/", "/%3A/", "/%3B/", "/%3C/", "/%3D/", "/%3E/", "/%3F/", "/%40/", "/%5B/", "/%5C/", "/%5D/", "/%5E/", "/%7B/", "/%7C/", "/%7D/", "/%7E/", "/\./");
	    $rep_pat = array(  "-"  ,   "-"  ,   "-"  ,   "-"  ,   "-"  , "-100" ,   "-"  ,   "-"  ,   "-"  ,   "-"  ,   "-"  ,   "-"  ,  "-"   ,   "-"  ,   "-"  ,   "-"  ,  "-"   ,   "-"  , "-at-" ,   "-"  ,   "-"   ,  "-"  ,   "-"  ,   "-"  ,   "-"  ,   "-"  ,   "-"  ,   "-" );
	    $title   = preg_replace($pattern, $rep_pat, $title);

    	// Transformation des caract�res accentu�s
    	//                  �        �        �        �        �        �        �        �        �        �        �        �        �        �        �        �
    	$pattern = array("/%B0/", "/%E8/", "/%E9/", "/%EA/", "/%EB/", "/%E7/", "/%E0/", "/%E2/", "/%E4/", "/%EE/", "/%EF/", "/%F9/", "/%FC/", "/%FB/", "/%F4/", "/%F6/");
	    $rep_pat = array(  "-"  ,   "e"  ,   "e"  ,   "e"  ,   "e"  ,   "c"  ,   "a"  ,   "a"  ,   "a"  ,   "i"  ,   "i"  ,   "u"  ,   "u"  ,   "u"  ,   "o"  ,   "o"  );
    	$title   = preg_replace($pattern, $rep_pat, $title);

		$tableau = explode("-", $title); // Transforme la chaine de caract�res en tableau
		$tableau = array_filter($tableau, array($this, "emptyString")); // Supprime les chaines vides du tableau
		$title   = implode("-", $tableau); // Transforme un tableau en chaine de caract�res s�par� par un tiret

	    if (sizeof($title) > 0)
	    {
	        if ($withExt) {
	            $title .= '.html';
	        }
	        return $title;
	    }
	    else
	        return '';
	}

	/**
	 * 
	 * @param $document
	 * @return string Converted text
	 */
	function html2text($document)
	{
		return icms_html2text($document);
	}

	/**
	 * Sets the title property
	 * @param string $title
	 *
	 */
	function setTitle($title)
	{
		global $icmsModule, $icmsModuleConfig;
		$this->_title = $this->html2text($title);
		$this->_title = $this->purifyText($this->_title);
		$this->_original_title = $this->_title;

		$moduleName = $icmsModule->getVar('name');

		$titleTag = array();

		$show_mod_name_breadcrumb = isset($icmsModuleConfig['show_mod_name_breadcrumb']) ? $icmsModuleConfig['show_mod_name_breadcrumb'] : true;

		if ($moduleName && $show_mod_name_breadcrumb) {
			$titleTag['module'] = $moduleName;
		}

		if (isset($this->_title) && ($this->_title != '') && (strtoupper($this->_title) != strtoupper($moduleName))) {
			$titleTag['title'] = $this->_title;
		}

		if (isset($this->_categoryPath) && ($this->_categoryPath != '')) {
			$titleTag['category'] = $this->_categoryPath;
		}

		$ret = isset($titleTag['title']) ? $titleTag['title'] : '';

		if (isset($titleTag['category']) && $titleTag['category'] != '') {
			if ($ret != '') {
				$ret .= ' - ';
			}
			$ret .= $titleTag['category'];
		}
		if (isset($titleTag['module']) && $titleTag['module'] != '') {
			if ($ret != '') {
				$ret .= ' - ';
			}
			$ret .= $titleTag['module'];
		}
		$this->_title = $ret;
	}

	/**
	 * Sets the keyword property
	 * 
	 * @param string $keywords
	 * 
	 */
	function setKeywords($keywords)
	{
		$this->_keywords = $keywords;
	}

	/**
	 * Sets the categoryPath property
	 * 
	 * @param string $categoryPath
	 *
	 */
	function setCategoryPath($categoryPath)
	{
		$categoryPath = $this->html2text($categoryPath);
		$this->_categoryPath = $categoryPath;
	}

	/**
	 * Sets the description property
	 * @param string $description
	 * 
	 */
	function setDescription($description)
	{
		if (!$description) {
			global $icmsModuleConfig;
			if (isset($icmsModuleConfig['module_meta_description'])) {
				$description = $icmsModuleConfig['module_meta_description'];
			}
		}

		$description = $this->html2text($description);
		$description = $this->purifyText($description);

		$description = ereg_replace("([^\r\n])\r\n([^\r\n])", "\\1 \\2", $description);
		$description = ereg_replace("[\r\n]*\r\n[\r\n]*", "\r\n\r\n", $description);
		$description = ereg_replace("[ ]* [ ]*", ' ', $description);
		$description = StripSlashes($description);

		$this->_description = $description;
		$this->_meta_description = $this->createMetaDescription();

	}

	/**
	 * An empty function
	 * 
	 */
	function createTitleTag()
	{

	}

	/**
	 * Cleans the provided text, a wrapper for icms_purifyText
	 * @see icms_purifyText
	 * @param string $text Text to be cleaned
	 * @param boolean $keyword Whether the provided string is a keyword, or not
	 * @return string The purified text
	 */
	function purifyText($text, $keyword = false)
	{
		return icms_purifyText($text, $keyword);
	}

	/**
	 * Creates a meta description
	 * @param int $maxWords Maximum number of words for the description
	 * @return string
	 */
	function createMetaDescription($maxWords = 100)
	{
		$words = array();
		$words = explode(" ", $this->_description);

		// Only keep $maxWords words
		$newWords = array();
		$i = 0;

		while ($i < $maxWords-1 && $i < count($words)) {
			$newWords[] = $words[$i];
			$i++;
		}
		$ret = implode(' ', $newWords);

		return $ret;
	}

	/**
	 * Generates a list of keywords from the provided text
	 * @param steing $text Text to parse
	 * @param int $minChar Minimum word length for the keywords
	 * @return array An array of keywords
	 */
	function findMetaKeywords($text, $minChar)
	{
		$keywords = array();

		$text = $this->purifyText($text);
		$text = $this->html2text($text);

		$text = ereg_replace("([^\r\n])\r\n([^\r\n])", "\\1 \\2", $text);
		$text = ereg_replace("[\r\n]*\r\n[\r\n]*", "\r\n\r\n", $text);
		$text = ereg_replace("[ ]* [ ]*", ' ', $text);
		$text = StripSlashes($text);
		$text =

		$originalKeywords = preg_split ('/[^a-zA-Z\'"-]+/', $text, -1, PREG_SPLIT_NO_EMPTY);

		foreach ($originalKeywords as $originalKeyword) {
			$secondRoundKeywords = explode("'", $originalKeyword);
			foreach ($secondRoundKeywords as $secondRoundKeyword) {
				if (strlen($secondRoundKeyword) >= $minChar) {
					if (!in_array($secondRoundKeyword, $keywords)) {
						$keywords[] = trim($secondRoundKeyword);
					}
				}
			}
		}
		return $keywords;
	}

	/**
	 * Creates a string of keywords
	 * @return string
	 */
	function createMetaKeywords()
	{
		global $icmsModuleConfig;
		$keywords = $this->findMetaKeywords($this->_original_title . " " . $this->_description, $this->_minChar);
		if (isset($icmsModuleConfig) && isset($icmsModuleConfig['moduleMetaKeywords']) && $icmsModuleConfig['moduleMetaKeywords'] != '') {
			$moduleKeywords = explode(",", $icmsModuleConfig['moduleMetaKeywords']);
			$keywords = array_merge($keywords, $moduleKeywords);
		}

		/* Commenting this out as it may cause problem on ImpressCMS ML websites
		$return_keywords = array();

		// Cleaning for duplicate keywords
		foreach ($keywords as $keyword) {
			if (!in_array($keyword, $keywords)) {
				$return_keywords[] = trim($keyword);
			}
		}*/

		// Only take the first 90 keywords
		$newKeywords = array();
		$i = 0;
		while ($i < 90 - 1 && isset($keywords[$i])) {
			$newKeywords[] = $keywords[$i];
			$i++;
		}
		$ret = implode(', ', $newKeywords);

		return $ret;
	}

	/**
	 * An empty function
	 * 
	 */
	function autoBuildMeta_keywords()
	{

	}

	/**
	 * Generates keywords, description and title, setting the associated properties
	 * 
	 */
	function buildAutoMetaTags()
	{
		global $icmsModule, $icmsModuleConfig;

		$this->_keywords = $this->createMetaKeywords();
		$this->_meta_description = $this->createMetaDescription();
		$this->_title = $this->createTitleTag();

	}

	/**
	 * Assigns the meta tags to the template
	 *
	 */
	function createMetaTags()
	{
		global $xoopsTpl, $xoTheme;

		if (is_object($xoTheme)) {
			$xoTheme->addMeta( 'meta', 'keywords',$this->_keywords);
			$xoTheme->addMeta( 'meta', 'description',$this->_description);
			$xoTheme->addMeta( 'meta', 'title', $this->_title);
		} else {
			$xoopsTpl->assign('xoops_meta_keywords',$this->_keywords);
			$xoopsTpl->assign('xoops_meta_description',$this->_description);
		}
		$xoopsTpl->assign('xoops_pagetitle',$this->_title);
	}

}

?>