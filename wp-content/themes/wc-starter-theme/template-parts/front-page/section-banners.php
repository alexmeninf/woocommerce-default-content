<?php 
if ( !class_exists('ACF') ) 
  return;

if (have_rows('banners')) : ?>
  <section id="main-banners">
    <div class="owl-carousel owl-theme" data-nav="true" data-dots="true" data-xs="1" data-autoplay="false">
      
      <?php 
      while (have_rows('banners')) :
        the_row(); 
        
        $link = get_sub_field("link_banner"); 

        if ($link) :
          $link_url    = $link['url'];
          $link_title  = $link['title'] ? $link['title'] : 'Veja mais';
          $link_target = $link['target'] ? $link['target'] : '_parent'; 
        ?>

          <div class="item">
            <a href="<?php echo esc_url( $link_url ); ?>" target="<?php echo esc_attr( $link_target ); ?>" title="<?php echo esc_html( $link_title ); ?>">
              <img src="<?= get_sub_field('imagem_mobile') ?>" alt="banner" class="rounded-0 img-fluid d-md-none">
              <img src="<?= get_sub_field('imagem_tablet') ?>" alt="banner" class="rounded-0 img-fluid d-none d-md-block d-lg-none">
              <img src="<?= get_sub_field('imagem_desktop') ?>" alt="banner" class="rounded-0 img-fluid d-none d-lg-block">
            </a>
          </div>

        <?php else: ?>

          <div class="item">
            <img src="<?= get_sub_field('imagem_mobile') ?>" alt="banner" class="rounded-0 img-fluid d-md-none">
            <img src="<?= get_sub_field('imagem_tablet') ?>" alt="banner" class="rounded-0 img-fluid d-none d-md-block d-lg-none">
            <img src="<?= get_sub_field('imagem_desktop') ?>" alt="banner" class="rounded-0 img-fluid d-none d-lg-block">
          </div>

        <?php endif; ?>
      <?php endwhile; ?>

    </div>
  </section>
<?php endif; ?>