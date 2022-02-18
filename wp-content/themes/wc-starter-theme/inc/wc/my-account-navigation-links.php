<?php

/**
 * 
 * Get current page in single account
 * Version 1.1
 * 
 */

function get_icons_navigation() {
  return array(
    'far fa-tachometer',
    'far fa-cart-arrow-down',
    'far fa-download',
    'far fa-map-marked-alt',
    'far fa-user-alt',
    'far fa-heart',
    'far fa-sign-out'
  );
}

function the_nav_link_account() {
  $icons = get_icons_navigation();
  $i = 0;
  foreach (wc_get_account_menu_items() as $endpoint => $label) : ?>
    <li>
      <a href="<?php echo esc_url(wc_get_account_endpoint_url($endpoint)); ?>">
        <i class="<?= $icons[$i] ?> mr-2"></i>
        <span><?php echo esc_html($label); ?></span>
      </a>
    </li>
  <?php $i++;
  endforeach;
}
