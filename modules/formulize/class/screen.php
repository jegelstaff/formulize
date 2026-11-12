<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2007 Freeform Solutions                  ##
###############################################################################
##                    XOOPS - PHP Content Management System                  ##
##                       Copyright (c) 2000 XOOPS.org                        ##
##                          <http://www.xoops.org/>                          ##
###############################################################################
##  This program is free software; you can redistribute it and/or modify     ##
##  it under the terms of the GNU General Public License as published by     ##
##  the Free Software Foundation; either version 2 of the License, or        ##
##  (at your option) any later version.                                      ##
##                                                                           ##
##  You may not change or alter any portion of this comment or credits       ##
##  of supporting developers from this source code or any supporting         ##
##  source code which is considered copyrighted (c) material of the          ##
##  original comment or credit authors.                                      ##
##                                                                           ##
##  This program is distributed in the hope that it will be useful,          ##
##  but WITHOUT ANY WARRANTY; without even the implied warranty of           ##
##  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            ##
##  GNU General Public License for more details.                             ##
##                                                                           ##
##  You should have received a copy of the GNU General Public License        ##
##  along with this program; if not, write to the Free Software              ##
##  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA ##
###############################################################################
##  Author of this file: Freeform Solutions                                  ##
##  Project: Formulize                                                       ##
###############################################################################

if (!defined("XOOPS_ROOT_PATH")) {
    die("XOOPS root path not defined");
}

require_once XOOPS_ROOT_PATH.'/kernel/object.php';
class formulizeScreen extends FormulizeObject {

	function __construct() {
        parent::__construct();
		$this->initVar('sid', XOBJ_DTYPE_INT, '', true);
        $this->initVar('screen_handle', XOBJ_DTYPE_TXTBOX, '', 255);
		$this->initVar('title', XOBJ_DTYPE_TXTBOX, '', true, 255);
		$this->initVar('fid', XOBJ_DTYPE_INT, '', true);
		$this->initVar('frid', XOBJ_DTYPE_INT, '', true);
		$this->initVar('type', XOBJ_DTYPE_TXTBOX, '', true, 100);
		$this->initVar('useToken', XOBJ_DTYPE_INT);
        $this->initVar('anonNeedsPasscode', XOBJ_DTYPE_INT);
        $this->initVar('theme', XOBJ_DTYPE_TXTBOX, '', true, 100);
		$this->initVar('rewriteruleAddress', XOBJ_DTYPE_TXTBOX, '', false, 255);
        $this->initVar('embedOrigins', XOBJ_DTYPE_TXTBOX, '', false, 1000);
        $this->initVar('rewriteruleElement', XOBJ_DTYPE_INT, '', true);
        // the screen's Appearance tab: the look the screen's page is shown with ('' is the site's look),
        // how wide the page can be ('look', as the look sets it, or 'full'), and the introductory text
        // the default templates print above the screen's content, which is markup the administrator
        // wrote, edited in a rich text editor or, when toptextcode is 1, as code
        $this->initVar('look', XOBJ_DTYPE_TXTBOX, '', false, 100);
        $this->initVar('pagewidth', XOBJ_DTYPE_TXTBOX, '', false, 10);
        $this->initVar('toptext', XOBJ_DTYPE_TXTAREA, '', false);
        $this->initVar('toptextcode', XOBJ_DTYPE_INT, 0, false);
	}

	/**
	 * What this type of screen is called in the admin UI. Each type of screen overrides this, and the
	 * types of screen there are, with their names, are read from the screen classes themselves (see
	 * formulize_getScreenTypes), so a new type of screen is offered wherever the types are listed
	 * without anything else being changed.
	 *
	 * @return string the name, for display
	 */
	public static function screenTypeName() {
		return '';
	}

	/**
	 * How wide a page this type of screen has when the site has not said: 'look', as wide as the look
	 * applied to the screen allows, or 'full', the full width of the window. The site's own choice for
	 * each type is in the Default Screen Widths setting (see formulize_defaultPageWidthForScreenType).
	 *
	 * @return string 'look' or 'full'
	 */
	public static function defaultPageWidth() {
		return 'look';
	}

	/**
	 * Whether this type of screen is offered where the types of screen are listed. A type that is kept
	 * only for screens that already exist says no unless the site has some.
	 *
	 * @return boolean
	 */
	public static function screenTypeOffered() {
		return true;
	}

	/**
	 * Whether this type of screen has introductory text: whether its templates have a place to print
	 * it. A type whose template is the whole of the screen, such as a template screen, has nowhere to
	 * put it but the template itself.
	 *
	 * @return boolean
	 */
	public static function screenTypeHasIntroductoryText() {
		return true;
	}

	/**
	 * The introductory text, ready to print above the screen's content: the markup the administrator
	 * wrote, translated if it has translations in it. It is not purified, because only an administrator
	 * who can edit the screen's templates can write it, and it is as much the screen's markup as they are.
	 *
	 * @return string the markup, or '' when there is none
	 */
	public function introductoryText() {
		$text = trim((string) $this->getVar('toptext', 'n'));
		return $text === '' ? '' : trans($text);
	}

    static function normalize_values($key, $value) {
        switch ($key) {
            case "customactions":
            case "decolumns":
            case "hiddencolumns":
            if (!is_array($value) && !is_array(unserialize($value))) {
                $value = serialize(array());
            }
            break;
        }
        return $value;
    }

    public function assignVar($key, $value) {
        parent::assignVar($key, self::normalize_values($key, $value));
    }

    public function setVar($key, $value, $not_gpc = false) {
        $value = self::normalize_values($key, $value);
        $settings = $this->elementColumnSettings();
        if(isset($settings[$key])) {
            $value = $this->elementReferencesAsIds($settings[$key], $value);
        }
        parent::setVar($key, $value, $not_gpc);
    }

    /**
     * The settings of this screen type that name elements, which are STORED as element ids.
     *
     * Storing ids is what keeps them out of the handle-rename maintenance: an id does not change when an
     * element is renamed, so there is nothing to keep in step. Lists and rows of columns are READ as handles,
     * though, because everything that consumes them - list and map rendering, saved views, the tools - speaks
     * handles, so the translation happens in setVar() and getVar() rather than at each of those places. A
     * setting that names one element is read as the id, which is what the admin page selects it by and what
     * _getElementObject() resolves.
     *
     * Overridden by each screen type that has such settings.
     *
     * @return array setting name => 'list' for a flat list, 'rows' for rows with the element in position 0,
     *   'value' for a single element
     */
    protected function elementColumnSettings() {
        return array();
    }

