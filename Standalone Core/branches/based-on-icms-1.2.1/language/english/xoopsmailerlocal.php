<?php
/**
 * Localize the mail functions
 *
 * The English localization is solely for demonstration
 */
// Do not change the class name
class XoopsMailerLocal extends XoopsMailer {

	function XoopsMailerLocal(){
		$this->XoopsMailer();
		// It is supposed no need to change the charset
		$this->charSet = strtolower( _CHARSET );
		// You MUST specify the language code value so that the file exists: XOOPS_ROOT_PAT/class/mail/phpmailer/language/lang-["your-language-code"].php
	    $this->multimailer->SetLanguage("en");
	}
	
	// Multibyte languages are encouraged to make their proper method for encoding FromName
	function encodeFromName($text)
	{
		// Activate the following line if needed
		// $text = "=?{$this->charSet}?B?".base64_encode($text)."?=";
		return $text;
	}

	// Multibyte languages are encouraged to make their proper method for encoding Subject
	function encodeSubject($text)
	{
		// Activate the following line if needed
		// $text = "=?{$this->charSet}?B?".base64_encode($text)."?=";
		return $text;
	}
}
?>