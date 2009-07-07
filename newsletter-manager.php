<?php
/*
Plugin Name: Newsletter Manager
Plugin URI: http://www.semiologic.com/software/newsletter-manager/
Description: Lets you readily add a newsletter subscription form to your WordPress installation.
Version: 5.0 RC
Author: Denis de Bernardy
Author URI: http://www.getsemiologic.com
Text Domain: newsletter-manager
Domain Path: /lang
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts (http://www.mesoconcepts.com), and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.mesoconcepts.com/license/
**/


load_plugin_textdomain('newsletter-manager', null, dirname(__FILE__) . '/lang');

if ( !defined('sem_newsletter_widget_debug') )
	define('sem_newsletter_widget_debug', false);


/**
 * newsletter_manager
 *
 * @package Newsletter Manager
 **/

add_action('widgets_init', array('newsletter_manager', 'widgets_init'));

if ( !is_admin() ) {
	add_action('wp_print_styles', array('newsletter_manager', 'styles'), 0);
	add_action('wp_print_scripts', array('newsletter_manager', 'scripts'), 0);
}

add_action('init', array('newsletter_manager', 'subscribe'));

class newsletter_manager extends WP_Widget {
	/**
	 * init()
	 *
	 * @return void
	 **/

	function init() {
		if ( get_option('widget_newsletter_widget') === false ) {
			foreach ( array(
				'newsletter_manager_widgets' => 'upgrade',
				'sem_newsletter_manager_params' => 'upgrade_3x',
				'sem_newsletter_params' => 'upgrade_2x',
				) as $ops => $method ) {
				if ( get_option($ops) !== false ) {
					$this->alt_option_name = $ops;
					add_filter('option_' . $ops, array(get_class($this), $method));
					break;
				}
			}
		}
	} # init()
	
	
	/**
	 * styles()
	 *
	 * @return void
	 **/

	function styles() {
		$folder = plugin_dir_url(__FILE__);
		wp_enqueue_style('newsletter_manager', $folder . 'css/styles.css', null, '5.0');
	} # styles()
	
	
	/**
	 * scripts()
	 *
	 * @return void
	 **/

	function scripts() {
		$folder = plugin_dir_url(__FILE__);
		wp_enqueue_script('newsletter_manager', $folder . 'js/scripts.js', array('jquery'), '5.0');
	} # scripts()
	
	
	/**
	 * widgets_init()
	 *
	 * @return void
	 **/

	function widgets_init() {
		register_widget('newsletter_manager');
	} # widgets_init()
	
	
	/**
	 * newsletter_manager()
	 *
	 * @return void
	 **/

	function newsletter_manager() {
		$widget_ops = array(
			'classname' => 'newsletter_widget',
			'description' => __('A newsletter subscription form.', 'newsletter-manager'),
			);
		$control_ops = array(
			'width' => 430,
			);
		
		$this->init();
		$this->WP_Widget('newsletter_widget', __('Newsletter Widget', 'newsletter-manager'), $widget_ops, $control_ops);
	} # newsletter_manager()
	
	
	/**
	 * widget()
	 *
	 * @param array $args widget args
	 * @param array $instance widget options
	 * @return void
	 **/

	function widget($args, $instance) {
		extract($args, EXTR_SKIP);
		$instance = wp_parse_args($instance, newsletter_manager::defaults());
		extract($instance, EXTR_SKIP);
		
		if ( is_admin() ) {
			echo $before_widget
				. ( $email
					? ( $before_title . $email . $after_title )
					: ''
					)
				. $after_widget;
			return;
		}
		
		$title = apply_filters('widget_title', $title);
		
		echo $before_widget;
		
		if ( $title )
			echo $before_title . $title . $after_title;
		
		if ( !is_email($email) ) {
			echo '<div style="border: solid 2px firebrick; padding: 5px; background-color: AntiqueWhite; color: firebrick; font-weight: bold;">'
				. __('Your mailing list is not configured.', 'newsletter-manager')
				. '</div>' . "\n";
		} elseif ( isset($_GET['subscribed']) && $_GET['subscribed'] == intval(end(explode('-', $widget_id))) ) {
			echo apply_filters('widget_text', wpautop($thank_you));
		} else {
			if ( $syntax == 'aweber' )
				echo newsletter_manager::get_aweber_form($args, $instance);
			else
				echo newsletter_manager::get_default_form($args, $instance);
		}
		
		echo $after_widget . "\n";
	} # widget()
	
	
	/**
	 * get_aweber_form()
	 *
	 * @param array $args
	 * @param array $instance
	 * @return string form
	 **/

