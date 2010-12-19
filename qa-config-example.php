<?php

/*
	Question2Answer 1.3 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-config-example.php
	Version: 1.3
	Date: 2010-11-23 06:34:00 GMT
	Description: After renaming, use this to set up database details and other stuff


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
	======================================================================
	  THE 4 DEFINITIONS BELOW ARE REQUIRED AND MUST BE SET BEFORE USING!
	======================================================================
*/

	define('QA_MYSQL_HOSTNAME', '127.0.0.1'); // try '127.0.0.1' or 'localhost' if MySQL on same server
	define('QA_MYSQL_USERNAME', 'your-mysql-username');
	define('QA_MYSQL_PASSWORD', 'your-mysql-password');
	define('QA_MYSQL_DATABASE', 'your-mysql-db-name');
	
/*
	Ultra-concise installation instructions:
	
	1. Create a MySQL database.
	2. Create a MySQL user with full permissions for that database.
	3. Rename this file to qa-config.php.
	4. Set the above four definitions and save.
	5. Place all the Question2Answer files on your server.
	6. Open the appropriate URL, and follow the instructions.

	More detailed installation instructions here: http://www.question2answer.org/
*/

/*
	======================================================================
	 OPTIONAL DEFINITIONS FOR SINGLE SIGN-ON
	======================================================================
   By default, the complete domain name is used for cookies.
   If you're using proxy-sso-login and you've installed q2a on a different domain
   then your main app-- eg:  q2a.foo.com and foo.com; or q2a.foo.com and main_app.foo.com
   Then you can and should set it to the 'main' part of your domain  */
	 
	 //define('QA_COOKIE_DOMAIN', 'your_domain_name.com');
	 //define('QA_PROXY_SSO_DEBUG', true);


/*
	======================================================================
	 REGULAR AUTHENTICATION ON or OFF
	======================================================================
   By default, QA enables people to signin and register through its
   built-in name/password authentication system. 
   
   But you might decide to turn of standard authentication and force
   all of your users to signin via Facebook, Proxy SSO, etc.
   
   If you use "External_Users" authentication, then you should leave
   regular authentication on since External Users replaces regular
   authentication. */
   define ('QA_ENABLE_REG_AUTH', true);


/*
	======================================================================
	 OPTIONAL CONSTANT DEFINITIONS, INCLUDING SUPPORT FOR SINGLE SIGN-ON
	======================================================================

	QA_MYSQL_TABLE_PREFIX will be added to all table names, to allow multiple datasets
	in a single MySQL database, or to include the QA tables in an existing database.
*/

	define('QA_MYSQL_TABLE_PREFIX', 'qa_');

/*
	Flags for using external code - set to true if you're replacing default functions
	
	QA_EXTERNAL_LANG to use your language translation logic in qa-external/qa-external-lang.php
	QA_EXTERNAL_USERS to use your user identification code in qa-external/qa-external-users.php
	QA_EXTERNAL_EMAILER to use your email sending function in qa-external/qa-external-emailer.php
*/
	
	define('QA_EXTERNAL_USERS', false);
	define('QA_EXTERNAL_LANG', false);
	define('QA_EXTERNAL_EMAILER', false);

/*
	Some settings to help optimize your QA site's performance.
	
	If QA_HTML_COMPRESSION is true, HTML web pages will be output using Gzip compression, if
	the user's browser indicates this is supported. This will increase the performance of your
	site, but will make debugging harder if PHP does not complete execution.
	
	QA_MAX_LIMIT_START is the maximum start parameter that can be requested. As this gets
	higher, queries tend to get slower, since MySQL must examine more information. Very high
	start numbers will usually only requested by search engine robots anyway.
	
	If a title word or tag is used QA_IGNORED_WORDS_FREQ times or more, it is ignored when
	searching or finding related questions. This saves time by ignoring words which are so
	common that they are probably not worth matching on.

	Set QA_OPTIMIZE_LOCAL_DB to true if your web server and MySQL are running on the same box.
	When viewing a page on your site, this will use several simple MySQL queries instead of one
	complex one, which makes sense since there is no latency for localhost access.
	
	Set QA_PERSISTENT_CONN_DB to true to use persistent database connections. Only use this if
	you are absolutely sure it is a good idea under your setup - generally it is not.
	For more information: http://www.php.net/manual/en/features.persistent-connections.php
	
	Set QA_DEBUG_PERFORMANCE to true to show detailed performance profiling information.
*/

	define('QA_HTML_COMPRESSION', true);
	define('QA_MAX_LIMIT_START', 19999);
	define('QA_IGNORED_WORDS_FREQ', 10000);
	define('QA_OPTIMIZE_LOCAL_DB', false);
	define('QA_PERSISTENT_CONN_DB', false);
	define('QA_DEBUG_PERFORMANCE', false);
	
/*
	And lastly... if you want to, you can define any constant from qa-db-maxima.php in this
	file to override the default setting. Just make sure you know what you're doing!
*/
	

/*
	Omit PHP closing tag to help avoid accidental output
*/