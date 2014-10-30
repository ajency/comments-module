<?php
/**
 * Comments Module Plugin
 *
 * A simple Comments Module Plugin for wordpress
 *
 * @package   comments-module
 * @author    Team Ajency <talktous@ajency.in>
 * @license   GPL-2.0+
 * @link      http://ajency.in
 * @copyright 10-22-2014 Ajency.in
 *
 * @wordpress-plugin
 * Plugin Name: Comments Module
 * Plugin URI:  http://ajency.in
 * Description: A simple Comments Module Plugin for wordpress
 * Version:     0.1.0
 * Author:      Team Ajency
 * Author URI:  http://ajency.in
 * Text Domain: comments-module-locale
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path: /lang
 */

// If this file is called directly, abort.
if (!defined("WPINC")) {
	die;
}

// include the register comment objects functions file
require_once( plugin_dir_path( __FILE__ ) . '/include/register_comments_objects.php');

// include the custom plugin functions file
require_once( plugin_dir_path( __FILE__ ) . '/include/functions.php');

require_once(plugin_dir_path(__FILE__) . "CommentsModule.php");

// Register hooks that are fired when the plugin is activated, deactivated, and uninstalled, respectively.
register_activation_hook(__FILE__, array("CommentsModule", "activate"));
register_deactivation_hook(__FILE__, array("CommentsModule", "deactivate"));

function aj_commentsmodule() {
	return CommentsModule::get_instance();
}

// add the document management to globals
$GLOBALS['aj_commentsmodule'] = aj_commentsmodule();
