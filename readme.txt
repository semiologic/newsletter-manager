=== Newsletter Manager ===
Contributors: Denis-de-Bernardy, Mike_Koepke
Donate link: https://www.semiologic.com/donate/
Tags: semiologic
Requires at least: 2.8
Tested up to: 4.0
Stable tag: trunk

A widget-driven newsletter form manager.


== Description ==


> *This plugin has been retired.  No further development will occur on it.*

The Newsletter Manager plugin for WordPress allows you to manage newsletter subscription forms on your site.

It is widget-driven, and plays best with widget-driven themes such as the Semiologic theme, especially when combined with the Inline Widgets plugin.

The plugin plays well with aweber, getresponse, 1shoppingcart, majordomo, and other list managers that can service a list-subscribe@domain.com email.

= Placing a Subscription Form in a panel/in a sidebar =

It's short and simple:

1. Browse Appearance / Widgets
2. Open the panel of your choice (or sidebar, if not using the Semiologic theme)
3. Place a "Newsletter Widget" in that panel/sidebar
4. Configure that newsletter widget as needed

Usually, no configuration will be required beyond entering the email of your list.

Common places to insert a form automatically include:

- To the top/middle right of your site in a sidebar. Users commonly swipe their mouse to the top right corner of their screen, and eyeballs generally look for it in that area once they're done reading.
- After your post's content ("Entry: Content" widget in the "Each Entry" panel), provided of course that your content is read to the very end.
- After all posts ("After The Entries" panel.)

= Embedding a subscription form in a static page =

You'll frequently want a subscription form directly in the content of key pages on your site:

1. Open the Inline Widgets panel, under Appearance / Widgets
2. Place and configure a Newsletter Widget
3. Create or edit your pages as needed; note the "Widgets" drop down menu
4. Select your newly configured newsletter widget in the "Widgets" drop down menu to insert it where your mouse cursor is at

= Google Analytics integration =

Combining this plugin with the Google Analytics (GA) plugin adds an interesting bonus. Specifically, form subscription usage gets tracked as page events. In addition, newly subscribed users are segmented automatically.

= Help Me! =

The [Semiologic Support Page](https://www.semiologic.com/support/) is the best place to report issues.


== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress


== Change Log ==

= 5.3.1 =

- Development has ceased on this plugin.  Updated source and readme accordingly
- Updated to use PHP5 constructors as WP deprecated PHP4 constructor type in 4.3.
- WP 4.3 compat
- Tested against PHP 5.6


= 5.3 =

- WP 4.0 compat

= 5.2 =

- Use minified javascript file
- Code refactoring
- WP 3.9 compat

= 5.1.1 =

- WP 3.8 compat

= 5.1 =

- WP 3.6 compat
- PHP 5.4 compat

= 5.0.1 =

- WP 3.5 compat
- Added default text for typical Name field

= 2.0.1 =

- Pot file tweak

= 2.0 =

- Complete rewrite
- WP_Widget class
- Localization
- Code enhancements and optimizations
