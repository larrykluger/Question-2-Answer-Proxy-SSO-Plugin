<?php

/*
	Proxy SSO Plugin 1.4.1 (c) 2011, Larry Kluger

	http://www.question2answer.org/

	
	File: qa-plugin/mouseover-layer/qa-mouseover-layer.php
	Version: 1.4.1
	Date: 2011-07-10 06:58:57 GMT
	Description: Theme layer class for mouseover layer plugin


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

	class qa_html_theme_layer extends qa_html_theme_base {
	  
	  function main() {
	    if ($this->template == 'account') {
        // Remove password form if the user "source" is not null
        $source = qa_get_logged_in_user_field('sessionsource');
	      if (strlen ($source)> 0) {
          unset($this->content['form_password']);
        }	      
	    }
			qa_html_theme_base::main(); // call back through to the default function	    
	  }
	};
	

/*
	Omit PHP closing tag to help avoid accidental output
*/