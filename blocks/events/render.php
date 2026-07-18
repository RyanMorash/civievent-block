<?php
/**
 * Dynamic render template for the CiviCRM Events block.
 *
 * @var array<string, mixed> $attributes Block attributes.
 *
 * @package CiviEventBlock
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo \CiviEvent_Block\Renderer::render( $attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by the renderer.
