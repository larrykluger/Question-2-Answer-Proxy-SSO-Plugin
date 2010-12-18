#  Question 2 Answer Proxy SSO Plugin

This project is the **Proxy SSO Plugin** for the [Question 2 Answer](http://www.question2answer.org) project. Question 2 Answer is a StackOverflow clone.

## Multiple Authentication Methods
Question 2 Answer (Q2A) includes multiple user authentication techniques. Some can be used together:

* Built-in user authentication support: registration, signin, signout.
* Built-in *External Users SSO.* Q2A can be configured to use an externally supplied library of authentication functions writting in PHP. Since you are writing the procedures, they offer great flexibility in connecting Q2A to different authentication systems. If External Users SSO is used, it replaces the built-in authentication system.
* *Facebook Connect Plugin.* Q2A includes a plugin that will authenticate the user with Facebook using the Facebook connect technology. Facebook Connect can be used with the built-in authentication support.
* The *Proxy SSO Plugin* enables an external *authentication website* to manage the user registration and signin functions for Q2A.

## [Screencast demonstration of Proxy SSO Plugin User Interaction] (http://marketing.masteragenda.com/screencasts/q2a_proxy_sso_demo/index.html)
