<?php
/**
* Functions needed by the ImpressCMS installer
*
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license	LICENSE.txt
* @package	installer
* @since	XOOPS
* @author	http://www.xoops.org The XOOPS Project
* @author		marcan <marcan@impresscms.org>
* @author	modified by UnderDog <underdog@impresscms.org>
* @version	$Id: functions.php 9738 2010-01-10 14:44:48Z skenow $
*/

/**
 * Safely create a folder
 *
 * @since 1.2.1
 * @copyright ImpressCMS
 *
 * @param string $target path to the folder to be created
 * @param integer $mode permissions to set on the folder. This is affected by umask in effect
 * @param string $base root location for the folder, ICMS_ROOT_PATH or ICMS_TRUST_PATH, for example
 * @return boolean True if folder is created, False if it is not
 */
function imcms_install_mkdir($target, $mode = 0777 ) {

	if( is_dir( $target ) ) return TRUE;

	$metachars = array('[', '?', '"', '<', '>', '|', ' ' ); // Need to exclude . and : because they may occur in the root path
	$target = str_replace( $metachars , '_', $target );

	if( mkdir($target, $mode, TRUE) ) {
		
		// create an index.html file in this directory
		if ($fh = @fopen($target.'/index.html', 'w')) {
			fwrite($fh, '<script>history.go(-1);</script>');
			@fclose($fh);
		}
  	}

  	if( substr( decoct( fileperms( $target ) ),2) != $mode ) {
  		chmod($target, $mode);
  	}

	return is_dir( $target );
}

/**
* Change the permission of a file or folder
*
* @author	Newbb2 developpement team
* @param	string	$target  target file or folder
* @param	int		$mode	permission
* @return   bool	Returns true on success, false on failure
*/
function imcms_install_chmod($target, $mode = 0777) {
	return @ chmod($target, $mode);
}
// ----- New Password System
function imcms_createSalt($slength=64)
{
	$salt= '';
	$base = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$microtime = function_exists('microtime') ? microtime() : time();
		srand((double)$microtime * 1000000);
		for($i=0; $i<=$slength; $i++)
		$salt.= substr($base, rand() % strlen($base), 1);
		return $salt;
}
function imcms_encryptPass($adminpass, $adminsalt, $mainSalt)
{
	$pass = hash('sha256', $adminsalt.md5($adminpass).$mainSalt);
	unset($mainSalt);
	return $pass;
}
// ----- End New Password System
/**
 * Recursively delete a directory
 *
 * @param string $dir Directory name
 * @param boolean $deleteRootToo Delete specified top-level directory as well
 */
function unlinkRecursive($dir, $deleteRootToo=true)
{
    if(!$dh = @opendir($dir))
    {
        return;
    }
    while (false !== ($obj = readdir($dh)))
    {
        if($obj == '.' || $obj == '..')
        {
            continue;
        }

        if (!@unlink($dir . '/' . $obj))
        {
            unlinkRecursive($dir.'/'.$obj, true);
        }
    }

    closedir($dh);

    if ($deleteRootToo)
    {
        @rmdir($dir);
    }

    return;
}

/**
* Copy a file, or a folder and its contents
*
* @author	Aidan Lister <aidan@php.net>
* @param	string	$source	The source
* @param	string  $dest	  The destination
* @return   bool	Returns true on success, false on failure
*/
function imcms_copyr($source, $dest)
{
	// Simple copy for a file
	if(is_file($source)) {return copy($source, $dest);}
	// Make destination directory
	if(!is_dir($dest)) {mkdir($dest);}
	// Loop through the folder
	$dir = dir($source);
	while(false !== $entry = $dir->read())
	{
		// Skip pointers
		if($entry == '.' || $entry == '..') {continue;}
		// Deep copy directories
		if(is_dir("$source/$entry") && ($dest !== "$source/$entry")) {imcms_copyr("$source/$entry", "$dest/$entry");}
		else {copy("$source/$entry", "$dest/$entry");}
	}
	// Clean up
	$dir->close();
	return true;
}