	function get_aweber_form($args, $instance) {
		extract($args, EXTR_SKIP);
		extract($instance, EXTR_SKIP);
		
		if ( $your_name ) {
			$your_name = esc_attr($your_name);
			$name_field = <<<EOS
<input type="text" class="name" name="name" value="$your_name" title="$your_name" /><br />
EOS;
		} else {
			$name_field = '';
		}
		
		$your_email = esc_attr($your_email);
		$email_field = <<<EOS
<input type="text" class="from" name="from" value="$your_email" title="$your_email" />
EOS;
		
		$sign_up = esc_attr($sign_up);
		$sign_up_field = <<<EOS
<input type="submit" class="button submit" name="submit" value="$sign_up" />
EOS;
		
		if ( $teaser ) {
			$teaser = '<div class="newsletter_teaser">'
				. apply_filters('widget_text', wpautop($teaser))
				. '</div>';
		}
		
		if ( $policy ) {
			$policy = '<div class="newsletter_policy">'
				. apply_filters('widget_text', wpautop($policy))
				. '</div>';
		}
		
		if ( $name_field || preg_match("/\bsidebar\b/", $id) && !preg_match("/\b(?:top|bottom|wide)\b/", $id) ) {
			$fields = <<<EOS
<p class="newsletter_fields newsletter_block">
$name_field
$email_field
</p>
<p class="newsletter_submit">
$sign_up_field
</p>
EOS;
		} else {
			$fields = <<<EOS
<p class="newsletter_fields newsletter_inline">
$email_field$sign_up_field
</p>
EOS;
		}
		
		$unit = explode('@', $email);
		$unit = esc_attr($unit[0]);
		
		if ( !$redirect ) {
			$redirect = ( is_ssl() ? 'https://' : 'http://' )
				. $_SERVER['HTTP_HOST']
				. $_SERVER['REQUEST_URI'];
			$redirect = rtrim($redirect, '?&');
			if ( strpos($redirect, '?') !== false ) {
				$redirect = preg_replace("/(\?.*)subscribed=\d*&?/i", "$1", $redirect);
				$redirect = rtrim($redirect, '?&') . '&';
			} else {
				$redirect .= '?';
			}
			$redirect .= 'subscribed=' . intval(end(explode('-', $widget_id)));
		}
		
		$redirect = esc_url($redirect);
		
		return <<<EOS
<form class="newsletter_manager" method="post" action="http://www.aweber.com/scripts/addlead.pl">
<input type="hidden" name="unit" value="$unit" />
<input type="hidden" name="meta_message" value="1" />
<input type="hidden" name="meta_required" value="from" />
<input type="hidden" name="redirect" value="$redirect" />
$teaser
$fields
$policy
</form>
EOS;
	} # get_aweber_form()
	
	
	/**
	 * get_default_form()
	 *
	 * @param string $widget_id
	 * @param array $instance
	 * @return string form
	 **/

