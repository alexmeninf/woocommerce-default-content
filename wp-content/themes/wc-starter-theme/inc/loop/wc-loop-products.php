<?php
/**
 * query_products_loop
 * 
 * @version 1.0
 *
 * @param  mixed $args
 * @return void
 */
function query_products_loop($args = array(), $node_parent = true) {

  if (empty($args)) {
    $args = array(
      'post_type'      => 'product',
      'posts_per_page' => 12,
      'order'          => 'desc',
      'orderby'        => 'rand',
      'post_status'    => 'publish',
    );
  }

  if (class_exists('WooCommerce')) :

    $loop = new WP_Query($args);

    if ($loop->have_posts()) {

      echo $node_parent ? '<ul class="products">' : '';

      while ($loop->have_posts()) : $loop->the_post();
        wc_get_template_part('content', 'product');
      endwhile;

      echo $node_parent ? '</ul>' : '';

    } else {
      echo "Nenhum produto foi encontrado.";
    }

    wp_reset_postdata();
  endif;
}

add_filter('theme_loop_shop_produts', 'query_products_loop', 10, 2);
