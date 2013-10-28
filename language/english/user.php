<?php
// $Id: user.php 21918 2011-06-30 08:08:52Z blauer-fisch $
//%%%%%%		File Name user.php 		%%%%%
define('_US_NOTREGISTERED','Not registered?  Click <a href="register.php">here</a>.');
define('_US_LOSTPASSWORD','Lost your password?');
define('_US_NOPROBLEM','No problem. Simply enter the e-mail address we have on file for your account.');
define('_US_YOUREMAIL','Your Email: ');
define('_US_SENDPASSWORD','Send Password');
define('_US_LOGGEDOUT','You are now logged out');
define('_US_THANKYOUFORVISIT','Thank you for your visit to our site!');
define('_US_INCORRECTLOGIN','Incorrect Login!');
define('_US_LOGGINGU','Thank you for logging in, %s.');
define('_US_RESETPASSWORD','Reset your password');
define('_US_SUBRESETPASSWORD','Reset Password');
define('_US_RESETPASSTITLE','Your password has expired!');
define('_US_RESETPASSINFO','Please complete the following form in order to reset your password. If your email, username and current password all match our record, your password will be changed instantly and you will be able to log back in!');
define('_US_PASSEXPIRED','Your password has expired.<br />You will now be redirected to a form where you will be able to reset your password.');
define('_US_SORRYUNAMENOTMATCHEMAIL','The username entered is not associated with the given Email address!');
define('_US_PWDRESET','Your password has been reset successfully!');
define('_US_SORRYINCORRECTPASS','You have entered your current password incorrectly!');

// 2001-11-17 ADD
define('_US_NOACTTPADM','The selected user has been deactivated or has not been activated yet.<br />Please contact the administrator for details.');
define('_US_ACTKEYNOT','Activation key not correct!');
define('_US_ACONTACT','Selected account is already activated!');
define('_US_ACTLOGIN','Your account has been activated. Please login with the registered password.');
define('_US_NOPERMISS','Sorry, you dont have the permission to perform this action!');
define('_US_SURETODEL','Are you sure you want to delete your account?');
define('_US_REMOVEINFO','This will remove all your info from our database.');
define('_US_BEENDELED','Your account has been deleted.');
define('_US_REMEMBERME', 'Remember me');

//%%%%%%		File Name register.php 		%%%%%
define('_US_USERREG','User Registration');
define('_US_EMAIL','Email');
define('_US_ALLOWVIEWEMAIL','Allow other users to view my email address');
define('_US_WEBSITE','Website');
define('_US_TIMEZONE','Time Zone');
define('_US_AVATAR','Avatar');
define('_US_VERIFYPASS','Verify Password');
define('_US_SUBMIT','Submit');
define('_US_LOGINNAME','Username');
define('_US_FINISH','Finish');
define('_US_REGISTERNG','Could not register new user.');
define('_US_MAILOK','Receive occasional email notices from administrators and moderators?');
define('_US_DISCLAIMER','Disclaimer');
define('_US_IAGREE','I agree to the above');
define('_US_UNEEDAGREE', 'Sorry, you have to agree to our disclaimer to get registered.');
define('_US_NOREGISTER','Sorry, we are currently closed for new user registrations');

// %s is username. This is a subject for email
define('_US_USERKEYFOR','User activation key for %s');

define('_US_YOURREGISTERED','You are now registered. An email containing a user activation key has been sent to the email account you provided. Please follow the instructions in the mail to activate your account. ');
define('_US_YOURREGMAILNG','You are now registered. However, we were unable to send the activation mail to your email account due to an internal error that had occurred on our server. We are sorry for the inconvenience, please send the webmaster an email notifying him/her of the situation.');
define('_US_YOURREGISTERED2','You are now registered.  Please wait for your account to be activated by the adminstrators.  You will receive an email once you are activated.  This could take a while so please be patient.');

// %s is your site name
define('_US_NEWUSERREGAT','New user registration at %s');
// %s is a username
define('_US_HASJUSTREG','%s has just registered!');

define('_US_INVALIDMAIL','ERROR: Invalid email');
define('_US_INVALIDNICKNAME','ERROR: Invalid Loginname, please try an other Loginname.');
define('_US_NICKNAMETOOLONG','Username is too long. It must be less than %s characters.');
define('_US_NICKNAMETOOSHORT','Username is too short. It must be more than %s characters.');
define('_US_NAMERESERVED','ERROR: Name is reserved.');
define('_US_NICKNAMENOSPACES','There cannot be any spaces in the Username.');
define('_US_LOGINNAMETAKEN','ERROR: Username taken.');
define('_US_NICKNAMETAKEN','ERROR: Display Name taken.');
define('_US_EMAILTAKEN','ERROR: Email address already registered.');
define('_US_ENTERPWD','ERROR: You must provide a password.');
define('_US_SORRYNOTFOUND','Sorry, no corresponding user info was found.');

