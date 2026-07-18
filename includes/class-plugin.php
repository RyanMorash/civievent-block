<?php
/**
 * Plugin bootstrap and WordPress integrations.
 *
 * @package CiviEventBlock
 */

namespace CiviEvent_Block;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the block and its editor-only API endpoint.
 */
final class Plugin {

	/**
	 * Attach WordPress hooks.
	 *
	 * @return void
	 */
	public static function boot() {
		add_action( 'init', array( self::class, 'register_block' ) );
		add_action( 'rest_api_init', array( self::class, 'register_rest_routes' ) );
	}

	/**
	 * Register the block from its metadata.
	 *
	 * @return void
	 */
	public static function register_block() {
		register_block_type_from_metadata( CIVIEVENT_BLOCK_PATH . 'blocks/events' );
	}

	/**
	 * Register routes used by block editor controls.
	 *
	 * @return void
	 */
	public static function register_rest_routes() {
		register_rest_route(
			'civievent-block/v1',
			'/event-types',
			array(
				'methods'             => 'GET',
				'callback'            => array( self::class, 'get_event_types' ),
				'permission_callback' => static function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}

	/**
	 * Return CiviCRM event types for the editor select control.
	 *
	 * @param WP_REST_Request $request Current request (unused).
	 * @return WP_REST_Response|WP_Error
	 */
	public static function get_event_types( WP_REST_Request $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		$initialized = Renderer::initialize_civicrm();

		if ( is_wp_error( $initialized ) ) {
			return $initialized;
		}

		try {
			$result = civicrm_api3(
				'Event',
				'getoptions',
				array(
					'field'   => 'event_type_id',
					'context' => 'search',
				)
			);
		} catch ( \Throwable $error ) {
			Renderer::log_error( $error );

			return new WP_Error(
				'civievent_block_event_types_failed',
				__( 'CiviCRM event types could not be loaded.', 'civievent-block' ),
				array( 'status' => 503 )
			);
		}

		$options = array();

		if ( ! empty( $result['values'] ) && is_array( $result['values'] ) ) {
			foreach ( $result['values'] as $value => $label ) {
				$options[] = array(
					'value' => absint( $value ),
					'label' => sanitize_text_field( (string) $label ),
				);
			}
		}

		return new WP_REST_Response( $options );
	}
}
