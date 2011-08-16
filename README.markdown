#  Question 2 Answer Proxy SSO Plugin

This project is the **Proxy SSO Plugin** for the [Question 2 Answer](http://www.question2answer.org) project. Question 2 Answer is a StackOverflow clone.

**Plugin ver 1.4 tested with Q2A ver. 1.4**

Use the Switch Tags link (above) to choose a version.

**[Screencast demonstration of Proxy SSO Plugin User Interaction](http://marketing.masteragenda.com/screencasts/q2a_proxy_sso_demo/index.html)** 

Note that the screencast is large, and may take several minutes to load. Thank you.

## Multiple Authentication Methods for Question 2 Answer
Question 2 Answer (Q2A) includes multiple user authentication techniques. Some can be used together:

* Built-in user authentication support: registration, signin, signout.
* Built-in *External Users SSO.* Q2A can be configured to use an externally supplied library of authentication functions written in PHP. Since you are writing the procedures, they offer great flexibility in connecting Q2A to different authentication systems. If External Users SSO is used, it replaces the built-in authentication system.
* *Facebook Connect Plugin.* Q2A includes a plugin that will authenticate the user with Facebook using the Facebook connect technology. Facebook Connect can be used with the built-in authentication support.
* The *Proxy SSO Plugin* (this project) enables an external *authentication website* to manage the user registration and signin functions for Q2A.

**[Screencast demonstration of Proxy SSO Plugin User Interaction](http://marketing.masteragenda.com/screencasts/q2a_proxy_sso_demo/index.html)** 

Note that the screencast is large, and may take several minutes to load. Thank you.

## Benefits

* Flexibility: The external *authentication website* can be located on the same machine as the Q2A installation or a different machine. 
* Flexibility: The *authentication website* can be written in a different language and use a different database system than the Q2A site.
* Installation speed: Only two changes are needed on the *authentication website:* A  new web method and updating your service's logout to destroy Question2Answer cookies. 

## Limitations 
The Q2A and authentication websites must have the same core domain name. Eg Q2A.example.com and app.example.com. Or Q2A.example.com and example.com. Or example.com/Q2A and app.example.com.

Why: the Proxy SSO works by having the Q2A website *proxy* the session cookies from the browser through the Q2A website to the authentication website. Both the Q2A and authentication website need to set their cookies to a common domain that both share. See the installation section for more information.

The authentication website must use session cookies for determining who is the currently logged on user. Cookies sent as get parameters won't work.

## Installation

You will install the software on your Q2A system, modify your authentication system, and then configure and test.

Watch the [**Installation Screencast**](http://marketing.masteragenda.com/screencasts/q2a_proxy_sso_install/index.html) Note that this is a **different screencast** than the one listed above. The screencast is large and may take several minutes to download and start. The screencast is for the first version of the plugin. Now the installation is even faster.

### Question 2 Answer Installation

1. Download the project from github by using the "Downloads" button. Copy the qa-plugin/proxy-sso-login to your installation's qa-plugin directory.

2. qa-include patches: One core Q2A file needs to be patched to enable the plugin. Copy/over-write the file in the plugin's qa-include directory to your Q2A installation. 

3. css new rules: add the following to your question2answer css file.
Your default css file is in qa-theme/Default/qa-styles.css

          .qa-flash {background:#efe; border:1px solid #090; color:#090; font-size:16px;
                     padding:6px; text-align:center; margin-bottom: 10px; width: 80%; font-weight: bold;}
          .qa-flash p {color:#090; font-size:12px; font-weight: normal; text-align:center; margin-top: 6px;}
          

4. qa-config changes. Set QA_COOKIE_DOMAIN as described below.
QA_EXTERNAL_USERS in your config file must be set to false.

5. Add the widget.
    * Login to question2answer as a Super Administrator. Goto the **Admin** page.
    * Choose the **Layout** tab. Near the bottom of the page, find the **Proxy SSO Greeting** widget. Click "add widget."
    * Choose Position: Main area - Top.
    * Click "Show widget in this position on all available pages"
    * Click "Add Widget"
    * You are now back on the **Layout** tab's screen
    * Click "Save options"

### Add an SSO url to your authentication website

You will modify your authentication website to respond to a GET request from Q2A asking if a user is currently logged in or not. Any url can be used. Eg app.domain.com/Q2A_sso

The "sso url" will not be visible to end users. It responds with a JSON structure, not with an HTML page.

The sso url will be called frequently and should respond quickly. It will be called with any cookies that share a common cookie domain with the Q2A installation.

**If no one is logged in via the supplied cookies:** the sso url should return an empty HTTP body with 200 status.

**If someone is logged in via the supplied cookies:** the sso url should return a JSON structure with the following keys:

* id *Required.* An id for the user that is unique in the sister application. Can be a number or a string. 
* fname *Optional.* First name. Used for "Welcome back Larry!" message

Note The following are only used the first time a given user logs into Q2A via the authentication website. They are used to create the new user. Therefore, if they are changed for a given user later on, Q2A will not pay attention to the change.

* email      *Required.* User's email
* handle     *Required.* A proposed handle for the user. If it is already taken by someone else in the Q2A system, then it will be modified to be unique. The user can then further change it as desired in the account profile page. If your system does not use handles, then you must create one for the user. Eg Initials; First name and initial from last name.
* confirmed  *Required.* Boolean. Has the email been verified to belong to the user?
* name       *Required.* Full name of the user. Not publicly shown.
* location   *Optional.*
* website    *Optional.*
* about      *Optional.* A description.
* avatar     *Optional.* Complete url of a photo or avatar for the user.

Test your sso url that it works correctly. You can test it from a browser and view the output. To test that the sso url is correctly returning no data when no one is logged in, you may need to use Fiddler or a similar HTTP debug tool.

### Signin and Signout urls on the authentication website

Users pressing the "Login" link on Q2A will be directed to your authentication website's signin page. It can be your usual signin page or a special landing page you create for the Q2A users. As you saw in the Installation screencast, you can add query variables to your usual signin url when the user has arrived via Q2A.

As shown in the screencast, this enables your signin page to specifically welcome your Q2A user.

The signin link will also have a query parameter **redirect**. Use the parameter to redirect the user after a successful login.

Signout is handled in the same way. While the redirect query parameter will also be supplied on signout requests, it is not as essential as for signin.

**Signout cookie destruction.** Your authentication site's signout method must also be modified to delete or clear the Q2A cookies named **PHPSESSID**, **qa_session** and **qa_php_session** in the common cookie domain.

### Common cookie domain: Q2A and your authentication application

For the Proxy SSO plugin to work, both your Q2A and application domain have to use the exact same cookie domain. 

By default, applications' cookie domains are usually the same as their domain name. So an application at www.foo.com will usually use cookie domain of www.foo.com. Note that cookie domains usually start with a period, so the actual cookie domain would be .www.foo.com

**Determine your common cookie domain** It should be your domain name without any subdomains. Eg foo.com, foo.org.il, foo.info, etc. 

**Modify your authentication website to use the common cookie domain** The process will depend on your application and its web framework. In particular, check that the common cookie domain is used both when your application is accessed via www.foo.com and via foo.com

**Modify Q2A to use the common cookie domain** Change your setting for QA_COOKIE_DOMAIN in your qa-config.php file. Include the leading period. Eg

          define('QA_COOKIE_DOMAIN', '.foo.com');

**Check that Q2A and your authentication website are using the same common cookie domain** I suggest using the [Firecookie](https://addons.mozilla.org/en-US/firefox/addon/6683/) plugin for Firebug on Firefox.

### Configure the Proxy SSO Plugin on Q2A

Congratulations! You're now ready to configure the Proxy SSO plugin via your Q2A installation.

* QA_EXTERNAL_USERS in your config file should be set to false.
* Sign in to Q2A as a Super Administrator
* Click the plugins tab and configure the plugin per the instructions and the screencast.
* Test out signing in and out of Q2A via your authentication website.
* If you're signed into your authentication website and then go to your Q2A website, you should already be signed into the Q2A application.

### Optional: Disable Q2A's Built-in user authentication support

You can configure your Q2A site so that users will only have access to Q2A via your authentication application:

1. Sign into Q2A via your authentication app. Then sign out. This ensures that you now have a user record in Q2A via SSO.
2. Sign into Q2A as a super administrator via built-in authentication. Open the user record that you created in step 1 and upgrade it to be a super administrator account. Sign out.
3. Sign in again via your authentication app and check that you're a super admin.
4. Make the regular "Login" and "Register" links invisible:
Add to your css file:
          .qa-nav-user-list .qa-nav-user-login, .qa-nav-user-list .qa-nav-user-register {display:none;}
NB. The default css file is qa-theme/Default/qa-styles.css
NB. If you later find yourself needing to login to your Q2A site directly, the url is 
    ...question2answer/index.php?qa=login
5. Disable any new registrations through the built-in system:
As an administrator, go to the Admin page. Use the **Spam** tab
  Turn ON the option "Temporarily suspend new user registrations"

## Questions?

Use the [Question2Answer Q&A site](http://www.question2answer.org/qa/)

## Credits

* Gideon Greenspan for Question2Answer
* Tim Gunter for the [Vanilla Forums Proxyconnect plugin.](http://vanillaforums.org/addon/472/proxyconnect) This plugin is based on the Vanilla Forums plugin.

## License 

GPL v2

## Change log
For Q2A 1.4.1 compatibility: No longer need most patch files. Added widget to handle the flash messages.