	function get_default_form($args, $instance) {
		extract($args, EXTR_SKIP);
		extract($instance, EXTR_SKIP);
		
		if ( $your_name ) {
			$your_name = esc_attr($your_name);
			$name_field = <<<EOS
<input type="text" class="name" name="name" value="$your_name" title="$your_name" /><br />
EOS;
		} else {
			$name_field = '';
		}
		
		$your_email = esc_attr($your_email);
		$email_field = <<<EOS
<input type="text" class="from" name="from" value="$your_email" title="$your_email" />
EOS;
		
		$sign_up = esc_attr($sign_up);
		$sign_up_field = <<<EOS
<input type="submit" class="button submit" name="submit" value="$sign_up" />
EOS;
		
		if ( $teaser ) {
			$teaser = '<div class="newsletter_teaser">'
				. apply_filters('widget_text', wpautop($teaser))
				. '</div>';
		}
		
		if ( $policy ) {
			$policy = '<div class="newsletter_policy">'
				. apply_filters('widget_text', wpautop($policy))
				. '</div>';
		}
		
		
		if ( $name_field || preg_match("/\bsidebar\b/", $id) && !preg_match("/\b(?:top|bottom|wide)\b/i", $id) ) {
			$fields = <<<EOS
<p class="newsletter_fields newsletter_block">
$name_field
$email_field
</p>
<p class="newsletter_submit">
$sign_up_field
</p>
EOS;
		} else {
			$fields = <<<EOS
<p class="newsletter_fields newsletter_inline">
$email_field$sign_up_field
</p>
EOS;
		}
		
		$unit = intval(end(explode('-', $widget_id)));
		
		$action = ( is_ssl() ? 'https://' : 'http://' )
			. $_SERVER['HTTP_HOST']
			. $_SERVER['REQUEST_URI'];
		$action = rtrim($action, '?&');
		if ( strpos($action, '?') !== false ) {
			$action = preg_replace("/(\?.*)subscribed=\d*&?/i", "$1", $action);
			$action = rtrim($action, '?&');
		}
		
		$action = esc_url($action);
		
		return <<<EOS
<form class="newsletter_manager" method="post" action="$action">
<input type="hidden" name="newsletter_widget" value="$unit" />
$teaser
$fields
$policy
</form>
EOS;
	} # get_default_form()
	
	
	/**
	 * update()
	 *
	 * @param array $new_instance
	 * @param array $old_instance
	 * @return array $instance
	 **/

	function update($new_instance, $old_instance) {
		$instance = array();
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['email'] = is_email($new_instance['email'])
			? $new_instance['email']
			: '';
		$instance['redirect'] = $new_instance['redirect']
			? esc_url_raw($new_instance['redirect'])
			: '';
		$instance['teaser'] = current_user_can('unfiltered_html')
			? $new_instance['teaser']
			: $old_instance['teaser'];
		$instance['policy'] = current_user_can('unfiltered_html')
			? $new_instance['policy']
			: $old_instance['policy'];
		$instance['thank_you'] = current_user_can('unfiltered_html')
			? $new_instance['thank_you']
			: $old_instance['thank_you'];
		$instance['your_name'] = strip_tags($new_instance['your_name']);
		$instance['your_email'] = strip_tags($new_instance['your_email']);
		$instance['sign_up'] = strip_tags($new_instance['sign_up']);
		
		$defaults = newsletter_manager::defaults();
		if ( !$instance['your_email'] )
			$instance['your_email'] = $defaults['your_email'];
		if ( !$instance['sign_up'] )
			$instance['sign_up'] = $defaults['sign_up'];
		if ( !$instance['redirect'] && !$instance['thank_you'] )
			$instance['thank_you'] = $defaults['thank_you'];
		
		if ( $instance['email'] ) {
			if ( preg_match("/@aweber\.com$/i", $instance['email']) )
				$instance['syntax'] = 'aweber';
			elseif ( preg_match("/@(?:getresponse|1shoppingcart)\.com$/", $instance['email']) )
				$instance['syntax'] = 'list';
			else
				$instance['syntax'] = 'list-subscribe';
		} else {
			$instance['syntax'] = false;
		}
		
		return $instance;
	} # update()
	
	
	/**
	 * form()
	 *
	 * @param $instance
	 * @return void
	 **/

