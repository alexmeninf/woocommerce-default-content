<?php

// Exit if accessed directly
if ( ! defined('ABSPATH') )
  exit;
  
?>

<!DOCTYPE html>
<html class="no-js" <?php language_attributes(); ?>>

<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
  <meta name="urlajax" content="<?php bloginfo('url') ?>">
  <meta name="themeroot" content="<?= THEMEROOT ?>">

  <title><?php echo is_front_page() ? '' : wp_title('', false) . ' | '; bloginfo('name'); ?></title>

  <?php wp_head(); ?>

</head>

<body <?php body_class(!is_woocommerce() ? 'woocommerce' : '') ?> data-color-scheme="light">

  <?php wp_body_open(); ?>

  <div id="page">
    
    <?php 
    /**
     * Header action
    */
    do_action('theme_header_open');