<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) 
  exit;

include get_template_directory() . '/inc/_framework/framework.php';
include get_stylesheet_directory() . '/inc/custom-theme.php';
include get_stylesheet_directory() . '/inc/user-rest-api.php';

/**
 * CSS Files
 */
new_css('bootstrap', 'assets/plugins/bootstrap/css/bootstrap.css');
new_css('fontawesome-default', 'assets/plugins/fontawesome/css/all.min.css');
new_css('owl-carousel', 'assets/plugins/owl-carousel/css/owl.carousel.min.css');
new_css('parent-style', 'style.css');
new_css('main-default', 'assets/css/main.css', false, true);
new_css('child-style', 'style.css', false, true);

/**
 * Use CSS Default
 */
use_css('bootstrap');
use_css('fontawesome-default');
use_css('owl-carousel');
use_css('parent-style');
use_css('main-default');
// use_css('child-style');


/**
 * Scripts Files 
 */
new_js('utilities', 'assets/plugins/util.js');
new_js('popper', 'assets/plugins/bootstrap/js/popper.min.js');
new_js('bootstrap-default', 'assets/plugins/bootstrap/js/bootstrap.min.js');
new_js('jquery.mask-default', 'assets/plugins/jquery-mask/js/jquery.mask.min.js');
new_js('lazyload-default', 'assets/plugins/lazyload.min.js');
new_js('sweetalert-default', 'assets/plugins/sweetalert/sweetalert2.all.min.js');
new_js('smooth-scroll', 'assets/plugins/smooth-scroll.js');
new_js('owl-carousel', 'assets/plugins/owl-carousel/js/owl.carousel.min.js');
new_js('wc-cart', 'assets/js/cart.js');
new_js('main-default', 'assets/js/main.js', true, true);

/**
 * Use JS Default
 */
use_js('utilities');
use_js('popper');
use_js('bootstrap-default');
use_js('jquery.mask-default');
use_js('lazyload-default');
use_js('sweetalert-default');
use_js('smooth-scroll');
use_js('owl-carousel');
use_js('wc-cart');
use_js('main-default');
