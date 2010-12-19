#  Question 2 Answer Proxy SSO Plugin

This project is the **Proxy SSO Plugin** for the [Question 2 Answer](http://www.question2answer.org) project. Question 2 Answer is a StackOverflow clone.

**Tested with Question 2 Answer ver. 1.3**

## Multiple Authentication Methods for Question 2 Answer
Question 2 Answer (Q2A) includes multiple user authentication techniques. Some can be used together:

* Built-in user authentication support: registration, signin, signout.
* Built-in *External Users SSO.* Q2A can be configured to use an externally supplied library of authentication functions writting in PHP. Since you are writing the procedures, they offer great flexibility in connecting Q2A to different authentication systems. If External Users SSO is used, it replaces the built-in authentication system.
* *Facebook Connect Plugin.* Q2A includes a plugin that will authenticate the user with Facebook using the Facebook connect technology. Facebook Connect can be used with the built-in authentication support.
* The *Proxy SSO Plugin* (this project) enables an external *authentication website* to manage the user registration and signin functions for Q2A.

**[Screencast demonstration of Proxy SSO Plugin User Interaction](http://marketing.masteragenda.com/screencasts/q2a_proxy_sso_demo/index.html)** 

Note that the screencast is large, and may take several minutes to load. Thank you.

## Benefits

* Flexibility: The external *authentication website* can be located on the same machine as the Q2A installation or a different machine. 
* Flexibility: The *authentication website* can be written in a different language and use a different database system than the Q2A site.
* Installation speed: Only one new web method needs to be written on the *authentication website.*

## Limitations 
The Q2A and authentication websites must have the same core domain name. Eg q2a.example.com and app.example.com. Or q2a.example.com and example.com. Or example.com/q2a and app.example.com.

Why: the Proxy SSO works by having the Q2A website *proxy* the session cookies from the browser through the q2a website to the authentication website. Both the q2a and authentication website need to set their cookies to a common domain that both share. See the installation section for more information.

The authentication website must use session cookies for determining who is the currently logged on user. Cookies sent as get parameters won't work.

## Installation

You will install the software on your q2a system, modify your authentication system, and then configure and test.

### Question 2 Answer Installation

1. Copy the qa-plugin/proxy-sso-login to your installation's qa-plugin directory.

2. qa-include patches: Several core Q2A files need to be patched to enable the plugin. Copy/over-write the files in qa-include to your Q2A installation. 

3. css patch: copy the qa-theme/Default/qa-styles.css file to your installation. If you've already modified your css file, then just add the following new rules for the new "flash" div:

<code>.qa-flash {background:#efe; border:1px solid #090; color:#090; font-size:16px;
                 padding:6px; text-align:center; margin-bottom: 10px; width: 80%;
                 font-weight: bold;}
.qa-flash p {color:#090; font-size:12px; font-weight: normal; text-align:center; margin-top: 6px;}
</code>