<?php
// Add 'Preço de Custo' field
if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
	if ( is_admin() ) {
		$url = plugin_dir_url(__FILE__);
		//Quick edit product
		add_action('woocommerce_product_quick_edit_end', 'wc_add_products_purchase_price_quick_edit_field');
		add_action('woocommerce_product_quick_edit_save', 'wc_save_products_purchase_price_quick_edit_field');
		add_action('manage_product_posts_custom_column', 'wc_get_products_purchase_price_for_quick_edit_field', 99, 2);
		add_action('admin_enqueue_scripts', 'product_purchase_price_admin_scripts');
		// For simple products:
		// Add Field
		add_action('woocommerce_product_options_general_product_data', 'wc_add_product_purchase_price_field');
		//Save field
		add_action('woocommerce_process_product_meta', 'wc_save_product_purchase_price_field', 10, 2);
		// For variations:
		// Add Field
		add_action('woocommerce_product_after_variable_attributes', 'wc_add_variable_product_purchase_price_field', 10, 3);
		//Save field
		add_action('woocommerce_save_product_variation', 'wc_save_variable_product_purchase_price_field', 10, 2);
		// Text domain
		add_action('plugins_loaded', 'load_product_purchase_price_textdomain');
	}
}


function product_purchase_price_admin_scripts($hook) {
	if ('edit.php' != $hook) {
		return;
	}
	$url = plugin_dir_url(__FILE__);
	wp_register_script('wc_products_purchase_price_quick_edit_main', $url . 'assets/js/wc_purchase_price_quick_edit.js');
	wp_enqueue_script('wc_products_purchase_price_quick_edit_main');
}


function load_product_purchase_price_textdomain() {
	load_plugin_textdomain('products-purchase-price-for-woocommerce', false, plugin_basename(dirname(__FILE__)) . '/languages/');
}


function wc_add_product_purchase_price_field() {
	$currency = get_woocommerce_currency_symbol();

	woocommerce_wp_text_input(
		array(
			'id'            => '_purchase_price',
			'class'         => '',
			'wrapper_class' => 'pricing show_if_simple show_if_external',
			'label'         => 'Preço de custo' . " ($currency)",
			'data_type'     => 'price',
			'desc_tip'      => true,
			'description'   => 'Este é o preço de custo do produto.',
		)
	);
}


function wc_save_product_purchase_price_field($post_id, $post) {
	if (isset($_POST['_purchase_price'])) {
		$purchase_price = ($_POST['_purchase_price'] === '') ? '' : wc_format_decimal($_POST['_purchase_price']);
		update_post_meta($post_id, '_purchase_price', $purchase_price);
	}
}


/**
 * Create purchase price field for variations
 */
function wc_add_variable_product_purchase_price_field($loop, $variation_data, $variation) {
	$currency = get_woocommerce_currency_symbol();
	woocommerce_wp_text_input(array(
		'id'            => 'variable_purchase_price[' . $loop . ']',
		'wrapper_class' => 'form-row form-row-first',
		'label'         => 'Preço de custo' . " ($currency)",
		'placeholder'   => '',
		'data_type'     => 'price',
		'desc_tip'      => false,
		'value'         => get_post_meta($variation->ID, '_purchase_price', true)
	));
}

function wc_save_variable_product_purchase_price_field($variation_id, $i) {
	if (isset($_POST['variable_purchase_price'][$i])) {
		$purchase_price = ($_POST['variable_purchase_price'][$i] === '') ? '' : wc_format_decimal($_POST['variable_purchase_price'][$i]);
		update_post_meta($variation_id, '_purchase_price', $purchase_price);
	}
}

/**
 * Quick edit functions (only for simple or external products) 
 * Add field to the bottom of the quick edit window
 */
function wc_add_products_purchase_price_quick_edit_field()
{
?>
	<br class="clear" />
	<div class="price_fields">
		<label>
			<span class="title"><?php _e('Cost', 'products-purchase-price-for-woocommerce'); ?></span>
			<span class="input-text-wrap">
				<input type="text" name="_purchase_price" class="text wc_input_price" placeholder="Preço de custo" value="">
			</span>
		</label>
	</div>
	<?php
}


/**
 * Save the value of _purchase_price
 * when using the quick edit update
 */
function wc_save_products_purchase_price_quick_edit_field($product)
{
	$post_id = $product->id;

	if (isset($_REQUEST['_purchase_price'])) {
		$new_purchase_price = $_REQUEST['_purchase_price'] === '' ? '' : wc_format_decimal($_REQUEST['_purchase_price']);
		update_post_meta($post_id, '_purchase_price', $new_purchase_price);
	}
}

/**
 * Get and add _purchase_price value to the products
 * post columns so it is available for js script
 */
function wc_get_products_purchase_price_for_quick_edit_field($column, $post_id)
{
	switch ($column) {
		case 'name': ?>
			<div class="hidden" id="woocommerce_purchase_price_<?php echo $post_id; ?>">
				<div class="purchase_price"><?php echo get_post_meta($post_id, '_purchase_price', true); ?></div>
			</div>
		<?php
			break;

		default:
			break;
	}
}
