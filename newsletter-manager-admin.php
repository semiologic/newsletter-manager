<?php

class newsletter_manager_admin
{
	#
	# init()
	#

	function init()
	{
		add_filter('sem_api_key_protected', array('newsletter_manager_admin', 'sem_api_key_protected'));
	} # init()
	
		
	#
	# sem_api_key_protected()
	#
	
	function sem_api_key_protected($array)
	{
		$array[] = 'http://www.semiologic.com/media/software/marketing/newsletter-manager/newsletter-manager.zip';
		
		return $array;
	} # sem_api_key_protected()


	#
	# upgrade_options()
	#

	function upgrade_options()
	{
		$options = get_option('sem_newsletter_params');

		if ( (string) $options['version'] === '' )
		{
			$defaults = newsletter_manager::default_options();

			if ( $options === false )
			{
				$options = $defaults;
				$options['version'] = 4;
				return $options;
			}
			
			# replace add_subscribe with syntax
			if ( $options['add_subscribe'] === 'aweber' )
			{
				$options['syntax'] = 'aweber';
			}
			elseif ( !isset($options['add_subscribe']) || $options['add_subscribe'] )
			{
				$options['syntax'] = 'list-subscribe';
			}
			else
			{
				$options['syntax'] = 'list';
			}
			unset($options['add_subscribe']);
			
			# captions
			$options['captions']['widget_title'] = $options['title'];
			$options['captions']['widget_teaser'] = $options['teaser'];
			$options['captions']['thank_you'] = $options['thanks'];
			$options['captions'] = array_merge($defaults['captions'], $options['captions']);
			unset($options['title']);
			unset($options['teaser']);
			unset($options['thanks']);

			# new defaults
			$options = array_merge($defaults, $options);

			# update version
			$options['version'] = 4;
		}

		return $options;
	} # upgrade_options()


	#
	# widget_control()
	#

