<?php
/**
 * Apply idempotent AIOSEO pre-launch cleanup.
 *
 * Usage:
 *   ssh ... "cd <wp> && wp eval-file -" < scripts/aioseo-prelaunch-cleanup.php
 *
 * @package Surtilec
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

global $wpdb;

$raw_options = get_option( 'aioseo_options' );
$options     = is_array( $raw_options ) ? $raw_options : json_decode( (string) $raw_options, true );
if ( ! is_array( $options ) ) {
	WP_CLI::error( 'No se pudo leer aioseo_options como JSON/array.' );
}

foreach ( array( 'author', 'date' ) as $archive ) {
	$options['searchAppearance']['archives'][ $archive ]['show'] = false;
	$robots = &$options['searchAppearance']['archives'][ $archive ]['advanced']['robotsMeta'];
	if ( ! is_array( $robots ) ) {
		$robots = array();
	}
	$robots['default']         = false;
	$robots['noindex']         = true;
	$robots['nofollow']        = false;
	$robots['noarchive']       = false;
	$robots['noimageindex']    = false;
	$robots['notranslate']     = false;
	$robots['nosnippet']       = false;
	$robots['noodp']           = false;
	$robots['maxSnippet']      = -1;
	$robots['maxVideoPreview'] = -1;
	$robots['maxImagePreview'] = 'large';
	unset( $robots );
}

update_option( 'aioseo_options', is_array( $raw_options ) ? $options : wp_json_encode( $options, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );

$metadata = array(
	45   => array(
		'title'       => 'Solicitud de cotización | Surtilec',
		'description' => 'Solicita cotización de cables especiales, THHN/THWN-2, VFD y automatización industrial. Envía tus datos y recibe respuesta en horario hábil.',
	),
	46   => array(
		'title'       => 'Subir listado de materiales | Surtilec',
		'description' => 'Carga tu listado de materiales para cotizar cables, conductores y automatización industrial. Surtilec organiza referencias y cantidades para tu proyecto.',
	),
	1447 => array(
		'title'       => 'Cable de control vs instrumentación | Surtilec',
		'description' => 'Conoce cuándo usar cable de control o cable de instrumentación en proyectos industriales, tableros, señales y automatización.',
	),
	1448 => array(
		'title'       => 'THHN vs THWN-2: diferencias | Surtilec',
		'description' => 'Diferencias prácticas entre conductores THHN y THWN-2 para instalaciones eléctricas, canalizaciones y proyectos en Colombia.',
	),
	1449 => array(
		'title'       => 'Cable VFD apantallado: cuándo usarlo | Surtilec',
		'description' => 'Aprende por qué los variadores de frecuencia requieren cable VFD apantallado y qué revisar al cotizar motores y accionamientos.',
	),
	1450 => array(
		'title'       => 'Cable TC/TC-ER para bandeja portacable | Surtilec',
		'description' => 'Guía sobre cable para bandeja portacable TC y TC-ER: usos, aplicaciones industriales y datos clave para una cotización técnica.',
	),
	1451 => array(
		'title'       => 'Cómo elegir calibre AWG de un cable | Surtilec',
		'description' => 'Criterios básicos para elegir calibre AWG según corriente, distancia, caída de tensión, instalación y requisitos del proyecto.',
	),
	1452 => array(
		'title'       => 'RETIE y NTC 2050 para cableado | Surtilec',
		'description' => 'Resumen técnico de RETIE y NTC 2050 aplicado a cableado eléctrico en Colombia, con criterios para compras y especificación.',
	),
	1453 => array(
		'title'       => 'FAQ Surtilec: cotización, despacho y producto',
		'description' => 'Respuestas sobre cómo cotizar en Surtilec, tiempos de atención, despachos en Colombia, productos disponibles y datos para solicitar precios.',
	),
);

$table   = $wpdb->prefix . 'aioseo_posts';
$now     = current_time( 'mysql', true );
$updated = 0;
$inserted = 0;
$skipped = array();

foreach ( $metadata as $post_id => $seo ) {
	$post = get_post( $post_id );
	if ( ! $post || 'publish' !== $post->post_status ) {
		$skipped[] = $post_id;
		continue;
	}

	$row_id = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT id FROM {$table} WHERE post_id = %d LIMIT 1",
			$post_id
		)
	);

	$data = array(
		'title'               => $seo['title'],
		'description'         => $seo['description'],
		'og_title'            => $seo['title'],
		'og_description'      => $seo['description'],
		'twitter_title'       => $seo['title'],
		'twitter_description' => $seo['description'],
		'twitter_use_og'      => 0,
		'robots_default'      => 1,
		'robots_noindex'      => 0,
		'robots_nofollow'     => 0,
		'updated'             => $now,
	);

	if ( $row_id ) {
		$wpdb->update( $table, $data, array( 'id' => $row_id ) );
		$updated++;
	} else {
		$data['post_id'] = $post_id;
		$data['created'] = $now;
		$wpdb->insert( $table, $data );
		$inserted++;
	}
}

wp_cache_flush();

WP_CLI::success( 'AIOSEO pre-launch cleanup aplicado.' );
WP_CLI::log( "metadata_updated: $updated" );
WP_CLI::log( "metadata_inserted: $inserted" );
WP_CLI::log( 'skipped_posts: ' . ( empty( $skipped ) ? '0' : implode( ',', $skipped ) ) );
WP_CLI::log( 'archives_noindexed: author,date' );
