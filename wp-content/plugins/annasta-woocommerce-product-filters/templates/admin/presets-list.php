<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>

<h2><?php esc_html_e( 'Filter Presets', 'annasta-filters' ); ?></h2>

<table class="widefat awf-presets-table ui-sortable">
  <thead>
    <tr>
      <th></th>
      <th><?php esc_html_e( 'ID', 'annasta-filters' ) ?></th>
      <th><?php esc_html_e( 'Preset', 'annasta-filters' ) ?></th>
      <th><?php esc_html_e( 'Displays on', 'annasta-filters' ) ?></th>
      <th></th>
      <th></th>
		</tr>
  </thead>
	<tbody>
  <?php foreach( A_W_F::$presets as $preset_id => $preset ) : ?>
    <tr data-id="<?php echo esc_attr( $preset_id ); ?>">
      <td class="sort-handle" title="<?php esc_attr_e( 'Move up or down to arrange presets in a convenient order.', 'annasta-filters' ); ?>"></td>
      <td class="awf-preset-id-column"><?php echo esc_html( $preset_id ); ?></td>
      <td class="awf-preset-name-column"><?php echo esc_html( get_option( 'awf_preset_' . $preset_id . '_name', '' ) ); ?></td>
      <td class="awf-associations-column"><?php echo esc_html( $associations_by_preset[$preset_id] ); ?></td>
      <td class="awf-preset-comments-column">
<?php
  switch( get_option( 'awf_preset_' . $preset_id . '_display_mode' ) ) {
    case 'togglable':
      echo '<span class="awf-preset-comment" title="', esc_attr__( 'Controlled by "Filters" button', 'annasta-filters' ), '"><i class="fas fa-toggle-on"></i></span>';
      break;
    case 'togglable-on-s':
      echo '<span class="awf-preset-comment" title="', esc_attr__( 'Controlled by "Filters" button on smaller screens', 'annasta-filters' ), '"><i class="fas fa-compress-arrows-alt"></i></span>';
      break;
    default:
      break;
  }
?>
      </td>
      <td class="awf-buttons-column">
        <?php A_W_F::$admin->awf_display_preset_btns( $preset_id, $this->settings_url ); ?>
        <a class="button button-secondary awf-icon awf-edit-preset-btn" href="<?php echo esc_url( add_query_arg( array( 'awf-preset' => $preset_id ), $this->settings_url ) ); ?>" title="<?php esc_attr_e( 'Edit preset', 'annasta-filters' ); ?>"></a>
      </td>
    </tr>
  <?php endforeach; ?>
	</tbody>
	<tfoot>
		<tr>
			<td colspan="6">
        <?php A_W_F::$admin->awf_display_presets_list_footer( $this->settings_url ); ?>
			</td>
		</tr>
	</tfoot>
</table>