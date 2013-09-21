<?php
/**
 * Installer paths configuration page
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
 * @version		$Id: page_pathsettings.php 10825 2010-12-03 00:01:23Z skenow $
 */
/**
 *
 */
require_once 'common.inc.php';

if (!defined( 'XOOPS_INSTALL' ) )	exit();

$wizard->setPage( 'pathsettings' );
$pageHasForm = true;
$pageHasHelp = true;



class PathStuffController {
	var $xoopsRootPath = '';
	var $xoopsTrustPath = '';
	var $xoopsUrl = '';

	var $validRootPath = false;
	var $validTrustPath = false;
	var $validUrl = false;

	var $permErrors = array();

	function PathStuffController() {
		if (isset( $_SESSION['settings']['ROOT_PATH'] )) {
			$this->xoopsRootPath = $_SESSION['settings']['ROOT_PATH'];
		} else {
			$path = str_replace( "\\", "/", @realpath( '../' ) );
			if (file_exists( "$path/mainfile.php" )) {
				$this->xoopsRootPath = $path;
			}
		}
		if (isset( $_SESSION['settings']['TRUST_PATH'] )) {
			$this->xoopsTrustPath = $_SESSION['settings']['TRUST_PATH'];
		} else {
			$web_root = dirname( $this->xoopsRootPath );
			$arr = explode('/',$web_root);
			$web_root = '';
			for ($i = 0; $i < count($arr)-1; $i++) {
				$web_root .= $arr[$i].'/';
			}

			$docroot = resolveDocumentRoot();

			$this->xoopsTrustPath = $docroot . substr( md5( time() ), 0, 15);
		}
		if (isset( $_SESSION['settings']['URL'] )) {
			$this->xoopsUrl = $_SESSION['settings']['URL'];
		} else {
			$path = $GLOBALS['wizard']->baseLocation();
			$this->xoopsUrl = substr( $path, 0, strrpos( $path, '/' ) );
		}
	}

