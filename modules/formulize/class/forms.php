<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2006 Freeform Solutions                  ##
##                Portions copyright (c) 2003 NS Tai (aka tuff)              ##
##                       <http://www.brandycoke.com/>                        ##
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

require_once XOOPS_ROOT_PATH.'/kernel/object.php';
include_once XOOPS_ROOT_PATH.'/modules/formulize/include/functions.php';

class formulizeForm extends XoopsObject {

    function checkFormOwnership($id_form,$form_handle){
		
		global $xoopsDB;
                //check to see if there are entries in the form which 
                //do not appear in the entry_owner_groups table. If so, it finds the 
                // owner/creator of the entry and calls setEntryOwnerGroups() which inserts the
                //first, get the form ids and handles.  
                $missingEntries=q("SELECT main.entry_id,main.creation_uid From " . $xoopsDB->prefix("formulize_".$form_handle) . " as main WHERE NOT EXISTS(
               SELECT 1 FROM " . $xoopsDB->prefix("formulize_entry_owner_groups") . " as eog WHERE eog.fid=".$id_form ." and eog.entry_id=main.entry_id )");
                //now we got the missing entries in the form and the users who created them.    
                $data_handler = new formulizeDataHandler($id_form);
                foreach ($missingEntries as $entry){
                        if (!$groupResult = $data_handler->setEntryOwnerGroups($entry['creation_uid'],$entry['entry_id'])) {
                                print "ERROR: failed to write the entry ownership information to the database.<br>";
                        }
                }
		
		return count($missingEntries);
    }
    
    function formulizeForm($id_form="", $includeAllElements=false){

		// validate $id_form
		global $xoopsDB;

		if(!is_numeric($id_form)) {
			// set empty defaults
			$id_form = "";
			$lockedform = "";
			$formq[0]['desc_form'] = "";
			$formq[0]['store_revisions'] = 0;
			$single = "";
			$elements = array();
			$elementCaptions = array();
			$elementColheads = array();
			$elementHandles = array();
			$elementTypes = array();
			$encryptedElements = array();
			$elementsWithData = array();
			$headerlist = array();
			$defaultform = "";
			$defaultlist = "";
		} else {
			$formq = q("SELECT * FROM " . $xoopsDB->prefix("formulize_id") . " WHERE id_form=$id_form");
			if(!isset($formq[0])) {
				unset($formq);
				$id_form = "";
				$lockedform = "";
				$formq[0]['desc_form'] = "";
				$formq[0]['tableform'] = "";
				$formq[0]['store_revisions'] = 0;
				$single = "";
				$elements = array();
				$elementCaptions = array();
				$elementColheads = array();
				$elementHandles = array();
				$elementTypes = array();
				$encryptedElements = array();
				$elementsWithData = array();
				$headerlist = array();
				$defaultform = "";
				$defaultlist = "";
				$formq[0]['menutext'] = "";
				$formq[0]['form_handle'] = "";
			} else {
				// gather element ids for this form
				$displayFilter = $includeAllElements ? "" : "AND ele_display != \"0\"";
				$elementsq = q("SELECT ele_id, ele_caption, ele_colhead, ele_handle, ele_type, ele_encrypt FROM " . $xoopsDB->prefix("formulize") . " WHERE id_form=$id_form $displayFilter ORDER BY ele_order ASC");
				$encryptedElements = array();
				$elementTypesWithData = array();
				$element_handler = xoops_getmodulehandler('elements', 'formulize');
				
				foreach($elementsq as $row=>$value) {
					$elements[$value['ele_id']] = $value['ele_id'];
					$elementCaptions[$value['ele_id']] = $value['ele_caption'];
					$elementColheads[$value['ele_id']] = $value['ele_colhead'];
					$elementHandles[$value['ele_id']] = $value['ele_handle'];
					$elementTypes[$value['ele_id']] = $value['ele_type'];
					if($value['ele_encrypt']) {
						$encryptedElements[$value['ele_id']] = $value['ele_handle'];
					}
					
					if(!isset($elementTypesWithData[$value['ele_type']])) {
						// instantiate element object for this element, and check if it has data
						$elementObject = $element_handler->get($value['ele_id']);
						$elementTypesWithData[$value['ele_type']] = $elementObject->hasData ? true : false;
					}
					
					if($elementTypesWithData[$value['ele_type']]) {
						$elementsWithData[$value['ele_id']] = $value['ele_id'];
					}
				}
				
				// propertly format the single value
				switch($formq[0]['singleentry']) {
					case "group":
						$single = "group";
						break;
					case "on":
						$single = "user";
						break;
					case "":
						$single = "off";
						break;
					default:
						$single = "";
						break;
				}
				// setup the headerlist -- note...it's still in screwed up string format and must be processed after this by the user code that gets it
				$headerlist = $formq[0]['headerlist'];
				$defaultform = $formq[0]['defaultform'];
				$defaultlist = $formq[0]['defaultlist'];
			}
			
			// gather the view information
			$viewq = q("SELECT * FROM " . $xoopsDB->prefix("formulize_saved_views") . " WHERE sv_mainform = '$id_form' OR (sv_mainform = '' AND sv_formframe = '$id_form')");
			if(!isset($viewq[0])) {
				$views = array();
				$viewNames = array();
				$viewFrids = array();
				$viewPublished = array();
			} else {
				for($i=0;$i<count($viewq);$i++) {
					$views[$i] = $viewq[$i]['sv_id'];
					$viewNames[$i] = stripslashes($viewq[$i]['sv_name']);
					$viewFrids[$i] = $viewq[$i]['sv_mainform'] ? $viewq[$i]['sv_formframe'] : "";
					$viewPublished[$i] = $viewq[$i]['sv_pubgroups'] ? true : false;
				}
			}
			
			// setup the filter settings
			$filterSettingsq = q("SELECT groupid, filter FROM " . $xoopsDB->prefix("formulize_group_filters") . " WHERE fid='$id_form'");
			if(!isset($filterSettingsq[0])) {
				$filterSettings = array();
			} else {
				foreach($filterSettingsq as $filterSettingData) {
					$filterSettings[$filterSettingData['groupid']] = unserialize($filterSettingData['filter']);
				}
			}
		}

		$this->XoopsObject();
		//initVar params: key, data_type, value, req, max, opt
		$this->initVar("id_form", XOBJ_DTYPE_INT, $id_form, true);
		$this->initVar("lockedform", XOBJ_DTYPE_INT, $formq[0]['lockedform'], true);
		$this->initVar("title", XOBJ_DTYPE_TXTBOX, $formq[0]['desc_form'], true, 255);
		$this->initVar("tableform", XOBJ_DTYPE_TXTBOX, $formq[0]['tableform'], true, 255);
		$this->initVar("single", XOBJ_DTYPE_TXTBOX, $single, false, 5);
		$this->initVar("elements", XOBJ_DTYPE_ARRAY, serialize($elements));
		$this->initVar("elementCaptions", XOBJ_DTYPE_ARRAY, serialize($elementCaptions));
		$this->initVar("elementColheads", XOBJ_DTYPE_ARRAY, serialize($elementColheads));
		$this->initVar("elementHandles", XOBJ_DTYPE_ARRAY, serialize($elementHandles));
		$this->initVar("elementTypes", XOBJ_DTYPE_ARRAY, serialize($elementTypes));
		$this->initVar("encryptedElements", XOBJ_DTYPE_ARRAY, serialize($encryptedElements));
		$this->initVar("elementsWithData", XOBJ_DTYPE_ARRAY, serialize($elementsWithData));
		$this->initVar("views", XOBJ_DTYPE_ARRAY, serialize($views));
		$this->initVar("viewNames", XOBJ_DTYPE_ARRAY, serialize($viewNames));
		$this->initVar("viewFrids", XOBJ_DTYPE_ARRAY, serialize($viewFrids));
		$this->initVar("viewPublished", XOBJ_DTYPE_ARRAY, serialize($viewPublished));
		$this->initVar("filterSettings", XOBJ_DTYPE_ARRAY, serialize($filterSettings));
		$this->initVar("headerlist", XOBJ_DTYPE_TXTAREA, $headerlist);
		$this->initVar("defaultform", XOBJ_DTYPE_INT, $defaultform, true);
		$this->initVar("defaultlist", XOBJ_DTYPE_INT, $defaultlist, true);
		$this->initVar("menutext", XOBJ_DTYPE_TXTBOX, $formq[0]['menutext'], false, 255);
		$this->initVar("form_handle", XOBJ_DTYPE_TXTBOX, $formq[0]['form_handle'], false, 255);
		$this->initVar("store_revisions", XOBJ_DTYPE_INT, $formq[0]['store_revisions'], true);
		$this->initVar("on_before_save", XOBJ_DTYPE_TXTAREA, $formq[0]['on_before_save']);
		$this->initVar("note", XOBJ_DTYPE_TXTAREA, $formq[0]['note']);
    }

