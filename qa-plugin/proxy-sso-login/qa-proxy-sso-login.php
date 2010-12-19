<?php

/*
  Proxy SSO login plugin (c) 2010, Larry Kluger

	File: qa-plugin/proxy-sso-login/qa-proxy-sso-login.php
	Version: 1
	Date: 2010-12-10 06:34:00 GMT
	Description: Login module class for Proxy SSO login plugin

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.
	
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	More about this license: http://www.question2answer.org/license.php

  See README for more info
*/

	class qa_proxy_sso_login {
	  
	  /* This plugin enables q2a to have Single Sign On (SSO) with a sister app.
	     The sister app maintains the user list. q2a does an HTTP GET operation to
	     a preset URL on the sister app. The GET is done from the server that runs
	     q2a, not by a browser.
	     
	     The q2a server does the GET to the sister app, passing all of the available
	     cookies to the sister app. The q2a server acts as proxy to the sister app.
	     
	     The limitation is that the sister app's session cookies stored on the user's
	     browser need to be visible to the q2a server. This means that both the 
	     q2a and sister app have to share a common part of their domains and set their
	     cookie domains accordingly.
	     
	     Eg sister app can be at www.foo.com and q2a can be at q2a.foo.com. But both
	     apps must then set their cookie domains to be foo.com. The proxy won't work
	     if the sister app uses cookie domain www.foo.com.
	  
	     This plugin stores the following as options within q2a:
	     
	     proxy_sso_url -- Required. Complete url (include http://) used for the GET requests
	                      to see if a user is currently logged in. Can include query 
	                      parameters. Eg http://foo.com/sso?q2a=1
	                      
	     proxy_sso_login -- Required. Complete url for logging in at sister app.
	                        Can include query parameters. Will also be given 
	                        additional query parameter of redirect.
	                            
	     proxy_sso_logout -- Required. Complete url for logging out at sister app.
	                         Can include query parameters. Will also be given 
	                         additional query parameter of redirect.
	                            
	     proxy_sso_login_label -- Optional. Label for logging in. Default 'Login'.

	     proxy_sso_new_user_msg -- Optional. A msg for the user the first time they sign in to
	                               q2a via proxy sso.             
	                            
	     proxy_sso_welcome_msg -- Optional. A welcome msg for the user when they sign in to
	                              q2a via proxy sso. Not used for the first time.
	     
	     Both proxy_sso_new_user_msg and proxy_sso_welcome_msg can use include arguments:
	       %1$s -- fname
	       %2$s -- name
	       %3$s -- handle assigned by q2a. May be diifferent from the requested handle if
                 another user already had that handle.
                 
	                            	     
	     The proxy_sso_url is called with a GET operation by the q2a server with any available
	     cookies from the browser. It returns:
	     * If no user is logged in: return a zero length HTTP body. -- No data at all.
	     * If a user is logged in: return the following as a JSON encoded hash/associative
	       array. Members:
	       
	       id         Required. An id for the user that is unique in the sister application. Can be
	                  a number or a string.
	       
	       The following are only used the first time to create the new user. 
         email      Required. User's email
         handle     Required. A proposed handle for the user. If it is already taken by someone else
                    in the q2a system, then it will be modified to be unique. The user can then 
                    further change it as desired in the account profile page. If your system
                    does not use handles, then you must create one for the user. Eg Initials; First 
                    name and initial from last name.
         confirmed  Required. Boolean. Has the email been verified to belong to the user?
         name       Required. Full name of the user. Not publicly shown.
         fname      Optional. First name. Used for "Welcome back Larry!" message
         location   Optional.
         website    Optional.
         about      Optional. A description.
         avatar     Optional. Complete url of a photo or avatar for the user.
	  
	  =========================================================================================
	  =========================================================================================	  
	  DEBUGGING
	  
	  To see the messages that are being received from the remove server via the proxy_sso_url:
	  
	  Add 	
	  define('QA_PROXY_SSO_DEBUG', true); // either in this file or the main qa-config.php file

	  */
		
		var $directory;
		var $urltoroot;
		
		function load_module($directory, $urltoroot)
		{
			$this->directory=$directory;
			$this->urltoroot=$urltoroot;
			$this->source_name='proxy_sso';
		}
		
		function proxy_active() {
		  return strlen(qa_opt('proxy_sso_url'));
		}
		
    // Function: check_login
    // Called by qa-page
    // Direct return: none
    // Side effect: Call library function qa_log_in_external_user if the user id logged
    //              in via the other service. 
    //              
		// Note: Called for every page display until someone is logged in. -- So needs to quickly determine
		//       that no one is logged in if that is the case.
		function check_login()
		{
			if (!$this->proxy_active())
				return;
			if (!$this->do_sso_call()) // Don't make a remote url query if there's no point
				return;
			
			$url = qa_opt('proxy_sso_url');
			$raw_data = ""; // $raw_data needs to be set here since it isn't set if there's an exception
			
      try {
        $raw_data = $this->proxy_request($url);
      } catch (Exception $e) {
          qa_set_flash('Oops! We had a problem contacting the Single Sign-on server ' . $url . '. Please try again or contact support if the problem continues. Thank you.');
          error_log("ERROR, qa-proxy-sso-login when contacting " . $url . ' -- ' . $e->getMessage());
      }
      if (defined('QA_PROXY_SSO_DEBUG') && QA_PROXY_SSO_DEBUG)
        error_log('Proxy-sso-login Received from SSO server: ' . print_r($raw_data, true));
      
      if (strlen($raw_data) == 0)
        return; // no one logged in
      
      if (substr($raw_data, 0, 1) !== '{') {
        // Problem, JSON was not returned. Either a temp network error or a more
        // substantive editor with the proxy_sso_url function
        qa_set_flash('Oops! We had a problem contacting the Single Sign-on server ' . $url . '. Please try again or contact support if the problem continues. Thank you.');
        error_log("ERROR, qa-proxy-sso-login when contacting " . $url . ' -- ' . print_r($raw_data, true));
      } else {
        // Decode the data and log the person in. If first time, then a new user will be created 
			  require_once $this->directory.'JSON.php';
			  
			  $json=new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
			  $user=$json->decode($raw_data);
			  
			  if (is_array($user)) {
			    // qa_log_in_external_user is defined in qa-include/qa-app-users
			    // It will either login a user or create a new user and then log him in.
			  	list($new_user, $handle) = qa_log_in_external_user($this->source_name, $user['id'], array(
			  		'email' => $user['email'],
			  		'handle' => $user['handle'],
			  		'confirmed' => $user['confirmed'],
			  		'name' => $user['name'],
			  		'location' => @$user['location'],
			  		'website' => @$user['website'],
			  		'about' => @$user['about'],
			  		'avatar' => strlen(@$user['picture']) ? qa_retrieve_url($user['picture']) : null,
			  	));
	        // Set flash welcome msg
	        // %1$s -- fname
	        // %2$s -- name
	        // %3$s -- handle assigned by q2a.
          $template = qa_opt($new_user ? 'proxy_sso_new_user_msg' : 'proxy_sso_welcome_msg');
          if (strlen($template)) 
			      qa_set_flash(sprintf($template, $user['fname'], $user['name'], $handle));
			  }
			}
		}
				
		function do_sso_call() {
		  // Should we make the remote url call to the sso server?
			$cookie_prefix = qa_opt('proxy_sso_parent_cookie');
			$do_sso_call = !strlen($cookie_prefix); // if no cookie_prefix then we always make the sso call
			
			if (!$do_sso_call) {
		  	$l = strlen($cookie_prefix);
		  	foreach ($_COOKIE as $key => $value) {
			  	if (substr($key, 0, $l)==$cookie_prefix) {
				  	$do_sso_call = true;
				    break; // end loop, we found a matching cookie
				  }
				}
			}
			return $do_sso_call;
		}

		function match_source($source)
		{
			return $source == $this->source_name;
		}
		
		function add_redirect($url, $to) 
		{
		   return $url . (strpos($url, '?') ? "&redirect=" : "?redirect=") . urlencode($to); 
		}
		
		// Called from page qa-page for the Login in the top menu,
		// plus qa-page-register and qa-page-login
		function login_html($tourl, $context)
		{
			if (!$this->proxy_active())
				return;
			$url = $this->add_redirect(qa_opt('proxy_sso_login'), $tourl);
			$l = qa_opt('proxy_sso_login_label');
			$label = strlen($l) ? $l : 'Login';
			echo "<a class='qa-nav-user-link' href='" . $url . "'>" . $label . "</a>"; 
		}
		
		function logout_html($tourl)
		{
			if (!$this->proxy_active())
				return;
			$url = $this->add_redirect(qa_opt('proxy_sso_logout'), $tourl);
			echo "<a class='qa-nav-user-link' href='" . $url . "'>Logout</a>"; 
		}
		
		function admin_form()
		{
			$saved=false;

			if (qa_clicked('proxy_sso_save_button')) {
				qa_opt('proxy_sso_url', qa_post_text('proxy_sso_url'));
				qa_opt('proxy_sso_login', qa_post_text('proxy_sso_login'));
				qa_opt('proxy_sso_logout', qa_post_text('proxy_sso_logout'));
				qa_opt('proxy_sso_new_user_msg', qa_post_text('proxy_sso_new_user_msg'));
				qa_opt('proxy_sso_welcome_msg', qa_post_text('proxy_sso_welcome_msg'));
				qa_opt('proxy_sso_login_label', qa_post_text('proxy_sso_login_label'));
				qa_opt('proxy_sso_parent_cookie', qa_post_text('proxy_sso_parent_cookie'));
				$saved=true;
			}
		  
		  // Forms are processed by qa-theme-base. See functions form_field_rows, form_label,\
		  // form_data et al
			return array(
				'ok' => $saved ? 'Settings saved' : null,
				'title' => "Proxy SSO Settings",
				
				'fields' => array(
					array(
						'label' => 'SSO URL:',
						'value' => qa_html(qa_opt('proxy_sso_url')),
						'tags' => 'NAME="proxy_sso_url"',
						'note' => 'Full url (include http://) that will return a JSON object/associative array if the user is logged in. See <a href="https://github.com/larrykluger/Question-2-Answer-Proxy-SSO-Plugin" target="_blank">docs.</a>'
					),

					array(
						'label' => 'Login URL:',
						'value' => qa_html(qa_opt('proxy_sso_login')),
						'tags' => 'NAME="proxy_sso_login"',
						'note' => 'Full url (include http://) to login at the SSO application.'
					),

					array(
						'label' => 'Logout URL:',
						'value' => qa_html(qa_opt('proxy_sso_logout')),
						'tags' => 'NAME="proxy_sso_logout"',
						'note' => 'Full url (include http://) to logout from the SSO and this Q2A application'
					),

					array(
						'label' => 'New user message:',
						'value' => qa_html(qa_opt('proxy_sso_new_user_msg')),
						'tags' => 'NAME="proxy_sso_new_user_msg"',
						'note' => 'Optional. Message shown to new users when they login for the first time.
						<br/>Can include arguments: %1$s&#151;first&nbsp;name; %2$s&#151;full&nbsp;name; %3$s&#151;assigned handle within Question 2 Answer. Note that the assigned handle will be different from the handle in your SSO application if someone else already had that handle within this Q2A system.<br/><br/>
Example:<br/>
Welcome to the Question & Answer site!&lt;p&gt;You have been assigned user name %3$s. You can change it by clicking on "My Account" on the upper right corner of the screen.&lt;/p&gt;'
					),

					array(
						'label' => 'Welcome message:',
						'value' => qa_html(qa_opt('proxy_sso_welcome_msg')),
						'tags' => 'NAME="proxy_sso_welcome_msg"',
						'note' => 'Optional. Message shown to users when they login other than the first time.<br/>Can include same arguments as the New user message.<br/><br/>
Example:<br/>
Welcome back %1$s!'
					),

					array(
						'label' => 'Login label:',
						'value' => qa_html(qa_opt('proxy_sso_login_label')),
						'tags' => 'NAME="proxy_sso_login_label"',
						'note' => 'Label used for Login link. Default is "Login"'
					),
					
					array(
						'label' => 'SSO Server cookie name:',
						'value' => qa_html(qa_opt('proxy_sso_parent_cookie')),
						'tags' => 'NAME="proxy_sso_parent_cookie"',
						'note' => "Optional. SSO server's session cookie name. Or just first part of name. Leave blank if you don't know."
					),
				),
				
				'buttons' => array(
					array(
						'label' => 'Save Changes',
						'tags' => 'NAME="proxy_sso_save_button"',
					),
				),
			);
		}
			
   /**
    * function ProxyRequest
    * From the Vanilla project. www.vanillaforums.org GPL v3 license
    * See file Vanilla/library/core/functions.general.php
    *
    * Uses curl or fsock to make a request to a remote server. Returns the
    * response. Request includes cookies from our client
    *
    * @param string $Url The full url to the page being requested (including http://)
    */
   function proxy_request($Url, $Timeout = FALSE, $FollowRedirects = FALSE) {
      $OriginalTimeout = $Timeout;
		if(!$Timeout)
			$Timeout = 5.0;

      $UrlParts = parse_url($Url);
      $Scheme = $this->get_value('scheme', $UrlParts, 'http');
      $Host = $this->get_value('host', $UrlParts, '');
      $Port = $this->get_value('port', $UrlParts, '80');
      $Path = $this->get_value('path', $UrlParts, '');
      $Query = $this->get_value('query', $UrlParts, '');
      // Get the cookies we'll be sending.
      $Cookie = '';
      $EncodeCookies = true;
      
      foreach($_COOKIE as $Key => $Value) {
         if(strncasecmp($Key, 'XDEBUG', 6) == 0)
            continue;
         
         if(strlen($Cookie) > 0)
            $Cookie .= '; ';
            
         $EValue = ($EncodeCookies) ? urlencode($Value) : $Value;
         $Cookie .= "{$Key}={$EValue}";
      }
      $Response = '';
      if (function_exists('curl_init')) {
         
         //$Url = $Scheme.'://'.$Host.$Path;
         $Handler = curl_init();
         curl_setopt($Handler, CURLOPT_URL, $Url . ($Query ? '?' . $Query : ''));
         curl_setopt($Handler, CURLOPT_PORT, $Port);
         curl_setopt($Handler, CURLOPT_HEADER, 1);
         curl_setopt($Handler, CURLOPT_USERAGENT, ArrayValue('HTTP_USER_AGENT', $_SERVER, 'Q2A/' . QA_VERSION));
         curl_setopt($Handler, CURLOPT_RETURNTRANSFER, 1);
         if ($Cookie != '')
            curl_setopt($Handler, CURLOPT_COOKIE, $Cookie);
                  
         $Response = curl_exec($Handler);
         $Success = TRUE;
         if ($Response == FALSE) {
            $Success = FALSE;
            $Response = curl_error($Handler);
         }
         
         curl_close($Handler);
      } else if (function_exists('fsockopen')) {
         // Use sockets and build up the http request by hand
      
         // Make the request
         $Pointer = @fsockopen($Host, $Port, $ErrorNumber, $Error);
         if (!$Pointer)
            throw new Exception(sprintf('Encountered an error while making a request to the remote server (%1$s): [%2$s] %3$s', $Url, $ErrorNumber, $Error));
   
         if(strlen($Cookie) > 0)
            $Cookie = "Cookie: $Cookie\r\n";
         
         $HostHeader = $Host.(($Port != 80) ? ":{$Port}" : '');
         $Header = "GET $Path?$Query HTTP/1.1\r\n"
            ."Host: {$HostHeader}\r\n"
            // If you've got basic authentication enabled for the app, you're going to need to explicitly define the user/pass for this fsock call
            // "Authorization: Basic ". base64_encode ("username:password")."\r\n" . 
            ."User-Agent: ". $this->get_value('HTTP_USER_AGENT', $_SERVER, 'Q2A/' . QA_VERSION)."\r\n"
            ."Accept: */*\r\n"
            ."Accept-Charset: utf-8;\r\n"
            ."Connection: close\r\n";
            
         if ($Cookie != '')
            $Header .= $Cookie;
         
         $Header .= "\r\n";
         
         // Send the headers and get the response
         fputs($Pointer, $Header);
         while ($Line = fread($Pointer, 4096)) {
            $Response .= $Line;
         }
         @fclose($Pointer);
         $Response = trim(substr($Response, strpos($Response, "\r\n\r\n") + 4));
         $Success = TRUE;
      } else {
         throw new Exception('Encountered an error while making a request to the remote server: Your PHP configuration does not allow curl or fsock requests.');
      }
      
      if (!$Success)
         return $Response; // Return the error response
      
      $ResponseHeaderData = trim(substr($Response, 0, strpos($Response, "\r\n\r\n")));      
      $ResponseHeaderLines = explode("\n",trim($ResponseHeaderData));
      $Status = array_shift($ResponseHeaderLines);
      $ResponseHeaders = array();
      $ResponseHeaders['HTTP'] = trim($Status);
      
      /* get the numeric status code. 
       * - trim off excess edge whitespace, 
       * - split on spaces, 
       * - get the 2nd element (as a single element array), 
       * - pop the first (only) element off it... 
       * - return that.
       */
      $ResponseHeaders['StatusCode'] = array_pop(array_slice(explode(' ',trim($Status)),1,1));
      foreach ($ResponseHeaderLines as $Line) {
         $Line = explode(':',trim($Line));
         $Key = trim(array_shift($Line));
         $Value = trim(implode(':',$Line));
         $ResponseHeaders[$Key] = $Value;
      }
      
      if ($FollowRedirects) { 
         $Code = $this->get_value('StatusCode',$ResponseHeaders, 200);
         if (in_array($Code, array(301,302))) {
            if (array_key_exists('Location', $ResponseHeaders)) {
               $Location = $this->get_value('Location', $ResponseHeaders);
               return $this->proxy_request($Location, $OriginalTimeout, $FollowRedirects);
            }
         }
      }
      
      return $Response;
   }
		
	 /**
    * From the Vanilla project. www.vanillaforums.org GPL v3 license
    * See file Vanilla/library/core/functions.general.php
    *
	  * Return the value from an associative array or an object.
	  *
	  * @param string $Key The key or property name of the value.
	  * @param mixed $Collection The array or object to search.
	  * @param mixed $Default The value to return if the key does not exist.
     * @param bool $Remove Whether or not to remove the item from the collection.
	  * @return mixed The value from the array or object.
	  */
	 function get_value($Key, &$Collection, $Default = FALSE, $Remove = FALSE) {
	 	$Result = $Default;
	 	if(is_array($Collection) && array_key_exists($Key, $Collection)) {
	 		$Result = $Collection[$Key];
          if($Remove)
             unset($Collection[$Key]);
	 	} elseif(is_object($Collection) && property_exists($Collection, $Key)) {
	 		$Result = $Collection->$Key;
          if($Remove)
             unset($Collection->$Key);
       }
	 		
       return $Result;
	 }	
		
		
		
		
		
	};
	

/*
	Omit PHP closing tag to help avoid accidental output
*/