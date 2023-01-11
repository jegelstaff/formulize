<?php
/**
 * Installer configuration check page
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
 * @version		$Id: page_modcheck.php 19775 2010-07-11 18:54:25Z malanciault $
 */
/**
 *
 */
require_once 'common.inc.php';
if (!defined( 'XOOPS_INSTALL' ) )	exit();

$wizard->setPage( 'modcheck' );
$pageHasForm = false;

$diagsOK = false;

function xoDiag( $status = -1, $str = '') {
    if ($status == -1) {
        $GLOBALS['error'] = true;
    }
    $classes = array( -1 => 'error', 0 => 'warning', 1 => 'success' );
    $strings = array( -1 => FAILED, 0 => WARNING, 1 => SUCCESS );
    if (empty($str)) {
        $str = $strings[$status];
    }
    return '<td class="' . $classes[$status] . '">' . $str . '</td>';
}

function xoDiagBoolSetting( $name, $wanted = false, $severe = false) {
    $setting = strtolower( ini_get( $name ) );
    $setting = ( empty( $setting ) || $setting == 'off' || $setting == 'false' ) ? false : true;
    if ($setting == $wanted) {
        return xoDiag( 1, $setting ? 'ON' : 'OFF' ) . successImg(1);
    } else {
        $severity = $severe ? -1 : 0;
        return xoDiag( $severity, $setting ? 'ON' : 'OFF' ) . successImg($severity);
    }
}

function xoDiagIfWritable( $path) {
    $path = "../" . $path;
    $error = true;
    if (!is_dir( $path )) {
        if (file_exists( $path )) {
            @chmod( $path, 0666 );
            $error = !is_writeable( $path );
        }
    } else {
        @chmod( $path, 0777 );
        $error = !is_writeable( $path );
    }
    return xoDiag( $error ? -1 : 1, $error ? 'Not writable' : 'Writable' );
}

function phpVersionCheck() {
    if (version_compare( phpversion(), '8.0', '>=')) {
        return 1;
    } else {
        return -1;
    }
}

function successImg($success_val) {
    $img_name = $success_val >= 0 ? "yes" : "no";
    return '<img src="img/' . $img_name . '.png" alt="Success?" class="rootimg" />';
}

$php_version_success = phpVersionCheck();
$mysql_success = function_exists('mysqli_connect') ? 1 : -1;
$extension_success = extension_loaded('session') ? 1 : -1;
$pcre_success = extension_loaded('pcre') ? 1 : -1;

$php_version_success_str = xoDiag($php_version_success, phpversion()) . successImg($php_version_success);
$mysql_success_str = xoDiag($mysql_success) . successImg($mysql_success);
$extension_success_str = xoDiag($extension_success) . successImg($extension_success);
$pcre_success_str = xoDiag($pcre_success) . successImg($pcre_success);

ob_start();
?>

    <fieldset>
        <h3><?php echo REQUIREMENTS; ?></h3>
        <h4><?php echo SERVER_API; ?>:&nbsp; <?php echo php_sapi_name(); ?> <img
                    src="img/yes.png" alt="Success" class="rootimg" /></h4>
        <div class="clear">&nbsp;</div>
        <h4><?php echo _PHP_VERSION; ?>:&nbsp;
            <?php echo $php_version_success_str ?>
        </h4>
        <div class="clear">&nbsp;</div>
        <h4><?php printf( PHP_EXTENSION, 'MySQL' ); ?>:&nbsp; <?php echo $mysql_success_str; ?> </h4>
        <div class="clear">&nbsp;</div>
        <h4><?php printf( PHP_EXTENSION, 'Session' ); ?>:&nbsp; <?php echo $extension_success_str ?></h4>
        <div class="clear">&nbsp;</div>
        <h4><?php printf( PHP_EXTENSION, 'PCRE' ); ?>:&nbsp; <?php echo $pcre_success_str ?></h4>
        <div class="clear">&nbsp;</div>
        <h4>file_uploads:&nbsp; <?php echo xoDiagBoolSetting( 'file_uploads', true ); ?>
        <div class="clear">&nbsp;</div>
    </fieldset>

    <fieldset>
        <h3><?php echo RECOMMENDED_EXTENSIONS; ?></h3>
        <p><?php echo RECOMMENDED_EXTENSIONS_MSG; ?></p>
        <div class="clear">&nbsp;</div>

        <h4><?php printf( PHP_EXTENSION, CHAR_ENCODING ); ?>:&nbsp; <?php
            $ext = array();
            if (extension_loaded( 'iconv' ) )		$ext[] = 'Iconv';
            if (extension_loaded( 'mb_string' ) )	$ext[] = 'MBString';
            if (empty($ext)) {
                echo xoDiag( 0, NONE );
            } else {
                echo xoDiag( 1, implode( ',', $ext ) );
            }
            ?> <img src="img/yes.png" alt="Success" class="rootimg" /></h4>
        <div class="clear">&nbsp;</div>
        <h4><?php printf( PHP_EXTENSION, XML_PARSING ); ?>:&nbsp; <?php
            $ext = array();
            if (extension_loaded( 'xml' ) )		$ext[] = 'XML';
            //if (extension_loaded( 'dom' ) )		$ext[] = 'DOM';
            if (empty($ext)) {
                echo xoDiag( 0, NONE );
            } else {
                echo xoDiag( 1, implode( ',', $ext ) );
            }
            ?> <img src="img/yes.png" alt="Success" class="rootimg" /></h4>
        <div class="clear">&nbsp;</div>
        <h4><?php printf( PHP_EXTENSION, OPEN_ID ); ?>:&nbsp; <?php
            $ext = array();
            if (extension_loaded( 'curl' ) )		$ext[] = 'Curl  <img src="img/yes.png" alt="Success" class="rootimg" />  ';
            if (extension_loaded( 'bcmath' ) )		$ext[] = ' Math Support  <img src="img/yes.png" alt="Success" class="rootimg" />  ';
            if (extension_loaded( 'openssl' ) )	$ext[] = ' OpenSSL  <img src="img/yes.png" alt="Success" class="rootimg" />';
            if (empty($ext)) {
                echo xoDiag( 0, NONE );
            } else {
                echo xoDiag( 1, implode( ' ', $ext ) );
            }
            ?></h4>
        <div class="clear">&nbsp;</div>
    </fieldset>
    <!--
	<table class="diags">
	<caption><?php echo FILE_PERMISSIONS; ?></caption>
    <thead>
    	<tr><th>Path</th><th>Status</th></tr>
    </thead>
	<?php
    $paths = array("uploads/", "cache/", "templates_c/", "mainfile.php");
    foreach ( $paths as $path) {
        ?>
	<tr>
		<th scope="row"><?php echo $path; ?></th>
		<td><?php echo xoDiagIfWritable( $path ); ?></td>
	</tr>
	<?php } ?>
	</table>
	-->
<?php
$content = ob_get_contents();
ob_end_clean();

include 'install_tpl.php';

?>