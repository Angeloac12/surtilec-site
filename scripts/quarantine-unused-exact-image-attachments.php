<?php
/**
 * Mark exact-batch attachments that are no longer used by any product as
 * quarantined. The media files are preserved for audit/recovery.
 *
 * Usage:
 *   wp eval-file - dry
 *   wp eval-file - live
 *
 * @package Surtilec
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

global $wpdb;
$mode = isset( $args[0] ) ? $args[0] : 'dry';
$live = 'live' === $mode;
$requested_ids = array_filter( array_map( 'absint', array_slice( $args, 1 ) ) );

$attachment_ids = $requested_ids ?: get_posts(
	array(
		'post_type'      => 'attachment',
		'post_status'    => 'inherit',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
		'meta_key'       => '_surtilec_image_match_status',
		'meta_value'     => 'coincidencia_exacta_revisada',
	)
);

$unused = array();
foreach ( $attachment_ids as $attachment_id ) {
	$used = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_thumbnail_id' AND meta_value = %s",
			(string) $attachment_id
		)
	);
	if ( 0 === $used ) {
		$unused[] = (int) $attachment_id;
	}
}

WP_CLI::log( 'Adjuntos exactos sin producto asignado: ' . count( $unused ) );
foreach ( $unused as $attachment_id ) {
		WP_CLI::log( '#' . $attachment_id . ' ' . get_the_title( $attachment_id ) );
		if ( $live ) {
			update_post_meta( $attachment_id, '_surtilec_image_match_status', 'revisar_variante_antes_de_publicar' );
			update_post_meta( $attachment_id, '_surtilec_image_quarantined', '1' );
			update_post_meta( $attachment_id, '_surtilec_image_quarantine_reason', 'adjunto_sin_producto_asignado' );
		}
}

wp_cache_flush();
WP_CLI::success( $live ? 'Adjuntos no usados marcados en cuarentena.' : 'Dry-run OK. No se modificaron adjuntos.' );
