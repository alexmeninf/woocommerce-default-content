<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) 
  exit;

/**
 * Add support to WooCommerce
 */
add_theme_support('woocommerce');



/**
 * Definições do tema e funcionalidades 
 */

// Habilitar preload nas páginas
define('THEME_ENABLE_PRELOAD', false);

// Gerar meta preload na requisição das fontes do tema
define('THEME_ENABLE_NAVBAR', true);

// Gerar meta preload na requisição das fontes do tema
define('THEME_ENABLE_PRELOAD_FONT', true);

// Suporte para troca de cor do tema
define('ENABLE_COLOR_SCHEME_MODE', true);

// Ativar construtor de formulários
define('ENABLE_FORM_BUILDER', false);

/**
 * Para WooCommerce
 */

// Habilitar opção para método de pagamento
define('CART_NEEDS_PAYMENT', false);

// Desativa a exibição de preços na loja
define('THEME_DISABLE_PRODUCT_PRICE', false);

// Habilitar link customizado para a página de cadastro
define('THEME_CUSTOM_REGISTER_PAGE', false);


/**
 * Require functions
 */
require 'inc/_framework/framework.php';
require 'inc/colors.php';
require 'inc/dark-mode.php';
require 'inc/filters.php';
require 'inc/form/form.php';
require 'inc/hooks.php';
require 'inc/theme.php';

/**
 * WooCommerce Functions
 */
require 'inc/wc/add-class-to-input.php';
require 'inc/wc/wc_product_purchase_price_field.php';
require 'inc/wc/remove-links-content-product.php';
require 'inc/wc/enqueue-plugin-scripts.php';
require 'inc/wc/my-account-navigation-links.php';
require 'inc/wc/empty-cart.php';
require 'inc/wc/update-cart-fragments.php';
require 'inc/wc/wc-required-fields.php';
require 'inc/wc/custom-illustration-pages.php';
require 'inc/wc/filter-my-account-login.php';
require 'inc/wc/filter-purchase-steps.php';

require 'inc/widgets/menu_cat_hierarchical.php';
require 'inc/widgets/navbar-shop.php';
require 'inc/widgets/menu.php';

require 'inc/loop/wc-loop-products.php';


/**
 * Replace and detect social links
 * theme_social_menu
 *
 * @return void
 */
function theme_social_menu($menu_class = '') {

  $menu = wp_nav_menu(
    array(
      'theme_location' => 'social_links_menu',
      'fallback_cb'    => false,
      'item_spacing'   => 'discard',
      'menu_id'        => 'social-menu',
      'menu_class'     => $menu_class,
      'echo'           => false,
    )
  );

  // Buscar tags
  $tag_parent = 'li';
  $pattern_parent = "#<\s*?$tag_parent\b[^>]*>(.*?)</$tag_parent\b[^>]*>#s";
  preg_match_all($pattern_parent, $menu, $scripts_li); // Obter toda lista

  $tag_child = 'a';
  $pattern_child = "#<\s*?$tag_child\b[^>]*>(.*?)</$tag_child\b[^>]*>#s";
  preg_match_all($pattern_child, $menu, $scripts_a); // Obter todos links

  $new_anchor = [];

  foreach ($scripts_a[1] as $key => $value) {
    // Remover text em <a></a>
    $anchor_cleared = preg_replace('#(<a.*?>).*?(</a>)#', '$1$2', $scripts_a[0][$key]);
    // Adiciona atributos
    $new_anchor[$key] = str_replace('<a ', '<a aria-label="Social link" target="_blank" rel="noopener noreferrer" title="' . $scripts_a[1][$key] . '" ', $anchor_cleared);
  }

  // $li = []; Create an array
  $lis = '';

  // Substitui todo conteúdo em cada li pelas anchoras novas
  foreach ($scripts_li[0] as $key => $value) {
    $lis .= preg_replace('#(<li.*?>).*?(</li>)#', '$1' . $new_anchor[$key] . '$2', $scripts_li[0][$key]);
  }

  // Atualiza a lista
  $menu = preg_replace('#(<ul.*?>).*?(</ul>)#', '$1' . $lis . '$2', $menu);

  echo $menu;
}


