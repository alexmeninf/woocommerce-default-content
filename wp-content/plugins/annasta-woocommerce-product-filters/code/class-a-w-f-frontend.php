<?php

defined( 'ABSPATH' ) or die( 'Access denied' );

if( ! class_exists('A_W_F_frontend') ) {

	class A_W_F_frontend {
		
		/** Current running instance of A_W_F_frontend object
		 *
		 * @since 1.0.0
		 * @var A_W_F_frontend object
		 */
		protected static $instance;
		
		public $filters_manager;

		public $awf_settings = array();

		public $filter_on = false;
		
		/** List of all the AWF variables
		 *
		 * @since 1.2.5
		 * @var StdClass object with the following array properties: tax, awf, meta, range
		 */
		public $vars;
		public $query;
		
		public $url_query;
		public $page_associations;
		public $page_parent_associations;
		public $permalinks_on = false;
		public $shop_on_frontpage;
		public $shop_page_id;
		public $shop_url;
		public $current_url;
		public $seo_parts;

		protected $is_wp_page = false;
		public $is_sc_page = false;
		public $is_archive = false;

		public $counts_cache_name;
		public $counts;
		public $update_counts_cache;
		
		public $get_access_to = array();
		public $preset;
		
		/** Current site language for the sites with multiple language support
		 *
		 * @since 1.2.6
		 * @var NULL / Boolean / String
		 */
		public $language = null;

		protected function __construct() {
			
			$this->query = (object) array( 'awf' => array(), 'tax' => array(), 'meta' => array(), 'range' => array() );

			add_action( 'init', array( $this, 'initialize' ), 20 );
			add_action( 'init', array( $this, 'edit_woocommerce_before_shop_loop' ), 100 );
			add_filter( 'query_vars', array( $this, 'register_query_vars' ) );
			add_action( 'pre_get_posts', array( $this, 'before_wc_query' ), 0 );
			// add_action( 'woocommerce_product_query', array( $this, 'wc_query' ), 20, 2 );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 10 );

			if( 'yes' === get_option( 'awf_variations_stock_support', 'no' ) ) {
				add_filter( 'awf_product_counts_query', array( $this, 'add_variations_stock_support_to_product_counts' ) );
			}

			if( ! empty( get_option( 'awf_user_js', '' ) ) ) {
				add_action( 'wp_footer', array( $this, 'load_footer_js' ), 20 );
			}
			
			if( 'yes' === get_option( 'awf_add_seo_meta_description', 'no' ) ) {
				add_action( 'wp_head', array( $this, 'add_meta_description' ) );
			}
			
			if( 'yes' === get_option( 'awf_redirect_archives', 'no' ) ) {
				add_action( 'template_redirect', array( $this, 'redirect_archives' ), 20 );
			}
			
			add_action( 'wp_footer', array( $this, 'display_togglable_presets' ), 10 );
			add_action( 'shutdown', array( $this, 'update_counts_cache' ) );
			
			$this->filters_manager = 'A_W_F_filter_frontend';
		}

		public function ajax_controller() {

			if( 'filter' === $_GET['awf_action'] ) {
				$sc_args = array();
					
				if( isset( $_GET['awf_sc_page'] ) ) {
					$this->is_sc_page = (int) $_GET['awf_sc_page'];
					$this->initialize();

					add_action( 'woocommerce_shortcode_before_products_loop', array( $this, 'insert_sc_ajax_vars' ) );          
					add_action( 'woocommerce_shortcode_products_loop_no_results', array( $this, 'insert_sc_ajax_vars' ) );

					$sc_args = isset( $_GET['awf_sc'] ) ? $_GET['awf_sc'] : array();

				} else {
					$this->initialize();

					$sc_args = array( 'paginate' => true );

					if( ! isset( $this->query->awf['orderby'] ) ) {
						$sc_args['orderby'] = get_option( 'woocommerce_default_catalog_orderby', 'menu_order' );
					}
					
					if( ! empty( $this->awf_settings['shop_columns'] ) ) { $sc_args['columns'] = $this->awf_settings['shop_columns']; }
					
					if( empty( $this->awf_settings['ppp_default'] ) ) {
						$sc_args['limit'] = ( empty( $this->awf_settings['shop_columns'] ) ? wc_get_default_products_per_row() : $this->awf_settings['shop_columns'] ) * wc_get_default_product_rows_per_page();
						
					} else {
						$sc_args['limit'] = $this->awf_settings['ppp_default'];
					}
				}
				
				$page_number = false;
				
				if( isset( $_GET['page_number'] ) && ! empty( $sc_args['paginate'] ) ) {
					$page_number = (int) $_GET['page_number'];
					
					add_action( 'woocommerce_before_shop_loop', array( $this, 'display_ajax_pagination_resut_count' ), 1000 );
				}
				
				$this->build_ajax_queries();
				
				$_GET = $this->get_url_query();
				
				add_filter( 'woocommerce_shortcode_products_query', array( $this, 'wc_shortcode_query' ), 10, 3 );
				add_action( 'woocommerce_shortcode_before_products_loop', array( $this, 'edit_woocommerce_before_shop_loop' ) );
				add_action( 'woocommerce_before_shop_loop', array( $this, 'add_ajax_products_header' ), 5 );
				add_action( 'awf_add_ajax_products_header_title', array( $this, 'add_ajax_products_header_title' ) );
				add_filter( 'woocommerce_page_title', array( $this, 'adjust_shop_title' ) );
				if( ! $this->is_archive && ! $this->is_sc_page ) { add_filter( 'document_title_parts', array( $this, 'adjust_document_title' ) ); }
				
				if( $page_number ) {
					$_GET['product-page'] = $page_number;
					if( version_compare( WC_VERSION, '3.3.3', '<' ) ) { set_query_var( 'product-page', $page_number ); }
				}
				
				add_filter( 'woocommerce_pagination_args', array( $this, 'adjust_wc_pagination' ) );
				add_filter( 'paginate_links', function( $href ) {
					$remove_pagination_args = array_keys( $_REQUEST );
					
					return remove_query_arg( $remove_pagination_args, $href );
				});

				/* Fix for the main WC "date" orderby implicitly implying "date-desc" */
				if( isset( $this->query->awf['orderby'] ) && 'date' === $this->query->awf['orderby'] ) {
					$this->query->awf['orderby'] = 'date-desc';
				}
				
				add_action( 'woocommerce_shortcode_products_loop_no_results', array( $this, 'display_no_results_msg' ) );

				if( empty( intval( get_option( 'awf_counts_cache_days', '30' ) ) ) ) {
					$sc_args['cache'] = false;
				}

				if( class_exists( 'SitePress' ) ) {
					$this->maybe_add_wpml_adjustments();
				}

				do_action( 'awf_ajax_filter_before_wc_products_shortcode' );
				
				$this->do_wc_products_shortcode( $sc_args );

			} else if( 'get_search_autocomplete' === $_GET['awf_action'] ) {
				if( isset( $_GET['awf_sc_page'] ) ) { $this->is_sc_page = (int) $_GET['awf_sc_page']; }
				
				$filter_data = sanitize_title( $_GET['awf_filter'] );
				$filter_data = explode( '-filter-', $filter_data );
				$filter_data = array_pop( $filter_data );
				$filter_data = explode( '-', str_replace( '-wrapper', '', $filter_data) );
				$filter = new A_W_F_filter( $filter_data[0], $filter_data[1] );
				
				if( empty( $filter->settings['autocomplete'] ) ) {
					echo '';
					die();
				}
				
				$this->initialize();

				$sc_args = array(
					'paginate' => false,
					'columns' => 1,
					'limit' => intval( $filter->settings['type_options']['autocomplete_results_count'] ),
					'class' => 'awf-autocomplete-products-container',
				);

				if( empty( $filter->settings['type_options']['autocomplete_filtered'] ) ) {
					$this->query->awf['search'] = sanitize_text_field( $_GET['awf_query'][$this->vars->awf['search']] );
					
				} else {
					$this->build_ajax_queries();
					
					unset( $this->query->awf['ppp'] );
					if( ! isset( $this->query->awf['orderby'] ) ) {
						$sc_args['orderby'] = get_option( 'woocommerce_default_catalog_orderby', 'menu_order' );
					}
				}
					
				add_filter( 'woocommerce_shortcode_products_query', array( $this, 'wc_shortcode_query' ), 10, 3 );
				
				remove_all_actions( 'woocommerce_before_shop_loop' );
				remove_all_actions( 'woocommerce_before_shop_loop_item' );
				remove_all_actions( 'woocommerce_before_shop_loop_item_title' );
				remove_all_actions( 'woocommerce_after_shop_loop_item_title' );
				remove_all_actions( 'woocommerce_after_shop_loop_item' );
				remove_all_actions( 'woocommerce_after_shop_loop' );
				
				add_action( 'woocommerce_before_shop_loop_item', 'woocommerce_template_loop_product_link_open', 10 );
				if( ! empty( $filter->settings['type_options']['autocomplete_show_img'] ) ) {
					add_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10 );
				}
				if( ! empty( $filter->settings['type_options']['autocomplete_show_price'] ) ) {
					add_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10 );
				}
				add_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 5 );
				if( ! empty( $filter->settings['type_options']['autocomplete_view_all'] ) ) {
					$this->get_access_to['autocomplete_view_all_label'] = isset( $filter->settings['style_options']['autocomplete_view_all_label'] ) ? $filter->settings['style_options']['autocomplete_view_all_label'] : __( 'View all results', 'annasta-filters' );
					add_action( 'woocommerce_shortcode_after_products_loop', array( $this, 'add_search_autocomplete_all_results_link' ), 10 );
				}

				$this->do_wc_products_shortcode( $sc_args );

			} else if( 'update_filters' === $_GET['awf_action'] ) {

// $microtime = microtime();

				$response = array();
				$this->awf_settings['include_children'] = ( 'yes' === get_option( 'awf_include_children_on', 'yes' ) );
				$this->set_query_vars();

				$this->build_ajax_queries();
				$this->sort_query();
				
				$excluded_taxonomy = sanitize_text_field( $_GET['awf_exclude_taxonomy'] );

				unset( $this->query->awf['orderby'] );
				unset( $this->query->awf['ppp'] );

				if( class_exists( 'SitePress' ) && ! empty( $this->get_current_language() ) ) {
					$this->query->awf_lang = $this->language;
					$this->maybe_add_wpml_adjustments();
				}

				$this->counts_cache_name = 'awf_counts_' . md5( wp_json_encode( $this->query ) );
				$this->counts = get_transient( $this->counts_cache_name );
				if( false === $this->counts ) { $this->counts = array(); }

				foreach( $_GET['awf_callers'] as $caller ) {
					$caller = sanitize_text_field( $caller );

					$pieces = substr( $caller, 0, -strlen( '-wrapper' ) );
					$pieces = explode( '-', $pieces );
					$preset_id = (int) array_pop( $pieces );

					if( isset( A_W_F::$presets[$preset_id] ) ) { $preset = new A_W_F_preset_frontend( $preset_id ); }
				}

				if( true === $this->update_counts_cache && ! empty( $this->counts ) ) {
					if( ! empty( $lifespan = intval( get_option( 'awf_counts_cache_days', '30' ) ) ) ) {
						set_transient( $this->counts_cache_name, $this->counts, DAY_IN_SECONDS * $lifespan );
					}
				}

				foreach( $this->counts as $taxonomy => $counts ) {
					$response['counts'][$this->vars->tax[$taxonomy]] = $counts;
				}

				if( 'yes' === get_option( 'awf_dynamic_price_ranges', 'no' ) ) {
					$response['price_filter_min_max'] = $this->get_price_filter_min_max();
				}
				
// $response['time'] = microtime() - $microtime ;
				echo( json_encode( $response ) );
			}

			die();
		}

		public function get_price_filter_min_max() {
			global $wpdb;

			$query_args = array(
				'post_type' => 'product',
				'fields' => 'ids',
				'post_status' => 'publish',
				'ignore_sticky_posts' => true,
				'no_found_rows' => true,
				'posts_per_page' => -1,
				'paged' => '',
				'post__in' => array(),
			);
			
			if( isset( $this->get_access_to['counts_meta_query'] ) ) {
				$query_args['meta_query'] = $this->get_access_to['counts_meta_query'];
			} else {
				$this->get_access_to['counts_meta_query'] = $query_args['meta_query'] = $this->set_wc_meta_query( array() );
			}

			unset( $query_args['meta_query']['price_filter'] );
			
			if( isset( $this->get_access_to['counts_post__in'] ) ) {
				$query_args['post__in'] = $this->get_access_to['counts_post__in'];
			} else {
				$this->get_access_to['counts_post__in'] = $query_args['post__in'] = $this->get_wc_post__in( $query_args['post__in'] );
			}

			$query_args['tax_query'] = $this->set_wc_tax_query( array() );
			$this->set_default_visibility( $query_args['tax_query'] );

			$query = new WP_Query( $query_args );

			if( ! empty( $query->posts ) ) {
				$sql = "
				SELECT MIN( min_price ) as min_price, MAX( max_price ) as max_price
				FROM {$wpdb->wc_product_meta_lookup}
				WHERE product_id IN (" . implode( ',', $query->posts ) . ")";

				return $wpdb->get_row( $sql, ARRAY_A );
			}

			return array( 0, 1 );
		}

		protected function build_ajax_queries() {
			
			if( ! empty( $_GET['awf_query'] ) ) {
				
				foreach( $_GET['awf_query'] as $var => $value ) {
					if( ( false !== ( $taxonomy = array_search( $var, $this->vars->tax ) ) ) ) {
						$this->query->tax[$taxonomy] = array_map( 'sanitize_text_field', explode( ',', $value ) );
						
					} elseif( false !== ( $awf_var_name = array_search( $var, $this->vars->awf ) ) ) {
						$this->query->awf[$awf_var_name] = sanitize_text_field( $value );

					} else if( false !== ( $meta_var_name = array_search( $var, $this->vars->meta ) ) ) {
						$this->query->meta[$meta_var_name] = array_map( 'sanitize_text_field', explode( ',', $value ) );
						
					} elseif( false !== ( $range_var_name = array_search( $var, $this->vars->range ) ) ) {
						$this->query->range[$range_var_name] = (float) $value;
					}
				}
				
				$this->set_numeric_taxonomy_ranges();
			}
			
			if( isset( $_GET['awf_query']['archive-filter'] )
				&& false !== ( $tax = array_search( $_GET['awf_archive_page'], $this->vars->tax ) )
				&& isset( $this->query->tax[$tax] )
			) {
				$this->is_archive = $tax;
				$this->setup_archive( implode( ',', $this->query->tax[$tax] ) );
			}
		}
		
		public function initialize() {

			$this->permalinks_on = ! empty( get_option( 'permalink_structure' ) );
			
			$this->shop_page_id = wc_get_page_id( 'shop' );
			if( 'page' === get_option( 'show_on_front' ) && intval( get_option( 'page_on_front' ) ) === $this->shop_page_id ) {
				$this->shop_on_frontpage = true;
			}

			$this->set_awf_settings();
			$this->set_query_vars();
			$this->setup_urls();

			if( isset( $_POST['awf_submit'] ) ) {
				$url_query = array();
				
				foreach( $_POST as $var => $value ) {
					if( is_array( $value ) ) { $value = implode( ',', $value ); }
					if( '' !== $value ) { $url_query[$var] = $value; }
				}
				
				if( isset( $_POST['awf_sc_page'] ) ) {
					$url = get_permalink( intval( $_POST['awf_sc_page'] ) );
					unset( $url_query['awf_sc_page'] );
					
				} elseif( isset( $_POST['awf_archive_page'] )
					&& false !== ( $tax = array_search( $_POST['awf_archive_page'], $this->vars->tax ) )
					&& isset( $url_query[$_POST['awf_archive_page']] )
			) {

					$this->is_archive = $tax;
					$this->setup_archive( $url_query[$_POST['awf_archive_page']] );
					$url = $this->current_url;
					$url_query = array_diff_key( $url_query, array( 'awf_archive_page' => '', $_POST['awf_archive_page'] => '' ) );
					$url_query['archive-filter'] = 1;
					
				} else {
					$url = $this->shop_url;
				}
				
				unset( $url_query['awf_submit'] );

				$url = add_query_arg( $url_query, $url );

				wp_redirect( esc_url_raw( $url ) );
				exit();
			}
		}

		protected function set_awf_settings() {

			if( 0 < ( $this->awf_settings['shop_columns'] = (int) apply_filters( 'awf_set_shop_columns', get_option( 'awf_shop_columns', 0 ) ) ) && ! wp_doing_ajax() ) {
				add_filter( 'loop_shop_columns', array( $this, 'set_shop_columns' ), 20 );
			}

			$this->awf_settings['ppp_default'] = (int) apply_filters( 'awf_set_ppp_default', get_option( 'awf_ppp_default', 0 ) );
			if( ! wp_doing_ajax() ) { add_filter( 'loop_shop_per_page', array( $this, 'set_products_per_page' ), 20 ); }

			$this->awf_settings['include_children'] = ( 'yes' === get_option( 'awf_include_children_on', 'yes' ) );
		}

		public function set_query_vars() {
			$this->vars = (object) get_option( 'awf_query_vars', array( 'tax' => array(), 'awf' => array(), 'range' => array(), 'meta' => array() ) );
			
			if( empty( $this->vars->tax ) ) {
				A_W_F::build_query_vars();
				$this->vars = (object) get_option( 'awf_query_vars', array( 'tax' => array(), 'awf' => array(), 'range' => array(), 'meta' => array() ) );
			}
		}

		public function register_query_vars( $vars ) {
			
			$vars[] = 'archive-filter';

			foreach( $this->vars->tax as $var ) {
				if( ! in_array( $var, $vars ) ) { $vars[] = $var; }
			}

			foreach( $this->vars->awf as $var ) {
				if( ! in_array( $var, $vars ) ) { $vars[] = $var; }
			}

			foreach( $this->vars->range as $var ) {
				if( ! in_array( $var, $vars ) ) { $vars[] = $var; }
			}

			foreach( $this->vars->meta as $var ) {
				if( ! in_array( $var, $vars ) ) { $vars[] = $var; }
			}

			return $vars;
		}

		public function setup_urls() {
			
			if( ! empty( $this->shop_on_frontpage ) ) {
				$this->shop_url = $this->current_url = get_home_url() . '/';
				
			} else {
				if( $this->permalinks_on ) {
					$this->shop_url = $this->current_url = get_permalink( $this->shop_page_id );
				} else {
					$this->shop_url = $this->current_url = get_post_type_archive_link( 'product' );
				}
			}

			if ( false !== $this->is_sc_page ) { $this->current_url = get_permalink( $this->is_sc_page ); }
		}

		public function wc_query( $query, $class_wc_query ) {}

		public function before_wc_query( $query ) {

			if( ! $query->is_main_query() || is_admin() ) { return; }
			
			$is_shop = false;
			$product_taxonomies = array_keys( $this->vars->tax );

			if( $query->is_post_type_archive( 'product' ) ) {
				$is_shop = true;
				$this->filter_on = true;
				
			} elseif( $query->is_tax( $product_taxonomies ) ) {
				$this->is_archive = true;
				$this->filter_on = true;
			
			} else {
			
				if( ! empty( $query->get( 'page_id' ) ) ) {
					$this->is_wp_page = $query->get( 'page_id' ) . '';

				} elseif( $query->queried_object instanceof WP_Post ) {
					$this->is_wp_page = $query->queried_object_id . '';
				}
				
				if( ! empty( $this->shop_on_frontpage ) && ( $query->is_home() && ! (bool) $query->is_posts_page ) || (int) $this->is_wp_page === $this->shop_page_id ) {
					$is_shop = true;
					$this->filter_on = true;

				} elseif( in_array( $this->is_wp_page, get_option( 'awf_shortcode_pages', array() ) ) ) {
					$this->is_sc_page = $this->is_wp_page;
					$this->filter_on = true;

					$this->current_url = get_permalink( $this->is_wp_page );

					add_filter( 'woocommerce_shortcode_products_query', array( $this, 'wc_shortcode_query' ), 10, 3 );
					add_filter( 'shortcode_atts_products', array( $this, 'add_awf_sc_class' ) );
					add_action( 'woocommerce_shortcode_before_products_loop', array( $this, 'insert_sc_ajax_vars' ) );
					add_action( 'woocommerce_shortcode_products_loop_no_results', array( $this, 'insert_sc_ajax_vars' ) );
					add_action( 'woocommerce_shortcode_products_loop_no_results', array( $this, 'display_sc_page_no_results_message' ), 20 );
				}
			}
			
			if( $this->filter_on ) {
				
				foreach( $query->query as $var => $value ) {
					if( false !== ( $var_name = array_search( $var, $this->vars->tax ) ) ) {

						if( is_array( $value ) ) {
							$terms = $value;
						} else {
							$terms = explode( ',', $value );
						}

						$this->query->tax[$var_name] = array_map( 'sanitize_text_field', $terms );

					} else if( false !== ( $awf_var_name = array_search( $var, $this->vars->awf ) ) ) {
						$this->query->awf[$awf_var_name] = sanitize_text_field( $value );

					} else if( false !== ( $meta_var_name = array_search( $var, $this->vars->meta ) ) ) {
						$this->query->meta[$meta_var_name] = explode( ',', $value );
						$this->query->meta[$meta_var_name] = array_map( 'sanitize_text_field', $this->query->meta[$meta_var_name] );

					} else if( false !== ( $range_var_name = array_search( $var, $this->vars->range ) ) ) {
						$this->query->range[$range_var_name] = (float) $value;
					}
				}
				
				$this->set_numeric_taxonomy_ranges();
				
				if( empty( $this->query->awf['search'] ) && ! empty( $query->get( 's' ) ) ) {
					$this->query->awf['search'] = $query->query['s'];
				}
				
				if( $this->is_archive ) {
					if( 1 === count( array_intersect_key( $query->tax_query->queried_terms, $this->vars->tax ) )
						&& ( ! empty( $query->get( 'archive-filter' ) ) || ! count( $this->query->tax ) )
					) {
						$queried_object = $query->get_queried_object();
						$this->is_archive = $queried_object->taxonomy;
						$this->setup_archive( $query->get( $this->is_archive, '' ) );
						if( 'yes' === get_option( 'awf_breadcrumbs_support', 'yes' ) ) {
							add_filter( 'woocommerce_get_breadcrumb', array( $this, 'adjust_breadcrumbs' ), 10, 2 );
						}	
						
					} else {
						foreach( $product_taxonomies as $t ) {
							if( isset( $query->tax_query->queried_terms[$t] ) ) {
								$this->query->tax[$t] = (array) $query->get( $t );
							}
						}
						
						$this->is_archive = false;
						$is_shop = true;
					}
				}
				
				if( $is_shop ) {
					$query->set( 'post_type', 'product' );
					$query->is_post_type_archive = true;
					$query->is_archive = true;
					// $query->is_tax = false;
					
					add_filter( 'woocommerce_page_title', array( $this, 'adjust_shop_title' ) );
					add_filter( 'document_title_parts', array( $this, 'adjust_document_title' ) );
					
					if( class_exists( 'SitePress' ) ) {
						$this->maybe_add_wpml_adjustments();
					}
				}
				
				if( false === $this->is_sc_page ) {
					if( 'yes' === get_option( 'awf_force_products_display_on', 'yes' ) ) { add_filter( 'woocommerce_is_filtered', '__return_true' ); }
					add_filter( 'woocommerce_product_query_tax_query', array( $this, 'set_wc_tax_query' ) );
					add_filter( 'woocommerce_product_query_meta_query', array( $this, 'set_wc_meta_query' ) );
					add_filter( 'loop_shop_post_in', array( $this, 'get_wc_post__in' ) );
				}
			}
			
			$this->url_query = $this->get_url_query();
			$this->sort_query();
		}

		private function setup_archive( $archive_terms_string ) {
			$archive_terms = explode( ',', $archive_terms_string );
			$archive_terms = array_map( 'sanitize_title', $archive_terms );

			if( 'no' === get_option( 'awf_hierarchical_archive_permalinks', 'no' ) ) {
				add_filter( 'term_link', array( $this, 'adjust_hierarchical_term_links' ), 10, 3 );

				if( class_exists( 'SitePress' ) ) {
					global $woocommerce_wpml;

					if ( $woocommerce_wpml && isset( $woocommerce_wpml->url_translation ) ) {
						if( 'product_cat' === $this->is_archive || 'product_tag' == $this->is_archive ) {
							add_filter( 'term_link', [ $woocommerce_wpml->url_translation, 'translate_taxonomy_base' ], 15, 3 );

						} else {
							if( in_array( substr( $this->is_archive, 3 ), wp_list_pluck( wc_get_attribute_taxonomies(), 'attribute_name' ) ) ) {
								add_filter( 'term_link', [ $woocommerce_wpml->url_translation, 'translate_taxonomy_base' ], 15, 3 );
							} else {
								add_filter( 'term_link', array( $this, 'adjust_wpml_custom_taxonomy_base' ), 15, 3 );
							}
						}
					}
				}
			}
			
			$this->current_url = user_trailingslashit( get_term_link( reset( $archive_terms ), $this->is_archive ) );
			remove_filter( 'term_link', array( $this, 'adjust_hierarchical_term_links' ), 10 );
			remove_filter( 'term_link', array( $this, 'adjust_wpml_custom_taxonomy_base' ), 15 );

			if( is_wp_error( $this->current_url ) ) {
				$this->current_url = $this->shop_url;

			} else {
				if( 1 < count( $archive_terms ) ) {
					if( $this->permalinks_on ) {
						$replace = user_trailingslashit( '/' . reset( $archive_terms ) );
						$pos = strrpos( $this->current_url, $replace );
						if ( $pos !== false ) {
							$this->current_url = substr_replace( $this->current_url, user_trailingslashit( '/' . implode( ',', $archive_terms ) ), $pos, strlen( $replace ) );
						}
					}
				}
			}

			$this->query->tax[$this->is_archive] = $archive_terms;
		}

		public function adjust_hierarchical_term_links( $termlink, $term, $taxonomy ) {
			$slug = $term->slug;
			$t    = get_taxonomy( $taxonomy );
	
			if ( isset( $t->rewrite['hierarchical'] ) && $t->rewrite['hierarchical'] ) {
				global $wp_rewrite;

				$termlink = $wp_rewrite->get_extra_permastruct( $taxonomy );
				$termlink = str_replace( "%$taxonomy%", $slug, $termlink );

				$termlink = home_url( user_trailingslashit( $termlink, 'category' ) );
			}

			return $termlink;
		}

		public function adjust_wpml_custom_taxonomy_base( $termlink, $term, $taxonomy ) {
			$t = get_taxonomy( $taxonomy );

			if( ! empty( $t->rewrite['slug'] ) ) {
				$base = $t->rewrite['slug'];
				$name = sprintf( 'URL %s tax slug', $taxonomy );
				
				$translated_base = apply_filters( 'wpml_translate_single_string', $base, 'WordPress', $name );
				if( $translated_base !== $base ) {
					$termlink = str_replace( '/' . $base . '/', '/' . $translated_base . '/', $termlink );
				}
			}

			return $termlink;
		}

		public function wc_shortcode_query( $args, $attrs, $type ) {
						
			if( isset( $this->query->awf['ppp'] ) ) {
				$args['posts_per_page'] = (int) $this->query->awf['ppp'];

				if( absint( get_option( 'awf_ppp_limit', '200' ) ) < $args['posts_per_page'] || -1 === $args['posts_per_page'] ) {
					$args['posts_per_page'] = absint( get_option( 'awf_ppp_limit', '200' ) );
				}
			}

			if( isset( $this->query->awf['stock'] ) && 'outofstock' === $this->query->awf['stock'] && 'yes' === get_option( 'woocommerce_hide_out_of_stock_items', 'no' ) && 'yes' === get_option( 'awf_variations_stock_support', 'no' ) ) {
				$this->unhide_outofstock( $args['tax_query'] );
			}
			
			$tax_query = $this->set_wc_tax_query( array() );
			$args['tax_query'] = array_merge( $args['tax_query'], $tax_query );

			$meta_query = $this->set_wc_meta_query( array() );
			$args['meta_query'] = array_merge( $args['meta_query'], $meta_query );

			if( empty( $args['post__in'] ) ) { $args['post__in'] = array(); }
			$args['post__in'] = $this->get_wc_post__in( $args['post__in'] );
			
			if( isset( $this->query->awf['orderby'] ) ) {
				$pieces = explode( '-', $this->query->awf['orderby'] );
				if( empty( $pieces[1] ) ) { $pieces[1] = 'ASC'; } else {
					$pieces[1] = in_array( strtoupper( $pieces[1] ), array( 'ASC', 'DESC') ) ? strtoupper( $pieces[1] ) : 'ASC' ;
				}

				$ordering_args = WC()->query->get_catalog_ordering_args( $pieces[0], $pieces[1] );
				$args['orderby']        = $ordering_args['orderby'];
				$args['order']          = $ordering_args['order'];
				if ( $ordering_args['meta_key'] ) {
					$args['meta_key']       = $ordering_args['meta_key'];
				}
			}

			return $args;
		}

		public function set_wc_tax_query( $query ) {
			
			foreach( $this->query->tax as $var => $terms ) {
				$operator = get_option( 'awf_' . $var . '_query_operator', 'IN' );
				
				$query[] = array(
					'taxonomy' => $var,
					'field' => 'slug',
					'terms' => $terms,
					'operator' => $operator,
					'include_children' => $this->awf_settings['include_children'],
				);
			}

			if( isset( $this->query->awf['stock'] )) {
				if( 'yes' === get_option( 'awf_variations_stock_support', 'no' ) ) {
					if( 'outofstock' === $this->query->awf['stock'] && 'yes' === get_option( 'woocommerce_hide_out_of_stock_items', 'no' ) ) {
						$this->unhide_outofstock( $query );
					}

				} else {
					if( in_array( $this->query->awf['stock'], array( 'instock', 'outofstock' ) ) ) {
						$this->{ 'set_visibility_' . $this->query->awf['stock'] }( $query );
					}      
				}
			}

			if( isset( $this->query->awf['featured'] ) ) {
				$this->set_visibility_featured( $query );
			}
			
			return $query;
		}

		public function set_wc_meta_query( $query ) {

			if( isset( $this->query->awf['stock'] ) ) {
				if( 'no' === get_option( 'awf_variations_stock_support', 'no' ) ) {
					switch( $this->query->awf['stock'] ) {
						case 'onbackorder':
							$query[] = array(
								'key' => '_stock_status',
								'value' => 'onbackorder'
							);
							break;
						case 'instock':
							$query[] = array(
								'key' => '_stock_status',
								'value' => 'onbackorder',
								'compare' => 'NOT IN'
							);
							break;
						default:
							break;
					}
				}
			}
			
			if( ( isset( $this->query->range['min_price'] ) || isset( $this->query->range['max_price'] ) ) ) {
				
				if( version_compare( WC_VERSION, '3.6', '>=' ) ) {
					add_action( 'woocommerce_product_query', function() {
						remove_filter( 'posts_clauses', array( WC()->query, 'price_filter_post_clauses' ), 10 );
					} );
				}
				
				$min_price = isset( $this->query->range['min_price'] ) ? floatval( wp_unslash( $this->query->range['min_price'] ) ) : 0;
				$max_price = isset( $this->query->range['max_price'] ) ? floatval( wp_unslash( $this->query->range['max_price'] ) ) : PHP_INT_MAX;

				if ( wc_tax_enabled() && 'incl' === get_option( 'woocommerce_tax_display_shop' ) && ! wc_prices_include_tax() ) {
					$tax_rates = WC_Tax::get_rates( '' );

					if ( $tax_rates ) {
						$min_price -= WC_Tax::get_tax_total( WC_Tax::calc_inclusive_tax( $min_price, $tax_rates ) );
						$max_price -= WC_Tax::get_tax_total( WC_Tax::calc_inclusive_tax( $max_price, $tax_rates ) );
					}
				}

				$query['price_filter'] = array(
					'key'     => '_price',
					'value'   => array( $min_price, $max_price ),
					'compare' => 'BETWEEN',
					'type'    => 'DECIMAL(12,' . wc_get_price_decimals() . ')',
				);
			}
			
			if( ( isset( $this->query->range['min_rating'] ) || isset( $this->query->range['max_rating'] ) ) & ! isset( $query['awf_rating_filter']['awf_rating_filter'] ) ) {
				$range_min = isset( $this->query->range['min_rating'] ) ? (float) $this->query->range['min_rating'] : (float) 0.01;
				if( floatval(0) === $range_min ) { $range_min = (float) 0.01; }
				$range_max = isset( $this->query->range['max_rating'] ) ? (float) $this->query->range['max_rating'] : (float) 5;
				
				$query['awf_rating_filter'] = array(
					'key' => '_wc_average_rating',
					'value' => array( $range_min, $range_max ),
					'compare' => 'BETWEEN',
					'type' => 'DECIMAL(3,2)',
					'awf_rating_filter' => true,
				);
			}
			
			foreach( $this->vars->meta as $meta => $meta_name ) {
				if( isset( $this->query->meta[$meta] ) ) {
					
					if( 'awf_date_filter_' === substr( $meta, 0, 16) ) {
						
						if( empty( $this->query->meta[$meta] ) ) { continue; }
						
						$date_format = explode( '_', $meta );
						$date_format = $date_format[3];
						$date_formats = A_W_F::get_db_date_formats();
						
						if( ! isset( $date_formats[$date_format] ) ) { continue; }
						
						if( 2 === count( $this->query->meta[$meta] ) ) {
							
							$db_values = array( gmdate( $date_formats[$date_format]['format'], intval( $this->query->meta[$meta][0] ) ), gmdate( $date_formats[$date_format]['format'], intval( $this->query->meta[$meta][1] ) ) );
							$db_date_type = null;

							switch( $date_format ) {
								case( 'c' ):
								case( 'd' ):
									$db_date_type = 'DATE';
									break;

								case( 'e' ):
								case( 'f' ):
									$db_date_type = 'DATETIME';
									break;

								default:
									$db_date_type = 'NUMERIC';
									break;
							}

							$query[] = array(
								'key'     => substr( $meta, 18 ),
								'value'   => $db_values,
								'compare' => 'BETWEEN',
								'type' => $db_date_type,
							);
							
						} else {

							$db_date_type = null;
							$db_compare = null;

							switch( $date_format ) {
								case( 'c' ):
								case( 'd' ):
									$db_date_type = 'DATE';
									$db_compare = '=';
									break;

								case( 'e' ):
								case( 'f' ):
									$db_date_type = 'DATETIME';
									$db_compare = '=';
									break;

								default:
									$db_date_type = false;
									$db_compare = 'IN';
									break;
							}
							
							$meta_query = array(
								'key'         => substr( $meta, 18 ),
								'value'       => gmdate( $date_formats[$date_format]['format'], intval( $this->query->meta[$meta][0] ) ),
								'db_compare'  => $db_compare,
							);
							if( $db_date_type ) { $meta_query['type'] = $db_date_type; }
							
							$query[] = $meta_query;
						}
						
					} else {
						$query[] = array(
							'key'     => $meta,
							'value'   => $this->query->meta[$meta],
							'compare' => 'IN',
						);
					}
					
				} elseif( isset( $this->query->range['min_' . $meta] ) && isset( $this->query->range['max_' . $meta] ) ) {
					$query[] = array(
						'key' => $meta,
						'compare' => 'EXISTS',
					);
					
					$query[] = array(
						'key' => $meta,
						'value' => NULL,
						'compare' => 'NOT IN',
					);
					
					$query[] = array(
						'key' => $meta,
						'value' => array( $this->query->range['min_' . $meta], $this->query->range['max_' . $meta] ),
						'compare' => 'BETWEEN',
						'type' => 'numeric',
					);
				}
			}
			
			return $query;
		}
		
		protected function set_numeric_taxonomy_ranges() {}
		
		public function get_wc_post__in( $post__in ) {
			
			if( isset( $this->query->awf['onsale'] ) ) {
				$onsale_posts = array_merge( array( 0 ), wc_get_product_ids_on_sale() );
				if( empty( $post__in ) ) { $post__in = $onsale_posts;
				} else { $post__in = array_intersect( $post__in, $onsale_posts ); }
			}
			
			if ( ! empty( $this->query->awf['search'] ) ) {
				$data_store = WC_Data_Store::load( 'product' );
				$search_posts = array_merge( array( 0 ), $data_store->search_products( wc_clean( wp_unslash( $this->query->awf['search'] ) ), '', true, true ) );
				if( empty( $post__in ) ) { $post__in = $search_posts;
				} else { $post__in = array_intersect( $post__in, $search_posts ); }
			}

			if( 'yes' === get_option( 'awf_variations_stock_support', 'no' ) ) {
				if( isset( $this->query->awf['stock'] ) ) {
					$stock_posts = array_merge( array( 0 ), $this->get_stock_posts_with_variations( $this->query->awf['stock'] ) );
					if( empty( $post__in ) ) { $post__in = $stock_posts;
					} else { $post__in = array_intersect( $post__in, $stock_posts ); }

				} elseif( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items', 'no' ) ) {
					$stock_posts = array_merge( array( 0 ), $this->get_stock_posts_with_variations( 'outofstock', 'NOT IN' ) );
					
					if( empty( $post__in ) ) { $post__in = $stock_posts;
					} else { $post__in = array_intersect( $post__in, $stock_posts ); }
				}
			}
			
			$post__in = array_unique( $post__in );

			return $post__in;
		}

		public function add_variations_stock_support_to_product_counts( $args ) {
			$args['post__in'] = $this->get_wc_post__in( array() );

			return $args;
		}

		private function get_stock_posts_with_variations( $availability, $operator='IN' ) {
			global $wpdb;

			$availability = esc_sql( $availability );
			$products_ids = array();

			$attributes_filters = array_filter( $this->query->tax, function( $v, $k ) {
				if( 0 === strpos( $k, 'pa_' ) && ! empty( $v ) ) { return true; }
				return false;
			}, ARRAY_FILTER_USE_BOTH);

			$products_ids = $wpdb->get_col(
				"SELECT DISTINCT posts.ID AS product_id FROM {$wpdb->posts} as posts
				INNER JOIN {$wpdb->postmeta} AS postmeta ON posts.ID = postmeta.post_id AND postmeta.meta_key = '_stock_status'
				WHERE posts.post_type = 'product'
				AND posts.ID NOT IN (SELECT DISTINCT post_parent FROM {$wpdb->posts} as p2 WHERE p2.post_type = 'product_variation' AND p2.post_parent > 0 )
				AND postmeta.meta_value {$operator} ('{$availability}')
				LIMIT 30000"
			);

			if( empty( $attributes_filters ) ) {

				$variable_products_ids = $wpdb->get_col(
					"SELECT DISTINCT posts.post_parent FROM {$wpdb->posts} AS posts
					INNER JOIN {$wpdb->postmeta} AS postmeta ON posts.ID = postmeta.post_id AND postmeta.meta_key = '_stock_status'
					WHERE posts.post_type = 'product_variation'
						AND postmeta.meta_value {$operator} ('{$availability}')
					LIMIT 30000"
				);

				$products_ids = array_merge( $products_ids, $variable_products_ids );

			} else {

				if( 'NOT IN' === $operator ) { $availability = 'instock'; }

				$combinations = array( array() );

				foreach( $attributes_filters as $attribute => $slugs) {
					foreach( $combinations as $combination ) {
							foreach( $slugs as $slug ) {
								array_push( $combinations, array_merge( array( $attribute => $slug ), $combination ) );
							}
					}
				}
				array_shift( $combinations );

				$combinations_by_count = array();

				foreach( $combinations as $i => $combination ) {
					$combinations_by_count[count( $combination )][] = $combination;
				}

				foreach( $combinations_by_count as $count => $combinations ) {
					$in_clauses = array();
					$i = 0;

					$attributes_count_check = $wpdb->get_col( "
						SELECT postmeta.post_id FROM {$wpdb->postmeta} AS postmeta
						WHERE postmeta.meta_key LIKE 'attribute_%' OR (postmeta.meta_key, postmeta.meta_value) IN (('_stock_status', '{$availability}'))
						GROUP BY postmeta.post_id
						HAVING COUNT(*) = " . ( $count + 1 ) . "
						LIMIT 30000
					" );

					if( ! empty( $attributes_count_check ) ) {

						$attributes_count_check = implode( ',', $attributes_count_check );

						foreach( $combinations as $variation ) {
							$i++;
							$in_clause = array( "('_stock_status', '{$availability}')" );

							foreach( $variation as $meta_key => $meta_value ) {
								$in_clause[] = "('attribute_" . esc_sql( $meta_key ) . "', '" . esc_sql( $meta_value ) . "')";
							}

							$in_clauses[] = "
								posts.ID IN (
									SELECT pm{$i}.post_id FROM {$wpdb->postmeta} AS pm{$i}
									WHERE (pm{$i}.meta_key, pm{$i}.meta_value) IN (" . implode( ',', $in_clause ) . ")
									GROUP BY pm{$i}.post_id
									HAVING COUNT(*) = " . ( $count + 1 ) . "
								)
							";

						}

						$in_clauses = implode( ' OR ', $in_clauses );

						$filtered_variations_parents = $wpdb->get_col(
							"SELECT DISTINCT posts.post_parent FROM {$wpdb->posts} AS posts
							WHERE ({$in_clauses}) AND (posts.ID IN ({$attributes_count_check}))
							LIMIT 30000"
						);

						$products_ids = array_merge( $products_ids, $filtered_variations_parents );
					}

					if( 'NOT IN' === $operator ) {

						$attributes_count_check = $wpdb->get_col( "
							SELECT postmeta.post_id FROM {$wpdb->postmeta} AS postmeta
							WHERE postmeta.meta_key LIKE 'attribute_%' OR (postmeta.meta_key, postmeta.meta_value) IN (('_stock_status', 'onbackorder'))
							GROUP BY postmeta.post_id
							HAVING COUNT(*) = " . ( $count + 1 ) . "
							LIMIT 30000
						" );

						if( ! empty( $attributes_count_check ) ) {
							$attributes_count_check = implode( ',', $attributes_count_check );
							$in_clauses = array();

							foreach( $combinations as $variation ) {
								$i++;
								$in_clause = array( "('_stock_status', 'onbackorder')" );
	
								foreach( $variation as $meta_key => $meta_value ) {
									$in_clause[] = "('attribute_" . esc_sql( $meta_key ) . "', '" . esc_sql( $meta_value ) . "')";
								}
								$in_clauses[] = "
									posts.ID IN (
										SELECT pm{$i}.post_id FROM {$wpdb->postmeta} AS pm{$i}
										WHERE (pm{$i}.meta_key, pm{$i}.meta_value) IN (" . implode( ',', $in_clause ) . ")
										GROUP BY pm{$i}.post_id
										HAVING COUNT(*) = " . ( $count + 1 ) . "
									)
								";
							}
	
							$in_clauses = implode( ' OR ', $in_clauses );

							$backordered_variations_parents = $wpdb->get_col(
								"SELECT DISTINCT posts.post_parent FROM {$wpdb->posts} AS posts
								WHERE ({$in_clauses}) AND (posts.ID IN ({$attributes_count_check}))
								LIMIT 30000"
							);

							$products_ids = array_merge( $products_ids, $backordered_variations_parents );
						}
					}
				}

				if( ! empty( $combinations_by_count[1] ) ) {
					/* Include variations with unfiltered second attribute (supports up to 2-attribute variations!) */
		
					$all_attributes = $wpdb->get_col( "
						SELECT DISTINCT postmeta.meta_key FROM {$wpdb->postmeta} AS postmeta
						WHERE postmeta.meta_key LIKE 'attribute_%'
						LIMIT 30000
					" );

					$filtered_attributes = array_keys( $attributes_filters );
					$filtered_attributes = array_map( function( $filter ) { return 'attribute_' . $filter; }, $filtered_attributes );

					$unfiltered_attributes = array_diff( $all_attributes, $filtered_attributes );
					$unfiltered_attributes = array_map( function( $attribute ) { return "'" . esc_sql( $attribute ) . "'"; }, $unfiltered_attributes );
					$unfiltered_attributes = implode( ',', $unfiltered_attributes );

					$i = 0;

					if( ! empty( $unfiltered_attributes ) ) {
						$clauses = array();

						foreach( $combinations_by_count[1] as $variation ) {
							foreach( $variation as $meta_key => $meta_value ) {
								$i++;

								$clauses[] = "posts.ID IN (
									SELECT pm{$i}.post_id FROM {$wpdb->postmeta} AS pm{$i}
									WHERE
										(pm{$i}.meta_key, pm{$i}.meta_value) IN (('attribute_" . esc_sql( $meta_key ) . "', '" . esc_sql( $meta_value ) . "'))
										OR pm{$i}.meta_key IN ({$unfiltered_attributes}) OR (pm{$i}.meta_key, pm{$i}.meta_value) IN (('_stock_status', '{$availability}'))
									GROUP BY pm{$i}.post_id
									HAVING COUNT(*) = 3
								)";

								if( 'NOT IN' === $operator ) {
									$i++;

									$clauses[] = "posts.ID IN (
										SELECT pm{$i}.post_id FROM {$wpdb->postmeta} AS pm{$i}
										WHERE
											(pm{$i}.meta_key, pm{$i}.meta_value) IN (('attribute_" . esc_sql( $meta_key ) . "', '" . esc_sql( $meta_value ) . "'))
											OR pm{$i}.meta_key IN ({$unfiltered_attributes}) OR (pm{$i}.meta_key, pm{$i}.meta_value) IN (('_stock_status', 'onbackorder'))
										GROUP BY pm{$i}.post_id
										HAVING COUNT(*) = 3
									)";
								}
							}
						}

						if( ! empty( $clauses ) ) {
							$clauses = implode( ' OR ', $clauses );

							$half_filtered_variations = $wpdb->get_col( "
								SELECT DISTINCT posts.post_parent FROM {$wpdb->posts} AS posts
								WHERE
									posts.post_type = 'product_variation'
									AND {$clauses}
								LIMIT 30000
							" );

							$products_ids = array_merge( $products_ids, $half_filtered_variations );
						}

					}
				}

			}
			
			if( empty( $products_ids ) ) { $products_ids = array( 0 ); }

			return $products_ids;
		}
		
		private function unhide_outofstock( &$tax_query ) {
			foreach( $tax_query as $i => $q ) {
				if( is_array( $q ) && isset( $q['taxonomy'] ) && 'product_visibility' === $q['taxonomy'] && isset( $q['operator'] ) && 'NOT IN' === $q['operator'] ) {
					if( isset( $q['terms'] ) && is_array( $q['terms'] ) && isset( $q['field'] ) && 'term_taxonomy_id' === $q['field'] ) {
						$product_visibility_terms  = wc_get_product_visibility_term_ids();
						$tax_query[$i]['terms'] = array_diff( $q['terms'], array( $product_visibility_terms['outofstock'] ) );

						add_filter( 'woocommerce_product_is_visible', array( $this, 'adjust_outofstock_visibility' ), 20, 2 );
					}
				}
			}
		}

		public function adjust_outofstock_visibility( $visible, $product_id ) {
			$product = wc_get_product( $product_id );

			if( $product ) {
				/* Remove the woocommerce_hide_out_of_stock_items condition from WC_Product > is_visible_core */
				$visible = true;

				$visible = 'visible' === $product->get_catalog_visibility() || ( is_search() && 'search' === $product->get_catalog_visibility() ) || ( ! is_search() && 'catalog' === $product->get_catalog_visibility() );

				if ( 'trash' === $product->get_status() ) {
					$visible = false;
				} elseif ( 'publish' !== $product->get_status() && ! current_user_can( 'edit_post', $product->get_id() ) ) {
					$visible = false;
				}
		
				if ( $product->get_parent_id() ) {
					$parent_product = wc_get_product( $product->get_parent_id() );
		
					if ( $parent_product && 'publish' !== $parent_product->get_status() ) {
						$visible = false;
					}
				}	
			}

			return $visible;
		}

		public function set_default_visibility( &$tax_query ) {
			$terms = array( 'exclude-from-catalog' );
			if( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items', 'no' ) ) {
				if( 'no' === get_option( 'awf_variations_stock_support', 'no' ) ) {
					$terms[] = 'outofstock';
				}
			}
			
			$tax_query[] = array(
				'taxonomy'         => 'product_visibility',
				'terms'            => $terms,
				'field'            => 'name',
				'operator'         => 'NOT IN',
				'include_children' => false,
			);
		}

		public function set_visibility_instock( &$tax_query ) {
			if( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items', 'no' ) ) { return; }
			
			$tax_query[] = array(
					'taxonomy'         => 'product_visibility',
					'terms'            => array( 'outofstock' ),
					'field'            => 'name',
					'operator'         => 'NOT IN',
					'include_children' => false,
			);
		}

		public function set_visibility_outofstock( &$tax_query ) {
			
			$tax_query[] = array(
					'taxonomy'         => 'product_visibility',
					'terms'            => array( 'outofstock' ),
					'field'            => 'name',
					'operator'         => 'IN',
					'include_children' => false,
			);
		}
		
		public function set_visibility_featured( &$tax_query ) {
			$tax_query[] = array(
				'taxonomy'         => 'product_visibility',
				'terms'            => 'featured',
				'field'            => 'name',
				'operator'         => 'IN',
				'include_children' => false,
			);
		}

		public function set_shop_columns() {
			return $this->awf_settings['shop_columns'];
		}

		public function set_products_per_page( $ppp ) {

			if( isset( $this->query->awf['ppp'] ) ) {
				$ppp = $this->query->awf['ppp'] = (int) $this->query->awf['ppp'];

				if( $ppp > absint( get_option( 'awf_ppp_limit', '200' ) ) || -1 === $ppp ) {
					$ppp = $this->query->awf['ppp'] = absint( get_option( 'awf_ppp_limit', '200' ) );
				}

			} elseif( ! empty( $this->awf_settings['ppp_default'] ) ) {
				$ppp = $this->awf_settings['ppp_default'];
			}

			return $ppp;
		}

		public function add_meta_description() {
			if ( is_shop() || is_product_category() ) {
				echo '<meta name="description" content="' . esc_attr( A_W_F::get_seo_meta_description( $this->query ) ) . '" />' . PHP_EOL;
			}
		}

		public function edit_woocommerce_before_shop_loop() {

			if( 'yes' === get_option( 'awf_remove_wc_shop_title', 'no' ) ) {
				add_filter( 'woocommerce_show_page_title', '__return_false' );
			}

			if( 'yes' === get_option( 'awf_remove_wc_orderby', 'no' ) ) {
				remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 ); // WC
				
				if ( class_exists( 'Storefront' ) ) {
					remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 10 );
					remove_action( 'woocommerce_after_shop_loop', 'woocommerce_catalog_ordering', 10 );
				}
			}

			$template_options = get_option( 'awf_product_list_template_options', array() );

			foreach( $template_options as $option => $options ) {
				foreach( $options as $id => $data ) {
					switch( $option ) {
						case( 'awf_preset' ):
							if( 'no' === get_option( 'awf_force_wrapper_reload', 'no' ) ) {
								add_action( $data['hook'], function() use ( $data ) {
									echo do_shortcode( '[annasta_filters preset_id=' . $data['preset'] . ']' );
								}, $data['priority'] );
							}
							break;
						case( 'shop_title' ):
							add_action( $data['hook'], array( $this, 'display_shop_title' ), $data['priority'] );
							break;
						case( 'orderby' ):
							add_action( $data['hook'], 'woocommerce_catalog_ordering', $data['priority'] );
							break;
						case( 'pagination' ):
							add_action( $data['hook'], 'woocommerce_pagination', $data['priority'] );
							break;
						case( 'result_count' ):
							add_action( $data['hook'], 'woocommerce_result_count', $data['priority'] );
							break;
						case( 'active_badges' ):
							if( $this instanceof A_W_F_premium_frontend ) { $this->hook_active_badges( $data ); }
							break;
						case( 'reset_btn' ):
							add_action( $data['hook'], array( $this, 'display_reset_btn' ), $data['priority'] );
							break;
						default: break;
					}
				}
			}
		}

		public function add_search_autocomplete_all_results_link( $filter ) {
			$url_query_args = $this->get_url_query();
			
			if( isset( $url_query_args[$this->vars->awf['search']] ) ) {
				$url_query_args[$this->vars->awf['search']] = urlencode( $url_query_args[$this->vars->awf['search']] );
			}
			
			echo '<div class="awf-s-autocomplete-view-all-container"><a href="' , add_query_arg( $url_query_args, $this->current_url ) , '">' , esc_html( $this->get_access_to['autocomplete_view_all_label'] ) , '</a></div>' , PHP_EOL;
		}

		public function adjust_wc_pagination( $args ) {
			if( ! empty( $this->is_sc_page ) ) {
				$args['base'] = esc_url_raw( add_query_arg( array( 'product-page' => '%#%' ), $this->current_url ) );
				
			} else {
				if( $this->permalinks_on ) {
					global $wp_rewrite;
					
					$args['base'] = str_replace( 999999999, '%#%', trailingslashit( $this->current_url ) . user_trailingslashit( $wp_rewrite->pagination_base . '/' . 999999999, 'paged' ) );
					
				} else {
					$args['base'] = esc_url_raw( add_query_arg( array( 'paged' => '%#%' ), $this->current_url ) );
				}
				
				$args['format'] = '';
			}

			$args['add_args'] = $_GET;
			unset( $args['add_args']['product-page'] );
			
			return $args;
		}

		protected function do_wc_products_shortcode( $args ) {
			
			$shortcode = '[products';
			
			foreach( $args as $k => $v ) {
				if( in_array( $k, array( 'limit', 'columns', 'rows', 'page' ) ) ) {
				$shortcode .= ' ' . $k . '="' . intval( $v ) . '"';

				} elseif( in_array( $k, array( 'paginate', 'cache' ) ) ) {

					if( is_string( $v ) ) { $v = strtolower( $v ); }
					elseif( is_bool( $v ) ) { $v = wc_bool_to_string( $v ); }

					$shortcode .= ' ' . $k . '=' . ( in_array( $v, array( 'false', '0', 'no' ), true ) ? 'false' : 'true' ) . '';

				} else {
					$shortcode .= ' ' . sanitize_key( $k ) . '="' . sanitize_text_field( $v ) . '"';
				}
			}
			
			$shortcode .= ']';

			if( ! isset( $GLOBALS['post'] ) ) { $GLOBALS['post'] = null; } // fix for the WC shortcode throwing notice for the missing 'post'
			
			echo do_shortcode( $shortcode );
		}

		public function add_awf_sc_class( $attrs ) {
			if( empty( $attrs['class'] ) ) { $attrs['class'] = 'awf-sc'; } else { $attrs['class'] .= ' awf-sc'; }

			return $attrs;
		}

		public function insert_sc_ajax_vars( $attrs ) {
			foreach( $attrs as $name => $value ) {
				if( 'class' === $name ) { continue; }
				echo '<input type="hidden" name="' . $name . '" value="' . $value . '" class="awf-sc-var">';
			}
		}

		public function display_sc_page_no_results_message( $attrs ) {
			wc_no_products_found();
		}

		public function display_ajax_pagination_resut_count() {
			$total = wc_get_loop_prop( 'total' );
			$last  = min( $total, wc_get_loop_prop( 'per_page' ) * wc_get_loop_prop( 'current_page' ) );
			
			echo '<div class="awf-ajax-pagination-result-count" style="display: none;">';
			printf( _nx( 'Showing %1$d&ndash;%2$d of %3$d result', 'Showing %1$d&ndash;%2$d of %3$d results', $total, 'with first and last result', 'woocommerce' ), '1', $last, $total );
			echo '</div>';
		}

		public function redirect_archives() {
			if( $this->is_archive ) {
				unset( $this->url_query['archive-filter'] );
				$this->url_query[$this->vars->tax[$this->is_archive]] = implode( ',', $this->query->tax[$this->is_archive] );
				wp_redirect( add_query_arg( $this->url_query, $this->shop_url ) );
				die;
			}
		}

		public function enqueue_scripts( $hook ) {
			
			$google_fonts = array();
			
			wp_enqueue_style( 'awf-nouislider', A_W_F_PLUGIN_URL . '/styles/nouislider.min.css', array(), A_W_F::$plugin_version );
			wp_enqueue_script( 'awf-wnumb', A_W_F_PLUGIN_URL . '/code/js/wNumb.js', array() );
			
			if ( 'yes' === get_option( 'awf_pretty_scrollbars' ) ) {
				wp_enqueue_style( 'awf-pretty-scrollbars', A_W_F_PLUGIN_URL . '/styles/perfect-scrollbar.css' );
				wp_enqueue_script( 'awf-pretty-scrollbars', A_W_F_PLUGIN_URL . '/code/js/perfect-scrollbar.min.js', array(), A_W_F::$plugin_version );
			}
			
			if ( ! empty( get_option( 'awf_daterangepicker_enabled' ) ) ) {
				wp_enqueue_style( 'awf-daterangepicker', A_W_F_PLUGIN_URL . '/styles/daterangepicker.css' );
				wp_enqueue_script( 'awf-moment', A_W_F_PLUGIN_URL . '/code/js/moment.min.js', array(), A_W_F::$plugin_version );
				wp_enqueue_script( 'awf-daterangepicker', A_W_F_PLUGIN_URL . '/code/js/daterangepicker.js', array( 'jquery', 'awf-moment' ), A_W_F::$plugin_version );
			}

			switch( get_option( 'awf_fontawesome_font_enqueue', 'awf' ) ) {
				case 'awf':
					wp_enqueue_style( 'awf-font-awesome', A_W_F_PLUGIN_URL . '/styles/awf-font-awesome.css', false, A_W_F::$plugin_version );
				case 'yes':
					wp_enqueue_style( 'awf-font-awesome', A_W_F_PLUGIN_URL . '/styles/awf-font-awesome-5-free.css', false, A_W_F::$plugin_version );
					wp_enqueue_style( 'awf-font-awesome-all', A_W_F_PLUGIN_URL . '/styles/fontawesome-all.min.css', array( 'awf-font-awesome' ), A_W_F::$plugin_version );
					break;
				default: break;
			}
			
			if( 'yes' === get_option( 'awf_fontawesome_font_enqueue', 'yes' ) ) {
				wp_enqueue_style( 'awf-font-awesome', A_W_F_PLUGIN_URL . '/styles/awf-font-awesome.css', false, A_W_F::$plugin_version );
			}
			
			$awf_style_file = 'awf.css';
			$awf_custom_style = get_option( 'awf_custom_style', 'none' );
			if( 'none' !== $awf_custom_style ) { $awf_style_file = 'custom-styles/awf-' . $awf_custom_style . '.css'; }
			
			if( 'yes' === get_option( 'awf_default_font_enqueue', 'yes' ) ) {
				$font = get_option( 'awf_default_font' );
				if( empty( $font ) ) { $font = A_W_F::get_awf_custom_style_default_font(); }
				if( 'inherit' !== $font ) { $google_fonts[] = 'family=' . $font . ':wght@100;200;300;400;500;600;700;800'; }
			}
			
			if( ! empty( $google_fonts ) ) {
				wp_enqueue_style( 'awf-google-fonts', 'https://fonts.googleapis.com/css2?' . implode( '&', $google_fonts), false, false );
			}
			
			wp_enqueue_style( 'awf', A_W_F_PLUGIN_URL . '/styles/' . $awf_style_file, false, A_W_F::$plugin_version );
			
			A_W_F::enqueue_style_options_css();

			wp_enqueue_script( 'awf-nouislider', A_W_F_PLUGIN_URL . '/code/js/nouislider.min.js' );
			wp_enqueue_script( 'awf', A_W_F_PLUGIN_URL . '/code/js/awf.js', array( 'jquery', 'jquery-ui-core', 'jquery-ui-sortable', 'awf-nouislider' ), A_W_F::$plugin_version );

			wp_localize_script( 'awf', 'awf_data', $this->build_js_data() );
		}

		public function load_footer_js() {
?><script type="text/javascript">
<?php echo stripcslashes( get_option( 'awf_user_js', '' ) ); ?>

</script>
<?php
		}

		protected function build_js_data() {
			$current_url_pieces = explode( '?', $this->current_url );
			$current_url_pieces[1] = isset( $current_url_pieces[1] ) ? wp_parse_args( $current_url_pieces[1] ) : array();
			
			$js_data = array( 
				'filters_url' => $current_url_pieces[0],
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'query' => array_merge( (array) $this->url_query, $current_url_pieces[1] ),
				'wrapper_reload' => get_option( 'awf_force_wrapper_reload', 'no' ),
				'reset_all_exceptions' => $this->get_reset_all_exceptions( array_keys( $current_url_pieces[1] ) ),
				'togglable_preset' => array( 
					'insert_btn_before_container' => '',
					'close_preset_on_ajax_update' => true,
				),
				'i18n' => array(
					'badge_reset_label' => esc_attr( get_option( 'awf_badge_reset_label', '' ) ),
					'togglable_preset_btn_label' => esc_attr( get_option( 'awf_toggle_btn_label', __( 'Filters', 'annasta-filters' ) ) ),
				)
			);
			
			if( $this->is_archive ) {
				$js_data['archive_page'] = $this->vars->tax[$this->is_archive];
				$js_data['query'][$this->vars->tax[$this->is_archive]] = implode( ',', $this->query->tax[$this->is_archive] );
				unset( $js_data['query'][$this->is_archive] );

				if( $this->permalinks_on ) {
					$js_data['filters_url'] = $js_data['filters_url'];
					$js_data['archive_page_switch'] = user_trailingslashit( '/' . $js_data['query'][$this->vars->tax[$this->is_archive]] );
					$js_data['archive_page_trailingslash'] = user_trailingslashit( '' );

				} else {
					$js_data['archive_page_tax'] = $this->is_archive;
				}
			}
			
			if( $this->permalinks_on ) { $js_data['permalinks_on'] = 'yes'; }
			
			if( false === $this->filter_on ) {
				$js_data['redirect_ajax'] = 'yes';
				
			} else {

				$js_data['pagination_container'] = '.woocommerce-pagination';
				/* $js_data['pagination_after'] - not set by default, can be used to control pagination insertion */
				$js_data['orderby_container'] = '.woocommerce-ordering';
				$js_data['result_count_container'] = '.woocommerce-result-count';
				$js_data['no_result_container'] = '.woocommerce-info';

				if( false === $this->is_sc_page ) {
					
					if( ! empty( get_option( 'awf_products_html_wrapper' ) ) ) {
						$js_data['products_wrapper'] = get_option( 'awf_products_html_wrapper' );
					}

				} else {
					$js_data['sc_page'] = $this->is_sc_page;
					$js_data['products_wrapper'] = '.awf-sc';
					if( 'yes' === get_option( 'awf_shortcode_title_badges', 'no' ) ) { $js_data['shortcode_title_badges'] = 'yes'; }
				}
				
				$js_data['products_container'] = '.products';

				$template_options = get_option( 'awf_product_list_template_options', array() );
				if( isset( $template_options['active_badges'] ) && false !== array_search( 'js', array_column( $template_options['active_badges'], 'hook' ) ) ) {
					$js_data['title_badges'] = 'yes';
				}
				
				if( 'none' !== ( $pagination_type = get_option( 'awf_ajax_pagination', 'none' ) ) ) {
					$js_data['ajax_pagination'] = array(
						'type' => $pagination_type,
						'page_number' => 'a.page-numbers',
						'next' => '.next',
						'product_container' => '.product',
					);
					
					if( 'more_button' === $pagination_type ) { $js_data['i18n']['ajax_pagination_more_button'] = __( 'Load more products', 'annasta-filters' ); }
				}
				
				if( 'yes' === get_option( 'awf_ajax_scroll_on', 'no' ) ) {
					$js_data['ajax_scroll'] = (int) get_option( 'awf_ajax_scroll_adjustment', 50 );
				}
			}
			
			$js_data = apply_filters( 'awf_js_data', $js_data );
			if( empty( $js_data['query'] ) ) { $js_data['query'] = array(); }
			$js_data['query'] = new ArrayObject( $js_data['query'] );

			return $js_data;
		}

		public function display_shortcode( $parameters ) {
			return $this->get_preset_html( $parameters['preset_id'], $parameters['shortcode_id'] );
		}

		public function display_widget( $preset_id, $parameters ) {
			
			if( empty( $parameters['id'] ) ) { $parameters['id'] = 'awf-widget-' . A_W_F::$caller_id; }
			
			A_W_F::$caller_id++;
			
			echo $this->get_preset_html( intval( $preset_id ), $parameters['id'] );
		}

		protected function get_preset_html( $preset_id, $caller_id ) {
			
			if( ! isset( A_W_F::$presets[$preset_id] ) ) { return ''; };
			
			if( is_null( $this->page_associations ) ) {
				$this->build_page_associations();

				$counts_cache_query = clone $this->query;
				unset( $counts_cache_query->awf['orderby'] );
				unset( $counts_cache_query->awf['ppp'] );

				if( class_exists( 'SitePress' ) && ! empty( $this->get_current_language() ) ) {
					$counts_cache_query->awf_lang = $this->language;
				}

				$this->counts_cache_name = 'awf_counts_' . md5( wp_json_encode( $counts_cache_query ) );
				$this->counts = get_transient( $this->counts_cache_name );
				if( false === $this->counts ) { $this->counts = array(); }
			}

			$associated = false;
			
			foreach( A_W_F::$presets[$preset_id]['associations'] as $association_id ) {
				if( $this->in_page_associations( $association_id ) ) {
					$associated = true;
					break;
				}
			}
			
			if( ! $associated ) { return ''; }
			
			$preset_id = apply_filters( 'awf_display_preset_id', $preset_id );
			
			$this->preset = new A_W_F_preset_frontend( $preset_id, $caller_id );
			
			return $this->preset->get_html();
		}

		protected function build_page_associations() {

			$this->page_associations = array();
			$this->page_parent_associations = array();

			foreach( $this->query->tax as $taxonomy => $terms ) {
				
				foreach( $terms as $slug ) {
					if( false !== ( $term = get_term_by( 'slug', $slug, $taxonomy ) ) ) {
						$this->add_associations( $term, $taxonomy );
					}
				}
			}
		}

		protected function add_associations( $term, $taxonomy, $is_parent = false ) {

			if( $term->parent > 0 ) {
				if( false !== ( $parent = get_term_by( 'id', $term->parent, $taxonomy ) ) ) {
					$this->add_associations( $parent, $taxonomy, true );
				}
			}

			if(
				! isset( $this->page_associations[$taxonomy] )
				|| ! isset( $this->page_associations[$taxonomy][$term->parent] )
				|| ! in_array( $term->slug, $this->page_associations[$taxonomy][$term->parent] )
			) {
				
				switch( $is_parent ) {
					case( true ):
						if( 'no' === get_option( 'awf_include_parents_in_associations', 'yes' ) ) {
							$this->page_parent_associations[$taxonomy][$term->parent][] = $term->slug;
							break;
						}
					default:
						$this->page_associations[$taxonomy][$term->parent][] = $term->slug;
						break;
				}
				
			}

			return;
		}

		public function in_page_associations( $association_id ) {

			if( ( $association_id === 'all' )
				|| ( $association_id === 'shop-pages' && is_shop() )
				|| ( $this->is_archive && $association_id === $this->is_archive . '--archive-pages' )
				|| ( false !== $this->is_wp_page && $association_id === 'wp_page--' . $this->is_wp_page )
				) {
					return true;
			}
			
			$association = explode( '--', $association_id );

			if( isset( $this->page_associations[$association[0]] ) ) {
				
				if( isset( $association[2] ) ) {
					if( ( 'shop-page' === $association[2] && is_shop() ) || ( 'archive-page' === $association[2] && $this->is_archive ) ) {
						foreach( $this->page_associations[$association[0]] as $term ) {
							if( in_array( $association[1], $term ) ) {
								return true;
							}
						}
					}

				} elseif( isset( $association[1] ) && 'shop-pages' === $association[1] && is_shop() ) {
					return true;
				}
			}

			return false;
		}

		public function get_url_query() {

			$url_query = array();
			$delete_params = array( 's' => '', 'paged' => '', 'product-page' => '' );

			foreach( $this->query->tax as $var => $value ) {
				$url_query[$this->vars->tax[$var]] = implode( ',', $value );
			}
			
			foreach( $this->query->awf as $var => $value ) {
				$url_query[$this->vars->awf[$var]] = $value;
			}
			
			foreach( $this->query->meta as $var => $value ) {
				$url_query[$this->vars->meta[$var]] = implode( ',', $value );
			}
			
			foreach( $this->query->range as $var => $value ) {
				$url_query[$this->vars->range[$var]] = $value;
				
				$var = substr( $var, 4 );
				if( isset( $this->vars->tax[$var] ) ) { $delete_params[$this->vars->tax[$var]] = ''; }
			}
			
			if( $this->filter_on ) { $url_query = array_merge( $url_query, $_GET ); }
			
			$url_query = array_diff_key( $url_query, $this->vars->tax, $delete_params );
			
			if( isset( $url_query[$this->vars->awf['search']] ) ) {
				$url_query[$this->vars->awf['search']] = str_replace( array( '\"', "\'" ), array( '"', "'" ), $url_query[$this->vars->awf['search']] );
			}
			
			if( $this->is_archive ) {
				$url_query['archive-filter'] = 1;

				if( $this->permalinks_on ) {
					unset( $url_query[$this->vars->tax[$this->is_archive]] );
				} else {
					$url_query[$this->is_archive] = $url_query[$this->vars->tax[$this->is_archive]];
					unset( $url_query[$this->vars->tax[$this->is_archive]] );
				}
			}
			
			return $url_query;
		}
		
		public function get_reset_all_exceptions( $exceptions = array() ) {
			$exceptions = array_merge( $exceptions, array( 'ppp', 'orderby' ) );

			if( $this->is_archive ) { array_push( $exceptions, $this->is_archive ); }

			return $exceptions;
		}

		public function display_shop_title() {
			if( ! $this->is_sc_page ) {
				echo '<h1 class="woocommerce-products-header__title page-title">', woocommerce_page_title( false ), '</h1>';
			}
		}

		public function display_no_results_msg( $attrs ) {
			
			if( $this->is_archive ) {
				$term = get_term_by( 'slug', reset( $this->query->tax[$this->is_archive] ), $this->is_archive );
				if( $term ) { echo '<h1 class="woocommerce-products-header__title page-title">', $term->name, '</h1>'; }
				
			} elseif( ! $this->is_sc_page ) {
				if( 'yes' === get_option( 'awf_force_wrapper_reload', 'no' ) ) {
					if( 'yes' === get_option( 'awf_remove_wc_shop_title', 'no' ) ) {
						echo '<div class="awf-wc-shop-title" style="display: none;">', woocommerce_page_title( false ), '</div>';
					} else {
						echo '<h1 class="woocommerce-products-header__title page-title">', woocommerce_page_title( false ), '</h1>';
					}

				} else {
					if( 'seo' === get_option( 'awf_shop_title', 'wc_default' ) ) {
						echo '<div class="awf-wc-shop-title" style="display: none;">', woocommerce_page_title( false ), '</div>';
					}
				}

				echo '<div class="awf-document-title" style="display: none;">', wp_get_document_title(), '</div>',
					'<div class="awf-meta-description" style="display: none;">', esc_attr( A_W_F::get_seo_meta_description( $this->query ) ), '</div>'
				;
			}
			
			wc_no_products_found();
		}

		public function add_ajax_products_header() {
			echo '<header class="woocommerce-products-header">';

			if( $this->is_archive ) {
				$term = get_term_by( 'slug', reset( $this->query->tax[$this->is_archive] ), $this->is_archive );
				if( $term ) {
					do_action( 'awf_add_ajax_products_header_title', $term->name );

					if( 'yes' === get_option( 'awf_breadcrumbs_support', 'yes' ) ) {
						$this->add_awf_breadcrumbs_support();
					}
				}
				
			} elseif( ! $this->is_sc_page ) {
				do_action( 'awf_add_ajax_products_header_title', woocommerce_page_title( false ) );
				echo '<div class="awf-document-title" style="display: none;">' , wp_get_document_title() , '</div>' ,
					'<div class="awf-meta-description" style="display: none;">' , esc_attr( A_W_F::get_seo_meta_description( $this->query ) ) , '</div>'
				;
			}
			
			echo '</header>';
		}
		
		public function add_awf_breadcrumbs_support() {

			if( ! empty( $this->query->tax[$this->is_archive] ) ) {

				$terms = array();
				
				foreach( $this->query->tax[$this->is_archive] as $slug ) {
					$term = get_term_by( 'slug', $slug, $this->is_archive );
					if( false !== $term ) { $terms[] = $term->name; }
				}

				$crumbs = '<span id="awf-breadcrumbs-support" style="display: none;">';
				$crumbs .= implode( get_option( 'awf_seo_filter_values_separator', ', ' ), $terms );				
				$crumbs .= '</span>';
			}

			echo $crumbs;
		}
		
		public function add_ajax_products_header_title( $title = false ) {
			if( empty( $title ) ) {
				if( 'yes' === get_option( 'awf_force_wrapper_reload', 'no' ) ) {
					echo '<div class="awf-wc-shop-title" style="display: none;"></div>';
				} else {
					if( 'seo' === get_option( 'awf_shop_title', 'wc_default' ) ) {
						echo '<div class="awf-wc-shop-title" style="display: none;"></div>';
					}
				}

			} else {
				if( 'yes' === get_option( 'awf_force_wrapper_reload', 'no' ) ) {
					if( 'yes' === get_option( 'awf_remove_wc_shop_title', 'no' ) ) {
						echo '<div class="awf-wc-shop-title" style="display: none;">', woocommerce_page_title( false ), '</div>';
					} else {
						echo '<h1 class="woocommerce-products-header__title page-title">', woocommerce_page_title( false ), '</h1>';
					}

				} else {
					if( 'seo' === get_option( 'awf_shop_title', 'wc_default' ) ) {
						echo '<div class="awf-wc-shop-title" style="display: none;">', woocommerce_page_title( false ), '</div>';
					}
				}
			}
		}
		
		public function adjust_shop_title( $title ) {
			
			switch( get_option( 'awf_shop_title', 'wc_default' ) ) {
				case 'awf_default':
					$title = get_option( 'awf_default_shop_title', _x( 'Shop', 'Default page title', 'annasta-filters' ) );
					break;
				case 'seo':
					$title = A_W_F::get_seo_title( $this->query, 'shop' );
					break;
				default:
					break;
			}

			return $title;
		}

		public function adjust_document_title( $title ) {
			/* $title = array( 'title', 'page', 'tagline', 'site' ) */

			if ( is_shop() || ( wp_doing_ajax() && isset( $_REQUEST['awf_front'] ) )
			) {
				$title = $this->get_awf_page_title( $title );
			}

			return $title;
		}
		
		protected function get_awf_page_title( $title = array( 'title' => '' ) ) {
			switch( get_option( 'awf_page_title', 'wc_default' ) ) {
				case 'awf_default':
					$title['title'] = get_option( 'awf_default_page_title', _x( 'Shop', 'Default page title', 'annasta-filters' ) );
					$title['tagline'] = '';
					$title['site'] = '';
					break;
				case 'seo':
					$title['title'] = A_W_F::get_seo_title( $this->query );
					$title['tagline'] = '';
					$title['site'] = '';
					break;
				default:
					$title['title'] = get_the_title( $this->shop_page_id );
					break;
			}
			
			return $title;
		}

		public function adjust_breadcrumbs( $crumbs, $class ) {

			if( is_archive() && ! empty( $this->query->tax[$this->is_archive] ) ) {
				array_pop( $crumbs );
				$terms = array();
				
				foreach( $this->query->tax[$this->is_archive] as $slug ) {
					$term = get_term_by( 'slug', $slug, $this->is_archive );
					if( false !== $term ) { $terms[] = $term->name; }
				}
				
				$crumbs[] = array( implode( get_option( 'awf_seo_filter_values_separator', ', ' ), $terms ) );
			}

			return $crumbs;
		}
		
		public function display_togglable_presets() {
			foreach( array_keys( A_W_F::$presets ) as $preset_id ) {
				if( 'togglable' === get_option( 'awf_preset_' . $preset_id . '_display_mode', 'visible' ) ) {
					echo do_shortcode( '[annasta_filters preset_id=' . $preset_id . ']' );
				}
			}
		}
		
		public function get_current_language() {
			
			if( ! is_null( $this->language ) ) { return $this->language; }
			
			$this->language = false;
		
			if( class_exists( 'SitePress' ) ) {
				/* WPML */
				
				/* ICL_LANGUAGE_CODE keeps the initial language before any $sitepress->switch_lang() would occur */
				
				if ( apply_filters( 'wpml_default_language', NULL ) !== ( $language = apply_filters( 'wpml_current_language', NULL ) ) ) {
					$this->language = $language;
				}
				
			} else if ( function_exists( 'pll_default_language' ) ) {
				/* Polylang */
				
				if ( pll_default_language() !== ( $language = pll_current_language() ) ) {
					$this->language = $language;
				}
				
			} else if ( function_exists( 'qtranxf_getLanguageDefault' ) ) {
				/* qTranslate */
				
				if ( qtranxf_getLanguageDefault() !== ( $language = qtranxf_getLanguage() ) ) {
					$this->language = $language;
				}
			}
			
			return $this->language;
		}
				
		protected function maybe_add_wpml_adjustments() {
			global $sitepress;

			if( $sitepress->is_display_as_translated_post_type( 'product' ) && ( $sitepress->get_current_language() !== $sitepress->get_default_language() ) ) {
				if( class_exists( 'WPML_Display_As_Translated_Tax_Query_Factory' ) ) {
					( new WPML_Display_As_Translated_Tax_Query_Factory() )->create()->add_hooks();
					add_filter( 'posts_where', array( $this, 'add_wpml_wp_query_adjustments' ), 9, 2 ); // priority 9 before WPML hook
					add_filter( 'posts_where', array( $this, 'remove_wpml_wp_query_adjustments' ), 11, 2 ); // priority 11 after WPML hook
				}
			}
		}
		
		public function add_wpml_wp_query_adjustments( $where, WP_Query $query ) {
			$query->awf_query_backup = array(
				'is_archive' => $query->is_archive,
				'is_tax' => $query->is_tax,
			);

			$query->is_archive = true;
			$query->is_tax = true;

			return $where;
		}
		
		public function remove_wpml_wp_query_adjustments( $where, WP_Query $query ) {
			$query->is_archive = isset( $query->awf_query_backup['is_archive'] ) ? $query->awf_query_backup['is_archive'] : $query->is_archive;
			$query->is_tax = isset( $query->awf_query_backup['is_tax'] ) ? $query->awf_query_backup['is_tax'] : $query->is_tax;

			unset( $query->awf_query_backup );

			return $where;
		}

		protected function sort_query() {
			ksort( $this->query->tax );
			ksort( $this->query->awf );
			ksort( $this->query->meta );
			ksort( $this->query->range );

			array_walk( $this->query->tax, function( &$value, $key ) {
				if( is_array( $value ) ) { sort( $value ); }
			});

			array_walk( $this->query->meta, function( &$value, $key ) {
				if( is_array( $value ) ) { sort( $value ); }
			});
		}

		public function update_counts_cache() {
			if( true === $this->update_counts_cache && ! empty( $this->counts ) ) {
				if( ! empty( $lifespan = intval( get_option( 'awf_counts_cache_days', '30' ) ) ) ) {
					set_transient( $this->counts_cache_name, $this->counts, DAY_IN_SECONDS * $lifespan );
				}
			}
		}
		
		final function __clone() {}
		final function __wakeup() {}
		public static function get_instance() {
			if( is_null( self::$instance ) ) {
				$called_class = get_called_class();
				self::$instance = new $called_class;
			}
			return self::$instance;
		}
		
//A_W_F::format_print_r($query);

	}
}

?>