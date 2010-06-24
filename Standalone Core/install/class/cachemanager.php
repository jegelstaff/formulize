<?php
/**
* Cache Manager Class
*
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license	LICENSE.txt
* @package	installer
* @since	XOOPS
* @author	http://www.xoops.org The XOOPS Project
* @author	modified by UnderDog <underdog@impresscms.org>
* @version	$Id: cachemanager.php 8568 2009-04-11 13:15:53Z icmsunderdog $
*/

/**
* cache_manager for XOOPS installer
*
* @author Haruki Setoyama  <haruki@planewave.org>
* @version $Id: cachemanager.php 8568 2009-04-11 13:15:53Z icmsunderdog $
* @access public
**/
class cache_manager {

    var $s_files = array();
    var $f_files = array();

    function write($file, $source){
        if (false != $fp = fopen(XOOPS_CACHE_PATH.'/'.$file, 'w')) {
            fwrite($fp, $source);
            fclose($fp);
            $this->s_files[] = $file;
        }else{
            $this->f_files[] = $file;
        }
    }

    function report(){
        $content = "<table align='center'><tr><td align='left'>\n";
        foreach($this->s_files as $val){
            $content .= _OKIMG.sprintf(_INSTALL_L123, "<b>$val</b>")."<br />\n";
        }
        foreach($this->f_files as $val){
            $content .= _NGIMG.sprintf(_INSTALL_L124, "<b>$val</b>")."<br />\n";
        }
        $content .= "</td></tr></table>\n";
        return $content;
    }

}


?>