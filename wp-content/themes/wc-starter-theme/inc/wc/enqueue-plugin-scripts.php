<?php 
/**
 * Dequeue woocommerce styles
 */
add_filter( 'woocommerce_enqueue_styles', '__return_false' );



/**
 * Enqueue your own stylesheet woocommerce
 */
function theme_enqueue_wc_style() {
  wp_register_style( 'theme-woocommerce', THEMEROOT . '/woocommerce/assets/css/woocommerce.css' );
  
  if ( class_exists( 'woocommerce' ) ) {
    wp_enqueue_style( 'theme-woocommerce' );
  }
}

add_action( 'wp_enqueue_scripts', 'theme_enqueue_wc_style', 0 );



/**
 * remove some styles
 */
function wc_dequeue_scripts_plugins() {

  // Remove style plugin TI Woocommerce wishlist
  wp_dequeue_style( 'tinvwl' );
} 

add_action( 'wp_enqueue_scripts', 'wc_dequeue_scripts_plugins');
