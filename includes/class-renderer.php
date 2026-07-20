<?php
/**
 * CiviCRM event query and block rendering.
 *
 * @package CiviEventBlock
 */

namespace CiviEvent_Block;

use DateTimeImmutable;
use Throwable;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Queries CiviCRM and renders event markup.
 */
final class Renderer {

	/**
	 * Whether CiviCRM initialization has already succeeded.
	 *
	 * @var bool
	 */
	private static $civicrm_initialized = false;

	/**
	 * Determine whether the WordPress integration for CiviCRM is active.
	 *
	 * @return bool
	 */
	public static function is_civicrm_available() {
		return function_exists( 'civicrm_initialize' );
	}

	/**
	 * Render the dynamic block.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @return string
	 */
	public static function render( array $attributes ) {
		$attributes = self::normalize_attributes( $attributes );
		$wrapper    = get_block_wrapper_attributes(
			array(
				'class' => 'civievent-block civievent-block--' . $attributes['displayMode'],
			)
		);

		$initialized = self::initialize_civicrm();

		if ( is_wp_error( $initialized ) ) {
			return self::render_editor_error( $wrapper, $initialized->get_error_message() );
		}

		$events = self::get_events( $attributes );

		if ( is_wp_error( $events ) ) {
			return self::render_editor_error( $wrapper, $events->get_error_message() );
		}

		$heading = '';
		if ( '' !== $attributes['heading'] ) {
			$heading = sprintf(
				'<h%1$d class="civievent-block__heading">%2$s</h%1$d>',
				$attributes['headingLevel'],
				esc_html( $attributes['heading'] )
			);
		}

		if ( empty( $events ) ) {
			return sprintf(
				'<div %1$s>%2$s<p class="civievent-block__empty">%3$s</p></div>',
				$wrapper,
				$heading,
				esc_html( $attributes['emptyMessage'] )
			);
		}

		$event_markup = '';
		foreach ( $events as $event ) {
			$event_markup .= self::render_event( $event, $attributes );
		}

		$view_all = '';
		if ( $attributes['showViewAll'] ) {
			$view_all_url = self::get_civicrm_url( 'civicrm/event/ical', 'reset=1&list=1&html=1' );

			if ( '' !== $view_all_url ) {
				$view_all = sprintf(
					'<p class="civievent-block__view-all"><a href="%1$s">%2$s</a></p>',
					esc_url( $view_all_url ),
					esc_html__( 'View all events', 'civievent-block' )
				);
			}
		}

		return sprintf(
			'<div %1$s>%2$s<div class="civievent-block__events">%3$s</div>%4$s</div>',
			$wrapper,
			$heading,
			$event_markup,
			$view_all
		);
	}

	/**
	 * Initialize CiviCRM before using its API.
	 *
	 * @return true|WP_Error
	 */
	public static function initialize_civicrm() {
		if ( self::$civicrm_initialized && function_exists( 'civicrm_api3' ) ) {
			return true;
		}

		if ( ! self::is_civicrm_available() ) {
			return new WP_Error(
				'civievent_block_civicrm_missing',
				__( 'CiviCRM must be active to display events.', 'civievent-block' ),
				array( 'status' => 503 )
			);
		}

		try {
			civicrm_initialize();
		} catch ( Throwable $error ) {
			self::log_error( $error );

			return new WP_Error(
				'civievent_block_civicrm_initialization_failed',
				__( 'CiviCRM could not be initialized.', 'civievent-block' ),
				array( 'status' => 503 )
			);
		}

		if ( ! function_exists( 'civicrm_api3' ) ) {
			return new WP_Error(
				'civievent_block_api_missing',
				__( 'The CiviCRM API is unavailable.', 'civievent-block' ),
				array( 'status' => 503 )
			);
		}

		self::$civicrm_initialized = true;

		return true;
	}

	/**
	 * Log an API failure only when WordPress debugging is enabled.
	 *
	 * @param Throwable $error Error to log.
	 * @return void
	 */
	public static function log_error( Throwable $error ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'Events Listing Block for CiviCRM: ' . $error->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
	}

