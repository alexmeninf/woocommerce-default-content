<?php

if ( !class_exists( 'WooCommerce' ) ) 
	return;


/**
 * init_action_dependencies
 * Dependencias necessárias para o plugin funcionar corretamente.
 *
 * @return mixed
 */

function init_action_dependencies_theme() {

  if ( !is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
    echo '<div class="error"><p>' . __('Aviso: O tema precisa do Plugin <b>WooCommerce</b> ativo para funcionar. <a href="'.admin_url('plugins.php').'">Ative agora.</a>', 'wcstartertheme') . '</p></div>';
    
    if ( ! is_admin() ) {
      exit;
    }
  
  } elseif ( ! is_plugin_active( 'recently-viewed-and-most-viewed-products/recently-viewed-and-most-viewed-products.php' ) ) {
    echo '<div class="notice"><p>' . __('Aviso: O plugin <b>Recently viewed and most viewed products</b> deve ser ativo para melhor aproveito do seu tema. <a href="'.admin_url('plugins.php').'">Ative agora.</a>', 'wcstartertheme') . '</p></div>';

  } elseif ( ! is_plugin_active( 'ti-woocommerce-wishlist/ti-woocommerce-wishlist.php' ) ) {
    echo '<div class="notice"><p>' . __('Aviso: O plugin <b>TI WooCommerce Wishlist</b> deve ser ativo para melhor aproveito do seu tema. <a href="'.admin_url('plugins.php').'">Ative agora.</a>', 'wcstartertheme') . '</p></div>';
  
  } elseif ( ! is_plugin_active( 'ajax-search-for-woocommerce/ajax-search-for-woocommerce.php' ) ) {
    echo '<div class="notice"><p>' . __('Aviso: O plugin <b>FiboSearch - AJAX Search for WooCommerce</b> deve ser ativo para melhor aproveito do seu tema. <a href="'.admin_url('plugins.php').'">Ative agora.</a>', 'wcstartertheme') . '</p></div>';
  
  
  } elseif ( ! is_plugin_active( 'annasta-woocommerce-product-filters/annasta-woocommerce-product-filters.php' ) ) {
    echo '<div class="notice"><p>' . __('Aviso: O plugin <b>annasta Woocommerce Product Filters</b> deve ser ativo para melhor aproveito do seu tema. <a href="'.admin_url('plugins.php').'">Ative agora.</a>', 'wcstartertheme') . '</p></div>';
  }
}

add_action( 'admin_init', 'init_action_dependencies_theme' );


/**
 * callback_show_product_banners
 * Show bannners
 * 
 * Hook shown at wooocommerce.php, front-page.php
 *
 * @param  mixed $args
 * @return void
 */
function callback_show_product_banners($args = null) {

  if ( ! class_exists('ACF') ) 
    return;

  $offset = isset($args['offset']) ? $args['offset']  : 0;
  $limit  = isset($args['limit']) ? $args['limit']  : 1000;
  $class =  isset($args['class']) ? $args['class']  : '';

  if (have_rows('banners_after_wc_page', 'options')) : ?>

    <section class="<?= $class ?>">
      <div class="container">
        <div class="row row-cols-1 row-cols-lg-2 g-4">

          <?php
          $itemPosition = 1;
          $itemsLimit = 1;

          while (have_rows('banners_after_wc_page', 'options')) :
            the_row();

            if ($itemPosition > $offset) :
              if ($itemsLimit <= $limit) :

                $link = get_sub_field("link_banner");

                if ($link) :
                  $link_url    = $link['url'];
                  $link_title  = $link['title'] ? $link['title'] : 'Veja mais';
                  $link_target = $link['target'] ? $link['target'] : '_parent';
                ?>

                  <div class="col">
                    <a href="<?php echo esc_url($link_url); ?>" target="<?php echo esc_attr($link_target); ?>" title="<?php echo esc_html($link_title); ?>">
                      <img class="lazyload shadow text-center" data-src="<?= get_sub_field('image') ?>" alt="Banner">
                    </a>
                  </div><!-- /.col -->

                <?php else : ?>

                  <div class="col">
                    <img class="lazyload shadow text-center" data-src="<?= get_sub_field('image') ?>" alt="Banner">
                  </div><!-- /.col -->

                <?php endif;

              endif;

              $itemsLimit++;
            endif;

            $itemPosition++;
          endwhile; ?>
        </div><!-- /.row -->
      </div><!-- /.container -->
    </section><!-- End section -->

  <?php endif;
}

add_action('theme_woocommece_after_container', 'callback_show_product_banners', 10, 1);
add_action('theme_front_page_before_loop', 'callback_show_product_banners', 10, 1);
add_action('theme_front_page_after_loop', 'callback_show_product_banners', 20, 1);


