<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) 
  exit;


/**
 * callback_theme_footer
 * Footer copyright
 * 
 */
function callback_theme_footer() { ?>
  
    <div class="copyrights text-center text-xl-start mt-5 mb-0">
      <div class="row">
        <div class="col-lg-6 text-center text-lg-start">

          <?php do_action('footer_before_links'); ?>

          <p>&copy; <?= date('Y') . ' ' . get_bloginfo('name') ?>. <?php _e('Todos os direitos reservados.', 'starterthemechild') ?></p>
        </div>

        <div class="col-lg-6 text-center text-lg-end">

          <?php do_action('footer_before_developer'); ?>

          <p class="developer js-dev-footer"><?php _e('Desenvolvido por', 'starterthemechild') ?> <a href="https://inovany.com.br" target="_blank" rel="noopener" title="iNova">
              <img src="https://assets.comet.com.br/assets/default/logo-inova-dark.png" alt="Inova" height="24">
            </a>
            <a href="https://bluelizard.com.br" target="_blank" rel="noopener" title="Blue Lizard">
              <img src="https://assets.comet.com.br/assets/default/logo-bluelizard-default.png" alt="Blue Lizard" height="24">
            </a>
          </p>
        </div>
      </div>
    </div>


<?php
}

add_action('theme_footer_open', 'callback_theme_footer', 50);