    static function sanitize_handle_name($handle_name) {
        // strip non-alphanumeric characters from form and element handles
        return preg_replace("/[^a-zA-Z0-9_]+/", "", $handle_name);
    }

    public function assignVar($key, $value) {
        if ("form_handle" == $key) {
            $value = self::sanitize_handle_name($value);
        }
        parent::assignVar($key, $value);
    }

    public function setVar($key, $value, $not_gpc = false) {
        if ("form_handle" == $key) {
            $value = self::sanitize_handle_name($value);
        }
        parent::setVar($key, $value, $not_gpc);
        if ("on_before_save" == $key) {
            $this->cache_on_before_save_code();
        }
        if ("on_after_save" == $key) {
            $this->cache_on_after_save_code();
        }
        if ("custom_edit_check" == $key) { // Added for custom_edit_check var
            $this->cache_custom_edit_check_code();
        }
    }

    protected function on_before_save_function_name() {
        // form ID is used so the function name is unique
        return "form_".$this->id_form."_on_before_save";
    }

    protected function on_after_save_function_name() {
        // form ID is used so the function name is unique
        return "form_".$this->id_form."_on_after_save";
    }

    protected function on_before_save_filename() {
        // save the code in the icms cache folder (because it is known to be writeable)
        return ICMS_CACHE_PATH."/{$this->on_before_save_function_name}.php";
    }

    protected function on_after_save_filename() {
        // save the code in the icms cache folder (because it is known to be writeable)
        return ICMS_CACHE_PATH."/{$this->on_after_save_function_name}.php";
    }

    private function cache_on_before_save_code() {
        if (strlen($this->on_before_save) > 0) {
            $on_before_save_code = <<<EOF
<?php

function form_{$this->id_form}_on_before_save(\$entry_id, \$element_values, \$form_id) {
    extract(\$element_values);  // this converts the array elements into PHP variables

{$this->on_before_save}

    return get_defined_vars();  // this converts PHP variables back into an array
}

EOF;
            // todo: there is a way to validate php files on disk, so do that and report any syntax errors
            return (false !== file_put_contents($this->on_before_save_filename, $on_before_save_code));
        } else {
            if (file_exists($this->on_before_save_filename)) {
                unlink($this->on_before_save_filename);
            }
            return true;
        }
    }

    private function cache_on_after_save_code() {
        if (strlen($this->on_after_save) > 0) {
            $on_after_save_code = <<<EOF
<?php

function form_{$this->id_form}_on_after_save(\$entry_id, \$form_id) {
{$this->on_after_save}
}

EOF;
            // todo: there is a way to validate php files on disk, so do that and report any syntax errors
            return (false !== file_put_contents($this->on_after_save_filename, $on_after_save_code));
        } else {
            if (file_exists($this->on_after_save_filename)) {
                unlink($this->on_after_save_filename);
            }
            return true;
        }
    }

    protected function custom_edit_check_function_name() {
        // form ID is used so the function name is unique
        return "form_".$this->id_form."_custom_edit_check";
    }

    protected function custom_edit_check_filename() {
        // save the code in the icms cache folder (because it is known to be writeable)
        return ICMS_CACHE_PATH."/{$this->custom_edit_check_function_name}.php";
    }
    private function cache_custom_edit_check_code() {
        if (strlen($this->custom_edit_check) > 0) {
            $custom_edit_check_code = <<<EOF
<?php

function form_{$this->id_form}_custom_edit_check(\$form_id,\$entry_id,\$user_id, \$allow_editing) {
{$this->custom_edit_check}
return \$allow_editing; // this will pass the result of the custom operation into the correct variable slot.
}

EOF;
            // todo: there is a way to validate php files on disk, so do that and report any syntax errors
            return (false !== file_put_contents($this->custom_edit_check_filename, $custom_edit_check_code));
        } else {
            if (file_exists($this->custom_edit_check_filename)) {
                unlink($this->custom_edit_check_filename);
            }
            return true;
        }
    }

    public function on_before_save() {
        // this function exists only because otherwise xoops automatically converts \n (which is stored in the database) to <br />
        return $this->vars['on_before_save']['value'];
    }

    public function on_after_save() {
        // this function exists only because otherwise xoops automatically converts \n (which is stored in the database) to <br />
        return $this->vars['on_after_save']['value'];
    }

    public function custom_edit_check() {
        // this function exists only because otherwise xoops automatically converts \n (which is stored in the database) to <br />
        return $this->vars['custom_edit_check']['value'];
    }

    public function onBeforeSave($entry_id, $element_values) {
        // if there is any code to run before saving, include it (write if necessary), and run the function
        if (strlen($this->on_before_save) > 0 and (file_exists($this->on_before_save_filename) or $this->cache_on_before_save_code())) {
            include_once $this->on_before_save_filename;
            // note that the custom code could create new values in the element_values array, so the caller must limit to valid field names
            $element_values = call_user_func($this->on_before_save_function_name, $entry_id, $element_values, $this->getVar('id_form'));
            foreach ($element_values["element_values"] as $key => $value) {
                if (0 == preg_match("/^[a-zA-Z_][a-zA-Z0-9_]*$/", $key)) {
                    // this key is invalid for a PHP variable name, so it was not set in the on-before-save function and
                    //  the value in the array (which could have been modified) should be returned
                    $element_values[$key] = $value;
                }
            }
            // due to extract()ing and then collecting back into an array, the array contains itself, so remove the duplicate
            unset($element_values["element_values"]);
        }
        return $element_values;
    }

    public function onAfterSave($entry_id) {
        // if there is any code to run after saving, include it (write if necessary), and run the function
        if (strlen($this->on_after_save) > 0 and (file_exists($this->on_after_save_filename) or $this->cache_on_after_save_code())) {
            include_once $this->on_after_save_filename;
            // note that the custom code could create new values in the element_values array, so the caller must limit to valid field names
            call_user_func($this->on_after_save_function_name, $entry_id, $this->getVar('id_form'));
        }
    }

    public function customEditCheck($form_id, $entry_id, $user_id, $allow_editing) {
        // if there is any code to run to check if editing is allowed, include it (write if necessary), and run the function
        if (strlen($this->custom_edit_check) > 0 and (file_exists($this->custom_edit_check_filename) or $this->cache_custom_edit_check_code())) {
            include_once $this->custom_edit_check_filename;
            // note that the custom code could create new values in the element_values array, so the caller must limit to valid field names
            $allow_editing =call_user_func($this->custom_edit_check_function_name,$form_id, $entry_id, $user_id,$allow_editing, $this->getVar('id_form'));
            return $allow_editing;
        }else{
            return $allow_editing; // return passed value if there is no code to check with.
        }
    }

