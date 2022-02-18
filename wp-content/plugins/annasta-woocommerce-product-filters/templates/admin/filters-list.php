<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>

<h3><?php esc_html_e( 'Preset Filters', 'annasta-filters' ) ?></h3>

<table class="widefat awf-preset-filters-table">
  <thead>
    <tr>
      <th>
<?php
  echo A_W_F::$admin->build_select_html( array( 'id' => 'awf_filters_select', 'options' => $filters_select, 'selected' => null ) );
?>
      </th>
      <th class="awf-buttons-column awf-add-btn-column">
        <button id="awf-add-filter" class="button button-secondary awf-icon awf-add-btn" type="button" title="<?php esc_attr_e( 'Add filter', 'annasta-filters' ); ?>"></button>
      </th>
    </tr>
<?php if( ! empty( $non_latin_slugs ) ) : ?>
    <tr>
      <th colspan="2">
        <div class="awf-info-notice">
          <?php echo sprintf( __( 'The following taxonomies can not be set as filters because they seem to contain non-latin slugs: %1$s. Please visit our %2$s to learn about the ways to address this issue.', 'annasta-filters' ), '<strong>' . implode( ', ', $non_latin_slugs ) . '</strong>', '<a href="https://annasta.net/plugins/annasta-woocommerce-product-filters/troubleshoot/#non-latin_slugs" target="_blank">Troubleshoot Guide</a>' ); ?>
        </div>
      </th>
    </tr>
<?php endif; ?>
  </thead>
  <tbody class="ui-sortable">
<?php 
  foreach( A_W_F::$presets[$this->preset->id]['filters'] as $filter_id => $position ) { 
    $filter = new A_W_F_filter( $this->preset->id, $filter_id );
    include( A_W_F_PLUGIN_PATH . 'templates/admin/filter.php' );
  }
?>
  </tbody>
  <tfoot>
  </tfoot>
</table>
