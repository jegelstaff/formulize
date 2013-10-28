<?php
/**
 * Extended User Profile
 *
 *
 *
 * @copyright       The ImpressCMS Project http://www.impresscms.org/
 * @license         LICENSE.txt
 * @license			GNU General Public License (GPL) http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @package         modules
 * @since           1.2
 * @author          Jan Pedersen
 * @author          Marcello Brandao <marcello.brandao@gmail.com>
 * @author	   		Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @version         $Id: search.php 21139 2011-03-20 20:58:11Z m0nty_ $
 */

include '../../mainfile.php';
$modname = basename(dirname(__FILE__));
if (!icms::$module->config['profile_social']) redirect_header(icms_getPreviousPage('index.php'), 3, _NOPERM);

icms_loadLanguageFile('core', 'user');
$op = isset($_REQUEST['op']) ? htmlspecialchars($_REQUEST['op']) : 'search';
$groups = icms::$user ? icms::$user->getGroups() : array(ICMS_GROUP_ANONYMOUS);
switch ($op) {
    default:
    case "search":
        $xoopsOption['cache_group'] = implode('', $groups);
        $xoopsOption['template_main'] = "profile_search.html";
        include ICMS_ROOT_PATH."/header.php";

        // Dynamic fields
        $profile_handler = icms_getModuleHandler('profile', basename(dirname(__FILE__)), 'profile');
        // Get fields
        $fields =& $profile_handler->loadFields();
        // Get ids of fields that can be searched
        $searchable_fields = icms::handler('icms_member_groupperm')->getItemIds('profile_search', $groups, icms::$module->getVar('mid'));

        $searchform = new icms_form_Theme("", "searchform", "search.php", "post");

        $name_tray = new icms_form_elements_Tray(_US_NICKNAME);
        $name_tray->addElement(new icms_form_elements_select_Matchoption('', 'uname_match'));
        $name_tray->addElement(new icms_form_elements_Text('', 'uname', 35, 255));
        $searchform->addElement($name_tray);
        $sortby_arr['uname'] = _US_NICKNAME;

        $email_tray = new icms_form_elements_Tray(_MD_PROFILE_EMAIL);
        $email_tray->addElement(new icms_form_elements_select_Matchoption('', 'email_match'));
        $email_tray->addElement(new icms_form_elements_Text('', 'email', 35, 255));
        $searchform->addElement($email_tray);
        $sortby_arr['email'] = _MD_PROFILE_EMAIL;

        $searchable_types = array('textbox',
        'select',
        'radio',
        'yesno',
        'date',
        'datetime',
        'timezone',
        'language');
        foreach (array_keys($fields) as $i) {
            if (in_array($fields[$i]->getVar('fieldid'), $searchable_fields) && in_array($fields[$i]->getVar('field_type'), $searchable_types)) {
                $sortby_arr[$fields[$i]->getVar('fieldid')] = $fields[$i]->getVar('field_title');
                switch ($fields[$i]->getVar('field_type')) {
                    case "textbox":
                        if ($fields[$i]->getVar('field_valuetype') == XOBJ_DTYPE_INT) {
                            $searchform->addElement(new icms_form_elements_Text(sprintf(_MD_PROFILE_LARGERTHAN, $fields[$i]->getVar('field_title')), $fields[$i]->getVar('field_name')."_larger", 35, 35));
                            $searchform->addElement(new icms_form_elements_Text(sprintf(_MD_PROFILE_SMALLERTHAN, $fields[$i]->getVar('field_title')), $fields[$i]->getVar('field_name')."_smaller", 35, 35));
                        }
                        else {
                            $tray = new icms_form_elements_Tray($fields[$i]->getVar('field_title'));
                            $tray->addElement(new icms_form_elements_select_Matchoption('', $fields[$i]->getVar('field_name')."_match"));
                            $tray->addElement(new icms_form_elements_Text('', $fields[$i]->getVar('field_name'), 35, $fields[$i]->getVar('field_maxlength')));
                            $searchform->addElement($tray);
                            unset($tray);
                        }
                        break;

                    case "radio":
                    case "select":
                        $options = unserialize($fields[$i]->getVar('field_options', 'n'));
                        $size = count($options) > 10 ? 10 : count($options);
                        $element = new icms_form_elements_Select($fields[$i]->getVar('field_title'), $fields[$i]->getVar('field_name'), null, $size, true);
                        asort($options);
                        $element->addOptionArray($options);
                        $searchform->addElement($element);
                        unset($element);
                        break;

                    case "yesno":
                        $element = new icms_form_elements_Select($fields[$i]->getVar('field_title'), $fields[$i]->getVar('field_name'), null, 2, true);
                        $element->addOption(1, _YES);
                        $element->addOption(0, _NO);
                        $searchform->addElement($element);
                        unset($element);
                        break;

                    case "date":
                    case "datetime":
                        $searchform->addElement(new icms_form_elements_Date(sprintf(_MD_PROFILE_LATERTHAN, $fields[$i]->getVar('field_title')), $fields[$i]->getVar('field_name')."_larger", 15, 0));
                        $searchform->addElement(new icms_form_elements_Date(sprintf(_MD_PROFILE_EARLIERTHAN, $fields[$i]->getVar('field_title')), $fields[$i]->getVar('field_name')."_smaller", 15, time()));
                        break;

                    case "timezone":
                        $element = new icms_form_elements_Select($fields[$i]->getVar('field_title'), $fields[$i]->getVar('field_name'), null, 6, true);
                        $element->addOptionArray(icms_form_elements_select_Timezone::getTimeZoneList());
                        $searchform->addElement($element);
                        unset($element);
                        break;

                    case "language":
                        $element = new icms_form_elements_select_Lang($fields[$i]->getVar('field_title'), $fields[$i]->getVar('field_name'), null, 6);
                        $searchform->addElement($element);
                        unset($element);
                        break;
                }
            }
        }
        asort($sortby_arr);
        $sortby_select = new icms_form_elements_Select(_MD_PROFILE_SORTBY, 'sortby');
        $sortby_select->addOptionArray($sortby_arr);
        $searchform->addElement($sortby_select);

        $order_select = new icms_form_elements_Radio(_MD_PROFILE_ORDER, 'order', 0);
        $order_select->addOption(0, _ASCENDING);
        $order_select->addOption(1, _DESCENDING);
        $searchform->addElement($order_select);

        $limit_text = new icms_form_elements_Text(_MD_PROFILE_PERPAGE, 'limit', 15, 10);
        $searchform->addElement($limit_text);
        $searchform->addElement(new icms_form_elements_Hidden('op', 'results'));

        $searchform->addElement(new icms_form_elements_Button('', 'submit', _SUBMIT, 'submit'));

        $searchform->assign($xoopsTpl);
        break;

    case "results":
        $xoopsOption['template_main'] = "profile_results.html";
        include_once ICMS_ROOT_PATH."/header.php";

        $member_handler = icms::handler('icms_member');
        // Dynamic fields
        $profile_handler = icms_getModuleHandler('profile', basename(dirname(__FILE__)), 'profile');
        // Get fields
        $fields = $profile_handler->loadFields();
        // Get ids of fields that can be searched
        $searchable_fields = icms::handler('icms_member_groupperm')->getItemIds('profile_search', $groups, icms::$module->getVar('mid'));
        $searchvars = array();
	    $search_url = array();

        $criteria = new icms_db_criteria_Compo(new icms_db_criteria_Item('level', 0, ">"));
        if (isset($_REQUEST['uname']) && $_REQUEST['uname'] != "") {
            $string = icms_core_DataFilter::addSlashes(trim($_REQUEST['uname']));
		    $search_url[] = 'uname='. $string;
            switch ($_REQUEST['uname_match']) {
                case XOOPS_MATCH_START:
                    $string .= "%";
                    break;

                case XOOPS_MATCH_END:
                    $string = "%".$string;
                    break;

                case XOOPS_MATCH_CONTAIN:
                    $string = "%".$string."%";
                    break;
            }
            $criteria->add(new icms_db_criteria_Item('uname', $string, "LIKE"));
            $searchvars[] = "uname";
        }
        if (isset($_REQUEST['email']) && $_REQUEST['email'] != "") {
            $string = icms_core_DataFilter::addSlashes(trim($_REQUEST['email']));
		    $search_url[] = 'email='. $string;
            switch ($_REQUEST['email_match']) {
                case XOOPS_MATCH_START:
                    $string .= "%";
                    break;

                case XOOPS_MATCH_END:
                    $string = "%".$string;
                    break;

                case XOOPS_MATCH_CONTAIN:
                    $string = "%".$string."%";
                    break;
            }
            $searchvars[] = "email";
            $criteria->add(new icms_db_criteria_Item('email', $string, "LIKE"));
            $criteria->add(new icms_db_criteria_Item('user_viewemail', 1));
        }
        $searchable_types = array('textbox',
        'select',
        'radio',
        'yesno',
        'date',
        'datetime',
        'timezone',
        'language');

        foreach (array_keys($fields) as $i) {
            if (in_array($fields[$i]->getVar('fieldid'), $searchable_fields) && in_array($fields[$i]->getVar('field_type'), $searchable_types)) {
                $fieldname = $fields[$i]->getVar('field_name');
                if (in_array($fields[$i]->getVar('field_type'), array("select", "radio"))) {
                    if (isset($_REQUEST[$fieldname]) && $_REQUEST[$fieldname]) {
                        //If field value is sent through request and is not an empty value
                        switch ($fields[$i]->getVar('field_valuetype')) {
                            case XOBJ_DTYPE_OTHER:
                            case XOBJ_DTYPE_INT:
                                $value = array_map('intval', $_REQUEST[$fieldname]);
                                $searchvars[] = $fieldname;
                                $criteria->add(new icms_db_criteria_Item($fieldname, "(".implode(',', $value).")", "IN"));
                                break;

                            case XOBJ_DTYPE_URL:
                            case XOBJ_DTYPE_TXTBOX:
                            case XOBJ_DTYPE_TXTAREA:
                                $value = array_map(array(icms::$xoopsDB, "quoteString"), $_REQUEST[$fieldname]);
                                $searchvars[] = $fieldname;
                                $criteria->add(new icms_db_criteria_Item($fieldname, "(".implode(',', $value).")", "IN"));
                                break;
                        }
                    }
                }
                else {
                    switch ($fields[$i]->getVar('field_valuetype')) {
                        case XOBJ_DTYPE_OTHER:
                        case XOBJ_DTYPE_INT:
                            switch ($fields[$i]->getVar('field_type')) {
                                case "date":
                                case "datetime":
                                    $value = $_REQUEST[$fieldname."_larger"];
                                    if (!($value = strtotime($_REQUEST[$fieldname."_larger"]))) {
                                        $value = (int)$_REQUEST[$fieldname."_larger"];
                                    }
                                    if ($value > 0) {
                                        $search_url[] = $fieldname."_larger=".$value;
                                        $searchvars[] = $fieldname;
                                        $criteria->add(new icms_db_criteria_Item($fieldname, $value, ">="));
                                    }
                                    
                                    $value = $_REQUEST[$fieldname."_smaller"];
                                    if (!($value = strtotime($_REQUEST[$fieldname."_smaller"]))) {
                                        $value = (int)$_REQUEST[$fieldname."_smaller"];
                                    }
                                    if ($value > 0) {
                                        $search_url[] = $fieldname."_smaller=".$value;
                                        $searchvars[] = $fieldname;
                                        $criteria->add(new icms_db_criteria_Item($fieldname, $value, "<="));
                                    }
                                    break;
                                    
//                                case "datetime":
//                                    $value = $_REQUEST[$fieldname."_larger"]['date'];
//                                    if ((int)$value < 0) { //(int) of a date string is -1
//                                        $value = strtotime($_REQUEST[$fieldname."_larger"]['date']);
//                                    }
//                                    else {
//                                        $value = (int)$_REQUEST[$fieldname."_larger"]['date'];
//                                    }
//                                    $search_url[] = $fieldname."_larger=".$value;
//                                    $searchvars[] = $fieldname;
//                                    $criteria->add(new icms_db_criteria_Item($fieldname, $value, ">="));
//                                    
//                                    $value = $_REQUEST[$fieldname."_smaller"]['date'];
//                                    if ((int)$value < 0) { //(int) of a date string is -1
//                                        $value = strtotime($_REQUEST[$fieldname."_smaller"]['date']);
//                                    }
//                                    else {
//                                        $value = (int)$_REQUEST[$fieldname."_smaller"]['date'];
//                                    }
//                                    $search_url[] = $fieldname."_smaller=".$value;
//                                    $searchvars[] = $fieldname;
//                                    $criteria->add(new icms_db_criteria_Item($fieldname, $value, "<="));
//                                    break;
                                    
                                default:
                                    if (isset($_REQUEST[$fieldname."_larger"]) && (int)$_REQUEST[$fieldname."_larger"] != 0) {
                                        $value = (int)$_REQUEST[$fieldname."_larger"];
                                        $search_url[] = $fieldname."_larger=".$value;
                                        $searchvars[] = $fieldname;
                                        $criteria->add(new icms_db_criteria_Item($fieldname, $value, ">="));
                                    }
                                    
                                    if (isset($_REQUEST[$fieldname."_smaller"]) && (int)$_REQUEST[$fieldname."_smaller"] != 0) {
                                        $value = (int)$_REQUEST[$fieldname."_smaller"];
                                        $search_url[] = $fieldname."_smaller=".$value;
                                        $searchvars[] = $fieldname;
                                        $criteria->add(new icms_db_criteria_Item($fieldname, $value, "<="));
                                    }
                                    break;
                            }
                         
                            if (isset($_REQUEST[$fieldname]) && !isset($_REQUEST[$fieldname."_smaller"]) && !isset($_REQUEST[$fieldname."_larger"])) {
                                if (!is_array($_REQUEST[$fieldname])) {
                                    $value = (int)$_REQUEST[$fieldname];
                                    $search_url[] = $fieldname."=".$value;
                                    $criteria->add(new icms_db_criteria_Item($fieldname, $value, "="));
                                }
                                else {
                                    $value = array_map("intval", $_REQUEST[$fieldname]);
                                    foreach ($value as $thisvalue) {
                                        $search_url[] = $fieldname."[]=".$thisvalue;
                                    }
                                    $criteria->add(new icms_db_criteria_Item($fieldname, "(".implode(',', $value).")", "IN"));
                                }

                                $searchvars[] = $fieldname;
                            }
                            break;

                        case XOBJ_DTYPE_URL:
                        case XOBJ_DTYPE_TXTBOX:
                        case XOBJ_DTYPE_TXTAREA:
                            if (isset($_REQUEST[$fieldname]) && $_REQUEST[$fieldname] != "") {
                                $value = icms_core_DataFilter::addSlashes(trim($_REQUEST[$fieldname]));
                                switch ($_REQUEST[$fieldname.'_match']) {
                                    case XOOPS_MATCH_START:
                                        $value .= "%";
                                        break;

                                    case XOOPS_MATCH_END:
                                        $value = "%".$value;
                                        break;

                                    case XOOPS_MATCH_CONTAIN:
                                        $value = "%".$value."%";
                                        break;
                                }
                                $search_url[] = $fieldname."=".$value;
                                $operator = "LIKE";
                                $criteria->add(new icms_db_criteria_Item($fieldname, $value, $operator));
                                $searchvars[] = $fieldname;
                            }
                            break;
                    }
                }
            }
        }

        if ($searchvars == array()) {
            $searchvars[] = 'uname';
        }

        if ($_REQUEST['sortby'] == "name") {
            $criteria->setSort("name");
        }
        elseif ($_REQUEST['sortby'] == "email") {
            $criteria->setSort("email");
        }
        elseif ($_REQUEST['sortby'] == "uname") {
            $criteria->setSort("uname");
        }
        elseif (isset($fields[$_REQUEST['sortby']])) {
            $criteria->setSort($fields[$_REQUEST['sortby']]->getVar('field_name'));
        }
        $order = $_REQUEST['order'] == 0 ? "ASC" : "DESC";
        $criteria->setOrder($order);

        $limit = isset($_REQUEST['limit']) && (int)$_REQUEST['limit'] > 0 ? (int)$_REQUEST['limit'] : 20;
        $criteria->setLimit($limit);

        $start = isset($_REQUEST['start']) ? (int)$_REQUEST['start'] : 0;
        $criteria->setStart($start);

        //Get users based on criteria
        $profile_handler = icms_getModuleHandler('profile', basename(dirname(__FILE__)), 'profile');
        list($users, $profiles, $total_users) = $profile_handler->search($criteria, $searchvars);

        //Sort information
        if (is_object(icms::$user)) {
        	$isAdmin = icms::$user->isAdmin();
        } else {
        	$isAdmin = false;
        }
	$link_target = icms::$module->config['profile_social'] ? 'index.php' : 'userinfo.php';
        foreach (array_keys($users) as $k) {
            $userarray["output"][] = "<a href='".$link_target."?uid=".(int)$users[$k]->getVar('uid')."'>".$users[$k]->getVar('uname')."</a>";
            if (is_object(icms::$user)) $userarray["output"][] = $users[$k]->getVar('user_viewemail') || $isAdmin ? $users[$k]->getVar('email') : "";

            foreach (array_keys($fields) as $i) {
                if (in_array($fields[$i]->getVar('fieldid'), $searchable_fields) && in_array($fields[$i]->getVar('field_type'), $searchable_types) && in_array($fields[$i]->getVar('field_name'), $searchvars)) {
                    $userarray["output"][] = $fields[$i]->getOutputValue($users[$k], $profiles[$k]);
                }
            }
            $xoopsTpl->append('users', $userarray);
            unset($userarray);
        }

        //Get captions
        $captions[] = _US_NICKNAME;
        if (is_object(icms::$user)) $captions[] = _MD_PROFILE_EMAIL;
        foreach (array_keys($fields) as $i) {
            if (in_array($fields[$i]->getVar('fieldid'), $searchable_fields) && in_array($fields[$i]->getVar('field_type'), $searchable_types) && in_array($fields[$i]->getVar('field_name'), $searchvars)) {
                $captions[] = $fields[$i]->getVar('field_title');
            }
        }
        $xoopsTpl->assign('captions', $captions);

        if ($total_users > $limit) {
            $search_url[] = "op=results";
            $search_url[] = "order=".$order;
            $search_url[] = "sortby=".$_REQUEST['sortby'];
            $search_url[] = "limit=".$limit;
            $args = implode("&amp;", $search_url);
            $nav = new icms_view_PageNav($total_users, $limit, $start, "start", $args);
            $xoopsTpl->assign('nav', $nav->renderNav(5));
        }
        break;
}
include ICMS_ROOT_PATH."/footer.php";