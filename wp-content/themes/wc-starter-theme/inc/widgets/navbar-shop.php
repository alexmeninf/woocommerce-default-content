<?php

/**
 * header_navbar_shop
 * 
 * Mostra um navigation com as principais páginas da loja
 * 
 * Valores disponíveis no array: 
 * parent_class - Adiciona classes na <ul>. Valor padrão (vazio)
 * children_class - Adiciona classes nas <li>. Valor padrão (vazio)
 * anchor_class - Adiciona classes em <li> > <a>. Valor padrão (vazio)
 * enable_icons - Permite ativar icones para cada link das páginas. Valor padrão (true)
 * node_parent - Remove o pai <ul> e deixa so os filhos
 * enable_dropdown - O menu vira um Dropdown Bootstrap
 * 
 * @param  mixed $args
 * @return void
 */

function header_navbar_shop($args = array())
{
  $parent_class    = ($args['parent_class'] !== null) ? $args['parent_class'] : '';
  $child_class     = ($args['children_class'] !== null) ? ('class="' . $args['children_class'] . '"') : '';
  $anchor_class    = ($args['anchor_class'] !== null) ? ('class="' . $args['anchor_class'] . '"') : '';
  $show_icons      = ($args['enable_icons'] !== null) ? boolval($args['enable_icons']) : true;
  $enable_dropdown = ($args['enable_dropdown'] !== null) ? boolval($args['enable_dropdown']) : false;

  if ($enable_dropdown) : ?>

    <li class="nav-item dropdown <?= $parent_class ?>">
      <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
        <?php echo __('Minha conta', 'wcstartertheme') ?>
      </a>      
      <div class="dropdown-menu" aria-labelledby="navbarDropdown">

  <?php elseif ($args['node_parent']) : ?>

    <ul class="<?= $parent_class ?>">

  <?php endif;

    if (is_user_logged_in()) :

      $i = 0;
      $icons = get_icons_navigation();

      foreach ( wc_get_account_menu_items() as $endpoint => $label ) : ?>
        <?= $enable_dropdown ? '' : '<li>' ?>
          <a <?= $anchor_class ?> href="<?php echo esc_url( wc_get_account_endpoint_url( $endpoint ) ); ?>">
            <?= $show_icons ? '<i class="'.$icons[$i].'"></i>' : '' ?>
            <?php echo esc_html( $label ); ?>
          </a>
        <?= $enable_dropdown ? '' : '</li>' ?>
      <?php $i++; endforeach; 
      
    else:  ?>
      
      <?= $enable_dropdown ? '' : '<li>' ?>
        <a <?= $anchor_class ?> href="<?php the_permalink_register(); ?>">
          <?= $show_icons ? '<i class="far fa-user"></i>' : '' ?>
          <?php echo __('Criar conta', 'wcstartertheme') ?>
        </a>
      <?= $enable_dropdown ? '' : '</li>' ?>

      <?= $enable_dropdown ? '' : '<li>' ?>
        <a <?= $anchor_class ?> href="<?php the_permalink(get_option('woocommerce_myaccount_page_id')); ?>">
          <?= $show_icons ? '<i class="far fa-sign-in-alt"></i>' : '' ?>
          <?php echo __('Fazer login', 'wcstartertheme') ?>
        </a>
      <?= $enable_dropdown ? '' : '</li>' ?>

    <?php endif;

  if ($enable_dropdown) : ?>

      </div><!-- ./dropdown-menu -->
    </li><!-- /.nav-item dropdown -->

  <?php elseif ($args['node_parent']) : ?>

    </ul><!-- /.menu defailt -->
    
  <?php endif; 

}
add_filter('theme_navbar_shop', 'header_navbar_shop', 10, 1);
