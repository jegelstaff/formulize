<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2004 Freeform Solutions                  ##
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

// internal is an array consisting of:
//  [0] file id
//  [1] (array) column headings
//  [2] formulize form id
//  [3] formulize form elements
//  [4] (array) column headings to formulize form elements
//  [8] formulize form handle

use Google\Service\AccessContextManager\Expr;

function stripLegacyControlPair($value) {
    static $legacyPair = "\x13\x10"; // chr(19).chr(16)
		return $value === '' ? $value : str_replace($legacyPair, '', $value);
}

function importCsv() {

	// INITIAL SETUP AND VALIDATION
	global $xoopsDB, $xoopsUser;
	$formHandler = xoops_getmodulehandler('forms', 'formulize');
	$gperm_handler = xoops_gethandler('groupperm');
	$errors = array();

	// open the file, start $csv stream
	if(!isset($_FILES['csv_name']['tmp_name']) OR !$csv = fopen($_FILES['csv_name']['tmp_name'], "r")) {
		throw new Exception("No file uploaded found");
	}
	if (feof($csv)) {
    throw new Exception("Uploaded file appears empty");
  }

	// validate the form
	if(!$fid = isset($_GET['fid']) ? $_GET['fid'] : (isset($_POST['fid']) ? intval($_POST['fid']) : 0)) {
		throw new Exception("No form specified for import");
	}
	if(!$formObject = $formHandler->get($fid)) {
		throw new Exception("Specified form ($fid) does not exist");
	}

	if(!$userHasImportPermission = $gperm_handler->checkRight("import_data", $fid, ($xoopsUser ? $xoopsUser->getGroups() : array(XOOPS_GROUP_ANONYMOUS)), getFormulizeModId())) {
		$errors[] = "You do not have permission to import entries to this form.";
		return $errors;
	}

	// DECLARE WHAT WE NEED
	$elementHandler = xoops_getmodulehandler('elements', 'formulize');
	$validateData = (isset($_POST['validatedata']) AND $_POST['validatedata']) ? true : false;
	$pkColumn = (isset($_POST['pkColumn']) AND _getElementObject($_POST['pkColumn'])) ? $_POST['pkColumn'] : _formulize_ENTRY_ID;
	$pkColumnNumber = null;
	$columnHeaders = array();
	$columnElements = array();
	$columnElementTypes = array();
	$elementTypeHandlers = array();
	$metadataColumns = array(
		'creator' => _formulize_DE_CALC_CREATOR,
		'createdate' => _formulize_DE_CALC_CREATEDATE,
		'modifier' => _formulize_DE_CALC_MODIFIER,
		'moddate' => _formulize_DE_CALC_MODDATE,
		'username' => _formulize_DE_IMPORT_USERNAME,
		'fullname' => _formulize_DE_IMPORT_FULLNAME,
		'password' =>_formulize_DE_IMPORT_PASSWORD,
		'email' => _formulize_DE_IMPORT_EMAIL,
		'regcode' => _formulize_DE_IMPORT_REGCODE,
		'idreqcol' => _formulize_DE_IMPORT_IDREQCOL,
		'usethisentryid' => _formulize_DE_IMPORT_NEWENTRYID,
		'entryid' => _formulize_ENTRY_ID,
		'idreqs' => $pkColumn,
	);
	$metadataColumnNumbers = array();
	$lineNumber = 0;

	// LOOP THROUGH THE FILE ONE LINE AT A TIME
	while($csvLine = fgetcsv($csv, escape: "\\")) { // TODO: ALTER THE ESCAPE PARAM WHEN PHP 9 CHANGES HOW THIS IS DONE? OR KEEP THIS FOR LEGACY COMPATIBILITY?

		$lineNumber++;
		$errorCountAtStartOfLine = count($errors);

		// read the header row first, get the name of elements we're dealing with in order
		// setup metadata info for reference later
    if(empty($columnHeaders)) {
			foreach($csvLine as $i => $header) {
				$header = stripLegacyControlPair($header);
				$headerElement = null;
				// lookup non metadata columns to see if they're real elements
				if(($header == $pkColumn OR !in_array($header, $metadataColumns)) AND !$headerElement = _getElementObject($header)) {
					$errors[] = "Element specified in header row not found in database: $header";
				}
				$columnHeaders[] = $header;
				$columnElements[] = $headerElement;
				if($headerElement) {
					$elementType = $headerElement->getVar('ele_type');
					$columnElementTypes[] = $elementType;
					if(!isset($elementTypeHandlers[$elementType])) {
						$elementTypeHandlers = xoops_getmodulehandler($elementType.'Element', 'formulize');
					}
				} else {
					$columnElementTypes[] = null;
				}
				if($header == $pkColumn) {
					$pkColumnNumber = $i;
				}
			}
			// ******* CAREFUL HERE, BECAUSE WE CAN IGNORE LACK OF PKCOLUMN WHEN DOING NEW ENTRIES... BUT IF WE'RE DOING AN UPDATE IMPORT, THEN THIS IS A PROBLEM... BUT WE DON'T DISTINGUISH BETWEEN UPDATE AND NOT ANYMORE??
			if(!$pkColumnNumber AND $pkColumn != _formulize_ENTRY_ID) {
				$errors[] = "Primary key column not found in the uploaded file";
			}
			foreach($metadataColumns as $metadataKey => $metadataColumnName) {
				$metadataColumnNumbers[$metadataKey] = array_search($metadataColumnName, $columnHeaders);
			}
			// bail if there are errors in the header row
			if(count($errors) > 0) {
				return $errors;
			}
			continue; // done with the header row - go to next row in csv
		}

		// carrying on with non header row...

		// establish the line identifier for use in error messages -- if the pk column is present and has a value, use that as part of the identifier, otherwise just use the line number
		$lineIdentifier = "Line $lineNumber";
		if(isset($csvLine[$pkColumnNumber]) AND $csvLine[$pkColumnNumber]) {
			$lineIdentifier .= " (".$csvLine[$pkColumnNumber].")";
		}

		// if the column count doesn't match the header row, skip the line and record an error
		if(count($csvLine) != count($columnHeaders)) {
			$errors[] = "$lineIdentifier skipped: column count does not match header row.";
			continue;
		}

		// importset 3 is the column headings, which is csvElements array now
		// importset 4 is fid
		// importset 5 is some linked element metadata
		// importset 6 is the mapping of csv columns to form element ordinals based on creation order in the database??
		// importset 7 is the metadata column numbers, which can be used to pull those metadata values from the csvElements array for each row
		// importset 8 is the form handle

		// loop through the headers of the row
		$uid = 0;
		foreach($columnHeaders as $columnNumber => $csvHeader) {

			$cell_value = $csvLine[$columnNumber]; // already validated the line has same number of columns as the header row, so this should be safe
			$element = $columnElements[$columnNumber];
			$elementType = $columnElementTypes[$columnNumber];
			$elementTypeHandler = $elementType ? $elementTypeHandlers[$elementType] : null;

			// WHAT IS UID USED FOR??? SHOULD BE PROXY VALUE FOR CREATING NEW ENTRIES??
			if($metadataColumnNumbers['creator'] == $columnNumber) {
				if(!$uid = getUserId($cell_value)) {
					$errors[] = "$lineIdentifier, column $csvHeader specifies a user that was not found: ".strip_tags(htmlspecialchars($cell_value)).". Should be a user id or username or full name.";
				}
			}

			// user wants data validated first
    	if ($validateData) {

					if($cell_value == "" AND $element AND $element->getVar('ele_required')) {
						$errors[] = "$lineIdentifier, column $csvHeader requires a value, but the cell was blank";
					}

					if($cell_value AND $metadataColumnNumbers['idreqs'] == $columnNumber) {
						$entryId = $pkColumn == _formulize_ENTRY_ID ? $cell_value : getImportEntryIdFromPkColumnValue($cell_value, $element);
						if(!$entryId) {
							$errors[] = "$lineIdentifier, Invalid entry identifier specified. No matching entry found for '".strip_tags(htmlspecialchars($cell_value))."'";
						} elseif(formulizePermHandler::user_can_edit_entry($fid, ($xoopsUser ? $xoopsUser->getVar('uid') : 0), $entryId) === false) {
							$errors[] = "$lineIdentifier, Invalid entry identifier specified. You do not have permission to modify the entry.";
						}
					}

					// validate by element type, ******* move code to methods in objects
					if($elementTypeHandler) {
						$validationResult = $elementTypeHandler->validateValueForDB($cell_value, $element);
					}

			}

		}

		// need to build up data while looping row above, then write data


		if (is_array($row) AND count((array) $row) > 1) {
            $rowCount++;
            $this_id_req = "";
            if(isset($importSet[7]['idreqs'])) {
							if(!$pkColumn OR $pkColumn == _formulize_ENTRY_ID OR (isset($_POST['usePkColumnAsEntryId']) AND $_POST['usePkColumnAsEntryId'])) {
								$this_id_req = intval($row[$importSet[7]['idreqs']]);
							} elseif($pkColumn) {
								$this_id_req = getImportEntryIdFromPkColumnValue($row[$importSet[7]['idreqs']], $importSet[5][0][$importSet[6][$importSet[7]['idreqs']]]);
							}
						}


						// VALIDATION STUFF BELOW


                        // check columns from form
                        $switchEleType = anySelectElementType($element["ele_type"]) ? "select" : $element["ele_type"];
        								switch ($switchEleType) {
                            case "select":
                                $ele_value = unserialize($element["ele_value"]);
                                if (isset($importSet[5][1][$link]) AND !strstr($cell_value, ",") AND (!is_numeric($cell_value) OR $cell_value < 10000000))
                                {
                                    // Linked element, but allow entries with commas to pass through unvalidated, and also allow through numeric values with no commas, if they are really big (assumption is big numbers are some kind of special entry_id reference, as in the case of UofT)
                                    $linkElement = $importSet[5][1][$link];

                                    if ($ele_value[1] AND !($ele_value['snapshot'] AND $ele_value[16])) {
                                        // Multiple options
                                        //echo "Multiple options<br>";

                                        $items = explode("\n", $cell_value);
                                        //$all_valid_options = getElementOptions($linkElement[0], $linkElement[1]);
                                        list($all_valid_options, $all_valid_options_ids) = getElementOptions($linkElement[2]['ele_handle'], $linkElement[2]['id_form']);
                                        foreach ($items as $item)
                                        {
                                            $item_value = trim($item);

                                            if (!in_array($item_value, $all_valid_options)) {
                                                $foundit = false;
                                                foreach ($all_valid_options as $thisoption) {
                                                    if (trim($item_value) == stripslashes(trim(trans($thisoption)))) { // stripslashes is necessary only because the data contains slashes in the database (which it should not, so this should be removed when that is fixed)
                                                        $foundit = true;
                                                        break;
                                                    }
                                                }
                                                if (!$foundit) {
                                                    $some_options = array_slice($all_valid_options, 0, 20);
                                                    $errors[] = "<li>line " . $rowCount .
                                                        ", column " . $importSet[3][$link] .
                                                        ",<br> <b>found</b>: " . $item_value .
                                                        ", <b>was expecting values such as</b>: " .
                                                        stripslashes(implode(", ", $some_options)) . "</li>";
                                                }
                                            }
                                        }
                                    } elseif(!($ele_value['snapshot'] AND $ele_value[16])) {

																			// Single option
																			list($all_valid_options, $all_valid_options_ids) = getElementOptions($linkElement[2]['ele_handle'], $linkElement[2]['id_form']);
																			$errorFoundValidOptions = false;
																			if(intval($cell_value)>0 AND !in_array($cell_value, $all_valid_options_ids)) {
																				$errorFoundValidOptions = $all_valid_options_ids;
																			} elseif(!in_array($cell_value, $all_valid_options)) {
																				foreach ($all_valid_options as $thisoption) {
																					if (trim($cell_value) == stripslashes(trim(trans($thisoption)))) { // stripslashes is necessary only because the data contains slashes in the database (which it should not, so this should be removed when that is fixed)
																						break 2;
																					}
																				}
																				$errorFoundValidOptions = $all_valid_options;
																			}
																			if($errorFoundValidOptions) {
																				$errors[] = "<li>line " . $rowCount .
																						", column " . $importSet[3][$link] .
																						",<br> <b>found</b>: " . $cell_value .
																						", <b>was expecting</b>: " . stripslashes(implode(", ", $errorFoundValidOptions)) . "</li>";
																			}

                                    }
                                } elseif (!strstr($cell_value, ",") AND (!is_numeric($cell_value) OR $cell_value < 10000000)) {
                                    // Not-Linked element
                                    $ele_value = unserialize($element["ele_value"]);

                                    // handle fullnames or usernames
                                    $temparraykeys = array_keys($ele_value[2]);
                                    if ($temparraykeys[0] === "{FULLNAMES}" OR $temparraykeys[0] === "{USERNAMES}") {
                                        // ADDED June 18 2005 to handle pulling in usernames for the user's group(s) -- updated for real live use September 6 2006
                                        if ($temparraykeys[0] === "{FULLNAMES}") {
                                            $nametype = "name";
                                        }
                                        if ($temparraykeys[0] === "{USERNAMES}") {
                                            $nametype = "uname";
                                        }
                                        if (!isset($fullnamelist)) {
                                            $fullnamelistq = q("SELECT uid, $nametype FROM " . $xoopsDB->prefix("users"));
                                            static $fullnamelist = array();
                                            foreach ($fullnamelistq as $thisname) {
                                                $fullnamelist[$thisname['uid']] = $thisname[$nametype];
                                            }
                                        }
                                        if ($ele_value[1]) { // multiple
                                            $items = explode("\n", $cell_value);
                                        } else {
                                            $items = array(0=>$cell_value);
                                        }
                                        foreach ($items as $item) {
                                            if (is_numeric($item)) {
                                                if (!isset($fullnamelist[$item])) {
                                                    $errors[] = "<li>line " . $rowCount .
                                                        ", column " . $importSet[3][$link] .
                                                        ",<br> <b>User Id</b>: " . $item .
                                                        " <b>is not a valid id for a user</b></li>";
                                                }
                                            } else {
                                                $uids = array_keys ($fullnamelist, $item);
                                                if (count((array) $uids) == 0) {
                                                    $errors[] = "<li>line " . $rowCount .
                                                        ", column " . $importSet[3][$link] .
                                                        ",<br> <b>Name</b>: " . $item .
                                                        " <b>is not a valid name for a user</b></li>";
                                                    break;
                                                }
                                            }
                                        }
                                        break;
                                    }
                                    if ($ele_value[1]) {
                                        // Multiple options
                                        $options = $ele_value[2];
																				$uiTexts = unserialize($element["ele_uitext"]);
                                        $items = explode("\n", $cell_value);
                                        foreach ($items as $item) {
                                            $item_value = trim($item);
																						if (!in_array($item_value, (array)$options, true) AND !in_array($item_value, (array)$uiTexts, true)) {
                                                // last option causes strict matching by type
                                                $foundit = false;
                                                foreach ($options as $thisoption=>$default_value) {

                                                    if (trim($item_value) == trim(trans($thisoption))) {
                                                        $foundit = true;
                                                        break;
                                                    }
                                                }
                                                if (!$foundit) {
                                                    for (reset($options); $key = key($options); next($options)) {
                                                        $result[] = $key;
                                                    }

                                                    $errors[] = "<li>line " . $rowCount .
                                                        ", column " . $importSet[3][$link] .
                                                        ",<br> <b>found</b>: " . $item_value .
                                                        ", <b>was expecting</b>: " . implode(", ", $result) . "</li>";
                                                }
                                            }
                                        }
                                    } else {
                                        // Single option
                                        $options = $ele_value[2];
																				$uiTexts = unserialize($element["ele_uitext"]);
                                        if (!in_array(trim($cell_value), (array)$options, true) AND !in_array(trim($cell_value), (array)$uiTexts, true)) {
                                            // last option causes strict matching by type
                                            // then do a check against the translated options
                                            foreach ($options as $thisoption=>$default_value) {
                                                if (trim($cell_value) == trim(trans($thisoption))) {
                                                    break 2;
                                                }
                                            }

                                            for (reset($options); $key = key($options); next($options)) {
                                                $result[] = $key;
                                            }

                                            $errors[] = "<li>line " . $rowCount .
                                                ", column " . $importSet[3][$link] .
                                                ",<br> <b>found</b>: " . $cell_value .
                                                ", <b>was expecting</b>: " . implode(", ", $result) . "</li>";
                                        }
                                    }
                                }
                                break;


                            case "checkbox":
														case "checkboxLinked":

															$ele_value = unserialize($element["ele_value"]);
														if (isset($importSet[5][1][$link]) AND !strstr($cell_value, ",") AND (!is_numeric($cell_value) OR $cell_value < 10000000))
														{
																// Linked element, but allow entries with commas to pass through unvalidated, and also allow through numeric values with no commas, if they are really big (assumption is big numbers are some kind of special entry_id reference, as in the case of UofT)
																$linkElement = $importSet[5][1][$link];

																if (!$ele_value['snapshot']) {
																		$items = explode("\n", $cell_value);
																		list($all_valid_options, $all_valid_options_ids) = getElementOptions($linkElement[2]['ele_handle'], $linkElement[2]['id_form']);
																		foreach ($items as $item)
																		{
																				$item_value = trim($item);

																				if (!in_array($item_value, $all_valid_options)) {
																						$foundit = false;
																						foreach ($all_valid_options as $thisoption) {
																								if (trim($item_value) == stripslashes(trim(trans($thisoption)))) { // stripslashes is necessary only because the data contains slashes in the database (which it should not, so this should be removed when that is fixed)
																										$foundit = true;
																										break;
																								}
																						}
																						if (!$foundit) {
																								$some_options = array_slice($all_valid_options, 0, 20);
																								$errors[] = "<li>line " . $rowCount .
																										", column " . $importSet[3][$link] .
																										",<br> <b>found</b>: " . $item_value .
																										", <b>was expecting values such as</b>: " .
																										stripslashes(implode(", ", $some_options)) . "</li>";
																						}
																				}
																		}
																}
														} else {
															$options = unserialize($element["ele_value"]);
															$uiTexts = unserialize($element["ele_uitext"]);
															$options = $options[2];
															if(strstr($cell_value, "\n")) {
															$items = explode("\n", $cell_value);
															} else {
																	$items = explode(",", $cell_value);
															}
															foreach ($items as $item) {
																	$item_value = trim($item);
			                            if (!in_array($item_value, (array)$options, true) AND !in_array($item_value, (array)$uiTexts, true)) {
																			// last option causes strict matching by type
																			$foundit = false;
																			$hasother = false;
																			foreach ($options as $thisoption=>$default_value) {
																					if (trim($item_value) == trim(trans($thisoption))) {
																							$foundit = true;
																					}
																					if (preg_match('/\{OTHER\|+[0-9]+\}/', $thisoption)) {
																							$hasother = true;
																					}
																			}
																			if (!$foundit AND !$hasother) {
																					$keys_output = implode(', ', array_keys((array)$options));
																					$errors[] = "<li>line " . $rowCount .
																							", column " . $importSet[3][$link] .
																							",<br> <b>found</b>: " . $item_value .
																							", <b>was expecting</b>: { " . $keys_output . " }</li>";
																			}
																	}
															}
														}
                            break;

                            case "radio":
                            $options = unserialize($element["ele_value"]);
														$uiTexts = unserialize($element["ele_uitext"]);
                            if (!in_array(trim($cell_value), (array)$options, true) AND !in_array(trim($cell_value), (array)$uiTexts, true)) {
                                // last option causes strict matching by type
                                // then do a check against the translated options
                                $foundit = false;
                                $hasother = false;
                                foreach ($options as $thisoption=>$default_value) {
                                    if (trim($cell_value) == trim(trans($thisoption))) {
                                        $foundit = true;
                                    }
                                    if (preg_match('/\{OTHER\|+[0-9]+\}/', $thisoption)) { $hasother = true; }
                                }

                                if (!$foundit AND !$hasother) {
                                    $keys_output = "";
                                    for (reset($options); $key = key($options); next($options)) {
                                        if ($keys_output != "") {
                                            $keys_output .= ", ";
                                        }
                                        $keys_output .= $key;
                                    }
                                    $errors[] = "<li>line " . $rowCount .
                                        ", column " . $importSet[3][$link] .
                                        ",<br> <b>found</b>: " . $cell_value .
                                        ", <b>was expecting</b>: { " . $keys_output . " }</li>";
                                }
                            }
                            break;


                            case "date":
                            $date_value = date("Y-m-d", strtotime($cell_value));
                            if ($date_value == "") {
                                $errors[] = "<li>line " . $rowCount .
                                    ", column " . $importSet[3][$link] .
                                    ",<br> <b>found</b>: " . $cell_value .
                                    ", <b>was expecting</b>: "._DATE_DEFAULT."</li>";
                            }
                            break;


                            case "yn":
                            if (is_numeric($cell_value)) {
                                if (!($cell_value == 1 || $cell_value == 2)) {
                                    $errors[] = "<li>line " . $rowCount .
                                        ", column " . $importSet[3][$link] .
                                        ",<br> <b>found</b>: " . $cell_value .
                                        ", <b>was expecting</b>: { 1, 2, " . _formulize_TEMP_QYES . ", " . _formulize_TEMP_QNO . " }</li>";
                                }
                            } else {
                                $yn_value = strtoupper($cell_value);

                                if (!($yn_value == strtoupper(_formulize_TEMP_QYES) || $yn_value == strtoupper(_formulize_TEMP_QNO))) {
                                    // changed to use language constants, June 29, 2006 {
                                    $errors[] = "<li>line " . $rowCount .
                                        ", column " . $importSet[3][$link] .
                                        ",<br> <b>found</b>: " . $cell_value .
                                        ", <b>was expecting</b>: { 1, 2, " . _formulize_TEMP_QYES . ", " . _formulize_TEMP_QNO . " }</li>";
                                }
                            }
                            break;

														case "provinceList":
														case "provinceRadio":
															$provinceElementHandler = xoops_getmodulehandler('provinceListElement', 'formulize');
															$provinces = $provinceElementHandler->getProvinceList();
															if(!in_array($cell_value, $provinces)) {
																$errors[] = "<li>line " . $rowCount .
																	", column " . $importSet[3][$link] .
																	",<br> <b>found</b>: " . $cell_value .
																	", <b>was expecting: { ".implode(", ", $provinces)." }</b></li>";
															}
															break;
                        }
                    }
                }
            }
        }
    }




                    if ($row_value != "") {
                        $switchEleType = anySelectElementType($element["ele_type"]) ? "select" : $element["ele_type"];
        								switch ($switchEleType) {

                            case "derived":
                                break; // ignore derived values for importing

                            case "select":
                            $ele_value = unserialize($element["ele_value"]);
                            if ($importSet[5][1][$link] AND !strstr($row_value, ",")
                                AND (!is_numeric($row_value) OR $row_value < 10000000))
                            {
                                // Linked element
                                $linkElement = $importSet[5][1][$link];
                                $ele_value = unserialize($element["ele_value"]);
                                list($all_valid_options, $all_valid_options_ids) = getElementOptions($linkElement[2]['ele_handle'], $linkElement[2]['id_form']);
                                if ($ele_value[1] AND !($ele_value['snapshot'] AND $ele_value[16])) {
                                    // Multiple options
                                    $element_value = $linkElement[0] . "#*=:*" .
                                    $linkElement[1] . "#*=:*";
                                    $items = explode("\n", $row_value);
                                    if($ele_value['snapshot']) {
                                        $row_value = '';
                                        if(count((array) $items)>1) {
                                            $row_value .= '*=+*:';
                                        }
                                        $row_value .= implode('*=+*:',$items);
                                    } else {
                                    $row_value = ",";
                                    foreach ($items as $item) {
                                        $item_value = trim($item);
                                        if ($optionIndex = array_search($item_value, $all_valid_options)) {
                                            $ele_id = $all_valid_options_ids[$optionIndex];
                                        } else {
                                            foreach ($all_valid_options as $optionIndex=>$thisoption) {
                                                if (trim($item_value) == trim(trans($thisoption))) {
                                                    $item_value = $thisoption;
                                                    $ele_id = $all_valid_options_ids[$optionIndex];
                                                    break;
                                                }
                                            }
                                        }
                                        $row_value .= $ele_id . ",";
                                    }
                                    }
                                } elseif(!($ele_value['snapshot'] AND $ele_value[16])) {
                                    // Single option
                                    if($ele_value['snapshot']) {
                                        $row_value = $row_value; // take the validated item the user has put into the box
                                        break;
																		} elseif(intval($row_value)>0 AND in_array($row_value, $all_valid_options_ids)) {
																				$ele_id = $row_value;
                                    } elseif ($optionIndex = array_search($row_value, $all_valid_options)) {
                                        $ele_id = $all_valid_options_ids[$optionIndex];
                                    } else {
                                        foreach ($all_valid_options as $optionIndex=>$thisoption) {
                                            if (trim($row_value) == trim(trans($thisoption))) {
                                                $row_value = $thisoption;
                                                $ele_id = $all_valid_options_ids[$optionIndex];
                                                break;
                                            }
                                        }
                                    }
                                    $row_value = $ele_id;
                                }
                            } elseif (!strstr($row_value, ",") AND (!is_numeric($row_value) OR $row_value < 10000000)) {
                                // Not-Linked element
                                $ele_value = unserialize($element["ele_value"]);

                                // handle fullnames or usernames
                                $temparraykeys = array_keys($ele_value[2]);
                                if ($temparraykeys[0] === "{FULLNAMES}" OR $temparraykeys[0] === "{USERNAMES}") { // ADDED June 18 2005 to handle pulling in usernames for the user's group(s) -- updated for real live use September 6 2006
                                    if ($temparraykeys[0] === "{FULLNAMES}") {
                                        $nametype = "name";
                                    }
                                    if ($temparraykeys[0] === "{USERNAMES}") {
                                        $nametype = "uname";
                                    }
                                    if (!isset($fullnamelist)) {
                                        $fullnamelistq = q("SELECT uid, $nametype FROM " . $xoopsDB->prefix("users") . " ORDER BY uid");
                                        static $fullnamelist = array();
                                        foreach ($fullnamelistq as $thisname) {
                                            $fullnamelist[$thisname['uid']] = $thisname[$nametype];
                                        }
                                    }
                                    if ($ele_value[1]) {
                                        // multiple
                                        $items = explode("\n", $row_value);
                                    } else {
                                        $items = array(0=>$row_value);
                                    }
                                    $numberOfNames = 0;
                                    $row_value = "";
                                    foreach ($items as $item) {
                                        if (is_numeric($item)) {
                                            $row_value .= "*=+*:" . $item;
                                            $numberOfNames++;
                                        } else {
                                            $uids = array_keys ($fullnamelist, $item);
                                            // instead of matching on all values, like we used to, match only the first name found (lowest user id)
                                            // to match other users besides the first one, use a user id number instead of a name in the import spreadsheet
                                            $row_value .= "*=+*:" . $uids[0];
                                            $numberOfNames++;
                                        }
                                    }
                                    if ($numberOfNames == 1) {
                                        // single entries are not supposed to have the separator at the front
                                        $row_value = substr_replace($row_value, "", 0, 5);
                                    }
                                    break;
                                }

                                if ($ele_value[1]) {
                                    // Multiple options
                                    $element_value = "";
                                    $options = $ele_value[2];
																		$uiTexts = unserialize($element["ele_uitext"]);
                                    $items = explode("\n", $row_value);
                                    foreach ($items as $item) {
                                        $item_value = trim($item);
																				if(in_array($item_value, (array)$uiTexts, true)) {
																					$item_value = array_search($item_value, $uiTexts, true);
																				} elseif (!in_array($item_value, (array)$options, true)) {
                                            // last option causes strict matching by type
                                            foreach ($options as $thisoption=>$default_value) {
                                                if ($item_value == trim(trans($thisoption))) {
                                                    $item_value = $thisoption;
                                                    break;
                                                }
                                            }
                                        }
                                        $element_value .= "*=+*:" . $item_value;
                                    }
                                    $row_value = $element_value;
                                } else {
                                    // Single option
                                    $options = $ele_value[2];
																		$uiTexts = unserialize($element["ele_uitext"]);
																		if(in_array(trim($row_value), (array)$uiTexts, true)) {
																			$row_value = array_search(trim($row_value), $uiTexts, true);
																		} elseif (!in_array(trim($row_value), (array)$options, true)) {
																			// last option causes strict matching by type
																			foreach ($options as $thisoption=>$default_value) {
																					if (trim($row_value) == trim(trans($thisoption))) {
																							$row_value = $thisoption;
																							break;
																					}
																			}
                                    }
                                }
                            } elseif (strstr($row_value, ",") OR (is_numeric($row_value) AND $row_value > 10000000)) {
                                // the value is a comma separated list of linked values, so we need to add commas before and after, to adhere to the Formulize data storage spec
                                if (substr($row_value, 0, 1)!=",") {
                                    $row_value = ",".$row_value;
                                }
                                if (substr($row_value, -1)!=",") {
                                    $row_value = $row_value.",";
                                }
                            }
                            break;


                            case "checkbox":
														case "checkboxLinked":
															$ele_value = unserialize($element["ele_value"]);
															if ($importSet[5][1][$link] AND !strstr($row_value, ",")
																	AND (!is_numeric($row_value) OR $row_value < 10000000))
															{
																	// Linked element
																	$linkElement = $importSet[5][1][$link];
																	$ele_value = unserialize($element["ele_value"]);
																	list($all_valid_options, $all_valid_options_ids) = getElementOptions($linkElement[2]['ele_handle'], $linkElement[2]['id_form']);
																	if (!$ele_value['snapshot']) {
																			// Multiple options
																			$element_value = $linkElement[0] . "#*=:*" .
																			$linkElement[1] . "#*=:*";
																			$items = explode("\n", $row_value);
																			if($ele_value['snapshot']) {
																					$row_value = '';
																					if(count((array) $items)>1) {
																							$row_value .= '*=+*:';
																					}
																					$row_value .= implode('*=+*:',$items);
																			} else {
																				$row_value = ",";
																				foreach ($items as $item) {
																						$item_value = trim($item);
																						if ($optionIndex = array_search($item_value, $all_valid_options)) {
																								$ele_id = $all_valid_options_ids[$optionIndex];
																						} else {
																								foreach ($all_valid_options as $optionIndex=>$thisoption) {
																										if (trim($item_value) == trim(trans($thisoption))) {
																												$item_value = $thisoption;
																												$ele_id = $all_valid_options_ids[$optionIndex];
																												break;
																										}
																								}
																						}
																						$row_value .= $ele_id . ",";
																				}
																			}
																	}
																} else {
																	$options = unserialize($element["ele_value"]);
																	$uiTexts = unserialize($element["ele_uitext"]);
																	$element_value = "";
																	$options = $options[2];
																	if(strstr($row_value, "\n")) {
																			$items = explode("\n", $row_value);
																	} else {
																			$items = explode(",", $row_value);
																	}
																	foreach ($items as $item) {
																			$item_value = trim($item);
																			if (!in_array($item_value, (array)$options, true)) {
																					// last option causes strict matching by type
																					$foundit = false;
																					$hasother = false;
																					foreach ($options as $thisoption=>$default_value) {
																							if ($item_value == trim(trans($thisoption))) {
																									$item_value = $thisoption;
																									$foundit = true;
																							}
																							if (preg_match('/\{OTHER\|+[0-9]+\}/', $thisoption)) {
																									$hasother = $thisoption;
																							}
																							if($foundit AND $hasother) { break; } // no need to keep looking for stuff
																					}
																					if ($foundit) {
																							$element_value .= "*=+*:" . $item_value;
																					} elseif(in_array($item_value, (array)$uiTexts, true)) {
																							$element_value .= "*=+*:" . array_search($item_value, $uiTexts, true);
																					} elseif ($hasother) {
																							$other_values[] = "INSERT INTO " . $xoopsDB->prefix("formulize_other") . " (id_req, ele_id, other_text) VALUES (\"$max_id_req\", \"" . $element["ele_id"] . "\", \"" . $myts->htmlSpecialChars(trim($item_value)) . "\")";
																							$element_value .= "*=+*:" . $hasother;
																					} elseif ($validateData) {
																							print "ERROR: INVALID TEXT FOUND FOR A CHECKBOX ITEM -- $item_value -- IN ROW:<BR>";
																							print_r($row);
																							print "<br><br>";
																					}
																			} else {
																					$element_value .= "*=+*:" . $item_value;
																			}
																	}
																	$row_value = $element_value;
																}
                            break;

														case "radio":
                            $options = unserialize($element["ele_value"]);
														$uiTexts = unserialize($element["ele_uitext"]);
														if (!in_array(trim($row_value), (array)$options, true)) {
                                // last option causes strict matching by type
                                $foundit = false;
                                $hasother = false;
                                foreach ($options as $thisoption=>$default_value) {
                                    if (trim($row_value) == trim(trans($thisoption))) {
                                        $row_value = $thisoption;
                                        $foundit = true;
                                        break;
                                    }
                                    if (preg_match('/\{OTHER\|+[0-9]+\}/', $thisoption)) {
                                        $hasother = $thisoption;
                                    }
                                }
																if(!$foundit AND in_array(trim($row_value), (array)$uiTexts, true)) {
																	$row_value = array_search(trim($row_value), $uiTexts, true);
																} elseif (!$foundit AND $hasother) {
                                    $other_values[] = "INSERT INTO " . $xoopsDB->prefix("formulize_other") . " (id_req, ele_id, other_text) VALUES (\"$max_id_req\", \"" . $element["ele_id"] . "\", \"" . $myts->htmlSpecialChars(trim($row_value)) . "\")";
                                    $row_value = $hasother;
                                } elseif (!$foundit AND !$validateOverride) {
                                    print "ERROR: INVALID TEXT FOUND FOR A RADIO BUTTON ITEM -- $row_value -- IN ROW:<BR>";
                                    print_r($row);
                                    print "<br><br>";
                                }
                            }
                            break;


                            case "date":
                            $row_value = date("Y-m-d", strtotime(str_replace("/", "-", $row_value)));
                            break;


                            case "yn":
                            if (!is_numeric($row_value)) {
                                $yn_value = strtoupper($row_value);

                                if ($yn_value == "YES")
                                    $row_value = 1;
                                else if ($yn_value == "NO")
                                    $row_value = 2;
                            }
                            break;

														case "text":
															$textElementHandler = xoops_getmodulehandler('textElement', 'formulize');
															$row_value = $textElementHandler->prepareDataForSaving($row_value, $textElementHandler->get($element['ele_handle']));
															break;
														case "textarea":
															$row_value = trim($row_value);
															break;

														case "provinceList":
														case "provinceRadio":
															$provinceElementHandler = xoops_getmodulehandler('provinceListElement', 'formulize');
															$provinces = $provinceElementHandler->getProvinceList();
															$row_value = array_search($row_value, $provinces);
															break;
                        }

                        // record the values for inserting as part of this record
                        // prior to 3.0 we did not do the htmlspecialchars conversion if this was a linked selectbox...don't think that's a necessary exception in 3.0 with new data structure

												$elementObject = $element_handler->get($element['ele_handle']);
												if($elementObject->hasData) {
                        	$fieldValues[$element['ele_handle']] = $myts->htmlSpecialChars($row_value);
												}

                    } // end of if there's a value in the current column
                } elseif (isset($importSet[7]['usethisentryid']) AND $link == $importSet[7]['usethisentryid']) {
                    // if this is not a valid column, but it is an entry id column, then capture the entry id from the cell
                    $newEntryId = $row[$link] ? intval($row[$link]) : "";
                } // end of if this is a valid column
            } // end of looping through $links (columns?)