	function execute() {
		$this->readRequest();
		$valid = $this->validate();
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$_SESSION['settings']['ROOT_PATH'] = $this->xoopsRootPath;
			$_SESSION['settings']['TRUST_PATH'] = $this->xoopsTrustPath;
			$_SESSION['settings']['URL'] = $this->xoopsUrl;
			if ($valid) {
				$GLOBALS['wizard']->redirectToPage( '+1' );
			} else {
				$GLOBALS['wizard']->redirectToPage( '+0' );
			}
		}
	}

	function readRequest() {
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$request = $_POST;
			/*
			 $request = xo_input_get_args( INPUT_POST, array(
				'ROOT_PATH'	=> FILTER_SANITIZE_STRING,
				'URL'		=> array(
				'filter'	=> FILTER_VALIDATE_URL,
				'flags'		=> FILTER_FLAG_SCHEME_REQUIRED | FILTER_FLAG_HOST_REQUIRED,
				),
				) );*/
			if (isset($request['ROOT_PATH'])) {
				$request['ROOT_PATH'] = str_replace( "\\", "/", $request['ROOT_PATH'] );
				if (substr( $request['ROOT_PATH'], -1 ) == '/') {
					$request['ROOT_PATH'] = substr( $request['ROOT_PATH'], 0, -1 );
				}
				$this->xoopsRootPath = $request['ROOT_PATH'];
			}
			if (isset($request['TRUST_PATH'])) {
				$request['TRUST_PATH'] = str_replace( "\\", "/", $request['TRUST_PATH'] );
				if (substr( $request['TRUST_PATH'], -1 ) == '/') {
					$request['TRUST_PATH'] = substr( $request['TRUST_PATH'], 0, -1 );
				}
				$this->xoopsTrustPath = $request['TRUST_PATH'];
			}
			if (isset( $request['URL'] )) {
				if (substr( $request['URL'], -1 ) == '/') {
					$request['URL'] = substr( $request['URL'], 0, -1 );
				}
				$this->xoopsUrl = $request['URL'];
			}
		}
	}

	function validate() {
		if ($this->checkRootPath()) {
			$this->checkPermissions();
		}
		if ($this->checkTrustPath()) {
			$this->checkTrustPathPermissions();
		}
		$this->validUrl = !empty($this->xoopsUrl);
		return ( $this->validRootPath && $this->validTrustPath && $this->validUrl && empty( $this->permErrors ) );
	}

	/**
	 * Check if the specified folder is a valid "XOOPS_ROOT_PATH" value
	 * @return bool
	 */
	function checkRootPath() {
		if (@is_dir( $this->xoopsRootPath ) && @is_readable( $this->xoopsRootPath )) {
			@include_once "$this->xoopsRootPath/include/version.php";
			if (file_exists( "$this->xoopsRootPath/mainfile.php" ) && defined( 'XOOPS_VERSION' )) {
				return $this->validRootPath = true;
			}
		}
		return $this->validRootPath = false;
	}

	/**
	 * Check if the specified folder is a valid "XOOPS_ROOT_PATH" value
	 * @return bool
	 */
	function checkTrustPath() {
		if (@is_dir( $this->xoopsTrustPath ) && @is_readable( $this->xoopsTrustPath )) {
			return $this->validTrustPath = true;
		}
		return $this->validTrustPath = false;
	}

	function createTrustPath() {
		if (@icms_core_Filesystem::mkdir($this->xoopsTrustPath, 0777, '', array('[', '?', '"', '<', '>', '|', ' ' ))) {
			if (@is_dir( $this->xoopsTrustPath ) && @is_readable( $this->xoopsTrustPath )) {
				$_SESSION['settings']['TRUST_PATH'] = $this->xoopsTrustPath;
				return $this->validTrustPath = true;
			}
		}
		return $this->validTrustPath = false;
	}

	function checkPermissions() {
		$paths = array( 'mainfile.php', 'uploads', 'modules', 'templates_c', 'cache' );
		$errors = array();
		foreach ( $paths as $path) {
			$errors[$path] = $this->makeWritable( "$this->xoopsRootPath/$path" );
		}
		if (in_array( false, $errors )) {
			$this->permErrors = $errors;
			return false;
		}
		return true;
	}

	function checkTrustPathPermissions() {
		$errors = array();
		$errors['trustpath'] = $this->makeWritable( "$this->xoopsTrustPath" );
		if (in_array( false, $errors )) {
			$this->permErrors = $errors;
			return false;
		}
		return true;
	}


	/**
	 * Write-enable the specified file/folder
	 * @param string $path
	 * @param string $group
	 * @param bool $recurse
	 * @return false on failure, method (u-ser,g-roup,w-orld) on success
	 */
	function makeWritable( $path, $group = false, $recurse = false) {
		if (!file_exists( $path )) {
			return false;
		}
		$perm = @is_dir( $path ) ? 6 : 7;
		if (@!is_writable($path)) {
			// First try using owner bit
			@chmod( $path, octdec( '0' . $perm . '00' ) );
			clearstatcache();
			if (!@is_writable( $path ) && $group !== false) {
				// If group has been specified, try using the group bit
				@chgrp( $path, $group );
				@chmod( $path, octdec( '0' . $perm . $perm . '0' ) );
			}
			clearstatcache();
			if (!@is_writable( $path )) {
				@chmod( $path, octdec( '0' . $perm . $perm . $perm ) );
			}
		}
		clearstatcache();
		if (@is_writable( $path )) {
			$info = stat( $path );
			//echo $path . ' : ' . sprintf( '%o', $info['mode'] ) . '....';
			if ($info['mode'] & 0002) {
				return 'w';
			} elseif ($info['mode'] & 0020) {
				return 'g';
			}
			return 'u';
		}
		return false;
	}
	/**
	 * Find the webserved Group ID
	 * @return int
	 */
	function findServerGID() {
		$name = tempnam( '/non-existent/', 'XOOPS' );
		$group = 0;
		if ($name) {
			if (touch( $name )) {
				$group = filegroup( $name );
				unlink( $name );
				return $group;
				//$info = posix_getgrgid( $group );
			}
		}
		return false;
	}
}

function resolveDocumentRoot() {
	$current_script = dirname($_SERVER['SCRIPT_NAME']);
	$current_path   = dirname($_SERVER['SCRIPT_FILENAME']);

	/* work out how many folders we are away from document_root
	 by working out how many folders deep we are from the url.
	 this isn't fool proof */
	$adjust = explode("/", $current_script);
	$adjust = count($adjust);

	/* move up the path with ../ */
	$traverse = str_repeat("../", $adjust);
	$adjusted_path = sprintf("%s/%s", $current_path, $traverse);

	/* real path expands the ../'s to the correct folder names */
	$rootp = @realpath($adjusted_path);

	// a fix for Windows slashes
	$rootp = str_replace("\\","/",$rootp);
	$lastchar = substr($rootp,strlen($rootp)-1,1);

	if ($lastchar != '/' && $lastchar != '\\') {
		$rootp .= '/';
	}

	return $rootp;
}

function genRootCheckHtml( $valid) {
	if ($valid) {
		return '<img src="img/yes.png" alt="Success" class="rootimg" />' .  sprintf( XOOPS_FOUND, XOOPS_VERSION);
	}  else {
		return '<img src="img/no.png" alt="Error" class="rootimg" />' .ERR_NO_XOOPS_FOUND;
	}
}


function genTrustPathCheckHtml( $valid) {
	if ($valid) {
		return '<img src="img/yes.png" alt="Success" class="rootimg" />' . _INSTALL_TRUST_PATH_FOUND;
	}  else {
		return '<img src="img/no.png" alt="Error" class="rootimg" />' . _INSTALL_ERR_NO_TRUST_PATH_FOUND;
	}
}

