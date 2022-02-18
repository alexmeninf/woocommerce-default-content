<?php 
/**
 * 
 * Added Required fields
 * Version 1.0
 * 
 */

add_filter( 'woocommerce_billing_fields', 'checkout_shipping_fields', 10 );
add_filter( 'woocommerce_shipping_fields', 'checkout_billing_fields', 10 );

// Our hooked in function - $fields is passed via the filter!
function checkout_billing_fields( $fields ) {
  $fields['shipping_neighborhood'] = array(
    'label'    => __( 'Neighborhood', 'woocommerce-extra-checkout-fields-for-brazil' ),
    'class'    => array( 'form-row-first', 'address-field' ),
    'clear'    => true,
    'priority' => 65,
    'required' => true,
  );
  
  return $fields;
}

function checkout_shipping_fields ($fields) {
  $fields['billing_neighborhood'] = array(
    'label'    => __( 'Neighborhood', 'woocommerce-extra-checkout-fields-for-brazil' ),
    'class'    => array( 'form-row-first', 'address-field' ),
    'clear'    => true,
    'priority' => 65,
    'required' => true,
  );

  return $fields;
}
