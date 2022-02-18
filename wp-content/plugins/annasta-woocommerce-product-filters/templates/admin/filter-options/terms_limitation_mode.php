<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
              <tr class="awf-hide-for-daterangepicker">
                <td>
                  <label><?php esc_html_e( 'Filter items control', 'annasta-filters' ); ?></label>
                  <span class="woocommerce-help-tip" data-tip="<?php esc_attr_e( 'To disable items control, make sure that you removed all items from the limitations list.', 'annasta-filters' ); ?>"></span>
                </td>
                <td class="awf-terms-limitations-container">
<?php
  $select_options = array(
    'name' => $filter->prefix . 'terms_limitation_mode',
    'id' => $filter->prefix . 'terms_limitation_mode',
    'class' => 'terms-limitation-mode-select',
    'selected' => empty( $filter->settings['terms_limitation_mode'] ) ? 'exclude' : $filter->settings['terms_limitation_mode'],
    'options' => array(
      'exclude' => __( 'Exclude from list', 'annasta-filters' ),
      'include' => __( 'Manual selection', 'annasta-filters' ),
      'active' => __( 'Active filters', 'annasta-filters' ),
    )
  );

  echo A_W_F::$admin->build_select_html( $select_options );
?>
                  <?php echo A_W_F::$admin->build_terms_limitations( $filter ); ?>
                </td>
              </tr>