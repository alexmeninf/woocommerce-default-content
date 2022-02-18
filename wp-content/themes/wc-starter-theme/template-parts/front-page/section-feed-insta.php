<?php if ( function_exists('wdi_feed') ) : ?>
  
  <section class="<?= section_class('min-vh-50', true, false) ?>">
    <div class="container">
      <div class="theme-offset-left">
        <div class="theme-section-side-title top-space rotate"><?php _e('Acompanhe no Instagram', 'wcstartertheme'); ?></div>

        <div class="theme-offset-right no-space">
          <div class="theme-newsletter-section">
            <h5 class="theme-title d-flex align-items-center">
              <i class="fab fa-instagram fa-3x me-3 gradient-insta"></i>
              <?php _e('Acompanhe no seu feed ofertas e destaques de produtos, dicas e informações exclusivas para você.', 'wcstartertheme'); ?>
            </h5>
          </div>
        </div>

        <?php echo wdi_feed(array('id' => '1')); ?>

      </div>
    </div>
  </section>

<?php  
else :
  echo '<p class="spacing container">O plugin <b>10Web Social Photo Feed</b> esta desativado.</p>';
endif; ?>