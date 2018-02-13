---
layout: default

---

# Enabling Google Login
<br>
In order to take advantage of the Login with Google feature in Formulize first it is necessary to enable the feature.

Also make sure that the email account you have supplied in your Formulize user account is the one that you will be using to login with Google.

# Obtain Credentials from Google
<br>
First you will need to obtain authorization credentials that identify Formulize to Google's OAuth 2.0 server so that Formulize can log you in based on your Gmail address. The following steps explain how to create credentials. 

Visit this link to get started on the process: [Google setup](https://console.developers.google.com/apis/credentials)

Follow along with the steps below after visiting the link.

### First create a project:
![image](../images/createproj.png)

![image](../images/addnewproj.png)

Name the project Formulize for convenience sake.

### Set a product name on the OAuth consent screen:

![image](../images/productname.png)

Also set the product name here to Formulize. This name is displayed to users who click on the "Login with Google" url on the Formulize homepage.

### Next select OAuth client ID from the "Create Credentials" dropdown:

![image](../images/createclientID.png)

Select Web application. It does not matter what the application name is here. You **must** provide an authorized redirect URI. 
This will dependent on your exact setup of Formulize. For example if I am hosting Formulize on a webserver on my own machine I would enter: http://localhost/formulize.  

### Lastly download the credentials:

![image](../images/downloadcreds.png)

Place the file in your Formulize Trust Path and **rename** it to client_secrets.json.

### Thats it! Login with Google should now be ready to go!