define('_US_USERINVITE', 'Membership invitation');
define('_US_INVITENONE','ERROR: Registration is by invitation only.');
define('_US_INVITEINVALID','ERROR: Incorrect invitation code.');
define('_US_INVITEEXPIRED','ERROR: Invitation code is already used or expired.');

define('_US_INVITEBYMEMBER','Only an existing member can invite new members; please request an invitation email from some registered member.');
define('_US_INVITEMAILERR','We were unable to send the mail with registration link to your email account due to an internal error that had occurred on our server. We are sorry for the inconvenience, please try again and if problem persists, do send the webmaster an email notifying him/her of the situation. <br />');
define('_US_INVITEDBERR','We were unable to process your registration request due to an internal error. We are sorry for the inconvenience, please try again and if problem persists, do send the webmaster an email notifying him/her of the situation. <br />');
define('_US_INVITESENT','An email containing registration link has been sent to the email account you provided. Please follow the instructions in the mail to register your account. This could take few minutes so please be patient.');
// %s is your site name
define('_US_INVITEREGLINK','Registration invitation from %s');

// %s is your site name
define('_US_NEWPWDREQ','New Password Request at %s');
define('_US_YOURACCOUNT', 'Your account at %s');

define('_US_MAILPWDNG','mail_password: could not update user entry. Contact the Administrator');
define('_US_RESETPWDNG','reset_password: could not update user entry. Contact the Administrator');

define('_US_RESETPWDREQ','Reset Password Request at %s');
define('_US_MAILRESETPWDNG','reset_password: could not update user entry. Contact the Administrator');
define('_US_NEWPASSWORD','New Password');
define('_US_YOURUSERNAME','Your Username');
define('_US_CURRENTPASS','Your Current Password');
define('_US_BADPWD','Bad Password, Password can not contain username.');

// %s is a username
define('_US_PWDMAILED','Password for %s mailed.');
define('_US_CONFMAIL','Confirmation Mail for %s mailed.');
define('_US_ACTVMAILNG', 'Failed sending notification mail to %s');
define('_US_ACTVMAILOK', 'Notification mail to %s sent.');

//%%%%%%		File Name userinfo.php 		%%%%%
define('_US_SELECTNG','No User Selected! Please go back and try again.');
define('_US_PM','PM');
define('_US_ICQ','ICQ');
define('_US_AIM','AIM');
define('_US_YIM','YIM');
define('_US_MSNM','MSNM');
define('_US_LOCATION','Location');
define('_US_OCCUPATION','Occupation');
define('_US_INTEREST','Interest');
define('_US_SIGNATURE','Signature');
define('_US_EXTRAINFO','Extra Info');
define('_US_EDITPROFILE','Edit Profile');
define('_US_LOGOUT','Logout');
define('_US_INBOX','Inbox');
define('_US_MEMBERSINCE','Member Since');
define('_US_RANK','Rank');
define('_US_POSTS','Comments/Posts');
define('_US_LASTLOGIN','Last Login');
define('_US_ALLABOUT','All about %s');
define('_US_STATISTICS','Statistics');
define('_US_MYINFO','My Info');
define('_US_BASICINFO','Basic information');
define('_US_MOREABOUT','More About Me');
define('_US_SHOWALL','Show All');

//%%%%%%		File Name edituser.php 		%%%%%
define('_US_PROFILE','Profile');
define('_US_REALNAME','Real Name');
define('_US_SHOWSIG','Always attach my signature');
define('_US_CDISPLAYMODE','Comments Display Mode');
define('_US_CSORTORDER','Comments Sort Order');
define('_US_PASSWORD','Password');
define('_US_TYPEPASSTWICE','(type a new password twice to change it)');
define('_US_SAVECHANGES','Save Changes');
define('_US_NOEDITRIGHT',"Sorry, you don't have the right to edit this user's info.");
define('_US_PASSNOTSAME','Both passwords are different. They must be identical.');
define('_US_PWDTOOSHORT','Sorry, your password must be at least <b>%s</b> characters long.');
define('_US_PROFUPDATED','Your Profile Updated!');
define('_US_USECOOKIE','Store my user name in a cookie for 1 year');
define('_US_NO','No');
define('_US_DELACCOUNT','Delete Account');
define('_US_MYAVATAR', 'My Avatar');
define('_US_UPLOADMYAVATAR', 'Upload Avatar');
define('_US_MAXPIXEL','Max Pixels');
define('_US_MAXIMGSZ','Max Image Size (Bytes)');
define('_US_SELFILE','Select file');
define('_US_OLDDELETED','Your old avatar will be deleted!');
define('_US_CHOOSEAVT', 'Choose avatar from the available list');
define('_US_SELECT_THEME', 'Default Theme');
define('_US_SELECT_LANG', 'Default Language');

