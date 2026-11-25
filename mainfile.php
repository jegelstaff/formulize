<?php
/**
* All information in order to connect to database are going through here.
*
* Be careful if you are changing data's in this file.
*
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @package		Core
* @version		$Id: mainfile.php 20902 2011-02-27 02:34:57Z skenow $
*/
// ImpressCMS is not installed yet.

// XOOPS Physical Path
// Physical path to your main XOOPS directory WITHOUT trailing slash
// Example: define('XOOPS_ROOT_PATH', '/path/to/xoops/directory');
// ALTERED BY FREEFORM SOLUTIONS...
// AS DEFINED IN INSTALLER BY USER:
// define('XOOPS_ROOT_PATH', '');
// AS DETERMINED FROM FIRST PRINCIPLES:
define('XOOPS_ROOT_PATH', realpath(dirname(__FILE__)));

// XOOPS Security Physical Path
// Physical path to your security XOOPS directory WITHOUT trailing slash.
// Ideally off your server WEB folder
// Example: define('XOOPS_TRUST_PATH', '/path/to/trust/directory');
define('XOOPS_TRUST_PATH', '');

// sdata#--#

// XOOPS Virtual Path (URL)
// Virtual path to your main XOOPS directory WITHOUT trailing slash
// Example: define('XOOPS_URL', 'http://url_to_xoops_directory');
// ALTERED BY FREEFORM SOLUTIONS...
// AS DEFINED IN INSTALLER BY USER:
// define('XOOPS_URL', 'http://');
// AS DETERMINED FROM FIRST PRINCIPLES:
if (!defined("SITE_BASE_URL")) {
    # if this code is in a subfolder of the website, figure out what the subfolder url is
    if (XOOPS_ROOT_PATH != $_SERVER["DOCUMENT_ROOT"]) {
        // construct the SITE_BASE_URL portion from the part of the root path that is different from the shared stem with the document root
        // ie: root path could be /var/subsite/sitename and document root could be /var/www
        // or could be /var/www/sitename vs /var/www
        // we need to extract /subsite/sitename, or /sitename
        $slashType = strstr(XOOPS_ROOT_PATH,"/") ? "/" : "\\";
        $slashPos = 0;
        $base_url = "";
        while($nextSlashPos = strpos(XOOPS_ROOT_PATH.$slashType,$slashType,$slashPos+1)) {
            $rpPart = substr(XOOPS_ROOT_PATH,$slashPos+1,$nextSlashPos-$slashPos-1);
            $drPart = substr($_SERVER["DOCUMENT_ROOT"],$slashPos+1,$nextSlashPos-$slashPos-1);
            if($rpPart == $drPart) {
                $slashPos = $nextSlashPos; // look for the next part of the path
            } elseif($slashPos == 0) { // nothing in common, so give up on automatic detection of base url, user might need to specify manually!
                error_log('Formulize: could not detect base url automatically. If your website is not located in the root of the domain, you may need to specify the base url manually in the mainfile.php. Look for this message in there to see where to do it.');
                $base_url = '';
                break;
            } else {
                $base_url = str_replace('\\','/',substr(XOOPS_ROOT_PATH,$slashPos));
                break;
            }
        }
        define("SITE_BASE_URL", $base_url);
    } else {
        define("SITE_BASE_URL", "");
    }
}

$PortNum = (80 == $_SERVER["SERVER_PORT"] OR 443 == $_SERVER["SERVER_PORT"]) ? "" : ":" . $_SERVER["SERVER_PORT"];
define('XOOPS_URL', ((443 == $_SERVER["SERVER_PORT"] OR (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) AND $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? "https://" : "http://") . $_SERVER['SERVER_NAME'] . $PortNum . SITE_BASE_URL );

define('ICMS_ROOT_PATH', XOOPS_ROOT_PATH);
define('ICMS_URL', XOOPS_URL);

if(! defined('XOOPS_INSTALL')){
    header('Location: install/index.php');
    exit();
}
