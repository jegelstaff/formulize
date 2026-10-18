<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2011 Freeform Solutions                  ##
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

require_once XOOPS_ROOT_PATH . "/modules/formulize/class/elements.php"; // you need to make sure the base element class has been read in first!

class formulizeFileUploadElement extends formulizeElement {

    var $needsDataType;
    var $overrideDataType;
    var $hasData;
    var $name;
    var $adminCanMakeRequired;
    var $alwaysValidateInputs;
		public static $category = "selectors";

    function __construct() {
        $this->name = "File Upload Box";
        $this->hasData = true; // set to false if this is a non-data element, like the subform or the grid
        $this->needsDataType = false; // set to false if you're going force a specific datatype for this element using the overrideDataType
        $this->overrideDataType = "text"; // use this to set a datatype for the database if you need the element to always have one (like 'date').  set needsDataType to false if you use this.
        $this->adminCanMakeRequired = true; // set to true if the webmaster should be able to toggle this element as required/not required
        $this->alwaysValidateInputs = true; // set to true if you want your custom validation function to always be run.  This will override any required setting that the webmaster might have set, so the recommendation is to set adminCanMakeRequired to false when this is set to true.
        parent::__construct();
    }

	/**
	 * Static function to provide the mcp server with the schema for the properties that can be used with the create_form_element and update_form_element tools
	 * Concerned with the properties for the ele_value property of the element object
	 * Follows the convention of properties used publically (MCP, Public API, etc).
	 * @param bool|int $update True if this is being called as part of building the properties for Updating, as opposed to properties for Creating. Default is false (Creating).
	 * @return string The schema for the properties that can be used with the create_form_element and update_form_element tools
	 */
	public static function mcpElementPropertiesDescriptionAndExamples($update = false) {
		$defaultExtensions = formulizeFileUploadElementHandler::DEFAULT_EXTENSIONS;
		$blockedExtensions = implode(', ', formulizeFileUploadElementHandler::BLOCKED_EXTENSIONS);
		return
"**Element:** File Upload Box (fileUpload)
**Description:** Lets users attach one file to an entry. The file can be downloaded from the entry in forms and in lists of entries. Images are shown as thumbnails.
**Properties:**
- allowedExtensions (string, a comma separated list of the file extensions users may upload, eg: \"pdf,docx,jpg\". Default is \"$defaultExtensions\". An empty string allows every type of file, which is not recommended. These types are always refused, whatever this setting says: $blockedExtensions.)
- maxFileSize (number, the largest file users may upload, in megabytes. Default is 10.)
- allowDirectLinks (boolean, default false. When true, files are served from a plain web address, so anyone who knows the address can download the file without logging in. When false, files are only available through links in Formulize, which check that the user has permission to see the entry. Only set this to true if the files are meant to be public.)
**Examples:**
- A box for uploading a resume as a PDF or Word document: { allowedExtensions: \"pdf,doc,docx\" }
- A box for uploading a photo, up to 5 MB: { allowedExtensions: \"jpg,jpeg,png,gif,webp\", maxFileSize: 5 }
- A box for uploading files that will be shared publicly: { allowedExtensions: \"pdf\", allowDirectLinks: true }";
	}

}

#[AllowDynamicProperties]
class formulizeFileUploadElementHandler extends formulizeElementsHandler {

    var $db;
    var $clickable; // used in formatDataForList
    var $striphtml; // used in formatDataForList
    var $length; // used in formatDataForList

    function __construct($db) {
        $this->db =& $db;
    }

    // the file types offered by default, when a new element is made
    const DEFAULT_EXTENSIONS = 'doc,docx,xls,xlsx,ppt,pptx,csv,txt,pdf,jpg,jpeg,gif,png,webp,odt,ods,odp';

    // File types that are never accepted, whatever an element allows, because some web server somewhere might
    // run them as a program. This is the canonical list: uploads/.htaccess (and the fallback copy in
    // include/uploads_execution_probe.php, and the copy printed in docs/uploads_not_executable.md) deny the
    // same set at the server, and tests/blocked_extensions_sync_test.php fails if any of them drift from this.
    // A deny list can never be complete - it is a backstop. The real gates are the per-element allowed-types
    // list, serving non-direct-link files through download.php (readfile, never executed), and the server
    // being configured not to run anything in the uploads folder at all.
    const BLOCKED_EXTENSIONS = array(
        'php','php3','php4','php5','php7','php8','phps','phtml','pht','phar',  // PHP
        'cgi','fcgi','pl','py','pyw','wsgi','rb','erb','rhtml','lua','sh',     // CGI, Perl, Python, Ruby, Lua, shell
        'jsp','jspx',                                                          // Java
        'asp','aspx','ashx','asmx','ascx','asax','cshtml','vbhtml','config',   // ASP / ASP.NET (incl. web.config on IIS)
        'cfm','cfc','cfml',                                                    // ColdFusion
        'shtml','shtm','stm',                                                  // Server Side Includes
        'htaccess','htpasswd'                                                  // Apache config that could re-enable any of the above
    );

    /**
     * The blocked extensions as an alternation for a regular expression, eg: php|php3|cgi. This is how the
     * uploads/.htaccess FilesMatch rule denies the same set that BLOCKED_EXTENSIONS refuses on upload, from
     * one source. tests/blocked_extensions_sync_test.php uses this to check the shipped .htaccess has not drifted.
     * @return string
     */
    public static function blockedExtensionsRegex() {
        return implode('|', self::BLOCKED_EXTENSIONS);
    }

    function create() {
        return new formulizeFileUploadElement();
    }

	public function getDefaultEleValue() {
		return array(
			0 => 10, // maximum file size in megabytes
			1 => self::DEFAULT_EXTENSIONS, // allowed extensions, blank means all
			2 => 0 // whether files can be linked to directly
		);
	}

	/**
	 * Validate properties for this element type, based on the structure used publically (MCP, Public API, etc).
	 * The description in the mcpElementPropertiesDescriptionAndExamples static method on the element class, follows this convention
	 * properties are the contents of the ele_value property on the object
	 * @param array $properties The properties to validate
	 * @param array $ele_value The ele_value settings for this element, if applicable. Should be set by the caller, to the current ele_value settings of the element, if this is an existing element.
	 * @param int|string|object $elementIdentifier The element id, handle or object of the element for which we're validating the properties.
	 * @return array An array of properties ready for the object, ie: ele_value, plus notes for the AI assistant about settings that may not do what was intended
	 * @throws Exception if a property has a value that cannot be used
	 */
	public function validateEleValuePublicAPIProperties($properties, $ele_value = [], $elementIdentifier = null) {
		$notes = array();
		if(array_key_exists('allowedExtensions', $properties)) {
			$extensions = $this->normalizeExtensionList($properties['allowedExtensions']);
			$blocked = array_values(array_intersect($extensions, self::BLOCKED_EXTENSIONS));
			if($blocked) {
				$extensions = array_values(array_diff($extensions, self::BLOCKED_EXTENSIONS));
				if(empty($extensions)) {
					// an empty list means every type is allowed, which is the opposite of what was asked for
					throw new Exception("allowedExtensions only contained types that are never accepted (".implode(', ', $blocked)."). Provide at least one other extension.");
				}
				$notes[] = "These extensions were left out of allowedExtensions, because files of these types are never accepted: ".implode(', ', $blocked).".";
			}
			$ele_value[1] = implode(',', $extensions);
			if($ele_value[1] === '') {
				$notes[] = "allowedExtensions is empty, so users can upload every type of file (apart from the types that are never accepted). To restrict it, provide a list of extensions.";
			}
		}
		if(array_key_exists('maxFileSize', $properties)) {
			if(!is_numeric($properties['maxFileSize']) OR $properties['maxFileSize'] <= 0) {
				throw new Exception("maxFileSize must be a number of megabytes greater than 0.");
			}
			$ele_value[0] = $properties['maxFileSize'] + 0;
			$phpLimit = self::phpUploadLimitBytes();
			if($phpLimit AND $ele_value[0] * 1048576 > $phpLimit) {
				$notes[] = "This server's PHP settings limit uploads from a browser to ".round($phpLimit / 1048576, 1)." MB, so larger files will be refused when users upload them in a form, even though the element allows up to ".$ele_value[0]." MB.";
			}
		}
		if(array_key_exists('allowDirectLinks', $properties)) {
			$ele_value[2] = filter_var($properties['allowDirectLinks'], FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
		}
		if(empty($ele_value[0])) {
			$ele_value[0] = 10; // the same default the admin UI enforces
		}
		$result = array('ele_value' => $ele_value);
		if($notes) {
			$result['notes'] = $notes;
		}
		return $result;
	}

	/**
	 * Report this element's settings using the same properties that validateEleValuePublicAPIProperties accepts,
	 * so that an AI assistant can read a setting, change it, and send it back unaltered in shape.
	 * @param array $element The element's fields, keyed by column name, with serialized fields already unserialized
	 * @return array The properties, keyed by their public names
	 */
	public static function readPublicAPIProperties($element) {
		$ele_value = $element['ele_value'] ?? array();
		return array(
			'allowedExtensions' => (string) ($ele_value[1] ?? ''),
			'maxFileSize' => (isset($ele_value[0]) AND is_numeric($ele_value[0]) AND $ele_value[0] > 0) ? $ele_value[0] + 0 : 10,
			'allowDirectLinks' => !empty($ele_value[2]),
		);
	}

	/**
	 * Turn a list of extensions, as a comma separated string or an array, into a clean list: lowercase, no dots or spaces, no duplicates
	 * @param string|array $extensions The extensions
	 * @return array The cleaned up extensions
	 */
	function normalizeExtensionList($extensions) {
		if(!is_array($extensions)) {
			$extensions = explode(',', (string) $extensions);
		}
		$cleaned = array();
		foreach($extensions as $extension) {
			$extension = strtolower(str_replace(array(' ', '.'), '', trim((string) $extension)));
			if($extension !== '' AND !in_array($extension, $cleaned)) {
				$cleaned[] = $extension;
			}
		}
		return $cleaned;
	}

	/**
	 * The largest upload PHP will accept from a browser, which is the smaller of upload_max_filesize and post_max_size
	 * @return int The limit in bytes, or 0 if there is no limit
	 */
	static function phpUploadLimitBytes() {
		$limits = array();
		foreach(array('upload_max_filesize', 'post_max_size') as $setting) {
			$bytes = self::iniSizeToBytes(ini_get($setting));
			if($bytes > 0) {
				$limits[] = $bytes;
			}
		}
		return $limits ? min($limits) : 0;
	}

	/**
	 * Convert a size from php.ini, like 8M or 1G, into bytes
	 * @param string $size The size as written in php.ini
	 * @return int The number of bytes. 0 or less means no limit.
	 */
	static function iniSizeToBytes($size) {
		$size = trim((string) $size);
		if(!preg_match('/^(-?\d+)\s*([KMG]?)/i', $size, $matches)) {
			return 0;
		}
		$bytes = (int) $matches[1];
		switch(strtoupper($matches[2])) { // each case falls through to the next, multiplying as it goes
			case 'G':
				$bytes *= 1024;
			case 'M':
				$bytes *= 1024;
			case 'K':
				$bytes *= 1024;
		}
		return $bytes;
	}

	/**
	 * Check a file against the element's settings, and the types that are never accepted. Used for files uploaded
	 * through a form, and files arriving another way (see include/fileintake.php).
	 * @param object $element The element object
	 * @param string $fileName The name of the file, which provides the extension
	 * @param string $filePath Where the file is on disk now, so its contents can be checked
	 * @param string $claimedMimeType Optional. The type that whoever sent the file says it is, ie: what the browser reported
	 * @return bool|string True if the file is acceptable, otherwise the reason it is not: 'no_extension', 'blocked', 'not_allowed', 'html_mismatch' or 'type_mismatch'
	 */
	function checkFileAgainstElementSettings($element, $fileName, $filePath, $claimedMimeType = '') {
		// mimetype map - thanks to https://stackoverflow.com/questions/7519393/php-mime-types-list-of-mime-types-publically-available
		// file defines $mime_types_map, array of key=>value pairs that is extensions and mime types
		include XOOPS_ROOT_PATH."/modules/formulize/include/mime_types_map.php";
		$dotPos = strrpos($fileName, '.');
		if(!$dotPos) { // no dot, or only a leading dot as in .htaccess
			return 'no_extension';
		}
		$extension = strtolower(substr($fileName, $dotPos+1));
		if(in_array($extension, self::BLOCKED_EXTENSIONS)) {
			return 'blocked';
		}
		$ele_value = $element->getVar('ele_value');
		$allowedExtensions = str_replace(array(" ","."),"",strtolower(trim($ele_value[1])));
		if($allowedExtensions AND !in_array($extension, explode(",", $allowedExtensions))) {
			return 'not_allowed';
		}
		$deducedMimeType = mime_content_type($filePath);
		$extensionMimeType = isset($mime_types_map[$extension]) ? $mime_types_map[$extension] : false;
		// catalogue the mime type if it wasn't in our list
		$this->logMissingMimeType($extension, $deducedMimeType, $mime_types_map);
		if($claimedMimeType) {
			$this->logMissingMimeType($extension, $claimedMimeType, $mime_types_map);
		}
		// A file whose contents are a web page, carrying an extension that is not for web pages, is almost
		// always a sign in page or a viewer returned in place of the real file (over URL intake), or an
		// attempt to get HTML/script onto the server under a harmless looking name. Refused whatever the
		// element allows, and ahead of the checks below so an unknown extension cannot excuse it.
		if($deducedMimeType == 'text/html' AND !in_array($extension, array('html','htm','xhtml'))) {
			return 'html_mismatch';
		}
		// if we accept all files, or this is a type that we don't have records for (at least it matched literal extension), or some of the mime type info is in agreement (if the extension type is a mismatch, maybe the claimed type can redeem things?)
		if(!$allowedExtensions
			OR !$extensionMimeType
			OR in_array($deducedMimeType, $extensionMimeType)
			OR ($claimedMimeType AND $claimedMimeType == $deducedMimeType)) {
			return true;
		}
		return 'type_mismatch';
	}

	/**
	 * The name a file is stored under. When users cannot link directly to files, a random prefix is added, so the
	 * location of the file cannot be guessed. getFileDisplayName removes it again.
	 * @param object $element The element object
	 * @param string $originalName The name of the file as it arrived
	 * @return string The name to store the file under
	 */
	function storedFileName($element, $originalName) {
		$ele_value = $element->getVar('ele_value');
		$safeName = basename($originalName);
		if($ele_value[2]) {
			return $safeName;
		}
		// random_int, not mt_rand: when direct links are off, this prefix is the only thing that stops the
		// file's web address being guessed, so it must not be predictable from other prefixes the site issued
		$obscureFile = '';
		for ($i = 0; $i < 20; $i++) {
			$obscureFile .= random_int(0, 9);
		}
		return $obscureFile."+---+".$safeName;
	}

	/**
	 * The folder where the file for one entry is kept
	 * @param object $element The element object
	 * @param int $entryId The entry id
	 * @return string The full path to the folder
	 */
	function entryFolderPath($element, $entryId) {
		return XOOPS_ROOT_PATH."/uploads/formulize_".$element->getVar('id_form')."_".intval($entryId)."_".$element->getVar('ele_id');
	}

	/**
	 * Put a file into a folder in uploads, creating the folder if necessary
	 * @param string $folderLocation The full path of the folder
	 * @param string $sourcePath Where the file is now
	 * @param string $storedName The name to store it under, from storedFileName
	 * @param bool $isHttpUpload True if the file was uploaded through a form, so it must be moved with move_uploaded_file
	 * @return bool|string True on success, otherwise 'no_location' if the folder could not be made, or 'lost' if the file could not be moved
	 */
	function placeFile($folderLocation, $sourcePath, $storedName, $isHttpUpload = false) {
		if(!file_exists($folderLocation) AND !mkdir($folderLocation)) {
			return 'no_location';
		}
		$destination = $folderLocation."/".$storedName;
		$moveResult = $isHttpUpload ? move_uploaded_file($sourcePath, $destination) : rename($sourcePath, $destination);
		if($moveResult AND !$isHttpUpload) {
			// temporary files are made readable by their owner only, so give it the permissions an uploaded file would get
			chmod($destination, 0666 & ~umask());
		}
		return $moveResult ? true : 'lost';
	}

    // this method would gather any data that we need to pass to the template, besides the ele_value and other properties that are already part of the basic element class
    // it receives the element object and returns an array of data that will go to the admin UI template
    // when dealing with new elements, $element might be FALSE
    // can organize template data into two top level keys, advanced-tab-values and options-tab-values, if there are some options for the element type that appear on the Advanced tab in the admin UI. This requires an additional template file with _advanced.html as the end of the name. Text elements have an example.
	function adminPrepare($element) {
        if(!$element) {
            $ele_value = $this->getDefaultEleValue(); // nothing has been saved yet, so let's set a default of 10MB, and some default file types
            $directLinkNo = " checked ";
            $directLinkYes = "";
        } else {
            $ele_value = $element->getVar('ele_value');
            if(!$ele_value[0]) {
                $ele_value[0] = 10; // adminSave should enforce a default of 10, but just to be safe...
            }
            $directLinkNo = $ele_value[2] ? "" : " checked";
            $directLinkYes = $ele_value[2] ? " checked" : "";
        }
        return array('maxfilesize'=>$ele_value[0],'extensions'=>$ele_value[1],'directlinkno'=>$directLinkNo,'directlinkyes'=>$directLinkYes);
    }

    // this method would read back any data from the user after they click save in the admin UI, and save the data to the database, if it were something beyond what is handled in the basic element class
    // this is called as part of saving the options tab.  It receives a copy of the element object immediately prior to it being saved, so the element object will have all its properties set as they would be based on the user's changes in the names & settings tab, and in the options tab (the tabs are saved in order from left to right).
    // the exception is the special ele_value array, which is passed separately from the object (this will contain the values the user set in the Options tab)
    // You can modify the element object in this function and since it is an object, and passed by reference by default, then your changes will be saved when the element is saved.
    // You should return a flag to indicate if any changes were made, so that the page can be reloaded for the user, and they can see the changes you've made here.
    // advancedTab is a flag to indicate if this is being called from the advanced tab (as opposed to the Options tab, normal behaviour). In this case, you have to go off first principals based on what is in $_POST to setup the advanced values inside ele_value (presumably).
	function adminSave($element, $ele_value = array(), $advancedTab = false) {
        $changed = false;
        if($ele_value[0] == 0) {
            $ele_value[0] = 10; // set ten as a default if there is no file size specified
            $changed = true;
        }
        $element->setVar('ele_value',$ele_value);
        return $changed;
    }

    // this method reads the current state of an element based on the user's input, and the admin options, and sets ele_value to what it needs to be so we can render the element correctly
    // it must return $ele_value, with the correct value set in it, so that it will render as expected in the render method
		// $element is the element object
		// $value is the value that was retrieved from the database for this element in the active entry.  It is a raw value, no processing has been applied, it is exactly what is in the database (as prepared in the prepareDataForSaving method and then written to the DB)
    // $entry_id is the ID of the entry being loaded
	function loadValue($element, $value, $entry_id) {
				$ele_value = $element->getVar('ele_value');
        $value = $value ? unserialize($value) : array(); // what we've got in the database is a serialized array, first key is filename, second key is flag for whether the filename is for real (might be an error message)
        $ele_value[3] = (isset($value['name']) AND $value['name']) ? $value['name'] : null; // add additional keys to ele_value where we'll put the value that is coming from the database for user's to see, plus other flags and so on
        $ele_value[4] = $this->getFileDisplayName(strval($ele_value[3]));
        $ele_value[5] = (isset($value['isfile']) AND $value['isfile']) ? $ele_value[3] : null;
        return $ele_value;
    }

    // this method renders the element for display in a form
    // the caption has been pre-prepared and passed in separately from the element object
    // if the element is disabled, then the method must take that into account and return a non-interactable label with some version of the element's value in it
    // $ele_value is the options for this element - which will either be the admin values set by the admin user, or will be the value created in the loadValue method
    // $caption is the prepared caption for the element
    // $markupName is what we have to call the rendered element in HTML
    // $isDisabled flags whether the element is disabled or not so we know how to render it
		// $element is the element object
    // $entry_id is the ID number of the entry where this particular element comes from
    // $screen is the screen object that is in effect, if any (may be null)
    function render($ele_value, $caption, $markupName, $isDisabled, $element, $entry_id, $screen, $owner) {
        // ele_value[3] is the fileName or error message
        // ele_value[4] is the displayName version of the fileName (only going to be diff from fileName if there is no direct linking to files allowed)
        // ele_value[5] is the flag for whether there's a file or not
        static $fileDeleteCode;
        $introToUploadBox = "";
        if($isDisabled) {
            $displayName = $ele_value[4];
            $url = $this->createFileURL($element, $entry_id, serialize(array('name'=>$ele_value[3], 'isfile'=>$ele_value[5])));
            $link = $this->createDownloadLink($element, $url, $displayName).$this->imageFileNameLabel($displayName);
            $formElement = new xoopsFormLabel($caption, $link);
        } else {
            // create the file upload element, and also a hidden element with the correct markup name.  That hidden value will trigger the correct saving logic, and is necessary because file upload elements are excluded from POST.
            if(!$ele_value[5]) {
                $introToUploadBox = "<div id='formulize_fileStatus_".$element->getVar('ele_id')."_$entry_id' class='no-print formulize-fileupload-element'>".$ele_value[3]."</div>";
            } elseif($ele_value[3]) {
                if(!$fileDeleteCode) { // only do this once per page load
                    $fileDeleteCode = "<script type='text/javascript'>

                    var formulizeFile".$markupName."Exists = true;

                    function warnAboutFileDelete(folderName, element_id, entry_id) {
                        var answer = confirm('" . _AM_UPLOAD_DELETE_WARN . "');
                        if(answer) {
                            var xhr_params = [];
                            xhr_params[0] = folderName;
                            xhr_params[1] = element_id;
                            xhr_params[2] = entry_id;
                            formulize_xhr_send('delete_uploaded_file', xhr_params);
                        }
                    }

                    function formulize_delete_successful(response) {
                        formulizechanged = 1;
                        response = eval('('+response+')');
                        window.document.getElementById('formulize_fileStatus_'+response.element_id+'_'+response.entry_id).innerHTML = '';
                        jQuery('#fileUploadUI_de_".$element->getVar('id_form')."_'+response.entry_id+'_'+response.element_id).show();
                        var fileExistsFlag = 'formulizeFilede_".$element->getVar('id_form')."_'+response.entry_id+'_'+response.element_id+'Exists = false';
                        eval(fileExistsFlag)
                    }

                    function formulize_delete_failed() {
                        alert('" . _AM_UPLOAD_DELETE_FAIL . "');
                    }

                    </script>";

                    $introToUploadBox .= $fileDeleteCode;
                } elseif($element->getVar('ele_required')) { // just set the flag that tells the required element logic that the file is present
                    $introToUploadBox .= "<script type='text/javascript'>
                    var formulizeFile".$markupName."Exists = true;
                    </script>";
                }
                // have to spoof the raw value from the database, in order to generate the URL for use in the download link, since that's what the URL method is expecting
                $fakeRawValue = serialize(array('name'=>$ele_value[3], 'isfile'=>$ele_value[5]));
                $introToUploadBox .= "<div id='formulize_fileStatus_".$element->getVar('ele_id')."_$entry_id' class='no-print formulize-fileupload-element'>".$this->createDownloadLink($element, $this->createFileURL($element, $entry_id, $fakeRawValue), $ele_value[4]).$this->imageFileNameLabel($ele_value[4])." &mdash; <a class='formulize_fileUploadDeleteButton' href='' onclick='warnAboutFileDelete(\"".str_replace("de_","formulize_",$markupName)."\", \"".$element->getVar('ele_id')."\", \"$entry_id\");return false;'>" . _DELETE . "</a></div>";
            } else {
                $introToUploadBox = "<div id='formulize_fileStatus_".$element->getVar('ele_id')."_$entry_id' class='no-print formulize-fileupload-element'></div>";
            }
            $displayUploadUI = $ele_value[5] ? "style='display: none;'" : "";
            $allowedExtensions = explode(',',strtolower(trim($ele_value[1])));
            $acceptExtensions = array();
            foreach($allowedExtensions as $ext) {
                if($ext) {
                    $acceptExtensions[] = '.'.str_replace('.','',$ext);
                }
            }
            $acceptExtensions = implode(',',$acceptExtensions);
            $acceptAttribute = $acceptExtensions ? 'accept="'.$acceptExtensions.'"' : '';
            $introToUploadBox .= "<input type='hidden' name='MAX_FILE_SIZE' value='".($ele_value[0]*1048576)."' /><div id='fileUploadUI_".$markupName."' $displayUploadUI><input type='file' name='fileupload_".$markupName."' size=50 id='".$markupName."' onchange=\"javascript:formulizechanged=1;\" $acceptAttribute class='no-print' aria-describedby='$markupName-help-text' /><input type='hidden' id='$markupName' name='$markupName' value='$markupName' /></div>";
            $formElement = new xoopsFormLabel($caption, $introToUploadBox);
        }
        return $formElement;
    }

    // this method returns any custom validation code (javascript) that should figure out how to validate this element
    // 'myform' is a name enforced by convention that refers to the form where this element resides
    // use the adminCanMakeRequired property and alwaysValidateInputs property to control when/if this validation code is respected
    function generateValidationCode($caption, $markupName, $element) {
        // always set the cue properly, based on whether there's a file or not to be uploaded
        $validationmsg = "You must upload a file for '$caption'.";
	$validationmsg = str_replace("'", "\'", stripslashes( $validationmsg ) );
        $cueName = str_replace("de_","decue_",$markupName);
        $validationCode = array();
        if($element->getVar('ele_required')) { // need to include this only if the admin wants to force a value for this element, and there's no file selected already
            $validationCode[] = "if(formulizechanged && myform.fileupload_{$markupName}.value == '' && (typeof formulizeFile".$markupName."Exists == 'undefined' || formulizeFile".$markupName."Exists == false)) {\n";
            $validationCode[] = "  window.alert('{$validationmsg}');\n myform.fileupload_{$markupName}.focus();\n return false;\n ";
            $validationCode[] = "} else if(formulizechanged && myform.fileupload_{$markupName}.value == '' && typeof formulizeFile".$markupName."Exists != 'undefined' && formulizeFile".$markupName."Exists == true) {\n";
            $validationCode[] = "  myform.{$cueName}.name = 'skipit!';\n";
            $validationCode[] = "} else if(formulizechanged) {\n";
            $validationCode[] = "  myform.{$cueName}.name = '{$cueName}';\n";
            $validationCode[] = "}\n";
        } else { // file not required, if there's going to be a bonafide form submission, remove the cue if there's no file here
            $validationCode[] = "if(formulizechanged && myform.fileupload_{$markupName}.value == '') {\n"; // if no file has been selected, then neuter the cue element that tells us we need to handle this one when saving
            $validationCode[] = "  myform.{$cueName}.name = 'skipit!';\n";
            $validationCode[] = "} else if(formulizechanged) {\n";
            $validationCode[] = "  myform.{$cueName}.name = '{$cueName}';\n";
            $validationCode[] = "}\n";
        }
        return $validationCode;
    }

    // this method will read what the user submitted, and package it up however we want for insertion into the form's datatable
	// You can return null to cause a null value to be saved in the database
	// $value is what the user submitted
	// $element is the element object
	// $entry_id is the ID number of the entry that this data is being saved into. Can be "new", or null in the event of a subformblank entry being saved.
	// $subformBlankCounter is the counter for the subform blank entries, if applicable
	function prepareDataForSaving($value, $element, $entry_id=null, $subformBlankCounter=null) {

        $fileKey = 'fileupload_'.$value;
        if($_FILES[$fileKey]['error'] == 0) {
            if($this->checkFileAgainstElementSettings($element, $_FILES[$fileKey]['name'], $_FILES[$fileKey]['tmp_name'], $_FILES[$fileKey]['type']) === true) {
                // if it's a blank subform entry that we're saving, do things a bit differently...elements are named differently in markup in this case
                if(strstr($value, "desubform")) {
                    $underscorePos = strpos($value, "_"); // everything after the first underscore is the normal identifier for the element
                    $replacementString = substr($value, 0, $underscorePos+1);
                } else {
                    $replacementString = "de_";
                }
                $folderLocation = XOOPS_ROOT_PATH."/uploads/".str_replace($replacementString,"formulize_",$value);
                $storedName = $this->storedFileName($element, $_FILES[$fileKey]['name']);
                $placeResult = $this->placeFile($folderLocation, $_FILES[$fileKey]['tmp_name'], $storedName, isHttpUpload: true);
                if($placeResult === true) {
                    $value = array();
                    $value['name'] = $storedName;
                    $value['isfile'] = true; // second array position will indicate that we have a real file here
                    $value['type'] = $_FILES[$fileKey]['type']; // save the mime type for later use
                    $value['size'] = $_FILES[$fileKey]['size']; // save the size for later use
                    if(strstr($folderLocation, "_new_")) {
                        $GLOBALS['formulize_afterSavingLogicRequired'][$element->getVar('ele_id')] = $element->getVar('ele_type'); // set the flag that will trigger a post-save operation when we can rename the folder where the file resides to match the newly assigned entry id
                    }
                } elseif($placeResult == 'lost') {
                    $value = _AM_UPLOAD_LOST;
                    print formulize_userMessage($value);
                } else {
                    $value = _AM_UPLOAD_NOLOCATION;
                    print formulize_userMessage($value);
                }
            } else {
                $value = _AM_UPLOAD_ERROR_MIMETYPES . strip_tags($element->getVar('ele_caption', 'n'))."'";
                print formulize_userMessage($value);
            }
        } elseif($_FILES[$fileKey]['error'] == UPLOAD_ERR_NO_FILE) {
            list($fieldtype, $de, $form_id, $entry_id, $element_id) = explode('_', $fileKey);
            $dataHandler = new formulizeDataHandler($form_id);
            // Return the existing serialized value in the database. This should never happen because validation javascript should eliminate the cue in the relevant cases.
            return $dataHandler->getElementValueInEntry($entry_id, $element_id);
        } else {
            switch($_FILES[$fileKey]['error']) {
                case UPLOAD_ERR_INI_SIZE:
                    $value = _AM_UPLOAD_ERR_INI_SIZE;
                    break;
                case UPLOAD_ERR_FORM_SIZE:
                    $value = _AM_UPLOAD_ERR_FORM_SIZE . $element->getVar('ele_caption')."'";
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $value = _AM_UPLOAD_ERR_PARTIAL;
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $value = _AM_UPLOAD_ERR_NO_TMP_DIR;
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $value = _AM_UPLOAD_ERR_CANT_WRITE;
                    break;
                case UPLOAD_ERR_EXTENSION:
                    $value = _AM_UPLOAD_ERR_EXTENSION;
                    break;
            }
            print "<script>alert(\"".str_replace('"','\"',$value)."\");</script>";
        }
        if(!is_array($value)) {
            return null;
        }
        return serialize($value);
    }

    // this method will handle any final actions that have to happen after data has been saved
    // this is typically required for modifications to new entries, after the entry ID has been assigned, because before now, the entry ID will have been "new"
    // value is the value that was just saved
    // element_id is the id of the element that just had data saved
    // entry_id is the entry id that was just saved
    // ALSO, $GLOBALS['formulize_afterSavingLogicRequired']['elementId'] = type must be declared in the prepareDataForSaving step if further action is required now -- see fileUploadElement.php for an example
    function afterSavingLogic($value, $element_id, $entry_id) {
        // we need to update the folder path so that "_new_" is replaced with the _entryid_ and the file can then be accessible.
        $element_handler = xoops_getmodulehandler('elements','formulize');
        $elementObject = $element_handler->get($element_id);
        rename(XOOPS_ROOT_PATH."/uploads/formulize_".$elementObject->getVar('id_form')."_new_".$element_id,XOOPS_ROOT_PATH."/uploads/formulize_".$elementObject->getVar('id_form')."_".$entry_id."_".$element_id);
    }

    // this method will prepare a raw data value from the database, to be included in a dataset when formulize generates a list of entries or the getData API call is made
    // in the standard elements, this particular step is where multivalue elements, like checkboxes, get converted from a string that comes out of the database, into an array, for example
    // $value is the raw value that has been found in the database
    // $handle is the element handle for the field that we're retrieving this for
    // $entry_id is the entry id of the entry in the form that we're retrieving this for
    function prepareDataForDataset($value, $handle, $entry_id) {
        $url = $this->createFileURL($this->get($handle), $entry_id, $value);
        // store the displayName in case we need to format this in a list later -- only the URL will be passed to the formatDataForList method, since that's all we're passing back here, so we need another way of getting the displayName into that method
        $value = $value ? unserialize($value) : $value;
				if(is_array($value)) {
					$name = isset($value['name']) ? $value['name'] : '';
					$GLOBALS['formulize_fileUploadElementDisplayName'][$entry_id][$handle] = $this->getFileDisplayName($name);
				}
        return $url;
    }

    // this method will take a text value that the user has specified at some point, and convert it to a value that will work for comparing with values in the database.  This is used primarily for preparing user submitted text values for saving in the database, or for comparing to values in the database.  The typical user submitted values would be coming from a condition form (ie: fieldX = [term the user typed in]) or other situation where the user types in a value that needs to interact with the database.
    // this would be where a Yes value would be converted to a 1, for example, in the case of a yes/no element, since 1 is how yes is represented in the database for that element type
    // LINKED ELEMENTS AND UITEXT ARE RESOLVED PRIOR TO THIS METHOD BEING CALLED
	function prepareLiteralTextForDB($value, $element, $partialMatch=false) {
        return $value;
    }

    // this method will format a dataset value for display on screen when a list of entries is prepared
    // for standard elements, this step is where linked selectboxes potentially become clickable or not, among other things
    // Set certain properties in this function, to control whether the output will be sent through a "make clickable" function afterwards, sent through an HTML character filter (a security precaution), and trimmed to a certain length with ... appended.
    function formatDataForList($value, $handle="", $entry_id=0, $textWidth=100) {
        // $value will be the url as determined in prepareDataForDataset above...or an error message, etc, if there's no valid file
        $this->clickable = false; // we build the download link ourselves in composeMarkupForList, below
        $this->dataIsHtml = false; // the value is a URL (or an error message) - plain text, so it gets escaped
        $this->length = 2000; // truncate to a maximum of 2000 characters, and append ... on the end
        return parent::formatDataForList($value, $handle, $entry_id, $textWidth); // always return the result of formatDataForList through the parent class (where the properties you set here are enforced)
    }

    // wrap the URL in a download link. $value (the URL) has ALREADY been escaped by the parent when we
    // get here, so it is safe to place in the href; the display name is escaped separately below.
    // We make the links manually rather than using the clickable flag, since we don't just want the URL
    // part to become a link, we want to wrap the display name in a link to the URL.
    function composeMarkupForList($value, $handle="", $entry_id=0, $rawValue=null, $textWidth=100) {
        if(!strstr($value, 'http')) {
            return $value; // not a valid file - an error message or similar, already escaped
        }
        $displayName = isset($GLOBALS['formulize_fileUploadElementDisplayName'][$entry_id][$handle]) ? $GLOBALS['formulize_fileUploadElementDisplayName'][$entry_id][$handle] : ''; // set aside in GLOBALS by the prepareDataForDataset method above
        return $this->createDownloadLink($this->get($handle), $value, $displayName);
    }

		/**
		 * Delete any associated data and resources for this element when an element is deleted from a form
		 * @param object $element The element object that is being deleted
		 * @param string|null $entryScope Required. The scope of the deletion. Can be 'all' or an entry ID. If null (default), no action is taken.
		 * @return array A list of the full paths to the files that were deleted
		 */
		function deleteAssociatedDataAndResources($element, $entryScope = null) {
			$deletedFilePaths = array();
			if($entryScope !== 'null' AND $entryScope === 'all' OR intval($entryScope) > 0) {
				// we need to delete uploaded files associated with this element
				// folder names are: formulize_{fid}_{entryId}_{elementId}
				// delete all folders in the /uploads folder off the root of the XOOPS installation that match this pattern
				// regex for matching folder names: /^formulize_\d+_\d+_{$elementId}$/
				// if $scope is 'all', delete all folders that match the elementId
				// if $scope is an entry ID, delete only the folder that matches that entry ID and the elementId
				if($entryScope === 'all') {
					$entryId = '\d+';
				} else {
					$entryId = intval($entryScope);
				}
				$regex = "/^formulize_".$element->getVar('id_form')."_".$entryId."_".$element->getVar('ele_id')."$/";
				$uploadDir = XOOPS_ROOT_PATH."/uploads/";
				$allFolders = scandir($uploadDir);
				foreach($allFolders as $folder) {
					if(preg_match($regex, $folder)) {
						$folderLocation = $uploadDir.$folder;
						if(file_exists($folderLocation) AND is_dir($folderLocation)) {
							$files = scandir($folderLocation);
							foreach($files as $file) {
								if($file != "." AND $file != "..") {
									if(unlink($folderLocation."/".$file)) {
										$deletedFilePaths[] = $folderLocation."/".$file;
									}
								}
							}
							rmdir($folderLocation);
						}
					}
				}
			}
			return $deletedFilePaths;
		}

    // this method returns the URL for a file, not a href - or the text value that is in place of a file name, such as an error message
    // $element is the element object
    // $entry_id is the ID number of this entry
    // $value is the serialized raw value from the database for this particular entry
    function createFileURL($element, $entry_id, $value) {
			$value = $value ? unserialize($value) : $value;
			$fileName = (is_array($value) AND isset($value['name'])) ? $value['name'] : '';
			$isFile = (is_array($value) AND isset($value['isfile'])) ? $value['isfile'] : '';
			if($isFile) {
				$ele_value = $element->getVar('ele_value');
				$basePath = "/uploads/formulize_".$element->getVar('id_form')."_".$entry_id."_".$element->getVar('ele_id')."/";
				if($ele_value[2]) { // users can connect directly to file or not?
					return XOOPS_URL.$basePath.rawurlencode($fileName);
				} else {
					$fileModTime = fileatime(XOOPS_ROOT_PATH.$basePath.$fileName); // add mod time to change the URL so we don't serve an old file because of browser caching
					return XOOPS_URL."/modules/formulize/download.php?element=".$element->getVar('ele_id')."&entry_id=$entry_id&m=$fileModTime";
				}
			} else {
				return $fileName; // may be an error message or something like that
			}
    }

    // this method is for the file upload element only.  It will return a href that links to the actual file.
    function createDownloadLink($element, $url, $displayName) {
        $ele_value = $element->getVar('ele_value');
        if(fileNameHasImageExtension($displayName)) {
            $linkContents = $this->createImageTag($url, $displayName);
        } else {
            $linkContents = htmlspecialchars(strip_tags($displayName),ENT_QUOTES);
        }
        if($ele_value[2]) { // files we link to directly get a '_blank' target
            return "<a href='".$url."' target='_blank'>".$linkContents."</a>";
        } else {
            return "<a href='".$url."'>".$linkContents."</a>";
        }
    }

    // An image is shown in a form as its thumbnail, which on its own leaves no way to tell what the file is called.
    // So in a form, the name follows the thumbnail. Other files are already shown by name, as the link itself.
    // Only used when rendering the element in a form; lists show the thumbnail alone.
    function imageFileNameLabel($displayName) {
        if(!fileNameHasImageExtension($displayName)) {
            return "";
        }
        return " <span class='formulize-fileupload-name'>".htmlspecialchars(strip_tags($displayName))."</span>";
    }

    // this method will return the displayName for a file (ie: remove the obscuring timestamp on the front, if any)
    function getFileDisplayName($fileName) {
			  $fileNameParts = explode("+---+",$fileName);
				$displayName = isset($fileNameParts[1]) ? $fileNameParts[1] : $fileNameParts[0];
        return $displayName;
    }

    // this method will write the extension and mimeType to a list for later review
    function logMissingMimeType($extension, $mimeType, $map) {
        if(!isset($map[$extension]) OR !in_array($mimeType, $map[$extension])) {
            $missingList = file(XOOPS_ROOT_PATH.'/uploads/missingMimeTypes.txt');
            $line = "$extension,$mimeType\n";
            if(!is_array($missingList) OR !in_array($line,$missingList)) {
                $missingMimeTypes = fopen(XOOPS_ROOT_PATH.'/uploads/missingMimeTypes.txt', 'a');
                fwrite($missingMimeTypes, $line);
                fclose($missingMimeTypes);
            }
        }
    }

		/**
		 * Create an HTML img tag with the src set as the url for the image. Creates a thumbnail version of the image if one does not exist already.
		 *
		 * @param string $url The URL used to access the image.
		 * @param string $displayName Optional. The name of the file/image, for use in the title attribute.
		 * @param string $class Optional. The class to assign to the img tag. Defaults to 'formulize-uploaded-image-thumbnail'
		 * @return string Returns the HTML img tag referring to the image
		 */
		function createImageTag($url, $displayName='', $class='formulize-uploaded-image-thumbnail') {
			$result = $this->getThumbnailUrl($url, $displayName);
			if(is_array($result) AND $result['success']) {
				$url = $result['result'];
				return "<img class='".htmlspecialchars(strip_tags($class), ENT_QUOTES)."' title='".htmlspecialchars(strip_tags($displayName),ENT_QUOTES)."' src='$url' />";
			} else {
				return $result['result']; // return the error message
			}
		}

		/**
		 * Return the URL for the thumbnail version of an image, and create the thumbnail if necessary
		 *
		 * @param string $url The URL used to access the image.
		 * @return string The URL of the thumbnail version of the image. Or the URL passed in if the URL is not for an image.
		 */
		function getThumbnailUrl($url) {

			try {
				list($entry_id, $element_id, $fid) = $this->extractEntryIdAndElementIdFromUrl($url);
				$data_handler = new formulizeDataHandler($fid);
				$fileInfo = $data_handler->getElementValueInEntry($entry_id, $element_id);
				$fileInfo = unserialize($fileInfo);
				if(fileNameHasImageExtension($fileInfo['name'])) {
					$dotPos = strrpos($fileInfo['name'], '.');
					$fileExtension = strtolower(substr($fileInfo['name'], $dotPos+1));
					$thumbFileName = substr_replace($fileInfo['name'], ".thumb.$fileExtension", $dotPos);
					$path = XOOPS_ROOT_PATH."/uploads/formulize_".$fid."_".$entry_id."_".$element_id."/".$fileInfo['name'];
					$thumbPath = XOOPS_ROOT_PATH."/uploads/formulize_".$fid."_".$entry_id."_".$element_id."/".$thumbFileName;
					if(strstr($url, XOOPS_URL."/modules/formulize/download.php?element=")) {
						$thumbUrl = $url . "&size=thumb";
					} else {
						$thumbUrl = str_replace($fileInfo['name'], $thumbFileName, $url);
					}
					if(!file_exists($thumbPath)) {
						$imageInfo = getimagesize($path);
						if ($imageInfo === false) {
							return ["success" => false, "result" => _formulize_COULD_NOT_GENERATE_THUMBNAIL . ' (' . _formulize_IMAGE_NOT_FOUND . ')'];
						} else {
							$width = $imageInfo[0];
							$height = $imageInfo[1];
						}
						$memory_limit = ini_get('memory_limit');
						if ($memory_limit == -1 || $memory_limit === '-1') {
							// Unlimited memory, skip the check
							$memory_limit = PHP_INT_MAX;
						} elseif (preg_match('/^(\d+)([KMG]?)$/i', $memory_limit, $matches)) {
							$memory_limit = (int)$matches[1];
							$suffix = strtoupper($matches[2]);
							if ($suffix == 'G') {
								$memory_limit *= 1024 * 1024 * 1024;
							} elseif ($suffix == 'M') {
								$memory_limit *= 1024 * 1024;
							} elseif ($suffix == 'K') {
								$memory_limit *= 1024;
							}
							// No suffix means bytes, no conversion needed
						}
						if(($width * $height * 4 * 1.8) > $memory_limit) {
							return ["success" => false, "result" => _formulize_COULD_NOT_GENERATE_THUMBNAIL . ' (' . _formulize_IMAGE_TOO_LARGE . ')'];
						}
						$image = null;
						switch($fileExtension) {
							case 'gif':
								$image = imagecreatefromgif($path);
								break;
							case 'png':
								$image = imagecreatefrompng($path);
								break;
							case 'webp':
								// PHP's GD library can be built without WebP support, in which case the function does not exist
								if(function_exists('imagecreatefromwebp')) {
									$image = imagecreatefromwebp($path);
								}
								break;
							case 'jpg':
							case 'jpeg':
								$image = imagecreatefromjpeg($path);
								break;
						}
						// the exif extension is optional in PHP, so without it the thumbnail is made without correcting the orientation
						$exif = function_exists('exif_read_data') ? exif_read_data($path) : false;
						if ($image) {
							if($exif AND isset($exif['Orientation']))	{
								$orientation = $exif['Orientation'];
								if ($orientation == 6 OR $orientation == 5) { $image = imagerotate($image, 270, 0); }
								if ($orientation == 3 OR $orientation == 4) { $image = imagerotate($image, 180, 0); }
								if ($orientation == 8 OR $orientation == 7) { $image = imagerotate($image, 90, 0); }
								if ($orientation == 5 OR $orientation == 4 OR $orientation == 7) { imageflip($image, IMG_FLIP_HORIZONTAL); }
							}
							$image = imagescale($image, 200);
							switch($fileExtension) {
								case 'gif':
									imagegif($image, $thumbPath);
									break;
								case 'png':
									imagepng($image, $thumbPath);
									break;
								case 'webp':
									if(function_exists('imagewebp')) {
										imagewebp($image, $thumbPath);
									}
									break;
								case 'jpg':
								case 'jpeg':
									imagejpeg($image, $thumbPath);
									break;
							}
						}
					}
					// If no thumbnail could be made (animated WebP, which GD cannot read, no WebP support in GD, a damaged file, etc.)
					// keep the URL of the original image, which the thumbnail class constrains to thumbnail size anyway
					if(file_exists($thumbPath)) {
						$url = $thumbUrl;
					}
				}
			} catch (Exception $e) {
				return ["success" => false, "result" => _formulize_COULD_NOT_GENERATE_THUMBNAIL . ' (' . $e->getMessage() . ')'];
			}
			return ["success" => true, "result" => $url];
		}

		/**
		 * Get the entryId and elementId and form Id from the URL for an image. Parsing depends on the kind of URL
		 *
		 * @param string $url The URL used to access the image.
		 * @return array An array of the entry Id and element Id and form Id
		 */
		function extractEntryIdAndElementIdFromUrl($url) {
			if(strstr($url, XOOPS_URL."/modules/formulize/download.php?element=")) {
				$urlParts = explode('?', $url);
				$urlParams = explode('&', $urlParts[1]);
				$elementIdParamParts = explode('=',$urlParams[0]);
				$entryIdParamParts = explode('=',$urlParams[1]);
				$elementId = $elementIdParamParts[1];
				$entryId = $entryIdParamParts[1];
			} else {
				$metaData = str_replace(XOOPS_URL."/uploads/formulize_", "", $url);
				$metaData = explode('/', $metaData);
				$metaDataParts = explode('_',$metaData[0]);
				$elementId = $metaDataParts[2];
				$entryId = $metaDataParts[1];
			}
			$element_handler = xoops_getmodulehandler('elements', 'formulize');
			$fid = 0;
			if($elementObject = $element_handler->get($elementId)) {
				$fid = $elementObject->getVar('id_form');
			}
			return array($entryId, $elementId, $fid);
		}
}

/**
 * Take the value of a file upload element, and return an image tag if applicable
 *
 * @param array $entryOrDataset The record from a dataset, or the entire dataset, as returned from getData
 * @param string $elementHandle The element handle of the file upload element we're working with
 * @param int $dataSetKey Optional. The key in the dataset array of the entry record we want to work with. Required if $entryOrDataset is the entire dataset.
 * @param int $localId Optional. The ordinal id of the instance of the element handle we want to work with. Only required if there are multiple entries represented in this dataset record which all include data attached to this element handle, ie: if the handle is on the many side of a one to many connection in the dataset.
 * @return string Returns the an HTML img tag referring to the file if the file is an image, or returns the url for the file otherwise. If the file upload failed, this will return the error message from when the upload failed.
 */
function displayFileImage($entryOrDataset, $elementHandle, $dataSetKey=null, $localId='NULL') {
	$displayName = "";
	$url = getValue($entryOrDataset, $elementHandle, $dataSetKey, $localId);
	$element_handler = xoops_getmodulehandler('fileUploadElement', 'formulize');
	list($entryId, $elementId, $fid) = $element_handler->extractEntryIdAndElementIdFromUrl($url);
	// lookup the name
	if($elementObject = $element_handler->get($elementId)) {
		$dataHandler = new formulizeDataHandler($elementObject->getVar('id_form'));
		$value = $dataHandler->getElementValueInEntry($entryId, $elementId);
		$value = unserialize($value);
		$name = isset($value['name']) ? $value['name'] : '';
		$displayName = $element_handler->getFileDisplayName($name);
	}
	if(fileNameHasImageExtension($displayName)) {
    return $element_handler->createImageTag($url, $displayName);
	}
  return $url;
}

/**
 * Take the value of a file upload element, and return an anchor tag of the image thumbnail if applicable
 *
 * @param array $entryOrDataset The record from a dataset, or the entire dataset, as returned from getData
 * @param string $elementHandle The element handle of the file upload element we're working with
 * @param int $dataSetKey Optional. The key in the dataset array of the entry record we want to work with. Required if $entryOrDataset is the entire dataset.
 * @param int $localId Optional. The ordinal id of the instance of the element handle we want to work with. Only required if there are multiple entries represented in this dataset record which all include data attached to this element handle, ie: if the handle is on the many side of a one to many connection in the dataset.
 * @return string Returns the an HTML a tag referring to the file if the file is an image, and using the thumbnail as the clickable element, or returns the url for the file otherwise. If the file upload failed, this will return the error message from when the upload failed.
 */
function displayFileImageLink($entryOrDataset, $elementHandle, $dataSetKey=null, $localId='NULL') {
	$image = displayFileImage($entryOrDataset, $elementHandle, $dataSetKey=null, $localId='NULL');
	if(substr($image, 0, 4) == '<img') {
		$url = getValue($entryOrDataset, $elementHandle, $dataSetKey, $localId);
		$image = "<a href='$url' target='_blank'>$image</a>";
	}
	return $image;
}

/**
 * Check if a filename has one of various common image file type extensions.
 *
 * @param string $displayName The name of the file
 * @return boolean Return true or false depending if the file has a matching extension. If there is no displayName, return false.
 */
function fileNameHasImageExtension($displayName) {
    if(!$displayName) { return false; }
		if(strstr($displayName, _formulize_COULD_NOT_GENERATE_THUMBNAIL)) { return true; }
    $dotPos = strrpos($displayName, '.');
    $fileExtension = substr($displayName, $dotPos);
    $imageTypes = array('.gif', '.jpg', '.png', '.jpeg', '.webp');
    return in_array(strtolower($fileExtension), $imageTypes);
}



