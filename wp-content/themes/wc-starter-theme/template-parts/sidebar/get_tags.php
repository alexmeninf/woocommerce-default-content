<?php 
/**
 * Documentation URl: https://developer.wordpress.org/reference/functions/get_tags/
 */

// Exit if accessed directly
if ( ! defined('ABSPATH') )
  exit;


$tags = get_tags();

if ($tags) { 
  $html = '<div class="sidebar-tags">';

  foreach ( $tags as $tag ) {
    $tag_link = get_tag_link( $tag->term_id );
    
    $term = get_tag( $tag->term_id );
    $total_posts = $term->count;

    $html .= "<a href='{$tag_link}' class='{$tag->slug}'>";
    $html .= "{$tag->name} ({$total_posts})</a>";
  }
  $html .= '</div>'; 
  
  echo $html;
}
