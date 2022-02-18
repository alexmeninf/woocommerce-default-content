<?php

// Exit if accessed directly
if ( ! defined('ABSPATH') )
  exit;

  
get_header();

$search = get_search_query();

if (isset($search) && $search != '') {
  
  $search_post_type = array('product');
  $args_total_posts = array(
    'post_type'      => $search_post_type,
    'posts_per_page' => -1,
    's'              => $search,
    '_meta_or_title' => $search
  ); // total posts search

  $posts_total    = new WP_Query($args_total_posts);
  $posts_count    = $posts_total->post_count; // total de posts
  $posts_per_page = 9;
  $pages_count    = ceil($posts_count / $posts_per_page);
  $current_page   = ( isset($_GET['pg']) && $_GET['pg'] > 1 && $_GET['pg'] <= $pages_count ) ? $_GET['pg'] : 1;

  $args = array(
    'post_type'      => $search_post_type,
    'orderby'        => 'id',
    'posts_per_page' => $posts_per_page,
    'order'          => 'DESC',
    'paged'          => $current_page,
    's'              => $search,
    '_meta_or_title' => $search
  );
  $query_search = new WP_Query($args);
}
?>


<section class="<?= section_class('search min-vh-adjustment', true, false) ?>">
  <div class="container<?= is_active_sidebar('sidebar-left-page') && is_active_filter_products() ? '-fluid' : '' ?>">
    <?php
    if ( isset($search) && $search != '' ) :

      if ( $query_search->have_posts() ) : ?>

        <div class="row justify-content-center">

          <?php if (is_active_sidebar('sidebar-left-page') && is_active_filter_products()) : ?>
            <div class="col-md-4 col-lg-3 col-xxl-2 widget-area" role="complementary">

              <?php dynamic_sidebar('sidebar-left-page'); ?>

            </div>
          <?php endif; ?>

          <div class="<?= is_active_sidebar('sidebar-left-page') && is_active_filter_products() ? 'col-md-7 col-lg-8 col-xxl-8 ps-md-5' : 'col-lg-12' ?>">
            <h1 class="title-search page-title"><?php _e('Resultados relacionados com', 'wcstartertheme') ?> '<?= $search ?>'</h1>

            <p class="total-results">
              <?= $posts_count > 1 ? sprintf(__('Exibindo todos %s resultados encontrados.', 'wcstartertheme'), $posts_count) : __('Exibindo um único resultado.', 'wcstartertheme') ?>
            </p>

            <ul class="products columns-4 mb-4">
              <?php
              /*----------  Loop  ----------*/
              while ($query_search->have_posts()) :
                $query_search->the_post(); ?>

                <?php wc_get_template_part('content', 'product'); ?>

              <?php endwhile; ?>
            </ul>

            <?php get_pagination($current_page, $pages_count); ?>
          </div>
        </div>

      <?php else : ?>

        <div class="row justify-content-center">
          <div class="col-md-6 text-center">
            <div class="alert alert-warning" role="alert">

              <?php echo illustration_page('search'); ?>

              <div class="mb-4">
                <?php search_form_theme() ?>
              </div>

              <p class="woocommerce-info" role="alert">
                <?php printf(__('Nenhum resultado com o nome <b>%s</b> foi encontrado.', 'wcstartertheme'), $search); ?>
              </p>

            </div>
          </div>
        </div>

      <?php endif;
      wp_reset_postdata();

    else : ?>

      <div class="row justify-content-center">
        <div class="col-md-6 text-center">
          <div class="alert alert-danger" role="alert">

            <?php echo illustration_page('search'); ?>

            <div class="mb-4">
              <?php search_form_theme() ?>
            </div>

            <p class="woocommerce-error">
              <?php _e('Nenhum resultado foi encontrado, você deve digitar o que busca.', 'wcstartertheme'); ?>
            </p>

          </div>
        </div>
      </div>

    <?php
    endif; ?>
  </div><!-- /.container -->
</section><!-- section end -->


<?php get_footer() ?>