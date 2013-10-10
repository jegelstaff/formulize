Running the Android Prototype with Eclipse

1. Install the ADT Bundle
	Install the Android Development Tools:
	http://developer.android.com/sdk/installing/index.html
	The SDK contains an installation of Eclispe with the Android plugins installed. Make sure you have the latest Java JDK installed.

	If you already have an installation of Eclipse and don't mind spending some more time, you can install the Eclipse plugin:
	http://developer.android.com/sdk/installing/installing-adt.html

	Follow the installation instructions provided in those links.

2. Install Android SDK tools in ADT/Eclipse
	Follow the instructions here to access the Android SDK Manager:
	https://developer.android.com/tools/help/sdk-manager.html
	By default, the latest Android API should have been installed, but make sure Android 4.2.2 (API 17) is installed.

3. Import the Android Prototype Project as an existing project
	Import the AndroidProtoype folder in the Formulize repository using Eclipse with the Android SDK installed.
	The import source should be "Existing Projects into Workspace".

4. Run the Android Prototype
	If you have an Android device, you can load the application into it. 
	Follow the instructions here:
	http://developer.android.com/tools/device.html

	If you don't have an Android device you can run the emulator instead, follow the instructions here to set it up:
	http://developer.android.com/tools/devices/managing-avds.html

	With the FormulizePrototype open, run (Ctrl + F11/Cmd + Shift + F11) the project. When prompted, run the project as an Android Application, and select the Android (virtual) device you want to run the application on.

	The application should be installed into your device and can be tested! If you are using an Android Virtual Device (AVD), you will have to wait for the emulator to boot up first. Expect the performance to be sluggish at best on the emulator.

	