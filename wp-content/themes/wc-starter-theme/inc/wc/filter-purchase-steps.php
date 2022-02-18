<?php

if ( !class_exists( 'WooCommerce' ) ) 
  return;

/*
 * Passo a passo da compra - Checkout
 */

function purchase_step_page($custom_class = '') {
  $layout = null;

  // Class names
  $cart_step           = is_cart() ? 'class="current"' : (is_checkout() ? 'class="finished"' :  '');
  $checkout_step       = (is_checkout() && !is_wc_endpoint_url("order-received")) ? 'class="current"' : (is_wc_endpoint_url("order-received") ? 'class="finished"' :  '');
  $order_received_step = is_wc_endpoint_url("order-received") ? 'class="finished"' :  '';

  // Current titles
  $cart_title           = is_cart() ? __('Analise os itens no carrinho.', 'wcstartertheme') : (is_checkout() ? __('Revisão de itens completa!', 'wcstartertheme') :  '');
  $checkout_title       = (is_checkout() && !is_wc_endpoint_url("order-received")) ? __('Preencha os campos de entrega com seus dados.', 'wcstartertheme') : ((is_checkout() || is_cart()) && !is_wc_endpoint_url("order-received") ? __('Entrega e pagamento', 'wcstartertheme') :  __('Pagamento e entrega finalizados!', 'wcstartertheme'));
  $order_received_title = is_wc_endpoint_url("order-received") ? __('Compra confirmada com sucesso!', 'wcstartertheme') :  __('Confirmação da compra.', 'wcstartertheme');

  if ((is_cart() && WC()->cart->get_cart_contents_count() > 0) || is_checkout()) {
    $layout = '<ul class="purchase-steps list-inline ml-0 ' . $custom_class . '">
      <li ' . $cart_step . ' title="'. __('Etapa', 'wcstartertheme') .' 1: ' . $cart_title . '">
        <i class="icon-finished fal fa-check-circle wow zoomIn"></i>
        <div class="wrap">
          <i class="icon fal fa-shopping-bag"></i>
          <span>' . __('Carrinho', 'wcstartertheme') . '</span>
        </div>
      </li>

      <li ' . $checkout_step . ' title="'. __('Etapa', 'wcstartertheme') .' 2: ' . $checkout_title . '">
        <i class="icon-finished fal fa-check-circle wow zoomIn"></i>
        <div class="wrap">
          <i class="icon fal fa-truck"></i>
          <span>' . __('Entrega', 'wcstartertheme') . '</span>
        </div>
      </li>

      <li ' . $order_received_step . '  title="'. __('Etapa', 'wcstartertheme') .' 3: ' . $order_received_title . '">
        <i class="icon-finished fal fa-check-circle wow zoomIn"></i>
        <div class="wrap">
          <i class="icon fal fa-check-circle"></i>
          <span>' . __('Confirmação', 'wcstartertheme') . '</span>
        </div>
      </li>
    </ul>';
  }

  return $layout;
}
add_filter('theme_purchase_step_page', 'purchase_step_page', 10, 1);