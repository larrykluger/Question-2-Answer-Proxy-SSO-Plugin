<?php
	
/*
	Question2Answer 1.3 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-account.php
	Version: 1.3
	Date: 2010-11-23 06:34:00 GMT
	Description: Controller for user account page


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

	if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
		header('Location: ../');
		exit;
	}

	require_once QA_INCLUDE_DIR.'qa-db-users.php';
	require_once QA_INCLUDE_DIR.'qa-app-format.php';
	require_once QA_INCLUDE_DIR.'qa-app-users.php';
	require_once QA_INCLUDE_DIR.'qa-db-selects.php';
	require_once QA_INCLUDE_DIR.'qa-util-image.php';
	
	
//	Check we're not using single-sign on integration, that we're logged in, and we're not blocked
	
	if (QA_EXTERNAL_USERS)
		qa_fatal_error('User accounts are handled by external code');
		
	if (!isset($qa_login_userid))
		qa_redirect('login');
		
	if (qa_user_permit_error()) {
		$qa_content=qa_content_prepare();
		$qa_content['error']=qa_lang_html('users/no_permission');
		return $qa_content;
	}

	
//	Get current information on user

	list($useraccount, $userprofile, $userfields)=qa_db_select_with_pending(
		qa_db_user_account_selectspec($qa_login_userid, true),
		qa_db_user_profile_selectspec($qa_login_userid, true),
		qa_db_userfields_selectspec()
	);
	
	$doconfirms=qa_opt('confirm_user_emails') && ($useraccount['level']<QA_USER_LEVEL_EXPERT);
	$isconfirmed=($useraccount['flags'] & QA_USER_FLAGS_EMAIL_CONFIRMED) ? true : false;
	$haspassword=isset($useraccount['passsalt']) && isset($useraccount['passcheck']);

	
//	Process profile if saved

	if (qa_clicked('dosaveprofile')) {
		require_once QA_INCLUDE_DIR.'qa-app-users-edit.php';
		
		$inhandle=qa_post_text('handle');
		$inemail=qa_post_text('email');
		$inavatar=qa_post_text('avatar');
		
		$errors=qa_handle_email_validate($inhandle, $inemail, $qa_login_userid);

		if (!isset($errors['handle']))
			qa_db_user_set($qa_login_userid, 'handle', $inhandle);

		if (!isset($errors['email']))
			if ($inemail != $useraccount['email']) {
				qa_db_user_set($qa_login_userid, 'email', $inemail);
				qa_db_user_set_flag($qa_login_userid, QA_USER_FLAGS_EMAIL_CONFIRMED, false);
				$isconfirmed=false;
				
				if ($doconfirms)
					qa_send_new_confirm($qa_login_userid);
			}
			
		qa_db_user_set_flag($qa_login_userid, QA_USER_FLAGS_SHOW_AVATAR, ($inavatar=='uploaded'));
		qa_db_user_set_flag($qa_login_userid, QA_USER_FLAGS_SHOW_GRAVATAR, ($inavatar=='gravatar'));

		if (is_array(@$_FILES['file']) && $_FILES['file']['size'])
			if (!qa_set_user_avatar($qa_login_userid, file_get_contents($_FILES['file']['tmp_name']), $useraccount['avatarblobid']))
				$errors['avatar']=qa_lang_sub('users/avatar_not_read', implode(', ', qa_gd_image_formats()));

		$infield=array();
		foreach ($userfields as $userfield) {
			$fieldname='field_'.$userfield['fieldid'];
			$fieldvalue=qa_post_text($fieldname);

			$infield[$fieldname]=$fieldvalue;
			qa_profile_field_validate($fieldname, $fieldvalue, $errors);

			if (!isset($errors[$fieldname]))
				qa_db_user_profile_set($qa_login_userid, $userfield['title'], $fieldvalue);
		}
		
		if (empty($errors))
			qa_redirect('account', array('state' => 'profile-saved'));

		list($useraccount, $userprofile)=qa_db_select_with_pending(
			qa_db_user_account_selectspec($qa_login_userid, true),
			qa_db_user_profile_selectspec($qa_login_userid, true)
		);

		qa_logged_in_user_flush();
	}


//	Process change password if clicked

	if (qa_clicked('dochangepassword')) {
		require_once QA_INCLUDE_DIR.'qa-app-users-edit.php';
		
		$inoldpassword=qa_post_text('oldpassword');
		$innewpassword1=qa_post_text('newpassword1');
		$innewpassword2=qa_post_text('newpassword2');
		
		$errors=array();
		
		if ($haspassword && (strtolower(qa_db_calc_passcheck($inoldpassword, $useraccount['passsalt'])) != strtolower($useraccount['passcheck'])))
			$errors['oldpassword']=qa_lang_html('users/password_wrong');

		$errors=array_merge($errors, qa_password_validate($innewpassword1));

		if ($innewpassword1 != $innewpassword2)
			$errors['newpassword2']=qa_lang_html('users/password_mismatch');
			
		if (empty($errors)) {
			qa_db_user_set_password($qa_login_userid, $innewpassword1);
			unset($inoldpassword);
			qa_redirect('account', array('state' => 'password-changed'));
		}
	}


//	Prepare content for theme

	$qa_content=qa_content_prepare();

	$qa_content['title']=qa_lang_html('profile/my_account_title');
	
	$qa_content['form']=array(
		'tags' => ' ENCTYPE="multipart/form-data" METHOD="POST" ACTION="'.qa_self_html().'" ',
		
		'style' => 'wide',
		
		'fields' => array(
			'duration' => array(
				'type' => 'static',
				'label' => qa_lang_html('users/member_for'),
				'value' => qa_time_to_string(qa_opt('db_time')-$useraccount['created']),
			),
			
			'type' => array(
				'type' => 'static',
				'label' => qa_lang_html('users/member_type'),
				'value' => qa_html(qa_user_level_string($useraccount['level'])),
			),
			
			'handle' => array(
				'label' => qa_lang_html('users/handle_label'),
				'tags' => ' NAME="handle" ',
				'value' => qa_html(isset($inhandle) ? $inhandle : $useraccount['handle']),
				'error' => qa_html(@$errors['handle']),
			),
			
			'email' => array(
				'label' => qa_lang_html('users/email_label'),
				'tags' => ' NAME="email" ',
				'value' => qa_html(isset($inemail) ? $inemail : $useraccount['email']),
				'error' => isset($errors['email']) ? qa_html($errors['email']) :
					(($doconfirms && !$isconfirmed) ? qa_insert_login_links(qa_lang_html('users/email_please_confirm')) : null),
			),
			
			'avatar' => null, // for positioning
		),
		
		'buttons' => array(
			'save' => array(
				'label' => qa_lang_html('users/save_profile'),
			),
		),
		
		'hidden' => array(
			'dosaveprofile' => '1'
		),
	);
	
	if ($qa_state=='profile-saved')
		$qa_content['form']['ok']=qa_lang_html('users/profile_saved');
		

//	Avatar upload stuff

	if (qa_opt('avatar_allow_gravatar') || qa_opt('avatar_allow_upload')) {
		$avataroptions=array();
		
		if (qa_opt('avatar_default_show') && strlen(qa_opt('avatar_default_blobid'))) {
			$avataroptions['']='<SPAN STYLE="margin:2px 0; display:inline-block;">'.
				qa_get_avatar_blob_html(qa_opt('avatar_default_blobid'), qa_opt('avatar_default_width'), qa_opt('avatar_default_height'), 32).
				'</SPAN> '.qa_lang_html('users/avatar_default');
		} else
			$avataroptions['']=qa_lang_html('users/avatar_none');

		$avatarvalue=$avataroptions[''];
	
		if (qa_opt('avatar_allow_gravatar')) {
			$avataroptions['gravatar']='<SPAN STYLE="margin:2px 0; display:inline-block;">'.
				qa_get_gravatar_html($useraccount['email'], 32).' '.strtr(qa_lang_html('users/avatar_gravatar'), array(
					'^1' => '<A HREF="http://www.gravatar.com/" TARGET="_blank">',
					'^2' => '</A>',
				)).'</SPAN>';

			if ($useraccount['flags'] & QA_USER_FLAGS_SHOW_GRAVATAR)
				$avatarvalue=$avataroptions['gravatar'];
		}

		if (qa_has_gd_image() && qa_opt('avatar_allow_upload')) {
			$avataroptions['uploaded']='<INPUT NAME="file" TYPE="file">';

			if (isset($useraccount['avatarblobid']))
				$avataroptions['uploaded']='<SPAN STYLE="margin:2px 0; display:inline-block;">'.
					qa_get_avatar_blob_html($useraccount['avatarblobid'], $useraccount['avatarwidth'], $useraccount['avatarheight'], 32).
					'</SPAN>'.$avataroptions['uploaded'];

			if ($useraccount['flags'] & QA_USER_FLAGS_SHOW_AVATAR)
				$avatarvalue=$avataroptions['uploaded'];
		}
		
		$qa_content['form']['fields']['avatar']=array(
			'type' => 'select-radio',
			'label' => qa_lang_html('users/avatar_label'),
			'tags' => ' NAME="avatar" ',
			'options' => $avataroptions,
			'value' => $avatarvalue,
			'error' => qa_html(@$errors['avatar']),
		);
		
	} else
		unset($qa_content['form']['fields']['avatar']);


//	Other profile fields

	foreach ($userfields as $userfield) {
		$fieldname='field_'.$userfield['fieldid'];
		
		$value=@$infield[$fieldname];
		if (!isset($value))
			$value=@$userprofile[$userfield['title']];
			
		$label=trim(qa_user_userfield_label($userfield), ':');
		if (strlen($label))
			$label.=':';
			
		$qa_content['form']['fields'][$userfield['title']]=array(
			'label' => qa_html($label),
			'tags' => ' NAME="'.$fieldname.'" ',
			'value' => qa_html($value),
			'error' => qa_html(@$errors[$fieldname]),
			'rows' => ($userfield['flags'] & QA_FIELD_FLAGS_MULTI_LINE) ? 8 : null,
		);
	}
	

//	Change password form
  if (QA_ENABLE_REG_AUTH) 
  {
    $qa_content['form_2']=array(
	  	'tags' => ' METHOD="POST" ACTION="'.qa_self_html().'" ',
	  	
	  	'style' => 'wide',
	  	
	  	'title' => qa_lang_html('users/change_password'),
	  	
	  	'fields' => array(
	  		'old' => array(
	  			'label' => qa_lang_html('users/old_password'),
	  			'tags' => ' NAME="oldpassword" ',
	  			'value' => qa_html(@$inoldpassword),
	  			'type' => 'password',
	  			'error' => @$errors['oldpassword'],
	  		),
	  	
	  		'new_1' => array(
	  			'label' => qa_lang_html('users/new_password_1'),
	  			'tags' => ' NAME="newpassword1" ',
	  			'type' => 'password',
	  			'error' => @$errors['password'],
	  		),
    
	  		'new_2' => array(
	  			'label' => qa_lang_html('users/new_password_2'),
	  			'tags' => ' NAME="newpassword2" ',
	  			'type' => 'password',
	  			'error' => @$errors['newpassword2'],
	  		),
	  	),
	  	
	  	'buttons' => array(
	  		'change' => array(
	  			'label' => qa_lang_html('users/change_password'),
	  		),
	  	),
	  	
	  	'hidden' => array(
	  		'dochangepassword' => '1',
	  	),
	  );
	
	  if (!$haspassword) {
	  	$qa_content['form_2']['fields']['old']['type']='static';
	  	$qa_content['form_2']['fields']['old']['value']=qa_lang_html('users/password_none');
	  }
	  
	  if ($qa_state=='password-changed')
	  	$qa_content['form']['ok']=qa_lang_html('users/password_changed');
  }
		
	return $qa_content;
	

/*
	Omit PHP closing tag to help avoid accidental output
*/