<?php

/*
	Question2Answer 1.3 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page.php
	Version: 1.3
	Date: 2010-11-23 06:34:00 GMT
	Description: Routing and utility functions for page requests


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


//	Table routing requests to the appropriate PHP file

	$qa_routing=array(
		'' => QA_INCLUDE_DIR.'qa-page-home.php',
		'qa' => QA_INCLUDE_DIR.'qa-page-home.php',
		'questions' => QA_INCLUDE_DIR.'qa-page-home.php',
		'unanswered' => QA_INCLUDE_DIR.'qa-page-home.php',
		'answers' => QA_INCLUDE_DIR.'qa-page-home.php', // not currently in navigation
		'comments' => QA_INCLUDE_DIR.'qa-page-home.php', // not currently in navigation
		'activity' => QA_INCLUDE_DIR.'qa-page-home.php',
		'ask' => QA_INCLUDE_DIR.'qa-page-ask.php',
		'search' => QA_INCLUDE_DIR.'qa-page-search.php',
		'categories' => QA_INCLUDE_DIR.'qa-page-categories.php',
		'tags' => QA_INCLUDE_DIR.'qa-page-tags.php',
		'users' => QA_INCLUDE_DIR.'qa-page-users.php',
		'users/special' => QA_INCLUDE_DIR.'qa-page-users-special.php',
		'users/blocked' => QA_INCLUDE_DIR.'qa-page-users-blocked.php',
		'confirm' => QA_INCLUDE_DIR.'qa-page-confirm.php',
		'account' => QA_INCLUDE_DIR.'qa-page-account.php',
		'admin' => QA_INCLUDE_DIR.'qa-page-admin.php',
		'admin/emails' => QA_INCLUDE_DIR.'qa-page-admin.php',
		'admin/layout' => QA_INCLUDE_DIR.'qa-page-admin.php',
		'admin/users' => QA_INCLUDE_DIR.'qa-page-admin.php',
		'admin/viewing' => QA_INCLUDE_DIR.'qa-page-admin.php',
		'admin/posting' => QA_INCLUDE_DIR.'qa-page-admin.php',
		'admin/permissions' => QA_INCLUDE_DIR.'qa-page-admin.php',
		'admin/points' => QA_INCLUDE_DIR.'qa-page-admin-points.php',
		'admin/feeds' => QA_INCLUDE_DIR.'qa-page-admin.php',
		'admin/spam' => QA_INCLUDE_DIR.'qa-page-admin.php',
		'admin/hidden' => QA_INCLUDE_DIR.'qa-page-admin-hidden.php',
		'admin/stats' => QA_INCLUDE_DIR.'qa-page-admin-stats.php',
		'admin/plugins' => QA_INCLUDE_DIR.'qa-page-admin-plugins.php',
		'admin/recalc' => QA_INCLUDE_DIR.'qa-page-admin-recalc.php',
		'admin/categories' => QA_INCLUDE_DIR.'qa-page-admin-categories.php',
		'admin/userfields' => QA_INCLUDE_DIR.'qa-page-admin-userfields.php',
		'admin/usertitles' => QA_INCLUDE_DIR.'qa-page-admin-usertitles.php',
		'admin/pages' => QA_INCLUDE_DIR.'qa-page-admin-pages.php',
		'forgot' => QA_INCLUDE_DIR.'qa-page-forgot.php',
		'reset' => QA_INCLUDE_DIR.'qa-page-reset.php',
		'logout' => QA_INCLUDE_DIR.'qa-page-logout.php',
		'feedback' => QA_INCLUDE_DIR.'qa-page-feedback.php',
	);

  if (!defined('QA_ENABLE_REG_AUTH'))
    define ('QA_ENABLE_REG_AUTH', true);
  if (QA_ENABLE_REG_AUTH) {	// Regular Authentication enabled?
		$qa_routing['register'] = QA_INCLUDE_DIR.'qa-page-register.php';
		$qa_routing['login'] = QA_INCLUDE_DIR.'qa-page-login.php';
	}
	
  global $qa_flash; // Holds a flash msg for the user. Declare here so early authentication
                    // plugins can use it.

	function qa_self_html()
/*
	Return an HTML-ready relative URL for the current page, preserving GET parameters - this is useful for ACTION in FORMs
*/
	{
		global $qa_used_url_format, $qa_request;
		
		return qa_path_html($qa_request, $_GET, null, $qa_used_url_format);
	}