    /**
     * Read a setting, with any list or rows of columns it holds as handles, whatever is stored. Values from
     * before a setting became id-based are already handles and pass straight through, since the conversion
     * leaves anything that is not an element id alone - which also covers the metadata field names
     * (creation_uid, mod_datetime, etc) that are valid columns everywhere an element handle is.
     * Pass the 'n' format to get what is actually stored; insert() uses that to write ids back.
     */
    public function getVar($key, $format = 's') {
        $value = parent::getVar($key, $format);
        $settings = $this->elementColumnSettings();
        if(!isset($settings[$key]) OR $settings[$key] == 'value' OR $format == 'n' OR !is_array($value)) {
            return $value;
        }
        foreach($value as $index => $column) {
            if($settings[$key] == 'list') {
                $value[$index] = $this->columnReferenceAsHandle($column);
            } elseif(is_array($column) AND isset($column[0])) {
                $value[$index][0] = $this->columnReferenceAsHandle($column[0]);
            }
        }
        return $value;
    }

    /**
     * Turn the elements a setting names into element ids, for storing. Accepts the value as an array or as an
     * already-serialized string, because the admin save handlers pass one and the upsert apparatus the other.
     * @param string $kind 'list', 'rows' or 'value' - see elementColumnSettings()
     * @param mixed $value The setting.
     * @return mixed The setting, in the shape it came in.
     */
    private function elementReferencesAsIds($kind, $value) {
        if($kind == 'value') {
            if(is_string($value) AND $value !== '' AND !is_numeric($value)) {
                $converted = convertElementHandlesToElementIds(array($value));
                $value = (string) $converted[0];
            }
            return $value;
        }
        $wasSerialized = is_string($value);
        $columns = $wasSerialized ? @unserialize($value) : $value;
        if(!is_array($columns)) {
            return $value;
        }
        if($kind == 'list') {
            $columns = convertElementHandlesToElementIds($columns);
        } else {
            foreach($columns as $index => $column) {
                if(is_array($column) AND isset($column[0])) {
                    $converted = convertElementHandlesToElementIds(array($column[0]));
                    $columns[$index][0] = $converted[0];
                }
            }
        }
        return $wasSerialized ? serialize($columns) : $columns;
    }

    /**
     * Turn one stored column reference into a handle.
     *
     * Deliberately not convertElementIdsToElementHandles(): that one works out a single form id from the
     * first entry and converts the whole set within it, which is wrong here on two counts. A screen's columns
     * can come from several forms when a relationship is in effect, and the first column is often a metadata
     * field, which it cannot resolve a form from - it throws in that case.
     *
     * An id that no longer resolves is returned as it is rather than raising. This runs while a screen is
     * being rendered, so one column left over from a deleted element must not take the whole screen down; it
     * simply will not match a column and is ignored downstream.
     *
     * @param mixed $reference An element id, an element handle, or a metadata field name.
     * @return mixed The handle, or the reference unchanged.
     */
    protected function columnReferenceAsHandle($reference) {
        if(!is_numeric($reference)) {
            return $reference; // already a handle, or a metadata field name
        }
        static $handles = array();
        $reference = intval($reference);
        if(!isset($handles[$reference])) {
            $element_handler = xoops_getmodulehandler('elements', 'formulize');
            $elementObject = $element_handler->get($reference);
            $handles[$reference] = $elementObject ? $elementObject->getVar('ele_handle') : $reference;
        }
        return $handles[$reference];
    }

    public function form_id() {
        return $this->getVar("fid");
    }

    public function relationship_id() {
        return $this->getVar("frid");
    }

    function __get($name) {
        if (!isset($this->$name)) {
            if (method_exists($this, $name)) {
                $this->$name = $this->$name();
            } else {
                $this->$name = $this->getVar($name);
            }
        }
        return $this->$name;
    }

    // get the default template for this screen, either for theme or site wide
	function getDefaultTemplate($templateName, $theme="") {
		return getDefaultTemplate($templateName, $this->getVar('type'), $theme);
	}

    // get a custom template specified by admin user for this screen
    function getTemplate($templateName, $theme="") {
        if(!$theme) {
            global $xoopsConfig;
            $theme = $xoopsConfig['theme_set'];
        }
        static $templates = array();
        if (!isset($templates[$theme][$this->getVar('sid')][$templateName])) {
            // there is no template saved in memory, read it from the file
            $pathname = XOOPS_ROOT_PATH."/modules/formulize/templates/screens/".$theme."/".$this->getVar('sid')."/".$templateName.".php";
            if (file_exists($pathname)) {
                // a template that says nothing is no template, and the screen is drawn with the default
                $template = file_get_contents($pathname);
                $templates[$theme][$this->getVar('sid')][$templateName] = formulize_screenTemplateIsEmpty($template) ? '' : $template;
            } else {
                $templates[$theme][$this->getVar('sid')][$templateName] = $this->getVar($templateName) ? htmlspecialchars_decode($this->getVar($templateName), ENT_QUOTES) : $this->getVar($templateName);
                if ($templates[$theme][$this->getVar('sid')][$templateName] AND strlen($templates[$theme][$this->getVar('sid')][$templateName]) > 0) {
                    // the template content is stored in the database, but not the cache file
                    // database may have been copied from another site, so write to cache file
                    // Or, user has switched themes, so we initialze the theme file based on the last loaded theme file that was written to DB
                    $this->writeTemplateFile(htmlspecialchars_decode($templates[$theme][$this->getVar('sid')][$templateName], ENT_QUOTES), $templateName, $theme);
                }
            }
        }
        return $templates[$theme][$this->getVar('sid')][$templateName];
    }

    function writeTemplateFile($template_content, $template_name, $theme="") {
        if(!$theme) {
            global $xoopsConfig;
            $theme = $xoopsConfig['theme_set'];
        }
        $pathname = XOOPS_ROOT_PATH."/modules/formulize/templates/screens/".$theme."/".$this->getVar('sid')."/";
        $filename = $pathname.$template_name.".php";

        // a template that says nothing is the screen having no template of its own, and it is drawn with the
        // default, so it gets no file - and once none of the screen's templates are left, no folder either
        if (formulize_screenTemplateIsEmpty($template_content)) {
            if (file_exists($filename) AND !unlink($filename)) {
                error_log("ERROR: Could not remove empty template cache file: $filename");
                return false;
            }
            formulize_removeScreenTemplateFolderIfEmpty($pathname);
            return true;
        }

        // check if folder exists, if not, make it.
        if (!is_dir($pathname)) {
            mkdir($pathname, 0777, true);
        }

        if (!is_writable($pathname)) {
            chmod($pathname, 0777);
        }

        $success = file_put_contents($filename, $template_content);
        if (false === $success) {
            error_log("ERROR: Could not write to template cache file: $filename");
            return false;
        }

        return true;
    }

}

