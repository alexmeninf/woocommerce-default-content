<?php

if ( !class_exists( 'WooCommerce' ) ) 
	return;

/**
 * Logo do site
 * theme_logo_callback
 *
 * @param  string $localization
 * @return mixed
 */
function theme_logo_callback($localization = '') {
  $custom_logo_id = get_theme_mod( 'custom_logo' );
  $logo           = wp_get_attachment_image_src( $custom_logo_id , 'full' );
    
  if ( has_custom_logo() ) {
    
    if ($localization == 'footer') {
      echo '<a href="' . get_bloginfo( 'url' ) . '" class="theme-logo text-center d-block d-xl-inline-block text-xl-start">
              <img class="lazyload" data-src="' . esc_url( $logo[0] ) . '" alt="logo ' . get_bloginfo( 'name' ) . '">
            </a>';

    } else {
      echo '<span class="custom-logo-link">
              <a href="' . get_bloginfo( 'url' ) . '" class="mh-logo" title="' . get_bloginfo( 'name' ) . '" arial-label="logo" style="background-image:url(' . esc_url( $logo[0] ) . ')"></a>
            </span>';
    }

  } else {
    echo '<h1 class="custom-title">' . get_bloginfo('name') . '</h1>';
  }
}

add_filter('theme_logo_client', 'theme_logo_callback', 10, 1);



/**
 * filter_woocommerce_cart_needs_payment
 * Verifica se o pedido precisa de método de pagamento. Caso esteja desabilitado será removido.
 *
 * @return void
 */
function filter_woocommerce_cart_needs_payment() {
  if ( CART_NEEDS_PAYMENT ) {
    return true;
    
  } else {

    return false;
  }
}

add_filter('woocommerce_cart_needs_payment', 'filter_woocommerce_cart_needs_payment', 10, 1);



/**
 * filter_change_html_price_by_payment
 * Altera o valor exibido no carrinho de cada item, caso não houver método de pagamento habilitado.
 *
 * @param  mixed $price
 * @return string
 */
function filter_change_html_price_by_payment( $price ) {

  if ( ! is_admin() && THEME_DISABLE_PRODUCT_PRICE === true ) {
    $price = __('Sem valor', 'wcstartertheme');
  }

  return $price;
}

add_filter( 'woocommerce_get_price_html', 'filter_change_html_price_by_payment' );
add_filter( 'woocommerce_cart_item_price', 'filter_change_html_price_by_payment' );



/**
 * filter_theme_change_cart_table_price
 * Mostra o preço com desconto no carrinho
 *
 * @param  mixed $price
 * @param  mixed $values
 * @param  mixed $cart_item_key
 * @return void
 */
function filter_theme_change_cart_table_price($price, $values, $cart_item_key) {
  $slashed_price = $values['data']->get_price_html();
  $is_on_sale    = $values['data']->is_on_sale();
  
  if ($is_on_sale) {
    $price = $slashed_price;
  }
  return $price;
}

add_filter('woocommerce_cart_item_price', 'filter_theme_change_cart_table_price', 30, 3);



/**
 * woocommerce_cart_item_subtotal
 * Renomeia e zera o valor subtotal dos produtos do carrinho, caso não houver método de pagamento.
 * 
 * @param  mixed $price
 */
add_filter( 'woocommerce_cart_item_subtotal', function ( $price ) {

  if ( ! is_admin() && THEME_DISABLE_PRODUCT_PRICE === true ) {
    $price = get_woocommerce_currency_symbol() . ' 00,00';
  }

  return $price;
});



/**
 * woocommerce_cart_subtotal
 * Renomeia e zera o valor subtotal da compra, caso não houver método de pagamento.
 * 
 * @param  mixed $subtotal
 */
add_filter( 'woocommerce_cart_subtotal', function ( $subtotal ) {

  if ( ! is_admin() && THEME_DISABLE_PRODUCT_PRICE === true ) {
    $subtotal = get_woocommerce_currency_symbol() . ' 00,00';
  }

  return $subtotal;
});



/**
 * woocommerce_cart_total
 * Renomeia o subtotal da compra na pág. do carrinho e checkout, caso não houver método de pagamento.
 *
 * @param  mixed $total
 * @return void
 */
add_filter( 'woocommerce_cart_total', function( $total ) {

  if ( ! is_admin() && THEME_DISABLE_PRODUCT_PRICE === true ) {
    $total = __('Um consultor entrará em contato após a finalização da compra para negociar o valor.', 'wcstartertheme');
  }

  return $total;
});



/**
 * filter_rename_thankyou_title
 * Rename headline info in thank you page
 *
 * @param  mixed $thank_you_title
 * @param  mixed $order
 * @return string
 */
function filter_rename_thankyou_title($thank_you_title, $order) {

  $text = '<span class="d-block mb-3">' . __('Obrigado. <br class="d-block">Seu pedido foi recebido!', 'wcstartertheme') . '</span>';

  if ( CART_NEEDS_PAYMENT === false && THEME_DISABLE_PRODUCT_PRICE === true ) {
    $text .= '<span class="fs-5 d-block fw-light text-dark lh-1">' . __( 'Aguarde que um consultor entrará em contato para negociar com você o valor total de sua compra.', 'wcstartertheme') . '</span>';
  }

  return $text;
}

add_filter('woocommerce_thankyou_order_received_text', 'filter_rename_thankyou_title', 20, 2);



/**
 * Change checkout button text  "Place Order" to custom text in checkout page 
 *
 * @param $button_text
 * @return string
 */
function filter_rename_btn_order($button_text) {

  return __('Finalizar pedido', 'wcstartertheme'); // Replace this text in quotes with your respective custom button text
}

add_filter('woocommerce_order_button_text', 'filter_rename_btn_order');
