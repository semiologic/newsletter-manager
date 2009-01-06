<?php
/*
Plugin Name: Newsletter Manager
Plugin URI: http://www.semiologic.com/software/marketing/newsletter-manager/
Description: Lets you readily add a newsletter subscription form to your WordPress installation.
Author: Denis de Bernardy
Version: 4.2
Author URI: http://www.getsemiologic.com
Update Service: http://version.semiologic.com/plugins
Update Tag: newsletter_manager
Update Package: http://www.semiologic.com/media/software/marketing/newsletter-manager/newsletter-manager.zip
*/

/*
Terms of use
------------
http://www.mesoconcepts.com/license/
**/


class newsletter_manager
{
	#
	# init()
	#

	function init()
	{
		add_action('init', array('newsletter_manager', 'subscribe_user'));
		add_action('widgets_init', array('newsletter_manager', 'widgetize'));
	} # init()


	#
	# get_options()
	#

	function get_options()
	{
		if ( ( $o = get_option('newsletter_manager_widgets') ) === false )
		{
			if ( ( $o = get_option('sem_newsletter_params') ) !== false
				)
			{
			 	if ( $o['version'] < 3 )
				{
					include_once dirname(__FILE__) . '/newsletter-manager-admin.php';
					$o = newsletter_manager_admin::upgrade_options();
				}
				
				# trigger upgrade
				unset($o['version']);
				$o = array( 1 => $o );
				foreach ( array_keys( $sidebars = get_option('sidebars_widgets') ) as $k )
				{
					if ( !is_array($sidebars[$k]) )
					{
						continue;
					}
					
					if ( ( $key = array_search('newsletter', $sidebars[$k]) ) !== false )
					{
						$sidebars[$k][$key] = 'newsletter_widget-1';
						update_option('sidebars_widgets', $sidebars);
						break;
					}
					elseif ( ( $key = array_search('Newsletter', $sidebars[$k]) ) !== false )
					{
						$sidebars[$k][$key] = 'newsletter_widget-1';
						update_option('sidebars_widgets', $sidebars);
						break;
					}
				}
			}
			else
			{
				$o = array();
			}
			
			$o['version'] = '4';

			update_option('newsletter_manager_widgets', $o);
			#dump($o);
		}

		if (isset($o['%i%']) && !isset($o[1]))
		{
			$o[1] = $o['%i%'];
			unset($o['%i%']);
			update_option('newsletter_manager_widgets', $o);
		}
			
		#dump($o, get_option('sidebars_widgets'));
		
		return $o;
	} # get_options()
	
	
	#
	# new_widget()
	#
	
	function new_widget()
	{
		$o = newsletter_manager::get_options();
		$k = time();
		do $k++; while ( isset($o[$k]) );
		$o[$k] = newsletter_manager::default_options();
		
		update_option('newsletter_manager_widgets', $o);
		
		return 'newsletter_widget-' . $k;
	} # new_widget()
	
	
	#
	# default_options()
	#
	
	function default_options()
	{
		return array(
			'email' => '',
			'syntax' => 'aweber',
			'redirect' => '',
			'captions' => array(
				'widget_title' => __('Newsletter'),
				'widget_teaser' => __('Sign up to receive an occasional newsletter with insider tips, and irresistible offers.'),
				'thank_you' => __('Thank you for subscribing!'),
				'your_name' => __('Your Name'),
				'your_email' => __('Your Email'),
				'sign_up' => __('Sign Up'),
				),
			);
	} # default_options()


	#
	# widgetize()
	#

	function widgetize()
	{
		$options = newsletter_manager::get_options();
		
		$widget_options = array('classname' => 'newsletter_widget', 'description' => __( "A mailing list subscription form") );
		$control_options = array('width' => 700, 'id_base' => 'newsletter_widget');
		
		$id = false;
		
		# registered widgets
		foreach ( array_keys($options) as $o )
		{
			if ( !is_numeric($o) ) continue;
			$id = "newsletter_widget-$o";
			wp_register_sidebar_widget($id, __('Newsletter'), array('newsletter_manager', 'display_widget'), $widget_options, array( 'number' => $o ));
			wp_register_widget_control($id, __('Newsletter'), array('newsletter_manager_admin', 'widget_control'), $control_options, array( 'number' => $o ) );
		}
		
		# default widget if none were registered
		if ( !$id )
		{
			$id = "newsletter_widget-1";
			wp_register_sidebar_widget($id, __('Newsletter'), array('newsletter_manager', 'display_widget'), $widget_options, array( 'number' => -1 ));
			wp_register_widget_control($id, __('Newsletter'), array('newsletter_manager_admin', 'widget_control'), $control_options, array( 'number' => -1 ) );
		}
	} # widgetize()


