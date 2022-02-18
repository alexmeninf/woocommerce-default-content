<?php
/** 
 * 
 * Check for empty-cart get param to clear the cart
 * Version 1.0
 * 
 */

add_action( 'woocommerce_cart_actions', 'theme_action_add_button_empty_cart' );
function theme_action_add_button_empty_cart() { 
	if ( WC()->cart->get_cart_contents_count() >= 1 ) { ?>
		<a class="button empty-btn" href="<?= wc_get_cart_url(); ?>?empty-cart"><?php _e( 'Limpar carrinho', 'wcstartertheme' ); ?></a>
	<?php }
}

add_action( 'init', 'theme_action_clear_cart' );
function theme_action_clear_cart() {
  global $woocommerce;
	
	if ( isset( $_GET['empty-cart'] ) ) {
		$woocommerce->cart->empty_cart(); 
	}
}