// ************************
// writing logic here below.....












            // now that we've recorded all the values, do the actual updating/inserting of this record
						if(IMPORT_WRITE
							AND $userHasImportPermission
							AND (
								$data_handler->entryExists($this_id_req) == false
								OR formulizePermHandler::user_can_edit_entry($fid, ($xoopsUser ? $xoopsUser->getVar('uid') : 0), $this_id_req)
							)) {

								$writeEntryUid = $form_proxyid; // the creation/mod user will be the form_proxyid, which will be the active xoops user id
								$writeEntryEntryId = $this_id_req;
								$notType = 'update_entry';

								// if we're making a new entry, then...
								// use any user specified in the row as the creation user
								if($data_handler->entryExists($this_id_req) == false) {
									$notType = 'new_entry';
									$writeEntryEntryId = 'new';
									if ($form_uid != 0) {
										$writeEntryUid = $form_uid;
									}
								}

								$resultEntryId = $data_handler->writeEntry($writeEntryEntryId, $fieldValues, $writeEntryUid, forceUpdate: true);

								if($resultEntryId) {
									$entriesMap[] = $resultEntryId;
									$notEntriesList[$notType][$importSet[4]][] = $resultEntryId; // log the notification info
									if($writeEntryEntryId == 'new') {
										$usersMap[] = $writeEntryUid;
										$newEntriesMap[] = $resultEntryId;
									}
								} elseif($resultEntryId !== null) {
									static $duplicatesFound = false;
									if (strstr($xoopsDB->error(), "Duplicate entry")) {
										if (!$duplicatesFound) {
											print "<br><b>FAILED</b> to create/update an entry. At least one duplicate value was found in a column that does not allow duplicate values.<br>";
											$duplicatesFound = true;
										}
									} else {
										print "<br><b>FAILED</b> to create/update data for entry $writeEntryEntryId.<br>".$xoopsDB->error()."<br>";
									}
								}
							}

        } // end of if we have contents in this row
    } // end of looping through each row of the file

    if (count((array) $usersMap) > 0) {
        // if new entries were created...
        include_once XOOPS_ROOT_PATH . "/modules/formulize/class/data.php";
        $data_handler = new formulizeDataHandler($id_form);
        if (!$groupResult = $data_handler->setEntryOwnerGroups($usersMap, $newEntriesMap)) {
            print "ERROR: failed to write the entry ownership information to the database.<br>".$xoopsDB->error()."<br>";
        }
    }

		// *** COME BACK AS PART OF VALIDATION??
    // insert all the other values that were recorded
    foreach ($other_values as $other) {
        if (!$result = $xoopsDB->query($other)) {
            print "ERROR: could not insert 'other' value: $other<br>";
        }
    }
    // fid is $importSet[4] ?!!
    $GLOBALS['formulize_snapshotRevisions'][$importSet[4]] = formulize_getCurrentRevisions($importSet[4], $entriesMap);

    if(isset($_POST['updatederived']) AND $_POST['updatederived']) {
    // update derived values based on the form only
        $ele_types = $formObject->getVar('elementTypes');
        if(in_array('derived',$ele_types)) {
    foreach($entriesMap as $entry) {
        //if($entry > 200) { break; }
        //print "Entry $entry Memory usage: ".memory_get_usage()."<br>";
        $GLOBALS['formulize_doNotCacheDataSet'] = true;
        formulize_updateDerivedValues($entry, $importSet[4]); // 4 is the form id
    }
        }
    }

    if(isset($_POST['sendnotifications']) AND $_POST['sendnotifications']) {
    // send notifications
    foreach($notEntriesList as $notEvent=>$notDetails) {
        foreach($notDetails as $notFid=>$notEntries) {
            $notEntries = array_unique($notEntries);
            sendNotifications($notFid, $notEvent, $notEntries);
        }
    }
    }
}