	/**
	 * Query public upcoming events.
	 *
	 * @param array<string, mixed> $attributes Normalized block attributes.
	 * @return array<int, array<string, mixed>>|WP_Error
	 */
	private static function get_events( array $attributes ) {
		$params = array(
			'sequential'  => 1,
			'is_active'   => 1,
			'is_public'   => 1,
			'is_template' => 0,
			'start_date'  => array( '>=' => current_time( 'Y-m-d' ) ),
			'options'     => array(
				'limit'  => 'single' === $attributes['displayMode'] ? 1 : $attributes['limit'],
				'offset' => 'single' === $attributes['displayMode'] ? $attributes['offset'] : 0,
				'sort'   => 'start_date ASC',
			),
			'return'      => array(
				'id',
				'title',
				'summary',
				'start_date',
				'end_date',
				'is_online_registration',
				'registration_start_date',
				'registration_end_date',
				'registration_link_text',
				'is_show_location',
				'loc_block_id',
			),
		);

		if ( $attributes['eventTypeId'] > 0 ) {
			$params['event_type_id'] = $attributes['eventTypeId'];
		}

		/**
		 * Filters CiviCRM API parameters used to retrieve events.
		 *
		 * @param array<string, mixed> $params     CiviCRM Event.get parameters.
		 * @param array<string, mixed> $attributes Normalized block attributes.
		 */
		$params = apply_filters( 'civievent_block_query_args', $params, $attributes );

		try {
			$result = civicrm_api3( 'Event', 'get', $params );
		} catch ( Throwable $error ) {
			self::log_error( $error );

			return new WP_Error(
				'civievent_block_query_failed',
				__( 'CiviCRM events could not be loaded.', 'civievent-block' )
			);
		}

		$events = ! empty( $result['values'] ) && is_array( $result['values'] ) ? array_values( $result['values'] ) : array();

		/**
		 * Filters CiviCRM events before block rendering.
		 *
		 * @param array<int, array<string, mixed>> $events     Event records.
		 * @param array<string, mixed>             $attributes Normalized block attributes.
		 */
		$events = apply_filters( 'civievent_block_events', $events, $attributes );

		return is_array( $events ) ? $events : array();
	}

	/**
	 * Render one event.
	 *
	 * @param array<string, mixed> $event      Event record.
	 * @param array<string, mixed> $attributes Normalized block attributes.
	 * @return string
	 */
	private static function render_event( array $event, array $attributes ) {
		$event_id           = isset( $event['id'] ) ? absint( $event['id'] ) : 0;
		$title              = isset( $event['title'] ) ? sanitize_text_field( (string) $event['title'] ) : '';
		$info_url           = $event_id ? self::get_civicrm_url( 'civicrm/event/info', 'reset=1&id=' . $event_id ) : '';
		$title_heading_level = min( 6, $attributes['headingLevel'] + 1 );

		$title_markup = esc_html( $title );
		if ( '' !== $info_url ) {
			$title_markup = sprintf( '<a href="%1$s">%2$s</a>', esc_url( $info_url ), $title_markup );
		}

		$summary = '';
		if ( $attributes['showSummary'] && ! empty( $event['summary'] ) ) {
			$summary = sprintf(
				'<div class="civievent-block__summary">%s</div>',
				wp_kses_post( (string) $event['summary'] )
			);
		}

		$location = self::render_location( $event, $attributes );
		$register = $attributes['showRegistration'] ? self::render_registration_link( $event ) : '';

		return sprintf(
			'<article class="civievent-block__event"><div class="civievent-block__date">%1$s</div><div class="civievent-block__content"><h%2$d class="civievent-block__event-title">%3$s</h%2$d>%4$s%5$s%6$s</div></article>',
			self::render_date_range( $event, $attributes['showTime'] ),
			$title_heading_level,
			$title_markup,
			$location,
			$summary,
			$register
		);
	}

