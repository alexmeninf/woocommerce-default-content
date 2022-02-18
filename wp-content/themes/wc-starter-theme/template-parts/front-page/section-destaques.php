<?php

if ( !class_exists('ACF') ) 
  return;

$terms = get_field('destaque_categorias', 'options');

if ($terms) :
  global $product; ?>

<section class="<?= section_class('destaques-section pt-front-page', false, false) ?>">
  <div class="container">
    <div class="theme-offset">

      <div class="theme-section-side-title top-space rotate"><?php _e('Destaques da semana', 'wcstartertheme'); ?></div>
      <h2 class="title-section"><?php _e('As melhores opções pra você', 'wcstartertheme') ?></h2>

      <!--================ Tabs ================-->
      <div class="theme-tabs">

        <!--================ Tabs Navigation ================-->
        <div id="v-pills-tab" role="tablist" aria-orientation="vertical" class="theme-tabs-nav nav">
          <?php foreach ($terms as $key => $term) : ?>
            <button class="nav-link <?= $key == 0 ? 'active' : '' ?>" id="v-pills-tab-cat-<?= $key ?>" data-bs-toggle="pill" data-bs-target="#v-pills-cat-<?= $key ?>" type="button" role="tab" aria-controls="v-pills-cat-<?= $key ?>" aria-selected="<?= $key == 0 ? 'true' : 'false' ?>"><?php echo $term->name; ?></button>
          <?php endforeach; ?>
        </div>

        <!--================ Tabs Content ================-->
        <div class="tab-content" id="v-pills-tabContent">
          <?php
          foreach ($terms as $key => $term) : ?>
            <div id="v-pills-cat-<?= $key ?>" class="theme-tab tab-pane fade <?= $key == 0 ? 'show active' : '' ?>" role="tabpanel" aria-labelledby="v-pills-tab-cat-<?= $key ?>">
              <div class="theme-grid theme-products nav-style-2">
                <ul class="products d-block mt-0 owl-carousel theme-products" data-nav="true" data-xs="2"  data-sm="3" data-md="4" data-lg="5" data-xl="6" data-margin="10">
                  <?php
                  $args = array(
                    'post_type'      => 'product',
                    'posts_per_page' => 8,
                    'order'          => 'desc',
                    'meta_key'       => 'total_sales',
                    'orderby'        => 'meta_value_num',
                    'tax_query' => array(
                      array(
                        'taxonomy' => 'product_cat',
                        'field' => 'slug',
                        'terms' => $term->slug
                      )
                    )
                  );
                  apply_filters('theme_loop_shop_produts', $args, false);
                  ?>
                </ul>
              </div><!-- /.theme-grid -->
            </div><!-- end item -->
          <?php endforeach; ?>
        </div><!-- /.theme-tabs-container -->
      </div><!-- /.theme-tabs -->
    </div><!-- /.theme-offset -->
  </div><!-- /.container -->
</section>

<?php endif; ?>