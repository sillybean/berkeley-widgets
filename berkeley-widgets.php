<?php
/*
Plugin Name: Berkeley Engineering Widgets
Description: Creates custom widgets for the Berkeley Engineering sites.
Author: Stephanie Leary
Version: 1.1
Author URI: http://stephanieleary.com
Text Domain: beng
*/

// initialize all custom widgets
function berkeley_widgets_init() {
	register_widget( 'WP_Nav_Menu_Dropdown_Widget' );
	register_widget( 'Berkeley_Calendar_XML_Widget' );
	register_widget( 'Berkeley_Taxonomy_List_Widget' );
}

add_action( 'widgets_init', 'berkeley_widgets_init' );

include( 'inc/calendar-feed.php' );
include( 'inc/menu-select.php' );
include( 'inc/taxonomy-list.php' );