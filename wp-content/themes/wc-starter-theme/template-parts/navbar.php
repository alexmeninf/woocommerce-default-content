<?php

// Exit if accessed directly
if ( ! defined('ABSPATH') )
  exit;
  
?>

<div class="navigation">
  <div class="container">
    <div class="row">
      <div class="col-12">
        <?php
        if (class_exists('WooCommerce') && !is_shop()) :
          $args = array(
            'home'      => get_bloginfo('name'),
            'delimiter' => '<i class="fal fa-chevron-right mx-2 text-default"></i>' 
          );
          woocommerce_breadcrumb($args);
        else :
          custom_breadcrumbs();
        endif; ?>
      </div>
    </div>
  </div>
</div>