    public function default_form_screen() {
        $screen_handler = xoops_getmodulehandler('screen', 'formulize');
        return $screen_handler->get($this->defaultform);
    }

    public function default_list_screen() {
        $screen_handler = xoops_getmodulehandler('screen', 'formulize');
        return $screen_handler->get($this->defaultlist);
    }

    public function entry_count() {
        global $xoopsDB;
        $result = $xoopsDB->query("select count(*) as row_count from ".$xoopsDB->prefix("formulize_".$this->form_handle));
        if (false == $result) {
            error_log(mysql_error());
        }
        list($count) = $xoopsDB->fetchRow($result);
        return $count;
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

    public function getVar($key, $format = 's') {
        $return_value = parent::getVar($key, $format);
        if (XOBJ_DTYPE_ARRAY == $this->vars[$key]['data_type'] && !is_array($return_value)) {
            // now it's an array
            $return_value = array();
        }
        return $return_value;
    }
}


class formulizeFormsHandler {
	var $db;
	function formulizeFormsHandler(&$db) {
		$this->db =& $db;
	}
	function &getInstance(&$db) {
		static $instance;
		if (!isset($instance)) {
			$instance = new formulizeFormsHandler($db);
		}
		return $instance;
	}
	function &create() {
		return new formulizeForm();
	}

	function &get($fid,$includeAllElements=false) {
		$fid = intval($fid);
		// this is cheap...we're caching form objects potentially twice because of a possible difference in whether we want all objects included or not.  This could be handled much better.  Maybe iterators could go over the object to return all elements, or all visible elements, or all kinds of other much more elegant stuff.
		static $cachedForms = array();
		if(isset($cachedForms[$fid][$includeAllElements])) { return $cachedForms[$fid][$includeAllElements]; }
		if($fid > 0) {
			$cachedForms[$fid][$includeAllElements] = new formulizeForm($fid,$includeAllElements);
			return $cachedForms[$fid][$includeAllElements];
		}
		return false;
	}
	
	function getByHandle($handle) {
		global $xoopsDB;
		$sql = "SELECT id_form FROM " . $xoopsDB->prefix("formulize_id") . " WHERE form_handle = '".formulize_db_escape($handle) . "'";
		if($res = $xoopsDB->query($sql)) {
			$array = $xoopsDB->fetchArray($res);
			return $this->get($array['id_form']);
		}
		
	}

	function getAllForms($includeAllElements=false) {
		global $xoopsDB;
		$allFidsQuery = "SELECT id_form FROM " . $xoopsDB->prefix("formulize_id") . " ORDER BY desc_form";
		$allFidsRes = $xoopsDB->query($allFidsQuery);
		$foundFids = array();
		while($allFidsArray = $xoopsDB->fetchArray($allFidsRes)) {
			$foundFids[] = $this->get($allFidsArray['id_form'],$includeAllElements);
		}
		return $foundFids;
	}
		
	// accepts a framework object or frid
	function getFormsByFramework($framework_Object_or_Frid) {
		if(is_object($framework_Object_or_Frid)) {
			if(get_class($framework_Object_or_Frid) == "formulizeFramework") {
				$frameworkObject = $framework_Object_or_Frid;
			} else {
				return false;
			}
		} elseif(is_numeric($framework_Object_or_Frid)) {
			include_once XOOPS_ROOT_PATH . "/modules/formulize/class/frameworks.php";
			$frameworkObject = new formulizeFramework($frid);
		} else {
			return false;
		}
		$fids = array();
		foreach($frameworkObject->getVar('fids') as $thisFid) {
			$fids[] = $this->get($thisFid);
		}
		return $fids;
	}

	function getFormsByApplication($application_object_or_id, $returnIds=false) {
		if(is_numeric($application_object_or_id) AND $application_object_or_id > 0) {
			$application_handler = xoops_getmodulehandler('applications','formulize');
			$application_object_or_id = $application_handler->get($application_object_or_id);
		}
		$fids = array();
		if(is_object($application_object_or_id)) {
			if(get_class($application_object_or_id) == 'formulizeApplication') {
				$applicationObject = $application_object_or_id;
				foreach($applicationObject->getVar('forms') as $thisFid) {
					if($returnIds) {
						$fids[] = $thisFid;
					} else {
						$fids[] = $this->get($thisFid);
					}
				}
			} else {
				return false;
			}
		} else {
			// no application specified, so get forms that do not belong to an application
			$sql = "SELECT id_form FROM ".$this->db->prefix("formulize_id")." as formtable WHERE NOT EXISTS(SELECT 1 FROM ".$this->db->prefix("formulize_application_form_link")." as linktable WHERE linktable.fid=formtable.id_form) ORDER BY formtable.desc_form";
			if($res = $this->db->query($sql)) {
				while($array = $this->db->fetchArray($res)) {
					if($returnIds) {
						$fids[] = $array['id_form'];
					} else {
						$fids[] = $this->get($array['id_form']);
					}
				}
			}
		}
		return $fids;
	}

	function insert(&$formObject, $force=false) {
		if( get_class($formObject) != 'formulizeForm'){
            return false;
        }
        if( !$formObject->isDirty() ){
            return true;
        }
        if( !$formObject->cleanVars() ){
            return false;
        }
				foreach( $formObject->cleanVars as $k=>$v ){
					${$k} = $v;
				}
				
				$singleToWrite = "";
				switch($single) {
					case('user'):
						$singleToWrite = "on";
						break;
					case('off'):
						$singleToWrite = "";
						break;
					default:
					case('group'):
						$singleToWrite = "group";
						break;
				}

                if($formObject->isNew() || empty($id_form)) {
                    $sql = "INSERT INTO ".$this->db->prefix("formulize_id") . " (`desc_form`, `singleentry`, `tableform`, ".
                        "`defaultform`, `defaultlist`, `menutext`, `form_handle`, `store_revisions`, `on_before_save`, ".
                        "`on_after_save`, `custom_edit_check`, `note`) VALUES (".
                        $this->db->quoteString($title).", ".$this->db->quoteString($singleToWrite).", ".
                        $this->db->quoteString($tableform).", ".intval($defaultform).", ".intval($defaultlist).
                        ", ".$this->db->quoteString($menutext).", ".$this->db->quoteString($form_handle).", ".
                        intval($store_revisions).", ".$this->db->quoteString($on_before_save).", ".
                        $this->db->quoteString($on_after_save).", ".$this->db->quoteString($custom_edit_check).
                        ", ".$this->db->quoteString($note).")";
                } else {
                    $sql = "UPDATE ".$this->db->prefix("formulize_id") . " SET".
                        " `desc_form` = ".$this->db->quoteString($title).
                        ", `singleentry` = ".$this->db->quoteString($singleToWrite).
                        ", `headerlist` = ".$this->db->quoteString($headerlist).
                        ", `defaultform` = ".intval($defaultform).
                        ", `defaultlist` = ".intval($defaultlist).
                        ", `menutext` = ".$this->db->quoteString($menutext).
                        ", `form_handle` = ".$this->db->quoteString($form_handle).
                        ", `store_revisions` = ".intval($store_revisions).
                        ", `on_before_save` = ".$this->db->quoteString($on_before_save).
                        ", `on_after_save` = ".$this->db->quoteString($on_after_save).
                        ", `custom_edit_check` = ".$this->db->quoteString($custom_edit_check).
                        " , "."`note` = ".$this->db->quoteString($note).
                        " WHERE id_form = ".intval($id_form);
                }

                if (false != $force) {
                    $result = $this->db->queryF($sql);
                } else{
                    $result = $this->db->query($sql);
                }

				if( !$result ){
					print "Error: this form could not be saved in the database.  SQL: $sql<br>".$xoopsDB->error();
					return false;
				}
				if( empty($id_form) ){
					$id_form = $this->db->getInsertId();
				}
				$formObject->assignVar('id_form', $id_form);
				
				if( $form_handle == "" ){ // only occurs when forms have no handles specified by the user, which is probably only new forms, because non-new forms would default to the fid (but for new forms, fid is not known yet when insert is called)
					$formObject->setVar('form_handle', $id_form);
					$this->insert($formObject, $force); 
				}
				
				return $id_form;
				
	}