//	Memory/CPU usage tracking
	
	if (QA_DEBUG_PERFORMANCE) {

		function qa_usage_get()
	/*
		Return an array representing the resource usage as of now
	*/
		{
			global $qa_database_usage;
			
			$usage=array(
				'files' => count(get_included_files()),
				'queries' => $qa_database_usage['queries'],
				'ram' => function_exists('memory_get_usage') ? memory_get_usage() : 0,
				'clock' => array_sum(explode(' ', microtime())),
				'mysql' => $qa_database_usage['clock'],
			);
			
			if (function_exists('getrusage')) {
				$rusage=getrusage();
				$usage['cpu']=$rusage["ru_utime.tv_sec"]+$rusage["ru_stime.tv_sec"]
					+($rusage["ru_utime.tv_usec"]+$rusage["ru_stime.tv_usec"])/1000000;
			} else
				$usage['cpu']=0;
				
			$usage['other']=$usage['clock']-$usage['cpu']-$usage['mysql'];
				
			return $usage;
		}

		
		function qa_usage_delta($oldusage, $newusage)
	/*
		Return the difference between two resource usage arrays, as an array
	*/
		{
			$delta=array();
			
			foreach ($newusage as $key => $value)
				$delta[$key]=max(0, $value-@$oldusage[$key]);
				
			return $delta;
		}

		
		function qa_usage_mark($stage)
	/*
		Mark the beginning of a new stage of script execution and store usages accordingly
	*/
		{
			global $qa_usage_last, $qa_usage_stages;
			
			$usage=qa_usage_get();
			$qa_usage_stages[$stage]=qa_usage_delta($qa_usage_last, $usage);
			$qa_usage_last=$usage;
		}

	
		function qa_usage_line($stage, $usage, $totalusage)
	/*
		Return HTML to represent the resource $usage, showing appropriate proportions of $totalusage
	*/
		{
			return sprintf(
				"%s &ndash; <B>%.1fms</B> (%d%%) &ndash; PHP %.1fms (%d%%), MySQL %.1fms (%d%%), Other %.1fms (%d%%) &ndash; %d PHP %s, %d DB %s, %dk RAM (%d%%)",
				$stage, $usage['clock']*1000, $usage['clock']*100/$totalusage['clock'],
				$usage['cpu']*1000, $usage['cpu']*100/$totalusage['clock'],
				$usage['mysql']*1000, $usage['mysql']*100/$totalusage['clock'],
				$usage['other']*1000, $usage['other']*100/$totalusage['clock'],
				$usage['files'], ($usage['files']==1) ? 'file' : 'files',
				$usage['queries'], ($usage['queries']==1) ? 'query' : 'queries',
				$usage['ram']/1024, $usage['ram'] ? ($usage['ram']*100/$totalusage['ram']) : 0
			);
		}

		
		function qa_usage_output()
	/*
		Output an (ugly) block of HTML detailing all resource usage and database queries
	*/
		{
			global $qa_usage_start, $qa_usage_stages, $qa_database_queries;
			
			echo '<P><BR><TABLE BGCOLOR="#CCCCCC" CELLPADDING="8" CELLSPACING="0" WIDTH="100%">';
		
			echo '<TR><TD COLSPAN="2">';
			
			$totaldelta=qa_usage_delta($qa_usage_start, qa_usage_get());
			
			echo qa_usage_line('Total', $totaldelta, $totaldelta).'<BR>';
			
			foreach ($qa_usage_stages as $stage => $stagedelta)
				
			echo '<BR>'.qa_usage_line(ucfirst($stage), $stagedelta, $totaldelta);
			
			echo '</TD></TR><TR VALIGN="bottom"><TD WIDTH="30%"><TEXTAREA COLS="40" ROWS="20" STYLE="width:100%;">';
			
			foreach (get_included_files() as $file)
				echo qa_html(implode('/', array_slice(explode('/', $file), -3)))."\n";
			
			echo '</TEXTAREA></TD>';
			
			echo '<TD WIDTH="70%"><TEXTAREA COLS="40" ROWS="20" STYLE="width:100%;">'.qa_html($qa_database_queries).'</TEXTAREA></TD>';
			
			echo '</TR></TABLE>';
		}
		
	//	Initialize a bunch of usage-related global variables

		$qa_database_usage=array('queries' => 0, 'clock' => 0);
		$qa_database_queries='';
		$qa_usage_last=$qa_usage_start=qa_usage_get();
	}

	
