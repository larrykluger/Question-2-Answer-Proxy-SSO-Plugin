<?php

/*
	Question2Answer 1.3 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-theme-base.php
	Version: 1.3
	Date: 2010-11-23 06:34:00 GMT
	Description: Default theme class, broken into lots of little functions for easy overriding


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


/*
	How do I make a theme which goes beyond CSS to actually modify the HTML output?
	
	Create a file named qa-theme.php in your new theme directory which defines a class qa_html_theme
	that extends this base class qa_html_theme_base. You can then override any of the methods below,
	referring back to the default method using double colon (qa_html_theme_base::) notation.
	
	For more information and to see some example code, please consult the online QA documentation.
*/

	class qa_html_theme_base {
	
		var	$indent=0;
		var $lines=0;
		
		var $rooturl;
		var $template;
		var $content;
		var $request;
		
		function qa_html_theme_base($template, $content, $rooturl, $request)
	/*
		Initialize the object and assign local variables
	*/
		{
			$this->template=$template;
			$this->content=$content;
			$this->rooturl=$rooturl;
			$this->request=$request;
		}
		
		function output_array($elements)
	/*
		Output each element in $elements on a separate line, with automatic HTML indenting.
		This should be passed markup which uses the <tag/> form for unpaired tags, to help keep
		track of indenting, although its actual output converts these to <tag> for W3C validation
	*/
		{
			
			foreach ($elements as $element) {
				$delta=substr_count($element, '<')-substr_count($element, '<!')-2*substr_count($element, '</')-substr_count($element, '/>');
				
				if ($delta<0)
					$this->indent+=$delta;
				
				echo str_repeat("\t", max(0, $this->indent)).str_replace('/>', '>', $element)."\n";
				
				if ($delta>0)
					$this->indent+=$delta;
					
				$this->lines++;
			}
		}

		
		function output() // other parameters picked up via func_get_args()
	/*
		Output each passed parameter on a separate line - see output_array() comments
	*/
		{
			$this->output_array(func_get_args());
		}

		
		function output_raw($html)
	/*
		Output $html at the current indent level, but don't change indent level based on the markup within.
		Useful for user-entered HTML which is unlikely to follow the rules we need to track indenting
	*/
		{
			if (strlen($html))
				echo str_repeat("\t", max(0, $this->indent)).$html."\n";
		}

		
		function output_split($parts, $class, $outertag='SPAN', $innertag='SPAN')
	/*
		Output the three elements ['prefix'], ['data'] and ['suffix'] of $parts (if they're defined),
		with appropriate CSS classes based on $class, using $outertag and $innertag in the markup.
	*/
		{
			if (empty($parts) && ($outertag!='TD'))
				return;
				
			$this->output(
				'<'.$outertag.' CLASS="'.$class.'">',
				(strlen(@$parts['prefix']) ? ('<'.$innertag.' CLASS="'.$class.'-pad">'.$parts['prefix'].'</'.$innertag.'>') : '').
				(strlen(@$parts['data']) ? ('<'.$innertag.' CLASS="'.$class.'-data">'.$parts['data'].'</'.$innertag.'>') : '').
				(strlen(@$parts['suffix']) ? ('<'.$innertag.' CLASS="'.$class.'-pad">'.$parts['suffix'].'</'.$innertag.'>') : ''),
				'</'.$outertag.'>'
			);
		}

		
		function finish()
	/*
		Post-output cleanup. For now, check that the indenting ended right, and if not, output a warning in an HTML comment
	*/
		{
			if ($this->indent)
				echo "<!--\nIt's no big deal, but your HTML could not be indented properly. To fix, please:\n".
					"1. Use this->output() to output all HTML.\n".
					"2. Balance all paired tags like <TD>...</TD> or <DIV>...</DIV>.\n".
					"3. Use a slash at the end of unpaired tags like <img/> or <input/>.\n".
					"Thanks!\n-->\n";
		}

		
	//	From here on, we have a large number of class methods which output particular pieces of HTML markup
	//	The calling chain is initiated from qa-index.php, or qa-ajax-vote.php for refreshing the voting box
	//	For most HTML elements, the name of the function is similar to the element's CSS class, for example:
	//	search() outputs <DIV CLASS="qa-search">, q_list() outputs <DIV CLASS="qa-q-list">, etc...

		function doctype()
		{
			$this->output('<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">');
		}
		
		function head_css()
		{
			$this->output('<LINK REL="stylesheet" TYPE="text/css" HREF="'.$this->rooturl.$this->css_name().'"/>');
		}
		
		function css_name()
		{
			return 'qa-styles.css?'.QA_VERSION;
		}

		function head_custom()
		{} // abstract method
		
		function body_content()
		{
			$this->body_prefix();
			
			$this->output('<DIV CLASS="qa-body-wrapper">', '');

			$this->header();
			$this->sidepanel();
			$this->main();
			$this->footer();
			
			$this->output('</DIV> <!-- END body-wrapper -->');
			
			$this->body_suffix();
		}
		
		function body_tags()
		{} // abstract method

		function body_prefix()
		{} // abstract method

		function body_suffix()
		{} // abstract method

		function header()
		{
			$this->output('<DIV CLASS="qa-header">');
			
			$this->logo();
			$this->nav_user_search();
			$this->nav_main_sub();
			$this->header_clear();
			
			$this->output('</DIV> <!-- END qa-header -->', '');
		}
		
		function nav_user_search()
		{
			$this->nav('user');
			$this->search();
		}
		
		function nav_main_sub()
		{
			$this->nav('main');
			$this->nav('sub');
		}
		
		function logo()
		{
			$this->output(
				'<DIV CLASS="qa-logo">',
				$this->content['logo'],
				'</DIV>'
			);
		}
		
		function search()
		{
			$search=$this->content['search'];
			
			$this->output(
				'<DIV CLASS="qa-search">',
				'<FORM '.$search['form_tags'].' >',
				@$search['form_extra']
			);
			
			$this->search_field($search);
			$this->search_button($search);
			
			$this->output(
				'</FORM>',
				'</DIV>'
			);
		}
		
		function search_field($search)
		{
			$this->output('<INPUT '.$search['field_tags'].' VALUE="'.@$search['value'].'" CLASS="qa-search-field"/>');
		}
		
		function search_button($search)
		{
			$this->output('<INPUT TYPE="submit" VALUE="'.$search['button_label'].'" CLASS="qa-search-button"/>');
		}
		
		function nav($navtype)
		{
			$navigation=@$this->content['navigation'][$navtype];
			
			if (($navtype=='user') || !empty($navigation)) {
				$this->output('<DIV CLASS="qa-nav-'.$navtype.'">');
				
				if ($navtype=='user')
					$this->logged_in();
					
				// reverse order of 'opposite' items since they float right
				foreach (array_reverse($navigation, true) as $key => $navlink)
					if (@$navlink['opposite']) {
						unset($navigation[$key]);
						$navigation[$key]=$navlink;
					}
					
				$this->nav_list($navigation, $navtype);
				$this->nav_clear($navtype);
	
				$this->output('</DIV>');
			}
		}
		
		function nav_list($navigation, $navtype)
		{
			$this->output('<UL CLASS="qa-nav-'.$navtype.'-list">');

			foreach ($navigation as $key => $navlink)
				$this->nav_item($key, $navlink, $navtype);
			
			$this->output('</UL>');
		}
		
		function nav_clear($navtype)
		{
			$this->output(
				'<DIV CLASS="qa-nav-'.$navtype.'-clear">',
				'</DIV>'
			);
		}
		
		function nav_item($key, $navlink, $navtype)
		{
			$this->output('<LI CLASS="qa-nav-'.$navtype.'-item'.(@$navlink['opposite'] ? '-opp' : '').' qa-nav-'.$navtype.'-'.$key.'">');
			$this->nav_link($navlink, $navtype);
			$this->output('</LI>');
		}
		
		function nav_link($navlink, $navtype)
		{
			if (isset($navlink['url']))
				$this->output(
					'<A HREF="'.$navlink['url'].'" CLASS="qa-nav-'.$navtype.'-link'.
					(@$navlink['selected'] ? (' qa-nav-'.$navtype.'-selected') : '').'"'.
					(isset($navlink['target']) ? (' TARGET="'.$navlink['target'].'"') : '').'>'.$navlink['label'].
					'</A>'.
					(strlen(@$navlink['note']) ? (' ('.$navlink['note'].')') : '')
				);
			else
				$this->output($navlink['label']);
		}
		
		function logged_in()
		{
			$this->output_split(@$this->content['loggedin'], 'qa-logged-in', 'DIV');
		}
		
		function header_clear()
		{
			$this->output(
				'<DIV CLASS="qa-header-clear">',
				'</DIV>'
			);
		}
		
		function sidepanel()
		{
			$this->output('<DIV CLASS="qa-sidepanel">');
			$this->sidebar();
			$this->nav('cat');
			$this->output_raw(@$this->content['sidepanel']);
			$this->feed();
			$this->output('</DIV>', '');
		}
		
		function sidebar()
		{
			$sidebar=@$this->content['sidebar'];
			
			if (!empty($sidebar)) {
				$this->output('<DIV CLASS="qa-sidebar">');
				$this->output_raw($sidebar);
				$this->output('</DIV>', '');
			}
		}
		
		function feed()
		{
			$feed=@$this->content['feed'];
			
			if (!empty($feed)) {
				$this->output('<DIV CLASS="qa-feed">');
				$this->output('<A HREF="'.$feed['url'].'" CLASS="qa-feed-link">'.@$feed['label'].'</A>');
				$this->output('</DIV>');
			}
		}
		
		function main()
		{
			$content=$this->content;

			$this->output('<DIV CLASS="qa-main'.(@$this->content['hidden'] ? ' qa-main-hidden' : '').'">');
			
			$this->page_title();			
			$this->page_error();
			$this->page_flash();
			
			if (isset($content['main_form_tags']))
				$this->output('<FORM '.$content['main_form_tags'].' >');
				
			$this->main_parts($content);
		
			if (isset($content['main_form_tags']))
				$this->output('</FORM>');
				
			$this->page_links();
			$this->suggest_next();
			
			$this->output('</DIV> <!-- END qa-main -->', '');
		}
		
		function page_title()
		{
			$title=@$this->content['title'];
			
			if (strlen($title))
				$this->output('<H1>'.$title.'</H1>');
		}
		
		function page_error()
		{
			$error=@$this->content['error'];
			
			if (strlen($error))
				$this->output(
					'<DIV CLASS="qa-error">',
					$error,
					'</DIV>'
				);
		}
		
		function page_flash()
		{
			$t = qa_clear_flash();
			if (strlen($t))
				$this->output(
					'<DIV CLASS="qa-flash">',
					$t,
					'</DIV>'
				);
		}

		function main_parts($content)
		{
			foreach ($content as $key => $part) {
				if (strpos($key, 'custom')===0)
					$this->output_raw($part);

				elseif (strpos($key, 'form')===0)
					$this->form($part);
					
				elseif (strpos($key, 'q_list')===0)
					$this->q_list_and_form($part);

				elseif (strpos($key, 'q_view')===0)
					$this->q_view($part);
					
				elseif (strpos($key, 'a_list')===0)
					$this->a_list($part);
					
				elseif (strpos($key, 'ranking')===0)
					$this->ranking($part);
			}
		}
		
		function footer()
		{
			$this->output('<DIV CLASS="qa-footer">');
			
			$this->nav('footer');
			$this->attribution();
			$this->footer_clear();
			
			$this->output('</DIV> <!-- END qa-footer -->', '');
		}
		
		function attribution()
		{
			// Hi there. I'd really appreciate you displaying this link on your Q2A site. Thank you - Gideon
				
			$this->output(
				'<DIV CLASS="qa-attribution">',
				'Powered by <A HREF="http://www.question2answer.org/">Question2Answer</A>',
				'</DIV>'
			);
		}
		
		function footer_clear()
		{
			$this->output(
				'<DIV CLASS="qa-footer-clear">',
				'</DIV>'
			);
		}

		function section($title)
		{
			if (!empty($title))
				$this->output('<H2>'.$title.'</H2>');
		}
		
		function form($form)
		{
			if (!empty($form)) {
				$this->section(@$form['title']);
				
				if (isset($form['tags']))
					$this->output('<FORM '.$form['tags'].'>');
				
				$this->form_body($form);
	
				if (isset($form['tags']))
					$this->output('</FORM>');
			}
		}
		
		function form_columns($form)
		{
			if (isset($form['ok']) || !empty($form['fields']) )
				$columns=($form['style']=='wide') ? 3 : 1;
			else
				$columns=0;
				
			return $columns;
		}
		
		function form_spacer($form, $columns)
		{
			$this->output(
				'<TR>',
				'<TD COLSPAN="'.$columns.'" CLASS="qa-form-'.$form['style'].'-spacer">',
				'&nbsp;',
				'</TD>',
				'</TR>'
			);
		}
		
		function form_body($form)
		{
			$columns=$this->form_columns($form);
			
			if ($columns)
				$this->output('<TABLE CLASS="qa-form-'.$form['style'].'-table">');
			
			$this->form_ok($form, $columns);
			$this->form_fields($form, $columns);
			$this->form_buttons($form, $columns);

			if ($columns)
				$this->output('</TABLE>');

			$this->form_hidden($form);
		}
		
		function form_ok($form, $columns)
		{
			if (!empty($form['ok']))
				$this->output(
					'<TR>',
					'<TD COLSPAN="'.$columns.'" CLASS="qa-form-'.$form['style'].'-ok">',
					$form['ok'],
					'</TD>',
					'</TR>'
				);
		}
		
		function form_fields($form, $columns)
		{
			if (!empty($form['fields'])) {
				foreach ($form['fields'] as $field)
					if (@$field['type']=='blank')
						$this->form_spacer($form, $columns);
					else
						$this->form_field_rows($form, $columns, $field);
			}
		}
		
		function form_field_rows($form, $columns, $field)
		{
			$style=$form['style'];
			
			if (isset($field['style'])) { // field has different style to most of form
				$style=$field['style'];
				$colspan=$columns;
				$columns=($style=='wide') ? 3 : 1;
			} else
				$colspan=null;
			
			$prefixed=((@$field['type']=='checkbox') && ($columns==1) && !empty($field['label']));
			$suffixed=((@$field['type']=='select') && ($columns==1) && !empty($field['label']));
			$skipdata=@$field['tight'];
			$tworows=($columns==1) && (!empty($field['label'])) && (!$skipdata);
			
			if (($columns==1) && isset($field['id']))
				$this->output('<TBODY ID="'.$field['id'].'">', '<TR>');
			elseif (isset($field['id']))
				$this->output('<TR ID="'.$field['id'].'">');
			else
				$this->output('<TR>');
			
			if (($columns>1) || !empty($field['label']))
				$this->form_label($field, $style, $columns, $prefixed, $suffixed, $colspan);
			
			if ($tworows)
				$this->output(
					'</TR>',
					'<TR>'
				);
			
			if (!$skipdata)
				$this->form_data($field, $style, $columns, !($prefixed||$suffixed), $colspan);
			
			$this->output('</TR>');
			
			if (($columns==1) && isset($field['id']))
				$this->output('</TBODY>');
		}
		
		function form_label($field, $style, $columns, $prefixed, $suffixed, $colspan)
		{
			$this->output(
				'<TD CLASS="qa-form-'.$style.'-label"'.(isset($colspan) ? (' COLSPAN="'.$colspan.'"') : '').'>'
			);
			
			if ($prefixed)
				$this->form_field($field, $style);
					
			$this->output(
				@$field['label']
			);
			
			if ($suffixed)
				$this->form_field($field, $style);
			
			$this->output('</TD>');
		}
		
		function form_data($field, $style, $columns, $showfield, $colspan)
		{
			if ($showfield || (!empty($field['error'])) || (!empty($field['note']))) {
				$this->output(
					'<TD CLASS="qa-form-'.$style.'-data"'.(isset($colspan) ? (' COLSPAN="'.$colspan.'"') : '').'>'
				);
							
				if ($showfield)
					$this->form_field($field, $style);
	
				if (!empty($field['error']))
					$this->form_error($field, $style, $columns);
				
				elseif (!empty($field['note']))
					$this->form_note($field, $style, $columns);
				
				$this->output('</TD>');
			}
		}
		
		function form_field($field, $style)
		{
			$this->form_prefix($field, $style);
			
			switch (@$field['type']) {
				case 'checkbox':
					$this->form_checkbox($field, $style);
					break;
				
				case 'static':
					$this->form_static($field, $style);
					break;
				
				case 'password':
					$this->form_password($field, $style);
					break;
				
				case 'number':
					$this->form_number($field, $style);
					break;
				
				case 'select':
					$this->form_select($field, $style);
					break;
					
				case 'select-radio':
					$this->form_select_radio($field, $style);
					break;
					
				case 'image':
					$this->form_image($field, $style);
					break;
				
				case 'custom':
					echo @$field['html'];
					break;
				
				default:
					if ((@$field['type']=='textarea') || (@$field['rows']>1))
						$this->form_text_multi_row($field, $style);
					else
						$this->form_text_single_row($field, $style);
					break;
			}
		}
		
		function form_buttons($form, $columns)
		{
			if (!empty($form['buttons'])) {
				$style=$form['style'];
				
				if ($columns)
					$this->output(
						'<TR>',
						'<TD COLSPAN="'.$columns.'" CLASS="qa-form-'.$style.'-buttons">'
					);

				foreach ($form['buttons'] as $key => $button) {
					$this->form_button_data($button, $key, $style);
					$this->form_button_note($button, $style);
				}
	
				if ($columns)
					$this->output(
						'</TD>',
						'</TR>'
					);
			}
		}
		
		function form_button_data($button, $key, $style)
		{
			$baseclass='qa-form-'.$style.'-button qa-form-'.$style.'-button-'.$key;
			$hoverclass='qa-form-'.$style.'-hover qa-form-'.$style.'-hover-'.$key;
			
			$this->output('<INPUT '.@$button['tags'].' VALUE="'.@$button['label'].'" TITLE="'.@$button['popup'].'" TYPE="submit" CLASS="'.$baseclass.'" onmouseover="this.className=\''.$hoverclass.'\';" onmouseout="this.className=\''.$baseclass.'\';"/>');
		}
		
		function form_button_note($button, $style)
		{
			if (!empty($button['note']))
				$this->output(
					'<SPAN CLASS="qa-form-'.$style.'-note">',
					$button['note'],
					'</SPAN>',
					'<BR/>'
				);
		}
		
		function form_hidden($form)
		{
			if (!empty($form['hidden']))
				foreach ($form['hidden'] as $name => $value)
					$this->output('<INPUT TYPE="hidden" NAME="'.$name.'" VALUE="'.$value.'"/>');
		}
		
		function form_prefix($field, $style)
		{
			if (!empty($field['prefix']))
				$this->output('<SPAN CLASS="qa-form-'.$style.'-prefix">'.$field['prefix'].'</SPAN>');
		}
		
		function form_checkbox($field, $style)
		{
			$this->output('<INPUT '.@$field['tags'].' TYPE="checkbox" VALUE="1"'.(@$field['value'] ? ' CHECKED' : '').' CLASS="qa-form-'.$style.'-checkbox"/>');
		}
		
		function form_static($field, $style)
		{
			$this->output('<SPAN CLASS="qa-form-'.$style.'-static">'.@$field['value'].'</SPAN>');
		}
		
		function form_password($field, $style)
		{
			$this->output('<INPUT '.@$field['tags'].' TYPE="password" VALUE="'.@$field['value'].'" CLASS="qa-form-'.$style.'-text"/>');
		}
		
		function form_number($field, $style)
		{
			$this->output('<INPUT '.@$field['tags'].' TYPE="text" VALUE="'.@$field['value'].'" CLASS="qa-form-'.$style.'-number"/>');
		}
		
		function form_select($field, $style)
		{
			$this->output('<SELECT '.@$field['tags'].' CLASS="qa-form-'.$style.'-select">');
			
			foreach ($field['options'] as $tag => $value)
				$this->output('<OPTION VALUE="'.$tag.'"'.(($value==@$field['value']) ? ' SELECTED' : '').'>'.$value.'</OPTION>');
			
			$this->output('</SELECT>');
		}
		
		function form_select_radio($field, $style)
		{
			$radios=0;
			
			foreach ($field['options'] as $tag => $value) {
				if ($radios++)
					$this->output('<BR/>');
					
				$this->output('<INPUT '.@$field['tags'].' TYPE="radio" VALUE="'.$tag.'"'.(($value==@$field['value']) ? ' CHECKED' : '').' CLASS="qa-form-'.$style.'-radio"/> '.$value);
			}
		}
		
		function form_image($field, $style)
		{
			$this->output('<DIV CLASS="qa-form-'.$style.'-image">'.@$field['html'].'</DIV>');
		}
		
		function form_text_single_row($field, $style)
		{
			$this->output('<INPUT '.@$field['tags'].' TYPE="text" VALUE="'.@$field['value'].'" CLASS="qa-form-'.$style.'-text"/>');
		}
		
		function form_text_multi_row($field, $style)
		{
			$this->output('<TEXTAREA '.@$field['tags'].' ROWS="'.(int)$field['rows'].'" COLS="40" CLASS="qa-form-'.$style.'-text">'.@$field['value'].'</TEXTAREA>');
		}
		
		function form_error($field, $style, $columns)
		{
			$tag=($columns>1) ? 'SPAN' : 'DIV';
			
			$this->output('<'.$tag.' CLASS="qa-form-'.$style.'-error">'.$field['error'].'</'.$tag.'>');
		}
		
		function form_note($field, $style, $columns)
		{
			$tag=($columns>1) ? 'SPAN' : 'DIV';
			
			$this->output('<'.$tag.' CLASS="qa-form-'.$style.'-note">'.$field['note'].'</'.$tag.'>');
		}
		
		function ranking($ranking)
		{
			$this->section(@$ranking['title']);
			
			$class=(@$ranking['type']=='users') ? 'qa-top-users' : 'qa-top-tags';
			
			$rows=min($ranking['rows'], count($ranking['items']));
			
			if ($rows>0) {
				$this->output('<TABLE CLASS="'.$class.'-table">');
			
				$columns=ceil(count($ranking['items'])/$rows);
				
				for ($row=0; $row<$rows; $row++) {
					$this->output('<TR>');
		
					for ($column=0; $column<$columns; $column++)
						$this->ranking_item(@$ranking['items'][$column*$rows+$row], $class, $column>0);
		
					$this->output('</TR>');
				}
			
				$this->output('</TABLE>');
			}
		}
		
		function ranking_item($item, $class, $spacer)
		{
			if (empty($item)) {
				if ($spacer)
					$this->ranking_spacer($class);

				$this->ranking_spacer($class);
				$this->ranking_spacer($class);
			
			} else {
				if ($spacer)
					$this->ranking_spacer($class);
				
				if (isset($item['count']))
					$this->ranking_count($item, $class);
					
				$this->ranking_label($item, $class);
					
				if (isset($item['score']))
					$this->ranking_score($item, $class);
			}
		}
		
		function ranking_spacer($class)
		{
			$this->output('<TD CLASS="'.$class.'-spacer">&nbsp;</TD>');
		}
		
		function ranking_count($item, $class)
		{
			$this->output('<TD CLASS="'.$class.'-count">'.$item['count'].' &#215;'.'</TD>');
		}
		
		function ranking_label($item, $class)
		{
			$this->output('<TD CLASS="'.$class.'-label">'.$item['label'].'</TD>');
		}
		
		function ranking_score($item, $class)
		{
			$this->output('<TD CLASS="'.$class.'-score">'.$item['score'].'</TD>');
		}
		
		function q_list_and_form($q_list)
		{
			if (!empty($q_list)) {
				$this->section(@$q_list['title']);
	
				if (!empty($q_list['form']))
					$this->output('<FORM '.$q_list['form']['tags'].'>');
				
				$this->q_list($q_list);
				
				if (!empty($q_list['form'])) {
					unset($q_list['form']['tags']); // we already output the tags before the qs
					$this->q_list_form($q_list);
					$this->output('</FORM>');
				}
			}
		}
		
		function q_list_form($q_list)
		{
			if (!empty($q_list['form'])) {
				$this->output('<DIV CLASS="qa-q-list-form">');
				$this->form($q_list['form']);
				$this->output('</DIV>');
			}
		}
		
		function q_list($q_list)
		{
			$this->output('<DIV CLASS="qa-q-list">', '');
			
			foreach ($q_list['qs'] as $question)
				$this->q_list_item($question);

			$this->output('</DIV> <!-- END qa-q-list -->', '');
		}
		
		function q_list_item($question)
		{
			$this->output('<DIV CLASS="qa-q-list-item '.@$question['classes'].'" '.@$question['tags'].'>');

			$this->q_item_stats($question);
			$this->q_item_main($question);
			$this->q_item_clear();

			$this->output('</DIV> <!-- END qa-q-list-item -->', '');
		}
		
		function q_item_stats($question)
		{
			$this->output('<DIV CLASS="qa-q-item-stats">');
			
			$this->voting($question);
			$this->a_count($question);

			$this->output('</DIV>');
		}
		
		function q_item_main($question)
		{
			$this->output('<DIV CLASS="qa-q-item-main">');
			
			$this->q_item_title($question);
			$this->post_avatar($question, 'qa-q-item');
			$this->post_meta($question, 'qa-q-item');
			$this->post_tags($question, 'qa-q-item');
			
			$this->output('</DIV>');
		}
		
		function q_item_clear()
		{
			$this->output(
				'<DIV CLASS="qa-q-item-clear">',
				'</DIV>'
			);
		}
		
		function q_item_title($question)
		{
			$this->output(
				'<DIV CLASS="qa-q-item-title">',
				'<A HREF="'.$question['url'].'">'.$question['title'].'</A>',
				'</DIV>'
			);
		}
		
		function voting($post)
		{
			if (isset($post['vote_view'])) {
				$this->output('<DIV CLASS="qa-voting '.(($post['vote_view']=='updown') ? 'qa-voting-updown' : 'qa-voting-net').'" '.@$post['vote_tags'].' >');
				$this->voting_inner_html($post);
				$this->output('</DIV>');
			}
		}
		
		function voting_inner_html($post)
		{
			$this->vote_buttons($post);
			$this->vote_count($post);
			$this->vote_clear();
		}
		
		function vote_buttons($post)
		{
			$this->output('<DIV CLASS="qa-vote-buttons '.(($post['vote_view']=='updown') ? 'qa-vote-buttons-updown' : 'qa-vote-buttons-net').'">');

			switch (@$post['vote_state'])
			{
				case 'voted_up':
					$this->post_hover_button($post, 'vote_up_tags', '+', 'qa-vote-one-button qa-voted-up');
					break;
					
				case 'voted_up_disabled':
					$this->post_disabled_button($post, 'vote_up_tags', '+', 'qa-vote-one-button qa-vote-up');
					break;
					
				case 'voted_down':
					$this->post_hover_button($post, 'vote_down_tags', '&ndash;', 'qa-vote-one-button qa-voted-down');
					break;
					
				case 'voted_down_disabled':
					$this->post_disabled_button($post, 'vote_down_tags', '&ndash;', 'qa-vote-one-button qa-vote-down');
					break;
					
				case 'enabled':
					$this->post_hover_button($post, 'vote_up_tags', '+', 'qa-vote-first-button qa-vote-up');
					$this->post_hover_button($post, 'vote_down_tags', '&ndash;', 'qa-vote-second-button qa-vote-down');
					break;

				default:
					$this->post_disabled_button($post, 'vote_up_tags', '', 'qa-vote-first-button qa-vote-up');
					$this->post_disabled_button($post, 'vote_down_tags', '', 'qa-vote-second-button qa-vote-down');
					break;
			}

			$this->output('</DIV>');
		}
		
		function vote_count($post)
		{
			// You can also use $post['upvotes_raw'], $post['downvotes_raw'], $post['netvotes_raw'] to get
			// raw integer vote counts, for graphing or showing in other non-textual ways
			
			$this->output('<DIV CLASS="qa-vote-count '.(($post['vote_view']=='updown') ? 'qa-vote-count-updown' : 'qa-vote-count-net').'">');

			if ($post['vote_view']=='updown') {
				$this->output_split($post['upvotes_view'], 'qa-upvote-count');
				$this->output_split($post['downvotes_view'], 'qa-downvote-count');
			
			} else
				$this->output_split($post['netvotes_view'], 'qa-netvote-count');

			$this->output('</DIV>');
		}
		
		function vote_clear()
		{
			$this->output(
				'<DIV CLASS="qa-vote-clear">',
				'</DIV>'
			);
		}
		
		function a_count($post)
		{
			// You can also use $post['answers_raw'] to get a raw integer count of answers
			
			$this->output_split(@$post['answers'], 'qa-a-count');
		}
		
		function avatar($post, $class)
		{
			if (isset($post['avatar']))
				$this->output('<SPAN CLASS="'.$class.'-avatar">', $post['avatar'], '</SPAN>');
		}
		
		function a_selection($post)
		{
			$this->output('<DIV CLASS="qa-a-selection">');
			
			if (isset($post['select_tags']))
				$this->post_hover_button($post, 'select_tags', '', 'qa-a-select');
			elseif (isset($post['unselect_tags']))
				$this->post_hover_button($post, 'unselect_tags', '', 'qa-a-unselect');
			elseif ($post['selected'])
				$this->output('<DIV CLASS="qa-a-selected">&nbsp;</DIV>');
			
			if (isset($post['select_text']))
				$this->output('<DIV CLASS="qa-a-selected-text">'.@$post['select_text'].'</DIV>');
			
			$this->output('</DIV>');
		}
		
		function post_hover_button($post, $element, $value, $class)
		{
			if (isset($post[$element]))
				$this->output('<INPUT '.$post[$element].' TYPE="submit" VALUE="'.$value.'" CLASS="'.$class.
					'-button" onmouseover="this.className=\''.$class.'-hover\';" onmouseout="this.className=\''.$class.'-button\';"/> ');
		}
		
		function post_disabled_button($post, $element, $value, $class)
		{
			if (isset($post[$element]))
				$this->output('<INPUT '.$post[$element].' TYPE="submit" VALUE="'.$value.'" CLASS="'.$class.'-disabled" DISABLED="disabled"/> ');
		}
		
		function post_avatar($post, $class, $prefix=null)
		{
			if (isset($post['avatar'])) {
				if (isset($prefix))
					$this->output($prefix);

				$this->output('<SPAN CLASS="'.$class.'-avatar">', $post['avatar'], '</SPAN>');
			}
		}
		
		function post_meta($post, $class, $prefix=null, $separator='<BR/>')
		{
			$this->output('<SPAN CLASS="'.$class.'-meta">');
			
			if (isset($prefix))
				$this->output($prefix);
			
			$order=explode('^', @$post['meta_order']);
			
			foreach ($order as $element)
				switch ($element) {
					case 'what':
						$this->post_meta_what($post, $class);
						break;
						
					case 'when':
						$this->post_meta_when($post, $class);
						break;
						
					case 'where':
						$this->post_meta_where($post, $class);
						break;
						
					case 'who':
						$this->post_meta_who($post, $class);
						break;
				}
			
			if (!empty($post['when_2'])) {
				$this->output($separator);
				
				foreach ($order as $element)
					switch ($element) {
						case 'when':
							$this->output_split($post['when_2'], $class.'-when');
							break;
						
						case 'who':
							$this->output_split(@$post['who_2'], $class.'-who');
							break;
					}
			}
			
			$this->output('</SPAN>');
		}
		
		function post_meta_what($post, $class)
		{
			if (isset($post['what'])) {
				if (isset($post['what_url']))
					$this->output('<A HREF="'.$post['what_url'].'" CLASS="'.$class.'-what">'.$post['what'].'</A>');
				else
					$this->output('<SPAN CLASS="'.$class.'-what">'.$post['what'].'</SPAN>');
			}
		}
		
		function post_meta_when($post, $class)
		{
			$this->output_split(@$post['when'], $class.'-when');
		}
		
		function post_meta_where($post, $class)
		{
			$this->output_split(@$post['where'], $class.'-where');
		}
		
		function post_meta_who($post, $class)
		{
			if (isset($post['who'])) {
				$this->output('<SPAN CLASS="'.$class.'-who">');
				
				if (strlen(@$post['who']['prefix']))
					$this->output('<SPAN CLASS="'.$class.'-who-pad">'.$post['who']['prefix'].'</SPAN>');
				
				if (isset($post['who']['data']))
					$this->output('<SPAN CLASS="'.$class.'-who-data">'.$post['who']['data'].'</SPAN>');
				
				if (isset($post['who']['title']))
					$this->output('<SPAN CLASS="'.$class.'-who-title">'.$post['who']['title'].'</SPAN>');
	
				if (isset($post['who']['points'])) {
					$post['who']['points']['prefix']='('.$post['who']['points']['prefix'];
					$post['who']['points']['suffix'].=')';
					$this->output_split($post['who']['points'], $class.'-who-points');
				}
				
				if (strlen(@$post['who']['suffix']))
					$this->output('<SPAN CLASS="'.$class.'-who-pad">'.$post['who']['suffix'].'</SPAN>');
	
				$this->output('</SPAN>');
			}
		}
		
		function post_tags($post, $class)
		{
			if (!empty($post['q_tags'])) {
				$this->output('<DIV CLASS="'.$class.'-tags">');
				$this->post_tag_list($post, $class);
				$this->output('</DIV>');
			}
		}
		
		function post_tag_list($post, $class)
		{
			$this->output('<UL CLASS="'.$class.'-tag-list">');
			
			foreach ($post['q_tags'] as $tag)
				$this->post_tag_item($tag, $class);
				
			$this->output('</UL>');
		}
		
		function post_tag_item($tag, $class)
		{
			$this->output('<LI CLASS="'.$class.'-tag-item">'.$tag.'</LI>');
		}
	
		function page_links()
		{
			$page_links=@$this->content['page_links'];
			
			if (!empty($page_links)) {
				$this->output('<DIV CLASS="qa-page-links">');
				
				$this->page_links_label(@$page_links['label']);
				$this->page_links_list(@$page_links['items']);
				$this->page_links_clear();
				
				$this->output('</DIV>');
			}
		}
		
		function page_links_label($label)
		{
			if (!empty($label))
				$this->output('<SPAN CLASS="qa-page-links-label">'.$label.'</SPAN>');
		}
		
		function page_links_list($page_items)
		{
			if (!empty($page_items)) {
				$this->output('<UL CLASS="qa-page-links-list">');
				
				foreach ($page_items as $page_link) {
					$this->page_links_item($page_link);
					
					if ($page_link['ellipsis'])
						$this->page_links_item(array('type' => 'ellipsis'));
				}
				
				$this->output('</UL>');
			}
		}
		
		function page_links_item($page_link)
		{
			$this->output('<LI CLASS="qa-page-links-item">');
			$this->page_link_content($page_link);
			$this->output('</LI>');
		}
		
		function page_link_content($page_link)
		{
			$label=@$page_link['label'];
			$url=@$page_link['url'];
			
			switch ($page_link['type']) {
				case 'this':
					$this->output('<SPAN CLASS="qa-page-selected">'.$label.'</SPAN>');
					break;
				
				case 'prev':
					$this->output('<A HREF="'.$url.'" CLASS="qa-page-prev">&laquo; '.$label.'</A>');
					break;
				
				case 'next':
					$this->output('<A HREF="'.$url.'" CLASS="qa-page-next">'.$label.' &raquo;</A>');
					break;
				
				case 'ellipsis':
					$this->output('<SPAN CLASS="qa-page-ellipsis">...</SPAN>');
					break;
				
				default:
					$this->output('<A HREF="'.$url.'" CLASS="qa-page-link">'.$label.'</A>');
					break;
			}
		}
		
		function page_links_clear()
		{
			$this->output(
				'<DIV CLASS="qa-page-links-clear">',
				'</DIV>'
			);
		}

		function suggest_next()
		{
			$suggest=@$this->content['suggest_next'];
			
			if (!empty($suggest)) {
				$this->output('<DIV CLASS="qa-suggest-next">');
				$this->output($suggest);
				$this->output('</DIV>');
			}
		}
		
		function q_view($q_view)
		{
			if (!empty($q_view)) {
				$this->output('<DIV CLASS="qa-q-view'.(@$q_view['hidden'] ? ' qa-q-view-hidden' : '').' '.@$q_view['classes'].'" '.@$q_view['tags'].'>');
	
				$this->voting($q_view);
				$this->a_count($q_view);
				$this->q_view_main($q_view);
				$this->q_view_clear();
				
				$this->output('</DIV> <!-- END qa-q-view -->', '');
			}
		}
		
		function q_view_main($q_view)
		{
			$this->output('<DIV CLASS="qa-q-view-main">');

			$this->q_view_content($q_view);
			$this->q_view_follows($q_view);
			$this->post_tags($q_view, 'qa-q-view');
			$this->post_avatar($q_view, 'qa-q-view');
			$this->post_meta($q_view, 'qa-q-view');
			$this->q_view_buttons($q_view);
			$this->c_list(@$q_view['c_list'], 'qa-q-view');
			$this->form(@$q_view['a_form']);
			$this->c_list(@$q_view['a_form']['c_list'], 'qa-a-item');
			$this->form(@$q_view['c_form']);
			
			$this->output('</DIV> <!-- END qa-q-view-main -->');
		}
		
		function q_view_content($q_view)
		{
			if (!empty($q_view['content']))
				$this->output(
					'<DIV CLASS="qa-q-view-content">',
					$q_view['content'],
					'</DIV>'
				);
		}
		
		function q_view_follows($q_view)
		{
			if (!empty($q_view['follows']))
				$this->output(
					'<DIV CLASS="qa-q-view-follows">',
					$q_view['follows']['label'],
					'<A HREF="'.$q_view['follows']['url'].'" CLASS="qa-q-view-follows-link">'.$q_view['follows']['title'].'</A>',
					'</DIV>'
				);
		}
		
		function q_view_buttons($q_view)
		{
			if (!empty($q_view['form'])) {
				$this->output('<DIV CLASS="qa-q-view-buttons">');
				$this->form($q_view['form']);
				$this->output('</DIV>');
			}
		}
		
		function q_view_clear()
		{
			$this->output(
				'<DIV CLASS="qa-q-view-clear">',
				'</DIV>'
			);
		}
		
		function a_list($a_list)
		{
			if (!empty($a_list)) {
				$this->section(@$a_list['title']);
				
				$this->output('<DIV CLASS="qa-a-list">', '');
					
				foreach ($a_list['as'] as $a_item)
					$this->a_list_item($a_item);
				
				$this->output('</DIV> <!-- END qa-a-list -->', '');
			}
		}
		
		function a_list_item($a_item)
		{
			$extraclass=@$a_item['classes'].($a_item['hidden'] ? ' qa-a-list-item-hidden' : ($a_item['selected'] ? ' qa-a-list-item-selected' : ''));
			
			$this->output('<DIV CLASS="qa-a-list-item '.$extraclass.'" '.@$a_item['tags'].'>');
			
			$this->voting($a_item);
			$this->a_item_main($a_item);
			$this->a_item_clear();

			$this->output('</DIV> <!-- END qa-a-list-item -->', '');
		}
		
		function a_item_main($a_item)
		{
			$this->output('<DIV CLASS="qa-a-item-main">');
			
			if ($a_item['hidden'])
				$this->output('<DIV CLASS="qa-a-item-hidden">');
			elseif ($a_item['selected'])
				$this->output('<DIV CLASS="qa-a-item-selected">');

			$this->a_selection($a_item);
			$this->a_item_content($a_item);
			$this->post_avatar($a_item, 'qa-a-item');
			$this->post_meta($a_item, 'qa-a-item');
			$this->a_item_clear();
			
			if ($a_item['hidden'] || $a_item['selected'])
				$this->output('</DIV>');
			
			$this->a_item_buttons($a_item);
			$this->c_list(@$a_item['c_list'], 'qa-a-item');
			$this->form(@$a_item['c_form']);

			$this->output('</DIV> <!-- END qa-a-item-main -->');
		}
		
		function a_item_clear()
		{
			$this->output(
				'<DIV CLASS="qa-a-item-clear">',
				'</DIV>'
			);
		}
		
		function a_item_content($a_item)
		{
			$this->output(
				'<DIV CLASS="qa-a-item-content">',
				$a_item['content'],
				'</DIV>'
			);
		}
		
		function a_item_buttons($a_item)
		{
			if (!empty($a_item['form'])) {
				$this->output('<DIV CLASS="qa-a-item-buttons">');
				$this->form($a_item['form']);
				$this->output('</DIV>');
			}
		}
		
		function c_list($c_list, $class)
		{
			if (!empty($c_list)) {
				$this->output('', '<DIV CLASS="'.$class.'-c-list">');
					
				foreach ($c_list as $c_item)
					$this->c_list_item($c_item);
				
				$this->output('</DIV> <!-- END qa-c-list -->', '');
			}
		}
		
		function c_list_item($c_item)
		{
			$extraclass=@$c_item['classes'].($c_item['hidden'] ? ' qa-c-item-hidden' : '');
			
			$this->output('<DIV CLASS="qa-c-list-item '.$extraclass.'" '.@$c_item['tags'].'>');
			$this->c_item_main($c_item);
			$this->c_item_clear();
			$this->output('</DIV> <!-- END qa-c-item -->');
		}
		
		function c_item_main($c_item)
		{
			if (isset($c_item['url']))
				$this->c_item_link($c_item);
			else
				$this->c_item_content($c_item);
			
			$this->output('<DIV CLASS="qa-c-item-footer">');
			$this->post_avatar($c_item, 'qa-c-item');
			$this->post_meta($c_item, 'qa-c-item', null, '&mdash;');
			$this->c_item_buttons($c_item);
			$this->output('</DIV>');
		}
		
		function c_item_link($c_item)
		{
			$this->output(
				'<A HREF="'.$c_item['url'].'" CLASS="qa-c-item-link">'.$c_item['title'].'</A>'
			);
		}
		
		function c_item_content($c_item)
		{
			$this->output(
				'<SPAN CLASS="qa-c-item-content">',
				$c_item['content'],
				'</SPAN>'
			);
		}
		
		function c_item_buttons($c_item)
		{
			if (!empty($c_item['form'])) {
				$this->output('<DIV CLASS="qa-c-item-buttons">');
				$this->form($c_item['form']);
				$this->output('</DIV>');
			}
		}
		
		function c_item_clear()
		{
			$this->output(
				'<DIV CLASS="qa-c-item-clear">',
				'</DIV>'
			);
		}

	}


/*
	Omit PHP closing tag to help avoid accidental output
*/