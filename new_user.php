<?php



include "mainfile.php";
include "header.php";
include_once ICMS_ROOT_PATH .'/include/functions.php';
include_once ICMS_ROOT_PATH .'/modules/profile/include/forms.php';
include_once ICMS_ROOT_PATH . '/modules/formulize/include/functions.php';
include ICMS_ROOT_PATH .'/include/registerform.php';
include_once ICMS_ROOT_PATH . '/modules/profile/language/english/main.php';
include_once ICMS_ROOT_PATH .'/language/english/user.php';
include_once(XOOPS_ROOT_PATH.'/integration_api.php');

//protect against an attempt to directly enter the url into the browser for this page. Don't want it to be too public.
if (isset($_GET['newuser']) && ($_GET['newuser'] == $_SESSION['newuser'])) {
    //on first transition to the page we want to render the form, after submission deal with validation of values and attempt to create new user
   if (!isset($_POST["token"])){  
        renderRegForm();
    }else{
        //the condition where we know that we have submitted the form on this page and redirected
        //need to validate the token aka fetch others and see if it matches any 
        $submittedToken =$_POST["token"];
        $tokenHandler = xoops_getmodulehandler('token', 'formulize');
        $token = $tokenHandler->get($submittedToken);
        //attempt to get this token may have returned false
        if($token){
            //if we have uses left and can increment the number of uses that this token has seen
            if($tokenHandler->incrementUses($token)){
                $tokenGroupsString = $token->getVar('groups');
                $tokenGroups = explode(" ", $tokenGroupsString);
                $newFormulizeUser = new FormulizeUser(array(
                   'login_name'=>$_POST['login_name'],
                   'uname'=>$_POST['uname'],
                   'email'=>$_POST['email'],
                   'timezone_offset'=>$_POST['timezone_offset']
                ));
                if($newFormulizeUser->insertAndMapUser($tokenGroups)==false) {
                    // stop error may be set in the method and assigned to global icmsConfigUser for picking up in form
                    renderRegForm();
                }
            } else {
                //token was used too many times
                $icmsConfigUser['token_error'] = 1;
                renderRegForm();
            }
        } else {
            //token was not same in this case
            $icmsConfigUser['token_error'] = 1;
            renderRegForm();
        }
    }

}else{
    redirect_header(ICMS_URL.'/', 6, "Sorry, you don't have permission to access this area.");
}

include "footer.php";

function renderRegForm(){
        global $icmsTpl, $icmsConfigUser;
        $xoopsOption['template_main'] = 'profile_register.html';
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
        $newuser->setVar('login_name', str_replace(" ","",$_SESSION['name']));
        $newuser->setVar('uname', $_SESSION['name']);
        $newuser->setVar('email', $_SESSION['email']);

        //set config to not use captcha but manual auth token from admin instead
        $icmsConfigUser['use_captcha'] = 0;
        $icmsConfigUser['use_token'] = 1;
        $icmsConfigUser['exclude_pass'] = 1;

        $reg_form = getRegisterForm($newuser, $profile, 0, $steps[0]);

        $reg_form->assign($icmsTpl);
        $reg_form->display();
}