	function form($instance) {
		$instance = wp_parse_args($instance, newsletter_manager::defaults());
		extract($instance, EXTR_SKIP);
		
		echo '<p>'
			. '<label>'
			. __('Title:', 'newsletter-manager')
			. '<br />' . "\n"
			. '<input type="text" class="widefat"'
				. ' id="' . $this->get_field_id('title') . '"'
				. ' name="' . $this->get_field_name('title') . '"'
				. ' value="' . esc_attr($title) . '"'
				. ' />'
			. '</label>'
			. '</p>' . "\n";
		
		echo '<p>'
			. '<label>'
			. __('Mailing List, e.g. <code>mylist@aweber.com</code>:', 'newsletter-manager')
			. '<br />' . "\n"
			. '<input type="text" class="widefat"'
				. ' name="' . $this->get_field_name('email') . '"'
				. ' value="' . esc_attr($email) . '"'
				. ' />'
			. '</label>'
			. '<br />' . "\n"
			. sprintf(__('(Works with <a href="%1$s" onclick="window.open(this.href); return false;">AWeber</a> (recommended), <a href="%2$s" onclick="window.open(this.href); return false;">GetResponse</a> and <a href="%3$s" onclick="window.open(this.href); return false;">1ShoppingCart</a>.)', 'newsletter-manager'), 'http://go.semiologic.com/aweber', 'http://go.semiologic.com/getresponse', 'http://go.semiologic.com/1shoppingcart')
			. '</p>' . "\n";
		
		echo '<p>'
			. '<label for="' . $this->get_field_id('teaser') . '">'
			. __('Teaser Message:', 'newsletter-manager')
			. '</label>'
			. '<br />' . "\n"
			. '<textarea class="widefat" rows="2" id="' . $this->get_field_id('teaser') . '"'
				. ' name="' . $this->get_field_name('teaser') . '"'
				. ' >'
				. esc_html($teaser)
			. '</textarea>'
			. '</p>' . "\n";
		
		echo '<p>'
			. '<label for="' . $this->get_field_id('your_name') . '">'
			. __('Form Labels (<code>Your Name</code>, <code>Your Email</code>, <code>Sign Up</code>):', 'newsletter-manager')
			. '</label>'
			. '<br />' . "\n"
			. '<input type="text" class="widefat" style="width: 33.33%;"'
				. ' name="' . $this->get_field_name('your_name') . '"'
				. ' value="' . esc_attr($your_name) . '"'
				. ' />'
			. '<input type="text" class="widefat" style="width: 33.33%;"'
				. ' name="' . $this->get_field_name('your_email') . '"'
				. ' value="' . esc_attr($your_email) . '"'
				. ' />'
			. '<input type="text" class="widefat" style="width: 33.34%;"'
				. ' name="' . $this->get_field_name('sign_up') . '"'
				. ' value="' . esc_attr($sign_up) . '"'
				. ' />'
			. '</p>' . "\n";
		
		echo '<p>'
			. '<label for="' . $this->get_field_id('policy') . '">'
			. __('Privacy Policy:', 'newsletter-manager')
			. '</label>'
			. '<br />' . "\n"
			. '<textarea class="widefat" rows="2" id="' . $this->get_field_id('policy') . '"'
				. ' name="' . $this->get_field_name('policy') . '"'
				. ' >'
				. esc_html($policy)
			. '</textarea>'
			. '</p>' . "\n";
		
		echo '<p>'
			. '<label>'
			. __('Thank You Page URL, e.g. <code>http://domain.com/thank-you/</code>:', 'newsletter-manager')
			. '<br />' . "\n"
			. '<input type="text" class="widefat"'
				. ' name="' . $this->get_field_name('redirect') . '"'
				. ' value="' . esc_attr($redirect) . '"'
				. ' />'
			. '</label>'
			. '</p>' . "\n";
		
		echo '<p>'
			. '<label for="' . $this->get_field_id('thank_you') . '">'
			. __('Thank You Message (used if no thank you page is configured):', 'newsletter-manager')
			. '</label>'
			. '<br />' . "\n"
			. '<textarea class="widefat" rows="2" id="' . $this->get_field_id('thank_you') . '"'
				. ' name="' . $this->get_field_name('thank_you') . '"'
				. ' >'
				. esc_html($thank_you)
			. '</textarea>'
			. '</p>' . "\n";
	} # form()
	
	
	/**
	 * subscribe()
	 *
	 * @return void
	 **/

