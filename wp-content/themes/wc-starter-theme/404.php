<?php

// Exit if accessed directly
if ( ! defined('ABSPATH') )
  exit;


get_header(); ?>


<div class="<?php echo section_class('page-404') ?>">
	<div class="container">
		<div class="row justify-content-center">
			<div class="col-md-7 col-lg-6 text-center py-5">
				<!-- Image -->
				<?php echo illustration_page('404') ?>
				<!-- Title -->
				<h1 class="headline-5 wow fadeInUp " data-wow-delay=".2s"><?php _e('Página não encontrada', 'wcstartertheme') ?></h1>
				<!-- Description -->
				<p class="wow fadeInUp" data-wow-delay=".3s"><?php _e('Desculpe, mas parece que a página que você acessou não existe ou pode ter sido removida.', 'wcstartertheme') ?></p>
				<!-- Button -->
				<a 
					href="<?php bloginfo('url') ?>" 
					class="btn-theme wow fadeInUp" 
					data-wow-delay=".4s"
					style="--btn-border-radius: 50rem"
					>
					<?php _e('Voltar ao Início', 'wcstartertheme') ?>
				</a>
			</div>
		</div>
	</div>
</div>


<?php get_footer(); ?>
