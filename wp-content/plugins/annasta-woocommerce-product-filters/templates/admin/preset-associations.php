<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>

<table class="widefat awf-associations-table">
<?php if( ! empty( $associations_select ) ): ?>
  <thead>
    <tr>
      <th>
<?php
$associations_select = array( 'id' => 'awf-associations-select', 'options' => $associations_select, 'selected' => null );
echo A_W_F::$admin->build_select_html( $associations_select );

echo '<select id="awf-taxonomy-associations-select" style="display:none;"></select>'
?>
      </th>
      <th class="awf-buttons-column awf-add-btn-column">
        <button type="button" id="awf-add-association-btn" class="button button-secondary awf-icon awf-add-btn" title="<?php esc_attr_e( 'Add page association', 'annasta-filters' ); ?>"></button>
      </th>
    </tr>
  </thead>
<?php endif; ?>
  <tbody>
<?php foreach( $preset_associations as $association_id => $label ) : ?>
    <tr>
      <td class="awf-association-name"><?php echo esc_html( $label ); ?></td>
      <td class="awf-buttons-column">
        <button type="button" class="button button-secondary awf-icon awf-delete-btn awf-delete-association-btn" title="<?php esc_attr_e( 'Remove page association', 'annasta-filters' ); ?>" data-association="<?php echo esc_attr( $association_id ); ?>"></button>
      </td>
    </tr>
<?php endforeach; ?>
  </tbody>
</table>
