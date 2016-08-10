=== Berkeley Widgets ===
Contributors: sillybean
Author: Stephanie Leary
Author URI: http://stephanieleary.com
GitHub Plugin URI: https://github.com/sillybean/berkeley-widgets
Description: Custom post types and taxonomies, plus various helper filters and Dashboard widget.
License: GPL2
Text domain: berkeley-widgets
Requires at least: 3.1
Tested up to: 4.6
Stable tag: 1.4.1

== Description ==

Creates custom widgets for Berkeley Engineering sites:

* Calendar feeds (RSS and XML) for Berkeley Events
* Custom menu dropdown widget, for use in the site header
* Simple taxonomy list widget

== Installation ==

1. Upload the plugin directory to `/wp-content/plugins/` 
1. Activate the plugin through the 'Plugins' menu in WordPress

== Changelog ==

= 1.4.1 =
* Use unique text domain
* Add a few more text domains
* Prefix shared taxonomy functions
= 1.4 =
* Posts by Term widget query rewrite
* Added text domain throughout
* Prefixed ajaxurl var and widget_arg filter
* removed bad 'break'
* Check for existence of functions in content model plugin
* Check for $event object in calendar feed before trying to fetch GUID and title
* Check for $instance['display'] before trying to display
* Widget form defaults to prevent index warnings
* Escaping throughout
* (int) -> absint()
= 1.3.4 =
* Added form label to dropdown menu for accessibility.
= 1.3.3 =
* Separate opening list tag in calendar feed in case title is not shown.
= 1.3.2 =
* Menu select option tag fix
* Moved helper functions to appropriate files
= 1.2 =
* Improved taxonomy list widget
= 1.1 =
* Added taxonomy list widget
= 1.0 =
* Moved widgets in from COE theme