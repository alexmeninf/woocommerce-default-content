<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
              <tr>
                <td><label for="<?php echo esc_attr( $filter->prefix ); ?>autocomplete"><?php esc_html_e( 'Enable autocomplete', 'annasta-filters' ); ?></label></td>
                <td>
                  <input type="checkbox" name="<?php echo esc_attr( $filter->prefix ); ?>autocomplete" id="<?php echo esc_attr( $filter->prefix ); ?>autocomplete" value="yes"<?php if( ! empty( $value ) ) { echo ' checked="checked"'; } ?> class="awf-autocomplete-option">
                  <div class="awf-autocomplete-options-container<?php if( empty( $value ) ) { echo ' awf-collapsed'; } ?>">
                    
                    <div>
                      <input type="checkbox" name="<?php echo esc_attr( $filter->prefix ); ?>autocomplete_filtered" id="<?php echo esc_attr( $filter->prefix ); ?>autocomplete_filtered" value="yes"<?php if( ! empty( $filter->settings['type_options']['autocomplete_filtered'] ) ) { echo ' checked="checked"'; } ?>>
                      <label for="<?php echo esc_attr( $filter->prefix ); ?>autocomplete_filtered" class="awf-secondary-label"><?php esc_html_e( 'Apply filters to autocomplete results', 'annasta-filters' ); ?></label>
                      <span class="woocommerce-help-tip" data-tip="<?php esc_attr_e( 'Filter autocomplete results with the currently active shop filters. For example, when you have the Red color filter applied to your shop, the autocomplete will respect this when creating suggestions and entering \'apple\' in the search field will show suggestions only for the products that have Red color AND title / description containing the word \'apple\'.', 'annasta-filters' ); ?>"></span>
                    </div>
                    
                    <div>
                      <label for="<?php echo esc_attr( $filter->prefix ); ?>autocomplete_after"><?php esc_html_e( 'Begin autocomplete after', 'annasta-filters' ); ?></label>
                      <input type="text" name="<?php echo esc_attr( $filter->prefix ); ?>autocomplete_after" id="<?php echo esc_attr( $filter->prefix ); ?>autocomplete_after" value="<?php if( ! empty( $filter->settings['type_options']['autocomplete_after'] ) ) { echo esc_attr( $filter->settings['type_options']['autocomplete_after'] ); } else { echo '2'; } ?>" style="width: 5em;">
                      <label class="awf-secondary-label"><?php esc_html_e( 'characters', 'annasta-filters' ); ?></label>
                    </div>
                    
                    <div>
                      <label for="<?php echo esc_attr( $filter->prefix ); ?>autocomplete_results_count"><?php esc_html_e( 'Show the maximum of', 'annasta-filters' ); ?></label>
                      <input type="text" name="<?php echo esc_attr( $filter->prefix ); ?>autocomplete_results_count" id="<?php echo esc_attr( $filter->prefix ); ?>autocomplete_results_count" value="<?php if( ! empty( $filter->settings['type_options']['autocomplete_results_count'] ) ) { echo esc_attr( $filter->settings['type_options']['autocomplete_results_count'] ); } else { echo '5'; } ?>" style="width: 5em;">
                      <label class="awf-secondary-label"><?php esc_html_e( 'results', 'annasta-filters' ); ?></label>
                    </div>
                    
                    <div>
                      <input type="checkbox" name="<?php echo esc_attr( $filter->prefix ); ?>autocomplete_show_img" id="<?php echo esc_attr( $filter->prefix ); ?>autocomplete_show_img" value="yes"<?php if( ! empty( $filter->settings['type_options']['autocomplete_show_img'] ) ) { echo ' checked="checked"'; } ?>>
                      <label for="<?php echo esc_attr( $filter->prefix ); ?>autocomplete_show_img"><?php esc_html_e( 'Display products\' images', 'annasta-filters' ); ?></label>
                    </div>
                    
                    <div>
                      <input type="checkbox" name="<?php echo esc_attr( $filter->prefix ); ?>autocomplete_show_price" id="<?php echo esc_attr( $filter->prefix ); ?>autocomplete_show_price" value="yes"<?php if( ! empty( $filter->settings['type_options']['autocomplete_show_price'] ) ) { echo ' checked="checked"'; } ?>>
                      <label for="<?php echo esc_attr( $filter->prefix ); ?>autocomplete_show_price"><?php esc_html_e( 'Display products\' prices', 'annasta-filters' ); ?></label>
                    </div>
                    
                    <div>
                      <input type="checkbox" name="<?php echo esc_attr( $filter->prefix ); ?>autocomplete_view_all" id="<?php echo esc_attr( $filter->prefix ); ?>autocomplete_view_all" value="yes"<?php if( ! empty( $filter->settings['type_options']['autocomplete_view_all'] ) ) { echo ' checked="checked"'; } ?>>
                      <label for="<?php echo esc_attr( $filter->prefix ); ?>autocomplete_view_all"><?php esc_html_e( 'Show "View all results" link', 'annasta-filters' ); ?></label>
                    </div>
                    
                    <?php if( method_exists( A_W_F::$admin, 'display_premium_autocomplete_options' ) ) { A_W_F::$admin->display_premium_autocomplete_options( $filter ); } ?>
                  </div>
                </td>
              </tr>