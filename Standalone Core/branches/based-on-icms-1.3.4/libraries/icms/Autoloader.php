<?php
/**
 * ImpressCMS Autoloader
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @category	ICMS
 * @since		1.3
 * @author		Marc-André Lanciault (aka marcan) <mal@inboxintl.com>
 * @version		$Id: Autoloader.php 11448 2011-11-21 16:37:13Z fiammy $
 */
class icms_Autoloader {
	/**
	 * Paths of known global repositories
	 * @var array
	 */
	static protected $globalRepositories = array();
	/**
	 * Paths of known namespace repositories
	 * @var array
	 * @internal Each entry is stored as array(strlen($ns), $path), to avoid having to call strlen repeatedly during autoload
	 */
	static protected $localRepositories = array();
	/**
	 * Imported namespaces
	 * @var array
	 */
	static protected $imported = array();
	/**
	 * Whether setup has already been called or not
	 * @var bool
	 */
	static protected $initialized = FALSE;

	/**
	 * Initialize the autoloader, and register its autoload method
	 * @return void
	 */
	static public function setup() {
		if (!self::$initialized) {
			self::register(dirname(dirname(__FILE__)));
			spl_autoload_register(array('icms_Autoloader', 'autoload'));
			spl_autoload_register(array('icms_Autoloader', 'registerLegacy'));
			self::$initialized = TRUE;
		}
	}

	/**
	 * Split a fully qualified class name into Namespace and Local class name
	 *
	 * Supports both PHP 5.3 proj\sub\Class and 5.2 proj_sub_Class naming convention.
	 *
	 * @param string $class
	 * @return array
	 */
	static public function split($class) {
		if (FALSE === ($pos = strrpos($class, "\\"))) {
			$pos = strrpos($class, "_");
		}
		if ($pos) {
			$ns = substr($class, 0, $pos);
			$local = substr($class, $pos + 1);
		} else {
			$ns = "";
			$local = $class;
		}
		return array($ns, $local);
	}

	/**
	 * Register/unregister a class repository
	 *
	 * The autoload system will look for classes in all registered repositories one after the other.
	 *
	 * @param string $path Repository physical path
	 * @param string $namespace If specified, all classes of the repository belong to this namespace
	 * @return void
	 */
	static public function register($path, $namespace = "") {
		if ($namespace) {
			self::$localRepositories[ $namespace ] = array(strlen($namespace), $path);
		} else {
			self::$globalRepositories[] = $path;
		}
	}

	/**
	 * Import a namespace global elements (constants and functions)
	 *
	 * If a namespace has functions or constants, they must be put in a file called "namespace.php"
	 * that will be read by this function.
	 *
	 * @param string $namespace
	 * @param bool $required Whether to throw an exception or not if the namespace file is not found
	 * @return bool
	 */
	static public function import($namespace, $required = TRUE) {
		if (!isset(self::$imported[ $namespace ])) {
			$nspath = self::classPath($namespace, TRUE, DIRECTORY_SEPARATOR . "namespace.php");
			if ($nspath) {
				include_once($nspath . DIRECTORY_SEPARATOR . "namespace.php");
				return self::$imported[$namespace] = TRUE;
			}
			self::$imported[$namespace] = FALSE;
		}
		if (!self::$imported[$namespace] && $required) {
			throw new RuntimeException("No namespace file for namespace '$namespace'.");
		}
		return self::$imported[$namespace];
	}

