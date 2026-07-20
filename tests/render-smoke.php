<?php
/**
 * Minimal build-free smoke test for the dynamic renderer.
 *
 * Run with: php tests/render-smoke.php
 */

define( 'ABSPATH', __DIR__ );
define( 'WP_DEBUG', true );

class WP_Error {
	private $code;
	private $message;
	private $data;

	public function __construct( $code, $message, $data = array() ) {
		$this->code    = $code;
		$this->message = $message;
		$this->data    = $data;
	}

	public function get_error_message() {
		return $this->message;
	}
}

class CRM_Utils_System {
	public static function url( $path, $query ) {
		return 'https://example.test/' . $path . '?' . $query;
	}
}

function __( $text ) {
	return $text;
}

function esc_html__( $text ) {
	return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
}

function esc_html( $text ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
}

function esc_attr( $text ) {
	return esc_html( $text );
}

function esc_url( $url ) {
	return esc_attr( $url );
}

function sanitize_text_field( $text ) {
	return trim( strip_tags( (string) $text ) );
}

function wp_strip_all_tags( $text ) {
	return strip_tags( (string) $text );
}

function wp_kses_post( $html ) {
	return strip_tags( (string) $html, '<p><strong><em><a>' );
}

function absint( $value ) {
	return abs( (int) $value );
}

function wp_parse_args( $args, $defaults ) {
	return array_merge( $defaults, $args );
}

function current_time( $format ) {
	return '2026-07-18';
}

function wp_timezone() {
	return new DateTimeZone( 'America/New_York' );
}

function wp_date( $format, $timestamp, $timezone ) {
	return ( new DateTimeImmutable( '@' . $timestamp ) )->setTimezone( $timezone )->format( $format );
}

function get_option( $option, $default = false ) {
	$values = array(
		'date_format' => 'F j, Y',
		'time_format' => 'g:i a',
	);

	return isset( $values[ $option ] ) ? $values[ $option ] : $default;
}

function get_block_wrapper_attributes( $attributes ) {
	return 'class="wp-block-civievent-block-events ' . esc_attr( $attributes['class'] ) . '"';
}

function apply_filters( $hook, $value ) {
	return $value;
}

function current_user_can() {
	return true;
}

function is_wp_error( $value ) {
	return $value instanceof WP_Error;
}

function civicrm_initialize() {
	return true;
}

function civicrm_api3( $entity, $action, $params ) {
	if ( 'Event' === $entity && 'get' === $action ) {
		return array(
			'values' => array(
				array(
					'id'                      => 42,
					'title'                   => 'Community Workshop',
					'summary'                 => '<p>Learn <strong>together</strong>.</p>',
					'start_date'              => '2026-08-02 18:00:00',
					'end_date'                => '2026-08-02 20:00:00',
					'is_online_registration'  => 1,
					'registration_link_text'  => 'Save your seat',
					'is_show_location'        => 1,
					'loc_block_id'             => 12,
				)
			),
		);
	}

	if ( 'LocBlock' === $entity && 'getsingle' === $action ) {
		return array( 'address_id' => 21 );
	}

	if ( 'Address' === $entity && 'getsingle' === $action ) {
		return array(
			'city'                           => 'Troy',
			'state_province_id.abbreviation' => 'NY',
		);
	}

	throw new RuntimeException( 'Unexpected API call.' );
}

require dirname( __DIR__ ) . '/includes/class-renderer.php';

$html = \CiviEvent_Block\Renderer::render(
	array(
		'showSummary'  => true,
		'showCity'     => true,
		'stateDisplay' => 'abbreviate',
	)
);

$expectations = array(
	'wp-block-civievent-block-events',
	'Community Workshop',
	'August 2, 2026 6:00 pm',
	'8:00 pm',
	'Troy, NY',
	'Learn <strong>together</strong>.',
	'Save your seat',
);

foreach ( $expectations as $expectation ) {
	if ( false === strpos( $html, $expectation ) ) {
		fwrite( STDERR, 'Missing expected output: ' . $expectation . PHP_EOL );
		fwrite( STDERR, $html . PHP_EOL );
		exit( 1 );
	}
}

echo 'Renderer smoke test passed.' . PHP_EOL;