	function createTableFormElements($targetTableName, $fid) {
		
		$result = $this->db->query("SHOW COLUMNS FROM " . formulize_db_escape($targetTableName));
		$element_handler = xoops_getmodulehandler('elements', 'formulize');
		$element_order = 0;
		while($row = $this->db->fetchRow($result)) {
			$element =& $element_handler->create();
			$element->setVar('ele_caption', str_replace("_", " ", $row[0])); 
			$element->setVar('ele_desc', "");
			$element->setVar('ele_colhead', "");
			$element->setVar('ele_req', 0);
			$element->setVar('ele_order', $element_order);
			$element_order = $element_order + 5;
			$element->setVar('ele_forcehidden', 0);
			$element->setVar('ele_uitext', "");
			$element->setVar('ele_value', array(0=>"", 1=>$xoopsModuleConfig['ta_rows'], 2=>$xoopsModuleConfig['ta_cols'], 3=>"")); // 0 is default, 1 is rows, 2 is cols, 3 is association to another element -- not sure the xoopsModuleConfig is actually being picked up
			$element->setVar('id_form', $fid);
			$element->setVar('ele_private', 0);
			$element->setVar('ele_display', 1);
      $element->setVar('ele_disabled', 0);
			$element->setVar('ele_type', 'textarea');
			if( !$element_handler->insert($element) ){
				return false;
			}	
			unset($element);
		}
		$handleUpdateSQL = "UPDATE ".$this->db->prefix("formulize")." SET ele_handle=ele_id WHERE id_form=".intval($fid);
    if(!$res = $this->db->query($handleUpdateSQL)) {
      print "Error: could not synchronize handles with element ids for the '".strip_tags(htmlspecialchars($_POST['tablename'])). "' form";
			return false;
    }
		return true;
	}
		
	// lock the form...set the lockedform flag to indicate that no further editing of this form is allowed
	function lockForm($fid) {
		global $xoopsDB;
		$sql = "UPDATE ".$xoopsDB->prefix("formulize_id") . " SET lockedform = 1 WHERE id_form = ". intval($fid);
		if(!$res = $xoopsDB->queryF($sql)) {
			return false;
		} else {
			return true;
		}
	}

	// check to see if a handle is unique within a form
	function isHandleUnique($handle, $element_id="") {
        $handle = formulizeForm::sanitize_handle_name($handle);
		if(isMetaDataField($handle)){
			return false; // don't allow reserved words that will be used in the main data extraction queries
		}
		global $xoopsDB;
		$element_id_condition = $element_id ? " AND ele_id != " . intval($element_id) : "";
		$sql = "SELECT count(ele_handle) FROM " . $xoopsDB->prefix("formulize") . " WHERE ele_handle = '" . formulize_db_escape($handle) . "' $element_id_condition";
		if(!$res = $xoopsDB->query($sql)) {
			print "Error: could not verify uniqueness of handle '$handle' in form $fid";
		} else {
			$row = $xoopsDB->fetchRow($res);
			if($row[0] == 0) { // zero rows found with that handle in this form
				return true;
			} else {
				return false;
			}
		}
	}

	function delete($fid) {
		if(is_object($fid)) {
			if(!get_class($fid) == "formulizeForm") {
				return false;
			}
			$fid = $fid->getVar('id_form');
		} elseif(!is_numeric($fid)) {
			return false;
		}
		$isError = false;
		global $xoopsDB;
		$sql = "DELETE FROM ".$xoopsDB->prefix("formulize_id")." WHERE id_form = $fid";
		if(!$xoopsDB->query($sql)) {
			print "Error: could not delete form $fid";
			$isError = true;
		}
		$sql = "DELETE FROM ".$xoopsDB->prefix("formulize")." WHERE id_form = $fid";
		if(!$xoopsDB->query($sql)) {
			print "Error: could not delete elements for form $fid";
			$isError = true;
		}
		$sql = "DELETE FROM ".$xoopsDB->prefix("formulize_framework_links"). " WHERE fl_form1_id = $fid OR fl_form2_id = $fid";
		if(!$xoopsDB->query($sql)) {
			print "Error: could not delete relationship links for form $fid";
			$isError = true;
		}
		$sql = "SELECT sid, type FROM ".$xoopsDB->prefix("formulize_screen")." WHERE fid=$fid";
		if($res = $xoopsDB->query($sql)) {
			while($array = $xoopsDB->fetchArray($res)) {
				$sql = "DELETE FROM ".$xoopsDB->prefix("formulize_screen_".strtolower($array['type']))." WHERE sid=".intval($array['sid']);
				if(!$xoopsDB->query($sql)) {
					print "Error: could not delete screen ".htmlspecialchars(strip_tags($array['sid']))." for form $fid";
					$isError = true;
				}
				$application_handler = xoops_getmodulehandler('applications', 'formulize');
				$application_handler->deleteMenuLinkByScreen("sid=".intval($array['sid']));
				/*
				$sql1="select menu_id from ".$xoopsDB->prefix("formulize_menu_links")." where sid=".intval($array['sid']);
				$res1=$xoopsDB->query($sql1);
				$sql2="DELETE FROM ".$xoopsDB->prefix("formulize_menu_links")." where sid=".intval($array['sid']);
				
				if(!$result = $xoopsDB->query($sql2)) {
						print "Error: could not delete menu item ".htmlspecialchars(strip_tags($array['sid']))." for form $fid";
						$isError=true;
				}else{
						while($arr=$xoopsDB->fecthArray($res1)){
								$deletemenupermissions = "DELETE FROM `".$xoopsDB->prefix("formulize_menu_permissions")."` WHERE menu_id=" .intval($arr['menu_id']) .";";
								$xoopsDB->query($deletemenupermissions);
						}
				}
				*/
			}
			$sql = "DELETE FROM ".$xoopsDB->prefix("formulize_screen")." WHERE fid=$fid";
			if(!$xoopsDB->query($sql)) {
				print "Error: could not delete screens for form $fid";
				$isError = true;
			}
				$application_handler = xoops_getmodulehandler('applications', 'formulize');
				$application_handler->deleteMenuLinkByScreen("fid=".intval($fid));
		}
		$sql = "DELETE FROM ".$xoopsDB->prefix("formulize_application_form_link")." WHERE fid=$fid";
		if(!$xoopsDB->query($sql)) {
			print "Error: could not delete form $fid from its applications";
			$isError = true;
		}
		if(!$this->dropDataTable($fid)) {
			$isError = true;
		}
		return $isError ? false : true;
	}

