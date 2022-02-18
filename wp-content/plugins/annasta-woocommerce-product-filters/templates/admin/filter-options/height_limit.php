<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
              <tr class="awf-hide-for-range-slider awf-hide-for-daterangepicker">
                <td>
                  <label for="<?php echo $filter->prefix; ?>height_limit"><?php esc_html_e( 'Limit filter height', 'annasta-filters' ); ?></label>
                  <span class="woocommerce-help-tip" data-tip="<?php esc_attr_e( 'Limit the height of filters\' container. Scroll bars will appear if the total height of filter items exceeds this setting. Leave blank or set to zero for no height limit.', 'annasta-filters' ); ?>"></span>
                </td>
                <td>
                  <input name="<?php echo $filter->prefix; ?>height_limit" id="<?php echo $filter->prefix; ?>height_limit" type="text" value="<?php echo esc_attr( $value ); ?>" style="width: 5em;">
                  <span><?php esc_html_e( 'pixels', 'annasta-filters' ); ?></span>
                </td>
              </tr>