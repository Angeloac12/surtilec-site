<?php
/**
 * Fill missing image alt text from known Surtilec contexts.
 *
 * Usage:
 *   wp eval-file scripts/fix-image-alt.php dry-run
 *   wp eval-file scripts/fix-image-alt.php
 *   wp eval-file scripts/fix-image-alt.php include-generic
 *
 * Defaults:
 * - Fills empty alt only.
 * - Does not overwrite manual alt text.
 * - `--include-generic` also replaces known generic values such as "image".
 *
 * @package Surtilec
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

$dry_run         = false;
$include_generic = false;
$limit           = 0;

foreach ( $args as $arg ) {
	if ( '--dry-run' === $arg || 'dry-run' === $arg ) {
		$dry_run = true;
	} elseif ( '--include-generic' === $arg || 'include-generic' === $arg ) {
		$include_generic = true;
	} elseif ( 0 === strpos( $arg, '--limit=' ) || 0 === strpos( $arg, 'limit=' ) ) {
		$limit = max( 0, (int) substr( $arg, strpos( $arg, '=' ) + 1 ) );
	}
}

if ( ! function_exists( 'surtilec_attachment_contextual_alt_text' ) ) {
	WP_CLI::error( 'No está cargado surtilec-image-alt.php. Despliega mu-plugins antes de usar este script.' );
}

$attachments = get_posts(
	array(
		'post_type'      => 'attachment',
		'post_status'    => 'inherit',
		'post_mime_type' => 'image',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'orderby'        => 'ID',
		'order'          => 'ASC',
	)
);

$checked = 0;
$updated = 0;
$would_update = 0;
$skipped_no_context = 0;
$skipped_manual = 0;
$changes = array();

foreach ( $attachments as $attachment_id ) {
	$checked++;
	$current_alt = trim( (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) );
	$is_empty    = '' === $current_alt;
	$is_generic  = $include_generic && function_exists( 'surtilec_image_alt_is_generic' ) && surtilec_image_alt_is_generic( $current_alt );

	if ( ! $is_empty && ! $is_generic ) {
		$skipped_manual++;
		continue;
	}

	$recommended = surtilec_attachment_contextual_alt_text( $attachment_id );
	if ( '' === $recommended ) {
		$skipped_no_context++;
		continue;
	}

	$record = array(
		'attachment_id' => (int) $attachment_id,
		'old_alt'       => $current_alt,
		'new_alt'       => $recommended,
	);

	if ( $dry_run ) {
		$would_update++;
		$changes[] = $record;
	} else {
		update_post_meta( $attachment_id, '_wp_attachment_image_alt', $recommended );
		$updated++;
		$changes[] = $record;
	}

	if ( $limit > 0 && count( $changes ) >= $limit ) {
		break;
	}
}

WP_CLI::log( '== Reparación de alt text ==' );
WP_CLI::log( 'modo: ' . ( $dry_run ? 'dry-run' : 'live' ) );
WP_CLI::log( 'include_generic: ' . ( $include_generic ? 'yes' : 'no' ) );
WP_CLI::log( "attachments_checked: $checked" );
WP_CLI::log( "updated: $updated" );
WP_CLI::log( "would_update: $would_update" );
WP_CLI::log( "skipped_manual_alt: $skipped_manual" );
WP_CLI::log( "skipped_no_context: $skipped_no_context" );

if ( ! empty( $changes ) ) {
	WP_CLI::log( "\nCambios:" );
	foreach ( $changes as $change ) {
		WP_CLI::log( '  - ' . wp_json_encode( $change, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
	}
}

WP_CLI::success( $dry_run ? 'Dry-run terminado.' : 'Alt text actualizado.' );