	// this method figures out if the revisions history table for a form exists or not
	// variable passed in can be a form object, a form id or the text of the form handle itself, which is used as an override if you don't want to work with the object -- necessary when tables are being renamed!
	function revisionsTableExists($fid) {
		if(is_object($fid)) {
			if(!get_class($fid) == "formulizeForm") {
				return false;
			} else {
				$formObject = $fid;
				$form_handle = $formObject->getVar('form_handle');
			}
		} elseif(is_numeric($fid)) {
			$formObject = $this->get($fid);
			$form_handle = $formObject->getVar('form_handle');
		} else {
			$form_handle = $fid;
		}
		global $xoopsDB;
		$existingTables = array();
		if(count($existingTables)==0) {
			$testsql = "SHOW TABLES LIKE '%_revisions'";
			$resultst = $xoopsDB->queryF($testsql);
			while($table = $xoopsDB->fetchRow($resultst)) {
				$existingTables[] = $table[0];
			}
		}
		$foundval = in_array($xoopsDB->prefix("formulize_".$form_handle."_revisions"), $existingTables);
		return $foundval;
	}

	
	// create a data table for a form object (or form)
	// $fid can be an id or an object
	// Note that this method will add in fields for the elements in the form, if invoked as part of the 3.0 patch process, or when cloning forms.
	// if a map is provided, then we're cloning a form and the data types of the original elements will be preserved in the new form
	// revisionsTable is a flag used to indicate if we're creating the revisions copy of the form table or not
	function createDataTable($fid, $clonedForm=0, $map=false, $revisionsTable=false) {
		if(is_numeric($fid)) {
			$formObject = $this->get($fid, true); // true forces all elements to be included, even ones that are not displayed right now
		} elseif(!get_class($fid) == "formulizeForm") {
			return false;
		} else {
			$formObject = $fid;
		}
		if($revisionsTable) {
			$revisionTableName = "_revisions";
			$clonedForm = $formObject->getVar('id_form'); // we need to clone the datatypes of the original table
		} else {
			$revisionTableName = "";
		}
		$form_handler = xoops_getmodulehandler('forms', 'formulize');
		$clonedFormObject = $form_handler->get($clonedForm);
		$elementTypes = $formObject->getVar('elementTypes');
		global $xoopsDB;
		// build SQL for new table
		$newTableSQL = "CREATE TABLE " . $xoopsDB->prefix("formulize_" . $formObject->getVar('form_handle') . $revisionTableName) . " (";
		if($revisionsTable) {
			$newTableSQL .= "`revision_id` bigint(7) unsigned NOT NULL auto_increment,";
			$newTableSQL .= "`entry_id` int(7) unsigned NOT NULL,";
		} else {
			$newTableSQL .= "`entry_id` int(7) unsigned NOT NULL auto_increment,";	
		}
		$newTableSQL .= "`creation_datetime` Datetime NULL default NULL, ";
		$newTableSQL .= "`mod_datetime` Datetime NULL default NULL, ";
		$newTableSQL .= "`creation_uid` int(7) default '0',";
		$newTableSQL .= "`mod_uid` int(7) default '0',";
		foreach($formObject->getVar('elementHandles') as $elementId=>$thisHandle) {
						// NOTE: THIS WILL FAIL IF/WHEN SOMEONE CREATE A CUSTOM ELEMENT TYPE THAT IS NOT A DATA-STORING ELEMENT!!
						// WE WILL NEED TO GO GET THE ELEMENT OBJECT HERE, AND CHECK IF IT'S A DATA STORING ELEMENT TYPE OR NOT.  THIS IS A PROPERTY ON THE CUSTOM ELEMENT OBJECTS, SO NOT HARD, BUT A PAIN AND ADDS QUERIES TO THE PAGE.
						if($elementTypes[$elementId] == "areamodif" OR $elementTypes[$elementId] == "ib" OR $elementTypes[$elementId] == "sep" OR $elementTypes[$elementId] == "grid" OR $elementTypes[$elementId] == "subform") { continue; } // do not attempt to create certain types of fields since they don't live in the db!
						if($map !== false OR $revisionsTable) {
							// we're cloning with data, so base the new field's datatype on the original form's datatype for the corresponding field
							if(!isset($dataTypeMap)) {
								$dataTypeMap = array();
								$dataTypeSQL = "SHOW COLUMNS FROM " . $xoopsDB->prefix("formulize_".$clonedFormObject->getVar('form_handle'));
								if($dataTypeRes = $xoopsDB->queryF($dataTypeSQL)) {
									while($dataTypeArray = $xoopsDB->fetchArray($dataTypeRes)) {
										$dataTypeMap[$dataTypeArray['Field']] = $dataTypeArray['Type'];
									}
								} else {
									print "Error: could not get column datatype information for the source form.<br>$dataTypeSQL<br>";
									return false;
								}
							}
							if($revisionsTable) {
								// for revision tables, the handles will be exactly the same, so the lookup in the dataTypeMap is really easy
								$type_with_default = ("text" == $dataTypeMap[$thisHandle] ? "text" : $dataTypeMap[$thisHandle]." NULL default NULL");
								$newTableSQL .= "`$thisHandle` $type_with_default,";
								// for cloned forms, we have to look up the handle name in the map that was passed in, since element handles will have changed in the cloning process
							} else {
								$type_with_default = ("text" == $dataTypeMap[array_search($thisHandle, $map)] ? "text" : $dataTypeMap[array_search($thisHandle, $map)]." NULL default NULL");
								$newTableSQL .= "`$thisHandle` $type_with_default,";
							}
						} else {
							if($elementTypes[$elementId] == "date") {
								$newTableSQL .= "`$thisHandle` date NULL default NULL,";
							} else {
								$newTableSQL .= "`$thisHandle` text,";
							}
						}
		}
		if($revisionsTable) {
			$newTableSQL .= "PRIMARY KEY (`revision_id`),";
			$newTableSQL .= "INDEX i_entry_id (entry_id),";
		} else {
			$newTableSQL .= "PRIMARY KEY (`entry_id`),";	
		}
		$newTableSQL .= "INDEX i_creation_uid (creation_uid)";
		$newTableSQL .= ") ENGINE=MyISAM;";
		// make the table
		if(!$tableCreationRes = $xoopsDB->queryF($newTableSQL)) {
			print $xoopsDB->error();
			print "\n";
			print $newTableSQL;
			return false;
		}
		return true;
	}

	// drop the data table
	// fid can be an id or object
	function dropDataTable($fid) {
		if(is_object($fid)) {
			if(!get_class($fid) == "formulizeForm") {
				return false;
			}
			$fid = $fid->getVar('id_form');
			$form_handle = $fid->getVar('form_handle');
		} elseif(!is_numeric($fid)) {
			return false;
		} else {
			$form_handler = xoops_getmodulehandler('forms', 'formulize');
			$formObject = $form_handler->get($fid);
			$form_handle = $formObject->getVar('form_handle');
		}
		global $xoopsDB;
		$dropSQL = "DROP TABLE " . $xoopsDB->prefix("formulize_" . $form_handle);
		if(!$dropRes = $xoopsDB->queryF($dropSQL)) {
			return false;
		}
		// remove the revisions table when the form is deleted, if it is present
		if($this->revisionsTableExists($fid)) {
			$dropSQL = "DROP TABLE " . $xoopsDB->prefix("formulize_" . $form_handle."_revisions");
			if(!$dropRes = $xoopsDB->queryF($dropSQL)) {
				print "Error: could not remove the revisions table for form $fid";
			}	
		}
		// remove the entry owner groups info for that form
		$ownershipSQL = "DELETE FROM " . $xoopsDB->prefix("formulize_entry_owner_groups") . " WHERE fid=$fid";
		if(!$ownershipSQLRes = $xoopsDB->queryF($ownershipSQL)) {
			print "error: could not remove entry ownership data for form $fid";
		}
		return true;
	}
	
	// this function deletes an element field from the data table
	// $id can be numeric or an object
	function deleteElementField($element) {
		if(!$element = _getElementObject($element)) {
			return false;
		}
		global $xoopsDB;
		$form_handler = xoops_getmodulehandler('forms', 'formulize');
		$formObject = $form_handler->get($element->getVar('id_form'));
		$deleteFieldSQL = "ALTER TABLE " . $xoopsDB->prefix("formulize_" . $formObject->getVar('form_handle')) . " DROP `" . $element->getVar('ele_handle') . "`";
		if(!$deleteFieldRes = $xoopsDB->queryF($deleteFieldSQL)) {
			return false;
		}
		if($this->revisionsTableExists($element->getVar('id_form'))) {
			$deleteFieldSQL = "ALTER TABLE " . $xoopsDB->prefix("formulize_" . $formObject->getVar('form_handle')."_revisions") . " DROP `" . $element->getVar('ele_handle') . "`";
			if(!$deleteFieldRes = $xoopsDB->queryF($deleteFieldSQL)) {
				print "Error: could not remove element ".$element->getVar('ele_handle')." from the revisions table for form ". $formObject->getVar('form_handle');
				return false;
			}
		}
		return true;
	}
	