	function subscribe() {
		if ( !$_POST || !isset($_POST['newsletter_widget']) )
			return;
		
		$num = (int) $_POST['newsletter_widget'];
		$ops = get_option('widget_newsletter_widget');
		
		if ( !isset($ops[$num]) )
			return;
		
		extract($ops[$num], EXTR_SKIP);
		
		$name = '';
		$from = $_POST['from'];
		
		if ( !is_email($email) || !is_email($from) )
			return;
		
		if ( !empty($_POST['name']) ) {
			$name = strip_tags(stripslashes($_POST['name']));
			$name = preg_replace("/[^\w ]+/", " ", $name);
		}
		
		$to = ( $syntax == 'list-subscribe' )
			? str_replace('@', '-subscribe@', $email)
			: $email;
		
		if ( $name ) {
			$headers = 'From: "' . $name . '" <' . $from . '>';
		} else {
			$headers = 'From: ' . $from;
		}
		
		$title = 'subscribe';
		$message = 'subscribe';
		
		if ( !$redirect ) {
			$redirect = ( is_ssl() ? 'https://' : 'http://' )
				. $_SERVER['HTTP_HOST']
				. $_SERVER['REQUEST_URI'];
		}
		
		$redirect = rtrim($redirect, '?&');
		if ( strpos($redirect, '?') !== false ) {
			$redirect = preg_replace("/(\?.*)subscribed=\d*&?/i", "$1", $redirect);
			$redirect = rtrim($redirect, '?&') . '&';
		} else {
			$redirect .= '?';
		}
		$redirect .= 'subscribed=' . $num;
		
		if ( !sem_newsletter_widget_debug ) {
			wp_mail($to, $title, $message, $headers);
			wp_redirect($redirect);
		} else {
			$redirect = '<a href="' . esc_url($redirect) . '">' . $redirect . '</a>';
			dump($to, $title, $message, $headers, $redirect);
		}
		
		die;
	} # subscribe()
	
	
	/**
	 * defaults()
	 *
	 * @return array $options
	 **/

	function defaults() {
		return array(
			'title' => __('Newsletter', 'newsletter-manager'),
			'email' => '',
			'redirect' => '',
			'teaser' => __('Sign up to receive an occasional newsletter with insider tips and irresistible offers.', 'newsletter-manager'),
			'policy' => '',
			'thank_you' => __('Thank you for subscribing! You\'ll receive a confirmation email in a few moments.', 'newsletter-manager'),
			'your_name' => '',
			'your_email' => __('Your Email', 'newsletter-manager'),
			'sign_up' => __('Sign Up', 'newsletter-manager'),
			);
	} # defaults()
	
	
	/**
	 * upgrade()
	 *
	 * @param array $ops
	 * @return array $ops
	 **/

	function upgrade($ops) {
		$widget_contexts = class_exists('widget_contexts')
			? get_option('widget_contexts')
			: false;
		
		unset($ops['version']);
		
		foreach ( $ops as $k => $o ) {
			$ops[$k] = array(
				'title' => $o['captions']['widget_title'],
				'email' => $o['email'],
				'syntax' => $o['syntax'],
				'redirect' => $o['redirect'],
				'teaser' => $o['captions']['widget_teaser'],
				'thank_you' => $o['captions']['thank_you'],
				'your_name' => $o['captions']['your_name'],
				'your_email' => $o['captions']['your_email'],
				'sign_up' => $o['captions']['sign_up'],
				);
			if ( isset($widget_contexts['newsletter_widget-' . $k]) ) {
				$ops[$k]['widget_contexts'] = $widget_contexts['newsletter_widget-' . $k];
			}
		}
		
		return $ops;
	} # upgrade()
	
	
	/**
	 * upgrade_3x()
	 *
	 * @param array $ops
	 * @return array $ops
	 **/