//	Other includes required for all page views

	require_once QA_INCLUDE_DIR.'qa-app-cookies.php';
	require_once QA_INCLUDE_DIR.'qa-app-format.php';
	require_once QA_INCLUDE_DIR.'qa-app-users.php';
	require_once QA_INCLUDE_DIR.'qa-app-options.php';

	
//	Connect to database

	function qa_page_db_fail_handler($type, $errno=null, $error=null, $query=null)
/*
	Standard database failure handler function which bring up the install/repair/upgrade page
*/
	{
		$pass_failure_type=$type;
		$pass_failure_errno=$errno;
		$pass_failure_error=$error;
		$pass_failure_query=$query;
		
		require QA_INCLUDE_DIR.'qa-install.php';
		
		exit;
	}

	qa_base_db_connect('qa_page_db_fail_handler');


//	Get common parameters, queue some database information for retrieval and get the ID/cookie of the current user (if any)

	$qa_start=min(max(0, (int)qa_get('start')), QA_MAX_LIMIT_START);
	$qa_state=qa_get('state');
	unset($_GET['state']); // to prevent being passed through on forms

	$qa_nav_pages_pending=true;
	$qa_logged_in_pending=true;

	$qa_login_userid=qa_get_logged_in_userid();
	$qa_cookieid=qa_cookie_get();
	

// 	If not currently logged in as anyone, see if any of the registered login modules can help
	
	if (!isset($qa_login_userid)) {
		$modulenames=qa_list_modules('login');
		
		foreach ($modulenames as $tryname) {
			$module=qa_load_module('login', $tryname);
			
			if (method_exists($module, 'check_login')) {
				$module->check_login();
				$qa_login_userid=qa_get_logged_in_userid();
	
				if (isset($qa_login_userid)) // stop and reload page if it worked
					qa_redirect($qa_request, $_GET);
			}
		}
	}

	
	function qa_content_prepare($voting=false, $categoryid=null)
