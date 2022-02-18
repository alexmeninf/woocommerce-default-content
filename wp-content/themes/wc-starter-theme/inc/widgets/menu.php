<?php
add_filter('theme_harder_menu', 'header_menu', 10, 1);
add_action('wp_enqueue_scripts', 'script_menu', 1);
add_action('wp_enqueue_scripts', 'script_js_menu', 30);

/**
 * header_menu
 */
function header_menu() { ?>

  <nav class="top-header">
    <div class="container d-flex justify-content-center justify-content-lg-between align-items-center">
      
      <?php
      /**
       * Menu superior esquerdo do header
       */
      wp_nav_menu(array(
        'theme_location'  => 'header_left_menu',
        'fallback_cb'     => false,
        'item_spacing'    => 'discard',
        'container_class' => 'd-none d-lg-block'
      ));
      ?>

      <div class="d-flex align-items-center">
        <span class="me-3 d-none d-lg-block"><?php _e('Siga nas redes sociais', 'wcstartertheme'); ?></span>
        
        <?php 
        /**
         * Menu de Redes sociais
         */
        theme_social_menu('list-inline social-list before-icons small');
        ?>
        
      </div><!-- right -->
    </div>
  </nav>
  
  <header class="mh-head navbar navbar-expand-lg justify-content-between flex-wrap" id="header-main">
    <div class="container-lg">
      <!-- Button -->
      <a class="navbar-toggler mburger mburger--spin" href="#menu" aria-label="Toggle navigation">
        <b></b>
        <b></b>
        <b></b>
      </a>
      
      <?php apply_filters('theme_logo_client', null) ?>
            
      <div class="d-none d-lg-flex align-items-center" style="width: 70%;">
        <div class="col-8 col-xl-9 px-3">
          <?php search_form_theme() ?>
        </div><!-- /.col-8 -->

        <div class="col-4 col-xl-3 px-3">
          <div class="login-section">
            <span class="text-uppsecase op-8 d-block mb-1"><?php _e('Bem-vindo', 'wcstartertheme'); ?> :)</span>
            <?php if (!is_user_logged_in()) : ?>
              <a href="<?php echo get_permalink(get_option('woocommerce_myaccount_page_id')); ?>" class="login-link"><i class="me-1 fad fa-sign-in-alt"></i> <?php _e('Entre', 'wcstartertheme'); ?></a>
              <span class="mx-1"><?php _e('ou', 'wcstartertheme'); ?></span>
              <a href="<?php the_permalink_register() ?>" class="login-link"><?php _e('Registre-se', 'wcstartertheme'); ?></a>
            <?php else : ?>
              <a href="<?php echo get_permalink(get_option('woocommerce_myaccount_page_id')); ?>" class="login-link"><i class="me-1 fad fa-user"></i> <?php _e('Minha conta', 'wcstartertheme') ?></a>
              <span class="mx-1"> | </span>
              <a href="<?= wp_logout_url(get_bloginfo('url')) ?>" class="login-link"><i class="me-1 fad fa-sign-out-alt"></i> <?php _e('Sair', 'wcstartertheme') ?></a>
            <?php endif; ?>
          </div>
        </div><!-- /.col-4 -->
      </div><!-- /.search & login -->

      <!-- card icon -->
      <div class="d-flex">
        <?php if (function_exists('tinv_url_wishlist_default')) :  ?>
          <a href="<?php echo tinv_url_wishlist_default(); ?>" class="icon-link wishlist_products_counter no-txt wishlist-counter-with-products" title="<?php esc_attr_e('Lista de desejos', 'wcstartertheme'); ?>">
            <i class="far fa-heart"></i>
            <sup>
              <span class="badge rounded-pill wishlist_products_counter_number">
                <?php echo TInvWL_Public_WishlistCounter::counter(); ?>
              </span><!-- /.badge -->
            </sup>
          </a>
        <?php endif; ?>

        <a class="icon-link" href="#shoppingbag" title="<?php esc_attr_e('Meu carrinho', 'wcstartertheme'); ?>">
          <i class="fa fa-shopping-bag"></i>
          <sup>
            <span class="badge rounded-pill">
              <span class="cart_contents_count">
                <?= WC()->cart->get_cart_contents_count(); ?>
              </span><!-- /.cart_contents_count -->
            </span><!-- /.badge -->
          </sup>
        </a>
      </div>

      <div class="head-navgation justify-content-lg-center w-100 d-none d-lg-flex mt-3">
        <nav id="menu">
          <ul class="mx-auto mb-2 mb-lg-0">
            <li class="Divider"><?php _e('Loja', 'wcstartertheme'); ?></li>
            
            <li>
              <a href="<?php bloginfo('url') ?>"><i class="far fa-home-lg-alt"></i> <?php _e('Início', 'wcstartertheme'); ?></a>
            </li>

            <li>
              <a href="<?= wc_get_cart_url(); ?>">
                <i class="far fa-shopping-cart"></i>
                <?php _e('Carrinho', 'wcstartertheme'); ?>
                <span class="badge bg-primary rounded-pill">
                  <span class="cart_contents_count"><?= WC()->cart->get_cart_contents_count(); ?></span>
                </span><!-- /.badge -->
              </a>
            </li>

            <?php if (WC()->cart->get_cart_contents_count() > 0) : ?>

              <li>
                <a href="<?= wc_get_checkout_url() ?>">
                  <i class="far fa-shopping-bag"></i>
                  <?php _e('Finalizar pedido', 'wcstartertheme'); ?>
                </a>
              </li>

              <li>
                <a href="<?= get_permalink(wc_get_page_id('shop')) ?>">
                  <i class="far fa-cart-plus"></i>
                  <?php _e('Continue comprando', 'wcstartertheme'); ?>
                </a>
              </li>

            <?php else : ?>

              <li>
                <a href="<?= get_permalink(wc_get_page_id('shop')) ?>">
                  <i class="far fa-cart-plus"></i>
                  <?php _e('Comece a comprar', 'wcstartertheme'); ?>
                </a>
              </li>

            <?php endif; ?>

            <li class="d-lg-none">
              <a href="<?php echo get_permalink(get_option('woocommerce_myaccount_page_id')); ?>"><i class="far fa-store"></i> <?php echo __('Minha conta', 'wcstartertheme') ?></a>
              <?php
              /* Navigation shop pages */
              echo apply_filters(
                'theme_navbar_shop',
                array(
                  'parent_class' => '',
                  'children_class' => '',
                  'anchor_class' => '',
                  'enable_icons' => true,
                  'node_parent' => true,
				          'enable_dropdown' => false,
                )
              ); ?>
            </li>

            <li class="Divider"><?php _e('Categorias', 'wcstartertheme'); ?></li>
            <li>
              <a href="<?= get_permalink(wc_get_page_id('shop')) ?>">
                <i class="fal fa-tags"></i>
                <?php _e('Todas categorias', 'wcstartertheme'); ?>
              </a>
              <ul class="owl-carousel theme-products slide-categories" data-nav="true" data-dots="false" data-xs="3" data-md="4" data-lg="5" data-margin="2" data-padding="20">
                <?php menu_hierarchical('product_cat', false); ?>
              </ul>
            </li>
            <li class="Divider"><?php _e('Institucional', 'wcstartertheme'); ?></li>
            <li><a href="<?php the_permalink(89) ?>"><i class="fal fa-address-card"></i> <?php _e('Quem somos', 'wcstartertheme'); ?></a></li>
            <li class="d-lg-none"><a href="<?php echo get_privacy_policy_url() ?>"><i class="far fa-shield-alt"></i> <?php _e('Política de privacidade', 'wcstartertheme'); ?></a></li>
            <li class="d-lg-none"><a href="<?php the_permalink(584) ?>"><i class="fal fa-question-circle"></i> <?php _e('Dúvidas frequentes', 'wcstartertheme'); ?></a></li>
            <li class="d-lg-none"><a href="<?php the_permalink() ?>"><i class="far fa-reply"></i> <?php _e('Fale conosco', 'wcstartertheme'); ?></a></li>
          </ul>
        </nav><!-- /#menu -->
      </div><!-- /.head-navgation -->
    </div><!-- /.container-lg -->
  </header>

  <!-- Search field -->
  <div class="header-bottom mh-head d-lg-none pt-0">
    <?php search_form_theme() ?>
  </div>

  <!-- Menu cart -->
  <nav id="shoppingbag">
    <div>
      <div class="p-4">
        <?php get_template_part('inc/loop/theme-loop', 'box-cart'); ?>
      </div>
    </div>
  </nav>
<?php }


function script_menu()
{
  wp_register_style('script-header-menu', get_template_directory_uri() . '/assets/plugins/mmenu/demo.css', array(), false);
  wp_enqueue_style('script-header-menu');
}


function script_js_menu()
{
  wp_enqueue_script('script-mmenu', get_template_directory_uri() . '/assets/plugins/mmenu/js/mmenu.js', array(), false, true);
  wp_enqueue_script('script-mhead', get_template_directory_uri() . '/assets/plugins/mmenu/js/mhead.js', array(), false, true);
  wp_enqueue_script('script-mmenu-playground', get_template_directory_uri() . '/assets/plugins/mmenu/js/playground.js', array(), false, true);
  wp_enqueue_script('script-mmenu-polyfills', get_template_directory_uri() . '/assets/plugins/mmenu/js/mmenu.polyfills.js', array(), false, true);
  wp_enqueue_script('script-mmenu-settings', get_template_directory_uri() . '/assets/plugins/mmenu/js/mmenu-settings-v1.js', array(), false, true);
}
