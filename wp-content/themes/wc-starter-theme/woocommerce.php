<?php

if ( ! defined( 'ABSPATH' ) ) 
  exit;
  
  
get_header(); ?>


<section class="<?php echo section_class('wc-section', true, false) ?>">
  
  <?php do_action( 'theme_woocommece_before_container' ); ?>

  <div class="container<?= is_active_sidebar('sidebar-left-page') && is_active_filter_products() ? '-fluid' : '' ?>">
    <div class="row justify-content-center">

      <?php if (is_active_sidebar('sidebar-left-page') && is_active_filter_products()) : ?>
        <div class="col-md-4 col-lg-3 col-xxl-2 widget-area" role="complementary">

          <?php dynamic_sidebar('sidebar-left-page'); ?>

        </div>
      <?php endif; ?>

      <div class="<?= is_active_sidebar('sidebar-left-page') && is_active_filter_products() ? 'col-md-7 col-lg-8 col-xxl-8 ps-md-5' : 'col-lg-12' ?>">
        <?php woocommerce_content(); ?>
      </div>
    </div>
  </div>

  <?php do_action( 'theme_woocommece_after_container', array('class' => 'mt-5') ); ?>

</section>



<?php get_footer(); ?>