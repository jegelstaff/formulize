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
        } else {
					$error = true;
				}
    } else {
        @chmod( $path, 0775 );
        $error = !is_writeable( $path );
    }
    return xoDiag( $error ? -1 : 1, $error ? 'Not writable' : 'Writable' );
}

function successImg($success_val) {
    $img_name = $success_val >= 0 ? "yes" : "no";
    return '<img src="img/' . $img_name . '.png" alt="Success?" class="rootimg" />';
}

// everything Formulize needs, which the installer will not go past this page without - see XoopsInstallWizard::requirements()
$requirements = $wizard->requirements();
$pageStopsHere = !$wizard->requirementsMet();
$requirementStr = array();
foreach ($requirements as $key => $requirement) {
    $status = $requirement[1] ? 1 : -1;
    $requirementStr[$key] = xoDiag($status, $key == 'php' ? phpversion() : '') . successImg($status);
}

ob_start();
?>

    <?php if ($pageStopsHere) { ?>
    <div class="error"><p><?php echo REQUIREMENTS_MISSING; ?></p></div>
    <?php } ?>
    <fieldset>
        <h3><?php echo REQUIREMENTS; ?></h3>
        <h4><?php echo SERVER_API; ?>:&nbsp; <?php echo php_sapi_name(); ?> <img
                    src="img/yes.png" alt="Success" class="rootimg" /></h4>
        <div class="clear">&nbsp;</div>
        <?php foreach ($requirements as $key => $requirement) { ?>
        <h4><?php echo $requirement[0]; ?>:&nbsp; <?php echo $requirementStr[$key]; ?></h4>
        <div class="clear">&nbsp;</div>
        <?php } ?>
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

		<fieldset>
		<h3><?php echo FILE_PERMISSIONS; ?></h3>
	<table class="diags">

	<?php
    $writableFilesAndFolders = json_decode(file_get_contents('../writableFilesAndFolders.json'));
    foreach ( $writableFilesAndFolders->paths as $path) {
        ?>
	<tr>
		<td scope="row"><nobr><?php echo $path; ?></nobr></td>
		<td><?php echo xoDiagIfWritable( $path ); ?></td>
	</tr>
	<?php } ?>
	</table>
	</fieldset>
<?php
$content = ob_get_contents();
ob_end_clean();

include 'install_tpl.php';