/**
* Function to get the base domain name from a URL.
* credit for this function should goto Phosphorus and Lime, it is released under LGPL.
*
* @param string $url the URL to be stripped.
* @return string
*/
function imcms_get_base_domain($url)
{
     $debug = 0;
     $base_domain = '';

     // generic tlds (source: http://en.wikipedia.org/wiki/Generic_top-level_domain)
     $G_TLD = array(
     'biz','com','edu','gov','info','int','mil','name','net','org','aero','asia','cat','coop','jobs','mobi','museum','pro','tel','travel',
     'arpa','root','berlin','bzh','cym','gal','geo','kid','kids','lat','mail','nyc','post','sco','web','xxx',
     'nato', 'example','invalid','localhost','test','bitnet','csnet','ip','local','onion','uucp','co');

     // country tlds (source: http://en.wikipedia.org/wiki/Country_code_top-level_domain)
     $C_TLD = array(
     // active
     'ac','ad','ae','af','ag','ai','al','am','an','ao','aq','ar','as','at','au','aw','ax','az',
     'ba','bb','bd','be','bf','bg','bh','bi','bj','bm','bn','bo','br','bs','bt','bw','by','bz',
     'ca','cc','cd','cf','cg','ch','ci','ck','cl','cm','cn','co','cr','cu','cv','cx','cy','cz',
     'de','dj','dk','dm','do','dz','ec','ee','eg','er','es','et','eu','fi','fj','fk','fm','fo',
     'fr','ga','gd','ge','gf','gg','gh','gi','gl','gm','gn','gp','gq','gr','gs','gt','gu','gw',
     'gy','hk','hm','hn','hr','ht','hu','id','ie','il','im','in','io','iq','ir','is','it','je',
     'jm','jo','jp','ke','kg','kh','ki','km','kn','kr','kw','ky','kz','la','lb','lc','li','lk',
     'lr','ls','lt','lu','lv','ly','ma','mc','md','mg','mh','mk','ml','mm','mn','mo','mp','mq',
     'mr','ms','mt','mu','mv','mw','mx','my','mz','na','nc','ne','nf','ng','ni','nl','no','np',
     'nr','nu','nz','om','pa','pe','pf','pg','ph','pk','pl','pn','pr','ps','pt','pw','py','qa',
     're','ro','ru','rw','sa','sb','sc','sd','se','sg','sh','si','sk','sl','sm','sn','sr','st',
     'sv','sy','sz','tc','td','tf','tg','th','tj','tk','tl','tm','tn','to','tr','tt','tv','tw',
     'tz','ua','ug','uk','us','uy','uz','va','vc','ve','vg','vi','vn','vu','wf','ws','ye','yu',
     'za','zm','zw',
     // inactive
     'eh','kp','me','rs','um','bv','gb','pm','sj','so','yt','su','tp','bu','cs','dd','zr');

     // get domain
     if(!$full_domain = imcms_get_url_domain($url)) {return $base_domain;}

     // break up domain, reverse
     $DOMAIN = explode('.', $full_domain);
     if($debug) print_r($DOMAIN);
     $DOMAIN = array_reverse($DOMAIN);
     if($debug) print_r($DOMAIN);

     // first check for ip address
     if(count($DOMAIN) == 4 && is_numeric($DOMAIN[0]) && is_numeric($DOMAIN[3])) {return $full_domain;}

     // if only 2 domain parts, that must be our domain
     if(count($DOMAIN) <= 2) return $full_domain;

     /*
     finally, with 3+ domain parts: obviously D0 is tld now,
     if D0 = ctld and D1 = gtld, we might have something like com.uk so,
     if D0 = ctld && D1 = gtld && D2 != 'www', domain = D2.D1.D0 else if D0 = ctld && D1 = gtld && D2 == 'www',
     domain = D1.D0 else domain = D1.D0 - these rules are simplified below.
     */
     if(in_array($DOMAIN[0], $C_TLD) && in_array($DOMAIN[1], $G_TLD) && $DOMAIN[2] != 'www')
     {
          $full_domain = $DOMAIN[2].'.'.$DOMAIN[1].'.'.$DOMAIN[0];
     }
     else
     {
          $full_domain = $DOMAIN[1].'.'.$DOMAIN[0];
     }
     // did we succeed?
     return $full_domain;
}

/**
* Function to get the domain from a URL.
* credit for this function should goto Phosphorus and Lime, it is released under LGPL.
*
* @param string $url the URL to be stripped.
* @return string
*/
function imcms_get_url_domain($url)
{
     $domain = '';
     $_URL = parse_url($url);

     if(!empty($_URL) || !empty($_URL['host'])) {$domain = $_URL['host'];}
     return $domain;
}

?>