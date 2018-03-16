<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
$xoopsOption['template_main'] = 'profile_register.html';
include "mainfile.php";

include "header.php";
include ICMS_ROOT_PATH .'/include/registerform.php';
include_once ICMS_ROOT_PATH .'/include/functions.php';
include_once ICMS_ROOT_PATH .'/modules/profile/include/forms.php';
include_once ICMS_ROOT_PATH . '/modules/profile/language/english/main.php';


//protect against an attempt to directly enter the url into the browser for this page. Don't want it to be too public.
if (isset($_GET['newuser']) && ($_GET['newuser'] == $_SESSION['newuser'])) {
    //on first transition to the page we want to render the form, after submission deal with validation of values and attempt to create new user
    if (!isset($_SESSION["load"])){

        var_dump($_SESSION["load"]);

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

        //unset these values after we are done with them for safety reasons
        unset($_SESSION['name']);
        unset($_SESSION['email']);

        //set config to not use captcha but manual auth token from admin instead
        $icmsConfigUser['use_captcha'] = 0;
        $icmsConfigUser['use_token'] = 1;

        $reg_form = getRegisterForm($newuser, $profile, 0, $steps[0]);

        $reg_form->assign($icmsTpl);
        $_SESSION["load"] = TRUE;
    }else if(isset($_GET['fincode'])){
        header("Location: ".XOOPS_URL."/new_user.php?code=".$code);
    }else{
        //the condition where we know that we have submitted the form on this page and redirected
        var_dump($_SESSION["load"]);
        $_SESSION["load"] = NULL;

        //need to validate the token 
        $token = '123';
        if($_POST["token"] == $token){

                //attempt to create the user
                $user_data = array(
                    'uid'				=> 0,
                    'uname'				=> $_POST["uname"],
                    'login_name'		=> $_POST["login_name"],
                    'name'				=> $_POST["login_name"],
                    'pass'				=> $_POST["pass"],
                    'email'				=> $_POST["email"],
                    //@TODO fill in the timezone from the form
                    'timezone_offset'	=> $account->timezone/60/60, //formulize_convert_language
                    'language'			=> $account->language,
                    'user_avatar'		=> 'blank.gif',
                    'theme'				=> 'impresstheme',
                    'level'				=> 1
                );
                //will need to do some logic here to make sure pass and vpass are same?
                $user_data = new FormulizeUser($user_data);
                if( Formulize::createUser($user_data)){
                    print"HERE";
                    unset($_SESSION["load_sess"]);
                    header("Location: ".XOOPS_URL."/new_user.php?fincode=".$code);
                }else{
                    print"NOTHERE";
                }
        }else{
              print"NOTHERE";
        }
    }

}else{
    redirect_header(ICMS_URL.'/', 6, "Sorry, you don't have permission to access this area.");
}

include "footer.php";