#[AllowDynamicProperties]
class formulizeScreenHandler {

	// marks the stand-in handle settleHandle gives a new screen that has nothing usable to make one from, so
	// that insert() can recognize it and swap in a handle made from the sid the database has just assigned
	const TEMPORARY_HANDLE_PREFIX = 'temporary_screen_handle_';

	// A copy of the typed screen object get() made last, for the type handler's get() of the same screen to take,
	// so code that still fetches a screen in two steps (through this handler, then again through the type's) does
	// not query for it twice. It lives only until the next get() of any kind, insert() or delete(), so it never
	// outlives the line of code that follows; and it is a copy, so changes made to the object get() returned
	// don't come along with it. Held with the sid and the class of the handler that made it, since only that
	// same handler's get() would have made the same object: array('sid' => ..., 'handlerClass' => ..., 'screen' => ...)
	private static $typedScreenHandoff = null;

	var $db;
	function __construct(&$db) {
		$this->db =& $db;
	}
	function &getInstance(&$db) {
		static $instance;
		if (!isset($instance)) {
			$instance = new formulizeScreenHandler($db);
		}
		return $instance;
	}

	// Called first by each type handler's get(): the object this handler's get() just made for this handler's
	// get() above, if it is this screen and this handler's class made it, else false. $this is the type handler
	// doing the get(), so a handler that extends another one only ever takes what it made itself. Either way the
	// handoff is used up.
	protected function takeTypedScreenHandoff($sid) {
		$handoff = self::$typedScreenHandoff;
		self::$typedScreenHandoff = null;
		if ($handoff AND $handoff['sid'] == $sid AND $handoff['handlerClass'] === get_class($this)) {
			return $handoff['screen'];
		}
		return false;
	}
	function create() {
		return new formulizeScreen();
	}

	// The properties a theme's screen-defaults.json cannot set: what the screen is, which form it belongs to, and how
	// it is wired to other screens. These come from where the screen is made, never from the theme.
	const THEME_DEFAULTS_EXCLUDED = array('sid', 'fid', 'frid', 'type', 'theme', 'title', 'screen_handle', 'viewentryscreen');

	/**
	 * The settings a new screen of one type starts with in a theme, from the screen-defaults.json file in the theme's
	 * folder, ie: themes/Lyris/screen-defaults.json. The file is keyed by screen type, then by property name:
	 * {"listOfEntries": {"editdestination": "drawer", ...}}, and lists every setting with a fixed value, so the file
	 * shows the whole picture. Formulize's own file, include/screen_defaults.json, has the same settings with
	 * Formulize's standard values, and fills in anything the theme's file leaves out: everything, for a theme with no
	 * file, or with a file that cannot be read as JSON.
	 *
	 * The words a screen starts with (button labels and the like, which are in the site's language) and what comes
	 * from the form (the title, the handle, the Add/Update button's text) are not in the files. They're set in code by
	 * each type's setDefault...ScreenVars method. The 8.30 update compares existing screens with what this returns.
	 *
	 * Properties the screen type doesn't have are left in, and are ignored when the defaults are applied
	 * (see applyDefaultScreenVars).
	 *
	 * @param string $screenType the internal screen type, ie: listOfEntries, multiPage, template, calendar, map
	 * @param string|null $theme the theme folder name. Defaults to the site's theme, as it is set on
	 * Admin > Appearance > Settings
	 * @return array property name => value
	 */
	public function getThemeScreenDefaults($screenType, $theme = null) {
		$core = self::readScreenDefaultsFile(XOOPS_ROOT_PATH . '/modules/formulize/include/screen_defaults.json');
		$coreDefaults = (isset($core[$screenType]) AND is_array($core[$screenType])) ? $core[$screenType] : array();
		if($theme === null) {
			// The stored setting, not $xoopsConfig['theme_set']: include/common.php swaps that for the admin theme
			// when a webmaster is signed in, and for a theme chosen in the session, but the defaults are the
			// defaults of the theme everyone sees. The config handler's cached copy is untouched by those swaps.
			$config_handler = xoops_gethandler('config');
			$systemConfig = $config_handler->getConfigsByCat(ICMS_CONF);
			$theme = isset($systemConfig['theme_set']) ? $systemConfig['theme_set'] : '';
		}
		$theme = (string) $theme;
		// a theme is a folder directly in themes/, so the name can never reach anywhere else
		if($theme === '' OR !preg_match('/^[A-Za-z0-9_\-]+$/', $theme) OR !is_dir(ICMS_THEME_PATH . '/' . $theme)) {
			return $coreDefaults;
		}
		$themeFile = self::readScreenDefaultsFile(ICMS_THEME_PATH . '/' . $theme . '/screen-defaults.json');
		$themeDefaults = (isset($themeFile[$screenType]) AND is_array($themeFile[$screenType])) ? $themeFile[$screenType] : array();
		return array_merge($coreDefaults, $themeDefaults);
	}

	/**
	 * A screen defaults file, decoded, or an empty array when there's no file or it can't be read as JSON. Each file
	 * is read once per request.
	 *
	 * @param string $file the full path of the file
	 * @return array screen type => property name => value
	 */
	private static function readScreenDefaultsFile($file) {
		static $files = array();
		if(!isset($files[$file])) {
			$files[$file] = array();
			if(is_file($file)) {
				$decoded = json_decode((string) file_get_contents($file), true);
				if(is_array($decoded)) {
					$files[$file] = $decoded;
				} else {
					error_log('Formulize: the screen defaults in '.$file.' could not be read as JSON, so they are not used. '.json_last_error_msg());
				}
			}
		}
		return $files[$file];
	}

