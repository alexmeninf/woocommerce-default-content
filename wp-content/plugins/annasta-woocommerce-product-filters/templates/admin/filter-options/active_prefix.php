<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
              <tr>
                <td>
                  <label for="<?php echo $filter->prefix; ?>active_prefix"><?php esc_html_e( 'Filter value prefix', 'annasta-filters' ); ?></label>
                  <span class="woocommerce-help-tip" data-tip="<?php esc_attr_e( 'You will see this prefix in front of the filter value in active filter badges and filter label hover tips. Leave blank if not needed.', 'annasta-filters' ); ?>"></span>
                </td>
                <td><input name="<?php echo $filter->prefix; ?>active_prefix" id="<?php echo $filter->prefix; ?>active_prefix" type="text" value="<?php echo esc_attr( $value ); ?>"></td>
              </tr>