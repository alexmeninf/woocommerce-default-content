<?php

/**
 * Carrinho
 */
remove_action('woocommerce_cart_is_empty', 'wc_empty_cart_message', 10);
add_action('woocommerce_cart_is_empty', 'custom_empty_cart_message', 10);

function custom_empty_cart_message()
{
  $html  = '<span>' . illustration_page('cart') . '</span>';
  $html .= '<p class="cart-empty woocommerce-info">' . wp_kses_post(apply_filters('wc_empty_cart_message', __('Your cart is currently empty.', 'woocommerce'))) . '</p>';
  echo $html;
}


/**
 * Lista de desejos
 */
add_action('tinvwl_wishlist_is_empty', 'illustration_wishlist', 10);
add_action('tinvwl_wishlist_is_null', 'illustration_wishlist', 10);
function illustration_wishlist()
{
  echo illustration_page('wishlist'); ?>
  <style>
    .tinv-wishlist {display: flex;flex-wrap:wrap}
    .tinv-header {order: 0}
    p {order: 2}
    img {order: 1;}
    .return-to-shop {order: 3}
  </style>
<?php
}