	#
	# check_email()
	#

	function check_email($email)
	{
		return preg_match("/
			^
			[0-9a-zA-Z_.-]+
			@
			[0-9a-zA-Z_.-]+
			$
			/ix",
			$email
			);
	} # check_email()


	#
	# display_widget()
	#

	function display_widget($args = null, $widget_args = 1)
	{
		$options = newsletter_manager::get_options();

		if ( is_numeric($widget_args) )
			$widget_args = array( 'number' => $widget_args );
		$widget_args = wp_parse_args( $widget_args, array( 'number' => -1 ) );
		extract( $widget_args, EXTR_SKIP );
		
		$options = $options[$number];
		
		if ( is_admin() )
		{
			echo $args['before_widget']
				. $args['before_title']
				. $options['email']
				. $args['after_title']
				. $args['after_widget'];

			return;
		}

		# default args
		$defaults = array(
			'before_widget' => '',
			'after_widget' => '',
			'before_title' => '<h2>',
			'after_title' => '</h2>',
			'title' => $options['captions']['widget_title'],
			'teaser' => $options['captions']['widget_teaser'],
			'thank_you' => $options['captions']['thank_you'],
			);

		$args = array_merge($defaults, (array) $args);

		echo $args['before_widget'] . "\n";

		echo $args['title'] ? ( $args['before_title'] . $args['title'] . $args['after_title'] . "\n" ) : '';

		if ( !isset($_GET['subscribed']) )
		{
			echo $args['teaser'] ? ( '<div class="teaser">' . wpautop($args['teaser']) . '</div>' . "\n" ) : '';
		}

		echo newsletter_manager::get_form($number);

		echo $args['after_widget'] . "\n";
	} # display_widget()


	#
	# get_form()
	#

	function get_form($number)
	{
		$options = newsletter_manager::get_options();
		$options = $options[$number];

		if ( isset($_GET['subscribed']) )
		{
			return wpautop($options['captions']['thank_you']);
		}
		elseif ( !newsletter_manager::check_email($options['email']) )
		{
			return '<div style="border: solid 2px firebrick; padding: 5px; background-color: AntiqueWhite; color: firebrick; font-weight: bold;">'
				. __('Your mailing list is not configured.')
				. '</div>' . "\n";
		}

		switch ( $options['syntax'] )
		{
		case 'aweber':
			return newsletter_manager::aweber_form($number);
			break;

		default:
			return newsletter_manager::default_form($number);
			break;
		}
	} # get_form()


	#
	# default_form()
	#

	function default_form($number)
	{
		$id =& $number;

		$options = newsletter_manager::get_options();
		$options = $options[$number];
		$captions =& $options['captions'];

		$o = '<form method="post" action="'
					. $_SERVER['REQUEST_URI']
				. '"'
				. '>'
			. '<div class="newsletter_fields">'
			. '<input type="hidden" name="subscribe2newsletter" value="' . $number . '">';
		
		if (!empty($captions['your_name']))
		{
			$o .= '<input type="text"'
				. ' id="name_nm' . $id . '" name="name"'
				. ' value="' . htmlspecialchars($captions['your_name']) . '"'
				. ' onfocus="if ( this.value == \'' . addslashes(htmlspecialchars($captions['your_name'])) . '\' ) this.value = \'\';"'
				. ' onblur="if ( this.value == \'\' ) this.value = \'' . addslashes(htmlspecialchars($captions['your_name'])) . '\';"'
				. ' /><br />';
		}
		
		$o	.= '<input type="text"'
				. ' id="email_nm' . $id . '" name="email"'
				. ' value="' . htmlspecialchars($captions['your_email']) . '"'
				. ' onfocus="if ( this.value == \'' . addslashes(htmlspecialchars($captions['your_email'])) . '\' ) this.value = \'\';"'
				. ' onblur="if ( this.value == \'\' ) this.value = \'' . addslashes(htmlspecialchars($captions['your_email'])) . '\';"'
				. ' />'
			. '</div>' . "\n"
			. '<div class="newsletter_submit">'
			. '<input type="submit"'
				. ' value="' . htmlspecialchars($captions['sign_up']) . '"'
				. ' onclick="if ( !getElementById(\'email_nm' . $id . '\').value.match(/\S+@\S+/) ) { getElementById(\'email_nm' . $id . '\').focus(); return false; }"'
				. ' /></div>' . "\n"
			. '</form>';

		return $o;
	} # default_form()


	#
	# aweber_form()
	#