function genCreateTrustPathHtml($valid) {
	if (!$valid) {
		?>
<p><?php echo TRUST_PATH_NEED_CREATED_MANUALLY . '</p>'; ?>
<button type="button"
	onclick="createTrustPath(this.form.elements.trustpath.value);"><?php echo BUTTON_REFRESH; ?></button>
		<?
	} else {
		?>
<p><?php echo TRUST_PATH_SUCCESSFULLY_CREATED . '</p>';
	}
}

$ctrl = new PathStuffController();

if ($_SERVER['REQUEST_METHOD'] == 'GET' && @$_GET['action'] == 'checkrootpath') {
	$ctrl->xoopsRootPath = $_GET['path'];
	echo genRootCheckHtml( $ctrl->checkRootPath() );
	exit();
}
if ($_SERVER['REQUEST_METHOD'] == 'GET' && @$_GET['action'] == 'checktrustpath') {
	$ctrl->xoopsTrustPath = $_GET['path'];
	echo genTrustPathCheckHtml( $ctrl->checkTrustPath() );
	exit();
}
if ($_SERVER['REQUEST_METHOD'] == 'GET' && @$_GET['action'] == 'createtrustpath') {
	$ctrl->xoopsTrustPath = $_GET['path'];
	echo genCreateTrustPathHtml( $ctrl->createTrustPath() );
	exit();
}
$ctrl->execute();
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	return;
}

ob_start();
?> <script type="text/javascript" src="pathsettings.js"></script>

<div class="blokz">
<fieldset>
<h3><?php echo _INSTALL_WEB_LOCATIONS; ?></h3>
<label for="url"><?php echo _INSTALL_WEB_LOCATIONS_LABEL; ?></label>
<div class="xoform-help"><?php echo XOOPS_URL_HELP; ?></div>
<div class="clear">&nbsp;</div>
<input type="text" name="URL" id="url"
	value="<?php echo $ctrl->xoopsUrl; ?>" /></fieldset>
<br />
</div>
<div class="bloky">
<fieldset>
<h3><?php echo _INSTALL_PHYSICAL_PATH; ?></h3>
<label for="rootpath"><?php echo XOOPS_ROOT_PATH_LABEL; ?></label>
<div class="xoform-help"><?php echo XOOPS_ROOT_PATH_HELP; ?></div>
<div class="clear">&nbsp;</div>
<input type="text" name="ROOT_PATH" id="rootpath"
	value="<?php echo $ctrl->xoopsRootPath; ?>" /> <span id="rootpathimg"><?php echo genRootCheckHtml( $ctrl->validRootPath ); ?></span>
<?php if ($ctrl->validRootPath && !empty( $ctrl->permErrors )) { ?>
<div id="rootperms"><?php echo CHECKING_PERMISSIONS . '<br /><p>' . ERR_NEED_WRITE_ACCESS . '</p>'; ?>
<ul class="diags">
<?php foreach ( $ctrl->permErrors as $path => $result) {
	if ($result) {
		echo '<li class="success">' . sprintf( IS_WRITABLE, $path ) . '</li>';
	} else {
		echo '<li class="failure">' . sprintf( IS_NOT_WRITABLE, $path ) . '</li>';
	}
} ?>
	<button type="button" id="permrefresh" /><?php echo BUTTON_REFRESH; ?></button>
</ul>
<?php } else { echo '<div id="rootperms">'.CHECKING_PERMISSIONS .'<br /><ul class="diags"><li class="success">'.ALL_PERM_OK.'</li></ul></div>';} ?>

</fieldset>
<br />
</div>
<div class="blokx">
<fieldset>
<h3><?php echo _INSTALL_TRUST_PATH; ?></h3>
<label for="trustpath"><?php echo _INSTALL_TRUST_PATH_LABEL; ?></label>
<div class="xoform-help"><?php echo _INSTALL_TRUST_PATH_HELP; ?></div>
<div class="clear">&nbsp;</div>
<input type="text" name="TRUST_PATH" id="trustpath"
	value="<?php echo $ctrl->xoopsTrustPath; ?>" /> <span id="trustpathimg"><?php echo genTrustPathCheckHtml( $ctrl->validTrustPath ); ?></span>
<?php if (!$ctrl->validTrustPath && $ctrl->xoopsTrustPath != '') { ?>
<div id="trustperms">
<p><?php echo TRUST_PATH_VALIDATE . '</p>'; ?>
<button type="button" id="createtrustpath"><?php echo BUTTON_CREATE_TUST_PATH; ?></button>

</div>
<?php
}?></fieldset>
</div>
<?php
$content = ob_get_contents();
ob_end_clean();

include 'install_tpl.php';

?>