# How many CMSs can you fit inside a website?

Once upon a time, PHP was simply a way to extend HTML. Instead of writing static HTML like this:

Page 1:

    <h1>About Us</h1>

Page 2:

    <h1>Products</h1>

PHP let you make a single file and do this:

    echo "<h1>$pageTitle</h1>";
    
It didn't take long for people to figure out that they were using PHP in the same way over and over again on many different websites. Like all good technical ideas, PHP's complexity and usefulness grew in response to these discoveries about how it was being used. Before long, PHP started to look like this:

Logic:

    $page = $database->get($page_id);
    $template->assign($page);

Template:

    <h1>{$page->pageTitle}</h1>

At this point, our story splits into two paths. Down one path lies the land of frameworks of various shapes and sizes: Zend Framework, Symfony, CakePHP, eZ Components, CodeIgniter, to name barely a handful.

Down the other path lies the land of content management systems (CMSs), which have also come in all different shapes and sizes: PHP-Nuke, Joomla, TikiWiki, Drupal, TYPO3, WordPress, to name barely a handful again.

The paths have crossed from time to time, as some CMSs have incorporated some frameworks into their codebases. Notably, Drupal 8 is highly dependent on Symfony 2.

## One language. Multiple dialects.

As a result of all this work and evolution, we have some incredibly powerful tools for creating and managing websites. We have also ended up with a whole series of silos, with distinct ecosystems developing around different shards of the overall PHP landscape.

Code that is written with one framework in mind may or may not be easy to refactor to use a different framework. On the CMS side, the problem is particularly pronounced, since plugins written for WordPress cannot in any way be directly installed or used in Drupal or any other CMS.

We've ended up with a mountain of open source, PHP code, that is trapped inside defacto proprietary-style walled gardens! 

When the code you're concerned with is on the framework side, you always have the option of spending the time to refactor and connect different pieces of code together. But what about when you're on the CMS side?

The whole point of using a CMS is often speed and efficiency, both of initial deployment and also making changes. That includes changes to content and to configuration, which should all be point and click, not code-based. So if you want to use features from multiple CMSs, refactoring a lot of code is likely to be so time consuming that it will fail a cost/benefit analysis. CMS users also often lack ongoing access to developers who can maintain complex code customizations. How can you get the best of two CMS worlds in one website, with minimal effort? 

We're not talking about parallel systems where some website content and features are in one system, and some are in another, and they inhabit slightly different parts of the same domain. To regular users, that might look OK, but it's a cumbersome option in terms of theming (you need to manage the appearance of two systems instead of one) and in terms of interactive features (you quickly run into single-signon issues). It would be better if somehow you could just get the features you want from one CMS inside the other. But how? 

## It's just an extension of HTML

Let's step back and remember that PHP is just an enhancement to answering an http request. Drupal does not answer the http request, the web server does. The web server then looks for the file that was requested, and if the file is PHP then the instructions in that file are processed and the web server continues from there until all the included files and code have been processed. Then the web server returns a stream of HTML to the client, based on all the instructions it just read.

There's no reason in principle why you can't have as much PHP code as you want answer the page request. With very little effort, a single request can result in both CMSs starting up and generating content for the HTML stream. All CMSs have a relatively simple process that bootstraps their template system, their connection to their database, their internal API, and so on.

In WordPress, you include `wp-load.php`, as seen in the `wp-blog-header.php file`:

    if ( !isset($wp_did_header) ) {
        $wp_did_header = true;
        require_once( dirname(__FILE__) . '/wp-load.php' );
        wp();
        require_once( ABSPATH . WPINC . '/template-loader.php' );
    }
    
In Drupal, you call `bootstrap.inc`, as seen in the `index.php` file:

    define('DRUPAL_ROOT', getcwd());
    require_once DRUPAL_ROOT . '/includes/bootstrap.inc';
    drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);
    menu_execute_active_handler();
    
In Formulize, you call `mainfile.php` from any page (Formulize, like ImpressCMS and XOOPS which it derives from, uses a page controller architecture, so every page has to bootstrap the system first before starting its own logic), for example:

    require_once "../../mainfile.php";
    include XOOPS_ROOT_PATH.'/header.php';
    global $xoTheme;
    if($xoTheme) {
        $xoTheme->addStylesheet("/modules/formulize/templates/css/formulize.css");
        $xoTheme->addScript("/modules/formulize/libraries/formulize.js");
    }
    include 'initialize.php';
    include XOOPS_ROOT_PATH.'/footer.php';

