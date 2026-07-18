<?php
/**
 * Audit image alt text for Surtilec.
 *
 * Usage:
 *   wp eval-file scripts/audit-image-alt.php
 *   wp eval-file scripts/audit-image-alt.php format=json
 *   wp eval-file scripts/audit-image-alt.php strict
 *
 * @package Surtilec
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

$format = 'text';
$strict = false;
foreach ( $args as $arg ) {
	if ( '--format=json' === $arg || 'format=json' === $arg || 'json' === $arg ) {
		$format = 'json';
	} elseif ( '--strict' === $arg || 'strict' === $arg ) {
		$strict = true;
	}
}

global $wpdb;

$posts = $wpdb->posts;
$pm    = $wpdb->postmeta;

$stats = array(
	'published_products'                 => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$posts} WHERE post_type='product' AND post_status='publish'" ),
	'image_attachments_total'            => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$posts} WHERE post_type='attachment' AND post_mime_type LIKE 'image/%'" ),
	'image_attachments_missing_alt'      => (int) $wpdb->get_var(
		"SELECT COUNT(*)
		FROM {$posts} a
		LEFT JOIN {$pm} alt ON alt.post_id = a.ID AND alt.meta_key = '_wp_attachment_image_alt'
		WHERE a.post_type='attachment'
			AND a.post_mime_type LIKE 'image/%'
			AND (alt.meta_id IS NULL OR TRIM(alt.meta_value) = '')"
	),
	'products_with_featured_image'        => (int) $wpdb->get_var(
		"SELECT COUNT(*)
		FROM {$posts} p
		INNER JOIN {$pm} thumb ON thumb.post_id = p.ID AND thumb.meta_key = '_thumbnail_id' AND TRIM(thumb.meta_value) <> ''
		WHERE p.post_type='product' AND p.post_status='publish'"
	),
	'products_missing_featured_image'     => (int) $wpdb->get_var(
		"SELECT COUNT(*)
		FROM {$posts} p
		LEFT JOIN {$pm} thumb ON thumb.post_id = p.ID AND thumb.meta_key = '_thumbnail_id' AND TRIM(thumb.meta_value) <> ''
		WHERE p.post_type='product'
			AND p.post_status='publish'
			AND thumb.meta_id IS NULL"
	),
	'product_featured_images_missing_alt' => (int) $wpdb->get_var(
		"SELECT COUNT(*)
		FROM {$posts} p
		INNER JOIN {$pm} thumb ON thumb.post_id = p.ID AND thumb.meta_key = '_thumbnail_id' AND TRIM(thumb.meta_value) <> ''
		LEFT JOIN {$pm} alt ON alt.post_id = CAST(thumb.meta_value AS UNSIGNED) AND alt.meta_key = '_wp_attachment_image_alt'
		WHERE p.post_type='product'
			AND p.post_status='publish'
			AND (alt.meta_id IS NULL OR TRIM(alt.meta_value) = '')"
	),
	'product_featured_images_generic_alt' => 0,
	'samples'                            => array(
		'attachments_missing_alt'         => array(),
		'featured_images_missing_alt'     => array(),
		'featured_images_generic_alt'     => array(),
	),
);

$missing_attachments = $wpdb->get_results(
	"SELECT a.ID, a.post_title, guid
	FROM {$posts} a
	LEFT JOIN {$pm} alt ON alt.post_id = a.ID AND alt.meta_key = '_wp_attachment_image_alt'
	WHERE a.post_type='attachment'
		AND a.post_mime_type LIKE 'image/%'
		AND (alt.meta_id IS NULL OR TRIM(alt.meta_value) = '')
	ORDER BY a.ID ASC
	LIMIT 10"
);

foreach ( $missing_attachments as $attachment ) {
	$stats['samples']['attachments_missing_alt'][] = array(
		'id'              => (int) $attachment->ID,
		'title'           => html_entity_decode( wp_strip_all_tags( (string) $attachment->post_title ), ENT_QUOTES, 'UTF-8' ),
		'url'             => wp_get_attachment_url( (int) $attachment->ID ),
		'recommended_alt' => function_exists( 'surtilec_attachment_contextual_alt_text' ) ? surtilec_attachment_contextual_alt_text( (int) $attachment->ID ) : '',
	);
}

$featured_rows = $wpdb->get_results(
	"SELECT p.ID AS product_id, p.post_title, sku.meta_value AS sku, CAST(thumb.meta_value AS UNSIGNED) AS attachment_id, alt.meta_value AS alt
	FROM {$posts} p
	INNER JOIN {$pm} thumb ON thumb.post_id = p.ID AND thumb.meta_key = '_thumbnail_id' AND TRIM(thumb.meta_value) <> ''
	LEFT JOIN {$pm} sku ON sku.post_id = p.ID AND sku.meta_key = '_sku'
	LEFT JOIN {$pm} alt ON alt.post_id = CAST(thumb.meta_value AS UNSIGNED) AND alt.meta_key = '_wp_attachment_image_alt'
	WHERE p.post_type='product'
		AND p.post_status='publish'
	ORDER BY p.ID ASC"
);

foreach ( $featured_rows as $row ) {
	$alt        = trim( (string) $row->alt );
	$is_missing = '' === $alt;
	$is_generic = function_exists( 'surtilec_image_alt_is_generic' ) ? surtilec_image_alt_is_generic( $alt ) : false;

	if ( $is_missing && count( $stats['samples']['featured_images_missing_alt'] ) < 10 ) {
		$stats['samples']['featured_images_missing_alt'][] = array(
			'product_id'      => (int) $row->product_id,
			'sku'             => (string) $row->sku,
			'title'           => html_entity_decode( wp_strip_all_tags( (string) $row->post_title ), ENT_QUOTES, 'UTF-8' ),
			'attachment_id'   => (int) $row->attachment_id,
			'recommended_alt' => function_exists( 'surtilec_product_featured_image_alt_text' ) ? surtilec_product_featured_image_alt_text( (int) $row->product_id ) : '',
		);
	}

	if ( $is_generic ) {
		$stats['product_featured_images_generic_alt']++;
		if ( count( $stats['samples']['featured_images_generic_alt'] ) < 10 ) {
			$stats['samples']['featured_images_generic_alt'][] = array(
				'product_id'      => (int) $row->product_id,
				'sku'             => (string) $row->sku,
				'title'           => html_entity_decode( wp_strip_all_tags( (string) $row->post_title ), ENT_QUOTES, 'UTF-8' ),
				'attachment_id'   => (int) $row->attachment_id,
				'current_alt'     => $alt,
				'recommended_alt' => function_exists( 'surtilec_product_featured_image_alt_text' ) ? surtilec_product_featured_image_alt_text( (int) $row->product_id ) : '',
			);
		}
	}
}

$has_issues = $stats['image_attachments_missing_alt'] > 0
	|| $stats['product_featured_images_missing_alt'] > 0
	|| $stats['product_featured_images_generic_alt'] > 0;

if ( 'json' === $format ) {
	$stats['ok'] = ! $has_issues;
	WP_CLI::line( wp_json_encode( $stats, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
	if ( $strict && $has_issues ) {
		WP_CLI::halt( 1 );
	}
	return;
} else {
	WP_CLI::log( '== Auditoría de alt text ==' );
	foreach ( $stats as $key => $value ) {
		if ( 'samples' === $key ) {
			continue;
		}
		WP_CLI::log( "$key: $value" );
	}

	foreach ( $stats['samples'] as $label => $items ) {
		if ( empty( $items ) ) {
			continue;
		}
		WP_CLI::log( "\nMuestras: $label" );
		foreach ( $items as $item ) {
			WP_CLI::log( '  - ' . wp_json_encode( $item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
		}
	}
}

if ( $strict && $has_issues ) {
	WP_CLI::error( 'Alt text requiere corrección.' );
}

if ( ! $has_issues ) {
	WP_CLI::success( 'Alt text OK.' );
} else {
	WP_CLI::warning( 'Alt text requiere revisión.' );
}
