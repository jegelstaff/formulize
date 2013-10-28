<?php
/**
 * Localize the email functions
 *
 * The English localization is solely for demonstration
 */
// Do not change the class name
class XoopsMailerLocal extends icms_messaging_EmailHandler {

	public function __construct() {
		parent::__construct();
		// You MUST specify the language code value so that the file exists: XOOPS_ROOT_PAT/class/mail/phpmailer/language/lang-["your-language-code"].php
		$this->SetLanguage("en");
	}

	// Multibyte languages are encouraged to make their proper method for encoding FromName
	public function encodeFromName($text) {
		// Activate the following line if needed
		// $text = "=?{$this->charSet}?B?".base64_encode($text)."?=";
		return $text;
	}

	// Multibyte languages are encouraged to make their proper method for encoding Subject
	public function encodeSubject($text) {
		// Activate the following line if needed
		// $text = "=?{$this->charSet}?B?".base64_encode($text)."?=";
		return $text;
	}
}