/*
	Start preparing theme content in global $qa_content variable, with or without $voting support,
	in the context of $categoryid (if not null)
*/
	{
		global $qa_root_url_relative, $qa_request, $qa_login_userid, $qa_vote_error, $qa_nav_pages_cached, $qa_routing;
		
		if (QA_DEBUG_PERFORMANCE)
			qa_usage_mark('control');
		
		$qa_content=array(
			'navigation' => array(
				'user' => array(),

				'main' => array(),
				
				'footer' => array(
					'feedback' => array(
						'url' => qa_path_html('feedback'),
						'label' => qa_lang_html('main/nav_feedback'),
					),
				),
	
			),
			
			'sidebar' => qa_opt('show_custom_sidebar') ? qa_opt('custom_sidebar') : null,
			
			'sidepanel' => qa_opt('show_custom_sidepanel') ? qa_opt('custom_sidepanel') : null,
		);
		
		foreach ($qa_nav_pages_cached as $page)
			if ($page['nav']=='B')
				qa_navigation_add_page($qa_content['navigation']['main'], $page);
		
		$hascustomhome=qa_opt('show_custom_home');
		
		if (qa_opt('nav_home') && $hascustomhome)
			$qa_content['navigation']['main']['$']=array(
				'url' => qa_path_html(''),
				'label' => qa_lang_html('main/nav_home'),
			);

		if (qa_opt('nav_activity'))
			$qa_content['navigation']['main']['activity']=array(
				'url' => qa_path_html('activity'),
				'label' => qa_lang_html('main/nav_activity'),
			);
			
		if (qa_opt($hascustomhome ? 'nav_qa_not_home' : 'nav_qa_is_home'))
			$qa_content['navigation']['main'][$hascustomhome ? 'qa' : '$']=array(
				'url' => qa_path_html($hascustomhome ? 'qa' : ''),
				'label' => qa_lang_html('main/nav_qa'),
			);
			
		if (qa_opt('nav_questions'))
			$qa_content['navigation']['main']['questions']=array(
				'url' => qa_path_html('questions'),
				'label' => qa_lang_html('main/nav_qs'),
			);

		if (qa_opt('nav_unanswered'))
			$qa_content['navigation']['main']['unanswered']=array(
				'url' => qa_path_html('unanswered'),
				'label' => qa_lang_html('main/nav_unanswered'),
			);
			
		if (qa_using_tags() && qa_opt('nav_tags'))
			$qa_content['navigation']['main']['tag']=array(
				'url' => qa_path_html('tags'),
				'label' => qa_lang_html('main/nav_tags'),
			);
			
		if (qa_using_categories() && qa_opt('nav_categories'))
			$qa_content['navigation']['main']['categories']=array(
				'url' => qa_path_html('categories'),
				'label' => qa_lang_html('main/nav_categories'),
			);

		if (qa_opt('nav_users'))
			$qa_content['navigation']['main']['user']=array(
				'url' => qa_path_html('users'),
				'label' => qa_lang_html('main/nav_users'),
			);
			
		if (qa_user_permit_error('permit_post_q')!='level')
			$qa_content['navigation']['main']['ask']=array(
				'url' => qa_path_html('ask', (qa_using_categories() && strlen($categoryid)) ? array('cat' => $categoryid) : null),
				'label' => qa_lang_html('main/nav_ask'),
			);
		
		
		if (qa_get_logged_in_level()>=QA_USER_LEVEL_ADMIN)
			$qa_content['navigation']['main']['admin']=array(
				'url' => qa_path_html((isset($_COOKIE['qa_admin_last']) && isset($qa_routing[$_COOKIE['qa_admin_last']]))
					? $_COOKIE['qa_admin_last'] : 'admin'), // use previously requested admin page if valid
				'label' => qa_lang_html('main/nav_admin'),
			);
		
		$qa_content['search']=array(
			'form_tags' => ' METHOD="GET" ACTION="'.qa_path_html('search').'" ',
			'form_extra' => qa_path_form_html('search'),
			'title' => qa_lang_html('main/search_title'),
			'field_tags' => ' NAME="q" ',
			'button_label' => qa_lang_html('main/search_button'),
		);
		
		if (!qa_opt('feedback_enabled'))
			unset($qa_content['navigation']['footer']['feedback']);
			
		foreach ($qa_nav_pages_cached as $page)
			if ( ($page['nav']=='M') || ($page['nav']=='O') || ($page['nav']=='F') )
				qa_navigation_add_page($qa_content['navigation'][($page['nav']=='F') ? 'footer' : 'main'], $page);
		
		$logoshow=qa_opt('logo_show');
		$logourl=qa_opt('logo_url');
		$logowidth=qa_opt('logo_width');
		$logoheight=qa_opt('logo_height');
		
		if ($logoshow)
			$qa_content['logo']='<A HREF="'.qa_path_html('').'" CLASS="qa-logo-link" TITLE="'.qa_html(qa_opt('site_title')).'">'.
				'<IMG SRC="'.qa_html(is_numeric(strpos($logourl, '://')) ? $logourl : $qa_root_url_relative.$logourl).'"'.
				($logowidth ? (' WIDTH="'.$logowidth.'"') : '').($logoheight ? (' HEIGHT="'.$logoheight.'"') : '').
				' BORDER="0"/></A>';
		else
			$qa_content['logo']='<A HREF="'.qa_path_html('').'" CLASS="qa-logo-link">'.qa_html(qa_opt('site_title')).'</A>';

		$topath=qa_get('to'); // lets user switch between login and register without losing destination page

		$userlinks=qa_get_login_links($qa_root_url_relative, isset($topath) ? $topath : qa_path($qa_request, $_GET, ''));
		
		$qa_content['navigation']['user']=array();
			
		if (isset($qa_login_userid)) {
			$qa_content['loggedin']=qa_lang_html_sub_split('main/logged_in_x', QA_EXTERNAL_USERS
				? qa_get_logged_in_user_html(qa_get_logged_in_user_cache(), $qa_root_url_relative, false)
				: qa_get_one_user_html(qa_get_logged_in_handle(), false)
			);
			
			if (!QA_EXTERNAL_USERS)
				$qa_content['navigation']['user']['account']=array(
					'url' => qa_path_html('account'),
					'label' => qa_lang_html('main/nav_account'),
				);
				
			if (!empty($userlinks['logout']))
				$qa_content['navigation']['user']['logout']=array(
					'url' => qa_html(@$userlinks['logout']),
					'label' => qa_lang_html('main/nav_logout'),
				);
			
			if (!QA_EXTERNAL_USERS) {
				$source=qa_get_logged_in_source(); // Looking for logout html from the current auth src plugin
				
				if (strlen($source)) {
					$modulenames=qa_list_modules('login');
					
					foreach ($modulenames as $tryname) {
						$module=qa_load_module('login', $tryname);
						
						if (method_exists($module, 'match_source') && $module->match_source($source) && method_exists($module, 'logout_html')) {
							ob_start();
							$module->logout_html(qa_path('logout'));
							$qa_content['navigation']['user']['logout']=array('label' => ob_get_clean());
						}
					}
				}
			}
			
		} else {
			$modulenames=qa_list_modules('login');
			
			foreach ($modulenames as $tryname) {  // Alt authorization plugins
				$module=qa_load_module('login', $tryname);
				
				if (method_exists($module, 'login_html')) {
					ob_start();
					$module->login_html(isset($topath) ? (qa_opt('site_url').$topath) : 
					                                      qa_path($qa_request, $_GET, qa_opt('site_url')), 'menu');
					$qa_content['navigation']['user'][$tryname]=array('label' => ob_get_clean());
				}
			}
			
			if (!empty($userlinks['login']) && QA_ENABLE_REG_AUTH)
				$qa_content['navigation']['user']['login']=array(
					'url' => qa_html(@$userlinks['login']),
					'label' => qa_lang_html('main/nav_login'),
				);
				
			if (!empty($userlinks['register']) && QA_ENABLE_REG_AUTH)
				$qa_content['navigation']['user']['register']=array(
					'url' => qa_html(@$userlinks['register']),
					'label' => qa_lang_html('main/nav_register'),
				);
		}
		
		if ($voting) {
			$qa_content['error']=@$qa_vote_error;
			$qa_content['script_rel']=array('qa-content/jxs_compressed.js', 'qa-content/qa-votes.js?'.QA_VERSION);
		} else
			$qa_content['script_rel']=array();
			
		$qa_content['script_var']=array(
			'qa_root' => $qa_root_url_relative,
			'qa_request' => $qa_request,
		);
		
		return $qa_content;
	}


