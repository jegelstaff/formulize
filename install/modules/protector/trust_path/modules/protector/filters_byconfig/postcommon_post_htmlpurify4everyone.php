<?php

class protector_postcommon_post_htmlpurify4everyone extends ProtectorFilterAbstract {
	var $purifier ;
	var $method ;

	function execute()
	{
		// HTMLPurifier runs with PHP5 only
		if( version_compare( PHP_VERSION , '5.0.0' ) < 0 ) {
			die( 'Turn postcommon_post_htmlpurify4everyone.php off because this filter cannot run with PHP4' ) ;
		}

		// use HTMLPurifier inside ImpressCMS
		if(!class_exists('icms_core_HTMLFilter')) {
			$this->purifier =& icms_core_HTMLFilter::getInstance();
			$this->method = 'htmlpurify';

		} else {
			// use HTMLPurifier inside Protector
			require_once dirname(dirname(__FILE__)).'/library/HTMLPurifier.auto.php' ;
			$config = HTMLPurifier_Config::createDefault();
			$config->set('Cache', 'SerializerPath', XOOPS_TRUST_PATH.'/modules/protector/configs');
			$config->set('Core', 'Encoding', _CHARSET);
			//$config->set('HTML', 'Doctype', 'HTML 4.01 Transitional');
			$this->purifier = new HTMLPurifier($config);
			$this->method = 'purify' ;
		}

		$_POST = $this->purify_recursive( $_POST ) ;
	}


	function purify_recursive( $data )
	{
		if( is_array( $data ) ) {
			return array_map( array( $this , 'purify_recursive' ) , $data ) ;
		} else {
			return strlen( $data ) > 32 ? call_user_func( array( $this->purifier , $this->method ) , $data ) : $data ;
		}
	}

}

?>