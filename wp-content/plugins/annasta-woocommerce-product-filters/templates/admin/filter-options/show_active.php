<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
              <tr>
                <td>
                  <label for="<?php echo $filter->prefix; ?>show_active"><?php esc_html_e( 'Show active filters', 'annasta-filters' ); ?></label>
                  <span class="woocommerce-help-tip" data-tip="<?php esc_attr_e( 'This will display active filter badges under the filter title, or on top of filter if the title is omitted.', 'annasta-filters' ); ?>"></span>
                </td>
                <td><input type="checkbox" name="<?php echo $filter->prefix; ?>show_active" id="<?php echo $filter->prefix; ?>show_active" value="yes"<?php if( ! empty( $value ) ) { echo ' checked="checked"'; } ?>></td>
              </tr>