	/**
	 * Render an event date range using the WordPress date and time settings.
	 *
	 * @param array<string, mixed> $event     Event record.
	 * @param bool                 $show_time Whether to display times.
	 * @return string
	 */
	private static function render_date_range( array $event, $show_time ) {
		$start = self::parse_datetime( isset( $event['start_date'] ) ? (string) $event['start_date'] : '' );
		$end   = self::parse_datetime( isset( $event['end_date'] ) ? (string) $event['end_date'] : '' );

		if ( ! $start ) {
			return '';
		}

		$date_format = (string) get_option( 'date_format', 'F j, Y' );
		$time_format = (string) get_option( 'time_format', 'g:i a' );
		$start_text  = wp_date( $date_format, $start->getTimestamp(), wp_timezone() );

		if ( $show_time ) {
			$start_text .= ' ' . wp_date( $time_format, $start->getTimestamp(), wp_timezone() );
		}

		$output = sprintf(
			'<time datetime="%1$s">%2$s</time>',
			esc_attr( $start->format( DATE_W3C ) ),
			esc_html( $start_text )
		);

		if ( ! $end || $end <= $start ) {
			return $output;
		}

		$same_day = $start->format( 'Y-m-d' ) === $end->format( 'Y-m-d' );
		if ( $same_day && ! $show_time ) {
			return $output;
		}

		$end_text = $same_day ? '' : wp_date( $date_format, $end->getTimestamp(), wp_timezone() );
		if ( $show_time ) {
			$end_text .= ( '' === $end_text ? '' : ' ' ) . wp_date( $time_format, $end->getTimestamp(), wp_timezone() );
		}

		return sprintf(
			'%1$s <span aria-hidden="true">&ndash;</span> <time datetime="%2$s">%3$s</time>',
			$output,
			esc_attr( $end->format( DATE_W3C ) ),
			esc_html( $end_text )
		);
	}

	/**
	 * Render location fields selected in the block settings.
	 *
	 * @param array<string, mixed> $event      Event record.
	 * @param array<string, mixed> $attributes Normalized block attributes.
	 * @return string
	 */
	private static function render_location( array $event, array $attributes ) {
		if ( ! $attributes['showCity'] && 'none' === $attributes['stateDisplay'] && ! $attributes['showCountry'] ) {
			return '';
		}

		if ( isset( $event['is_show_location'] ) && ! (bool) $event['is_show_location'] ) {
			return '';
		}

		$loc_block_id = isset( $event['loc_block_id'] ) ? absint( $event['loc_block_id'] ) : 0;
		if ( ! $loc_block_id ) {
			return '';
		}

		$field_map = array();
		if ( $attributes['showCity'] ) {
			$field_map['city'] = 'city';
		}
		if ( 'none' !== $attributes['stateDisplay'] ) {
			$field_map['state'] = 'abbreviate' === $attributes['stateDisplay'] ? 'state_province_id.abbreviation' : 'state_province_id.name';
		}
		if ( $attributes['showCountry'] ) {
			$field_map['country'] = 'country_id.name';
		}

		try {
			$loc_block = civicrm_api3(
				'LocBlock',
				'getsingle',
				array(
					'id'     => $loc_block_id,
					'return' => array( 'address_id' ),
				)
			);

			$address_id = isset( $loc_block['address_id'] ) ? absint( $loc_block['address_id'] ) : 0;
			if ( ! $address_id ) {
				return '';
			}

			$address = civicrm_api3(
				'Address',
				'getsingle',
				array(
					'id'     => $address_id,
					'return' => array_values( $field_map ),
				)
			);
		} catch ( Throwable $error ) {
			self::log_error( $error );
			return '';
		}

		$parts = array();
		foreach ( $field_map as $field => $api_field ) {
			$value = isset( $address[ $api_field ] ) ? sanitize_text_field( (string) $address[ $api_field ] ) : '';
			if ( '' !== $value && ! in_array( $value, $parts, true ) ) {
				$parts[] = $value;
			}
		}

		if ( empty( $parts ) ) {
			return '';
		}

		return sprintf(
			'<p class="civievent-block__location">%s</p>',
			esc_html( implode( $attributes['locationSeparator'], $parts ) )
		);
	}

	/**
	 * Render a registration link when online registration is currently open.
	 *
	 * @param array<string, mixed> $event Event record.
	 * @return string
	 */
	private static function render_registration_link( array $event ) {
		if ( empty( $event['is_online_registration'] ) || empty( $event['id'] ) ) {
			return '';
		}

		$now          = new DateTimeImmutable( 'now', wp_timezone() );
		$opens        = ! empty( $event['registration_start_date'] ) ? self::parse_datetime( (string) $event['registration_start_date'] ) : null;
		$closes       = ! empty( $event['registration_end_date'] ) ? self::parse_datetime( (string) $event['registration_end_date'] ) : null;
		$registration = self::get_civicrm_url( 'civicrm/event/register', 'reset=1&id=' . absint( $event['id'] ) );

		if ( ( $opens && $opens > $now ) || ( $closes && $closes <= $now ) || '' === $registration ) {
			return '';
		}

		$label = ! empty( $event['registration_link_text'] )
			? sanitize_text_field( (string) $event['registration_link_text'] )
			: __( 'Register', 'civievent-block' );

		/**
		 * Filters an event's registration link label.
		 *
		 * @param string               $label Registration link label.
		 * @param array<string, mixed> $event Event record.
		 */
		$label = apply_filters( 'civievent_block_registration_label', $label, $event );

		return sprintf(
			'<p class="civievent-block__registration"><a href="%1$s">%2$s</a></p>',
			esc_url( $registration ),
			esc_html( $label )
		);
	}

