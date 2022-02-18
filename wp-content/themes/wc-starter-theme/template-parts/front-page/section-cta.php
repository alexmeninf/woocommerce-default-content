<section class="container px-2 spacing pb-0">
  <div class="theme-cta">
    <div class="row align-items-center">

      <div class="col-lg-7">
        <h2 class="cta-title">
          <?= !is_user_logged_in() ? __('Ainda não possui uma conta?', 'wcstartertheme') : __('Veja o seu perfil ou desconecte.', 'wcstartertheme') ?>
        </h2>
      </div>

      <div class="col-lg-5">
        <div class="btn-settings">
          <?php if (!is_user_logged_in()) : ?>

            <a href="<?php the_permalink_register(); ?>" class="button btn-active"><span><?php echo __('Criar conta', 'wcstartertheme') ?></span></a>
            <a href="<?php the_permalink(get_option('woocommerce_myaccount_page_id')); ?>" class="button"><?php echo __('Fazer login', 'wcstartertheme') ?></a>

          <?php else : ?>

            <a href="<?php the_permalink(get_option('woocommerce_myaccount_page_id')); ?>" class="button btn-active"><i class="me-1 fad fa-user"></i> <?php echo __('Minha conta', 'wcstartertheme') ?></a>
            <a href="<?= wp_logout_url(get_bloginfo('url')) ?>" class="button"><i class="me-1 fad fa-sign-out-alt"></i> <?php echo __('Sair', 'wcstartertheme') ?></a>

          <?php endif; ?>
        </div><!-- /.btn-settings -->
      </div><!-- /.col-lg-5 -->

    </div><!-- /.row -->
  </div><!-- /.theme-cta -->
</section>