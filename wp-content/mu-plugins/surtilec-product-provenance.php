<?php
/**
 * Display verified batch details on product pages.
 *
 * Internal source and image-license metadata remain private. Only useful
 * customer-facing details such as sales unit, temperature and datasheet link
 * are rendered when the importer has supplied them.
 *
 * @package Surtilec
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'woocommerce_after_single_product_summary', 'surtilec_product_batch_details', 6 );

/**
 * Render verified product details captured by the extended batch importer.
 *
 * @return void
 */
function surtilec_product_batch_details() {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}

	$product = function_exists( 'wc_get_product' ) ? wc_get_product( get_queried_object_id() ) : false;
	if ( ! $product ) {
		return;
	}

	$rows = array();
	$unit = trim( (string) get_post_meta( $product->get_id(), '_surtilec_unidad_venta', true ) );
	$temp = trim( (string) get_post_meta( $product->get_id(), '_surtilec_temperatura_maxima', true ) );
	$url  = trim( (string) get_post_meta( $product->get_id(), '_surtilec_datasheet_url', true ) );
	$is_generic = '1' === (string) get_post_meta( $product->get_id(), '_surtilec_generic_product', true );

	if ( '' !== $unit ) {
		$rows[] = '<tr><th scope="row">' . esc_html__( 'Unidad de venta', 'surtilec' ) . '</th><td>' . esc_html( $unit ) . '</td></tr>';
	}
	if ( '' !== $temp ) {
		$rows[] = '<tr><th scope="row">' . esc_html__( 'Temperatura máxima', 'surtilec' ) . '</th><td>' . esc_html( $temp ) . '</td></tr>';
	}
	$datasheet_host = wp_parse_url( $url, PHP_URL_HOST );
	$source_site_url = $datasheet_host && ( 'cablescolombia.com' === strtolower( $datasheet_host ) || preg_match( '/\.cablescolombia\.com$/i', $datasheet_host ) );
	if ( preg_match( '#^https?://[^[:space:]]+$#i', $url ) && ! $source_site_url ) {
		$label = $is_generic ? __( 'Fuente técnica de familia', 'surtilec' ) : __( 'Ficha técnica', 'surtilec' );
		$link  = $is_generic ? __( 'Consultar ficha de familia', 'surtilec' ) : __( 'Consultar ficha técnica', 'surtilec' );
		$rows[] = '<tr><th scope="row">' . esc_html( $label ) . '</th><td><a href="' . esc_url( $url ) . '" target="_blank" rel="noopener">' . esc_html( $link ) . '</a></td></tr>';
	}

	if ( empty( $rows ) ) {
		return;
	}

	echo '<section class="surtilec-specs surtilec-product-batch-details">';
	echo '<h2>' . esc_html__( 'Información del producto', 'surtilec' ) . '</h2>';
	echo '<table class="surtilec-spec-table"><tbody>' . implode( '', $rows ) . '</tbody></table>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '</section>';
}
