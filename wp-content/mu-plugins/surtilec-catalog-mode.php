<?php
/**
 * Plugin Name: Surtilec — Modo Catálogo
 * Description: Convierte WooCommerce en un catálogo de cotización: oculta precios, quita el botón de añadir al carrito y deshabilita carrito/checkout. Conserva el botón de YITH "Solicitar cotización".
 * Version:     0.1.0
 * Author:      Surtilec
 *
 * @package Surtilec
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interruptor global. Pon false para reactivar el comercio (carrito/checkout)
 * más adelante sin borrar este archivo.
 */
if ( ! defined( 'SURTILEC_CATALOG_MODE' ) ) {
	define( 'SURTILEC_CATALOG_MODE', true );
}

if ( SURTILEC_CATALOG_MODE ) {

	/**
	 * Reemplaza el precio por un texto de cotización en todas partes
	 * (loop de tienda, producto individual, relacionados, widgets).
	 *
	 * @return string
	 */
	add_filter(
		'woocommerce_get_price_html',
		function () {
			return '<span class="surtilec-precio-cotizar">' . esc_html__( 'Precio: solicitar cotización', 'surtilec' ) . '</span>';
		},
		PHP_INT_MAX
	);

	/**
	 * Quita el botón de añadir al carrito y el campo de cantidad.
	 *
	 * No usamos woocommerce_is_purchasable=false a propósito, para no romper
	 * el botón de YITH Request a Quote. Basta con remover las plantillas.
	 */
	add_action(
		'init',
		function () {
			// Loop de la tienda.
			remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );
			// Producto individual.
			remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
		}
	);

	/**
	 * Deshabilita las páginas de carrito y checkout: redirige a /cotizar/
	 * si existe, de lo contrario a la portada.
	 */
	add_action(
		'template_redirect',
		function () {
			if ( ! function_exists( 'is_cart' ) || ! function_exists( 'is_checkout' ) ) {
				return;
			}

			if ( is_cart() || is_checkout() ) {
				$cotizar = get_page_by_path( 'cotizar' );
				$destino = ( $cotizar instanceof WP_Post ) ? get_permalink( $cotizar ) : home_url( '/' );
				wp_safe_redirect( $destino );
				exit;
			}
		}
	);

	/**
	 * Quita los scripts de fragmentos del carrito (mejora de rendimiento:
	 * ya no hay carrito que actualizar por AJAX).
	 */
	add_action(
		'wp_enqueue_scripts',
		function () {
			wp_dequeue_script( 'wc-cart-fragments' );
		},
		11
	);

	/**
	 * Quita la pestaña de valoraciones del producto individual.
	 * (Refuerza el ajuste woocommerce_enable_reviews = no.)
	 */
	add_filter(
		'woocommerce_product_tabs',
		function ( $tabs ) {
			unset( $tabs['reviews'] );
			return $tabs;
		},
		98
	);

	/**
	 * Cierra los comentarios/valoraciones en los productos.
	 */
	add_filter(
		'comments_open',
		function ( $open, $post_id ) {
			if ( 'product' === get_post_type( $post_id ) ) {
				return false;
			}
			return $open;
		},
		10,
		2
	);
}

/**
 * Traducción al español de las cadenas visibles de YITH Request a Quote.
 *
 * El plugin trae traducción es_ES, pero el sitio usa es_CO, por lo que no se
 * carga y quedan textos en inglés. Filtramos solo el dominio del plugin (sin
 * editar archivos del plugin). Fuera del bloque SURTILEC_CATALOG_MODE para que
 * la traducción persista aunque se reactive el comercio.
 */
add_filter(
	'gettext',
	function ( $translated, $text, $domain ) {
		if ( 'yith-woocommerce-request-a-quote' !== $domain ) {
			return $translated;
		}

		static $map = array(
			'Add to Quote'                                                   => 'Añadir a cotización',
			'Product added to the list!'                                     => '¡Producto agregado a la lista!',
			'Product already in the list.'                                   => 'El producto ya está en la lista.',
			'No products in list'                                            => 'No hay productos en la lista',
			'Your list is empty'                                             => 'Tu lista está vacía',
			'Your list is empty, add products to the list to send a request' => 'Tu lista está vacía, agrega productos para enviar una solicitud',
			'Browse the list'                                                => 'Ver la lista',
			'Notes on your request...'                                       => 'Notas sobre tu solicitud…',
			'Quote request'                                                  => 'Solicitud de cotización',
			'Send the request'                                               => 'Enviar la solicitud',
			'Send Your Request'                                              => 'Envía tu solicitud',
			'Your request has been sent successfully'                        => 'Tu solicitud se envió correctamente',
		);

		return isset( $map[ $text ] ) ? $map[ $text ] : $translated;
	},
	10,
	3
);