//	End of setup phase

	if (QA_DEBUG_PERFORMANCE)
		qa_usage_mark('setup');

	
//	Process any incoming votes

	if (qa_is_http_post())
		foreach ($_POST as $field => $value)
			if (strpos($field, 'vote_')===0) {
				@list($dummy, $postid, $vote, $anchor)=explode('_', $field);
				
				if (isset($postid) && isset($vote)) {
					require_once QA_INCLUDE_DIR.'qa-app-votes.php';
					$qa_vote_error=qa_user_vote_error($qa_login_userid, $postid, $vote, $qa_request);

					if (!$qa_vote_error)
						qa_redirect($qa_request, $_GET, null, null, $anchor);
					break;
				}
			}


//	Otherwise include the appropriate PHP file for the page in the request
	
	if (!isset($qa_content)) {
		if (isset($qa_routing[$qa_request_lc])) {
			if ($qa_request_lc_parts[0]=='admin') {
				$_COOKIE['qa_admin_last']=$qa_request_lc; // for navigation tab now...
				setcookie('qa_admin_last', $_COOKIE['qa_admin_last'], 0, '/'); // ...and in future
			}
			
			$qa_template=$qa_request_lc_parts[0];
			$qa_content=require $qa_routing[$qa_request_lc];
	
		} elseif (is_numeric($qa_request_parts[0])) {
			$pass_questionid=$qa_request_parts[0]; // effectively a parameter that is passed to file
			$qa_template='question';
			$qa_content=require QA_INCLUDE_DIR.'qa-page-question.php';
	
		} elseif ( ($qa_request_lc_parts[0]=='tag') && strlen(@$qa_request_parts[1]) ) {
			$pass_tag=$qa_request_parts[1]; // effectively a parameter that is passed to file
			$qa_template='tag';
			$qa_content=require QA_INCLUDE_DIR.'qa-page-tag.php';
	
		} elseif ( ($qa_request_lc_parts[0]=='user') && strlen(@$qa_request_parts[1]) ) {
			$pass_handle=$qa_request_parts[1]; // effectively a parameter that is passed to file
			$qa_template='user';
			$qa_content=require QA_INCLUDE_DIR.'qa-page-user.php';
	
		} elseif ( ($qa_request_lc_parts[0]=='ip') && (long2ip(ip2long(@$qa_request_parts[1]))==@$qa_request_parts[1]) ) {
			$pass_ip=$qa_request_parts[1]; // effectively a parameter that is passed to file
			$qa_template='ip';
			$qa_content=require QA_INCLUDE_DIR.'qa-page-ip.php';
	
		} else {
			$qa_template='home';
			$qa_content=require QA_INCLUDE_DIR.'qa-page-home.php'; // handles many other pages, including custom pages and page modules
		}
	}
	
	
	function qa_is_slug_reserved($requestpart)