	/**
	 * Set the default values on a new screen: the settings with fixed values from the site's theme (see
	 * getThemeScreenDefaults), and the rest from the type handler's setDefault...ScreenVars method, which works out the
	 * words, what comes from the form, and the screen's empty content (pages, columns, buttons, filters) and hands
	 * them here. A theme's file can override those too, except what THEME_DEFAULTS_EXCLUDED lists, so a theme can set
	 * a button's text to "" to start screens without that button.
	 *
	 * @param object $screen the new screen object
	 * @param array $defaults property name => value, the values worked out in code. Must include 'type', which says
	 * which of the theme's defaults apply.
	 * @return void
	 */
	protected function applyDefaultScreenVars($screen, $defaults) {
		foreach($this->getThemeScreenDefaults($defaults['type']) as $property => $value) {
			if(!isset($screen->vars[$property]) OR in_array($property, self::THEME_DEFAULTS_EXCLUDED, true) OR $value === null) {
				continue;
			}
			$isArrayProperty = ($screen->vars[$property]['data_type'] == XOBJ_DTYPE_ARRAY);
			if(is_array($value) !== $isArrayProperty) {
				continue; // a list where a single value belongs, or the other way round
			}
			$defaults[$property] = $value;
		}
		foreach($defaults as $property => $value) {
			// array properties are stored serialized, as they always have been in the defaults
			if(is_array($value) AND isset($screen->vars[$property]) AND $screen->vars[$property]['data_type'] == XOBJ_DTYPE_ARRAY) {
				$value = serialize($value);
			}
			$screen->setVar($property, $value);
		}
	}


	// check to see if a handle is unique within a form
	function isScreenHandleUnique($handle, $screen_id = 0) {
		$handle = formulizeScreen::sanitize_handle_name($handle);
		global $xoopsDB;
		$screen_id_condition = $screen_id ? " AND sid != " . intval($screen_id) : "";
		$sql = "SELECT count(screen_handle) FROM " . $xoopsDB->prefix("formulize_screen") . " WHERE screen_handle = '" . formulize_db_escape($handle) . "' $screen_id_condition";
		if(!$res = $xoopsDB->query($sql)) {
			print "Error: could not verify uniqueness of handle '$handle'";
		} else {
			$row = $xoopsDB->fetchRow($res);
			if($row[0] == 0) { // zero rows found with that handle in this form
				return true;
			} else {
				return false;
			}
		}
	}

	function makeHandleUnique($handle, $sid = 0) {
		$firstUniqueCheck = true;
		$handle = formulizeScreen::sanitize_handle_name($handle);
		while (!$uniqueCheck = $this->isScreenHandleUnique($handle, $sid)) {
			if ($firstUniqueCheck AND $sid) {
				$handle = $handle . "_".$sid;
				$firstUniqueCheck = false;
			} else {
				$handle = $handle . "_copy";
			}
		}
		return $handle;
	}

	/**
	 * Settle the handle a screen is going to be saved with, and put it on the object, sanitized and unique.
	 *
	 * The handle is whatever was asked for, and otherwise made from the handle the screen already has or from
	 * its title. Whichever one is used goes through makeHandleUnique, so the handle left on the object is
	 * always sanitized: nothing a caller supplies reaches the database as it was typed. If none of the
	 * candidates has anything usable in it, the last resort is the screen's own sid, and for a screen that
	 * does not have one yet, a temporary handle that insert() replaces with the sid once the database has
	 * assigned it. A screen can never be left without a handle: screen_handle is a required var, so a screen
	 * with an empty handle fails cleanVars() and cannot be saved at all.
	 *
	 * insert() calls this itself, so callers only need to call it when a handle was explicitly asked for and
	 * has to win over the screen's title.
	 *
	 * @param formulizeScreen $screen The screen object, updated in place.
	 * @param string $requestedHandle Optional. The handle that was explicitly asked for, if any.
	 * @return void
	 */
	function settleHandle($screen, $requestedHandle = '') {
		$sid = intval($screen->getVar('sid'));
		// The candidates, in order of preference: whatever was asked for, then the handle the screen already
		// has, except that a new screen tries its title first. That matches the admin interface, where the
		// handle field is filled in from the title as it is typed, and it means a new screen prefers its title
		// over a handle seeded by setDefaultFormScreenVars/setDefaultListScreenVars from the form's singular
		// or plural name: those defaults are for the screens a form is created with, which have no title of
		// their own to work from, whereas a screen made deliberately has been given one.
		// 'n' because these are being sanitized, not displayed: the default escaping would turn the apostrophe
		// in a title like "Bob's Screen" into &#039; first, and the handle would come out as bob039s_screen
		$currentHandle = $screen->getVar('screen_handle', 'n');
		$candidates = $sid ? array($requestedHandle, $currentHandle) : array($requestedHandle, $screen->getVar('title', 'n'), $currentHandle);
		// the screen's sid is the last resort, and a temporary handle stands in for it until insert() has one
		$candidates[] = $sid ? $sid : self::TEMPORARY_HANDLE_PREFIX.uniqid();
		foreach($candidates as $candidate) {
			// a candidate has to sanitize to at least one letter or digit, not merely to a non-empty result:
			// sanitize_handle_name turns spaces into underscores before stripping, so a title of "!!! ???"
			// sanitizes to "__" rather than to nothing, and that would be taken as a usable handle and stop
			// the fallbacks behind it from ever being reached
			if(preg_match('/[a-z0-9]/', formulizeScreen::sanitize_handle_name($candidate))) {
				$screen->setVar('screen_handle', $this->makeHandleUnique($candidate, $sid));
				return;
			}
		}
	}

	// returns an array of screen objects
    function &getObjects($criteria = null, $fid = 0, $appid = -1, $sort = null, $order = null, $paged = false, $offset = -1, $limit = 20) {
        $sql = "SELECT * FROM " . $this->db->prefix("formulize_screen") . " AS screentable";
        if(is_object($criteria)) {
            $sql .= " WHERE " . $criteria->render();
            if (intval($fid) > 0) {
                $sql .= " AND fid=" . intval($fid);
            }
            if ($appid > 0) {
            	$sql .= " AND EXISTS(SELECT 1 FROM ".$this->db->prefix("formulize_application_form_link")." as linktable WHERE linktable.appid=" . $appid . " AND linktable.fid=screentable.fid)";
            } else if ($appid === 0) {
            	$sql .= " AND NOT EXISTS(SELECT 1 FROM ".$this->db->prefix("formulize_application_form_link")." as linktable WHERE linktable.appid>" . $appid . " AND linktable.fid=screentable.fid)";
            }
        } else {
            if (intval($fid) > 0) {
                $sql .= " WHERE fid=" . intval($fid);

                if ($appid > 0) {
                	$sql .= " AND EXISTS(SELECT 1 FROM ".$this->db->prefix("formulize_application_form_link")." as linktable WHERE linktable.appid=" . $appid . " AND linktable.fid=screentable.fid)";
                } else if ($appid === 0) {
            		$sql .= " AND NOT EXISTS(SELECT 1 FROM ".$this->db->prefix("formulize_application_form_link")." as linktable WHERE linktable.appid>" . $appid . " AND linktable.fid=screentable.fid)";
            	}
            } else if ($appid > 0) {
            	$sql .= " WHERE EXISTS(SELECT 1 FROM ".$this->db->prefix("formulize_application_form_link")." as linktable WHERE linktable.appid=" . $appid . " AND linktable.fid=screentable.fid)";
            } else if ($appid === 0) {
            	$sql .= " WHERE NOT EXISTS(SELECT 1 FROM ".$this->db->prefix("formulize_application_form_link")." as linktable WHERE linktable.appid>" . $appid . " AND linktable.fid=screentable.fid)";
            }
        }
        if($sort == null) {
        	$sort = "fid, title";
        }
        if($order == null) {
        	$order = "ASC";
        }
        $sql .= " order by " . $sort . " " . $order;
        if($paged) {
        	$sql .= " LIMIT " . $limit;
        	$begin = $offset < 2 ? 0 : ($offset - 1) * $limit;
        	$sql .= " OFFSET " . $begin;
        }
        $screens = array();
        if($result = $this->db->query($sql)) {
        while($array = $this->db->fetchArray($result)) {
            $screen = $this->create();
            $screen->assignVars($array);
            $screens[] = $screen;
            unset($screen);
            }
        }
        return $screens;
    }

