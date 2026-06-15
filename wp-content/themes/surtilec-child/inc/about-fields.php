<?php
/**
 * Surtilec — campos editables de la página Nosotros (ACF free).
 *
 * El grupo se muestra cuando la página usa la plantilla page-nosotros.php.
 * La plantilla trae defaults seguros, así que la página funciona antes de
 * que el cliente complete nada. Cifras = claims reales, no inventadas.
 *
 * @package Surtilec
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'acf/init',
	function () {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		$fields = array(
			array(
				'key'           => 'field_su_about_eyebrow',
				'label'         => 'Hero — antetítulo',
				'name'          => 'about_eyebrow',
				'type'          => 'text',
				'default_value' => 'Nosotros',
			),
			array(
				'key'           => 'field_su_about_heading',
				'label'         => 'Hero — título',
				'name'          => 'about_heading',
				'type'          => 'text',
				'default_value' => 'Distribuidor industrial de cables especiales y automatización',
			),
			array(
				'key'           => 'field_su_about_lead',
				'label'         => 'Hero — bajada',
				'name'          => 'about_lead',
				'type'          => 'textarea',
				'rows'          => 2,
				'default_value' => 'Atendemos a la industria colombiana con producto técnico, asesoría y despachos a todo el país desde Bogotá.',
			),
			array(
				'key'           => 'field_su_about_intro',
				'label'         => 'Sección — texto de presentación',
				'name'          => 'about_intro',
				'type'          => 'textarea',
				'rows'          => 6,
				'instructions'  => 'Quiénes somos y a quién servimos. Separa párrafos con una línea en blanco.',
				'default_value' => "Surtilec es un distribuidor colombiano especializado en cables especiales (control, THHN/THWN-2, variadores VFD, instrumentación, encauchetados y apantallados) y en automatización industrial (variadores de frecuencia, PLC, HMI y sensores).\n\nTrabajamos con distribuidores, integradores, contratistas y áreas de mantenimiento que necesitan el producto correcto, con respaldo técnico y tiempos de respuesta cortos.",
			),
			array(
				'key'           => 'field_su_about_coverage_title',
				'label'         => 'Cobertura — título',
				'name'          => 'about_coverage_title',
				'type'          => 'text',
				'default_value' => 'Cobertura nacional desde Bogotá',
			),
			array(
				'key'           => 'field_su_about_coverage_text',
				'label'         => 'Cobertura — texto',
				'name'          => 'about_coverage_text',
				'type'          => 'textarea',
				'rows'          => 3,
				'default_value' => 'Operamos desde Bogotá y despachamos a toda Colombia. Cotizamos en menos de 1 hora hábil y coordinamos el envío según tu proyecto.',
			),
			array(
				'key'           => 'field_su_about_coverage_bullets',
				'label'         => 'Cobertura — viñetas',
				'name'          => 'about_coverage_bullets',
				'type'          => 'textarea',
				'rows'          => 4,
				'instructions'  => 'Una por línea.',
				'default_value' => "Despachos a todo el país\nAsesoría técnica para selección de producto\nGestión de listados y cotizaciones por volumen",
			),
		);

		// Cuatro estadísticas (num + etiqueta). ACF free no tiene repeater.
		$stat_defaults = array(
			array( 'Bogotá', 'Centro de distribución' ),
			array( 'Nacional', 'Cobertura de despacho' ),
			array( '< 1 h', 'Respuesta en horario hábil' ),
			array( 'B2B', 'Industria, proyectos e integradores' ),
		);
		foreach ( $stat_defaults as $i => $def ) {
			$n          = $i + 1;
			$fields[]   = array(
				'key'           => "field_su_about_stat{$n}_num",
				'label'         => "Estadística {$n} — dato",
				'name'          => "about_stat{$n}_num",
				'type'          => 'text',
				'default_value' => $def[0],
				'wrapper'       => array( 'width' => '30' ),
			);
			$fields[]   = array(
				'key'           => "field_su_about_stat{$n}_label",
				'label'         => "Estadística {$n} — etiqueta",
				'name'          => "about_stat{$n}_label",
				'type'          => 'text',
				'default_value' => $def[1],
				'wrapper'       => array( 'width' => '70' ),
			);
		}

		acf_add_local_field_group(
			array(
				'key'      => 'group_surtilec_about',
				'title'    => 'Surtilec — Nosotros',
				'fields'   => $fields,
				'location' => array(
					array(
						array(
							'param'    => 'page_template',
							'operator' => '==',
							'value'    => 'page-nosotros.php',
						),
					),
				),
			)
		);
	}
);

/**
 * Lee un campo ACF de la página actual con fallback de cadena.
 *
 * @param string $name    Nombre del campo.
 * @param string $default Valor por defecto si ACF falta o el campo está vacío.
 * @return string
 */
function surtilec_about_field( $name, $default = '' ) {
	if ( function_exists( 'get_field' ) ) {
		$val = get_field( $name );
		if ( ! empty( $val ) ) {
			return $val;
		}
	}
	return $default;
}