	// this function adds an element field to the data table
	// $id can be numeric or an object
	function insertElementField($element, $dataType) {
		if(!$element = _getElementObject($element)) {
			return false;
		}
		global $xoopsDB;
		$form_handler = xoops_getmodulehandler('forms', 'formulize');
		$formObject = $form_handler->get($element->getVar('id_form'));
		$dataType = $dataType ? $dataType : "text";
		$type_with_default = ("text" == $dataType ? "text" : "$dataType NULL default NULL");
		$insertFieldSQL = "ALTER TABLE " . $xoopsDB->prefix("formulize_" . $formObject->getVar('form_handle')) . " ADD `" . $element->getVar('ele_handle') . "` $type_with_default";
		if(!$insertFieldRes = $xoopsDB->queryF($insertFieldSQL)) {
			return false;
		}
		if($this->revisionsTableExists($element->getVar('id_form'))) {
			$insertFieldSQL = "ALTER TABLE " . $xoopsDB->prefix("formulize_" . $formObject->getVar('form_handle')."_revisions") . " ADD `" . $element->getVar('ele_handle') . "` $dataType NULL default NULL";
			if(!$insertFieldRes = $xoopsDB->queryF($insertFieldSQL)) {
				print "Error: could not add element ".$element->getVar('ele_handle')." to the revisions table for form ". $formObject->getVar('form_handle');
				return false;
			}
		}
		return true;
	}
	
	// update the field name in the datatable.  $element can be an id or an object.
	// $newName can be used to override the current ele_handle value.  Introduced for handling the toggling of encryption on/off where we need to rename fields to something other than the ele_handle value.
	function updateField($element, $oldName, $dataType=false, $newName="") {
		if(!$element = _getElementObject($element)) {
			return false;
		}
		global $xoopsDB;
		$form_handler = xoops_getmodulehandler('forms', 'formulize');
		$formObject = $form_handler->get($element->getVar('id_form'));
		if(!$dataType) {
			// first get its current state:
			$fieldStateSQL = "SHOW COLUMNS FROM " . $xoopsDB->prefix("formulize_" . $formObject->getVar('form_handle')) ." LIKE '$oldName'"; // note very odd use of LIKE as a clause of its own in SHOW statements, very strange, but that's what MySQL does
			if(!$fieldStateRes = $xoopsDB->queryF($fieldStateSQL)) {
				return false;
			}
			$fieldStateData = $xoopsDB->fetchArray($fieldStateRes);
			$dataType = $fieldStateData['Type'];
		}
		$newName = $newName ? $newName : $element->getVar('ele_handle');
		$updateFieldSQL = "ALTER TABLE " . $xoopsDB->prefix("formulize_" . $formObject->getVar('form_handle')) . " CHANGE `$oldName` `$newName` ". $dataType; 
		if(!$updateFieldRes = $xoopsDB->queryF($updateFieldSQL)) {
		  return false;
		}
		if($this->revisionsTableExists($element->getVar('id_form'))) {
			$updateFieldSQL = "ALTER TABLE " . $xoopsDB->prefix("formulize_" . $formObject->getVar('form_handle')."_revisions") . " CHANGE `$oldName` `$newName` ". $dataType; 
			if(!$updateFieldRes = $xoopsDB->queryF($updateFieldSQL)) {
			  print "Error: could not update the field name for $oldName in form ".$formObject->getVar('form_handle');
			  return false;
			}
		}
		return true;
	}
	
	// this function updates the per group filter settings for a form
	// $filterSettings should be an array that has keys for groups, and then an array of all the filter settings (which will be an array of three other arrays, one for elements, one for ops and one for terms, all in synch)
	function setPerGroupFilters($filterSettings, $fid) {
		if(!is_numeric($fid) OR !is_array($filterSettings)) {
			return false;
		}
		global $xoopsDB;
		// loop through the settings and make a query to check for what exists and needs updating, vs. inserting
		$foundGroups = array();
		$checkSQL = "SELECT groupid FROM ".$xoopsDB->prefix("formulize_group_filters"). " WHERE fid=".$fid;
		$checkRes = $xoopsDB->query($checkSQL);
		while($checkArray = $xoopsDB->fetchArray($checkRes)) {
			$foundGroups[$checkArray['groupid']] = true;
		}
		
		$insertStart = true;
		$insertSQL = "INSERT INTO ".$xoopsDB->prefix("formulize_group_filters")." (`fid`, `groupid`, `filter`) VALUES ";
		$updateSQL = "UPDATE ".$xoopsDB->prefix("formulize_group_filters")." SET filter = CASE groupid ";
		$runUpdate = false;
		$runInsert = false;
		foreach($filterSettings as $groupid=>$theseSettings) {
			if(isset($foundGroups[$groupid])) {
				// add to update query
				$updateSQL .= "WHEN $groupid THEN '".formulize_db_escape(serialize($theseSettings))."' ";
				$runUpdate = true;
			} else {
				// add to the insert query
			  if(!$insertStart) { $insertSQL .= ", "; }
				$insertSQL .= "(".$fid.", ".$groupid.", '".formulize_db_escape(serialize($theseSettings))."')";
				$insertStart = false;
				$runInsert = true;
			}
		}
		$updateSQL .= " ELSE filter END WHERE fid=".$fid;
		
		if($runInsert) {
			if(!$xoopsDB->query($insertSQL)) {
				return false;
			}
		}
		if($runUpdate) {
			if(!$xoopsDB->query($updateSQL)) {
				return false;
			}
		}
		return true;
	
	}
	
	// this function clears the per group filters for a form
	function clearPerGroupFilters($groupids, $fid) {
		if(!is_array($groupids)) {
			$groupids = array(0=>$groupids);
		}
		if(!is_numeric($fid)) {
			return false;
		}
		global $xoopsDB;
		$deleteSQL = formulize_db_escape("DELETE FROM ".$xoopsDB->prefix("formulize_group_filters")." WHERE fid=$fid AND groupid IN (".implode(", ",$groupids).")");
		if(!$xoopsDB->query($deleteSQL)) {
			return false;
		} else {
			return true;
		}
	}


