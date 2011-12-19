<?php
/**
* ICMS kernel Base Class
*
* @copyright      http://www.impresscms.org/ The ImpressCMS Project
* @license         LICENSE.txt
* @package	kernel
* @since            1.1
* @version		$Id: icmskernel.php 8572 2009-04-11 17:07:46Z skenow $
*/

/**
 * Extremely reduced kernel class
 * Few notes:
 * - modules should use this class methods to generate physical paths/URIs (the ones which do not conform
 * will perform badly when true URL rewriting is implemented)
 * @package		kernel
 * @since 		1.1
 */
class IcmsKernel {
	/** @var array */
	var $paths = array(
		'www' => array(), 'modules' => array(), 'themes' => array(),
	);
	/** @var array */
	var $urls=false;
	
	/**
	 * Constructor for IcmsKernel, initiating all properties of the class
	 */
	function IcmsKernel() {
		$this->paths['www'] = array( ICMS_ROOT_PATH, ICMS_URL );
		$this->paths['modules'] = array( ICMS_ROOT_PATH . '/modules', ICMS_URL . '/modules' );
		$this->paths['themes'] = array( ICMS_ROOT_PATH . '/themes', ICMS_URL . '/themes' );
		$this->_buildRelevantUrls();
	}
	/**
	 * Convert a ImpressCMS path to a physical one
	 * @param	string	$url URL string to convert to a physical path
	 * @param 	boolean	$virtual
	 * @return 	string	
	 */
	function path( $url, $virtual = false ) {
		$path = '';
		@list( $root, $path ) = explode( '/', $url, 2 );
		if ( !isset( $this->paths[$root] ) ) {
			list( $root, $path ) = array( 'www', $url );
		}
		if ( !$virtual ) {		// Returns a physical path
			return $this->paths[$root][0] . '/' . $path;
		}
		return !isset( $this->paths[$root][1] ) ? '' : ( $this->paths[$root][1] . '/' . $path );
	}
	/**
	* Convert a ImpressCMS path to an URL
	* @param 	string	$url
	* @return 	string
	*/
	function url( $url ) {
		return ( false !== strpos( $url, '://' ) ? $url : $this->path( $url, true ) );
	}
	/**
	* Build an URL with the specified request params
	* @param 	string 	$url
	* @param 	array	$params
	* @return 	string
	*/
	function buildUrl( $url, $params = array() ) {
		if ( $url == '.' ) {
			$url = $_SERVER['REQUEST_URI'];
		}
		$split = explode( '?', $url );
		if ( count($split) > 1 ) {
			list( $url, $query ) = $split;
			parse_str( $query, $query );
			$params = array_merge( $query, $params );
		}
		if ( !empty( $params ) ) {
			foreach ( $params as $k => $v ) {
				$params[$k] = $k . '=' . rawurlencode($v);
			}
			$url .= '?' . implode( '&', $params );
		}
		return $url;
	}
	
	/**
	 * Build URLs for global use throughout the application
	 * @return 	array
	 */ 
	function _buildRelevantUrls() {

		if (!$this->urls) {
			$http = ((strpos(ICMS_URL, "https://")) === false) ? ("http://") : ("https://");
			$phpself = $_SERVER['PHP_SELF'];
			$httphost = $_SERVER['HTTP_HOST'];
			$querystring = $_SERVER['QUERY_STRING'];
			if ($querystring != '') {
				$querystring = '?' . $querystring;
			}
			$currenturl = $http . $httphost . $phpself . $querystring;
			$this->urls = array ();
			$this->urls['http'] = $http;
			$this->urls['httphost'] = $httphost;
			$this->urls['phpself'] = $phpself;
			$this->urls['querystring'] = $querystring;
			$this->urls['full_phpself'] = $http . $httphost . $phpself;
			$this->urls['full'] = $currenturl;
			
			$previouspage = '';
		    if ( array_key_exists( 'HTTP_REFERER', $_SERVER) && isset($_SERVER['HTTP_REFERER']) ) {
		        $this->urls['previouspage'] = $_SERVER['HTTP_REFERER'];
		    }
			//$this->urls['isHomePage'] = (ICMS_URL . "/index.php") == ($http . $httphost . $phpself);
		}
		return $this->urls;
	}
	

}

?>