<?php

if ( ! defined( 'ABSPATH' ) ) 
  exit;


get_header();

if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>


<section class="<?php echo section_class('single-page') ?>">
	<div class="container">
		<div class="row">

			<div class="col">

				<?php the_content(); ?>
				
			</div><!-- /.col -->

		</div><!-- /.row -->
	</div><!-- /.container -->
</section>


<?php endwhile; endif; ?>
<?php get_footer(); ?>