define('_US_PRESSLOGIN', 'Press the button below to login');

define('_US_ADMINNO', 'User in the webmasters group cannot be removed');
define('_US_GROUPS', 'User\'s Groups');

define('_US_YOURREGISTRATION', 'Your registration at %s');
define('_US_WELCOMEMSGFAILED', 'Error while sending the welcome email.');
define('_US_NEWUSERNOTIFYADMINFAIL', 'Notification to admin about new user registration failed.');
define('_US_REGFORM_NOJAVASCRIPT', 'To log in at the site it\'s necessary that your browser has javascript enabled.');
define('_US_REGFORM_WARNING', 'To register at the site you need to use a secure password. Try to create your password by using a mixture of letters (upper and lowercase), numbers and symbols. Try to create a password the more complex as possible although you can remember it.');
define('_US_CHANGE_PASSWORD', 'Change Password?');
define('_US_POSTSNOTENOUGH','Sorry, at least you need to have <b>%s</b> posts, to be able to upload your avatar.');
define('_US_UNCHOOSEAVT', 'Until you reach this amount you can choose avatar from the list below.');

// openid
define('_US_OPENID_NOPERM', 'No permission.');
define('_US_OPENID_FORM_CAPTION', 'OpenID');
define('_US_OPENID_FORM_DSC', '');
define('_US_OPENID_EXISTING_USER', 'Existing user');
define('_US_OPENID_EXISTING_USER_LOGIN_BELOW', 'If you are an existing user, login below with your username and password in order to associate your user account with this OpenID.');
define('_US_OPENID_NOM_MEMBER', 'No account yet ?');
define('_US_OPENID_NON_MEMBER_DSC', 'If you do not yet have an account on this site, please enter the username you would like to use and we will create an account for you, associated with this OpenID.');
define('_US_OPENID_YOUR', 'Your OpenID');
define('_US_OPENID_LINKED_AUTH_FAILED', 'The username and password you entered did not match a valid user. Please try again.');
define('_US_OPENID_LINKED_AUTH_NOT_ACTIVATED', 'The user account which your are loging in with has not been activated. Please activate your account and then try again.');
define('_US_OPENID_LINKED_AUTH_CANNOT_SAVE', 'Sorry, an error occured. It was not possible to update this user account with the authenticated OpenID.');
define('_US_OPENID_NEW_USER_UNAME_TOO_SHORT', 'The username you have inputed is too short. Please try again.');
define('_US_OPENID_NEW_USER_UNAME_EXISTS', 'The username your have inputed is already used. Please try again.');
define('_US_OPENID_NEW_USER_CANNOT_INSERT', 'Sorry, an error occured. It was not possible to create the new user. Please try again.');
define('_US_OPENID_NEW_USER_CANNOT_INSERT_INGROUP', 'Sorry, an error occured. It was not possible to add the new user in the proper groups. Please contact the administrator of the site.');
define('_US_OPENID_NEW_USER_AUTH_NOT_ACTIVATED', 'The newly created user has not been activated.');
define('_US_OPENID_NEW_USER_CREATED', 'A new user was created with the username %s. Automatically logging you in...');
define('_US_OPENID_LINKED_DONE', 'Your OpenID has been linked with the user %S. Logging you in...');
define('_US_ALREADY_LOGED_IN', 'You already are logged in, we are sorry but, you can\'t register while you\'re logged in the site');
define('_US_ALLOWVIEWEMAILOPENID','Allow other users to view my OpenID');
define('_US_SERVER_PROBLEM_OCCURRED','There was an issue while checking for spammers list!');
define('_US_INVALIDIP','ERROR: This IP adress is not allowed to register');

######################## Added in 1.2 ###################################
define('_US_LOGIN_NAME', "Loginname");
define('_US_OLD_PASSWORD', "Old Password");
define('_US_NICKNAME','Display Name');
define('_US_MULTLOGIN', 'It was not possible to login on the site!! <br />
        <p align="left" style="color:red;">
        Possible causes:<br />
         - You are already logged in on the site.<br />
         - Someone else logged in on the site using your username and password.<br />
         - You left the site or close the browser window without clicking the logout button.<br />
        </p>
        Wait a few minutes and try again later. If the problems still persists contact the site administrator.');
define("_US_OPENID_LOGIN", "Login with your OpenID");
define("_US_OPENID_URL", "Your OpenID URL:");
define("_US_OPENID_NORMAL_LOGIN", "Go back to normal login");
?>