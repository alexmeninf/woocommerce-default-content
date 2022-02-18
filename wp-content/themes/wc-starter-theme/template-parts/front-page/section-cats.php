<section class="<?= section_class('pt-front-page', false, false) ?>">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-10 col-lg-9">
        <h3 class="text-lg-center">Veja nossas categorias</h3>
        
        <ul class="theme-products slide-categories align-categories owl-carousel" data-nav="true" data-dots="false" data-xxs="2" data-xs="3" data-sm="4" data-md="5" data-lg="6" data-xl="7" data-margin="2" data-padding="20">
          <?php menu_hierarchical('product_cat', false); ?>
        </ul>
      </div>
    </div>
  </div>
</section>