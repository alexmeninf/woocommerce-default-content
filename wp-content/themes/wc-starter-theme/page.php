<?php

// Exit if accessed directly
if ( ! defined('ABSPATH') )
  exit;
  

get_header();

if ( have_posts() ) : while ( have_posts() ) : the_post();?>


<section class="<?php echo section_class('min-vh-adjustment ' . page_class(), true, false) ?>">
  <div class="container  <?= !is_store_page() ? 'px-4' : '' ?>">
  
		<?php echo apply_filters('theme_purchase_step_page', ''); ?>

    <?php the_content(); ?>
      
  </div>
</section>


<?php endwhile; endif; ?>
<?php get_footer(); ?>