// This function returns the saved values in the data table for the element it is passed
// It also returns a parallel array that contains the entry ids that each value belongs to
//function getElementOptions($id_form, $ele_caption)
function getElementOptions($ele_handle, $fid) {
    static $cachedElementOptions = array();
    if (!isset($cachedElementOptions[$fid][$ele_handle])) {
        global $xoopsDB, $myts;
        $form_handler = xoops_getmodulehandler('forms', 'formulize');
        $formObject = $form_handler->get(intval($fid));
        $result = array();
        if (!$myts) {
            $myts =& MyTextSanitizer::getInstance();
        }

        $sql = "SELECT entry_id, `".$ele_handle."` FROM " . $xoopsDB->prefix("formulize_".$formObject->getVar('form_handle'));
        $res = $xoopsDB->query($sql);
        $result = array();
        $resultIDs = array();
        while ($item = $xoopsDB->fetchArray($res)) {
            $result[] = $myts->undoHtmlSpecialChars($item[$ele_handle]);
            $resultIDs[] = $item['entry_id'];
        }
        $cachedElementOptions[$fid][$ele_handle] = array(0=>$result, 1=>$resultIDs);
    }
    return $cachedElementOptions[$fid][$ele_handle];
}

function getUserID($stringName) {
    global $xoopsDB, $xoopsUser;

		// if no valid name passed in, return current user's id
		if(!$stringName OR (!is_numeric($stringName) AND trim($stringName) == "")) {
			return $xoopsUser->getVar('uid');
		}

		// if passed a number, return that
		if (is_numeric($stringName)) {
			return $stringName;
		}

		// try to look up user by full name
    $sql = "SELECT uid FROM " . $xoopsDB->prefix("users") .
        " WHERE uname='" . formulize_db_escape($stringName) . "'";
    $result = $xoopsDB->query($sql);
    if ($xoopsDB->getRowsNum($result) > 0) {
        $item = $xoopsDB->fetchArray($result);
        if (@$item["uid"]) {
            return $item["uid"];
        }

		// try to lookup by login name
    } else {
        // or, if no username match found, get the first matching full name -- added June 29, 2006
        $sql = "SELECT uid FROM " . $xoopsDB->prefix("users") .
        " WHERE login_name='" . formulize_db_escape($stringName) . "'";

        if ($result = $xoopsDB->query($sql)) {
            $item = $xoopsDB->fetchArray($result);
            if (@$item["uid"]) {
                return $item["uid"];
            }
        }
    }

    // instead of returning nothing, return the current user's ID -- added June 29, 2006
    return $xoopsUser->getVar('uid');
}


function getImportEntryIdFromPkColumnValue($value, $element) {
	if($value==="") { return ""; }
	$handle = $element['ele_handle'];
	$data_handler = new formulizeDataHandler($element["id_form"]);
	return $data_handler->findFirstEntryWithValue($handle, $value);
}
