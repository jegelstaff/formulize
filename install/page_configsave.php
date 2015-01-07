<?php
/**
 * Installer mainfile creation page
 *
 * See the enclosed file license.txt for licensing information.
 * If you did not receive this file, get it at http://www.fsf.org/copyleft/gpl.html
 *
 * @copyright    The XOOPS project http://www.xoops.org/
 * @license      http://www.fsf.org/copyleft/gpl.html GNU General Public License (GPL)
 * @package		installer
 * @since        Xoops 2.3.0
 * @author		Haruki Setoyama  <haruki@planewave.org>
 * @author 		Kazumi Ono <webmaster@myweb.ne.jp>
 * @author		Skalpa Keo <skalpa@xoops.org>
 * @version		$Id: page_configsave.php 20098 2010-09-07 16:19:19Z skenow $
 */
/**
 *
 */
require_once 'common.inc.php';
if (!defined( 'XOOPS_INSTALL' ) )	exit();

$wizard->setPage( 'configsave' );
$pageHasForm = true;
$pageHasHelp = false;

$vars =& $_SESSION['settings'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	$error = '';
	// let's try and put the db info in the trust path
	$sdata_file_name = md5($vars['ROOT_PATH'] . time()) . '.php';

	if (!copy( $vars['ROOT_PATH'] . '/install/templates/sdata.dist.php', $vars['TRUST_PATH'] . '/' . $sdata_file_name )) {
		// we were not able to create the sdata file in trust path so we will use the old method
		$error = true;
	} else {
		clearstatcache();
		if (! $file = fopen( $vars['TRUST_PATH'] . '/' . $sdata_file_name, "r" )) {
			$error = ERR_READ_SDATA;
		} else {
			$content = fread( $file, filesize( $vars['TRUST_PATH'] . '/' . $sdata_file_name ) );
			fclose($file);

			$sdata_rewrite = array();
			$sdata_rewrite['DB_HOST'] = $vars['DB_HOST'];
			$sdata_rewrite['DB_USER'] = $vars['DB_USER'];
			$sdata_rewrite['DB_PASS'] = $vars['DB_PASS'];
			$sdata_rewrite['DB_NAME'] = $vars['DB_NAME'];
			$sdata_rewrite['DB_PREFIX'] = $vars['DB_PREFIX'];
			$sdata_rewrite['DB_SALT'] = $vars['DB_SALT'];

			foreach ($sdata_rewrite as $key => $val) {
				if (preg_match( "/(define\()([\"'])(SDATA_$key)\\2,\s*([\"'])(.*?)\\4\s*\)/", $content )) {
					$val = addcslashes( $val, '\$"\'' );
					$content = preg_replace( "/(define\()([\"'])(SDATA_$key)\\2,\s*([\"'])(.*?)\\4\s*\)/",
						"define( 'SDATA_$key', '$val' )", $content );
				} else {
					//$this->error = true;
					//$this->report .= _NGIMG.sprintf( ERR_WRITING_CONSTANT, "<b>$val</b>")."<br />\n";
				}
			}
			if (!$file = fopen( $vars['TRUST_PATH'] . '/' . $sdata_file_name, "w" )) {
				$error = ERR_WRITE_SDATA;
			} else {
				if (fwrite( $file, $content ) == -1) {
					$error = ERR_WRITE_SDATA;
				}
				fclose($file);
			}
		}
	}
	if (!$error) {
		// then we were able to save the db info into the trust path
		$dbinfo_in_trust_path = true;
		$vars['DB_HOST'] = 'SDATA_DB_HOST';
		$vars['DB_USER'] = 'SDATA_DB_USER';
		$vars['DB_PASS'] = 'SDATA_DB_PASS';
		$vars['DB_NAME'] = 'SDATA_DB_NAME';
		$vars['DB_PREFIX'] = 'SDATA_DB_PREFIX';
		$vars['DB_SALT'] = 'SDATA_DB_SALT';
	} else {
		$dbinfo_in_trust_path = false;
	}

	if (!copy( $vars['ROOT_PATH'] . '/install/templates/mainfile.dist.php', $vars['ROOT_PATH'] . '/mainfile.php' )) {
		$error = ERR_COPY_MAINFILE;
	} else {
		clearstatcache();

		$rewrite = array( 'GROUP_ADMIN' => 1, 'GROUP_USERS' => 2, 'GROUP_ANONYMOUS' => 3 );
		$rewrite = array_merge( $rewrite, $vars );
		if (! $file = fopen( $vars['ROOT_PATH'] . '/mainfile.php', "r" )) {
			$error = ERR_READ_MAINFILE;
		} else {
			$content = fread( $file, filesize( $vars['ROOT_PATH'] . '/mainfile.php' ) );
			fclose($file);

			if ($dbinfo_in_trust_path) {
				// add the line in mainfile to include the sdata file
				$include_line = "include_once XOOPS_TRUST_PATH . '/" . $sdata_file_name . "' ;";
				$content = str_replace('// sdata#--#', $include_line, $content);
			} else {
				$content = str_replace('// sdata#--#', '', $content);
			}

			// ADDED BY FREEFORM SOLUTIONS
			$rewrite['TRUST_PATH'] = "REPLACE-ROOT-IN-TRUST-PATH";

			foreach ($rewrite as $key => $val) {
				if (is_int($val) && preg_match("/(define\()([\"'])(XOOPS_$key)\\2,\s*([0-9]+)\s*\)/", $content )) {
					$content = preg_replace( "/(define\()([\"'])(XOOPS_$key)\\2,\s*([0-9]+)\s*\)/",
						"define( 'XOOPS_$key', $val )", $content );
				} elseif ($dbinfo_in_trust_path && isset($sdata_rewrite[$key])) {
					if (preg_match( "/(define\()([\"'])(XOOPS_$key)\\2,\s*([\"'])(.*?)\\4\s*\)/", $content )) {
						$val = addslashes( $val );
						$content = preg_replace( "/(define\()([\"'])(XOOPS_$key)\\2,\s*([\"'])(.*?)\\4\s*\)/",
							"define( 'XOOPS_$key', $val )", $content );
					}
				} elseif (preg_match( "/(define\()([\"'])(XOOPS_$key)\\2,\s*([\"'])(.*?)\\4\s*\)/", $content )) {
					$val = addslashes( $val );
					$content = preg_replace( "/(define\()([\"'])(XOOPS_$key)\\2,\s*([\"'])(.*?)\\4\s*\)/",
						"define( 'XOOPS_$key', '$val' )", $content );
				} else {
					//$this->error = true;
					//$this->report .= _NGIMG.sprintf( ERR_WRITING_CONSTANT, "<b>$val</b>")."<br />\n";
				}
			}
			// ADDED BY FREEFORM SOLUTIONS
			$content = str_replace("'REPLACE-ROOT-IN-TRUST-PATH", str_replace("'".addslashes($vars['ROOT_PATH']), "XOOPS_ROOT_PATH.'", "'".addslashes($vars['TRUST_PATH'])), $content);
			if (!$file = fopen( $vars['ROOT_PATH'] . '/mainfile.php', "w" )) {
				$error = ERR_WRITE_MAINFILE;
			} else {
				if (fwrite( $file, $content ) == -1) {
					$error = ERR_WRITE_MAINFILE;
				}
				fflush($file);
				fclose($file);
				clearstatcache();
			}
		}
	}

	if (ini_get('safe_mode') == 0 || strtolower(ini_get('safe_mode')) == 'off')
	{
		// creating the required folders in trust_path
		if (!icms_core_Filesystem::mkdir($vars['TRUST_PATH'] . '/cache/htmlpurifier', 0777, '', array('[', '?', '"', '<', '>', '|', ' ' ))) {
			/**
			 * @todo trap error
			 */
		}
		if (is_dir($vars['TRUST_PATH'] . '/cache/htmlpurifier'))
		{
			if (!icms_core_Filesystem::mkdir($vars['TRUST_PATH'].'/cache/htmlpurifier/HTML', 0777, '', array('[', '?', '"', '<', '>', '|', ' ' ))
				&& !icms_core_Filesystem::mkdir($vars['TRUST_PATH'].'/cache/htmlpurifier/CSS', 0777, '', array('[', '?', '"', '<', '>', '|', ' ' ))
				&& !icms_core_Filesystem::mkdir($vars['TRUST_PATH'].'/cache/htmlpurifier/URI', 0777, '', array('[', '?', '"', '<', '>', '|', ' ' ))
				&& !icms_core_Filesystem::mkdir($vars['TRUST_PATH'].'/cache/htmlpurifier/Test', 0777, '', array('[', '?', '"', '<', '>', '|', ' ' )))
			{
				/**
				 * @todo trap error
				 */
			}
		}
	}

	if (empty( $error )) {
		$wizard->redirectToPage( '+1' );
		exit();
	}
	$content = '<p class="errorMsg">' . $error . '</p>';
	include 'install_tpl.php';
	exit();
}

ob_start();
?>
<p class="x2-note"><?php echo READY_SAVE_MAINFILE; ?></p>
<dl style="height: 200px; overflow: auto; border: 1px solid #D0D0D0">
<?php foreach ( $vars as $k => $v) {
	echo "<dt>XOOPS_$k</dt><dd>$v</dd>";
} ?>
</dl>

<?php
$content = ob_get_contents();
ob_end_clean();
include 'install_tpl.php';
?>