	function widget_control($widget_args)
	{
		global $wp_registered_widgets;
		static $updated = false;

		if ( is_numeric($widget_args) )
			$widget_args = array( 'number' => $widget_args );
		$widget_args = wp_parse_args( $widget_args, array( 'number' => -1 ) );
		extract( $widget_args, EXTR_SKIP ); // extract number

		$options = newsletter_manager::get_options();
		
		if ( !$updated && !empty($_POST['sidebar']) )
		{
			$sidebar = (string) $_POST['sidebar'];

			$sidebars_widgets = wp_get_sidebars_widgets();
		
			if ( isset($sidebars_widgets[$sidebar]) )
				$this_sidebar =& $sidebars_widgets[$sidebar];
			else
				$this_sidebar = array();

			foreach ( $this_sidebar as $_widget_id ) {
				if ( array('newsletter_manager', 'display_widget') == $wp_registered_widgets[$_widget_id]['callback']
					&& isset($wp_registered_widgets[$_widget_id]['params'][0]['number']) )
				{
					$widget_number = $wp_registered_widgets[$_widget_id]['params'][0]['number'];
					if ( !in_array( "newsletter-$widget_number", $_POST['widget-id'] ) ) // the widget has been removed.
						unset($options[$widget_number]);
				}
			}

			foreach ( (array) $_POST['widget-newsletter'] as $widget_number => $opt ) 
			{
				foreach ( array('email', 'syntax', 'redirect') as $var )
				{
					$$var = trim(strip_tags(stripslashes($opt[$var])));
				}

				if ( !newsletter_manager::check_email($email) )
				{
					$email = '';
				}
				
				$captions = array();

				foreach ( array_keys($opt['captions']) as $key )
				{
					$captions[$key] = stripslashes(wp_filter_post_kses(stripslashes($opt['captions'][$key])));
				}

				$options[$widget_number] = compact( 'email', 'syntax', 'redirect', 'captions' );
			}

			update_option('newsletter_manager_widgets', $options);
			$updated = true;
		}

		if ( -1 == $number )
		{
			$ops = newsletter_manager::default_options();
			$number = '%i%';
		}
		else
		{
			$ops = $options[$number];
		}

		echo '<input type="hidden" name="update_newsletter_options" value="1" />';

		echo '<table cellpadding="0" cellspacing="0" border="0" style="width: 680px;">'
			. '<tr valign="top"><td style="width: 340px;">';

		echo '<h3>'
			. __('Captions')
			. '</h3>' . "\n";

		$captions = array(
			'widget_title' => __('Widget Title'),
			'widget_teaser' => __('Widget Teaser'),
			'your_name' => __('Your Name (leave blank to drop field)'),
			'your_email' => __('Your Email'),
			'sign_up' => __('Sign Up'),
			'thank_you' => __('Thank You Message'),
			);

		foreach ( $captions as $key => $val )
		{
			switch ( $key )
			{
			case 'widget_teaser':
			case 'thank_you':
				echo '<div style="margin: .2em 0px;">'
					. '<label for="newsletter__caption__' . $key . '-' . $number . '">' . $val . ':</label>' . '<br />'
					. '<textarea'
						. ' style="width: 320px; height: 60px;"'
						. ' id="newsletter__caption__' . $key . '-' . $number . '" name="widget-newsletter[' . $number . '][captions][' . $key . ']"'
						. ' >'
						. format_to_edit($ops['captions'][$key])
						. '</textarea>'
					. '</div>' . "\n";
				break;

			default:
				echo '<div style="margin: .2em 0px;">'
					. '<label for="newsletter__caption__' . $key . '-' . $number . '">' . $val . ':</label>' . '<br />'
					. '<input type="text"'
						. ' style="width: 320px;"'
						. ' id="newsletter__caption__' . $key . '-' . $number . '" name="widget-newsletter[' . $number . '][captions][' . $key . ']"'
						. ' value="' . attribute_escape($ops['captions'][$key]) . '"'
						. ' />'
					. '</div>' . "\n";
				break;
			}
		}

		echo '</td><td width="50%">';

		echo '<h3>'
			. __('Mailing List')
			. '</h3>' . "\n";

		echo '<div style="margin: .2em 0px;">' . "\n"
			. '<label for="newsletter__email-' . $number . '">'
				. __('Mailing List Address')
				. ':'
				. '</label>' . '<br />'
			. '<input type="text"'
				. ' style="width: 320px;"'
				. ' id="newsletter__email-' . $number . '" name="widget-newsletter[' . $number . '][email]"'
				. ' value="' . attribute_escape($ops['email']) . '"'
				. ' />'
			. '</div>' . "\n";

		echo '<div style="margin: .2em 0px;">' . "\n"
			. __('Subscription Syntax') . ':' . '<br />'
			. '<table cellpadding="0" cellspacing="4" border="0" style="width: 320px;">' . "\n"

			. '<tr valign="top">' . "\n"
			. '<td>'
			. '<input type="radio"'
				. ' id="newsletter__syntax__aweber-' . $number . '" name="widget-newsletter[' . $number . '][syntax]"'
				. ' value="aweber"'
				. ( $ops['syntax'] == 'aweber'
					? ' checked="checked"'
					: ''
					)
				. ' />'
			. '</td>' . "\n"
			. '<td>'
				. '<label for="newsletter__syntax__aweber-' . $number . '">'
				. __('I am using <a href="http://go.semiologic.com/aweber" target="_blank">aWeber</a>, as recommended in the <a href="http://www.semiologic.com/software/marketing/newsletter-manager/" target="_blank">plugin\'s documentation</a>.')
				. '</label>'
			. '</td>' . "\n"
			. '</tr>' . "\n"

			. '<tr valign="top">' . "\n"
			. '<td>'
			. '<input type="radio"'
				. ' id="newsletter__syntax__list-' . $number . '" name="widget-newsletter[' . $number . '][syntax]"'
				. ' value="list"'
				. ( $ops['syntax'] == 'list'
					? ' checked="checked"'
					: ''
					)
				. ' />'
			. '</td>' . "\n"
			. '<td>'
				. '<label for="newsletter__syntax__list-' . $number . '">'
				. __('My list manager (e.g. <a href="http://go.semiologic.com/1shoppingcart" target="_blank">1ShoppingCart</a>, <a href="http://go.semiologic.com/getresponse" target="_blank">GetResponse</a>) lets users subscribe when they email:') . '<br />'
				. 'mylist@mydomain.com'
				. '</label>'
			. '</td>' . "\n"
			. '</tr>' . "\n"

			. '<tr valign="top">' . "\n"
			. '<td>'
			. '<input type="radio"'
				. ' id="newsletter__syntax__list_subscribe-' . $number . '" name="widget-newsletter[' . $number . '][syntax]"'
				. ' value="list-subscribe"'
				. ( $ops['syntax'] == 'list-subscribe'
					? ' checked="checked"'
					: ''
					)
				. ' />'
			. '</td>' . "\n"
			. '<td>'
				. '<label for="newsletter__syntax__list_subscribe-' . $number . '">'
				. __('My list manager (e.g. <a href="http://www.greatcircle.com/majordomo" target="_blank">majordomo</a>) lets users subscribe when they email:') . '<br />'
				. 'mylist-subscribe@mydomain.com'
				. '</label>'
			. '</td>' . "\n"
			. '</tr>' . "\n"

			. '</table>' . "\n"
			. '</div>' . "\n";

		echo '<div style="margin: .2em 0px;">' . "\n"
			. '<label for="newsletter__redirect-' . $number . '">'
				. __('Thank You Page Url (optional)')
				. ':'
				. '</label>' . '<br />'
			. '<input type="text"'
				. ' style="width: 320px;"'
				. ' id="newsletter__redirect-' . $number . '" name="widget-newsletter[' . $number . '][redirect]"'
				. ' value="' . attribute_escape($ops['redirect']) . '"'
				. ' />'
			. '</div>' . "\n";

		echo '</td></tr>'
			. '</table>';
	} # widget_control()
} # newsletter_manager_admin

newsletter_manager_admin::init();
?>