	function aweber_form($number)
	{
		$id =& $number;

		$options = newsletter_manager::get_options();
		$options = $options[$number];
		$captions =& $options['captions'];

		$unit = $options['email'];

		if ( strpos($unit, '@aweber.com') !== false )
		{
			$unit = preg_replace("/@.+/", "", $unit);
		}

		$o = $options['teaser']
			. '<form method="post" action="http://www.aweber.com/scripts/addlead.pl">'
			. '<input type="hidden" name="unit" value="' . $unit . '" />'
			. '<input type="hidden" name="meta_message" value="1" />'
			. '<input type="hidden" name="meta_required" value="from" />'
			. '<input type="hidden" name="redirect" value="'
				. ( $options['redirect']
					? htmlspecialchars($options['redirect'])
					: ( 'http' . ( $_SERVER['HTTPS'] == 'on' ? 's' : '' ) . '://'
						. $_SERVER['HTTP_HOST']
						. $_SERVER['REQUEST_URI']
						. ( ( strpos($_SERVER['REQUEST_URI'], '?') !== false )
							? '&'
							: '?'
							) . 'subscribed=' . $number
						)
					)
				 . '" />'
			. '<div class="newsletter_fields">';
			
		if (!empty($captions['your_name']))
		{
			$o .= '<input type="text"'
				. ' id="name_nm' . $id . '" name="name"'
				. ' value="' . attribute_escape($captions['your_name']) . '"'
				. ' onfocus="if ( this.value == \'' . addslashes(htmlspecialchars($captions['your_name'])) . '\' ) this.value = \'\';"'
				. ' onblur="if ( this.value == \'\' ) this.value = \'' . addslashes(htmlspecialchars($captions['your_name'])) . '\';"'
				. ' /><br />';
		}
		
		$o	.= '<input type="text"'
				. ' id="email_nm' . $id . '" name="from"'
				. ' value="' . htmlspecialchars($captions['your_email']) . '"'
				. ' onfocus="if ( this.value == \'' . addslashes(htmlspecialchars($captions['your_email'])) . '\' ) this.value = \'\';"'
				. ' onblur="if ( this.value == \'\' ) this.value = \'' . addslashes(htmlspecialchars($captions['your_email'])) . '\';"'
				. ' />'
			. '</div>' . "\n"
			. '<div class="newsletter_submit">'
			. '<input type="submit"'
				. ' value="' . htmlspecialchars($captions['sign_up']) . '"'
				. ' name="submit"'
				. ' onclick="if ( !getElementById(\'email_nm' . $id . '\').value.match(/\S+@\S+/) ) { getElementById(\'email_nm' . $id . '\').focus(); return false; }"'
				. ' /></div>' . "\n"
			. '</form>';

		return $o;
	} # aweber_form()


	#
	# subscribe_user()
	#

	function subscribe_user()
	{
		$options = newsletter_manager::get_options();

		if ( @ $_POST['subscribe2newsletter']
		 	&& is_numeric($_POST['subscribe2newsletter'])
		)
		{
			$options = $options[$_POST['subscribe2newsletter']];
			
			if ( newsletter_manager::check_email($options['email'])
				&& newsletter_manager::check_email($_POST['email'])
				)
			{
				$to = $options['email'];

				if ( $options['syntax'] == 'list-subscribe' )
				{
					$to = str_replace('@', '-subscribe@', $to);
				}

				$name = trim($_POST['name']);
				$email = $_POST['email'];

				$name = preg_replace("/[^\w ]+/", " ", $name);

				if ( !$name
					|| $name == $options['captions']['your_name']
					)
				{
					$name = $email;
				}

				$from = $name ? ( '"' . $name . '" <' . $email . ">" ) : $email;

				$headers = "From: $from";

				$title = 'subscribe';
				$message = 'subscribe';

				#var_dump(
				wp_mail(
					$to,
					$title,
					$message,
					$headers
					);

				if ( $options['redirect'] != '' )
				{
					wp_redirect(
						$options['redirect']
							. ( ( strpos($_SERVER['REQUEST_URI'], '?') !== false )
							? '&'
							: '?'
							) . 'subscribed=' . $_POST['subscribe2newsletter']
						);
				}
				else
				{
					wp_redirect(
						$_SERVER['REQUEST_URI']
						. ( ( strpos($_SERVER['REQUEST_URI'], '?') !== false )
							? '&'
							: '?'
							) . 'subscribed=' . $_POST['subscribe2newsletter']
						);
				}
				
				die;
			}
		}
	} # subscribe_user()
} # newsletter_manager

newsletter_manager::init();


if ( is_admin() )
{
	include dirname(__FILE__) . '/newsletter-manager-admin.php';
}



########################
#
# backward compatibility
#

function the_subscribe_form($args = null)
{
	echo '<strong>Obsolete function called: the_subscribe_form';
} # end the_subscribe_form()

function newsletter_form()
{
	echo '<strong>Obsolete function called</strong>: newsletter_form';
} // end newsletter_form()
?>