## Bootstrapping two systems from one http request
    
So, the next question is, how do you get both systems to bootstrap as part of the same page request? To solve this, you have to first determine which system is answering the page request. An implicit assumption here, is that both systems are installed on the same web server. There would be ways to get this to work across servers, if you allowed remote includes and tweaked some other settings.

One system needs to be primary, and the other is secondary. The http request is asking for a file from only one of the systems. At some point during the running of that primary system, the secondary system needs to start up. 

This can be accomplished by modifying the core files of the primary CMS, for example, a one line change in Drupal's index.php file will trigger a Formulize bootstrap as part of every request:

    define('DRUPAL_ROOT', getcwd());
    require_once DRUPAL_ROOT . '/includes/bootstrap.inc'; // BOOTSTRAP THE PRIMARY SYSTEM
    require_once 'path/to/Formulize/mainfile.php'; // BOOTSTRAP THE SECONDARY SYSTEM
    drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);
    menu_execute_active_handler();

That will work, but modifying core files is rarely recommended for reasons that should be obvious! Sometimes a good hack is your best friend, but not always. This also has the drawback of invoking the secondary system on each request, when you probably only want it during certain specific pages instead.

A more robust solution is to find a way to leverage the primary CMS's extension capabilities, and extend it to bootstrap the secondary CMS only when required. For example, we have created a module for Drupal which bootstraps Formulize when it needs to display Formulize content inside Drupal.

    // LISTEN FOR MODULE INITIALIZATION BY DRUPAL
    function formulize_init() {
        ...
        // GET PATH TO FORMULIZE, WHICH IS USER-SPECIFIED IN THE ADMIN UI
        $formulize_path = variable_get('formulize_full_path', NULL);
        $formulize_path = is_dir($formulize_path) ? rtrim($formulize_path, '/\\') : dirname($formulize_path);
        $integration_api_path = $formulize_path . DIRECTORY_SEPARATOR . 'integration_api.php';
        ...
        include_once($integration_api_path);
        ...
    }
    
    // INSIDE INTEGRATION_API.PHP:
    class Formulize {
        ...
        static function init() { // INIT IS CALLED INTERNALLY BY ALL API METHODS
            ...
            include_once('mainfile.php'); // BOOTSTRAP THE SECONDARY SYSTEM
            ...
        }
        ...
    }
    
The last issue to consider at this point, is whether all the objects and variables you will rely on, are actually available. Most CMSs assume that their key objects and variables are in the global space, and so sprinkled throughout the rest of their code, you see things like this:

    global $user;
    
The code can then interact with the global `$user` object. The `$user` object is most likely created during the bootstrap process, which is usually assumed to have taken place in the global namespace. But, if you end up bootstrapping the CMS from inside a function, then `global $user` won't work anymore in all your dependent code. If that is the case, then you need to add the `global` keyword before the declaration of all relevant variables. That will ensure the variables are in the global space, even if they are created inside the scope of a function.

This is a bit of a pain, but really amounts to no more than a quick audit of the bootstrap process to identify where each of the important variables and objects are instantiated and ensuring they are assigned to the global namespace explicitly. In more modern architectures, PHP's namespace feature may be in use and that could simplify this issue a lot.

## Loose ends

So, now you have both systems bootstrapped and fully available as part of each page request in Drupal.  At this point, you probably have some other low level challenges to overcome.

All CMS's have some kind of security layer, that validates certain things about the request and tries to prevent things like CSRF attacks and other stuff that you don't want happening. In the Formulize codebase, this includes some older code that still checks the referrer information from the browser, to verify that the request came from a URL that is part of the same website. While this is not the most useful part of the security mechanism, it can't hurt (except when security software on a PC blocks referrer information!). 

