<?php
/**
 * Surtilec — homepage editable fields (ACF free).
 *
 * Lets the client edit the hero copy, CTAs and background image of the front
 * page from WP admin without touching the template. The template ships sensible
 * defaults, so the page works before anything is filled in.
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
				'key'      => 'group_surtilec_home',
				'title'    => 'Surtilec — Portada',
				'fields'   => array(
					array(
						'key'           => 'field_su_hero_eyebrow',
						'label'         => 'Hero — antetítulo',
						'name'          => 'su_hero_eyebrow',
						'type'          => 'text',
						'default_value' => 'Distribuidor industrial',
					),
					array(
						'key'           => 'field_su_hero_heading',
						'label'         => 'Hero — título',
						'name'          => 'su_hero_heading',
						'type'          => 'text',
						'default_value' => 'Cables especiales y automatización industrial para la industria colombiana',
					),
					array(
						'key'           => 'field_su_hero_subline',
						'label'         => 'Hero — subtítulo',
						'name'          => 'su_hero_subline',
						'type'          => 'textarea',
						'rows'          => 2,
						'new_lines'     => '',
						'default_value' => 'Despachos a toda Colombia desde Bogotá. Respuesta en menos de 1 hora hábil.',
					),
					array(
						'key'           => 'field_su_hero_bg',
						'label'         => 'Hero — imagen de fondo',
						'name'          => 'su_hero_bg',
						'type'          => 'image',
						'return_format' => 'id',
						'preview_size'  => 'medium',
						'instructions'  => 'Opcional. Foto industrial horizontal (≥1600px). Si se deja vacío se usa el degradado técnico.',
					),
					array(
						'key'           => 'field_su_cta1_label',
						'label'         => 'CTA principal — texto',
						'name'          => 'su_cta1_label',
						'type'          => 'text',
						'default_value' => 'Cotizar ahora',
					),
					array(
						'key'           => 'field_su_cta1_url',
						'label'         => 'CTA principal — enlace',
						'name'          => 'su_cta1_url',
						'type'          => 'text',
						'default_value' => '/cotizar/solicitud/',
					),
					array(
						'key'           => 'field_su_cta2_label',
						'label'         => 'CTA secundario — texto',
						'name'          => 'su_cta2_label',
						'type'          => 'text',
						'default_value' => 'Ver catálogo',
					),
					array(
						'key'           => 'field_su_cta2_url',
						'label'         => 'CTA secundario — enlace',
						'name'          => 'su_cta2_url',
						'type'          => 'text',
						'default_value' => '/productos/',
					),
				),
				'location' => array(
					array(
						array(
							'param'    => 'page_type',
							'operator' => '==',
							'value'    => 'front_page',
						),
					),
				),
			)
		);
	}
);

/**
 * Small helper: read an ACF field with a guaranteed string fallback.
 *
 * @param string $name    Field name.
 * @param string $default Fallback when ACF is missing or the value is empty.
 * @return string
 */
function surtilec_home_field( $name, $default = '' ) {
	if ( function_exists( 'get_field' ) ) {
		$val = get_field( $name );
		if ( ! empty( $val ) ) {
			return $val;
		}
	}
	return $default;
}
