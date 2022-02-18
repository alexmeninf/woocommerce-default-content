<div class="shopping-cart cart-fragments" 
  data-totals-cart="<?= THEME_DISABLE_PRODUCT_PRICE === false ? WC()->cart->total : '00.00' ?>"
  data-currency-symbol="<?php echo get_woocommerce_currency_symbol(); ?>">
  <div class="theme-products theme-product-small">

    <?php if (WC()->cart->is_empty()) : ?>

      <div class="theme-col">
        <div class="empty-cart-txt">
          <img data-src="<?= THEMEROOT ?>/assets/img/illustrations/empty-cart.png" alt="<?= __('Carrinho vazio', 'wcstartertheme') ?>" class="img-illustration lazyload">
          <span class="d-block my-4"><?php _e('Seu carrinho está vazio!', 'wcstartertheme') ?></span>
        </div>
      </div>

    <?php else : ?>

      <?php
      foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) :
        $_product   = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);
        $product_id = apply_filters('woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key);

        if ($_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters('woocommerce_cart_item_visible', true, $cart_item, $cart_item_key)) :

          $product_permalink = apply_filters('woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink($cart_item) : '', $cart_item, $cart_item_key);
          $thumbnail         = apply_filters('woocommerce_cart_item_thumbnail', $_product->get_image('thumbnail'), $cart_item, $cart_item_key);
          $product_name      = apply_filters('woocommerce_cart_item_name', sprintf('<a href="%s">%s</a>', esc_url($product_permalink), $_product->get_title()), $cart_item, $cart_item_key);
          $product_price     = apply_filters('woocommerce_cart_item_price', WC()->cart->get_product_price($_product), $cart_item, $cart_item_key);
          $product_subtotal  = $cart_item['quantity'] * $cart_item['data']->get_price();
          $attributes        = '';
          //Variation
          $attributes .= $_product->is_type('variable') || $_product->is_type('variation') ? wc_get_formatted_variation($_product) : '';
          // Meta data
          if (version_compare(WC()->version, '3.3.0', "<")) {
            $attributes .=  WC()->cart->get_item_data($cart_item);
          } else {
            $attributes .=  wc_get_formatted_cart_item_data($cart_item);
          }
      ?>
          <div class="theme-col item" data-cart-item-key="<?= $cart_item_key; ?>">
            <div class="theme-product">
              <!-- Remove -->
              <button class="theme-close-item remove-item"><i class="fal fa-times"></i></button>

              <!-- Image -->
              <a href="<?php echo $product_permalink; ?>" class="theme-product-image">
                <?php echo $thumbnail; ?>
              </a>
              
              <!-- product-info -->
              <div class="theme-product-description">
                <a href="<?php echo $product_permalink; ?>" class="theme-product-title theme-link">
                  <?php echo $product_name; ?> 
                </a>

                <div class="mt-2 attributes">
                  <?php echo $attributes ? $attributes : ''; ?>
                </div>

                <span class="theme-product-price" data-total-price="<?= THEME_DISABLE_PRODUCT_PRICE === false ? $product_subtotal : '00.00' ?>">
                  <span class="qnt"><?php echo $cart_item['quantity']; ?></span>                 
                  <?php 
                  if ( THEME_DISABLE_PRODUCT_PRICE === false ) :
                    echo ' x <span class="amount">' . $product_price . '</span>';
                  else:
                    echo $cart_item['quantity'] == 1 ? ' item' : ' itens';
                  endif; 
                  ?>
                </span>
              </div>
            </div>
          </div><!-- End of Product item -->

        <?php endif; ?>
      <?php endforeach; ?>
    <?php endif; ?>
  </div><!-- /.theme-products -->

  <div class="sc-footer">
    <?php 
    if ( THEME_DISABLE_PRODUCT_PRICE === false ) :
      echo '<div class="subtotal">Subtotal: <span class="cart_contents_subtotal">' . wc_price(WC()->cart->total, array(false, '', ',', '.')) . '</span></div>';
    endif; 
    ?>

    <?php if (WC()->cart->get_cart_contents_count() > 0) : ?>
      <a href="<?= wc_get_checkout_url() ?>" class="button alt w-100"><span><?= __('Finalizar pedido', 'wcstartertheme'); ?></span> <i class="fal fa-long-arrow-right ms-3"></i></a>
      <a href="<?= wc_get_cart_url(); ?>" class="button w-100 mt-2"><span><?= __('Carrinho', 'wcstartertheme'); ?></span> <i class="fal fa-long-arrow-right ms-3"></i></a>
    <?php else: ?>
      <a href="<?= get_permalink(wc_get_page_id('shop')) ?>" class="button w-100"><span><?= __('Fazer compras', 'wcstartertheme'); ?></span> <i class="fal fa-long-arrow-right ms-3"></i></a>
    <?php endif; ?>
  </div><!-- /.sc-cart -->
</div><!-- /.shopping-cart -->