	/**
	 * Returns an array of screen objects for a given form
	 * @param mixed $formObjectOrIdentifier Either a formulizeForm object or a form identifier (id or handle)
	 * @throws Exception if the form identifier is invalid or if there are problems retrieving the screens
	 * @return array An array of formulizeScreen objects, keyed by screen id
	 */
	function getScreensForForm($formObjectOrIdentifier) {
		if (!is_a($formObjectOrIdentifier, 'formulizeForm')) {
			$form_handler = xoops_getModuleHandler('forms', 'formulize');
			$formObjectOrIdentifier = $form_handler->get($formObjectOrIdentifier);
		}
		if(is_a($formObjectOrIdentifier, 'formulizeForm')) {
			$fid = $formObjectOrIdentifier->getVar('fid');
		} else {
			throw new Exception("Invalid form identifier when trying to get screens for a form.");
		}
		$screens = array();
		if(is_numeric($fid) AND $fid > 0) {
			$sql = 'SELECT * FROM '.$this->db->prefix('formulize_screen').' WHERE fid='.intval($fid);
			if (!$result = $this->db->query($sql)) {
					throw new Exception("Could not retrieve screens for form $fid from the database.");
			}
			while($array = $this->db->fetchArray($result)) {
				$screen = new formulizeScreen();
				$screen->assignVars($array);
				$screens[$screen->getVar('sid')] = $screen;
				unset($screen);
			}
		}
		return $screens;
	}

	// Returns the screen as an object of its own type (formulizeMultiPageScreen, etc), carrying that type's settings
	// as well as the ones every screen has, the way the elements handler returns elements of their own type. So one
	// get() through this handler is enough; there is no need to get the screen again through its type's handler.
	// Falls back to a plain formulizeScreen when the type has no class, or the screen has no row of the type's settings.
	// Only this handler hands off to the type's: a type handler that doesn't have its own get() runs this code too,
	// and gets the plain object rather than handing off to itself forever.
	function get($sid_or_screen_handle) {
		self::$typedScreenHandoff = null;
		if (is_numeric($sid_or_screen_handle)) {
			$sql = 'SELECT * FROM '.$this->db->prefix('formulize_screen').' WHERE sid='.intval($sid_or_screen_handle);
		} else {
			$sql = "SELECT * FROM ".$this->db->prefix('formulize_screen')." WHERE screen_handle='".formulize_db_escape($sid_or_screen_handle)."'";
		}
		if (!$result = $this->db->query($sql)) {
				return false;
		}
		$numrows = $this->db->getRowsNum($result);
		if ($numrows == 1) {
				$row = $this->db->fetchArray($result);
				$type = (string) $row['type'];
				if (get_class($this) === 'formulizeScreenHandler'
					AND preg_match('/^[a-z][A-Za-z0-9]*$/', $type)
					AND file_exists(XOOPS_ROOT_PATH.'/modules/formulize/class/'.$type.'Screen.php')
					AND $typeHandler = xoops_getmodulehandler($type.'Screen', 'formulize')
					AND $typedScreen = $typeHandler->get($row['sid'])) {
					self::$typedScreenHandoff = array('sid' => intval($row['sid']), 'handlerClass' => get_class($typeHandler), 'screen' => clone $typedScreen);
					return $typedScreen;
				}
				$screen = new formulizeScreen();
				$screen->assignVars($row);
				return $screen;
		}
		return false;
	}


	function delete($sid, $type) {
		self::$typedScreenHandoff = null;
		// a form screen's intro and thanks text are files named for its handle, so the handle is needed before the row goes
		$screenHandle = '';
		if($type == 'multiPage' AND $handleResult = $this->db->query("SELECT screen_handle FROM ".$this->db->prefix("formulize_screen")." WHERE sid=".intval($sid))) {
			$handleRow = $this->db->fetchArray($handleResult);
			$screenHandle = $handleRow ? (string) $handleRow['screen_handle'] : '';
		}
 		$sql1 ="DELETE FROM " . $this->db->prefix("formulize_screen") . " WHERE sid=" . intval($sid);
		$sql2 = "DELETE FROM " . $this->db->prefix("formulize_screen_".strtolower($type)) . " WHERE sid=" . intval($sid);
		if(!$result = $this->db->query($sql1)) {
			return false;
		}
		if(!$result = $this->db->query($sql2)) {
			return false;
		}
		$this->deleteTemplateFiles($sid);
		if($screenHandle !== '') {
			xoops_getmodulehandler('multiPageScreen', 'formulize')->deleteTextFiles($screenHandle);
		}
		return true;
	}