	function upgrade_3x($ops) {
		if ( defined('DOING_CRON') )
			return array();
		
		extract($ops, EXTR_SKIP);
		if ( !empty($captions) )
			extract($captions, EXTR_SKIP);
		
		$ops = array();
		if ( $widget_title )
			$ops['title'] = $widget_title;
		$ops['email'] = $email;
		$ops['syntax'] = $syntax;
		$ops['thank_you'] = $thank_you;
		$ops['your_name'] = $your_name;
		$ops['your_email'] = $your_email;
		$ops['sign_up'] = $sign_up;
		$ops['redirect'] = $redirect;
		
		$ops = array(
			2 => $ops,
			3 => $ops,
			);
		
		$ops[2]['teaser'] = $widget_teaser;
		$ops[3]['teaser'] = '';
		
		if ( is_admin() ) {
			$sidebars_widgets = get_option('sidebars_widgets', array('array_version' => 3));
		} else {
			global $_wp_sidebars_widgets;
			if ( !$_wp_sidebars_widgets )
				$_wp_sidebars_widgets = get_option('sidebars_widgets', array('array_version' => 3));
			$sidebars_widgets = get_option('sidebars_widgets', array('array_version' => 3));
		}
		
		foreach ( $sidebars_widgets as $sidebar => $widgets ) {
			if ( !is_array($widgets) )
				continue;
			$widgets = array_map('sanitize_title', $widgets);
			$key = array_search('newsletter', $widgets);
			if ( $key !== false ) {
				$sidebars_widgets[$sidebar][$key] = 'newsletter_widget-2';
				if ( $_wp_sidebars_widgets )
					$_wp_sidebars_widgets[$sidebar][$key] = 'newsletter_widget-2';
				break;
			}
		}
		
		if ( !in_array('newsletter_widget-3', (array) $sidebars_widgets['inline_widgets']) ) {
			$sidebars_widgets['inline_widgets'][] = 'newsletter_widget-3';
			if ( $_wp_sidebars_widgets )
				$_wp_sidebars_widgets['inline_widgets'] = 'newsletter_widget-3';
		}
		
		global $wpdb;
		
		$wpdb->query("
			UPDATE	$wpdb->posts
			SET 	post_content = replace(post_content, '<!--newsletter-->', '[widget id=\"newsletter_widget-3\"/]')
			WHERE	post_content LIKE '%<!--newsletter-->%'
			");
		
		update_option('widget_newsletter_widget', $ops);
		update_option('sidebars_widgets', $sidebars_widgets);
		
		return $ops;
	} # upgrade_3x()
	
	
	/**
	 * upgrade_2x()
	 *
	 * @param array $ops
	 * @return array $ops
	 **/

	function upgrade_2x($ops) {
		if ( defined('DOING_CRON') )
			return array();
		
		extract($ops, EXTR_SKIP);
		
		$ops = array();
		if ( $title )
			$ops['title'] = $title;
		$ops['email'] = $email;
		if ( preg_match("/@aweber\.com$/i", $ops['email']) )
			$ops['syntax'] = 'aweber';
		elseif ( preg_match("/@(?:getresponse|1shoppingcart)\.com$/", $ops['email']) )
			$ops['syntax'] = 'list';
		else
			$ops['syntax'] = 'list-subscribe';
		$ops['thank_you'] = $thanks;
		
		$ops = array(
			2 => $ops,
			3 => $ops,
			);
		
		$ops[2]['teaser'] = $teaser;
		$ops[3]['teaser'] = '';
		
		if ( is_admin() ) {
			$sidebars_widgets = get_option('sidebars_widgets', array('array_version' => 3));
		} else {
			global $_wp_sidebars_widgets;
			if ( !$_wp_sidebars_widgets )
				$_wp_sidebars_widgets = get_option('sidebars_widgets', array('array_version' => 3));
			$sidebars_widgets = get_option('sidebars_widgets', array('array_version' => 3));
		}
		
		foreach ( $sidebars_widgets as $sidebar => $widgets ) {
			if ( !is_array($widgets) )
				continue;
			$widgets = array_map('sanitize_title', $widgets);
			$key = array_search('newsletter', $widgets);
			if ( $key !== false ) {
				$sidebars_widgets[$sidebar][$key] = 'newsletter_widget-2';
				if ( $_wp_sidebars_widgets )
					$_wp_sidebars_widgets[$sidebar][$key] = 'newsletter_widget-2';
				break;
			}
		}
		
		if ( !in_array('newsletter_widget-3', (array) $sidebars_widgets['inline_widgets']) ) {
			$sidebars_widgets['inline_widgets'][] = 'newsletter_widget-3';
			if ( $_wp_sidebars_widgets )
				$_wp_sidebars_widgets['inline_widgets'] = 'newsletter_widget-3';
		}
		
		global $wpdb;
		
		$wpdb->query("
			UPDATE	$wpdb->posts
			SET 	post_content = replace(post_content, '<!--newsletter-->', '[widget id=\"newsletter_widget-3\"/]')
			WHERE	post_content LIKE '%<!--newsletter-->%'
			");
		
		update_option('widget_newsletter_widget', $ops);
		update_option('sidebars_widgets', $sidebars_widgets);
		
		return $ops;
	} # upgrade_2x()
} # newsletter_manager
?>