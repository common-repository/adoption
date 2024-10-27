=== Plugin Name ===
Contributors: aprea
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=chris%2eaprea%40gmail%2ecom&lc=AU&item_name=Chris%20Aprea&currency_code=AUD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted
Tags: user, users, statistics, registration, dashboard, widget, charts, graphs
Requires at least: 3.4
Tested up to: 3.4.1
Stable tag: 0.1.1
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Adoption displays user registration statistics in a neat dashboard widget

== Description ==

**This plugin requires PHP 5.3 or later, please check with your web host if you're unsure.**

Adoption uses the timezone that is set in the WordPres admin panel. Ensure you have set your local timezone in Settings > General.

Features:

* Displays a bar chart with registration statistics for predetermined time periods (e.g. this week, last week, last 3 months, last 12 months, etc.)
* Displays a "Quick Statistics" section which includes a summary of user registrations for popular time periods.
* Easy to use, tidy and fits in with the overall WordPress user interface.

== Installation ==

1. Upload entire `adoption` folder to the `/wp-content/plugins/` directory
2. Activate the Adoption plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= Who has access to view the Adoption dashboard widget? =

Only administrations by default. Additional roles can be configured to gain access by using Justin Tadlocks "Members" plugin, you can [grab it here](http://wordpress.org/extend/plugins/members/).

= The month views seem a little off, when I select any given month view I'm given an extra month, why is that? =

This is a design decision. If you select "Last 3 Months" you're given stats for this month and the previous 3 month (4 in total).

If you'd like to strictly get the last X months for any given time period you can use this code:

`function your_project_minus_one_month(){

	return 1;

}

add_filter( 'adoption_minus_one_month', 'your_project_minus_one_month' );`

== Screenshots ==

1. The Adoption dashboard widget showing statistics for the last 6 months, April is highlighted.

== Changelog ==

= 0.1.1 =
* Code clean up.

= 0.1 =
* Initial release.