	// TO BE CALLED FROM WITHIN THE CHILD CLASS AND THEN RETURNS SCREEN OBJECT, WHICH WILL HAVE THE CORRECT sid IN PLACE NOW
	function insert($screen, $force=false) {
		self::$typedScreenHandoff = null;
		if (!is_a($screen, 'formulizeScreen')) {
            return false;
        }
        // No handle reaches the database unsanitized or duplicated, whichever caller put it on the object:
        // the handle the screen is carrying is what this insert is asking for, and settleHandle sanitizes it
        // and makes it unique. A screen with nothing usable in its handle falls back to its title, and after
        // that to a temporary handle that becomes its sid below.
        $this->settleHandle($screen, $screen->getVar('screen_handle', 'n'));
        if (!$screen->cleanVars()) {
            return false;
        }
        foreach ($screen->cleanVars as $k => $v) {
            ${$k} = $v;
        }
        // A new screen that hasn't been given a page width starts with the one the site sets for its type of
        // screen, whoever is making it: the admin UI, an AI tool, or a form making its default screens.
        if ($pagewidth !== 'full' AND $pagewidth !== 'look') {
            $pagewidth = $sid ? 'look' : formulize_defaultPageWidthForScreenType($type);
            $screen->assignVar('pagewidth', $pagewidth);
        }
        $toptextcode = $toptextcode ? 1 : 0;
        if (!$sid) {
            $sql = sprintf("INSERT INTO %s (screen_handle, title, fid, frid, type, useToken, anonNeedsPasscode, theme, rewriteruleAddress, rewriteruleElement, embedOrigins, look, pagewidth, toptext, toptextcode) VALUES (%s, %s, %u, %d, %s, %u, %u, %s, %s, %u, %s, %s, %s, %s, %u)", $this->db->prefix('formulize_screen'), $this->db->quoteString($screen_handle), $this->db->quoteString($title), $fid, $frid, $this->db->quoteString($type), $useToken, $anonNeedsPasscode, $this->db->quoteString($theme), $this->db->quoteString($rewriteruleAddress), $rewriteruleElement, $this->db->quoteString($embedOrigins), $this->db->quoteString($look), $this->db->quoteString($pagewidth), $this->db->quoteString($toptext), $toptextcode);
        } else {
            $sql = sprintf("UPDATE %s SET screen_handle = %s, title = %s, fid = %u, frid = %d, type = %s, useToken = %u, anonNeedsPasscode = %u, theme = %s, rewriteruleAddress = %s, rewriteruleElement = %u, embedOrigins = %s, look = %s, pagewidth = %s, toptext = %s, toptextcode = %u WHERE sid = %u", $this->db->prefix('formulize_screen'), $this->db->quoteString($screen_handle), $this->db->quoteString($title), $fid, $frid, $this->db->quoteString($type), $useToken, $anonNeedsPasscode, $this->db->quoteString($theme), $this->db->quoteString($rewriteruleAddress), $rewriteruleElement, $this->db->quoteString($embedOrigins), $this->db->quoteString($look), $this->db->quoteString($pagewidth), $this->db->quoteString($toptext), $toptextcode, $sid);
        }
				if($force){
					$result = $this->db->queryF($sql);
				} else {
        	$result = $this->db->query($sql);
				}
        if (!$result) {
            print 'DB error: '.$this->db->error() . '<br>'.$sql.'<br>';
            return false;
        }
        if (!$sid) {
            $sid = $this->db->getInsertId();
            $screen->assignVar('sid', $sid);
        }
        // The screen had nothing usable for a handle in what it was given or in its title, so it went into the
        // database under the temporary handle settleHandle gave it, and now that it has a sid it can have the
        // handle it should have had. Only the one column needs to go back, so this is a targeted update.
        if (strpos($screen_handle, self::TEMPORARY_HANDLE_PREFIX) === 0) {
            $screen->setVar('screen_handle', $this->makeHandleUnique($sid, $sid));
            $handleSql = sprintf("UPDATE %s SET screen_handle = %s WHERE sid = %u", $this->db->prefix('formulize_screen'), $this->db->quoteString($screen->getVar('screen_handle', 'n')), $sid);
            if ($force) {
                $handleResult = $this->db->queryF($handleSql);
            } else {
                $handleResult = $this->db->query($handleSql);
            }
            if (!$handleResult) {
                print 'DB error: '.$this->db->error() . '<br>'.$handleSql.'<br>';
                return false;
            }
        }
        return $sid;
	}

    function writeTemplateToFile($text, $filename, $screen) {
        return $screen->writeTemplateFile($text, $filename, $screen->getVar('theme'));
    }

    // FINDS AND RETURNS A NEW TITLE FOR A CLONED SCREEN
    // Pattern for naming is "[Original Screen Name] - Cloned", "[Original Screen Name] - Cloned 2", etc.
    function titleForClonedScreen($sid) {
        $foundTitle = 1;
        $titleCounter = 0;
        $screenObject = $this->get($sid);
        $title = $screenObject->getVar('title');
        while ($foundTitle) {
            $titleCounter++;
            if ($titleCounter > 1) {
                // add a number to the new form name to ensure it is unique
                $newtitle = sprintf(_FORM_MODCLONED, $title)." $titleCounter";
            } else {
                $newtitle = sprintf(_FORM_MODCLONED, $title);
            }
            $titleCheckSQL = "SELECT title FROM " . $this->db->prefix("formulize_screen") . " WHERE title = '".formulize_db_escape($newtitle)."'";
            $titleCheckResult = $this->db->query($titleCheckSQL);
            $foundTitle = $this->db->getRowsNum($titleCheckResult);
        }
        return $newtitle; // use the last searched title (because it was not found)
    }

    // CLONE AND INSERT CLONED FORM INTO FORMULIZE_SCREEN TABLE
    // Takes the screen id of the screen being cloned
    // Returns the id of the newly cloned screen inserted into table
    // The clone gets a handle of its own, made from its new title, since a handle names one screen. It gets no
    // address, since the address the original has is the original's - two screens on one address would leave
    // which one it opens to chance.
    function insertCloneIntoScreenTable($sid, $newtitle) {
        $getrow = q("SELECT * FROM " . $this->db->prefix("formulize_screen") . " WHERE sid = $sid");
        $insert_sql = "INSERT INTO " . $this->db->prefix("formulize_screen") . " (";
        $start = 1;
        foreach($getrow[0] as $field=>$value) {
            if($field == "sid") { continue; }
            if(!$start) { $insert_sql .= ", "; }
            $start = 0;
            $insert_sql .= $field;
        }
        $insert_sql .= ") VALUES (";
        $start = 1;

        foreach($getrow[0] as $field=>$value) {
            if($field == "sid") { continue; }
            if($field == "title") { $value = $newtitle; }
            if($field == "screen_handle") { $value = $this->makeHandleUnique($newtitle); }
            if($field == "rewriteruleAddress") { $value = ""; }
            if(!$start) { $insert_sql .= ", "; }
            $start = 0;
            $insert_sql .= "'".formulize_db_escape($value)."'";
        }
        $insert_sql .= ")";
        if(!$result = $this->db->query($insert_sql)) {
            throw new Exception("Could not clone screen '$newtitle'. SQL: $insert_sql. ".$this->db->error());
        }
        $newsid = $this->db->getInsertId();
        return $newsid;
    }

