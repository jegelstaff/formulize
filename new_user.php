<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
$xoopsOption['template_main'] = 'profile_register.html';
include "mainfile.php";

include "header.php";
include ICMS_ROOT_PATH .'/include/registerform.php';
include_once ICMS_ROOT_PATH .'/include/functions.php';
include_once ICMS_ROOT_PATH . '/modules/formulize/include/functions.php';
include_once ICMS_ROOT_PATH .'/modules/profile/include/forms.php';
include_once ICMS_ROOT_PATH . '/modules/profile/language/english/main.php';


//protect against an attempt to directly enter the url into the browser for this page. Don't want it to be too public.
if (isset($_GET['newuser']) && ($_GET['newuser'] == $_SESSION['newuser'])) {
    //on first transition to the page we want to render the form, after submission deal with validation of values and attempt to create new user
    if (!isset($_POST["token"])){

        $member_handler = icms::handler('icms_member');
        $newuser = isset($_SESSION['profile']['uid']) ? $member_handler->getUser($_SESSION['profile']['uid']) : $member_handler->createUser();

        $profile_handler = icms_getmodulehandler('profile', 'profile', 'profile');
        $profile = $profile_handler->get($newuser->getVar('uid'));


        $criteria = new icms_db_criteria_Compo();
        $criteria->setSort('step_order');

        $regstep_handler = icms_getmodulehandler('regstep', 'profile', 'profile');
        $steps = $regstep_handler->getObjects($criteria);
        if (count($steps) == 0) redirect_header(ICMS_URL.'/', 6, _MD_PROFILE_NOSTEPSAVAILABLE);

        //set some of the inputs with the info we get from google
        $newuser->setVar('login_name', $_SESSION['name']);
        $newuser->setVar('uname', $_SESSION['name']);
        $newuser->setVar('email', $_SESSION['email']);

        //set config to not use captcha but manual auth token from admin instead
        $icmsConfigUser['use_captcha'] = 0;
        $icmsConfigUser['use_token'] = 1;

        $reg_form = getRegisterForm($newuser, $profile, 0, $steps[0]);

        $reg_form->assign($icmsTpl);
    }else{
        //the condition where we know that we have submitted the form on this page and redirected
        //need to validate the token 
        //TODO fix this to be real...
        $token = '123';
        if($_POST["token"] == $token){
                $login_name = $_POST["login_name"];
                //parse the space out of the name
                $login_name = str_replace(' ', '', $login_name);
                $uname = $_POST["uname"];
                $email = $_POST["email"];
                $pass = $_POST["pass"];
                $vpass =  $_POST["vpass"];
                $timezone_offset =  $_POST["timezone_offset"];
                $member_handler = icms::handler('icms_member');
                $user_handler = icms::handler('icms_member_user');
                //perform a chek for if the password and verified one seem ok
                $stop = $user_handler->userCheck($login_name, $uname, $email, $pass, $vpass);
                if (empty($stop)) {

                    //setup password info
                    $icmspass = new icms_core_Password();
                    $salt = $icmspass->createSalt();
                    $enc_type = $icmsConfigUser['enc_type'];
                    $pass1 = $icmspass->encryptPass($pass, $salt, $enc_type);
                    
			        $newuser =& $member_handler->createUser();
                    //attempt to create the user
                    $newuser->setVar('login_name', $login_name, TRUE);
                    $newuser->setVar('uname', $uname, TRUE);
			        $newuser->setVar('email', $email, TRUE);
                    $newuser->setVar('name', $login_name, TRUE);
                    $newuser->setVar('timezone_offset', $timezone_offset, TRUE);
                    $newuser->setVar('user_avatar', 'blank.gif', TRUE);
                    $newuser->setVar( 'theme', 'impresstheme', TRUE);
                    $newuser->setVar('level', 1, TRUE);
                    $newuser->setVar('pass', $pass1, TRUE);
                    $newuser->setVar('salt', $salt, TRUE);
                    $newuser->setVar('enc_type', $enc_type, TRUE);
                    if ($member_handler->insertUser($newuser)) {
                        header("Location: ".XOOPS_URL."/?code=".$_GET['newuser']."&newcode=".$_GET['newuser']);
                        exit();
                    }
                    }
        else {
            //echo "<span class="top_testresult top_shortPass"><span>$stop</span></span>";
            //TODO not the best way currently to display messages since it will stop displaying the form to just render a span element...
			echo "<span style='color:#ff0000; font-weight:bold;'>$stop</span>";
		}
    }
    }

}else{
    redirect_header(ICMS_URL.'/', 6, "Sorry, you don't have permission to access this area.");
}

include "footer.php";
