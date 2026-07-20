<?php
/**
 * Verify that the plugin remains safe and informative without CiviCRM.
 *
 * Run with: php tests/dependency-smoke.php
 */

define( 'ABSPATH', __DIR__ );

$civievent_block_can_edit = true;

class WP_Error {
	private $code;
	private $message;

	public function __construct( $code, $message ) {
		$this->code    = $code;
		$this->message = $message;
	}

	public function get_error_code() {
		return $this->code;
	}

	public function get_error_message() {
		return $this->message;
	}
}

function plugin_dir_path( $file ) {
	return dirname( $file ) . '/';
}

function add_action() {
	return true;
}

function __( $text ) {
	return $text;
}

function esc_html( $text ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
}

function esc_url( $url ) {
	return $url;
}

function wp_kses_post( $html ) {
	return $html;
}

function wp_strip_all_tags( $text ) {
	return strip_tags( (string) $text );
}

function sanitize_text_field( $text ) {
	return trim( strip_tags( (string) $text ) );
}

function absint( $value ) {
	return abs( (int) $value );
}

function wp_parse_args( $args, $defaults ) {
	return array_merge( $defaults, $args );
}

function get_block_wrapper_attributes( $attributes ) {
	return 'class="' . esc_html( $attributes['class'] ) . '"';
}

function current_user_can( $capability ) {
	global $civievent_block_can_edit;

	return 'edit_posts' === $capability ? $civievent_block_can_edit : true;
}

function is_wp_error( $value ) {
	return $value instanceof WP_Error;
}

function get_current_screen() {
	return (object) array( 'id' => 'plugins' );
}

require dirname( __DIR__ ) . '/civievent-block.php';

if ( \CiviEvent_Block\Renderer::is_civicrm_available() ) {
	fwrite( STDERR, 'CiviCRM was unexpectedly reported as available.' . PHP_EOL );
	exit( 1 );
}

$initialized = \CiviEvent_Block\Renderer::initialize_civicrm();
if ( ! is_wp_error( $initialized ) || 'civievent_block_civicrm_missing' !== $initialized->get_error_code() ) {
	fwrite( STDERR, 'Missing CiviCRM did not return the expected error.' . PHP_EOL );
	exit( 1 );
}

$editor_output = \CiviEvent_Block\Renderer::render( array() );
if ( false === strpos( $editor_output, 'CiviCRM must be active to display events.' ) ) {
	fwrite( STDERR, 'The editor did not receive dependency guidance.' . PHP_EOL );
	exit( 1 );
}

$civievent_block_can_edit = false;
if ( '' !== \CiviEvent_Block\Renderer::render( array() ) ) {
	fwrite( STDERR, 'The public renderer exposed output without CiviCRM.' . PHP_EOL );
	exit( 1 );
}

ob_start();
\CiviEvent_Block\Plugin::render_civicrm_notice();
$notice = ob_get_clean();

if ( false === strpos( $notice, 'CiviCRM installation guide' ) ) {
	fwrite( STDERR, 'The Plugins screen notice was not rendered.' . PHP_EOL );
	exit( 1 );
}

echo 'Missing dependency smoke test passed.' . PHP_EOL;