/**
 * page_class
 * Added new class names to specify the page
 *
 * @return string
 */
function page_class() {

  $class = '';

  if (is_checkout()) {
    $class = 'checkout';
  } elseif (!is_user_logged_in() && is_page(get_option('woocommerce_myaccount_page_id'))) {
    $class = 'login-register';
  } elseif (is_user_logged_in() && is_page(get_option('woocommerce_myaccount_page_id'))) {
    $class = 'my-account';
  }

  return $class;
}


/**
 * is_active_filter_products
 * 
 * @version 1.1
 *
 * @return boolean
 */
function is_active_filter_products() {

  if (class_exists('A_W_F_frontend')) {

    if (A_W_F_frontend::get_instance()->filter_on || is_search()) {
      return true;
    }
  }

  return false;
}


/**
 * is_store_page
 * 
 * Verifica se é alguma página padrão da loja
 *
 * @return boolean
 */
function is_store_page() {

  if (class_exists('WooCommerce')) {
    if (is_woocommerce() || is_shop() || is_product_category() || is_product_tag() || is_product() || is_cart() || is_checkout() || is_account_page() || is_wishlist_page()) {
      return true;
    }
  }

  return false;
}


/**
 * is_wishlist_page
 *
 * @return boolean
 */
function is_wishlist_page() {

  // Wishlist page
  if (function_exists('tinv_url_wishlist_default')) {
    $id_page = apply_filters('wpml_object_id', tinv_get_option('page', 'wishlist'), 'page', true);
  }

  return is_page($id_page);
}


/**
 * illustration_page
 * Exibe uma ilustração padrão para páginas sem resultados.
 *
 * @param  string $show Nome da página
 * 
 * @return void
 */
function illustration_page($show) {

  $dir = THEMEROOT . '/assets/img/illustrations';

  switch ($show) {
    case 'cart':
      return  '<img data-src="' . $dir . '/empty_cart.svg" alt="Carrinho vazio" class="img-illustration lazyload">';
      break;

    case 'search':
      return  '<img data-src="' . $dir . '/not_found_search.svg" alt="illustration" class="img-illustration lazyload">';
      break;

    case 'wishlist':
      return  '<img data-src="' . $dir . '/wishlist.svg" alt="illustration" class="img-illustration lazyload">';
      break;

    case '404':
      return  '<img data-src="' . $dir . '/404_page.svg" alt="illustration" class="img-illustration lazyload">';
      break;

    default:
      return __('Nenhuma ilustração foi selecionada.', 'wcstartertheme');
      break;
  }
}


/**
 * search_form_theme
 *
 * @return void
 */
function search_form_theme() {

  if (class_exists('DGWT_WC_Ajax_Search')) {
    echo do_shortcode('[fibosearch]');
  } else {
    get_search_form();
  }
}


/**
 * the_permalink_register
 * URL da página de registro
 *
 * @return string
 */
function the_permalink_register() {

  if (THEME_CUSTOM_REGISTER_PAGE) {

    if (is_page(array(get_option('woocommerce_myaccount_page_id'), 'registrar'))) {
      echo get_permalink(get_page_by_path('registrar'));
    } else {
      echo get_permalink(get_page_by_path('registrar')) . '?redirect_to=' . get_permalink();
    }
  } else {
    if (is_page(get_option('woocommerce_myaccount_page_id'))) {
      echo get_permalink(get_option('woocommerce_myaccount_page_id')) . '?f=register';
    } else {
      echo get_permalink(get_option('woocommerce_myaccount_page_id')) . '?f=register&redirect_to=' . get_permalink();
    }
  }
}


/**
 * custom_redirects
 *
 * @return wp_redirect
 */
function custom_redirects() {
  
  // Página de registro customizada
  if ( THEME_CUSTOM_REGISTER_PAGE === false && is_page('registrar') ) {

    wp_redirect( get_permalink(get_option('woocommerce_myaccount_page_id')) . '?f=register' );
    die;

  } else {

    if ( is_user_logged_in() && is_page('registrar') ) {

      wp_redirect( get_permalink(get_option('woocommerce_myaccount_page_id')) );
      die;
      
    }
  }

}

add_action( 'template_redirect', 'custom_redirects' );


