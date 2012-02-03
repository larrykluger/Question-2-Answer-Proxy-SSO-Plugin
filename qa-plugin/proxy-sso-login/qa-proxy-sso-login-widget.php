<?php

/*
	Question2Answer 1.4.1 (c) 2011, Larry Kluger

	http://www.question2answer.org/

	
	Version: 1.4.1
	Date: 2011-07-10 06:58:57 GMT
	Description: Widget module class for Proxy SSO Login


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
  Usage
  Store the flash message in the session. Eg
  
  		  $_SESSION['qa_flash']=$msg;
  
  NB. To avoid collisions with an already present flash msg, you could check the
      contents of the session before overwriting...
*/


	class qa_proxy_sso_widget {
		
		function allow_template($template)
		{
			return true;
		}
		
		function allow_region($region)
		{
			return $region == 'main';
		}
		
		function output_widget($region, $place, $themeobject, $template, $request, $qa_content)
		{
			$t = $this->clear_flash();
			if (strlen($t) > 0) {
?>
<DIV CLASS="qa-flash"><?php echo $t; ?></DIV>
<?php			
	  }}
	
	  function clear_flash() {
		  /* return current value of flash. Clear value. */
		  $f = @$_SESSION['qa_flash'];
		  $_SESSION['qa_flash']=null;
		  return $f;
		}
	};
	

/*
	Omit PHP closing tag to help avoid accidental output
*/