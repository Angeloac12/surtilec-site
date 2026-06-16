<?php
/**
 * Plugin Name: Surtilec — Indicador de metales (cobre / aluminio)
 * Description: Tira de referencia con el precio de cobre y aluminio en COP/kg = (USD spot por libra) × (TRM del Banco de la República, vía datos.gov.co) ÷ 0,4536. Refresco diario por WP-Cron, cacheado en una opción: NUNCA se consulta en cada carga. Es un valor de REFERENCIA, no constituye cotización.
 * Version:     0.1.0
 * Author:      Surtilec
 *
 * @package Surtilec
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const SURTILEC_METALS_OPT   = 'surtilec_metals_data'; // computed snapshot.
const SURTILEC_METALS_USD   = 'surtilec_metals_usd';  // manual USD/lb fallback (admin/CLI editable).
const SURTILEC_METALS_EVENT = 'surtilec_metals_refresh';
const SURTILEC_METALS_LB_KG = 0.45359237;

/**
 * Fallback de precios USD por libra (editables). El admin pone los reales con:
 *   wp option update surtilec_metals_usd '{"copper":4.2,"aluminum":1.15}' --format=json
 * o conecta una API real enganchando el filtro `surtilec_metals_usd`.
 *
 * @return array{copper:float,aluminum:float}
 */
function surtilec_metals_usd_defaults() {
	return array(
		'copper'   => 4.20,
		'aluminum' => 1.15,
	);
}

/* =============================================================
   Cron diario
   ============================================================= */
add_action(
	'init',
	function () {
		if ( ! wp_next_scheduled( SURTILEC_METALS_EVENT ) ) {
			wp_schedule_event( time() + 60, 'daily', SURTILEC_METALS_EVENT );
		}
	}
);
add_action( SURTILEC_METALS_EVENT, 'surtilec_metals_refresh' );

/**
 * TRM (COP por USD) del último día hábil, dataset oficial de datos.gov.co
 * (Superintendencia Financiera). Sin API key.
 *
 * @return float 0 si falla.
 */
function surtilec_metals_fetch_trm() {
	$url = 'https://www.datos.gov.co/resource/32sa-8pi3.json?$select=valor,vigenciadesde&$order=vigenciadesde%20DESC&$limit=1';
	$res = wp_remote_get( $url, array( 'timeout' => 12 ) );
	if ( is_wp_error( $res ) || 200 !== (int) wp_remote_retrieve_response_code( $res ) ) {
		return 0.0;
	}
	$body = json_decode( wp_remote_retrieve_body( $res ), true );
	return ( ! empty( $body[0]['valor'] ) ) ? (float) $body[0]['valor'] : 0.0;
}

/**
 * Precios USD/lb de cobre y aluminio. Por defecto = opción manual; un proveedor
 * de API real puede engancharse al filtro `surtilec_metals_usd` (cobre/aluminio).
 *
 * @return array{copper:float,aluminum:float}
 */
function surtilec_metals_fetch_usd() {
	$manual = wp_parse_args( (array) get_option( SURTILEC_METALS_USD, array() ), surtilec_metals_usd_defaults() );
	$usd    = array(
		'copper'   => (float) $manual['copper'],
		'aluminum' => (float) $manual['aluminum'],
	);
	/**
	 * Filtra los precios USD/lb. Devuelve ['copper'=>float,'aluminum'=>float].
	 * Aquí se puede conectar una API real (metals.dev, etc.).
	 */
	return apply_filters( 'surtilec_metals_usd', $usd );
}

/**
 * Recalcula el snapshot (cron). COP/kg = USD/lb × TRM ÷ (lb→kg).
 * Conserva el valor previo para la flecha de tendencia y purga la caché.
 */
function surtilec_metals_refresh() {
	$prev = (array) get_option( SURTILEC_METALS_OPT, array() );
	$trm  = surtilec_metals_fetch_trm();
	if ( $trm <= 0 ) {
		$trm = isset( $prev['trm'] ) ? (float) $prev['trm'] : 0.0; // conserva el último bueno.
	}
	$usd = surtilec_metals_fetch_usd();

	$cu = ( $trm > 0 ) ? round( $usd['copper'] * $trm / SURTILEC_METALS_LB_KG ) : 0;
	$al = ( $trm > 0 ) ? round( $usd['aluminum'] * $trm / SURTILEC_METALS_LB_KG ) : 0;

	$data = array(
		'copper_usd_lb'        => $usd['copper'],
		'aluminum_usd_lb'      => $usd['aluminum'],
		'trm'                  => $trm,
		'copper_cop_kg'        => $cu,
		'aluminum_cop_kg'      => $al,
		'prev_copper_cop_kg'   => isset( $prev['copper_cop_kg'] ) ? (int) $prev['copper_cop_kg'] : $cu,
		'prev_aluminum_cop_kg' => isset( $prev['aluminum_cop_kg'] ) ? (int) $prev['aluminum_cop_kg'] : $al,
		'updated'              => time(),
	);
	update_option( SURTILEC_METALS_OPT, $data, false );

	// Refresca la caché de página para que la tira muestre el nuevo valor.
	do_action( 'litespeed_purge_all' );
}

/* =============================================================
   Render — tira de referencia (la llama la barra utilitaria del tema)
   ============================================================= */

/**
 * Una flecha de tendencia (clase + glifo) comparando actual vs previo.
 *
 * @param int $now  Valor actual.
 * @param int $prev Valor previo.
 * @return array{0:string,1:string} [clase, glifo]
 */
function surtilec_metals_trend( $now, $prev ) {
	if ( $now > $prev ) {
		return array( 'up', '▲' );
	}
	if ( $now < $prev ) {
		return array( 'down', '▼' );
	}
	return array( 'flat', '■' );
}

/**
 * HTML de la tira de metales. Vacío si no hay datos (no rompe nada).
 *
 * @return string
 */
function surtilec_metals_ticker() {
	$d = (array) get_option( SURTILEC_METALS_OPT, array() );
	if ( empty( $d['copper_cop_kg'] ) || empty( $d['aluminum_cop_kg'] ) ) {
		return '';
	}

	$metals = array(
		array( 'Cobre', (int) $d['copper_cop_kg'], (int) $d['prev_copper_cop_kg'] ),
		array( 'Aluminio', (int) $d['aluminum_cop_kg'], (int) $d['prev_aluminum_cop_kg'] ),
	);

	$out  = '<span class="su-metals" title="Valor de referencia (cobre/aluminio LME/COMEX × TRM Banco de la República). No constituye cotización.">';
	foreach ( $metals as $m ) {
		list( $cls, $glyph ) = surtilec_metals_trend( $m[1], $m[2] );
		$out                .= '<span class="su-metal">'
			. '<span class="su-metal-name">' . esc_html( $m[0] ) . '</span> '
			. '<span class="su-metal-price">$' . esc_html( number_format_i18n( $m[1] ) ) . '</span> '
			. '<span class="su-metal-unit">COP/kg</span> '
			. '<span class="su-metal-trend ' . esc_attr( $cls ) . '" aria-hidden="true">' . $glyph . '</span>'
			. '</span>';
	}
	$out .= '<span class="su-metals-ref">ref.</span>';
	$out .= '</span>';
	return $out;
}
