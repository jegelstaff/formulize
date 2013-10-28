<?php
/**
 * Mainfile Manager Class
 *
 * @copyright	http://www.xoops.org/ The XOOPS Project
 * @copyright	XOOPS_copyrights.txt
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license	LICENSE.txt
 * @package	installer
 * @since	XOOPS
 * @author	http://www.xoops.org The XOOPS Project
 * @author	modified by UnderDog <underdog@impresscms.org>
 * @version	$Id: mainfilemanager.php 19775 2010-07-11 18:54:25Z malanciault $
 */

/**
 * mainfile manager for XOOPS installer
 *
 * @author Haruki Setoyama  <haruki@planewave.org>
 * @version $Id: mainfilemanager.php 19775 2010-07-11 18:54:25Z malanciault $
 * @access public
 **/
class mainfile_manager {

	var $path = '../mainfile.php';
	var $distfile = './templates/mainfile.dist.php';
	var $rewrite = array();

	var $report = '';
	var $error = false;

	function mainfile_manager() {
	}

	function setRewrite($def, $val) {
		$this->rewrite[$def] = $val;
	}

	function copyDistFile() {
		if (! copy($this->distfile, $this->path)) {
			$this->report .= _NGIMG.sprintf(_INSTALL_L126, "<b>".$this->path."</b>")."<br />\n";
			$this->error = true;
			return false;
		}
		$this->report .= _OKIMG.sprintf(_INSTALL_L125, "<b>".$this->path."</b>", "<b>".$this->distfile."</b>")."<br />\n";
		return true;
	}

	function doRewrite() {
		clearstatcache();
		if (! $file = fopen($this->path,"r")) {
			$this->error = true;
			return false;
		}
		$content = fread($file, filesize($this->path) );
		fclose($file);

		foreach ($this->rewrite as $key => $val) {
			if (is_int($val) &&
			preg_match("/(define\()([\"'])(".$key.")\\2,\s*([0-9]+)\s*\)/",$content)) {
				if ($key == 'PROTECTOR1' || $key == 'PROTECTOR2') {
					$content = preg_replace("/(define\()([\"'])(".$key.")\\2,\s*([0-9]+)\s*\)/", $val, $content);
					$this->report .= _OKIMG.sprintf(_INSTALL_L121, "<b>$key</b>", $val)."<br />\n";
					continue;
				}
				$content = preg_replace("/(define\()([\"'])(".$key.")\\2,\s*([0-9]+)\s*\)/"
				, "define('".$key."', ".$val.")"
				, $content);
				$this->report .= _OKIMG.sprintf(_INSTALL_L121, "<b>$key</b>", $val)."<br />\n";
			}
			elseif (preg_match("/(define\()([\"'])(".$key.")\\2,\s*([\"'])(.*?)\\4\s*\)/",$content)) {
				if ($key == 'PROTECTOR1' || $key == 'PROTECTOR2') {
					$content = preg_replace("/(define\()([\"'])(".$key.")\\2,\s*([\"'])(.*?)\\4\s*\)/", $val, $content);
					$this->report .= _OKIMG.sprintf(_INSTALL_L121, "<b>$key</b>", $val)."<br />\n";
					ontinue;
				}
				$content = preg_replace("/(define\()([\"'])(".$key.")\\2,\s*([\"'])(.*?)\\4\s*\)/"
				, "define('".$key."', '". str_replace( '$', '\$', addslashes( $val ) ) ."')"
				, $content);
				$this->report .= _OKIMG.sprintf(_INSTALL_L121, "<b>$key</b>", $val)."<br />\n";
			} else {
				$this->error = true;
				$this->report .= _NGIMG.sprintf(_INSTALL_L122, "<b>$val</b>")."<br />\n";
			}
		}

		if (!$file = fopen($this->path,"w")) {
			$this->error = true;
			return false;
		}

		if (fwrite($file,$content) == -1) {
			fclose($file);
			$this->error = true;
			return false;
		}

		fclose($file);

		return true;
	}

	function report() {
		$content = "<table align='center'><tr><td align='left'>\n";
		$content .= $this->report;
		$content .= "</td></tr></table>\n";
		return $content;
	}

	function error() {
		return $this->error;
	}
}

?>