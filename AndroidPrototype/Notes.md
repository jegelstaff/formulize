## Current Tasks

### Getting user login token from a Formulize Server

In the first Prototype, this was done by using a `WebView`. However, this shouldn't be done anymore since we don't need to load an entire web browser to log in users. 

Simpler HTTP Requests should be made instead. This way it also allows us to get the session token more easily, and detect whether a login has been successful or not.

### Create input validations for adding connections, login

### Saving Application State

In Android, applications should expect that their processes can be freed and destroyed at any time. (Users might put the phone to sleep, get a phone call etc.)

Not much thought has been put in handling these cases yet, this causes a bug in the `ScreenListActivity` where the title can disappear when the activity has been freed once since it is set dynamically in the code.

## Completed Tasks
* Have a activity for setting up multiple Formulize sites:
	* URL
	* Name
	* Options
	* Usernames, Passwords

* Implement Method to retrieve connections selected from the list
* Have working login workflow
	* When there is now username specified in the login connection, ask for login
	* Otherwise login automatically
	* If there is a bad password/username, prompt for login credentials again

## Things to be done later

### Android AsyncTask and LoginTask
To handle asynchronous tasks such as network calls, Android encourages the use of `AsyncTask` [in its library](https://developer.android.com/reference/android/os/AsyncTask.html). However, the Android Application Lifecycle does not automatically preserve asynchronous tasks when an activity is destroyed (e.g. when the user changes screen orientantion) hence `AsyncTask` objects need to be attached to [Android Fragments](https://developer.android.com/guide/components/fragments.html). Android allows some Fragments to be retained so it can bypass Android's destroy-create cycle, so AsyncTasks can be preserved by being attached to them. [(Source)](http://www.androiddesignpatterns.com/2013/04/retaining-objects-across-config-changes.html)

`LoginTask` should be implemented under this fashion. Currently if you rotate the screen while the system is logging in, the application will crash!

* Async Login Woes
	* Do not access the UI toolkit outside of the UI thread!
	* How do persist an AsyncTask when there is a runtime configuration change (e.g. screen orientation changed)?
		* http://www.androiddesignpatterns.com/2013/04/retaining-objects-across-config-changes.html

### Android Contextual Action Bar
In Android versions 3.0 or later, the use of the [contextual action bar](https://developer.android.com/guide/topics/ui/menus.html#CAB) is encouraged when the users need to be able to perform actions on particular objects in the application. For our case, that would be editing or deleting connections from a list. However, I realized that Android does not support these feature in their APIs before version 3.0. Since Android 2.2, 2.3 still has ~30% of the [Android market share](https://developer.android.com/about/dashboards/index.html), we should still support these older versions. 

One way is to use [ActionBarSherlock](http://actionbarsherlock.com/). It is an external library that backports some features in newer Android versions. I don't know how much time and effort it may take to use this external library though.

### Hashing Passwords

Must be done if we are to save passwords locally.

### Dealing with Disconnects
When application detects that the session has been lost, reprompt for login.
	* There might be a way to know when a session is about to be timed out, application can request a new token when that happens!