	/**
	 * Locate and load the appropriate file, based on a class name
	 *
	 * @param string $class
	 * @return bool
	 */
	static public function autoload($class) {
		if ($path = self::classPath($class)) {
			list($ns, $local) = self::split($class);
			include_once "$path.php";
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Attempt to find a class path by scanning registered repositories
	 *
	 * @param string $class Name of the class to find
	 * @param bool $useIncludePath If to search include paths too
	 */
	static public function classPath($class, $useIncludePath = FALSE, $ext = ".php") {
		$classPath = str_replace(array("\\", "_"), DIRECTORY_SEPARATOR, $class);
		// First, try local repositories
		if (strpos($class, "\\") || strpos($class, "_")) {
			foreach (self::$localRepositories as $name => $info) {
				list($len, $path) = $info;
				if (!strncmp($name . "\\", $class, $len+1) || !strncmp($name . "_", $class, $len+1)) {
					$localPath = substr($classPath, $len + 1);
					if (file_exists($path . DIRECTORY_SEPARATOR . $localPath . $ext)) {
						return $path . DIRECTORY_SEPARATOR . $localPath;
					}
				}
			}
		}
		// Search global repositories
		foreach(self::$globalRepositories as $path) {
			if (file_exists($path . DIRECTORY_SEPARATOR . $classPath . $ext)) {
				return $path . DIRECTORY_SEPARATOR . $classPath;
			}
		}
		// Search include path
		// On Windows include paths use "/" as directory_separator, even if added to set_include_path with anti-slashes
		// We do this to make sure the string we get compensates for that
		if ($useIncludePath) {
			foreach (explode(PATH_SEPARATOR, get_include_path()) as $path) {
				if (file_exists($path . DIRECTORY_SEPARATOR . $classPath . $ext)) {
					return (DIRECTORY_SEPARATOR != "/" ? str_replace("/", DIRECTORY_SEPARATOR, $path) : $path) . DIRECTORY_SEPARATOR . $classPath;
				}
			}
		}
		return FALSE;
	}

	/**
	 *  This function maps the legacy classes that were included in common.php and xoopsformloader.php
	 *  Rather than including all the legacy files, this defines where PHP should look to use these classes
	 *  It's ugly, but so was the code we're getting rid of
	 */
	static public function registerLegacy($class) {
		$class = strtolower($class);
		$legacyClassPath = array(
		    "database" 						=> "/class/database/database.php",
		    "icmsdatabase" 					=> "/class/database/database.php",
		    "xoopsdatabase" 				=> "/class/database/database.php",
			"mytextsanitizer" 				=> "/class/module.textsanitizer.php",
			"icmspreloadhandler"			=> "/kernel/icmspreloadhandler.php",
			"xoopsmodule" 					=> "/kernel/module.php",
			"xoopsmodulehandler"			=> "/kernel/module.php",
			"xoopsmemberhandler"			=> "/kernel/member.php",
			"icmspreloadhandler"			=> "/kernel/icmspreloadhandler.php",
			"icmspreloaditem" 				=> "/kernel/icmspreloadhandler.php",
			"icmskernel" 					=> "/kernel/icmskernel.php",
			"icmssecurity" 					=> "/class/xoopssecurity.php",
			"xoopssecurity" 				=> "/class/xoopssecurity.php",
			"xoopslogger" 					=> "/class/logger.php",
			"xoopsdatabasefactory" 			=> "/class/database/databasefactory.php",
			"icmsdatabasefactory" 			=> "/class/database/databasefactory.php",
			"xoopsobject" 					=> "/kernel/object.php",
			"xoopsobjecthandler" 			=> "/kernel/object.php",
			"xoopslists" 					=> "/class/xoopslists.php",
			"icmslists" 					=> "/class/xoopslists.php",
			"xoopsthemeform"				=> "/class/xoopsform/themeform.php",
			"xoopsformhidden"		 		=> "/class/xoopsform/formhidden.php",
			"xoopsformtext" 		 	 	=> "/class/xoopsform/formtext.php",
			"xoopsformelement"				=> "/class/xoopsform/formelement.php",
			"xoopsform"						=> "/class/xoopsform/form.php",
			"xoopsformlabel"				=> "/class/xoopsform/formlabel.php",
			"xoopsformselect"				=> "/class/xoopsform/formselect.php",
			"xoopsformpassword"				=> "/class/xoopsform/formpassword.php",
			"xoopsformbutton"				=> "/class/xoopsform/formbutton.php",
			"xoopsformcheckbox"				=> "/class/xoopsform/formcheckbox.php",
			"xoopsformfile"					=> "/class/xoopsform/formfile.php",
			"xoopsformradio"				=> "/class/xoopsform/formradio.php",
			"xoopsformradioyn"				=> "/class/xoopsform/formradioyn.php",
			"xoopsformselectcountry"		=> "/class/xoopsform/formselectcountry.php",
			"xoopsformselecttimezone"		=> "/class/xoopsform/formselecttimezone.php",
			"xoopsformselectlang"			=> "/class/xoopsform/formselectlang.php",
			"xoopsformselectgroup"			=> "/class/xoopsform/formselectgroup.php",
			"xoopsformselectuser"			=> "/class/xoopsform/formselectuser.php",
			"xoopsformselecttheme"			=> "/class/xoopsform/formselecttheme.php",
			"xoopsformselectmatchoption"	=> "/class/xoopsform/formselectmatchoption.php",
			"xoopsformtext"					=> "/class/xoopsform/formtext.php",
			"xoopsformtextarea"				=> "/class/xoopsform/formtextarea.php",
			"xoopsformdhtmltextarea"		=> "/class/xoopsform/formdhtmltextarea.php",
			"xoopsformelementtray"			=> "/class/xoopsform/formelementtray.php",
			"xoopsthemeform"				=> "/class/xoopsform/themeform.php",
			"xoopssimpleform"				=> "/class/xoopsform/simpleform.php",
			"xoopsformtextdateselect"		=> "/class/xoopsform/formtextdateselect.php",
			"xoopsformdatetime"				=> "/class/xoopsform/formdatetime.php",
			"xoopsformhiddentoken"			=> "/class/xoopsform/formhiddentoken.php",
			"xoopsformcolorpicker"			=> "/class/xoopsform/formcolorpicker.php",
			"xoopsformselecteditor"			=> "/class/xoopsform/formselecteditor.php",
			"xoopsformcaptcha"				=> "/class/xoopsform/formcaptcha.php",
			"icmsformcaptcha"				=> "/class/xoopsform/formcaptcha.php",
			"criteriacompo"                 => "/class/criteria.php",
			"criteria"                      => "/class/criteria.php",
			"criteriaelement"               => "/class/criteria.php",
			"icmspersistableobjecthandler"	=> "/kernel/icmspersistableobjecthandler.php",
			"icmspersistableobject"			=> "/kernel/icmspersistableobject.php",
			"icmspersistableregistry"		=> "/kernel/icmspersistableregistry.php",
			"icmspersistablecolumn"			=> "/kernel/icmspersistabletable.php",
			"icmspersistabletable"			=> "/kernel/icmspersistabletable.php",
			"errorhandler"					=> "/class/module.errorhandler.php",
			"icmsmetagen"					=> "/kernel/icmsmetagen.php",
		);
		if (in_array($class, array_keys($legacyClassPath))) {
			include_once ICMS_ROOT_PATH . $legacyClassPath[$class];
		}
	}
}