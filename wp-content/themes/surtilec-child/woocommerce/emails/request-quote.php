<?php
/**
 * YITH Request a Quote — admin email (HTML). Surtilec override.
 *
 * Override of templates/emails/request-quote.php from the plugin: the price
 * "Subtotal" column is removed (quote catalog shows no prices). Keeps all
 * plugin hooks. Do not edit the plugin; this is a theme template override.
 *
 * @package Surtilec
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<?php do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<p>
<?php
	/* translators: %s: user name */
	printf( esc_html__( 'You received a quote request from %s. The request is the following:', 'yith-woocommerce-request-a-quote' ), esc_html( $raq_data['user_name'] ) );
?>
</p>

<?php do_action( 'yith_ywraq_email_before_raq_table', $raq_data ); ?>

<h2><?php esc_html_e( 'Quote request', 'yith-woocommerce-request-a-quote' ); ?></h2>

<table cellspacing="0" cellpadding="6" style="width: 100%; border: 1px solid #eee; margin-bottom:30px" border="1" bordercolor="#eee">
	<thead>
	<tr>
		<th scope="col" style="text-align:left; border: 1px solid #eee;"><?php esc_html_e( 'Product', 'yith-woocommerce-request-a-quote' ); ?></th>
		<th scope="col" style="text-align:left; border: 1px solid #eee;"><?php esc_html_e( 'Quantity', 'yith-woocommerce-request-a-quote' ); ?></th>
	</tr>
	</thead>
	<tbody>
	<?php
	if ( ! empty( $raq_data['raq_content'] ) ) :
		foreach ( $raq_data['raq_content'] as $item ) :
			$_product = isset( $item['variation_id'] ) ? yith_ywraq_get_product( $item['variation_id'] ) : yith_ywraq_get_product( $item['product_id'] );
			if ( ! $_product ) {
				continue;
			}

			$product_admin_link = '';
			$posttype_object    = get_post_type_object( get_post( $_product->get_id() )->post_type ?? 'product' );
			if ( ( $posttype_object ) && ( $posttype_object->_edit_link ) ) {
				$product_admin_link = admin_url( sprintf( $posttype_object->_edit_link . '&action=edit', $_product->get_id() ) );
			}
			?>
			<tr>
				<td scope="col" style="text-align:left;"><a href="<?php echo esc_url( $product_admin_link ); ?>"><?php echo wp_kses_post( $_product->get_title() ); ?></a>
					<?php if ( isset( $item['variations'] ) ) : ?>
						<small><?php echo wp_kses_post( yith_ywraq_get_product_meta( $item ) ); ?></small>
					<?php endif ?>
				</td>
				<td scope="col" style="text-align:left;"><?php echo esc_html( $item['quantity'] ); ?></td>
			</tr>
			<?php
		endforeach;
	endif;
	?>
	</tbody>
</table>

<?php do_action( 'yith_ywraq_email_after_raq_table', $raq_data ); ?>
<?php if ( ! empty( $raq_data['user_message'] ) ) : ?>
<h2><?php esc_html_e( 'Customer message', 'yith-woocommerce-request-a-quote' ); ?></h2>
	<p><?php echo wp_kses_post( $raq_data['user_message'] ); ?></p>
<?php endif ?>
<h2><?php esc_html_e( 'Customer details', 'yith-woocommerce-request-a-quote' ); ?></h2>

<p><strong><?php esc_html_e( 'Name:', 'yith-woocommerce-request-a-quote' ); ?></strong> <?php echo esc_html( $raq_data['user_name'] ); ?></p>
<p><strong><?php esc_html_e( 'Email:', 'yith-woocommerce-request-a-quote' ); ?></strong> <a href="mailto:<?php echo esc_attr( $raq_data['user_email'] ); ?>"><?php echo esc_html( $raq_data['user_email'] ); ?></a></p>

<?php do_action( 'woocommerce_email_footer' ); ?>
