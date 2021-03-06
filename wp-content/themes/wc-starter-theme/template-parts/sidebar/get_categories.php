<?php 
/**
 * Documentation URl: https://developer.wordpress.org/reference/functions/get_categories/
 */

// Exit if accessed directly
if ( ! defined('ABSPATH') )
  exit;
?>


<ul class="sidebar-categories">
  <?php
  $categories = get_categories( array(
      'orderby' => 'name',
      'order'   => 'ASC'
  ) );
  
  foreach ( $categories as $category ) {
    $category_link = sprintf( 
      '<li><a href="%1$s" alt="%2$s">%3$s</a></li>',
      esc_url( get_category_link( $category->term_id ) ),
      esc_attr( sprintf( __( 'Visualizar todos os posts em %s', 'wcstartertheme' ), $category->name ) ),
      esc_html( $category->name )
    );
    
    echo $category_link;
  } ?>
</ul>