    // INSERT CLONED SCREEN INTO SCREEN-TYPE SPECIFIC TABLE
    // Takes the screen id of the screen being cloned, the screen id of the newly cloned screen,
    // and the title of the newly cloned screen.
    // Inserts the newly cloned screen into the specific table for that type of screen
    // (i.e. form, listOfEntries, or multiPage).
    function insertCloneIntoScreenTypeTable($sid, $newsid, $newtitle, $tablename) {
        $getrow = q("SELECT * FROM " . $this->db->prefix($tablename) . " WHERE sid = $sid");
        $insert_sql = "INSERT INTO " . $this->db->prefix($tablename) . " (";
        $start = 1;
        foreach($getrow[0] as $field=>$value) {
            if($field == "formid" OR $field == "listofentriesid" OR $field == "multipageid" OR $field == "templateid" OR $field == "calendarid") { continue; }
            if(!$start) { $insert_sql .= ", "; }
            $start = 0;
            $insert_sql .= $field;
        }
        $insert_sql .= ") VALUES (";
        $start = 1;

        foreach($getrow[0] as $field=>$value) {
            if($field == "formid" OR $field == "listofentriesid" OR $field == "multipageid" OR $field == "templateid"  OR $field == "calendarid") { continue; }
            if($field == "sid") { $value = $newsid; }
            if($field == "title") { $value = $newtitle; }
            if(!$start) { $insert_sql .= ", "; }
            $start = 0;
            $insert_sql .= "'".formulize_db_escape($value)."'";
        }
        $insert_sql .= ")";
        if(!$result = $this->db->query($insert_sql)) {
            throw new Exception("Could not clone screen '$newtitle' into $tablename. SQL: $insert_sql. ".$this->db->error());
        }
        $this->copyTemplateFiles($sid, $newsid);
        return $result;
    }

    /**
     * Give a cloned screen copies of the original's own template files, in every theme that has them -
     * templates/screens/<theme>/<sid>/ - since they are the templates themselves, and are kept nowhere else.
     * @param int $sid The screen being cloned.
     * @param int $newsid The clone.
     * @return void
     */
    function copyTemplateFiles($sid, $newsid) {
        $screensDir = XOOPS_ROOT_PATH."/modules/formulize/templates/screens/";
        foreach((array) glob($screensDir."*/".intval($sid), GLOB_ONLYDIR) as $sourceDir) {
            $targetDir = dirname($sourceDir)."/".intval($newsid);
            if(!is_dir($targetDir) AND !mkdir($targetDir, 0777, true)) {
                error_log("ERROR: Could not make the folder for the templates of cloned screen $newsid: $targetDir");
                continue;
            }
            foreach((array) glob($sourceDir."/*") as $sourceFile) {
                if(is_file($sourceFile) AND !copy($sourceFile, $targetDir."/".basename($sourceFile))) {
                    error_log("ERROR: Could not copy template file $sourceFile for cloned screen $newsid");
                }
            }
        }
    }

    /**
     * Remove a deleted screen's own template files, in every theme that has them - templates/screens/<theme>/<sid>/ -
     * and the folders they were in, so they are not left behind, or picked up by a new screen that gets the same sid.
     * @param int $sid The screen that has been deleted.
     * @return void
     */
    function deleteTemplateFiles($sid) {
        if(!$sid = intval($sid)) {
            return;
        }
        $screensDir = XOOPS_ROOT_PATH."/modules/formulize/templates/screens/";
        foreach((array) glob($screensDir."*/".$sid, GLOB_ONLYDIR) as $folder) {
            foreach((array) glob($folder."/*") as $file) {
                if(is_file($file) AND !unlink($file)) {
                    error_log("ERROR: Could not delete template file $file of deleted screen $sid");
                }
            }
            formulize_removeScreenTemplateFolderIfEmpty($folder);
        }
    }

}

/**
 * The types of screen there are, read from the screen classes: each type is a file in class/ named for
 * the type and ending in Screen.php (listOfEntriesScreen.php is the listOfEntries type), whose class
 * says what the type is called and how it starts out (see formulizeScreen::screenTypeName and the
 * methods after it). So a new type of screen is listed wherever the types are, with nothing else to
 * change. A type that is not offered on this site (formulizeScreen::screenTypeOffered) is left out.
 *
 * @return array type => array('name' => the type's name, 'pagewidth' => its default page width,
 *               'introtext' => whether it has introductory text), in the order the types are usually
 *               listed in: lists and forms first, then the rest by name
 */
function formulize_getScreenTypes() {
	static $types = null;
	if ($types !== null) {
		return $types;
	}
	$types = array();
	foreach (glob(XOOPS_ROOT_PATH . '/modules/formulize/class/*Screen.php') ?: array() as $file) {
		$type = substr(basename($file), 0, -strlen('Screen.php'));
		// the base class's file is screen.php, which a case-insensitive file system matches too
		if ($type === '' OR !preg_match('/^[a-z][A-Za-z0-9]*$/', $type)) {
			continue;
		}
		include_once $file;
		$class = 'formulize' . ucfirst($type) . 'Screen';
		if (!class_exists($class) OR !is_subclass_of($class, 'formulizeScreen') OR !$class::screenTypeOffered()) {
			continue;
		}
		$types[$type] = array(
			'name' => $class::screenTypeName() ? $class::screenTypeName() : $type,
			'pagewidth' => $class::defaultPageWidth() == 'full' ? 'full' : 'look',
			'introtext' => $class::screenTypeHasIntroductoryText() ? true : false,
		);
	}
	$first = array_flip(array('listOfEntries', 'multiPage', 'form'));
	uksort($types, function ($a, $b) use ($first, $types) {
		$aFirst = isset($first[$a]) ? $first[$a] : 99;
		$bFirst = isset($first[$b]) ? $first[$b] : 99;
		return $aFirst != $bFirst ? $aFirst - $bFirst : strcasecmp($types[$a]['name'], $types[$b]['name']);
	});
	return $types;
}

/**
 * The page width a new screen of a type starts with: the site's choice for the type, in the Default
 * Screen Widths setting, or if the site hasn't made one, the type's own default.
 *
 * @param string $type the type of screen, eg: 'listOfEntries'
 * @return string 'look' (as wide as the look allows) or 'full' (the full width of the window)
 */
