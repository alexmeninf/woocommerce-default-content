<?php

/**
 * Recursively get taxonomy and its children
 *
 * @param string $taxonomy
 * @param int $parent - parent term id
 * @return array
 */
function get_taxonomy_hierarchy( $taxonomy, $parent = 0 ) {
	// only 1 taxonomy
	$taxonomy = is_array( $taxonomy ) ? array_shift( $taxonomy ) : $taxonomy;
  $categories = array();

  // Retorna as categorias excluidas do menu
  if ( class_exists('ACF') ) {
    if (have_rows('categorias_destaque', 'options')) :
      while (have_rows('categorias_destaque', 'options')) :
        the_row();
        $categories[] = get_sub_field('categoria');
      endwhile;
    endif;
  }

	// get all direct descendants of the $parent
  $terms = get_terms( $taxonomy, array('hide_empty' => true, 'exclude' => $categories,  'parent' => $parent));
	// prepare a new array.  these are the children of $parent
	// we'll ultimately copy all the $terms into this new array, but only after they
	// find their own children
	$children = array();
	// go through all the direct descendants of $parent, and gather their children
	foreach ( $terms as $term ){
		// recurse to get the direct descendants of "this" term
		$term->children = get_taxonomy_hierarchy( $taxonomy, $term->term_id );
		// add the term to our new array
		$children[ $term->term_id ] = $term;
	}
	// send the results back to the caller
	return $children;
}

/**
 * Recursively get all taxonomies as complete hierarchies
 *
 * @param $taxonomies array of taxonomy slugs
 * @param $parent int - Starting parent term id
 *
 * @return array
 */
function get_taxonomy_hierarchy_multiple( $taxonomies, $parent = 0 ) {
	if ( ! is_array( $taxonomies )  ) {
		$taxonomies = array( $taxonomies );
	}

	$results = array();
	foreach( $taxonomies as $taxonomy ){
		$terms = get_taxonomy_hierarchy( $taxonomy, $parent );

		if ( $terms ) {
			$results[ $taxonomy ] = $terms;
		}
	}
	return $results;
}

/*
 * Exibe um menu baseado na hieraquia de niveis das categorias.
 */
function menu_hierarchical( $taxonomies, $dropdown = false ) {
  $list_terms = get_taxonomy_hierarchy( $taxonomies );
  $html = ''; //<ul class="">

  foreach ($list_terms as $term) {
    if ( $term->parent == 0) {
      // estrutura
      $html .= '<li class="%CLASS%"><a class="%CLASSLINK%" %ATTR% href="%HREF%">%IMAGE%' . $term->name . '</a>'; 
      
      // imagem
      $thumbnail_id = get_term_meta( $term->term_id, 'thumbnail_id', true );
      $image = wp_get_attachment_url( $thumbnail_id );
      if ($image) {
        $html  = str_replace('%IMAGE%', '<img class="img-cat" src="'.$image.'" alt="'.$term->name.'">', $html);
      } else {
        $html  = str_replace('%IMAGE%', '<img class="img-cat" src="https://via.placeholder.com/150/?text=Categoria" alt="categoria">', $html);
      }
      
      // subcategoria
      if (!empty($term->children)) {
        if ($dropdown) {
          $html = str_replace('%CLASS%', 'nav-item dropdown', $html);
          $html = str_replace('%CLASSLINK%', 'nav-link dropdown-toggle', $html);
          $html = str_replace('%ATTR%', 'data-bs-toggle="dropdown" aria-expanded="false" id="label'.$term->term_id.'"  role="button"', $html);
          $html = str_replace('%HREF%', '#', $html);
          $html .= '<ul class="dropdown-menu" aria-labelledby="label'.$term->term_id.'">';
        } else {
          $html = str_replace('%CLASS%', '', $html);
          $html = str_replace('%CLASSLINK%', '', $html);
          $html = str_replace('%ATTR%', '', $html);
          $html = str_replace('%HREF%', '#', $html);
          $html .= '<ul>';
        }

        $html .= get_parents_terms($term->children, 0, $dropdown);
        $html .= '</ul>';
        $html .= '</li>';
      } else {
        if ($dropdown) {
          $html = str_replace('%CLASS%', 'nav-item', $html);
          $html = str_replace('%CLASSLINK%', 'nav-link', $html);
        } else {
          $html = str_replace('%CLASS%', '', $html);
          $html = str_replace('%CLASSLINK%', '', $html);
        }
        $html = str_replace('%ATTR%', '', $html);
        $html = str_replace('%HREF%', get_term_link($term->term_id), $html);

        $html .= '</li>';
      }
    }
  }

  // $html .= '</ul>';

  echo $html;
}


/**
 * Função recursiva, verifica todos os subniveis das categorias
 */
function get_parents_terms($terms, $level = 0, $dropdown = false) {
  $html = '';

  foreach ($terms as $term) {
    $name = $term->name;

    // Se possui subcategoria
    if (!empty($term->children)) {
      if ($dropdown) {
        $html .= '<li class="nav-item dropdown">';
        $html .= '<a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" id="label'.$term->term_id.'" role="button">' . $name . '</a>';
        $html .= '<ul class="dropdown-menu" aria-labelledby="label'.$term->term_id.'">';
        $html .= get_parents_terms($term->children,  $level+1, $dropdown);
        $html .= '</ul>';
        $html .= '</li>';
      } else {
        $html .= '<li>';
        $html .= '<a>' . $name . '</a>';
        $html .= '<ul>';
        $html .= get_parents_terms($term->children,  $level+1, $dropdown);
        $html .= '</ul>';
        $html .= '</li>';
      }
      
    } else {
      $class = $dropdown ? 'class="dropdown-item"' : '';
      $html .= '<li><a '. $class .' href="' . get_term_link($term->term_id) . '">' . $name . '</a></li>';
    }
  }

  return $html;
}