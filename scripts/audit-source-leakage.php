<?php
/**
 * Audit live WordPress content and metadata for disallowed source markers.
 *
 * Local research CSVs are intentionally excluded because they are not deployed
 * to WordPress.
 *
 * Usage:
 *   ssh ... "cd <wp> && wp eval-file -" < scripts/audit-source-leakage.php
 *
 * @package Surtilec
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

global $wpdb;

$needles = array( '%cablescolombia%', '%cables colombia%', '%multimedia02.s3%' );
$checks  = array(
	'posts' => "SELECT COUNT(*) FROM {$wpdb->posts} WHERE LOWER(CONCAT_WS(' ', post_title, post_excerpt, post_content)) LIKE %s OR LOWER(CONCAT_WS(' ', post_title, post_excerpt, post_content)) LIKE %s OR LOWER(CONCAT_WS(' ', post_title, post_excerpt, post_content)) LIKE %s",
	'postmeta' => "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE LOWER(CONCAT_WS(' ', meta_key, meta_value)) LIKE %s OR LOWER(CONCAT_WS(' ', meta_key, meta_value)) LIKE %s OR LOWER(CONCAT_WS(' ', meta_key, meta_value)) LIKE %s",
	'terms' => "SELECT COUNT(*) FROM {$wpdb->terms} WHERE LOWER(name) LIKE %s OR LOWER(name) LIKE %s OR LOWER(name) LIKE %s",
	'termmeta' => "SELECT COUNT(*) FROM {$wpdb->termmeta} WHERE LOWER(CONCAT_WS(' ', meta_key, meta_value)) LIKE %s OR LOWER(CONCAT_WS(' ', meta_key, meta_value)) LIKE %s OR LOWER(CONCAT_WS(' ', meta_key, meta_value)) LIKE %s",
	'options' => "SELECT COUNT(*) FROM {$wpdb->options} WHERE LOWER(CONCAT_WS(' ', option_name, option_value)) LIKE %s OR LOWER(CONCAT_WS(' ', option_name, option_value)) LIKE %s OR LOWER(CONCAT_WS(' ', option_name, option_value)) LIKE %s",
);

$aioseo_table = $wpdb->prefix . 'aioseo_posts';
if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $aioseo_table ) ) === $aioseo_table ) {
	$checks['aioseo'] = "SELECT COUNT(*) FROM {$aioseo_table} WHERE LOWER(CONCAT_WS(' ', title, description, keywords, keyphrases, canonical_url, og_title, og_description, og_image_custom_url, og_image_url, twitter_image_custom_url, twitter_image_url, twitter_title, twitter_description, schema_type_options, schema, local_seo, options, ai, breadcrumb_settings)) LIKE %s OR LOWER(CONCAT_WS(' ', title, description, keywords, keyphrases, canonical_url, og_title, og_description, og_image_custom_url, og_image_url, twitter_image_custom_url, twitter_image_url, twitter_title, twitter_description, schema_type_options, schema, local_seo, options, ai, breadcrumb_settings)) LIKE %s OR LOWER(CONCAT_WS(' ', title, description, keywords, keyphrases, canonical_url, og_title, og_description, og_image_custom_url, og_image_url, twitter_image_custom_url, twitter_image_url, twitter_title, twitter_description, schema_type_options, schema, local_seo, options, ai, breadcrumb_settings)) LIKE %s";
}

$total = 0;
foreach ( $checks as $label => $query ) {
	$count = (int) $wpdb->get_var( $wpdb->prepare( $query, $needles ) );
	$total += max( 0, $count );
	WP_CLI::log( "$label: $count" );
}

if ( $total > 0 ) {
	WP_CLI::error( "Encontrados $total registros con marcadores de fuente no permitidos." );
}

WP_CLI::success( 'Auditoría de fuga de fuente limpia.' );