	// this function returns a per-group filter for the current user on the specified form, formatted as a where clause, for the specified form alias, if any
	// if groupids is specified, then it will base the filter on those groups and not the current groups
	function getPerGroupFilterWhereClause($fid, $formAlias="", $groupids=false) {

		if(!is_numeric($fid) OR $fid < 1) {
			return "";
		}

		global $xoopsUser;
		$uid = $xoopsUser ? $xoopsUser->getVar('uid') : 0;
		if(!is_array($groupids)) {
			$groupids = $xoopsUser ? $xoopsUser->getGroups() : array(0=>XOOPS_GROUP_ANONYMOUS);
		}
		
		
		if($formAlias) {
			$formAlias .= "."; // add a period at the end of the alias so it will work with the field names in the query
		}
		
		// get all the filters in effect for the specified groups, the process them all into a variable we can tack onto the end of any query
		// all filters are always on the mainform only
		global $xoopsDB;
		$getFiltersSQL = "SELECT filter FROM ".$xoopsDB->prefix("formulize_group_filters"). " WHERE groupid IN (".implode(",",$groupids).") AND fid=$fid";
		if(!$getFiltersRes = $xoopsDB->query($getFiltersSQL)) {
			return false;
		}
		$perGroupFilter = "";
		while($filters = $xoopsDB->fetchArray($getFiltersRes)) {
			$filterSettings = unserialize($filters['filter']);
			// filterSettings[0] will be the elements
			// filterSettings[1] will be the ops
			// filterSettings[2] will be the terms
			/* ALTERED - 20100317 - freeform - jeff/julian - start */
			// filterSettings[3] will be the types

			// find the filter indexes for 'match all' and 'match one or more'
			$filterAll = array();
			$filterOOM = array();
			for($i=0;$i<count($filterSettings[3]);$i++) {
				if($filterSettings[3][$i] == "all") {
					$filterAll[] = $i;
				} else {
					$filterOOM[] = $i;
				}
			}

			$perGroupFilterAND = $this->buildPerGroupFilterWhereClause("AND",$filterAll,$filterSettings,$uid,$formAlias);
			$perGroupFilterOR = $this->buildPerGroupFilterWhereClause("OR",$filterOOM,$filterSettings,$uid,$formAlias);

			if( $perGroupFilterAND || $perGroupFilterOR ) {
					$perGroupFilter = " AND (";
			}

			$perGroupFilter .= $perGroupFilterAND;
			if( $perGroupFilterOR ) {
				if( $perGroupFilterAND ) {
					$perGroupFilter .= " AND (" . $perGroupFilterOR . ")";
					//$perGroupFilter .= " OR (" . $perGroupFilterOR . ")";
				} else {
					$perGroupFilter .= $perGroupFilterOR;
				}
			}
			/* ALTERED - 20100317 - freeform - jeff/julian - stop */
		}

		if($perGroupFilter) {
			$perGroupFilter .= ") ";
		}

		//print( $perGroupFilter );

		return $perGroupFilter;
	}


	function buildPerGroupFilterWhereClause($match,$indexes,$filterSettings,$uid,$formAlias) {
		$perGroupFilter = "";

		for($io=0;$io<count($indexes);$io++) {
			$i = $indexes[$io];
			if(!($perGroupFilter == "")) {
				$perGroupFilter .= " $match ";
			}

			$likeBits = (strstr(strtoupper($filterSettings[1][$i]), "LIKE") AND substr($filterSettings[2][$i], 0, 1) != "%" AND substr($filterSettings[2][$i], -1) != "%") ? "%" : "";
			$termToUse = str_replace("{USER}", $uid, $filterSettings[2][$i]); 
			if (ereg_replace("[^A-Z{}]","", $termToUse) === "{TODAY}") {
				$number = ereg_replace("[^0-9+-]","", $termToUse);
				$termToUse = date("Y-m-d",mktime(0, 0, 0, date("m") , date("d")+$number, date("Y")));
			}
			$termToUse = (is_numeric($termToUse) AND !strstr(strtoupper($filterSettings[1][$i]), "LIKE")) ? $termToUse : "\"$likeBits".formulize_db_escape($termToUse)."$likeBits\"";
			$perGroupFilter .= "$formAlias`".$filterSettings[0][$i]."` ".htmlspecialchars_decode($filterSettings[1][$i]) . " " . $termToUse; // htmlspecialchars_decode is used because &lt;= might be the operator coming out of the DB instead of <=
		}

		return $perGroupFilter;
	}
	
	function cloneForm($fid, $clonedata=false) {
		if(is_object($fid)) {
			if(!get_class($fid) == "formulizeForm") {
				return false;
			}
			$fid = $fid->getVar('id_form');
		} elseif(!is_numeric($fid)) {
			return false;
		}
		// procedure:
		// duplicate row for that fid in db but use next incremental fid
		// duplicate rows in form table for that fid, but use new fid and increment ele_ids of course
		// redraw page

		$newtitle = $this->titleForClonedForm($fid);

		$getrow = q("SELECT * FROM " . $this->db->prefix("formulize_id") . " WHERE id_form = $fid");
		$insert_sql = "INSERT INTO " . $this->db->prefix("formulize_id") . " (";
		$start = 1;
		foreach($getrow[0] as $field=>$value) {
			if(is_null($value)) { continue; }
			if($this->fieldShouldBeSkippedInCloning($field)) { continue; }
			if(!$start) { $insert_sql .= ", "; }
			$start = 0;
			$insert_sql .= $field;
		}
		$insert_sql .= ") VALUES (";
		$start = 1;

		foreach($getrow[0] as $field=>$value) {
			if(is_null($value)) { continue; }
			if($this->fieldShouldBeSkippedInCloning($field)) { continue; }
			if($field == "desc_form") { $value = $newtitle; }
			if($field == "form_handle") {
				$oldFormHandle = $value;
				$value = "replace_with_handle_and_id";
			}
			if(!$start) { $insert_sql .= ", "; }
			$start = 0;
            $insert_sql .= '"'.formulize_db_escape($value).'"';
		}
		$insert_sql .= ")";
		if(!$result = $this->db->query($insert_sql)) {
			print "error duplicating form: '$title'<br>SQL: $insert_sql<br>".$xoopsDB->error();
			return false;
		}

		$newfid = $this->db->getInsertId();
		
		// replace formhandle of the new form
		$replaceSQL = "UPDATE ". $this->db->prefix("formulize_id") . " SET form_handle='".formulize_db_escape($oldFormHandle."_".$newfid)."' WHERE form_handle=\"replace_with_handle_and_id\"";
		if(!$result = $this->db->queryF($replaceSQL)) {
		  print "error setting the form_handle for the new form.<br>".$xoopsDB->error();
		  return false;
		}		
	
		$getelements = q("SELECT * FROM " . $this->db->prefix("formulize") . " WHERE id_form = $fid");
		$oldNewEleIdMap = array();
		foreach($getelements as $ele) { // for each element in the form....
			$insert_sql = "INSERT INTO " . $this->db->prefix("formulize") . " (";
			$start = 1;
			foreach($ele as $field=>$value) {
				if($field == "ele_id") { continue; }
				if(!$start) { $insert_sql .= ", "; }
				$start = 0;
				$insert_sql .= $field;
			}
			$insert_sql .= ") VALUES (";
			$start = 1;
			foreach($ele as $field=>$value) {
				if($field == "id_form") { $value = "$newfid"; }
				if($field == "ele_id") { continue; }
				if($field == "ele_handle") {
					if($value === $ele['ele_id']) {
						$value = "replace_with_ele_id";
					} else {
						$firstUniqueCheck = true;
						$value .= "_cloned";
						while(!$uniqueCheck = $this->isHandleUnique($value)) {
							if($firstUniqueCheck) {
								$value = $value . "_".$newfid;
								$firstUniqueCheck = false;
							} else {
								$value = $value . "_copy";
							}
						}
					}
					$oldNewEleIdMap[$ele['ele_handle']] = $value;
				}
				if(!$start) { $insert_sql .= ", "; }
				$start = 0;
				$value = addslashes($value);
				$insert_sql .= "\"$value\"";
			}
			$insert_sql .= ")";
			if(!$result = $this->db->query($insert_sql)) {
				print "error duplicating elements in form: '$title'<br>SQL: $insert_sql<br>".$xoopsDB->error();
				return false;
			}
			if($oldNewEleIdMap[$ele['ele_handle']] == "replace_with_ele_id") {
				$oldNewEleIdMap[$ele['ele_handle']] = $this->db->getInsertId();
			}
		}

		// replace ele_id flags that need replacing
		$replaceSQL = "UPDATE ". $this->db->prefix("formulize") . " SET ele_handle=ele_id WHERE ele_handle=\"replace_with_ele_id\"";
		if(!$result = $this->db->queryF($replaceSQL)) {
		   print "error setting the ele_handle values for the new form.<br>".$xoopsDB->error();
		   return false;
		}

	  // Need to create the new data table now -- July 1 2007
    if(!$tableCreationResult = $this->createDataTable($newfid, $fid, $oldNewEleIdMap)) { 
      print "Error: could not make the necessary new datatable for form " . $newfid . ".  Please delete the cloned form and report this error to <a href=\"mailto:formulize@freeformsolutions.ca\">Freeform Solutions</a>.<br>".$xoopsDB->error();
      return false;
    }

    if($clonedata) {
        // July 1 2007 -- changed how cloning happens with new data structure
        include_once XOOPS_ROOT_PATH . "/modules/formulize/class/data.php"; // formulize data handler
        $dataHandler = new formulizeDataHandler($newfid);
        if(!$cloneResult = $dataHandler->cloneData($fid, $oldNewEleIdMap)) {
        print "Error:  could not clone the data from the old form to the new form.  Please delete the cloned form and report this error to <a href=\"mailto:formulize@freeformsolutions.ca\">Freeform Solutions</a>.<br>".$xoopsDB->error();
            return false;
        }
    }

	// if revisions are enabled for the cloned form, then create the revisions table
	$form_handler = xoops_getmodulehandler('forms', 'formulize');
	$clonedFormObject = $form_handler->get($newfid);
	if ($clonedFormObject->getVar('store_revisions')) {
		if (!$tableCreationResult = $this->createDataTable($newfid, 0, false, true)) {
			print "Error: could not create revisions table for form $newfid. ".
				"Please delete the cloned form and report this error to ".
				"<a href=\"mailto:formulize@freeformsolutions.ca\">Freeform Solutions</a>.<br>".$xoopsDB->error();
			return false;
		}
	}

		$this->setPermissionsForClonedForm($fid, $newfid);

		// create and insert new defaultlist screen and defaultform screen using $newfid and $newtitle
		$defaultFormScreenId = $this->formScreenForClonedForm($newtitle, $newfid);
		$defaultListScreenId = $this->listScreenForClonedForm($defaultFormScreenId, $newtitle, $newfid);
		$clonedFormObject->setVar('defaultform', $defaultFormScreenId);
		$clonedFormObject->setVar('defaultlist', $defaultListScreenId);
		if(!$form_handler->insert($clonedFormObject)) {
			print "Error: could not update form object with default screen ids: ".$xoopsDB->error();
    }

		$this->setClonedFormAppId($newfid, $xoopsDB);
	}