/**
 * 
 * Added arrow in input quantity
 * 
 */
function arrow_qty_before() {
  echo '<button type="button" aria-label="decrescimo" class="arrow-qty decrescimo"><i class="fal fa-minus"></i></button>';
}

function arrow_qty_after() {
  echo '<button type="button" aria-label="acrescimo" class="arrow-qty acrescimo"><i class="fal fa-plus"></i></button>';
}

add_action('woocommerce_before_quantity_input_field', 'arrow_qty_before');
add_action('woocommerce_after_quantity_input_field', 'arrow_qty_after');


/**
 * theme_action_show_price
 * Remove o preço na listagem de produtos e no sumário da sua página, se não houver método de pagamento.
 *
 * @return boolean
 */
function theme_action_show_price() {

  if (THEME_DISABLE_PRODUCT_PRICE === true) {

    remove_action('woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10);
    remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_price', 10);
  }
}

add_action('init', 'theme_action_show_price', 99);


/**
 * theme_action_update_qty_cart
 * Atualiza o carrinho ao mudar a quantidade
 *
 * @return void
 */
function theme_action_update_qty_cart() {
  if (is_cart()) {  ?>
    <script type="text/javascript">
      jQuery('div.woocommerce').on('click', 'input.qty', function() {
        jQuery("[name='update_cart']").trigger("click");
      });
    </script>
  <?php
  }
}

add_action('wp_footer', 'theme_action_update_qty_cart');



/**
 * theme_action_vendacruzada_remove_hook
 * Ajustar itens de venda cruzada no carrinho.
 *
 * @return void
 */
function theme_action_vendacruzada_remove_hook() {
  remove_action('woocommerce_cart_collaterals', 'woocommerce_cross_sell_display');
}

add_action('woocommerce_cart_collaterals', 'theme_action_vendacruzada_remove_hook', 1);
add_action('woocommerce_after_cart', 'woocommerce_cross_sell_display', 1);



/**
 * remove_item_from_cart
 * Remove item from cart fragments
 *
 * @return void
 */
function remove_item_from_cart() {
  $cart_item_key = $_POST['cart_item_key'];

  if ($cart_item_key) {
    WC()->cart->remove_cart_item($cart_item_key);
    return true;
  }
  return false;
}

add_action('wp_ajax_remove_item_from_cart', 'remove_item_from_cart');
add_action('wp_ajax_nopriv_remove_item_from_cart', 'remove_item_from_cart');



/**
 * Produtos (Destaques da semana)
 * front-page.php
 * 
 */
function theme_action_section_destaque() {
  get_template_part('template-parts/front-page/section', 'destaques');
}

add_action('theme_front_page_after_loop', 'theme_action_section_destaque', 10);


/**
 * theme_custom_logo_setup
 * Filter at inc/filters/theme_logo_client
 *
 * @return mixed
 */
function theme_custom_logo_setup() {
  $defaults = array(
    'height'               => 48,
    'width'                => 136,
    'flex-height'          => true,
    'flex-width'           => true,
    'header-text'          => array('site-title', 'site-description'),
    'unlink-homepage-logo' => true,
  );

  add_theme_support('custom-logo', $defaults);
}
add_action('after_setup_theme', 'theme_custom_logo_setup');


/**
 * Register widget area.
 *
 * @link https://developer.wordpress.org/themes/functionality/sidebars/#registering-a-sidebar
 */

function theme_widgets_init() {
  register_sidebar(array(
    'name'          => __('Widget lateral esquerdo', 'wcstartertheme'),
    'id'            => 'sidebar-left-page',
    'description'   => __('Adicione widgets aqui para aparecer no seu tema.', 'wcstartertheme'),
    'before_widget' => '<aside id="%1$s" class="widget %2$s">',
    'after_widget'  => '</aside>',
    'before_title'  => '<h2 class="widget-title">',
    'after_title'   => '</h2>',
  ));
}
add_action('widgets_init', 'theme_widgets_init');


/**
 * Registration of website menus
 * mytheme_register_nav_menu
 *
 * @return void
 */
if (!function_exists('mytheme_register_nav_menu')) {

  function mytheme_register_nav_menu() 
  {
    register_nav_menus(array(
      'header_left_menu'  => __('Menu superior - Cabeçalho', 'wcstartertheme'),
      'footer_menu'       => __('Menu 1 - Rodapé', 'wcstartertheme'),
      'footer_after_menu' => __('Menu 2 - Rodapé', 'wcstartertheme'),
      'social_links_menu' => __('Redes sociais', 'wcstartertheme'),
    ));
  }
  add_action('after_setup_theme', 'mytheme_register_nav_menu', 0);
}
