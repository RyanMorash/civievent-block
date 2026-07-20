<?php
/*
Plugin Name: Events Listing Block for CiviCRM
Plugin URI: https://github.com/ryanmorash/civievent-block
Description: Display public CiviCRM events with a dynamic Gutenberg block.
Version: 1.0.1
Author: Ryan Morash
Author URI: https://github.com/ryanmorash
Requires at least: 6.6
Requires PHP: 7.4
License: GPL-3.0-or-later
License URI: https://www.gnu.org/licenses/gpl-3.0.html
Text Domain: civievent-block
*/

namespace CiviEvent_Block;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CIVIEVENT_BLOCK_VERSION', '1.0.1' );
define( 'CIVIEVENT_BLOCK_PATH', plugin_dir_path( __FILE__ ) );

require_once CIVIEVENT_BLOCK_PATH . 'includes/class-renderer.php';
require_once CIVIEVENT_BLOCK_PATH . 'includes/class-plugin.php';

add_action( 'plugins_loaded', array( Plugin::class, 'boot' ) );