function formulize_defaultPageWidthForScreenType($type) {
	static $siteDefaults = null;
	if ($siteDefaults === null) {
		$config_handler = xoops_gethandler('config');
		$formulizeConfig = $config_handler->getConfigsByCat(0, getFormulizeModId());
		$siteDefaults = (isset($formulizeConfig['formulizeScreenWidthDefaults']) AND is_array($formulizeConfig['formulizeScreenWidthDefaults']))
			? $formulizeConfig['formulizeScreenWidthDefaults']
			: array();
	}
	if (isset($siteDefaults[$type]) AND ($siteDefaults[$type] === 'full' OR $siteDefaults[$type] === 'look')) {
		return $siteDefaults[$type];
	}
	$class = 'formulize' . ucfirst((string) $type) . 'Screen';
	if (preg_match('/^[a-z][A-Za-z0-9]*$/', (string) $type) AND file_exists(XOOPS_ROOT_PATH . '/modules/formulize/class/' . $type . 'Screen.php')) {
		include_once XOOPS_ROOT_PATH . '/modules/formulize/class/' . $type . 'Screen.php';
	}
	return (class_exists($class) AND is_subclass_of($class, 'formulizeScreen') AND $class::defaultPageWidth() == 'full') ? 'full' : 'look';
}

/**
 * Where the default template for a type of screen can be found: the theme's own default, which takes
 * precedence, and the system default that is used when the theme has none. The files may not exist.
 *
 * @param string $templateName the name of the template, eg: 'toptemplate'
 * @param string $type the type of screen, eg: 'listOfEntries'
 * @param string $theme the theme to look in. Defaults to the site's current theme.
 * @return array the full path to the theme default, then the full path to the system default
 */
function getDefaultTemplatePaths($templateName, $type, $theme="") {
	global $xoopsConfig;
	$theme = $theme ? $theme : $xoopsConfig['theme_set'];
	return array(
		XOOPS_ROOT_PATH."/modules/formulize/templates/screens/".$theme."/default/".$type."/".$templateName.".php",
		XOOPS_ROOT_PATH."/modules/formulize/templates/screens/default/".$type."/".$templateName.".php"
	);
}

/**
 * The contents of the default template for a type of screen: the theme's own default if it has one,
 * otherwise the system default. Results are cached for the rest of the request.
 *
 * @param string $templateName the name of the template, eg: 'toptemplate'
 * @param string $type the type of screen, eg: 'listOfEntries'
 * @param string $theme the theme to look in. Defaults to the site's current theme.
 * @return string the template's contents, or an empty string if neither default template exists
 */
function getDefaultTemplate($templateName, $type, $theme="") {
	global $xoopsConfig;
	$theme = $theme ? $theme : $xoopsConfig['theme_set'];
	static $cachedDefaultTemplates = array();
	if(!isset($cachedDefaultTemplates[$theme][$type][$templateName])) {
		list($themeDefaultPath, $systemDefaultPath) = getDefaultTemplatePaths($templateName, $type, $theme);
		$template = '';
		if (file_exists($themeDefaultPath)) {
			$template = file_get_contents($themeDefaultPath);
		} elseif (file_exists($systemDefaultPath)) {
			$template = file_get_contents($systemDefaultPath);
		}
		$cachedDefaultTemplates[$theme][$type][$templateName] = $template;
	}
	return $cachedDefaultTemplates[$theme][$type][$templateName];
}

/**
 * Does a screen's template say nothing? Empty, or holding only the opening PHP tag that a code editor starts with.
 * A screen with a template like that has no template of its own, and is drawn with the default.
 *
 * @param string $template The template's contents.
 * @return bool True if there is nothing in it.
 */
function formulize_screenTemplateIsEmpty($template) {
	return trim(removeOpeningPHPTag((string) $template)) === '';
}

/**
 * Remove a screen's folder of templates, templates/screens/<theme>/<sid>/, if there is nothing left in it.
 *
 * @param string $folder The folder's path.
 * @return void
 */
function formulize_removeScreenTemplateFolderIfEmpty($folder) {
	if (is_dir($folder) AND !array_diff((array) scandir($folder), array('.', '..'))) {
		rmdir($folder);
	}
}

// returns the actual template we will use at render time. Reverts to default template if no specific template is set for this screen.
function getTemplateToRender($templateName, $screenOrScreenType="", $theme="") {
	if(is_object($screenOrScreenType) AND is_a($screenOrScreenType, 'formulizeScreen')) {
		$template = $screenOrScreenType->getTemplate($templateName, $theme);
		if(!$template) {
			$template = $screenOrScreenType->getDefaultTemplate($templateName, $theme);
			if(!$template) {
				list($themeDefaultPath, $systemDefaultPath) = getDefaultTemplatePaths($templateName, $screenOrScreenType->getVar('type'), $theme);
				error_log('Formulize Error: could not locate a '.$templateName.' for screen '.$screenOrScreenType->getVar('sid').'. No screen template set, and no theme default at: '.$themeDefaultPath.'. And no system default at: '.$systemDefaultPath);
			}
		}
		return $template;
	} else {
		if(!$screenOrScreenType) { exit('Cannot getTemplateToRender for non-screen without a Type specified'); }
		return getDefaultTemplate($templateName, $screenOrScreenType);
	}
}

function getTemplatePath($screenOrScreenType, $templateName, $subdir = '') {
	global $xoopsConfig;
	$paths = array();
	$type  = $screenOrScreenType;
	$file  = ($subdir ? $subdir . '/' : '') . $templateName . '.php';
	if(is_object($screenOrScreenType) AND is_a($screenOrScreenType, 'formulizeScreen')) {
		$paths[] = XOOPS_ROOT_PATH."/modules/formulize/templates/screens/".$xoopsConfig['theme_set']."/".$screenOrScreenType->getVar('sid')."/".$file;
		$type = $screenOrScreenType->getVar('type');
	}
	$paths[] = XOOPS_ROOT_PATH."/modules/formulize/templates/screens/".$xoopsConfig['theme_set']."/default/".$type."/".$file;
	$paths[] = XOOPS_ROOT_PATH."/modules/formulize/templates/screens/default/".$type."/".$file;
	foreach($paths as $path) {
		// a template that says nothing is no template, and the next one along is used
		if(file_exists($path) AND filesize($path) AND !formulize_screenTemplateIsEmpty(file_get_contents($path))) {
			return $path;
		}
	}
	return null;
}

// Shim for backward compatibility — prefer getTemplatePath($s, $name, 'variables') for new callers.
function getVariableTemplatePath($screenOrScreenType, $variableName) {
	return getTemplatePath($screenOrScreenType, $variableName, 'variables');
}

// Renders a variable sub-template, exposing $templateVars as local variables. Returns the rendered HTML or false if no template found.
function renderVariableTemplate($variableName, $screenOrScreenType, $templateVars = array()) {
	$path = getTemplatePath($screenOrScreenType, $variableName, 'variables');
	if(!$path) {
		return false;
	}
	extract($templateVars);
	ob_start();
	include $path;
	return ob_get_clean();
}
