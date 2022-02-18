<?php

if ( ! defined( 'ABSPATH' ) ) 
  exit;


get_header();

/**
 * Sobre a action (content_front_page)
 * 
 * Para exibir os posts, remova cada callback com remove_action 
 *  
 */

if (has_action('content_front_page')) {

  // Hook para conteúdo customizado.
  do_action('content_front_page');

} else {

  if ( have_posts() ) {

		echo '<ul class="container list-inline spacing">';

    while ( have_posts() ) {
      the_post();
      echo '<li>'. get_template_part('template-parts/post/content', 'post') .'</li>';
    }

		echo '</ul>';

  } else {

    _e('Nenhuma postagem foi encontrada.', 'wcstartertheme');

  }
}

get_footer();
