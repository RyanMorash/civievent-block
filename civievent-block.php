<?php
/*
Plugin Name: CiviEvent Block
Description: Display public CiviCRM events with a dynamic Gutenberg block.
Version: 0.1.0
Requires at least: 6.6
Requires PHP: 7.4
Requires Plugins: civicrm
License: GPL-3.0-or-later
License URI: https://www.gnu.org/licenses/gpl-3.0.html
Text Domain: civievent-block
*/

namespace CiviEvent_Block;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CIVIEVENT_BLOCK_VERSION', '0.1.0' );
define( 'CIVIEVENT_BLOCK_PATH', plugin_dir_path( __FILE__ ) );

require_once CIVIEVENT_BLOCK_PATH . 'includes/class-renderer.php';
require_once CIVIEVENT_BLOCK_PATH . 'includes/class-plugin.php';

add_action( 'plugins_loaded', array( Plugin::class, 'boot' ) );
