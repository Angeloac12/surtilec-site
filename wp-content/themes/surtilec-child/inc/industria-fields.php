<?php
/**
 * Surtilec — campos editables de las páginas de Industria (ACF free).
 *
 * Se muestran cuando la página usa la plantilla template-industria.php.
 * El mapeo industria→categorías y el copy son editables; si se dejan vacíos,
 * la plantilla muestra todas las líneas pilar reales (sin inventar nada).
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
		acf_add_local_field_group(
			array(
				'key'      => 'group_surtilec_industria',
				'title'    => 'Surtilec — Industria',
				'fields'   => array(
					array(
						'key'          => 'field_su_ind_subhead',
						'label'        => 'Hero — bajada',
						'name'         => 'industria_subhead',
						'type'         => 'textarea',
						'rows'         => 2,
						'instructions' => 'Define el sector en 1-2 frases. Si se deja vacío se usa un texto genérico.',
					),
					array(
						'key'          => 'field_su_ind_intro',
						'label'        => 'Productos — texto introductorio',
						'name'         => 'industria_intro',
						'type'         => 'textarea',
						'rows'         => 4,
					),
					array(
						'key'           => 'field_su_ind_cats',
						'label'         => 'Categorías relevantes',
						'name'          => 'industria_categorias',
						'type'          => 'taxonomy',
						'taxonomy'      => 'product_cat',
						'field_type'    => 'multi_select',
						'add_term'      => 0,
						'save_terms'    => 0,
						'load_terms'    => 0,
						'return_format' => 'id',
						'instructions'  => 'Elige las categorías de producto que aplican a este sector. Vacío = se muestran todas las líneas.',
					),
					array(
						'key'           => 'field_su_ind_why_title',
						'label'         => 'Por qué Surtilec — título',
						'name'          => 'industria_why_title',
						'type'          => 'text',
					),
					array(
						'key'          => 'field_su_ind_why_bullets',
						'label'        => 'Por qué Surtilec — viñetas',
						'name'         => 'industria_why_bullets',
						'type'         => 'textarea',
						'rows'         => 4,
						'instructions' => 'Una por línea.',
					),
				),
				'location' => array(
					array(
						array(
							'param'    => 'page_template',
							'operator' => '==',
							'value'    => 'template-industria.php',
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
 * @param string $default Valor por defecto.
 * @return mixed
 */
function surtilec_industria_field( $name, $default = '' ) {
	if ( function_exists( 'get_field' ) ) {
		$val = get_field( $name );
		if ( ! empty( $val ) ) {
			return $val;
		}
	}
	return $default;
}