	/**
	 * Generate a CiviCRM URL without assuming a particular WordPress base page.
	 *
	 * @param string $path  CiviCRM route.
	 * @param string $query Query string.
	 * @return string
	 */
	private static function get_civicrm_url( $path, $query ) {
		if ( ! class_exists( '\\CRM_Utils_System' ) ) {
			return '';
		}

		try {
			return (string) \CRM_Utils_System::url( $path, $query );
		} catch ( Throwable $error ) {
			self::log_error( $error );
			return '';
		}
	}

	/**
	 * Parse either a SQL-style or compact CiviCRM date.
	 *
	 * @param string $value Date value.
	 * @return DateTimeImmutable|null
	 */
	private static function parse_datetime( $value ) {
		if ( '' === $value ) {
			return null;
		}

		try {
			if ( preg_match( '/^\d{14}$/', $value ) ) {
				$date = DateTimeImmutable::createFromFormat( '!YmdHis', $value, wp_timezone() );
				return $date ?: null;
			}

			return new DateTimeImmutable( $value, wp_timezone() );
		} catch ( Throwable $error ) {
			self::log_error( $error );
			return null;
		}
	}

	/**
	 * Validate all client-provided block attributes.
	 *
	 * @param array<string, mixed> $attributes Raw attributes.
	 * @return array<string, mixed>
	 */
	private static function normalize_attributes( array $attributes ) {
		$defaults = array(
			'displayMode'        => 'list',
			'heading'            => __( 'Upcoming Events', 'civievent-block' ),
			'headingLevel'       => 2,
			'limit'              => 5,
			'offset'             => 0,
			'eventTypeId'        => 0,
			'showSummary'       => false,
			'showTime'          => true,
			'showRegistration'  => true,
			'showViewAll'       => false,
			'showCity'          => false,
			'stateDisplay'      => 'none',
			'showCountry'       => false,
			'locationSeparator' => ', ',
			'emptyMessage'      => __( 'No upcoming events.', 'civievent-block' ),
		);

		$attributes = wp_parse_args( $attributes, $defaults );
		$separator  = wp_strip_all_tags( (string) $attributes['locationSeparator'] );
		$separator  = preg_replace( '/[\r\n\t]+/', '', $separator );

		return array(
			'displayMode'        => in_array( $attributes['displayMode'], array( 'list', 'single' ), true ) ? $attributes['displayMode'] : 'list',
			'heading'            => sanitize_text_field( (string) $attributes['heading'] ),
			'headingLevel'       => min( 6, max( 2, absint( $attributes['headingLevel'] ) ) ),
			'limit'              => min( 20, max( 1, absint( $attributes['limit'] ) ) ),
			'offset'             => min( 50, absint( $attributes['offset'] ) ),
			'eventTypeId'        => absint( $attributes['eventTypeId'] ),
			'showSummary'       => (bool) $attributes['showSummary'],
			'showTime'          => (bool) $attributes['showTime'],
			'showRegistration'  => (bool) $attributes['showRegistration'],
			'showViewAll'       => (bool) $attributes['showViewAll'],
			'showCity'          => (bool) $attributes['showCity'],
			'stateDisplay'      => in_array( $attributes['stateDisplay'], array( 'none', 'abbreviate', 'full' ), true ) ? $attributes['stateDisplay'] : 'none',
			'showCountry'       => (bool) $attributes['showCountry'],
			'locationSeparator' => substr( $separator, 0, 10 ),
			'emptyMessage'      => sanitize_text_field( (string) $attributes['emptyMessage'] ),
		);
	}

	/**
	 * Show configuration failures to editors without exposing internals publicly.
	 *
	 * @param string $wrapper Wrapper attributes.
	 * @param string $message User-safe message.
	 * @return string
	 */
	private static function render_editor_error( $wrapper, $message ) {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return '';
		}

		return sprintf(
			'<div %1$s><p class="civievent-block__error">%2$s</p></div>',
			$wrapper,
			esc_html( $message )
		);
	}
}
