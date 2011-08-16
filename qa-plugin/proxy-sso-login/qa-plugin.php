<?php

/*
	Question2Answer 1.3 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-plugin/facebook-login/qa-plugin.php
	Version: 1.3
	Date: 2010-11-23 06:34:00 GMT
	Description: Initiates Facebook login plugin


	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.
	
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	More about this license: http://www.question2answer.org/license.php
*/

/*
	Plugin Name: Proxy SSO Login
	Plugin URI: https://github.com/larrykluger/Question-2-Answer-Proxy-SSO-Plugin
	Plugin Description: Allows users to log in using Q2A as a proxy to their main app
	Plugin Version: 2.0
	Plugin Date: 2011-08-15
	Plugin Author: Larry Kluger
	Plugin Author URI: http://www.masteragenda.org/
	Plugin License: GPLv2
	Plugin Minimum Question2Answer Version: 1.4
*/


	if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
		header('Location: ../../');
		exit;
	}


	qa_register_plugin_module('login', 'qa-proxy-sso-login.php', 'qa_proxy_sso_login', 'Proxy SSO Login');
	qa_register_plugin_module('widget', 'qa-proxy-sso-login-widget.php', 'qa_proxy_sso_widget', 'Proxy SSO Greeting');	

/*
	Omit PHP closing tag to help avoid accidental output
*/