<?php

// Exit if accessed directly
if ( ! defined('ABSPATH') )
  exit;
  
?>

<div class="col-lg-4 col-xxl-3">
  <aside class="sidebar">

    <div class="widget sbox">
      <h2><?php _e('Buscar no site', 'wcstartertheme') ?></h2>
      <?php get_search_form(); ?>
    </div>

    <div class="widget sbox">
      <h2><?php _e('Categorias', 'wcstartertheme') ?></h2>
      <?php get_template_part('template-parts/sidebar/get_categories'); ?>
    </div>

    <div class="widget sbox">
      <h2><?php _e('Tags', 'wcstartertheme') ?></h2>
      <?php get_template_part('template-parts/sidebar/get_tags'); ?>
    </div>

    <div class="widget sbox">
      <h2><?php _e('Registros', 'wcstartertheme') ?></h2>
      <?php get_template_part('template-parts/sidebar/get_archives'); ?>
    </div>

  </aside> <!-- /.sidebar -->
</div>