/**
 * Mostra uma mensagem generica caso não for encontrado informações do shortcode de produtos.
 *
 * @param  string $output_shortcode - Coloque o 'do_shorcode' aqui.
 * @param  string $class - Adicione classes CSS no wrap da sessão
 * 
 * @return mixed
 */
function theme_shortcode_products($output_shortcode, $shortcode_name, $class = '') {
    
  if (shortcode_exists($shortcode_name) && do_shortcode($output_shortcode) !== '') {

    echo do_shortcode($output_shortcode);

  } else { ?>

    <div class="col-12 text-center <?= $class ?>">
      <?php _e('Sem informações de produtos.', 'wcstartertheme'); ?>
    </div>

    <?php for ($i = 0; $i < 5; $i++) : ?>

      <div class="col-6 col-md-4 col-lg-3 col-xxl-2 position-relative text-center">
        <div class="p-3 h-100 w-100" style="background-color: #f9f9f9;min-height:10vh"></div>
      </div>

    <?php endfor;
  }
}


/**
 * Header principal do site
 * Location: header.php
 * 
 * @return mixed
 */
function callback_header_main() {
  apply_filters('theme_harder_menu', null);
}

add_action('theme_header_open', 'callback_header_main', 10);


/**
 * Footer do site
 * Location: footer.php
 */
function callback_logo_footer() {

  apply_filters('theme_logo_client', 'footer');
}

add_action('theme_footer_open', 'callback_logo_footer', 10);



/**
 * Menu principal do footer
 * Location: footer.php
 */
function callback_menu_main_footer() {

  wp_nav_menu(array(
    'theme_location'  => 'footer_menu',
    'fallback_cb'     => false,
    'item_spacing'    => 'discard',
    'container_class' => 'hr-list'
  ));
}

add_action('theme_footer_open', 'callback_menu_main_footer', 20);



/**
 * Lista de informações de contato da loja
 * Location: footer.php
 */
function callback_menu_info_footer() {

  wp_nav_menu(array(
    'theme_location'  => 'footer_after_menu',
    'fallback_cb'     => false,
    'item_spacing'    => 'discard',
    'container_class' => 'business-info text-center text-xl-start'
  ));
}

add_action('theme_footer_open', 'callback_menu_info_footer', 30);



/**
 * Menu de Redes sociais
 * Location: footer.php
 */
function callback_menu_social_footer() {

  theme_social_menu('list-inline social-list before-icons text-center text-xl-start');
}

add_action('theme_footer_open', 'callback_menu_social_footer', 40);


/**
 * Front page action
 * 
 * Template para mostrar os recursos do tema e as atualizações.
 * 
 */
function theme_resources_for_the_developer() {
    
  /**
   * Banners (Main)
   */
  get_template_part('template-parts/front-page/section', 'banners');

  /**
   * Hook front page #1
   */
  do_action( 'theme_front_page_after_banners');

  /**
   * Categorias
   */
  get_template_part('template-parts/front-page/section', 'cats');

  /**
   * Hook front page #2
   */
  do_action( 'theme_front_page_after_cats');


  /**
   * Hook front page #3
   */
  do_action( 'theme_front_page_before_loop', array(
    'class'  => 'pt-front-page d-block overflow-hidden', 
    'offset' => 0, 
    'limit'  => 2
    ) 
  );

  /**
   * Produtos (Mais procurados da semana)
   */
  get_template_part('template-parts/front-page/section', 'most-viewed');


  /**
   * Hook front page #4
   */
  do_action( 'theme_front_page_after_loop', array(
    'class'  => 'pt-front-page',
    'offset' => 2, 
    'limit'  => 4
    ) 
  );

  /**
   * Produtos (Vistos recentemente)
   */
  get_template_part('template-parts/front-page/section', 'recently-viewed');

  /**
   * Hook front page #5
   */
  // get_template_part('template-parts/front-page/section', 'feed-insta');

  get_template_part('template-parts/front-page/section', 'cta');

}

add_action('content_front_page', 'theme_resources_for_the_developer');


/**
 * Botão compartilhar API system
 */
function share_link_sumarry(){
  get_template_part('template-parts/post/get_share-post');
}

add_filter('woocommerce_share', 'share_link_sumarry');