	/**
	 * @param $field
	 * @return bool
	 */
	function fieldShouldBeSkippedInCloning($field)
	{
		return $field == "id_form" OR $field == "defaultform" OR $field == "defaultlist";
	}

	/**
	 * @param $newtitle
	 * @param $newfid
	 * @return mixed
	 */
	public function formScreenForClonedForm($newtitle, $newfid)
	{
		$formScreenHandler = xoops_getmodulehandler('formScreen', 'formulize');
		$defaultFormScreen = $formScreenHandler->create();
		$formScreenHandler->setDefaultFormScreenVars($defaultFormScreen, $newtitle, $newfid);
		if(!$defaultFormScreenId = $formScreenHandler->insert($defaultFormScreen)) {
			print "Error: could not create default form screen";
			return $defaultFormScreenId;
		}
		return $defaultFormScreenId;
	}

	/**
	 * @param $defaultFormScreenId
	 * @param $newtitle
	 * @param $newfid
	 * @return mixed
	 */
	public function listScreenForClonedForm($defaultFormScreenId, $newtitle, $newfid)
	{
		$listScreenHandler = xoops_getmodulehandler('listOfEntriesScreen', 'formulize');
		$screen = $listScreenHandler->create();
		$listScreenHandler->setDefaultListScreenVars($screen, $defaultFormScreenId, $newtitle, $newfid);

		if(!$defaultListScreenId = $listScreenHandler->insert($screen)) {
			print "Error: could not create default list screen";
			return $defaultListScreenId;
		}
		return $defaultListScreenId;
		}

    public function titleForClonedForm($fid) {
        $foundTitle = 1;
        $titleCounter = 0;
        $form_handler = xoops_getmodulehandler('forms', 'formulize');
        $formObject = $form_handler->get($fid);
        $title = $formObject->getVar('title');
        while ($foundTitle) {
            $titleCounter++;
            if ($titleCounter > 1) {
                // add a number to the new form name to ensure it is unique
                $newtitle = sprintf(_FORM_MODCLONED, $title)." $titleCounter";
            } else {
                $newtitle = sprintf(_FORM_MODCLONED, $title);
            }
            $titleCheckSQL = "SELECT desc_form FROM " . $this->db->prefix("formulize_id") . " WHERE desc_form = '".formulize_db_escape($newtitle)."'";
            $titleCheckResult = $this->db->query($titleCheckSQL);
            $foundTitle = $this->db->getRowsNum($titleCheckResult);
        }
        return $newtitle; // use the last searched title (because it was not found)
    }

	/**
	 * @param $newfid
	 * @param $xoopsDB
	 */
	public function setClonedFormAppId($newfid, $xoopsDB)
	{
		// set newly cloned form's app to match the app id from the form being cloned
		$cloned_app_id = $_POST['aid'];
		$insert_app_form_link_query = "INSERT INTO ".$this->db->prefix("formulize_application_form_link")." (`appid`, `fid`) VALUES ($cloned_app_id, $newfid)";
		if(!$result = $this->db->query($insert_app_form_link_query)) {
			print "error adding cloned form to application with id: '$cloned_app_id'<br>SQL: $insert_app_form_link_query<br>".$xoopsDB->error();
		}
	}

	function renameDataTable($oldName, $newName, $formObject) {
		global $xoopsDB;

		$renameSQL = "RENAME TABLE " . $xoopsDB->prefix("formulize_" . $oldName) . " TO " . $xoopsDB->prefix("formulize_" . $newName) . ";";
    //print $renameSQL;
		if(!$renameRes = $xoopsDB->queryF($renameSQL)) {
		  return false;
		}
		if($this->revisionsTableExists($oldName)) { // check with the fid, which will force the method to get a cached version of the object, that will have the old name, so we can check against that name (form_settings_save.php sends the updated object with the new name)
			$renameSQL = "RENAME TABLE " . $xoopsDB->prefix("formulize_" . $oldName."_revisions") . " TO " . $xoopsDB->prefix("formulize_" . $newName."_revisions") . ";";
			if(!$renameRes = $xoopsDB->queryF($renameSQL)) {
			  print "Error: could not rename the revisions table for form ".$formObject->getVar('form_handle');
			  return false;
			}
		}
		return true;
	}

	/**
	 * @param $fid
	 * @param $newfid
	 */
	public function setPermissionsForClonedForm($fid, $newfid)
	{
// replicate permissions of the original form on the new cloned form
		$criteria = new CriteriaCompo();
		$criteria->add(new Criteria('gperm_itemid', $fid), 'AND');
		$criteria->add(new Criteria('gperm_modid', getFormulizeModId()), 'AND');
		$gperm_handler = xoops_gethandler('groupperm');
		$oldFormPerms = $gperm_handler->getObjects($criteria);
		foreach ($oldFormPerms as $thisOldPerm) {
			// do manual inserts, since addRight uses the xoopsDB query method, which won't do updates/inserts on GET requests
			$sql = "INSERT INTO " . $this->db->prefix("group_permission") . " (gperm_name, gperm_itemid, gperm_groupid, gperm_modid) VALUES ('" . $thisOldPerm->getVar('gperm_name') . "', $newfid, " . $thisOldPerm->getVar('gperm_groupid') . ", " . getFormulizeModId() . ")";
			$res = $this->db->queryF($sql);
}
	}
}