The problem is, the referrer check fails when Formulize bootstraps during a request initiated from a Drupal page, because it's a different "website". This is easily addressed by neutering the referrer check. Since it is such a low value part of the security process, it's not a significant risk to turn it off:

    public function checkReferer($docheck = 1) {
        return true;

Any two systems that you try to tie together this way, will lead to some behind-the-scenes issues like this. If they are serious, you will need to spend a bit of time tracking them down.

Another low level issue you need to consider is the way each system relates to the database(s). It is often possible to have two systems share a single database, if one system can use a common prefix on the names of its tables (to avoid naming collisions). However, it is also possible for each system to connect to its own database on the same database server, or even to connect to its own database on a distinct database server. It all depends how that part of your CMS installation has been configured.

In the case of the Formulize codebase, the legacy `mysql_connect` function can be used to establish the database connection, as well as the newer PDO library. Which gets used depends on the settings chosen at the time of installation. If the `mysql_connect` function is used, and the Formulize database is on the same server as the primary system's database, it is possible that the primary system's connection to its database will be replaced by the connection to the Formulize database. Fortunately, this is rare, due to the prevalence of PDO now, and it has a simple solution in any case:

    if (XOOPS_DB_PCONNECT == 1) {
        // FINAL 'TRUE' FORCES A NEW CONNECTION INSTEAD OF REUSING EXISTING
        $this->conn = @ mysql_pconnect(XOOPS_DB_HOST, XOOPS_DB_USER, XOOPS_DB_PASS, true); 
    } else {
        // FINAL 'TRUE' FORCES A NEW CONNECTION INSTEAD OF REUSING EXISTING
        $this->conn = @ mysql_connect(XOOPS_DB_HOST, XOOPS_DB_USER, XOOPS_DB_PASS, true);
    }

## Session integration

The most major low level issue is session integration, or single-signon. In order to provide your users with any significantly useful behaviour in your co-joined set of CMSs, the secondary system needs to be aware of which user is logged in, in the primary system.

Most (all?) CMSs have a table of users in their database. Each user has a primary key in that table, a user ID, which is referenced elsewhere in other tables. A very simple approach to handling single-signon, is to establish a pattern/rule in the real world, manually, that when new users are created in the primary system, a corresponding user is created in the secondary system. If you manage to do this in the same sequence in both systems, the user IDs will be equivalent. This is not a viable solution for all cases, but we can start with it, if only to illustrate the concept: if the IDs of the two user records are identical, then single-signon can be achieved relatively easily. 

For example, at some point in the bootstrapping process for all CMSs, the active user is determined and their session information is loaded from the database or elsewhere. If the primary system bootstraps first, then it will have determined which user is active, and it will have created some global object or other representation of the active user. The secondary system can simply refer to this information to determine the active user, and if the user IDs between the two systems are in synch, the final step would be to force the secondary system to "log in" the appropriate user.

At that point, all you have to do during session initialization in the secondary system, is add something like this:

    // ADDITIONAL CODE TO IDENTIFY THE ACTIVE USER
    global $user;
    if($user) {
        $_SESSION['activeUser'] = $user->uid;
    }
    
    // CONTINUE WITH NORMAL SESSION INITIALIZATION
    if($_SESSION['activeUser']) {

A more robust solution is to create a translation table that records which user ID in the secondary system corresponds to which user ID in the primary system. This is relatively simple in any CMS that has any kind of "event" system that triggers events when users are created, updated or deleted.

The formulize module in Drupal maintains this translation table by responding to the Drupal "hook" system (which is an API convention for responding to various standard events in Drupal).

In Drupal when a new user is created:

    function formulize_user_insert($edit, $account, $category) {
        if (!_formulize_integration_init())
            return;
    
        $user_data = array(
            'uid' => $account->uid,
            'uname' => $account->name,
            'login_name' => $account->name,
            'name' => $account->name,
            'pass' => $account->pass,
            'email' => $account->mail,
            'timezone_offset' => $account->timezone/60/60,
            'language' => _formulize_convert_language($account->language),
            'user_avatar' => 'blank.gif',
            'theme' => 'impresstheme',
            'level' => 1
        );
    
        $user = new FormulizeUser($user_data);
        Formulize::createUser($user);
    
        // Add user to groups
        foreach ($account->roles as $roleid => $rolename) {
            Formulize::addUserToGroup($account->uid, $roleid);
        }
    }

In the Formulize integration API:

    static function createUser($user_data) {
        self::init();
        if($user_data->get('uid') == -1)
            throw new Exception('Formulize::createUser() - The supplied user doesn\'t have an ID.');
    
        //Create a user from the provided data
        $member_handler = xoops_gethandler('member');
        $newUser = $member_handler->createUser();
        $newUser->setVar('uname', $user_data->get('uname'));
        $newUser->setVar('login_name', $user_data->get('login_name'));
        $newUser->setVar('email', $user_data->get('email'));
        $newUser->setVar('timezone_offset', $user_data->get('timezone_offset'));
        $newUser->setVar('notify_method', $user_data->get('notify_method')); //email
        $newUser->setVar('level', $user_data->get('level')); //active, can login
    
        if ($member_handler->insertUser($newUser, true)) {
            //Map the created user to the external ID provided
            $user_id = $newUser->getVar('uid');
            $return = self::createResourceMapping(self::USER_RESOURCE, $user_data->get('uid'), $user_id);
            if ($return > 0) {
                $member_handler->addUserToGroup(2 /* 2 is registered users group */, $user_id);
            }
            return $return;
        } 
    }

Once that is done, you can refer to the translation table to identify which "secondary" user corresponds to which "primary" user. In Formulize, there is a method in the API called `getXoopsResourceID` which looks up the "external" ID and returns the ID of the corresponding resource in the local system.

During the module initialization in Drupal:

    global $user, $formulizeHostSystemUserId;
    $formulizeHostSystemUserId = $user->uid;

During the session initialization in Formulize:

    if (isset($GLOBALS['formulizeHostSystemUserId'])) {
    
        if ($GLOBALS['formulizeHostSystemUserId']) {
            $externalUid = $GLOBALS['formulizeHostSystemUserId'];
        } else {
            $externalUid = 0;
            $cookie_time = time() - 10000;
            $instance->update_cookie(session_id(), $cookie_time);
            $instance->destroy(session_id());
            unset($_SESSION['xoopsUserId']);
        }
        
    }
    
    if ($externalUid) {
    
        $xoops_userid = Formulize::getXoopsResourceID(Formulize::USER_RESOURCE, $externalUid);
        $icms_user = icms::handler('icms_member')->getUser($xoops_userid);
    
        if (is_object($icms_user)) {
    
            // set a few things in $_SESSION, similar to what include/checklogin.php does, and make a cookie and a database entry
            $_SESSION['xoopsUserId'] = $icms_user->getVar('uid');
            $_SESSION['xoopsUserGroups'] = $icms_user->getGroups();
            $_SESSION['xoopsUserLastLogin'] = $icms_user->getVar('last_login');
            $_SESSION['xoopsUserLanguage'] = $icms_user->language();
            $_SESSION['icms_fprint'] = $instance->createFingerprint();
            $xoops_user_theme = $icms_user->getVar('theme');
            if (in_array($xoops_user_theme, $icmsConfig['theme_set_allowed'])) {
                $_SESSION['xoopsUserTheme'] = $xoops_user_theme;
            }
    
            $instance->write(session_id(), session_encode());
            $icms_session_expiry = ini_get("session.gc_maxlifetime") / 60; // need to use the current maxlifetime setting, which will be coming from Drupal, so the timing of the sessions is synched.
            $cookie_time = time() + (60 * $icms_session_expiry);
            $instance->update_cookie(session_id(), $cookie_time);
    
        }
        
    }

## It's the content, stupid

With all this "administrative" work out of the way, you now have two fully integrated CMSs, with single-signon, capable of contributing together to an http request! Just one question: how do you actually integrate the content?

We need a straight forward way of merging content from the secondary system, with the page that is being generated in the primary system.

The first thing to keep in mind, is the concept that one system is primary and the other is secondary. All CMSs have some kind of templating system, some way that the various parts of the page that have been generated during the request, get merged into a single stream of HTML that gets sent to the client. The question for us to consider at this point, is which part of the page is the part where we want our secondary system's content to appear?

Typically, this would be the body section of the page. Most CMSs give you some way to invoke PHP commands in the body section of the page. Or you can use the extension capabilities of the CMS to create some new kind of body content.
For example, with the right settings turned on in Drupal, you can type PHP commands directly into the body section of a page:

![PHP code going into a Drupal page](figure1.png)

This is a very useful trick, as long as your secondary CMS has a convenient API for generating/getting the content of its various pages and components. You can simply use PHP commands to essentially embed the content from the secondary CMS inside the primary CMS. In the case of Formulize, each screen that a user can interact with, can be easily invoked by a few lines of PHP:

![A Drupal page with PHP code for invoking Formulize content](figure2.png)

Those lines generate the entire body section of the page:

![The rendered page in Drupal (content from Formulize highlighted in red)](figure3.png)

This same behaviour is usually easy to achieve using the extension capabilities of your CMS. For example in Drupal, the same module we use to bootstrap Formulize and synchronize the users, can also generate the screen contents and make it available to the Drupal templating system:

    function formulize_view($node, $view_mode) {
        if (_formulize_integration_init()) {
            drupal_add_css(drupal_get_path('module', 'formulize') . '/formulize.css');
            Formulize::init(); 
            drupal_add_js(XOOPS_URL . '/modules/formulize/libraries/formulize.js');
            ob_start(); // start capturing output from Formulize screens
            Formulize::renderScreen($node->screen_id);
            $output = ob_get_clean();
            $node->content['formulize_screen'] = array(
                '#markup' => $output,
                '#weight' => 1
            );
        } 
        return $node;
    }

By integrating the systems through the module, it means webmasters can use the admin UI to add content from one system to the other, instead of having to type in PHP code.

## Links of doom

There is one big challenge that arises at this point: the generation of links inside the content from the secondary CMS.

CMSs are generally architected based on the assumption that they are the single thing responsible for answering the http request. They often setup some constants or variables during the bootstrap process to represent the canonical root path to the installed CMS software on the server, and also the canonical URL at which users can reach the site.

Those constants are reused over and over again when the CMS generates the page contents. This is similar to how many frameworks operate as well.

A typical link in a CMS would be generated by code like this:

    $link = $base_url . "/profile/?user=".intval($user_id);

The problem comes when such a link from the secondary CMS, is embedded inside the primary CMS's page. When the user clicks on the link, and they are then taken to a page that is governed by the secondary CMS. At that point, all your careful work integrating the two systems is broken, since the new page request is not being answered by the primary system.

This problem does not arise for images and Javascript files and other dependent resources. They can be loaded without issue from their native locations inside the file structure of the secondary CMS. It only becomes an issue when the user activates a link that is based on the base URL of the secondary CMS, and essentially "breaks out" of the primary CMS at that moment.

There are three possible solutions to this problem.

First, this issue highlights a conceptual shortcoming of the standard model of setting up a constant for the root path to the files, and a constant for the base URL. Essentially, a third constant may help in some cases, something you might call the "deployment URL" to represent the address on the web where the secondary system's content will be deployed, distinct from the address where the secondary system is installed.

The secondary system's code could then be modified in certain places to generate links based on the "deployment URL" instead of its own base URL. In addition, you would have to take into account the addressing and aliasing scheme in the primary CMS. After all, in the example above, `/profile/?user=27` will not lead to a valid page in the primary CMS. Therefore, the secondary CMS would have to be altered in a way that caused it to generate URLs that pointed to valid pages inside the primary CMS.

Second, you could alter the secondary CMS so that it used the templating system from the primary CMS. That way, when people landed on the `/profile/?user=27` page, the secondary CMS would answer the request, but the contents would be presented inside the standard page template that the primary CMS is using. This is the most foolproof approach, since it would theoretically work for any address inside the secondary CMS.

Some combination of the first and second approaches could probably address most situations. However, there is a third option. This is what we have used when integrating Formulize with other systems, and it allows Formulize to work no matter what other system it is installed with.

## Reuse the current URL

Formulize doesn't use a base URL concept at all. Instead, it detects the current URL being used for the active http request. It then uses that URL as the destination of all links and the action URL for all forms, etc, regardless of whether that URL is part of the primary or secondary system.

Formulize therefore keeps requesting the same page from the server over and over again. All "state" information about what a user has clicked on or what settings they have altered, is posted with the request, so we can then see in $_POST exactly what the user has done and respond accordingly. For example, this is the URL and page after an initial page load:

![The initial page after the screen has loaded](figure4.png)

This is the page after a reload, after a search term has been submitted:

![The page after a search term has been submitted, note the URL](figure5.png)

Formulize was originally designed this way, so that Formulize content could be rendered equally well in the body section of the page, or inside a block on another page at a different location in the same CMS. This architectural approach turned out to be perfect for deploying Formulize content inside any other CMS as well.

## So what?

With a few relatively simple steps, it is possible to integrate two (or more) CMSs so they can collaborate in delivering content for a website. The big challenge is not in tying the two together, but when deploying content from one to the other. Architectural assumptions underlying how the systems are built can make this easy or hard. If you can overcome that barrier for your given use case, then you can break out of a CMSs walled garden and reap the benefits of a module or feature from a secondary CMS that is not available in your primary CMS.

This can give your employer/client much greater flexibility in terms of what systems they use and how. It can also avoid the need to migrate 100% of content and features from one system to another when a decision has been made to move from an old CMS to a new one.