/*
	Returns whether a URL path beginning with $requestpart is reserved by QA
*/
	{
		global $qa_routing;
		
		$requestpart=trim(strtolower($requestpart));
		
		if (isset($qa_routing[$requestpart]) || is_numeric($requestpart))
			return true;
			
		switch ($requestpart) {
			case 'tag':
			case 'user':
			case 'ip':
			case 'feed':
			case 'install':
			case 'url':
			case 'image':
				return true;
		}
		
		$modulenames=qa_list_modules('page');
		
		foreach ($modulenames as $tryname) {
			$trypage=qa_load_module('page', $tryname);

			if (method_exists($trypage, 'match_request') && $trypage->match_request($requestpart))
				return true;
		}
			
		return false;
	}
	
	
//	End of view phase

	if (QA_DEBUG_PERFORMANCE)
		qa_usage_mark('view');
	

//	Output the content if there is any
	
	if (is_array($qa_content)) {
	
	//	Set appropriate selected flags for navigation (not done in qa_content_prepare() since it also applies to sub-navigation)
		
		foreach ($qa_content['navigation'] as $navtype => $navigation)
			if (is_array($navigation))
				foreach ($navigation as $navprefix => $navlink)
					if (substr($qa_request_lc.'$', 0, strlen($navprefix)) == $navprefix)
						$qa_content['navigation'][$navtype][$navprefix]['selected']=true;
	
	
	//	Handle maintenance mode
	
		if (qa_opt('site_maintenance') && ($qa_request_lc!='login')) {
			if (qa_get_logged_in_level()>=QA_USER_LEVEL_ADMIN) {
				if (!isset($qa_content['error']))
					$qa_content['error']=strtr(qa_lang_html('admin/maintenance_admin_only'), array(
						'^1' => '<A HREF="'.qa_path_html('admin').'">',
						'^2' => '</A>',
					));
	
			} else {
				$qa_content=qa_content_prepare();
				$qa_content['error']=qa_lang_html('misc/site_in_maintenance');
			}
		}
	
	
	//	Load the appropriate theme class
	
		$themeclass=qa_load_theme_class(qa_opt('site_theme'), $qa_template, $qa_content, $qa_request);
	
	
	//	Set HTTP header and output start of HTML document
			
		header('Content-type: text/html; charset=utf-8');
		
		$themeclass->doctype();
		$themeclass->output(
			'<HTML>',
			'<!-- Powered by Question2Answer - http://www.question2answer.org/ -->'
		);
	
	
	//	Output <HEAD> section, mainly a bunch of dynamic JavaScripts
			
		$themeclass->output(
			'<HEAD>',
			'<META HTTP-EQUIV="Content-type" CONTENT="text/html; charset=utf-8"/>',
			'<TITLE>'.((empty($qa_content['title']) || empty($qa_request)) ? '' : (strip_tags($qa_content['title']).' - ')).qa_html(qa_opt('site_title')).'</TITLE>'
		);
		
		if (!empty($qa_content['description']))
			$themeclass->output('<META NAME="description" CONTENT="'.$qa_content['description'].'"/>');
		
		if (!empty($qa_content['keywords']))
			$themeclass->output('<META NAME="keywords" CONTENT="'.$qa_content['keywords'].'"/>');
				// as far as I know, META keywords have zero effect on search rankings or listings
				
		if (!empty($qa_content['canonical']))
			$themeclass->output('<LINK REL="canonical" HREF="'.$qa_content['canonical'].'"/>');
			
		if (!empty($qa_content['feed']['url']))
			$themeclass->output('<LINK REL="alternate" TYPE="application/rss+xml" HREF="'.$qa_content['feed']['url'].'" TITLE="'.@$qa_content['feed']['label'].'"/>');
			
		$themeclass->output('<SCRIPT TYPE="text/javascript"><!--');
	
		if (isset($qa_content['script_var']))
			foreach ($qa_content['script_var'] as $var => $value)
				$themeclass->output('var '.$var.'='.qa_js($value).';');
		
		if (isset($qa_content['script_lines']))
			foreach ($qa_content['script_lines'] as $script) {
				$themeclass->output('');
				$themeclass->output_array($script);
			}
			
		if (isset($qa_content['focusid']))
			$qa_content['script_onloads'][]=array(
				"var elem=document.getElementById(".qa_js($qa_content['focusid']).");",
				"if (elem) {",
				"\telem.select();",
				"\telem.focus();",
				"}",
			);
			
		if (isset($qa_content['script_onloads'])) {
			$themeclass->output(
				'',
				'var qa_oldonload=window.onload;',
				'window.onload=function() {',
				"\tif (typeof qa_oldonload=='function')",
				"\t\tqa_oldonload();"
			);
			
			foreach ($qa_content['script_onloads'] as $script) {
				$themeclass->output("\t");
				
				foreach ((array)$script as $scriptline)
					$themeclass->output("\t".$scriptline);
			}
	
			$themeclass->output('}');
		}
	
		$themeclass->output('--></SCRIPT>');
		
		if (isset($qa_content['script_rel']))
			foreach ($qa_content['script_rel'] as $script_rel)
				$themeclass->output('<SCRIPT SRC="'.qa_html($qa_root_url_relative.$script_rel).'" TYPE="text/javascript"></SCRIPT>');
	
		if (isset($qa_content['script_src']))
			foreach ($qa_content['script_src'] as $script_src)
				$themeclass->output('<SCRIPT SRC="'.qa_html($script_src).'" TYPE="text/javascript"></SCRIPT>');
	
		$themeclass->head_css();
		$themeclass->head_custom();
	
		if (qa_opt('show_custom_in_head'))
			$themeclass->output_raw(qa_opt('custom_in_head'));
	
		$themeclass->output('</HEAD>');
	
		
	//	Output <BODY> section
	
		$themeclass->output('<BODY');
		$themeclass->body_tags();
		$themeclass->output('>');
	
		if (qa_opt('show_custom_header'))
			$themeclass->output_raw(qa_opt('custom_header'));
	
		$themeclass->body_content();
	
		if (qa_opt('show_custom_footer'))
			$themeclass->output_raw(qa_opt('custom_footer'));
	
		$themeclass->output('</BODY>');
	
	
	//	Output end of HTML document and let theme do any clearing up
		
		$themeclass->output(
			'<!-- Powered by Question2Answer - http://www.question2answer.org/ -->',
			'</HTML>'
		);
	
		$themeclass->finish();
	
				
	//	End of output phase
	
		if (QA_DEBUG_PERFORMANCE) {
			qa_usage_mark('theme');
			qa_usage_output();
		}
	}

	
//	Disconnect from the database

	qa_base_db_disconnect();


/*
	Omit PHP closing tag to help avoid accidental output
*/