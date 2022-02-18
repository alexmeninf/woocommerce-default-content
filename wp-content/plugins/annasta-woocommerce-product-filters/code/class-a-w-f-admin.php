<?php

defined( 'ABSPATH' ) or die( 'Access denied' );

if(! class_exists('A_W_F_admin') ) {
  
  class A_W_F_admin {
    
    /** Current running instance of A_W_F_admin or A_W_F_premium_admin object
     *
     * @since 1.0.0
     * @var A_W_F_admin (or A_W_F_premium_admin) object
     */
    protected static $instance;
    
    /** Allowed filter control types (single select, multiple select, range select)
     *
     * @since 1.0.0
     * @var array
     */
    public $filter_types;
    
    /** Allowed filter styles
     *
     * @since 1.0.0
     * @var array
     */
    public $filter_styles;
    
    /** Extentions' type and style limitations
     *
     * @since 1.0.0
     * @var array
     */
    public $filter_style_limitations;
    
    protected function __construct() {
      if ( version_compare( A_W_F_VERSION, get_option( 'awf_version', '0.0.0' ) ) > 0 ) {
        add_action( 'plugins_loaded', array( $this, 'after_plugin_activation' ), 30 );
      }
      
      add_action( 'admin_menu', array( $this, 'add_plugin_menu' ) );
      add_filter( 'plugin_action_links_' . plugin_basename( A_W_F_PLUGIN_FILE ), array( $this, 'plugin_settings_link' ) );
      add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );
      add_filter( 'woocommerce_get_settings_pages', array( $this, 'set_plugin_settings_tab' ) );
      add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ), 20 );
      add_action( 'wp_ajax_awf_admin', array( $this, 'ajax_controller' ) );
      
      add_action( 'before_delete_post', array( $this, 'on_product_deletion') );
      add_action( 'wp_trash_post', array( $this, 'on_product_trashing') );
      add_filter( 'untrashed_post', array( $this, 'on_product_untrashing') );
      add_action( 'woocommerce_update_product', array( $this, 'on_product_update' ) );
      add_action( 'created_product_cat', array( $this, 'on_product_cat_created' ), 10, 2 );
      add_action( 'delete_product_cat', array( $this, 'on_product_cat_deleted' ), 10, 4 );
      
      $this->filter_types = array(
        'single' => array(
          'label' => __( 'Single item selection', 'annasta-filters' ),
          'styles' => array( 'radios', 'icons', 'labels', 'colours' )
        ),
        'multi' => array(
          'label' => __( 'Multiple items selection', 'annasta-filters' ),
          'styles' => array( 'checkboxes', 'icons', 'colours', 'tags' )
        ),
        'range' => array(
          'label' => __( 'Range selection', 'annasta-filters' ),
          'styles' => array( 'range-slider', 'radios', 'icons', 'labels', 'range-stars' )
        ),
        'date' => array(
          'label' => __( 'Dates selection', 'annasta-filters' ),
          'styles' => array( 'daterangepicker' )
        )
      );

      $this->filter_styles = array(
        'checkboxes' => __( 'System checkboxes', 'annasta-filters' ),
        'radios' => __( 'System radio buttons', 'annasta-filters' ),
        'range-slider' => __( 'Range slider', 'annasta-filters' ),
        'range-stars' => __( 'Stars', 'annasta-filters' ),
        'labels' => __( 'Labels', 'annasta-filters' ),
        'icons' => __( 'Custom icons', 'annasta-filters' ),
        'images' => __( 'Images', 'annasta-filters' ),
        'colours' => __( 'Color boxes', 'annasta-filters' ),
        'custom-terms' => __( 'Custom term icons and labels', 'annasta-filters' ),
        'tags' => __( 'Tags', 'annasta-filters' ),
        'daterangepicker' => __( 'Date picker', 'annasta-filters' ),
      );

      $this->filter_style_limitations = array(
        'taxonomy' => array(
          'single' => array( 'radios', 'labels', 'icons', 'images', 'colours', 'custom-terms' ),
          'multi' => array( 'checkboxes', 'icons', 'images', 'colours', 'tags', 'custom-terms' ),
        ),
        'price' => array(
          'range' => array( 'range-slider', 'radios', 'icons', 'labels', 'images', 'custom-terms' )
        ),
        'stock' => array(
          'single' => array( 'radios', 'icons', 'labels', 'images', 'custom-terms' )
        ),
        'featured' => array(
          'multi' => array( 'checkboxes', 'icons', 'labels', 'images', 'custom-terms' )
        ),
        'rating' => array(
          'range' => array( 'radios', 'icons', 'labels', 'images', 'custom-terms' )
        ),
        'onsale' => array(
          'multi' => array( 'checkboxes', 'icons', 'labels', 'images', 'custom-terms' )
        ),
        'ppp' => array(
          'single' => array( 'radios', 'icons', 'labels', 'images' )
        ),
        'orderby' => array(
          'single' => array( 'radios', 'icons', 'labels', 'images', 'custom-terms' )
        ),
        'meta' => array(
          'single' => array( 'radios', 'labels', 'icons', 'images', 'colours', 'custom-terms' ),
          'multi' => array( 'checkboxes', 'icons', 'images', 'colours', 'tags', 'custom-terms' ),
          'range' => array( 'range-slider', 'radios', 'icons', 'labels', 'images', 'custom-terms' ),
          'date' => array( 'daterangepicker' ),
        ),
      );
    }
    
    public function add_plugin_menu() {
      add_menu_page(
          __( 'annasta Filters Settings', 'annasta-filters' ),
          __( 'annasta Filters', 'annasta-filters' ),
          'manage_woocommerce',
          'annasta-filters',
          array( $this, 'safe_redirect_to_settings' ),
          'dashicons-filter',
          56
      );
      
      add_submenu_page( 'annasta-filters', '', __( 'Filter presets', 'annasta-filters' ), 'manage_woocommerce', 'annasta-filters', array( $this, 'safe_redirect_to_settings') );
      
      add_submenu_page( 'annasta-filters', '', __( 'Product lists', 'annasta-filters' ), 'manage_woocommerce', 'annasta-filters-product-list-settings', array( $this, 'safe_redirect_to_product_list_settings') );
      
      add_submenu_page( 'annasta-filters', '', __( 'Style settings', 'annasta-filters' ), 'manage_woocommerce', 'annasta-filters-styles-settings', array( $this, 'safe_redirect_to_styles_settings') );
      
      add_submenu_page( 'annasta-filters', '', __( 'SEO settings', 'annasta-filters' ), 'manage_woocommerce', 'annasta-filters-seo-settings', array( $this, 'safe_redirect_to_seo_settings') );
      
      add_submenu_page( 'annasta-filters', '', __( 'Plugin settings', 'annasta-filters' ), 'manage_woocommerce', 'annasta-filters-plugin-settings', array( $this, 'safe_redirect_to_plugin_settings') );
    }
    
    public function after_plugin_activation() {
      
      if( version_compare( PHP_VERSION, '5.5' ) < 0 ) {
        add_action( 'admin_notices', array( $this, 'display_php_version_warning' ) );
      }
        
      if( false === get_option( 'awf_version', false ) ) {
        /* Fresh installation */
				
			} else {
        /* Updates */
        
        if( version_compare( get_option( 'awf_version', '0.0.0' ), '1.2.0', '<' ) ) { update_option( 'awf_redirect_archives', 'yes' ); }
        
        if( version_compare( get_option( 'awf_version', '0.0.0' ), '1.2.8', '<' ) ) {
          if( ! empty( $color_image_style = get_option( 'awf_color_image_style', false ) ) ) {
            update_option( 'awf_color_filter_style', $color_image_style );
            update_option( 'awf_image_filter_style', $color_image_style );
          }
          delete_option( 'awf_color_image_style' );
        }
        
        if( version_compare( get_option( 'awf_version', '0.0.0' ), '1.3.0', '<' ) ) {
					update_option( 'awf_custom_style', 'deprecated-1-3-0' );
					if( 'yes' === get_option( 'awf_remove_wc_orderby', 'no' ) ) { update_option( 'awf_display_wc_orderby', 'no' ); }
        }
        
        if( version_compare( get_option( 'awf_version', '0.0.0' ), '1.4.0', '<' ) ) {
          /* Deprecated options: awf_display_wc_shop_title, awf_display_wc_orderby, awf_shop_title_badges */

          if( false === get_option( 'awf_product_list_template_options', false ) ) {
            if( 'no' === get_option( 'awf_display_wc_shop_title', 'yes' ) ) { update_option( 'awf_remove_wc_shop_title', 'yes' ); }
            if( 'no' === get_option( 'awf_display_wc_orderby', 'yes' ) ) { update_option( 'awf_remove_wc_orderby', 'yes' ); }
            if( 'yes' === get_option( 'awf_shop_title_badges', 'no' ) ) {
              update_option( 'awf_product_list_template_options', array( 'active_badges' => array( array( 'hook' => 'js', 'priority' => 15 ) ) ) );
            }
          }

          update_option( 'awf_force_wrapper_reload', 'yes' );
        }

        /* Presets and Filters updates */
        $update_presets = false;
        
        foreach( A_W_F::$presets as $preset_id => $preset ) {
          
          if( version_compare( get_option( 'awf_version', '0.0.0' ), '1.1.2', '<' ) ) {
            
            $ajax_on = get_option( 'awf_preset_' . $preset_id . '_ajax_on', '' );
            $button_on = get_option( 'awf_preset_' . $preset_id . '_button_on', '' );
            $type = 'ajax';

            if( 'yes' === $ajax_on ) {
              if( 'yes' === $button_on ) {
                $type = 'ajax-button';
              }

            } else {
              if( 'yes' === $button_on ) {
                $type = 'form';
              } else {
                $type = 'url';
              }
            }

            update_option( 'awf_preset_' . $preset_id . '_type', $type );
            delete_option( 'awf_preset_' . $preset_id . '_ajax_on' );
            delete_option( 'awf_preset_' . $preset_id . '_button_on' );
          }
          
          if( version_compare( get_option( 'awf_version', '0.0.0' ), '1.2.7', '<' ) ) {
            
            foreach( $preset['associations'] as $i => $association_id ) {
              if( in_array( $association_id, array( 'all', 'shop-pages' ) ) ) { continue; }
              
              $association_data = explode( '--', $association_id );
              
              if( ! isset( $association_data[0] ) || 'wp_page' === $association_data[0] ) { continue; }
              if( ! isset( $association_data[1] ) || in_array( $association_data[1], array( 'archive-pages', 'shop-pages' ) ) ) { continue; }
              
              A_W_F::$presets[$preset_id]['associations'][$i] = $association_id . '--shop-page';
              $update_presets = true;
            }
          }
          
          foreach( $preset['filters'] as $filter_id => $position ) {
            $filter = new A_W_F_filter( $preset_id, $filter_id );
            
            if( version_compare( get_option( 'awf_version' ), '1.0.7', '<' ) ) {
              
              if( ! in_array( $filter->module, array( 'featured', 'onsale', 'ppp' ) )
                  && ! array_key_exists( 'active_prefix', $filter->settings )
              ) {
                $active_prefix = '';
                
                if( isset( $filter->settings['style_options']['badge_label'] ) ) {
                  $active_prefix = sanitize_text_field( $filter->settings['style_options']['badge_label'] );
                  unset( $filter->settings['style_options']['badge_label'] );
                }

                $position = 3;
                if( 'taxonomy' === $filter->module ) { $position = 4; }

                $filter->settings = array_merge(
                  array_slice( $filter->settings, 0, $position, true ),
                  array( 'active_prefix' => $active_prefix ),
                  array_slice( $filter->settings, $position, count( $filter->settings ) - 1, true )
                );
              }
            }
            
            if( version_compare( get_option( 'awf_version', '0.0.0' ), '1.1.2', '<' ) ) {
              if( 'range-slider' === $filter->settings['style'] ) {
                if( ! isset( $filter->settings['type_options']['decimals'] ) ) { $filter->settings['type_options']['decimals'] = intval( 0 ); }
                if( ! isset( $filter->settings['style_options']['step'] ) ) { $filter->settings['style_options']['step'] = floatval( 1 ); }
                if( ! isset( $filter->settings['style_options']['value_prefix'] ) ) { $filter->settings['style_options']['value_prefix'] = ''; }
                if( ! isset( $filter->settings['style_options']['value_postfix'] ) ) { $filter->settings['style_options']['value_postfix'] = ''; }
              }
            }
            
            if( version_compare( get_option( 'awf_version', '0.0.0' ), '1.2.8', '<' ) ) {
              if( ! isset( $filter->settings['is_collapsible'] ) ) {
                $position = intval( array_search( 'type', array_keys( $filter->settings ) ) );
                
                if( ! empty( $position ) ) {
                  $filter->settings = array_merge(
                    array_slice( $filter->settings, 0, $position, true),
                    array( 'is_collapsible' => false, 'collapsed_on' => false ),
                    array_slice( $filter->settings, $position, count( $filter->settings ) - 1, true)
                  );
                }
              }
            }

            if( A_W_F::$premium && version_compare( get_option( 'awf_version', '0.0.0' ), '1.4.0', '<' ) ) {
              if( 'taxonomy' === $filter->module ) {
                $position = intval( array_search( 'reset_all', array_keys( $filter->settings ) ) );

                if( ! empty( $position ) && ! isset( $filter->settings['reset_active'] ) && ! isset( $filter->settings['reset_active_label'] ) ) {
                  $filter->settings = array_merge(
                    array_slice( $filter->settings, 0, $position, true),
                    array( 'reset_active' => false, 'reset_active_label' => _x( 'Clear filters', 'Label for single filter reset button', 'annasta-filters' ) ),
                    array_slice( $filter->settings, $position, count( $filter->settings ) - 1, true)
                  );
                }
              }

              if( in_array( $filter->module, array( 'taxonomy', 'price', 'stock', 'ppp', 'orderby', 'meta' ) ) ) {
                $position = intval( array_search( 'type', array_keys( $filter->settings ) ) );

                if( ! empty( $position ) && ! isset( $filter->settings['active_dropdown_title'] ) ) {
                  $filter->settings = array_merge(
                    array_slice( $filter->settings, 0, $position, true),
                    array( 'active_dropdown_title' => false ),
                    array_slice( $filter->settings, $position, count( $filter->settings ) - 1, true)
                  );
                }
              }
            }

            if( version_compare( get_option( 'awf_version', '0.0.0' ), '1.4.5', '<' ) ) {
              if( 'taxonomy' === $filter->module ) {
                $position = intval( array_search( 'children_collapsible', array_keys( $filter->settings ) ) );

                if( ! empty( $position ) && ! isset( $filter->settings['display_children'] ) ) {
                  $filter->settings = array_merge(
                    array_slice( $filter->settings, 0, $position, true),
                    array(
                      'hierarchical_level' => 1,
                      'display_children' => true,
                    ),
                    array_slice( $filter->settings, $position, count( $filter->settings ) - 1, true)
                  );
                }

                if( A_W_F::$premium ) {
                  $position = intval( array_search( 'show_search', array_keys( $filter->settings ) ) );

                  if( ! empty( $position ) && ! isset( $filter->settings['hierarchical_sbs'] ) ) {
                    $filter->settings = array_merge(
                      array_slice( $filter->settings, 0, $position, true),
                      array( 'hierarchical_sbs' => false, 'hide_preset_submit_btn' => false ),
                      array_slice( $filter->settings, $position, count( $filter->settings ) - 1, true)
                    );
                  }
                }
              }
            }

            if( version_compare( get_option( 'awf_version', '0.0.0' ), '1.4.6', '<' ) ) {
              if( isset( $filter->settings['style'] ) && 'custom-terms' === $filter->settings['style'] && isset( $filter->settings['style_options']['term_icons'] ) && isset( $filter->settings['style_options']['term_icons_solids'] ) && ! isset( $filter->settings['style_options']['term_icons_hover'] ) ) {
                $filter->settings['style_options']['term_icons_hover'] = $filter->settings['style_options']['term_icons'];
                $filter->settings['style_options']['term_icons_active'] = $filter->settings['style_options']['term_icons'];
                $filter->settings['style_options']['term_icons_active_hover'] = $filter->settings['style_options']['term_icons'];
                $filter->settings['style_options']['term_icons_hover_solids'] = $filter->settings['style_options']['term_icons_solids'];
                $filter->settings['style_options']['term_icons_active_solids'] = $filter->settings['style_options']['term_icons_solids'];
                $filter->settings['style_options']['term_icons_active_hover_solids'] = $filter->settings['style_options']['term_icons_solids'];
              }
            }

            if( version_compare( get_option( 'awf_version', '0.0.0' ), '1.4.7', '<' ) ) {
              if( isset( $filter->settings['excluded_items'] ) && ! isset( $filter->settings['terms_limitation_mode'] ) ) {
                $position = intval( array_search( 'excluded_items', array_keys( $filter->settings ) ) );

                $filter->settings = array_merge(
                  array_slice( $filter->settings, 0, $position, true),
                  array( 'terms_limitation_mode' => 'exclude', 'included_items' => array() ),
                  array_slice( $filter->settings, $position, count( $filter->settings ) - 1, true)
                );
              }
            }

            update_option( $filter->prefix. 'settings', $filter->settings );
          }
        }
        
        if( $update_presets ) { update_option( 'awf_presets', A_W_F::$presets ); }
        
      }
      
      /* Options and actions added by versions after the plugin activation */
      
      if( version_compare( get_option( 'awf_version', '0.0.0' ), '1.1.0', '<' ) ) {
        if( empty( get_option( 'awf_product_cat_pretty_name' ) ) ) { update_option( 'awf_product_cat_pretty_name', 'product-categories' ); }
        if( empty( get_option( 'awf_product_tag_pretty_name' ) ) ) { update_option( 'awf_product_tag_pretty_name', 'product-tags' ); }
      }
      
      if( version_compare( get_option( 'awf_version', '0.0.0' ), '1.4.8', '<' ) ) {
        add_action( 'init', array( 'A_W_F', 'build_query_vars' ) );
      }

      $this->generate_styles_css();
      
      update_option( 'awf_version', A_W_F_VERSION );
    }
    
    public function display_php_version_warning() {
      echo '<div class="notice notice-error"><p>',
      sprintf( esc_html__( 'annasta Woocommerce Product Filters requires PHP Version 5.5 or later to function. Your server currently runs PHP version %1$s. Please, install the newer version of PHP on your server for the plugin to function properly.', 'annasta-filters' ), PHP_VERSION ),
      '</p></div>';
    }
    
    public function set_plugin_settings_tab( $tabs ) {
      $tabs[] = new A_W_F_settings();
      
      return $tabs;
    }
    
    public function plugin_settings_link( $links ) {
      if ( current_user_can( 'manage_woocommerce' ) ) {
        $settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=annasta-filters' ) ) . '" aria-label="' . esc_attr__( 'View annasta Filters settings', 'annasta-filters' ) . '">' . esc_html__( 'Settings', 'annasta-filters' ) . '</a>';

        array_unshift( $links, $settings_link );
      }
      
      return $links;
    }
    
    public function plugin_row_meta( $links, $file ) {
      if ( strpos( $file, 'annasta-woocommerce-product-filters.php' ) !== false ) {
        $new_links = array(
            'documentation' => '<a href="' . esc_url( 'https://annasta.net/plugins/annasta-woocommerce-product-filters/documentation/' ) . '" aria-label="' . esc_attr__( 'View annasta Filters documentation', 'annasta-filters' ) . '" target="_blank">' . esc_html__( 'Documentation', 'annasta-filters' ) . '</a>'
        );

        $links = array_merge( $links, $new_links );
      }

      return $links;
    }

    public function redirect_to_presets_tab( $args = array() ) {
      $redirect_url = add_query_arg( $args, admin_url( 'admin.php?page=wc-settings&tab=annasta-filters' ) );

      wp_redirect( $redirect_url );
      exit();
    }

    public function safe_redirect_to_settings() {
      $redirect_url = admin_url( 'admin.php?page=wc-settings&tab=annasta-filters' );

      wp_safe_redirect( $redirect_url );
      exit();
    }

    public function safe_redirect_to_product_list_settings() {
      $redirect_url = admin_url( 'admin.php?page=wc-settings&tab=annasta-filters&section=product-list-settings' );

      wp_safe_redirect( $redirect_url );
      exit();
    }

    public function safe_redirect_to_styles_settings() {
      $redirect_url = admin_url( 'admin.php?page=wc-settings&tab=annasta-filters&section=styles-settings' );

      wp_safe_redirect( $redirect_url );
      exit();
    }

    public function safe_redirect_to_seo_settings() {
      $redirect_url = admin_url( 'admin.php?page=wc-settings&tab=annasta-filters&section=seo-settings' );

      wp_safe_redirect( $redirect_url );
      exit();
    }

    public function safe_redirect_to_plugin_settings() {
      $redirect_url = admin_url( 'admin.php?page=wc-settings&tab=annasta-filters&section=plugin-settings' );

      wp_safe_redirect( $redirect_url );
      exit();
    }
    
    public function enqueue_admin_scripts( $hook ) {
      
      if( isset( $_GET['tab'] ) && 'annasta-filters' === $_GET['tab'] ) {
        
        if( isset( $_GET['awf-preset'] ) ) {
          wp_enqueue_style( 'wp-color-picker' );
          wp_enqueue_style( 'awf-nouislider-styles', A_W_F_PLUGIN_URL . '/styles/nouislider.min.css', array() );
          wp_enqueue_script( 'awf-nouislider', A_W_F_PLUGIN_URL . '/code/js/nouislider.min.js', array() );
          wp_enqueue_script( 'awf-wnumb', A_W_F_PLUGIN_URL . '/code/js/wNumb.js', array() );
        }

        wp_enqueue_style( 'awf-styles', A_W_F_PLUGIN_URL . '/styles/awf-admin.css', false, A_W_F::$plugin_version );
        A_W_F::enqueue_style_options_css();
        wp_enqueue_style( 'awf-fontawesome', A_W_F_PLUGIN_URL . '/styles/fontawesome-all.min.css', array() );
        wp_enqueue_script( 'awf-admin', A_W_F_PLUGIN_URL . '/code/js/awf-admin.js', array( 'jquery', 'wp-color-picker', 'jquery-ui-sortable', 'jquery-blockui' ), A_W_F::$plugin_version );
        
        if( version_compare( WC_VERSION, '3.6', '>=' ) ) {
          wp_enqueue_style( 'awf-select2-fix', A_W_F_PLUGIN_URL . '/styles/awf-select2-fix.css', false, A_W_F::$plugin_version );
        }

        wp_localize_script( 'awf-admin', 'awf_js_data', array(
          'awf_ajax_referer' => wp_create_nonce( 'awf_ajax_nonce' ),
          'l10n' => array(
            'range_change_confirmation' => esc_html__( 'Changing the range type will force a preset update. Some of the current range settings might be lost. Are you ready to proceed?', 'annasta-filters' ),
            'add_seo_filters_btn_label' => esc_html__( 'Insert annasta filters list', 'annasta-filters' ),
            'apply_filter_template_confirmation' => esc_html__( 'Any unsaved changes to preset or filters will be lost! Please use the Cancel button to go back to preset settings to save any changes before proceeding. To ensure the proper template application the page needs to reload twice. Apply filter template and reload this page?', 'annasta-filters' ),
            'apply_preset_template_confirmation' => esc_html__( 'The settings and filters of the current preset will be changed to reflect the chosen template. To ensure the proper template application the page needs to reload twice. Apply preset template and reload this page?', 'annasta-filters' )
          ),
        ) );
      }
    }
    
    public function ajax_controller() {
      
      $nonce_check = check_ajax_referer( 'awf_ajax_nonce', 'awf_ajax_referer', false );
      if( empty( $nonce_check ) ) {
        wp_send_json_error( array( 'awf_error_message' => __( 'Your session has expired. Please reload the page and try again.', 'annasta-filters' ) ), 403 );
      }
      
      if ( ! current_user_can( 'manage_woocommerce' ) ) {
        wp_send_json_error( array( 'awf_error_message' => __( 'Error: permission denied.', 'annasta-filters' ) ), 403 );
      }

      if( 'regenerate_customizer_css' === $_POST['awf_action'] ) {
        echo $this->generate_customizer_css( $_POST['awf_customizer_settings'] );
				
			} elseif( 'update_presets_positions' === $_POST['awf_action'] ) {
        if( $this instanceof A_W_F_premium_admin ) { $this->update_presets_positions(); }
        
      } elseif( 'add_product_list_template_option' === $_POST['awf_action'] ) {
        $this->add_product_list_template_option();
        
      } elseif( 'delete_product_list_template_option' === $_POST['awf_action'] ) {
        $this->delete_product_list_template_option();
        
      } elseif( 'update_seo_filters_positions' === $_POST['awf_action'] ) {
        if( $this instanceof A_W_F_premium_admin ) { $this->update_seo_filters_positions(); }
        
      } elseif( 'clear_product_counts_cache' === $_POST['awf_action'] ) {
        $this->clear_product_counts_cache();
        
      } elseif( isset( $_POST['awf_preset'] ) ) {

        $preset_id = (int) $_POST['awf_preset'];
        
        if( ! isset( A_W_F::$presets[$preset_id] ) ) {
          wp_send_json_error( array( 'awf_error_message' => __( 'Error: preset doesn\'t exist', 'annasta-filters' ) ), 400 );
        }

        if( 'add-preset-association' === $_POST['awf_action'] ) {
          $this->add_preset_association( $preset_id );
          
        } else if( 'delete-preset-association' === $_POST['awf_action'] ) {
          $this->delete_preset_association( $preset_id );
          
        } else if( 'build-taxonomy-associations' === $_POST['awf_action'] ) {
          echo $this->build_taxonomy_associations( $preset_id );

        } else {
          
          if( 'add-filter' === $_POST['awf_action'] ) {
            $filter = $this->add_filter( $preset_id, sanitize_text_field( $_POST['awf_filter'] ) );
            include( A_W_F_PLUGIN_PATH . 'templates/admin/filter.php' );
            
          } elseif( 'update_filters_positions' === $_POST['awf_action'] ) {
            $this->update_positions( $preset_id );
            
          } elseif( isset( $_POST['awf_filter'] ) ) {

            $filter_id = (int) $_POST['awf_filter'];
            
            if( ! isset( A_W_F::$presets[$preset_id]['filters'][$filter_id] ) ) {
              wp_send_json_error( array( 'awf_error_message' => __( 'Error: filter doesn\'t exist', 'annasta-filters' ) ), 400 );
            }
            
            $filter = new A_W_F_filter( $preset_id, $filter_id );

            if( 'delete-filter' === $_POST['awf_action'] ) {
              echo $this->delete_filter( $filter );

            } elseif( 'rebuild-styles' === $_POST['awf_action'] ) {
              echo $this->build_style_options( $filter, sanitize_key( $_POST['awf_filter_type'] ) );

            } else if( 'rebuild-style-options' === $_POST['awf_action'] ) {
              echo $this->get_style_options_html( $filter, sanitize_key( $_POST['awf_filter_style'] ) );

            } else if( 'rebuild-range-type-options' === $_POST['awf_action'] ) {
              $filter->settings['type'] = 'range';
              $filter->settings['type_options']['range_type'] = isset( $_POST['awf_filter_range_type'] ) ? sanitize_key( $_POST['awf_filter_range_type'] ) : '';
      
              $this->display_range_type( $filter );
              
            } else if( 'add-custom-range-value' === $_POST['awf_action'] ) {
              $this->add_custom_range_value( $filter, floatval( str_replace( array( wc_get_price_thousand_separator(), wc_get_price_decimal_separator() ), array( '', '.' ), $_POST['awf_new_range_value'] ) ) );

            } else if( 'delete-custom-range-value' === $_POST['awf_action'] ) {
              $this->delete_custom_range_value( $filter, floatval( str_replace( array( wc_get_price_thousand_separator(), wc_get_price_decimal_separator() ), array( '', '.' ), $_POST['awf_delete_range_value'] ) ) );

            } else if( 'update-terms-limitation-mode' === $_POST['awf_action'] ) {
              $this->update_filter_terms_limitation_mode( $filter, sanitize_key( $_POST['awf_terms_limitation_mode'] ) );
              echo $this->build_terms_limitations( $filter );

            } else if( 'add-terms-limitation' === $_POST['awf_action'] ) {
              $this->add_filter_terms_limitation( $filter, intval( $_POST['awf_add_terms_limitation'] ) );
              echo $this->build_terms_limitations( $filter );

            } else if( 'remove-terms-limitation' === $_POST['awf_action'] ) {
              $this->remove_filter_terms_limitation( $filter, intval( $_POST['awf_remove_terms_limitation'] ) );
              echo $this->build_terms_limitations( $filter );
              
            } else if( 'add-ppp-value' === $_POST['awf_action'] ) {
              $this->add_ppp_value( $filter );

            } else if( 'remove-ppp-value' === $_POST['awf_action'] ) {
              $this->remove_ppp_value( $filter );
        
            } else {
              if( $this instanceof A_W_F_premium_admin ) { $this->premium_ajax_controller( $filter ); }
            }
              
          }
        }
      } else {
        if( $this instanceof A_W_F_premium_admin ) { $this->premium_ajax_controller(); }
      }

      wp_die();
    }
    
    private function add_product_list_template_option() {

      $new_option = sanitize_key( $_POST['awf_template_option'] );
      $default_options = $this->get_product_list_template_options();

      if( isset( $default_options[$new_option] ) ) {

        $template_options = get_option( 'awf_product_list_template_options', array() );

        $template_options[$new_option][] = array();
        end( $template_options[$new_option] );
        $id = key( $template_options[$new_option] );

        $template_options[$new_option][$id]['hook'] = 'woocommerce_before_shop_loop';
        $template_options[$new_option][$id]['priority'] = (int) 15;

        if( 'awf_preset' === $new_option ) {
          $presets = $this->get_presets_names();
          $presets = array_keys( $presets );

          $template_options[$new_option][$id]['preset'] = (int) array_shift( $presets );
        }

        update_option( 'awf_product_list_template_options', $template_options );

      }

      $this->display_product_list_settings_template_options();

    }
    
    private function delete_product_list_template_option() {

      $option = sanitize_key( $_POST['awf_template_option'] );
      $setting_id = (int) $_POST['awf_template_setting_id'];
      $template_options = get_option( 'awf_product_list_template_options', array() );

      if( isset( $template_options[$option] ) && isset( $template_options[$option][$setting_id] ) ) {
        $template_options[$option] = array_diff_key( $template_options[$option], array( $setting_id => array() ) );
        array_filter( $template_options ); /* clean up the empty values */

        update_option( 'awf_product_list_template_options', $template_options );
      }

      $this->display_product_list_settings_template_options();

    }
    
    private function add_preset_association( $preset_id ) {

      $association_id = sanitize_text_field( $_POST['awf_association'] );

      if( in_array( $association_id, A_W_F::$presets[$preset_id]['associations'] ) ) {
        wp_send_json_error( array( 'awf_error_message' => __( 'Error completing request: association already exists.', 'annasta-filters' ) ), 400 );
        
      } else {
        
        if( 'all' === $association_id ) {
          A_W_F::$presets[$preset_id]['associations'] = array( 'all' );
          
        } else {
          
          if( false !== ( $key = array_search( 'all', A_W_F::$presets[$preset_id]['associations'] ) ) ) {
            unset( A_W_F::$presets[$preset_id]['associations'][$key] );
          }
          
          $taxonomies = get_object_taxonomies( 'product', 'names' );
          $taxonomies = array_diff( $taxonomies, A_W_F::$excluded_taxonomies );
          
          if( 'shop-pages' === $association_id ) {
            foreach( A_W_F::$presets[$preset_id]['associations'] as $i => $association ) {
              $association_data = explode( '--', $association );
              if( isset( $association_data[1] ) && 'archive-pages' === $association_data[1] ) { continue; }
              if( isset( $association_data[2] ) && 'archive-page' === $association_data[2] ) { continue; }
              
              if( in_array( $association_data[0], $taxonomies ) ) { unset( A_W_F::$presets[$preset_id]['associations'][$i] ); }
            }
            
          } else {
            $all_associations = $this->get_all_associations();
            if( ! isset( $all_associations[$association_id] ) ) {
              wp_send_json_error( array( 'awf_error_message' => __( 'Request couldn\'t be completed: invalid association id.', 'annasta-filters' ) ), 400 );
            }
            
            $new_association_data = explode( '--', $association_id );
            
            if( in_array( $new_association_data[0], $taxonomies ) ) {
              
              if( isset( $new_association_data[2] ) ) {
                
                switch( $new_association_data[2] ) {
                  case 'shop-page':
                    foreach( A_W_F::$presets[$preset_id]['associations'] as $i => $association ) {
                      if( in_array( $association, array( 'all', 'shop-pages', $new_association_data[0] . '--shop-pages' ) ) ) {
                        unset( A_W_F::$presets[$preset_id]['associations'][$i] );
                      }
                    }
                    break;
                    
                  case 'archive-page':
                    foreach( A_W_F::$presets[$preset_id]['associations'] as $i => $association ) {
                      if( in_array( $association, array( 'all', 'archive-pages', $new_association_data[0] . '--archive-pages' ) ) ) {
                        unset( A_W_F::$presets[$preset_id]['associations'][$i] );
                      }
                    }
                    break;
                    
                  default: break;
                }
                
              } elseif( isset( $new_association_data[1] ) ) {
                
                switch( $new_association_data[1] ) {
                  case 'shop-pages':
                    foreach( A_W_F::$presets[$preset_id]['associations'] as $i => $association ) {
                      $association_data = explode( '--', $association );

                      if( $new_association_data[0] === $association_data[0] || in_array( $association, array( 'all', 'shop-pages' ) ) ) {
                        if( isset( $association_data[2] ) ) {
                          if( 'archive-page' === $association_data[2] ) { continue; }
                        } else {
                          if( isset( $association_data[1] ) && 'archive-pages' === $association_data[1] ) { continue; }
                        }

                        unset( A_W_F::$presets[$preset_id]['associations'][$i] );
                      }
                    }
                    break;
                    
                  case 'archive-pages':
                    foreach( A_W_F::$presets[$preset_id]['associations'] as $i => $association ) {
                      $association_data = explode( '--', $association );

                      if( $new_association_data[0] === $association_data[0] || in_array( $association, array( 'all' ) ) ) {
                        if( isset( $association_data[2] ) ) {
                          if( 'shop-page' === $association_data[2] ) { continue; }
                        } else {
                          if( isset( $association_data[1] ) && 'shop-pages' === $association_data[1] ) { continue; }
                        }

                        unset( A_W_F::$presets[$preset_id]['associations'][$i] );
                      }
                    }
                    break;
                    
                  default: break;
                }
                
              }

            }
          }
          
          A_W_F::$presets[$preset_id]['associations'][] = $association_id;
        }
        
        A_W_F::$presets[$preset_id]['associations'] = array_values( A_W_F::$presets[$preset_id]['associations'] );
        update_option( 'awf_presets', A_W_F::$presets );

        $this->display_associations( $preset_id );
      }
    }

    private function delete_preset_association( $preset_id ) {

      $association_id = sanitize_text_field( $_POST['awf_association'] );

      if ( false !== ( $key = array_search( $association_id, A_W_F::$presets[$preset_id]['associations'] ) ) ) {
        unset( A_W_F::$presets[$preset_id]['associations'][$key] );
        update_option( 'awf_presets', A_W_F::$presets );
        
        $this->display_associations( $preset_id );
        
      } else {
        wp_send_json_error( array( 'awf_error_message' => __( 'Request couldn\'t be completed: wrong preset or association.', 'annasta-filters' ) ), 400 );
      }
    }

    public function add_filter( $preset_id, $filter_name ) {

      $filter_data = $this->build_new_filter_data( $filter_name );

      if( empty( $filter_data ) || ! in_array( $filter_data['module'], A_W_F::$modules ) || ! isset( A_W_F::$presets[$preset_id] ) ) {
        wp_send_json_error( array( 'awf_error_message' => __( 'Error creating filter: invalid preset, filter, or taxonomy.', 'annasta-filters' ) ), 400 );
      }

      A_W_F::$presets[$preset_id]['filters'][] = count( A_W_F::$presets[$preset_id]['filters'] );
      end( A_W_F::$presets[$preset_id]['filters'] );
      $new_filter_id = key( A_W_F::$presets[$preset_id]['filters'] );
      
      update_option( 'awf_presets', A_W_F::$presets );

      $prefix = A_W_F_filter::get_prefix( $preset_id, $new_filter_id );
      $settings = $this->get_module_defaults( $filter_data );

      update_option( $prefix . 'name', $filter_name );
      update_option( $prefix . 'module', $filter_data['module'] );
      update_option( $prefix . 'settings', $settings );

      return new A_W_F_filter( $preset_id, $new_filter_id );
    }

    protected function build_new_filter_data( $filter_name ) {
      $filter_data = array();
      $all_filters = A_W_F::$admin->get_all_filters();

      if( isset( $all_filters[$filter_name] ) ) {
        if( 0 === strpos( $filter_name, 'taxonomy--' ) ) {
          $filter_name_data = explode( '--', $filter_name );
          $taxonomy = array_pop( $filter_name_data );
          $filter_data['module'] = 'taxonomy';
          $filter_data['taxonomy'] = get_taxonomy( $taxonomy );

          if( empty( $filter_data['taxonomy'] ) ) {
            return array();
          }

        } else {
          $filter_data['module'] = $filter_name;
          $filter_data['title'] = $this->get_filter_title( $filter_name );
        }
      }

      return $filter_data;
    }

    public function delete_filter( $filter ) {
      $ajax_response = array();

      if( wp_doing_ajax() ) {
        if( isset( A_W_F::$presets[$filter->preset_id]['filters'][$filter->id] ) ) {

          $ajax_response['option_value'] = esc_attr( $filter->name );

          if(  'taxonomy' === $filter->module ) {
            if( $taxonomy = get_taxonomy( $filter->settings['taxonomy'] ) ) {
              $ajax_response['option_label'] = esc_html( $taxonomy->label );
            }

            if( ! empty( $filter->settings['show_count'] ) || ( isset( $filter->settings['hide_empty'] ) && 'none' !== $filter->settings['hide_empty'] ) ) {
              $this->clear_product_counts_cache();
            }

          } else {
            $ajax_response['option_label'] = esc_html( $this->get_filter_title( $ajax_response['option_value'] ) );
          }

          unset( A_W_F::$presets[$filter->preset_id]['filters'][$filter->id] );

          if( ! empty( A_W_F::$presets[$filter->preset_id]['filters'] ) ) {
            asort( A_W_F::$presets[$filter->preset_id]['filters'], SORT_NUMERIC );
            A_W_F::$presets[$filter->preset_id]['filters'] = array_flip( A_W_F::$presets[$filter->preset_id]['filters'] );
            A_W_F::$presets[$filter->preset_id]['filters'] = array_values( A_W_F::$presets[$filter->preset_id]['filters'] );
            A_W_F::$presets[$filter->preset_id]['filters'] = array_flip( A_W_F::$presets[$filter->preset_id]['filters'] );
          }

          update_option( 'awf_presets', A_W_F::$presets );
          
          if( 'meta' === $filter->module ) {
            A_W_F::build_query_vars();
            $ajax_response = array();
          }

        } else {
          wp_send_json_error( array( 'awf_error_message' => sprintf( __( 'Error: a problem occured while deleting filter %1$s.', 'annasta-filters' ), $filter->preset_id . '-' . $filter->id ) ), 400 );
        }
      }

      delete_option( $filter->prefix . 'name' );
      delete_option( $filter->prefix . 'module' );
      delete_option( $filter->prefix . 'settings' );

      return json_encode( $ajax_response );
    }

    private function update_positions( $preset_id ) {

      $positions = isset( $_POST['awf_filters_positions'] ) && is_array( $_POST['awf_filters_positions'] ) ?  array_map( 'intval', $_POST['awf_filters_positions'] ) : array();

      $filters = array();
      foreach( $positions as $position => $filter_id ) {
        $filters[$filter_id] = (int) $position;
      }

      $check_ids = array_diff( $filters, A_W_F::$presets[$preset_id]['filters'] );

      if( count( $check_ids ) === 0 ) {

        A_W_F::$presets[$preset_id]['filters'] = $filters;
        update_option( 'awf_presets', A_W_F::$presets );

      } else {
        wp_send_json_error( array( 'awf_error_message' => __( 'An error occured when updating filters\' positions.', 'annasta-filters' ) ), 400 );
      }

    }
      
    public function update_filter( $filter ) {
      $old_settings = $filter->settings;
      $filter->settings['style_options'] = array();
      $response = array();

      foreach( $filter->settings as $setting => $value ) {
        if( is_null( $value ) ) { continue; }

        switch( $setting ) {
          case 'title':
          case 'active_prefix':
          case 'reset_active_label':
          case 'placeholder':
          case 'show_search_placeholder':
            $filter->settings[$setting] = $this->get_sanitized_text_field_setting( $filter->prefix . $setting );
            break;
          case 'type':
          case 'style':
          case 'meta_name':
            if( isset( $_POST[$filter->prefix . $setting] ) ) {
              $filter->settings[$setting] = sanitize_key( $_POST[$filter->prefix . $setting] );
            }
            break;
          case 'show_title':
          case 'show_active':
          case 'reset_all':
          case 'force_reload':
          case 'reset_active':
          case 'is_collapsible':
          case 'collapsed_on':
          case 'display_children':
          case 'children_collapsible':
          case 'children_collapsible_on':
          case 'hierarchical_sbs':
          case 'hide_preset_submit_btn':
          case 'show_search':
          case 'autocomplete':
            $filter->settings[$setting] = $this->get_sanitized_checkbox_setting( $filter, $setting );
            break;
          case 'show_in_row':
            $filter->settings[$setting] = $this->get_sanitized_checkbox_setting( $filter, $setting );
            break;
          case 'show_count':
            $filter->settings[$setting] = $this->get_sanitized_checkbox_setting( $filter, $setting );
            if( $filter->settings[$setting] !== $value ) { $response['clear_counts_cache'] = true; }
            break;
          case 'hide_empty':
            $filter->settings[$setting] = sanitize_key( $_POST[$filter->prefix . $setting] );
            if( $old_settings[$setting] !== $filter->settings[$setting] ) { $response['clear_counts_cache'] = true; }
            break;
          case 'hierarchical_level':
            if( isset( $_POST[$filter->prefix . $setting] ) ) { $filter->settings[$setting] = (int) $_POST[$filter->prefix . $setting]; }
            break;
          case 'height_limit':
            $filter->settings[$setting] = (int) $_POST[$filter->prefix . $setting];
            break;
          case 'sort_by':
          case 'sort_order':
            $filter->settings[$setting] = sanitize_key( $_POST[$filter->prefix . $setting] );
            break;
          default: break;
        }
      }

      if(  ! empty( $filter->settings['terms_limitation_mode'] ) && 'active' === $filter->settings['terms_limitation_mode'] ) {
        $filter->settings['style_options']['display_active_filter_siblings'] = $this->get_sanitized_checkbox_setting( $filter, 'display_active_filter_siblings' );
      }

      if( 'search' === $filter->module && ! empty( $filter->settings['autocomplete'] ) ) {
        $filter->settings['type_options']['autocomplete_filtered'] = $this->get_sanitized_checkbox_setting( $filter, 'autocomplete_filtered' );
        $filter->settings['type_options']['autocomplete_show_img'] = $this->get_sanitized_checkbox_setting( $filter, 'autocomplete_show_img' );
        $filter->settings['type_options']['autocomplete_show_price'] = $this->get_sanitized_checkbox_setting( $filter, 'autocomplete_show_price' );
        $filter->settings['type_options']['autocomplete_view_all'] = $this->get_sanitized_checkbox_setting( $filter, 'autocomplete_view_all' );
        $filter->settings['type_options']['autocomplete_after'] = $this->get_sanitized_int_setting( $filter->prefix . 'autocomplete_after', 2 );
        $filter->settings['type_options']['autocomplete_results_count'] = $this->get_sanitized_int_setting( $filter->prefix . 'autocomplete_results_count', 5 );
      }

      if( 'range' === $filter->settings['type'] ) {
        $filter->settings['type_options']['range_type'] = sanitize_key( $_POST[$filter->prefix . 'range_type'] );
        
        if( 'auto_range' === $filter->settings['type_options']['range_type']
           || 'custom_range' === $filter->settings['type_options']['range_type'] )
        {
          $filter->settings['type_options']['precision'] = round( floatval( $_POST[$filter->prefix . 'precision'] ), 2, PHP_ROUND_HALF_UP );
          $filter->settings['type_options']['decimals'] = absint( $_POST[$filter->prefix . 'decimals'] );
          if( 2 < $filter->settings['type_options']['decimals'] ) { $filter->settings['type_options']['decimals'] = 2; }
          
          $filter->settings['style_options']['value_prefix'] = $this->get_sanitized_text_field_setting( $filter->prefix . 'value_prefix' );
          $filter->settings['style_options']['value_postfix'] = $this->get_sanitized_text_field_setting( $filter->prefix . 'value_postfix' );
        }

        if( 'auto_range' === $filter->settings['type_options']['range_type'] ) {

          $range_min = round( floatval( $_POST[$filter->prefix . 'range_min'] ), 2, PHP_ROUND_HALF_UP );
          $range_max = round( floatval( $_POST[$filter->prefix . 'range_max'] ), 2, PHP_ROUND_HALF_UP );

          if( $range_min === $range_max ) { $range_max += 1; }
          elseif( $range_min > $range_max ) {
            $temp = $range_max;
            $range_max = $range_min;
            $range_min = $temp;
          }

          $range_segments = (int) $_POST[$filter->prefix . 'range_segments'];
          if( $range_segments < 1 ) { $range_segments = 1; }
          
          $increment = ( $range_max - $range_min ) / $range_segments;
          $increment = round( $increment, 2, PHP_ROUND_HALF_UP );

          $filter->settings['type_options']['range_values'] = array();

          for( $v = $range_min; $v < $range_max; $v += $increment ) {
            $filter->settings['type_options']['range_values'][] = round( $v, 2, PHP_ROUND_HALF_UP );
          }
          
          while( count( $filter->settings['type_options']['range_values'] ) > $range_segments ) {
            array_pop( $filter->settings['type_options']['range_values'] );
          }
          
          $filter->settings['type_options']['range_values'][] = $range_max;
          
        } elseif( 'custom_range' === $filter->settings['type_options']['range_type'] ) {
          $filter->settings['type_options']['range_values'] = $old_settings['type_options']['range_values'];
        }

      }

      if( 'icons' === $filter->settings['style'] ) {
        $filter->settings['style_options']['icons'][] = $this->get_sanitized_text_field_setting( $filter->prefix . 'unselected_icon' );
        $filter->settings['style_options']['solid'][] = $this->get_sanitized_checkbox_setting( $filter, 'unselected_icon_solid' ) ? 'awf-solid' : '';
        $filter->settings['style_options']['icons'][] = $this->get_sanitized_text_field_setting( $filter->prefix . 'unselected_icon_hover' );
        $filter->settings['style_options']['solid'][] = $this->get_sanitized_checkbox_setting( $filter, 'unselected_icon_hover_solid' ) ? 'awf-solid' : '';
        $filter->settings['style_options']['icons'][] = $this->get_sanitized_text_field_setting( $filter->prefix . 'selected_icon' );
        $filter->settings['style_options']['solid'][] = $this->get_sanitized_checkbox_setting( $filter, 'selected_icon_solid' ) ? 'awf-solid' : '';
        $filter->settings['style_options']['icons'][] = $this->get_sanitized_text_field_setting( $filter->prefix . 'selected_icon_hover' );
        $filter->settings['style_options']['solid'][] = $this->get_sanitized_checkbox_setting( $filter, 'selected_icon_hover_solid' ) ? 'awf-solid' : '';

      } elseif( 'range-slider' === $filter->settings['style'] ) {
        
        if( isset( $old_settings->settings['children_collapsible'] ) ) { $filter->settings['children_collapsible'] = $filter->settings['children_collapsible_on'] = false; }
        if( isset( $old_settings->settings['show_in_row'] ) ) { $filter->settings['show_in_row'] = false; }
        if( isset( $old_settings->settings['show_search'] ) ) { $filter->settings['show_search'] = false; }
        
        $filter->settings['height_limit'] = (int) 0;
        
        if( 'auto_range' === $filter->settings['type_options']['range_type']
           || 'custom_range' === $filter->settings['type_options']['range_type'] )
        {
          $filter->settings['style_options']['step'] = empty( $_POST[$filter->prefix . 'step'] ) ? floatval( 1 ) : (float) $_POST[$filter->prefix . 'step'];
          $filter->settings['style_options']['step'] = abs( $filter->settings['style_options']['step'] );
          $filter->settings['style_options']['slider_tooltips'] = empty( $_POST[$filter->prefix . 'slider_tooltips'] ) ? 'above_handles' : sanitize_key( $_POST[$filter->prefix . 'slider_tooltips'] );
        }
        
        $filter->settings['style_options']['show_range_btn'] = $this->get_sanitized_checkbox_setting( $filter, 'show_range_btn' );

      } elseif( 'daterangepicker' === $filter->settings['style'] ) {
        $filter->settings['style_options']['date_picker_type'] = empty( $_POST[$filter->prefix . 'date_picker_type'] ) ? 'single' : sanitize_key( $_POST[$filter->prefix . 'date_picker_type'] );
        $filter->settings['style_options']['db_date_format'] = empty( $_POST[$filter->prefix . 'db_date_format'] ) ? 'c' : sanitize_key( $_POST[$filter->prefix . 'db_date_format'] );
        $filter->settings['style_options']['daterangepicker_placeholder'] = $this->get_sanitized_text_field_setting( $filter->prefix . 'daterangepicker_placeholder' );

      } elseif( 'colours' === $filter->settings['style'] ) {

        if( ! isset( $_POST[$filter->prefix . 'show_label'] ) ) { $filter->settings['style_options']['hide_label'] = true; }
        $filter_terms = $filter->get_filter_terms( false );

        $filter->settings['style_options']['colours'] = array();

        foreach( $filter_terms as $mt ) {
          if( isset( $_POST[$filter->prefix . 'term_' . $mt->term_id . '_colour'] ) ) {
            $filter->settings['style_options']['colours'][$mt->term_id] = $this->get_sanitized_text_field_setting( $filter->prefix . 'term_' . $mt->term_id . '_colour' );
          }
        }
      }

      if( $this instanceof A_W_F_premium_admin ) {
        $this->update_premium_filter( $filter, $old_settings, $response );
        
      } else {
        if( ! empty( $filter->settings['is_collapsible'] ) ) {
          $filter->settings['show_title'] = true;
        }
      }

      update_option( $filter->prefix. 'settings', $filter->settings );

      return( $response );
    }

    protected function add_custom_range_value( $filter, $new_value ) {

      if( 'custom_range' !== $filter->settings['type_options']['range_type'] ) {
        wp_send_json_error( array( 'awf_warning_message' => __( 'Please save preset before adding or deleting values of the range.', 'annasta-filters' ) ), 400 );
      }

      if( isset( $filter->settings['type_options']['range_values'] ) && ! in_array( $new_value, $filter->settings['type_options']['range_values'] ) ) {
        $filter->settings['type_options']['range_values'][] = round( $new_value, 2, PHP_ROUND_HALF_UP );
        asort( $filter->settings['type_options']['range_values'], SORT_NUMERIC );
        $filter->settings['type_options']['range_values'] = array_values( $filter->settings['type_options']['range_values'] );

        update_option( $filter->prefix. 'settings', $filter->settings );
      }

      $this->display_range_type( $filter );
    }

    protected function delete_custom_range_value( $filter, $value ) {

      if( ! isset( $filter->settings['type_options']['range_values'] ) || count( $filter->settings['type_options']['range_values'] ) < 3  ) {
        wp_send_json_error( array( 'awf_warning_message' => __( 'This range value can not be deleted, because a range needs at least 2 values to work. If you want to change this value, first add the new value, and then delete the unneeded one.', 'annasta-filters' ) ), 400 );
      }

      if( 'custom_range' !== $filter->settings['type_options']['range_type'] ) {
        wp_send_json_error( array( 'awf_warning_message' => __( 'Please save preset before adding or deleting values of the range.', 'annasta-filters' ) ), 400 );
      }

      if( false !== ( $key = array_search( $value, $filter->settings['type_options']['range_values'] ) ) ) {
        
        unset( $filter->settings['type_options']['range_values'][$key] );
        $filter->settings['type_options']['range_values'] = array_values( $filter->settings['type_options']['range_values'] );

        update_option( $filter->prefix. 'settings', $filter->settings );
      }
    }

    protected function update_filter_terms_limitation_mode( &$filter, $new_limitation_mode ) {

      if( ! isset( $filter->settings['terms_limitation_mode'] ) ) { return; }

      $filter->settings['terms_limitation_mode'] = $new_limitation_mode;

      switch( $filter->settings['terms_limitation_mode'] ) {
        case 'exclude': break;
        case 'include':
          if( ! isset( $filter->settings['included_items'] ) ) { $filter->settings['included_items'] = array(); }
          break;
        case 'active': break;
        default:
          $filter->settings['terms_limitation_mode'] = 'exclude';
          break;
      }

      update_option( $filter->prefix. 'settings', $filter->settings );
    }

    public function add_filter_terms_limitation( &$filter, $term_id ) {
      if( ! isset( $filter->settings['terms_limitation_mode'] ) ) { return; }

      $all_items = $filter->get_filter_terms( false );
      $all_items_ids = wp_list_pluck( $all_items, 'term_id' );

      $terms_limitation = $this->setup_filter_terms_limitation_settings( $filter );

      if( in_array( $term_id, $all_items_ids ) && ! in_array( $term_id, $filter->settings[$terms_limitation] ) ) {
        $filter->settings[$terms_limitation][] = $term_id;
        update_option( $filter->prefix. 'settings', $filter->settings );
      }
    }

    public function remove_filter_terms_limitation( &$filter, $term_id ) {
      if( ! isset( $filter->settings['terms_limitation_mode'] ) ) { return; }

      $terms_limitation = $this->setup_filter_terms_limitation_settings( $filter );

      if( in_array( $term_id, $filter->settings[$terms_limitation] ) ) {
        $filter->settings[$terms_limitation] = array_diff( $filter->settings[$terms_limitation], array( $term_id ) );
        $filter->settings[$terms_limitation] = array_values( $filter->settings[$terms_limitation] );
        update_option( $filter->prefix. 'settings', $filter->settings );
      }
    }
    
    public function add_ppp_value( $filter ) {
      $value = (int) $_POST['awf_add_ppp_value'];
      
      $filter->settings['ppp_values'][$value] = mb_strimwidth( sanitize_text_field( $_POST['awf_add_ppp_label'] ), 0, 100, '...' );
      ksort( $filter->settings['ppp_values'] );
      update_option( $filter->prefix. 'settings', $filter->settings );
      
      echo $this->build_ppp_values_list( $filter, intval( get_option( 'awf_ppp_default', 0 ) ) );
    }
  
    public function remove_ppp_value( $filter ) {

      $value = (int) $_POST['awf_remove_ppp_value'];
      
      unset( $filter->settings['ppp_values'][$value] );
      update_option( $filter->prefix. 'settings', $filter->settings );

      echo $this->build_ppp_values_list( $filter, intval( get_option( 'awf_ppp_default', 0 ) ) );
    }
    
    public function build_associations_lists() {

      $associations_by_preset = array();
      foreach( A_W_F::$presets as $preset_id => $preset ) {

        $preset_associations = array();

        foreach( $preset['associations'] as $association_id ) {
          if( $association_id === 'all' ) {
            $preset_associations[] = __( 'All pages', 'annasta-filters' );
            
          } else if( $association_id === 'shop-pages' ) {
            $preset_associations[] = __( 'Shop pages', 'annasta-filters' );
            
          } else if( 0 === strpos( $association_id, 'wp_page--' ) ) {
            $page_id = (int) substr( $association_id, strlen( 'wp_page--' ) );
            $preset_associations[] = get_the_title( $page_id );
            
          } else if( false !== strpos( $association_id, '--' ) ) {

            $association_data = explode( '--', $association_id );
            $association_taxonomy = get_taxonomy( $association_data[0] );
            if( ! is_object( $association_taxonomy ) ) { continue; }
            
            if( 'archive-pages' === $association_data[1] ) {
              $preset_associations[] = ucfirst( sprintf( __( '%s taxonomy archive pages', 'annasta-filters' ), $association_taxonomy->label) );
              
            } elseif( 'shop-pages' === $association_data[1] ) {
              $preset_associations[] = ucfirst( sprintf( __( 'Shop pages with %s filters', 'annasta-filters' ), $association_taxonomy->label) );
              
            } elseif( isset( $association_data[2] ) && in_array( $association_data[2], array( 'shop-page', 'archive-page' ) ) ) {
              $association_term = get_term_by( 'slug', $association_data[1], $association_data[0] );

              if( is_object( $association_term ) ) {
                $preset_associations['taxonomies'][$association_data[2]][$association_taxonomy->name][] = $association_term->name;
              }
            }
            
          }
        }

        if( isset( $preset_associations['taxonomies'] ) ) {
          
          foreach( $preset_associations['taxonomies'] as $page_type => $taxonomies ) {
            foreach( $taxonomies as $tax => $terms ) {
              if( 'shop-page' === $page_type ) {
                $preset_associations[] = ucfirst( sprintf( __( 'shop pages with enabled %s filters', 'annasta-filters' ), implode( ', ', $terms ) ) );

              } elseif( 'archive-page' === $page_type ) {
                $preset_associations[] = ucfirst( sprintf( __( '%1$s archive pages', 'annasta-filters' ), implode( ', ', $terms ) ) );
              }
            }
          }

          unset( $preset_associations['taxonomies'] );
        }

        $associations_by_preset[$preset_id] = implode( ' / ', $preset_associations );
      }

      return $associations_by_preset;
    }
    
    protected function get_all_associations( $include_taxonomies = true ) {
      $all_associations = array(
        'all' => __( 'All pages', 'annasta-filters' ),
        'shop-pages' => __( 'Shop pages', 'annasta-filters' ),
      );

      $prefix = $arrow = '';

      if( ! $include_taxonomies ) {
        $arrow = '»&nbsp;&nbsp;';
        $prefix = 'awf-open--';
      }
      
      $taxonomies = get_object_taxonomies( 'product', 'objects' );
      foreach( $taxonomies as $t ) {
        if( in_array( $t->name, A_W_F::$excluded_taxonomies ) ) { continue; }
        if( $t->name !== sanitize_title( $t->name ) ) { continue; } // non-latin slugs check

        $terms = get_terms( array( 'taxonomy' => $t->name, 'parent' => 0, 'hide_empty' => false, 'orderby' => 'name' ) );    
        
        if( $t->public && $t->publicly_queryable ) {
          $all_associations[$prefix . $t->name . '--archive-pages'] = $arrow . sprintf( __( '%s taxonomy archive pages', 'annasta-filters' ), $t->label);
          if( $include_taxonomies ) { $all_associations += $this->build_associations_taxonomy_terms( $terms, 0, true ); }
        }
        
        $all_associations[$prefix . $t->name . '--shop-pages'] = $arrow . sprintf( __( 'Shop pages with enabled %s filters', 'annasta-filters' ), $t->label);
        if( $include_taxonomies ) { $all_associations += $this->build_associations_taxonomy_terms( $terms ); }
      }
      
      $wp_pages = get_all_page_ids();
      $wp_pages = array_diff( $wp_pages, array( wc_get_page_id( 'shop' ) ) );

      foreach( $wp_pages as $page_id ) {
        $all_associations['wp_page--' . $page_id] = __( 'WP page: ', 'annasta-filters' ) . get_the_title( $page_id );
      }
      
      return $all_associations;
    }
    
    public function display_associations( $preset_id ) {
      $all_associations = $this->get_all_associations();
      $select_associations = $this->get_all_associations( false );
      
      $preset_associations = array_intersect_key( $all_associations, array_flip( A_W_F::$presets[$preset_id]['associations'] ) );
      $associations_select = array_diff_key( $select_associations, $preset_associations );

      include( A_W_F_PLUGIN_PATH . 'templates/admin/preset-associations.php' );
    }
    
    private function build_taxonomy_associations( $preset_id ) {
      $associations = array();
      $options_html = '';

      $request = explode( '--', $_POST['awf_request'] );

      if( 3 !== count( $request ) ) { return ''; }

      $request = array_map( 'sanitize_title', $request );

      $type = array_pop( $request );
      $taxonomy = array_pop( $request );

      $terms = get_terms( array( 'taxonomy' => $taxonomy, 'parent' => 0, 'hide_empty' => false, 'orderby' => 'name' ) );

      if(
        ! in_array( $type, array( 'archive-pages', 'shop-pages' ) )
        || in_array( $taxonomy, A_W_F::$excluded_taxonomies )
        || is_wp_error( $terms )
        || empty( $terms )
      ) {
        return '';
      }

      $associations[$taxonomy . '--' . $type] = __( 'All', 'annasta-filters' );

      switch( $type ) {
        case 'archive-pages':
          $associations += $this->build_associations_taxonomy_terms( $terms, 0, true );
          break;
        case 'shop-pages':
          $associations += $this->build_associations_taxonomy_terms( $terms );
          break;
        default: break;
      }

      $preset_associations = array_flip( A_W_F::$presets[$preset_id]['associations'] );

      foreach( $associations as $name => $label ) {
        if( isset( $preset_associations[$name] ) ) { continue; }
        $options_html .= '<option value="' . $name . '">' . $label . '</option>';
      }

      return $options_html;
    }
    
    private function build_associations_taxonomy_terms( $terms, $indentation = 0, $archive = false ) {
      $options = array();

      foreach( $terms as $term ) {

        $association_id = $term->taxonomy . '--' . $term->slug;
        
        if( $archive ) {
          $association_id .= '--archive-page';
          $options[$association_id] = str_repeat( '»', $indentation ) . ( empty( $indentation) ? '' : '&nbsp;&nbsp;' ) . sprintf( __( '%1$s archive pages', 'annasta-filters' ), $term->name );
          
        } else {
          $association_id .= '--shop-page';
          $options[$association_id] = str_repeat( '»', $indentation ) . ( empty( $indentation) ? '' : '&nbsp;&nbsp;' ) . sprintf( __( 'Shop pages with enabled %s filter', 'annasta-filters' ), $term->name );
        }

        if( is_taxonomy_hierarchical( $term->taxonomy ) ) {
          $child_terms = get_terms( array( 'taxonomy' => $term->taxonomy, 'parent' => $term->term_id, 'hide_empty' => false, 'orderby' => 'name' ) );
          if(! empty( $child_terms ) ) {
            $options += $this->build_associations_taxonomy_terms( $child_terms, $indentation + 1, $archive );
          }
        }
      }

      return $options;
    }

    public function build_type_select( $filter ) {

      $html = '<select name="' . $filter->prefix . 'type" id="' . $filter->prefix . 'type" class="awf-filter-type-select">';

      if( isset( $this->filter_style_limitations[$filter->module] ) ) {
        foreach( $this->filter_style_limitations[$filter->module] as $type => $styles ) {
          $types[$type]['label']= $this->filter_types[$type]['label'];
        }
      } else {
        $types = $this->filter_types;
      }

      foreach( $types as $type => $data ) {
        $html .= '<option value="' . esc_attr( $type ) . '"';
        if( $filter->settings['type'] === $type ) { $html .= ' selected="selected"'; }
        $html .= '>' . esc_html( $data['label'] ) . '</option>';
      }

      $html .= '</select>';

      return $html;
    }

    public function display_range_type( $filter ) {      
      ob_start();
      require( A_W_F_PLUGIN_PATH . 'templates/admin/filter-options/type_options.php' );
      $html = ob_get_clean();
      
      echo $html;
    }

    public function build_range_type_options( $filter ) {
      $html = '';
      $old_settings = get_option( $filter->prefix. 'settings', array() );
      
      if( empty( $filter->settings['type_options']['range_values'] )
         || ( 'range' === $old_settings['type']
             && ( $old_settings['type_options']['range_type'] !== $filter->settings['type_options']['range_type'] && 'taxonomy_range' === $old_settings['type_options']['range_type'] )
            )
      ) {
        if( 'price' === $filter->module || 'rating' === $filter->module ) {
          $defaults = $this->get_module_defaults( array( 'module' => $filter->module, 'taxonomy' => (object) array( 'name' => '', 'label' => '' ), 'title' => '' ) );
          $filter->settings['type_options']['range_values'] = isset( $defaults['type_options']['range_values'] ) ? $defaults['type_options']['range_values'] : array( floatval( 0 ), floatval( 100 ) );
          
        } else {
          $filter->settings['type_options']['range_values'] = array( floatval( 0 ), floatval( 1000 ) );
        }

        update_option( $filter->prefix. 'settings', $filter->settings );
      }

      if( 'auto_range' === $filter->settings['type_options']['range_type'] ) {
        $html .= $this->build_auto_range( $filter );

      } elseif( 'custom_range' === $filter->settings['type_options']['range_type'] ) {
        $html .= $this->build_custom_range( $filter );

      } else {
        if( $this instanceof A_W_F_premium_admin ) { $html .= $this->build_taxonomy_range( $filter ); }
        update_option( $filter->prefix. 'settings', $filter->settings );
        
        return $html;
      }
      
      $html .= '<div class="awf-range-type-advanced"><div class="awf-range-type-options-row">';

      $html .= '<div>';
      $html .= '<label for="' . $filter->prefix . 'precision">' . esc_html__( 'Precision', 'annasta-filters' ) . '</label>';
      $html .= '<span class="woocommerce-help-tip" data-tip="' . esc_attr__( 'This setting controls the creation of radio-buttoned lists of ranges. With the default value of 0 a range with values 0, 10, 20, 30 will give you the following list of ranges: 0-10, 10-20, 20-30, without differences between the end and start values of adjacent range segments. Setting precision to 0.01 will alter the list to 0-9.99, 10-19.99, 20-29.99 etc. The smallest allowed value is 0.01.', 'annasta-filters' ) . '"></span>';
      $html .= '<input name="' . $filter->prefix . 'precision" type="text" value="' . esc_attr( isset( $filter->settings['type_options']['precision'] ) ? $filter->settings['type_options']['precision'] : '0' ) . '" style="width: 5em;">';
      $html .= '</div>';

      $html .= '<div>';
      $html .= '<label for="' . $filter->prefix . 'decimals">' . esc_html__( 'Number of decimals', 'annasta-filters' ) . '</label>';
      $html .= '<span class="woocommerce-help-tip" data-tip="' . esc_attr__( 'Define the amount of range values\' decimals (digits to the right of the decimal point). This value controls only the display format of range values, internally they are automatically rounded to 2 decimal points. ', 'annasta-filters' ) . '"></span>';
      $html .= '<input name="' . $filter->prefix . 'decimals" type="text" value="' . esc_attr( isset( $filter->settings['type_options']['decimals'] ) ? $filter->settings['type_options']['decimals'] : '0' ) . '" style="width: 5em;">';
      
      $html .= '</div>';

      $html .= '<div>';
      $html .= '<label for="' . $filter->prefix . 'value_prefix">' . esc_html__( 'Value prefix', 'annasta-filters' ) . '</label>';
      $html .= '<span class="woocommerce-help-tip" data-tip="' . esc_attr__( 'Symbol or word before the value (for currency symbols etc)', 'annasta-filters' ) . '"></span>';
      $html .= '<input name="' . $filter->prefix . 'value_prefix" type="text" value="' . esc_attr( empty( $filter->settings['style_options']['value_prefix'] ) ? '' : $filter->settings['style_options']['value_prefix'] ) . '" style="width: 5em;">';
      $html .= '</div>';

      $html .= '<div>';
      $html .= '<label for="' . $filter->prefix . 'value_postfix">' . esc_html__( 'Value postfix', 'annasta-filters' ) . '</label>';
      $html .= '<span class="woocommerce-help-tip" data-tip="' . esc_attr__( 'Symbol or word after the value (for currency symbols etc)', 'annasta-filters' ) . '"></span>';
      $html .= '<input name="' . $filter->prefix . 'value_postfix" type="text" value="' . esc_attr( empty( $filter->settings['style_options']['value_postfix'] ) ? '' : $filter->settings['style_options']['value_postfix'] ) . '" style="width: 5em;">';
      
      $html .= '</div></div>';
      
      $html .= '</div>';

      return $html;
    }

    protected function build_auto_range( $filter ) {

      $segments_count = count( $filter->settings['type_options']['range_values'] ) - 1;
      
      $html = '<div>';
      $html .= '<label for="' . $filter->prefix . 'range_min">' . esc_html__( 'Minimum value', 'annasta-filters' ) . '</label>';
      $html .= '<span class="woocommerce-help-tip" data-tip="' . esc_attr__( 'This setting defines the left-most (lowest possible) value of the range control.', 'annasta-filters' ) . '"></span>';
      $html .= '<input name="' . $filter->prefix . 'range_min" id="' . $filter->prefix . 'range_min" type="text" value="' . esc_attr( $filter->settings['type_options']['range_values'][0] ) . '" style="width: 10em;">';
      $html .= '</div><div>';
      $html .= '<label for="' . $filter->prefix . 'range_max">' . esc_html__( 'Maximum value', 'annasta-filters' ) . '</label>';
      $html .= '<span class="woocommerce-help-tip" data-tip="' . esc_attr__( 'This setting defines the right-most (highest possible) value of the range control.', 'annasta-filters' ) . '"></span>';
      $html .= '<input name="' . $filter->prefix . 'range_max" id="' . $filter->prefix . 'range_max" type="text" value="' . esc_attr( $filter->settings['type_options']['range_values'][$segments_count] ) . '" style="width: 10em;">';
      $html .= '</div><div>';
      $html .= '<label for="' . $filter->prefix . 'range_segments">' . esc_html__( 'Range divisions', 'annasta-filters' ) . '</label>';
      $html .= '<span class="woocommerce-help-tip" data-tip="' . esc_attr__( 'Define the amount of range segments. In a range slider this will control the amount of poles with labeled values displayed on the range scale. This setting has to be equal to or greater than 1. WARNING: please don\'t set very small ( less than 0.1 ) differences between the poles, it may result in an uneven segments distribution.', 'annasta-filters' ) . '"></span>';
      $html .= '<input name="' . $filter->prefix . 'range_segments" id="' . $filter->prefix . 'range_segments" type="text" value="' . $segments_count . '" style="width: 5em;">';
      $html .= '</div>';

      return $html;
    }

    protected function build_custom_range( $filter ) {
      $html = '<div>';

      foreach( $filter->settings['type_options']['range_values'] as $i => $value ) {
        $html .= '<div class="awf-custom-range-value-container">';
        $html .= '<button type="button" class="button button-secondary';
        $html .= ' awf-delete-custom-range-value-btn" title="' . esc_attr__( 'Delete value', 'annasta-filters' ) . '"';
        $html .= '>' . esc_html( number_format( $value, 2, wc_get_price_decimal_separator(), wc_get_price_thousand_separator() ) ) . '</button>';
        $html .= '</div>';
      }

      $html .= '</div>';

      $html .= '<div>';
      $html .= '<input class="awf-new-range-value" type="text" value="" style="width: 10em;">';
      $html .= '<button type="button" class="button button-secondary awf-add-custom-range-value-btn"';
      $html .= ' title="' . esc_attr__( 'Add new value to the range', 'annasta-filters' ) . '">' . esc_html__( 'Add value', 'annasta-filters' );
      $html .= '</button>';
      $html .= '</div>';

      return $html;
    }

    private function build_show_range_btn( $filter ) {
      $html = '<div>';
      $html .= '<input type="checkbox" id="' . $filter->prefix . 'show_range_btn" name="' . $filter->prefix . 'show_range_btn" value="yes"';
      if( ! empty( $filter->settings['style_options']['show_range_btn'] ) ) { $html .= ' checked="checked"'; }
      $html .= '>';
      $html .= '<label for="' . $filter->prefix . 'show_range_btn">' . esc_html__( 'Display Filter button', 'annasta-filters' ) . '</label>';
      $html .= '<span class="woocommerce-help-tip" data-tip="' . esc_attr__( 'Display submit button under your range control', 'annasta-filters' ) . '"></span>';
      $html .= '</div>';

      return $html;
    }

    public function build_style_options( $filter, $type = null ) {
      if( is_null( $type ) ) {
        $type = $filter->settings['type'];
      } else { 
        if( ! in_array( $filter->settings['style'], $this->filter_types[$type]['styles'] ) ) { $filter->settings['style'] = null; }
      }

      if( empty( $type ) || ! isset( $this->filter_types[$type] ) ) {
        return;
      } else {
        $filter->settings['type'] = $type;
      }

      if( isset( $this->filter_style_limitations[$filter->module] ) ) {
        $styles = array_intersect( $this->filter_types[$type]['styles'], $this->filter_style_limitations[$filter->module][$type] ) ;
      } else {
        $styles = $this->filter_types[$type]['styles'];
      }
      
      if( 'range' === $type && 'range' === $filter->settings['type'] && isset( $filter->settings['type_options']['range_type'] ) && 'taxonomy_range' === $filter->settings['type_options']['range_type'] ) {
        $styles = array( 'range-slider' );
        $filter->settings['style'] = 'range-slider';
      }

      if( is_null( $filter->settings['style'] ) ) { $filter->settings['style'] = reset( $styles ); }

      $select_html = '<select name="' . $filter->prefix . 'style" id="' . $filter->prefix . 'style" class="awf-filter-style-select">';
      $options_html = '<div id="' . $filter->prefix . 'style_options_container" class="awf-style-options-container">';

      foreach( $styles as $value ) {
        $select_html .= '<option value="' . esc_attr( $value ) . '"';
        if( $filter->settings['style'] === $value ) {
          $select_html .= ' selected="selected"';
          $options_html .= $this->get_style_options_html( $filter, $value );
        }
        $select_html .= '>' . esc_html( $this->filter_styles[$value] );
        $select_html .= '</option>';
      }

      $select_html .= '</select>';
      $select_html .= '<button type="button" title="' . esc_attr__( 'Toggle style options', 'annasta-filters' ) . '" class="button button-secondary awf-icon awf-style-options-btn"></button>';
      $options_html .= '</div>';

      return '<div class="awf-filter-style-select-wrapper">' . $select_html . '</div>' . $options_html;
    }

    private function get_style_options_html( $filter, $style ) {
      $html = '';
      $method = 'build_' . str_replace( '-', '_', $style ) . '_options_html';
      
      if( method_exists( $this, $method ) ) {
        $html .= $this->{$method}( $filter );
      }
      
      return $html;
    }

    public function build_daterangepicker_options_html( $filter ) {
      $db_date_formats = A_W_F::get_db_date_formats();
      $db_date_format_options = array();
      foreach( $db_date_formats as $type => $data ) {
        $db_date_format_options[$type] = $data['label'];
      }
      
      $html = '<div class="awf-daterangepicker-options-container">';
      
      $html .= '<div class="awf-options-row">';
      $html .= '<label for="' . $filter->prefix . 'date_picker_type">' . esc_html__( 'Date picker type', 'annasta-filters' ) . '</label>';
      $html .= A_W_F::$admin->build_select_html( array(
        'name' => $filter->prefix . 'date_picker_type', 
        'id' => $filter->prefix . 'date_picker_type', 
        'class' => 'awf-date-picker-type-select', 
        'options' => array( 'single' => __( 'Single date picker', 'annasta-filters' ), 'range' => __( 'Dates range picker', 'annasta-filters' ) ), 
        'selected' => isset( $filter->settings['style_options']['date_picker_type'] ) ? $filter->settings['style_options']['date_picker_type'] : ''
      ) );
      $html .= '</div>';
      
      $html .= '<div class="awf-options-row">';
      $html .= '<label for="' . $filter->prefix . 'db_date_format">' . esc_html__( 'Database date format', 'annasta-filters' ) . '</label>';
      $html .= '<span class="woocommerce-help-tip" data-tip="' . esc_attr__( 'Select the date format in which date values are stored in the database.', 'annasta-filters' ) . '"></span>';
      $html .= A_W_F::$admin->build_select_html( array(
        'name' => $filter->prefix . 'db_date_format', 
        'id' => $filter->prefix . 'db_date_format', 
        'class' => 'awf-date-format-select', 
        'options' => $db_date_format_options, 
        'selected' => isset( $filter->settings['style_options']['db_date_format'] ) ? $filter->settings['style_options']['db_date_format'] : ''
      ) );
      $html .= '</div>';
      
      $html .= '<div class="awf-options-row">';
      $html .= '<label for="' . $filter->prefix . 'daterangepicker_placeholder">' . esc_html__( 'Placeholder text', 'annasta-filters' ) . '</label>';
      $html .= '<input id="' . $filter->prefix . 'daterangepicker_placeholder" type="text" name="' . $filter->prefix . 'daterangepicker_placeholder" value="' . esc_attr( empty( $filter->settings['style_options']['daterangepicker_placeholder'] ) ? __( 'Select date...', 'annasta-filters' ) : $filter->settings['style_options']['daterangepicker_placeholder'] ) . '">';
      $html .= '</div>';
      
      $html .= '</div>';
      
      return $html;
    }

    public function build_icons_options_html( $filter ) {

      if( isset( $filter->settings['style_options']['icons'] ) ) {
        $icons = $filter->settings['style_options']['icons'];
        $solid_icons_class = $filter->settings['style_options']['solid'];
        $solid_icons = array_map( function( $is_solid ) {
          if( ! empty( $is_solid ) ) {
            return ' checked="checked"';
          }
          return '';
        }, $solid_icons_class );
        
      } else {
        $icons = array( '', '', '', '' );
        $solid_icons_class = array( '', '', 'awf-solid', '' );
        $solid_icons = array( '', '', ' checked="checked"', '' );
      }

      $html = '<div class="awf-icons-options-container">';
      $html .= '<h4>' . esc_html__( 'Set icons', 'annasta-filters' ) . '</h4>';
      $html .= '<table><tbody>';

      $unselected_id = $filter->prefix . 'unselected_icon';
      $html .= '<tr><td>';
      $html .= '<label for="' . $unselected_id . '">' . esc_html__( 'Inactive filter', 'annasta-filters' ) . '</label>';
      $html .= '</td><td>';
      $html .= '<input id="' . $unselected_id . '" type="text" name="' . $unselected_id . '" value="' . esc_attr( $icons[0] ) . '" class="awf-filter-icon awf-unselected-icon ' . sanitize_html_class( $solid_icons_class[0] ) . '">';
      $html .= '</td><td>';
      $html .= '<label><input type="checkbox" name="' . $unselected_id . '_solid" value="yes"' . $solid_icons[0] . ' class="awf-solid-icon">' . esc_html__( 'Solid style', 'annasta-filters' ) . '</label>';
      $html .= '</td></tr>';

      $unselected_hover_id = $filter->prefix . 'unselected_icon_hover';
      $html .= '<tr><td>';
      $html .= '<label for="' . $unselected_hover_id . '">' . esc_html__( 'Inactive filter hover', 'annasta-filters' ) . '</label>';
      $html .= '</td><td>';
      $html .= '<input id="' . $unselected_hover_id . '" type="text" name="' . $unselected_hover_id . '" value="' . esc_attr( $icons[1] ) . '" class="awf-filter-icon awf-unselected-icon-hover ' . sanitize_html_class( $solid_icons_class[1] ) . '">';
      $html .= '</td><td>';
      $html .= '<label><input type="checkbox" name="' . $unselected_hover_id . '_solid" value="yes"' . $solid_icons[1] . ' class="awf-solid-icon">' . esc_html__( 'Solid style', 'annasta-filters' ) . '</label>';
      $html .= '</td></tr>';

      $selected_id = $filter->prefix . 'selected_icon';
      $html .= '<tr><td>';
      $html .= '<label for="' . $selected_id . '">' . esc_html__( 'Active filter', 'annasta-filters' ) . '</label>';
      $html .= '</td><td>';
      $html .= '<input id="' . $selected_id . '" type="text" name="' . $selected_id . '" value="' . esc_attr( $icons[2] ) . '" class="awf-filter-icon awf-selected-icon ' . sanitize_html_class( $solid_icons_class[2] ) . '">';
      $html .= '</td><td>';
      $html .= '<label><input type="checkbox" name="' . $selected_id . '_solid" value="yes"' . $solid_icons[2] . ' class="awf-solid-icon">' . esc_html__( 'Solid style', 'annasta-filters' ) . '</label>';
      $html .= '</td></tr>';

      $selected_hover_id = $filter->prefix . 'selected_icon_hover';
      $html .= '<tr><td>';
      $html .= '<label for="' . $selected_hover_id . '">' . esc_html__( 'Active filter hover', 'annasta-filters' ) . '</label>';
      $html .= '</td><td>';
      $html .= '<input id="' . $selected_hover_id . '" type="text" name="' . $selected_hover_id . '" value="' . esc_attr( $icons[3] ) . '" class="awf-filter-icon awf-selected-icon-hover ' . sanitize_html_class( $solid_icons_class[3] ) . '">';
      $html .= '</td><td>';
      $html .= '<label><input type="checkbox" name="' . $selected_hover_id . '_solid" value="yes"' . $solid_icons[3] . ' class="awf-solid-icon">' . esc_html__( 'Solid style', 'annasta-filters' ) . '</label>';
      $html .= '</td></tr>';

      $html .= '</tbody></table>';
      $html .= '</div>';
      
      $html .= '<div class="awf-icons-preview-container">';
      $html .= '<h4>' . esc_html__( 'Preview', 'annasta-filters' ) . '</h4>';
        
      $preview_terms = $filter->get_limited_terms();

      foreach( $preview_terms as $i => $term ) {
        if( $i === 6 ) { break; }
        $html .= '<label class="';
        if( $i === 2 || ( $i === 5 && 'multi' === $filter->settings['type'] ) ) {
          $html .= 'awf-selected-icon-preview"><span class="awf-filter-icon ' . sanitize_html_class( $solid_icons_class[2] ) . '">' . esc_html( $icons[2] );
        }
        else { $html .= 'awf-unselected-icon-preview"><span class="awf-filter-icon ' . sanitize_html_class( $solid_icons_class[0] ) . '">' . esc_html( $icons[0] ); }

        $html .= '</span>' . esc_html( $term->name ) . '</label>';
      }
      
      $html .= '</div>';

      $html .= '<div class="awf-icons-examples-container" data-tip="' . esc_attr__( 'Copied to clipboard', 'annasta-filters' ) . '">';
      $html .= '<h4>' . esc_html__( 'Click an icon to copy to clipboard, then paste to the chosen box', 'annasta-filters' );
      $html .= '<span class="woocommerce-help-tip" data-tip="' . esc_attr__( 'Some icons are available only in the solid version, so make sure to toggle the \'Solid style\' checkbox if the icon doesn\'t display properly. Go to Fontawesome Icons Gallery for more amazing icons for your shop!', 'annasta-filters' ) . '"></span>' . '</h4>';
      
      $html .= '<span class="awf-icon-example"></span>';
      $html .= '<span class="awf-icon-example"></span>';
      $html .= '<span class="awf-icon-example"></span>';
      $html .= '<span class="awf-icon-example"></span>';
      $html .= '<span class="awf-icon-example awf-solid" title="' . esc_attr__( 'Solid style only', 'annasta-filters' ) . '"></span>';
      $html .= '<span class="awf-icon-example"></span>';
      $html .= '<span class="awf-icon-example"></span>';
      $html .= '<span class="awf-icon-example"></span>';
      $html .= '<span class="awf-icon-example"></span>';
      $html .= '<span class="awf-icon-example"></span>';
      $html .= '<span class="awf-icon-example"></span>';
      $html .= '<span class="awf-icon-example"></span>';
      
      $html .= '</div>';

      return $html;
    }

    public function build_colours_options_html( $filter ) {
      $terms_by_parent = $filter->build_terms_by_parent( $filter->get_filter_terms() );
      $first_parent = 0;

      $html = '<ul>';
      $html .= '<li>';
      $html .= '<input id="' . $filter->prefix . 'show_label" type="checkbox" name="' . $filter->prefix . 'show_label" value="yes"';
      if( ! isset( $filter->settings['style_options']['hide_label'] ) ) { $html .= ' checked="checked"'; }
      $html .= '>';
      $html .= '<label for="' . $filter->prefix . 'show_label">' . esc_html__( 'Display label', 'annasta-filters' ) . '</label>';
      $html .= '</li>';
      $html .= '</ul>';

      $html .= '<table class="awf-filter-options-secondary-table awf-terms-colours-container"><tbody>';
      $html .= $this->build_terms_colours_list( $filter, $terms_by_parent, $first_parent );
      $html .= '</tbody></table>';

      return $html;
    }

    protected function build_terms_colours_list( $filter, $terms_by_parent, $parent_id = 0 ) {
      $terms_html = '';

      foreach ( $terms_by_parent[$parent_id] as $term ) {
        $terms_html .= '<tr class="awf-term-colour-container">';
        $terms_html .= '<td>' . esc_html( $term->name ) . '</td>';
        $terms_html .= '<td>';
        $terms_html .= '<input type="text" name="' . $filter->prefix . 'term_' . sanitize_html_class( $term->term_id ) . '_colour" value="';
        if( isset( $filter->settings['style_options']['colours'] ) && isset( $filter->settings['style_options']['colours'][$term->term_id] ) ) {
          $terms_html .= esc_attr( $filter->settings['style_options']['colours'][$term->term_id] );
        }
        $terms_html .= '" class="awf-colorpicker" >';
        $terms_html .= '</td>';
        $terms_html .= '</tr>';

        if( isset( $terms_by_parent[$term->term_id] ) ) {
          $terms_html .= $this->build_terms_colours_list( $filter, $terms_by_parent, $term->term_id );
        }
      }

      return $terms_html;
    }

    public function build_range_slider_options_html( $filter ) {
      $html = '<div class="awf-range-slider-options-container">';
      
      if( in_array( $filter->settings['type_options']['range_type'], array( 'auto_range', 'custom_range' ) ) ) {
        $html .= '<div class="awf-range-slider-steps-container">';
        $html .= '<label for="' . $filter->prefix . 'step">' . esc_html__( 'Slider step', 'annasta-filters' ) . '</label>';
        $html .= '<span class="woocommerce-help-tip" data-tip="' . esc_attr__( 'This controls the smallest step that a value in a range slider control can jump to.', 'annasta-filters' ) . '"></span>';
        $html .= '<input name="' . $filter->prefix . 'step" type="text" value="' . esc_attr( empty( $filter->settings['style_options']['step'] ) ? '1' : $filter->settings['style_options']['step'] ) . '" style="width: 5em;">';
        $html .= '</div>';
        
        $html .= '<div class="awf-range-slider-tooltips-container">';
        $html .= '<label for="' . $filter->prefix . 'slider_tooltips">' . esc_html__( 'Tooltips display', 'annasta-filters' ) . '</label>';
        $html .= '<span class="woocommerce-help-tip" data-tip="' . esc_attr__( 'This option controls the display of tooltip labels for the current values of range slider.', 'annasta-filters' ) . '"></span>';
        
        $tooltips_options = array( 'none' => __( 'None', 'annasta-filters' ), 'above_handles' => __( 'Above slider handles', 'annasta-filters' ) );
        if( $this instanceof A_W_F_premium_admin ) { $this->add_premium_tooltips_options( $filter, $tooltips_options ); }
        
        $html .= A_W_F::$admin->build_select_html( array(
          'name' => $filter->prefix . 'slider_tooltips',
          'id' => $filter->prefix . 'slider_tooltips',
          'selected' => empty( $filter->settings['style_options']['slider_tooltips'] ) ? 'above_handles' : $filter->settings['style_options']['slider_tooltips'],
          'options' => $tooltips_options
        ) );
        $html .= '</div>';
      }

      if( A_W_F::$premium ) {
        $html .= '<div class="awf-range-slider-hide-slider-container">';
        $html .= '<input type="checkbox" id="' . $filter->prefix . 'hide_slider" name="' . $filter->prefix . 'hide_slider" value="yes"';
        if( ! empty( $filter->settings['style_options']['hide_slider'] ) ) { $html .= ' checked="checked"'; }
        $html .= '>';
        $html .= '<label for="' . $filter->prefix . 'hide_slider">' . esc_html__( 'Hide slider', 'annasta-filters' ) . '</label>';
        $html .= '<span class="woocommerce-help-tip" data-tip="' . esc_attr__( 'Hide the slider in cases when you wish to submit range values exclusively through interactive tooltips controls.', 'annasta-filters' ) . '"></span>';
        $html .= '</div>';
      }

      $html .= $this->build_show_range_btn( $filter );
      
      $html .= '</div>';

      if( in_array( $filter->settings['type_options']['range_type'], array( 'auto_range', 'taxonomy_range' ) ) ) {
        $html .= '<button name="save" class="button-primary woocommerce-save-button" type="submit" value="' . esc_attr__( 'Save and refresh range preview', 'annasta-filters' ) . '">' . esc_html__( 'Update preset and refresh range preview', 'annasta-filters' ) . '</button>';
      }
      
      if( ! empty( $filter->settings['type_options']['range_values'] ) && count( $filter->settings['type_options']['range_values'] ) > 1 ) {
        $tooltips = empty( $filter->settings['style_options']['slider_tooltips'] ) ? 'above_handles' : $filter->settings['style_options']['slider_tooltips'];
        
        if( 'interactive_above' === $tooltips && method_exists( $this, 'get_interactive_tooltips_html' ) ) {
          if( $this instanceof A_W_F_premium_admin ) { $html .= $this->get_interactive_tooltips_html(); }
        }
        
        $html .= '<div class="awf-range-slider-preview"';
        $html .= ' data-step="' . esc_attr( empty( $filter->settings['style_options']['step'] ) ? '1' : esc_attr( $filter->settings['style_options']['step'] ) ) . '"';
        
        if( 'taxonomy_range' === $filter->settings['type_options']['range_type'] ) {
          $html .= ' data-tooltips="none"';
          $html .= ' data-taxonomy-range="1"';
          $html .= ' data-labels="';
          if( ! empty( $filter->settings['type_options']['range_labels'] ) ) {
            $html .= esc_attr( implode( '_+_', $filter->settings['type_options']['range_labels'] ) );
          }
          $html .= '"';
          $html .= ' data-values="' . esc_attr( implode( '_+_', $filter->settings['type_options']['range_values'] ) ) . '"';
          
        } else {
          $html .= ' data-tooltips="' . esc_attr( $tooltips ) . '"';
          $html .= ' data-prefix="' . esc_attr( empty( $filter->settings['style_options']['value_prefix'] ) ? '' : $filter->settings['style_options']['value_prefix'] ) . '"';
          $html .= ' data-postfix="' . esc_attr( empty( $filter->settings['style_options']['value_postfix'] ) ? '' : $filter->settings['style_options']['value_postfix'] ) . '"';
          $html .= ' data-decimals="' . esc_attr( empty( $filter->settings['type_options']['decimals'] ) ? 0 : $filter->settings['type_options']['decimals'] ) . '"';
          $html .= ' data-decimals-separator="' . esc_attr( wc_get_price_decimal_separator() ) . '"';
          $html .= ' data-thousand-separator="' . esc_attr( wc_get_price_thousand_separator() ) . '"';
          $html .= ' data-values="' . esc_attr( implode( '_+_', $filter->settings['type_options']['range_values'] ) ) . '"';
        }

        $html .= '>';
        $html .= '</div>';
      }

      return $html;
    }

    protected function setup_filter_terms_limitation_settings( &$filter ) {

      $terms_limitation = null;

      switch( $filter->settings['terms_limitation_mode'] ) {
        case 'include':
          $terms_limitation = 'included_items';
          if( ! isset( $filter->settings[$terms_limitation] ) ) { $filter->settings[$terms_limitation] = array(); }
          break;
        case 'active':
          $terms_limitation = 'active_items';
          break;
        default:
          $terms_limitation = 'excluded_items';
          if( ! isset( $filter->settings[$terms_limitation] ) ) { $filter->settings[$terms_limitation] = array(); }
          break;
      }

      return $terms_limitation;
    }

    public function build_terms_limitations( $filter ) {
      $terms_limitation = $this->setup_filter_terms_limitation_settings( $filter );

      $terms = $filter->get_filter_terms();

      if( empty( $terms ) ) return '<div>' . esc_html__( 'This filter has no terms', 'annasta-filters' ) . '</div>';

      if( 'active_items' === $terms_limitation ) {
        $html = '<table class="awf-filter-options-secondary-table awf-terms-limitations-table">';
        $html .= '<thead><tr><th><div>' . esc_html__( 'Limit filter options display to hierarchical branches of currently selected terms.', 'annasta-filters' ) . '</div></br><div class="awf-info-notice">' . esc_html__( 'When none of filter options is selected, this setting will respect the Excluded items list, so please edit it accordingly by temporarily selecting the "Exlude from list" option above.', 'annasta-filters' ) . '</div></br><label for="' . $filter->prefix. 'display_active_filter_siblings" class="awf-secondary-label">' . __( 'Display active filters\' siblings', 'annasta-filters' ) . '</label><input type="checkbox" name="' . $filter->prefix. 'display_active_filter_siblings" id="' . $filter->prefix. 'display_active_filter_siblings" value="yes"' . ( ! empty( $filter->settings['style_options']['display_active_filter_siblings'] ) ? ' checked="checked"' : '' ) .'></br></br><div class="awf-info-notice">' . esc_html__( 'Siblings of an active filter belonging to the last hierarchical level will always get displayed.', 'annasta-filters' ) . '</div>' . '</th></tr></thead>';
        $html .= '</table>';
        
        return $html;
      }

      $terms_by_id = array();
      foreach( $terms as $term ) {
        $terms_by_id[$term->term_id] = $term;
      }
      
      /* Cleanup current terms limitations array */
      $delete_terms = array_diff( $filter->settings[$terms_limitation], array_keys( $terms_by_id ) );
      if( ! empty( $delete_terms ) ) {
        $filter->settings[$terms_limitation] = array_diff( $filter->settings[$terms_limitation], $delete_terms );
        update_option( $filter->prefix. 'settings', $filter->settings );
      }
      /* endof Cleanup current terms limitations array */

      $terms_for_select = $filter->build_terms_by_parent( $terms );
      $add_label = $remove_label = '';

      if( 'included_items' === $terms_limitation ) {
        $terms_for_select = empty( $terms_for_select[0] ) ? array( 0 => array() ) : array( 0 => $terms_for_select[0] );
        $add_label = __( 'Add to selected', 'annasta-filters' );
        $remove_label = __( 'Remove from selected', 'annasta-filters' );

      } elseif( 'excluded_items' === $terms_limitation ) {
        $add_label = __( 'Exclude', 'annasta-filters' );
        $remove_label = __( 'Remove from exclusions', 'annasta-filters' );
      }

      $html = '<table class="awf-filter-options-secondary-table awf-terms-limitations-table">';
      $html .= '<tbody>';

      foreach( $filter->settings[$terms_limitation] as $ei ) {
        if( isset( $terms_by_id[$ei] ) ) {
          $html .= '<tr id="awf-terms-limitation_' . $filter->preset_id . '_' . $filter->id . '_' . sanitize_html_class( $ei ) . '" class="awf-terms-limitation-container"><td>' . esc_html( $terms_by_id[$ei]->name ) . '</td>';
          $html .= '<td class="awf-terms-limitation-btn-container"><button type="button" class="button button-secondary awf-icon awf-delete-btn awf-remove-terms-limitation-btn"';
          $html .= ' title="' . esc_attr( $remove_label ) . '"></button></td>';
          $html .= '</tr>';
        }
      }

      $html .= '</tbody>';

      $select_options = $this->build_terms_limitations_select( $filter->settings[$terms_limitation], $terms_for_select );

      if( ! empty( $select_options ) ) {
        $html .= '<tfoot><tr>';
        $html .= '<td>';
        $html .= '<select id="awf-terms-limitations-' . $filter->preset_id . '-' . $filter->id . '">';
        $html .= $select_options;
        $html .= '</select>';
        $html .= '</td>';
        $html .= '<td class="awf-terms-limitation-btn-container">';
        $html .= '<button type="button" class="button button-secondary awf-add-terms-limitation-btn" title="' . esc_attr( $add_label ) . '">' . esc_html( $add_label ) . '</button>';
        $html .= '</td>';
        $html .= '</tr></tfoot>';
      }

      $html .= '</table>';

      return $html;
    }

    protected function build_terms_limitations_select( $limited_terms, $terms_by_parent, $parent_id = 0, $indentation = '' ) {
      $options_html = '';
			
			if( isset( $terms_by_parent[$parent_id] ) ) {
				foreach ( $terms_by_parent[$parent_id] as $term ) {
					if( in_array( $term->term_id, $limited_terms ) ) continue;
					$options_html .= '<option value="' . esc_attr( $term->term_id ) . '">';
					$options_html .= $indentation . esc_html( $term->name );
					$options_html .= '</option>';
					if( isset( $terms_by_parent[$term->term_id] ) ) {
						$options_html .= $this->build_terms_limitations_select( $limited_terms, $terms_by_parent, $term->term_id, $indentation . '&nbsp;&nbsp;' );
					}
				}
			}

      return $options_html;
    }

    public function build_ppp_values_list( $filter, $ppp_default ) {
      $html = '';

      foreach( $filter->settings['ppp_values'] as $value => $label ) {
        $html .= '<tr id="awf_ppp_value_' . $filter->preset_id . '_' . $filter->id . '_' . sanitize_html_class( $value ) .'" class="awf-ppp-value-container">';
        $html .= '<td>';
        if( -1 !== $value ) { $html .= $value; }
        $html .= ' ' . esc_html( $label );
        $html .= '</td>';
        $html .= '<td>';
        if( $value === $ppp_default ) {
          $html .= '<span class="dashicons dashicons-yes" title="' . esc_attr__( 'This is the default products per page value for your shop. You can change it in the Plugin Settings tab.', 'annasta-filters' ) . '"></span>';
        }
        $html .= '</td>';
        $html .= '<td class="awf-buttons-column"><button type="button" class="button button-secondary awf-icon awf-delete-btn awf-remove-ppp-value-btn" title="' . esc_attr__( 'Delete value', 'annasta-filters' ) . '"></button></td>';
        $html .= '</tr>';
      }

      return $html;
    }
    
    public function awf_display_preset_btns( $preset_id, $admin_url ) {}
    
    public function awf_display_presets_list_footer( $admin_url = '' ) {
      echo sprintf( wp_kses( __( '<strong><a href="%1$s">Upgrade</a></strong> to <strong>annasta Woocommerce Product Filters Premium</strong> to manage multiple presets!', 'annasta-filters' ), array(  'a' => array( 'href' => array() ), 'strong' => array() ) ), esc_url( a_w_f_fs()->get_upgrade_url() ) );
    }

    protected function get_module_defaults( $filter_data ) {
      $method = 'get_' . $filter_data['module'] . '_defaults';
      if( method_exists( $this, $method ) ) {
        return $this->$method( $filter_data );
      }

      return array();
    }

    protected function get_taxonomy_defaults( $filter_data ) {
      $settings = array(
        'taxonomy'                => $filter_data['taxonomy']->name,
        'title'                   => $filter_data['taxonomy']->label,
        'show_title'              => true,
        'active_prefix'           => '',
        'show_active'             => false,
        'is_collapsible'          => false,
        'collapsed_on'            => false,
        'type'                    => 'multi',
        'type_options'            => array(),
        'style'                   => 'icons',
        'style_options'           => array(),
        'show_in_row'             => false,
        'show_search'             => false,
        'show_search_placeholder' => '',
        'terms_limitation_mode'   => 'exclude',
        'excluded_items'          => array(),
        'included_items'          => array(),
        'sort_by'                 => 'admin',
        'sort_order'              => 'none',
        'height_limit'            => '0',
        'show_count'              => false,
      );

      if( $filter_data['taxonomy']->hierarchical ) {
        $position = intval( array_search( 'show_search', array_keys( $settings ) ) );
        
        $settings = array_merge(
          array_slice( $settings, 0, $position, true),
          array(
            'hierarchical_level' => 1,
            'display_children' => true,
            'children_collapsible' => true,
            'children_collapsible_on' => true,
          ),
          array_slice( $settings, $position, count( $settings ) - 1, true)
        );
      }

      return $settings;
    }

    protected function get_search_defaults( $filter_data ) {
      return( array(
        'title'           => $filter_data['title'],
        'show_title'      => true,
        'active_prefix'   => '',
        'show_active'     => false,
        'is_collapsible'  => false,
        'collapsed_on'    => false,
        'type'            => null,
        'type_options'    => array(),
        'style'           => null,
        'style_options'   => array(),
        'placeholder'     => __( 'Search products...', 'annasta-filters' ),
        'autocomplete'    => false,
        'height_limit'    => '0',
      ) );
    }

    protected function get_price_defaults( $filter_data ) {
      return( array(
        'title'             => $filter_data['title'],
        'show_title'        => true,
        'active_prefix'     => '',
        'show_active'       => false,
        'is_collapsible'    => false,
        'collapsed_on'      => false,
        'type'              => 'range',
        'type_options'      => array(
          'range_type'      => 'auto_range',
          'range_values'    => array( floatval( 0 ), floatval( $this->get_products_max_price() ) ),
          'decimals'        => intval( 0 )
        ),
        'style'             => 'range-slider',
        'style_options'     => array( 'step' => floatval( 1 ), 'value_prefix' => '', 'value_postfix' => '' ),
        'show_in_row'       => false,
        'height_limit'      => '0',
      ) );
    }

    protected function get_stock_defaults( $filter_data ) {
      return( array(
        'title'                   => $filter_data['title'],
        'show_title'              => true,
        'active_prefix'           => '',
        'show_active'             => false,
        'is_collapsible'          => false,
        'collapsed_on'            => false,
        'type'                    => 'single',
        'style'                   => 'radios',
        'style_options'           => array(),
        'show_in_row'             => false,
        'show_search'             => false,
        'show_search_placeholder' => '',
        'terms_limitation_mode'   => 'exclude',
        'excluded_items'          => array(),
        'included_items'          => array(),
        'height_limit'            => '0',
      ) );
    }

    protected function get_featured_defaults( $filter_data ) {
      return( array(
        'title'             => $filter_data['title'],
        'show_title'        => true,
        'is_collapsible'    => false,
        'collapsed_on'      => false,
        'type'              => 'multi',
        'style'             => 'checkboxes',
        'style_options'     => array(),
        'height_limit'      => '0',
      ) );
    }

    protected function get_onsale_defaults( $filter_data ) {
      return( array(
        'title'             => $filter_data['title'],
        'show_title'        => true,
        'is_collapsible'    => false,
        'collapsed_on'      => false,
        'type'              => 'multi',
        'style'             => 'checkboxes',
        'style_options'     => array(),
        'height_limit'      => '0',
      ) );
    }

    protected function get_rating_defaults( $filter_data ) {
      return( array(
        'title'                   => $filter_data['title'],
        'show_title'              => true,
        'active_prefix'           => '',
        'show_active'             => false,
        'is_collapsible'          => false,
        'collapsed_on'            => false,
        'type'                    => 'range',
        'type_options'            => array( 
          'range_type' => 'custom_range',
          'range_values' => array( round( floatval( 1 ), 2, PHP_ROUND_HALF_UP ), round( floatval( 2 ), 2, PHP_ROUND_HALF_UP ), round( floatval( 3 ), 2, PHP_ROUND_HALF_UP ), round( floatval( 4 ), 2, PHP_ROUND_HALF_UP ), round( floatval( 5 ), 2, PHP_ROUND_HALF_UP ), round( floatval( 5.01 ), 2, PHP_ROUND_HALF_UP ) ),
          'precision' => round( floatval( 0.01 ), 2, PHP_ROUND_HALF_UP ),
          'decimals' => intval( 0 ),
        ),
        'style'                   => 'radios',
        'style_options'           => array( 'step' => floatval( 1 ), 'value_prefix' => '', 'value_postfix' => '' ),
        'show_in_row'             => false,
        'show_search'             => false,
        'show_search_placeholder' => '',
        'height_limit'            => '0',
      ) );
    }
    
    protected function get_ppp_defaults( $filter_data ) {
      return( array(
        'title'                   => $filter_data['title'],
        'show_title'              => true,
        'show_active'             => false,
        'is_collapsible'          => false,
        'collapsed_on'            => false,
        'type'                    => 'single',
        'style'                   => 'radios',
        'style_options'           => array(),
        'ppp_values'              => array( 12 => __( 'products per page', 'annasta-filters' ) ),
        'show_in_row'             => false,
        'show_search'             => false,
        'show_search_placeholder' => '',
        'height_limit'            => '0',
      ) );
    }
    
    protected function get_orderby_defaults( $filter_data ) {
      return( array(
        'title'                   => $filter_data['title'],
        'show_title'              => true,
        'active_prefix'           => '',
        'show_active'             => false,
        'is_collapsible'          => false,
        'collapsed_on'            => false,
        'type'                    => 'single',
        'style'                   => 'radios',
        'style_options'           => array(),
        'show_in_row'             => false,
        'show_search'             => false,
        'show_search_placeholder' => '',
        'terms_limitation_mode'   => 'exclude',
        'excluded_items'          => array(),
        'included_items'          => array(),
        'height_limit'            => '0',
      ) );
    }
    
    protected function get_meta_defaults( $filter_data ) {
      return( array(
        'meta_name'               => '',
        'title'                   => $filter_data['title'],
        'show_title'              => true,
        'active_prefix'           => '',
        'show_active'             => false,
        'is_collapsible'          => false,
        'collapsed_on'            => false,
        'type'                    => 'single',
        'type_options'            => array(),
        'style'                   => 'radios',
        'style_options'           => array(),
        'show_in_row'             => false,
        'show_search'             => false,
        'show_search_placeholder' => '',
        'terms_limitation_mode'   => 'exclude',
        'excluded_items'          => array(),
        'included_items'          => array(),
        'height_limit'            => '0',
      ) );
    }

    public function get_all_filters() {
      $filters = array_flip( array_diff( A_W_F::$modules, array( 'taxonomy' ) ) );
      foreach( $filters as $filter_name => $label ) { $filters[$filter_name] = $this->get_filter_title( $filter_name ); }

      $taxonomies = get_object_taxonomies( 'product', 'objects' );

      foreach( $taxonomies as $t ) {
        if( in_array( $t->name, A_W_F::$excluded_taxonomies ) ) continue;

        $filters['taxonomy--' . $t->name] = $t->label;
      }
      
      return $filters;
    }

    public function get_filter_title( $filter_name ) {
      switch( $filter_name ) {
        case 'search': return esc_html__( 'Products Search', 'annasta-filters' ); break;
        case 'ppp': return esc_html__( 'Products Per Page', 'annasta-filters' ); break;
        case 'stock': return esc_html__( 'Products Stock Status Filter', 'annasta-filters' ); break;
        case 'price': return esc_html__( 'Products Price Filter', 'annasta-filters' ); break;
        case 'featured': return esc_html__( 'Featured Products Filter', 'annasta-filters' ); break;
        case 'onsale': return esc_html__( 'Products on Sale Filter', 'annasta-filters' ); break;
        case 'rating': return esc_html__( 'Products Rating Filter', 'annasta-filters' ); break;
        case 'orderby': return esc_html__( 'Products Sort by Control', 'annasta-filters' ); break;
        case 'meta': return esc_html__( 'Products Meta Data Filter', 'annasta-filters' ); break;
        default: return ''; break;
      }
    }

    public function get_default_filter_label( $module, $settings ) {
      $label = '';

      switch( $module ) {
        case 'taxonomy':
          $taxonomy = get_taxonomy( $settings['taxonomy'] );
          if( $taxonomy ) {
            $label = $taxonomy->label;
          }
          break;
        case 'meta':
          $label = sprintf( esc_html__( '%1$s Meta Data Filter', 'annasta-filters' ), $settings['meta_name'] );
          break;
        default:
          $label = $this->get_filter_title( $module );
      }

      return $label;
    }

    protected function get_presets_names() {
      $presets = array();
      
      foreach( A_W_F::$presets as $preset_id => $preset_data ) {
        $presets[$preset_id] = __( get_option( 'awf_preset_' . $preset_id . '_name', '' ) );
      }
      
      return $presets;
    }

    public function get_preset_settings( $preset ) {
      $prefix = 'awf_preset_' . $preset->id . '_';

      return array(
        array(
          'id' => 'awf_preset_settings_section_1', 
          'type' => 'title',
          'name' => 0 === $preset->id ? '' : sprintf( __( 'Preset id: %1$s', 'annasta-filters' ), $preset->id ),
        ),

        array( 
          'id'       => $prefix. 'name', 
          'type'     => 'text',
          'name'     => __( 'Preset name', 'annasta-filters' ),
          'default'  => $preset->name,
        ),

        array( 
          'id'       => $prefix. 'title', 
          'type'     => 'text',
          'name'     => __( 'Preset title', 'annasta-filters' ),
          'default'  => $preset->title,
          'desc_tip' => __( 'This will show as your filters\' header. Leave blank if not needed.', 'annasta-filters' )
        ),

        array( 
          'id'       => $prefix. 'description', 
          'type'     => 'textarea',
          'name'     => __( 'Preset description', 'annasta-filters' ),
          'default'  => $preset->description,
          'desc_tip' => __( 'Display custom text under the preset title. Leave blank if not needed.', 'annasta-filters' )
        ),

        array( 
          'id'       => $prefix. 'show_title_badges', 
          'type'     => 'checkbox',
          'name'     => __( 'Active filter badges', 'annasta-filters' ),
          'default'  => $preset->show_title_badges,
          'desc_tip' => __( 'Display active filter badges with reset buttons on top of the preset filters.', 'annasta-filters' )
        ),

        array(
          'id'       => $prefix. 'reset_btn', 
          'type'    => 'select',
          'name'    => __( 'Reset all button', 'annasta-filters' ),
          'default' => $preset->reset_btn,
          'options' => array(
            'none'        => __( 'None', 'annasta-filters' ),
            'top'       => __( 'At the top of the preset', 'annasta-filters' ),
            'bottom'  => __( 'At the bottom of the preset', 'annasta-filters' ),
            'both' => __( 'Both at the top and at the bottom', 'annasta-filters' )
          ),
          'desc'    => __( 'This controls the display of \'Clear all\' buttons that will reset all the existing active filters.', 'annasta-filters' ),
          'desc_tip' =>  true,
        ),

        array( 
          'id'       => $prefix. 'reset_btn_label', 
          'type'     => 'text',
          'name'     => __( 'Reset button label', 'annasta-filters' ),
          'default'  => $preset->reset_btn_label,
        ),

        array( 
          'id'       => $prefix. 'filter_btn_label', 
          'type'     => 'text',
          'name'     => __( 'Filter button label', 'annasta-filters' ),
          'default'  => $preset->filter_btn_label,
        ),

        array(
          'id'       => $prefix. 'type', 
          'type'    => 'select',
          'name'    => __( 'Filtering style', 'annasta-filters' ),
          'default' => $preset->type,
          'options' => array(
            'ajax'          => __( 'AJAX with instant submission', 'annasta-filters' ),
            'ajax-button'   => __( 'AJAX with button submission', 'annasta-filters' ),
            'url'           => __( 'URL filters', 'annasta-filters' ),
            'form'          => __( 'Form with button submission', 'annasta-filters' ),
            'sbs'           => __( 'Step-by-step filters', 'annasta-filters' ),
          ),
          'class' => 'awf-preset-type',
        ),

        array( 'type' => 'sectionend', 'id' => 'awf_preset_settings_section_1' ),
        
        array(
          'id' => 'awf_preset_settings_sbs_section', 
          'type' => 'title',
          'name' => __( 'Step by step filters settings', 'annasta-filters' ),
          'class' => 'awf-sbs-type',
        ),

        array(
          'id'       => $prefix. 'sbs_type', 
          'type'    => 'select',
          'name'    => __( 'Step by step style', 'annasta-filters' ),
          'default' => $preset->sbs_type,
          'options' => array(
            'unhide'   => __( 'Add filters one by one', 'annasta-filters' ),
            'show-one' => __( 'Display one filter at a time', 'annasta-filters' ),
          ),
          'class' => 'awf-sbs-type',
        ),
        
        array(
          'id'       => $prefix. 'sbs_submission', 
          'type'    => 'select',
          'name'    => __( 'Filters submission', 'annasta-filters' ),
          'default' => $preset->sbs_submission,
          'options' => array(
            'instant'   => __( 'Update products list with each filter application', 'annasta-filters' ),
            'instant-last' => __( 'Update products list when the last filter is applied', 'annasta-filters' ),
            'button' => __( 'Filter button displayed for all the steps', 'annasta-filters' ),
            'button-last' => __( 'Filter button displayed after the last filter selection', 'annasta-filters' ),
          ),
        ),

        array( 
          'id'       => $prefix. 'sbs_next_btn', 
          'type'     => 'checkbox',
          'name'     => __( 'Next button', 'annasta-filters' ),
          'default'  => $preset->sbs_next_btn,
          'desc_tip' => __( 'Trigger the moves to a next filter by a click on the "Next" button. By default the transitions from one filter to the next happen automatically when at least one filter option gets selected, which means that you will <strong>need</strong> to use the Next button to enable multi-selection with certain styles.', 'annasta-filters' )
        ),

        array( 
          'id'       => $prefix. 'sbs_back_btn', 
          'type'     => 'checkbox',
          'name'     => __( 'Back button', 'annasta-filters' ),
          'default'  => $preset->sbs_back_btn,
          'desc_tip' => __( 'Back button can be useful when displaying one filter at a time.', 'annasta-filters' )
        ),

        array( 
          'id'       => $prefix. 'sbs_redirect', 
          'type'     => 'text',
          'name'     => __( 'Redirect URL', 'annasta-filters' ),
          'default'  => $preset->sbs_redirect,
          'desc_tip' => __( 'Enter the URL of the page to which you wish to apply the filters. Leave blank to filter the current page, or redirect to the shop page from a non-products page. ATTENTION: for the redirection to a taxonomy archive page (product categories, tags, brands) to work properly, add the "archive-filter=1" parameter after the "?" sign of your url string, like so: https://mysite.com/brand/brand-1/?archive-filter=1.', 'annasta-filters' )
        ),
        
        array( 'type' => 'sectionend', 'id' => 'awf_preset_settings_sbs_section' ),
        
        array(
          'id' => 'awf_preset_settings_section_2', 
          'type' => 'title',
          'name' => __( 'Display options', 'annasta-filters' ),
        ),

        array(
          'id'       => $prefix. 'layout', 
          'type'    => 'select',
          'name'    => __( 'Layout', 'annasta-filters' ),
          'default' => $preset->layout,
          'options' => array(
            '1-column'        => __( '1 column', 'annasta-filters' ),
            '4-column'       => __( '4 columns', 'annasta-filters' )
          ),
          'desc'    => __( 'Choose the 4-column layout for headers or footers, 1-column layout is better for sidebars.', 'annasta-filters' ),
          'desc_tip' =>  true,
        ),

        array(
          'id'       => $prefix. 'display_mode', 
          'type'    => 'select',
          'name'    => __( 'Visibility', 'annasta-filters' ),
          'default' => $preset->display_mode,
          'options' => array(
            'visible'           => __( 'Visible', 'annasta-filters' ),
            'visible-on-s'    => __( 'Visible on screens narrower than the Responsive width', 'annasta-filters' ),
            'visible-on-l'     => __( 'Visible on screens wider than the Responsive width', 'annasta-filters' ),
            'togglable'         => __( 'Controlled by "Filters" button', 'annasta-filters' ),
            'togglable-on-s'  => __( 'Visible on screens wider than the Responsive width, controlled by "Filters" button on narrower screens', 'annasta-filters' ),
          ),
          'desc'    => __( 'If enabled, the "Filters" button will be inserted above the products list. Button-controlled presets will only work on pages with filterable products lists (shop/ taxonomy archives/ shortcode pages). You don\'t need to insert your preset via widget/shortcode when using the "Controlled by "Filters" button" mode.', 'annasta-filters' ),
          'desc_tip' =>  true,
          'class' => 'awf-preset-display-mode',
        ),

        array(
          'id'       => $prefix. 'togglable_mode', 
          'type'    => 'select',
          'name'    => __( '"Filters" button mode', 'annasta-filters' ),
          'default' => $preset->togglable_mode,
          'options' => array(
            'above-products'      => __( 'Display preset filters under the "Filters" button', 'annasta-filters' ),
            'left-popup-sidebar'  => __( 'Display preset filters in a left popup sidebar', 'annasta-filters' ),
          ),
          'desc'    => __( 'Choose the preset style when its visibility is controlled by the "Filters" button (see the "Visibility" setting).', 'annasta-filters' ),
          'desc_tip' =>  true,
          'class' => 'awf-preset-togglable-mode',
        ),

        array( 
          'id'       => $prefix. 'responsive_width', 
          'type'     => 'text',
          'name'     => __( 'Responsive width', 'annasta-filters' ),
          'default'  => $preset->responsive_width,
          'css'      => 'width: 100px;',
          'desc_tip' => __( 'Use in combination with the "Visibility" setting: set the screen width enabling the responsive behaviour, in pixels.', 'annasta-filters' )
        ),
        
        array( 'type' => 'sectionend', 'id' => 'awf_preset_settings_section_2' ),
      );
    }
    
    public function get_product_list_settings() {
      return array(

        10 => array(
          'id' => 'awf_product_list_settings_general_section',
          'type' => 'title',
          'name' => __( 'Woocommerce Product List Settings', 'annasta-filters' ),
        ),
				
				20 => array( 'type' => 'awf_product_list_settings_notice', 'id' => 'awf_product_list_settings_notice' ),

        30 => array( 
          'id'       => 'awf_theme_support', 
          'type'     => 'checkbox',
          'name'     => __( 'Theme support', 'annasta-filters' ),
          'default'  => get_option( 'awf_theme_support', 'yes' ),
          'desc_tip' => $this->get_awf_theme_support_tip(),
        ),

        40 => array( 
          'id'       => 'awf_shop_columns',
          'type'     => 'text',
          'name'     => __( 'Product columns', 'annasta-filters' ),
          'default'  => get_option( 'awf_shop_columns', '' ),
          'desc_tip' => __( 'Set this to the amount of product columns that you wish to be displayed by your shop. Leave blank for the Woocommerce default. WARNING: this option will only work with themes that support the relevant Woocommerce built-in setting. If your theme doesn\'t respond to this setting, set it through your theme customizer, and enter the same value here.', 'annasta-filters' ),
          'css'      => 'width: 50px;'
        ),

        50 => array( 
          'id'       => 'awf_ppp_default',
          'type'     => 'text',
          'name'     => __( 'Products per page', 'annasta-filters' ),
          'default'  => get_option( 'awf_ppp_default', '' ),
          'desc_tip' => __( 'Set your preferred products per page value here. It will be used unless user selects different value on a products per page control. Leave blank to use the Woocommerce default. WARNING: this option will only work with themes that support the built-in Woocommerce products per page setting. If your theme doesn\'t respond to this setting, set it to the amount of products displayed when you first load your shop page.', 'annasta-filters' ),
          'css'      => 'width: 50px;'
        ),
				
        60 => array( 
          'id'       => 'awf_ajax_pagination',
          'type'     => 'select',
          'options'  => array(
            'none'              => __( 'Default', 'annasta-filters' ),
            'page_numbers'      => __( 'AJAX pagination', 'annasta-filters' ),
            'infinite_scroll'   => __( 'Infinite Scroll', 'annasta-filters' ),
            'more_button'       => __( '"Load More" button', 'annasta-filters' ),
          ),
          'name'     => __( 'Pagination', 'annasta-filters' ),
          'default'  => get_option( 'awf_ajax_pagination', 'none' ),
          'desc_tip' => __( 'Select "Default" to leave the default theme pagination, or set to the AJAX pagination style that suits your shop. AJAX pagination options may not work with some themes, you are welcome to contact us if you wish to add support for your theme.', 'annasta-filters' )
        ),
				
        70 => array( 
          'id'       => 'awf_breadcrumbs_support',
          'type'     => 'checkbox',
          'name'     => __( 'Breadcrumbs support', 'annasta-filters' ),
          'default'  => get_option( 'awf_breadcrumbs_support', 'yes' ),
          'desc_tip' => __( 'Uncheck to disable breadcrumbs adjustments on taxonomy archive pages.', 'annasta-filters' ),
        ),

        99 => array( 'type' => 'sectionend', 'id' => 'awf_product_list_settings_general_section' ),

        100 => array(
          'id' => 'awf_product_list_settings_remove_from_template_section',
          'type' => 'title',
          'name' => __( 'Remove from product list template', 'annasta-filters' ),
        ),
        
        110 => array( 
          'id'       => 'awf_remove_wc_shop_title', 
          'type'     => 'checkbox',
          'name'     => __( 'Shop title', 'annasta-filters' ),
          'default'  => get_option( 'awf_remove_wc_shop_title', 'no' ),
          'desc_tip' => __( 'Remove Woocommerce page title from shop page.', 'annasta-filters' )
        ),

        120 => array( 
          'id'       => 'awf_remove_wc_orderby', 
          'type'     => 'checkbox',
          'name'     => __( 'Sort by', 'annasta-filters' ),
          'default'  => get_option( 'awf_remove_wc_orderby', 'no' ),
          'desc_tip' => __( 'Remove or hide all the native Woocommerce Sort by controls.', 'annasta-filters' )
        ),

        199 => array( 'type' => 'sectionend', 'id' => 'awf_product_list_settings_remove_from_template_section' ),

        200 => array(
          'id' => 'awf_product_list_settings_template_section',
          'type' => 'title',
          'name' => __( 'Add to product list template', 'annasta-filters' ),
        ),

				210 => array( 'type' => 'awf_product_list_settings_template_options', 'id' => 'awf_product_list_settings_template_options' ),

        299 => array( 'type' => 'sectionend', 'id' => 'awf_product_list_settings_template_section' ),

        300 => array(
          'id' => 'awf_product_list_settings_ajax_section',
          'type' => 'title',
          'name' => __( 'AJAX options', 'annasta-filters' ),
        ),

        310 => array( 
          'id'       => 'awf_ajax_scroll_on', 
          'type'     => 'checkbox',
          'name'     => __( 'Scroll to ajax results', 'annasta-filters' ),
          'default'  => get_option( 'awf_ajax_scroll_on', 'no' ),
          'desc_tip' => __( 'Enable an animated scroll to the top of the products list on ajax filter application.', 'annasta-filters' )
        ),
        
        320 => array( 
          'id'       => 'awf_ajax_scroll_adjustment', 
          'type'     => 'text',
          'name'     => __( 'Ajax scroll adjustment', 'annasta-filters' ),
          'default'  => get_option( 'awf_ajax_scroll_adjustment', 50 ),
          'desc_tip' => __( 'Use this to tweak (in pixels) the point to which the page scrolls during the animated slide to the top of the products list after an ajax filter application.', 'annasta-filters' ),
          'css'      => 'width: 50px;'
        ),

        330 => array( 
          'id'       => 'awf_products_html_wrapper', 
          'type'     => 'text',
          'name'     => __( 'Products html wrapper', 'annasta-filters' ),
          'default'  => get_option( 'awf_products_html_wrapper', '' ),
          'desc_tip' => __( 'Please leave this blank for the html wrapper auto-detection. If your ajax filters don\'t work, it may mean that your theme\'s template uses a non-standard html structure. In a case like that, you can try to fix it by entering the class (preceded by a dot) or the id (preceded by the "#" sign) of your template-specific html wrapper for the products list html container.', 'annasta-filters' ),
        ),

        340 => array( 
          'id'       => 'awf_force_wrapper_reload', 
          'type'     => 'checkbox',
          'name'     => __( 'Force wrapper reload', 'annasta-filters' ),
          'default'  => get_option( 'awf_force_wrapper_reload', 'no' ),
          'desc_tip' => __( 'Reload the products list header and footer on each AJAX call. This option is incompatible with some themes and options.', 'annasta-filters' )
        ),
				
        399 => array( 'type' => 'sectionend', 'id' => 'awf_product_list_settings_ajax_section' ),
			);
		}

    public function display_product_list_settings_notice() {
      echo
        '<tr><th scope="row" class="titledesc" style="padding: 0;"></th><td style="padding: 0;"></td></tr>',
					
        '<tr>',
        '<th colspan="2" scope="row" class="awf-info-notice-container"><span class="awf-info-notice">',
					
				wp_kses( __( 'Some of the options offered by this section may not be supported by your theme. You are welcome to contact us if you wish us to look into it.', 'annasta-filters' ), array( 'strong' => array() ) ),
			
				'</span></th>',
        '</tr>'
      ;
    }

    protected function get_product_list_template_option_hooks( $option ) {
      $hooks = array(
        'woocommerce_before_shop_loop' => 'woocommerce_before_shop_loop',
        'woocommerce_after_shop_loop' => 'woocommerce_after_shop_loop',
      );

      if( ! in_array( $option, array( 'pagination', 'result_count', 'orderby' ) ) ) {
        $hooks = array(
          'woocommerce_before_main_content' => 'woocommerce_before_main_content',
          'woocommerce_archive_description' => 'woocommerce_archive_description',
        ) + $hooks;
        $hooks += array(
          'woocommerce_after_main_content' => 'woocommerce_after_main_content',
          'woocommerce_no_products_found' => 'woocommerce_no_products_found',
        );
      }

      return apply_filters( 'awf_product_list_template_option_hooks', $hooks, $option );
    }

    protected function get_product_list_template_options() {
      $options = array(
        'shop_title' => __( 'Shop title', 'annasta-filters' ),
        'orderby' => __( 'Sort by control', 'annasta-filters' ),
        'pagination' => __( 'Page numbers', 'annasta-filters' ),
        'result_count' => __( 'Result count message', 'annasta-filters' ),
      );

      if( 'no' === get_option( 'awf_force_wrapper_reload', 'no' ) ) {
        $options = array( 'awf_preset' => __( 'annasta Filters preset', 'annasta-filters' ) ) + $options;
      }

      return $options;
    }

    public function display_product_list_settings_template_options() {

      $default_options = $this->get_product_list_template_options();
      $template_options = get_option( 'awf_product_list_template_options', array() );

      if( ! wp_doing_ajax() ) {
        echo
        '<table class="form-table">',
          '<tr>',
          '<th colspan="2" scope="row" class="awf-info-notice-container"><span class="awf-info-notice">',
            
          wp_kses( __( 'Filters insertion and some other options of this section will work properly only with <strong>Force wrapper reload</strong> option (see below) disabled.', 'annasta-filters' ), array( 'strong' => array() ) ),
        
          '</span></th>',
          '</tr>',
        '</table>'
        ;
      }

      echo
        '<table class="widefat awf-template-options-table">',
        '<thead>'
      ;

      $template_options_select = array( 'id' => 'awf-template-options-select', 'options' => $default_options, 'selected' => null );

      echo
        '<tr>',
        '<th colspan="3">',
          A_W_F::$admin->build_select_html( $template_options_select ),
        '</th>',
        '<th class="awf-buttons-column awf-add-btn-column">',
          '<button type="button" id="awf-add-template-option-btn" class="button button-secondary awf-icon awf-add-btn" title="', esc_attr_e( 'Add to product lists', 'annasta-filters' ), '"></button>',
        '</th>',
        '</tr>',
        '</thead>',
        '<tbody>'
      ;

      foreach( $template_options as $option => $options ) {

        if( ! isset( $default_options[$option] ) ) { continue; }

        foreach( $options as $id => $data ) {
          $setting_id = 'awf_template_option_' . $option . '_' . $id;
          $option_label = '';

          if( 'awf_preset' === $option ) {
            $preset_select = A_W_F::$admin->build_select_html(
              array( 'id' => $setting_id . '_preset_id', 'name' => $setting_id . '_preset', 'options' => $this->get_presets_names(), 'selected' => $data['preset'] )
            );
            $option_label = '<label>' . esc_html( $default_options['awf_preset'] ) . '</label>' . $preset_select;

          } else {
            $option_label = '<label>' . esc_html( $default_options[$option] ) . '</label>';
          }

          $hooks = $this->get_product_list_template_option_hooks( $option );
          $hooks_select = A_W_F::$admin->build_select_html(
            array( 'id' => $setting_id . '_hook', 'class' => 'awf_template_option_active_badges_hook', 'name' => $setting_id . '_hook', 'options' => $hooks, 'selected' => $data['hook'] )
          );

          echo
            '<tr class="awf-template-option-' . str_replace( '_', '-', $option ) . '">',
            '<td class="awf-template-option-name">', $option_label,
            '</td>',
            '<td class="awf-template-option-hook">', $hooks_select, '</td>',
            '<td class="awf-template-option-priority">', 
            '<input name="' . $setting_id . '_priority" type="text" value="' . esc_attr( isset( $data['priority'] ) ? $data['priority'] : '15' ) . '" style="width: 5em;">',
            '</td>',
            '<td class="awf-buttons-column">',
              '<button type="button" class="button button-secondary awf-icon awf-delete-btn awf-delete-template-option-btn" title="',
              esc_attr( 'Remove', 'annasta-filters' ), '" data-option="', esc_attr( $option ), '" data-setting-id="', esc_attr( $id ), '"></button>',
            '</td>',
            '</tr>'
            ;
        }
      }

      echo '</tbody>';

      if( 0 < count( $template_options ) ) {
        echo
          '<tfoot>',
          '<tr>',
            '<th></th>',
            '<th class="awf-template-option-hook-label"><i class="fas fa-angle-up"></i>', esc_html__( 'Hook', 'annasta-filters' ), '</th>',
            '<th><i class="fas fa-angle-up"></i>', esc_html__( 'Priority', 'annasta-filters' ), '</th>',
            '<th></th>',
          '</tr>',
          '</tfoot>'
        ;
      }

      echo '</table>';

    }
		
    public function update_product_list_settings() {
      $template_options = get_option( 'awf_product_list_template_options', array() );

      foreach( $template_options as $option => $options ) {

        foreach( $options as $id => $data ) {
          $setting_id = 'awf_template_option_' . $option . '_' . $id . '_';
          $hooks = $this->get_product_list_template_option_hooks( $option );

          if( isset( $_POST[$setting_id . 'hook'] ) && isset( $hooks[$_POST[$setting_id . 'hook']] ) ) {
            $template_options[$option][$id]['hook'] = $_POST[$setting_id . 'hook'];
          }
          
          if( isset( $_POST[$setting_id . 'priority'] ) ) {
            $template_options[$option][$id]['priority'] = (int) $_POST[$setting_id . 'priority'];
          }
          
          if( isset( $_POST[$setting_id . 'preset'] ) ) {
            $template_options[$option][$id]['preset'] = (int) $_POST[$setting_id . 'preset'];
          }
        }
      }

      update_option( 'awf_product_list_template_options', $template_options );
    }
		
    public static function get_awf_custom_style_options() {
      return array(
        'none'        => __( 'Default', 'annasta-filters' ),
        'deprecated-1-3-0'   => __( 'Deprecated since version 1.3.0', 'annasta-filters' ),
      );
		}
    
    public function get_styles_settings() {

      return array(

        10 => array(
          'id' => 'awf_styles_settings_tab',
          'type' => 'title',
          'name' => __( 'Style Settings', 'annasta-filters' ),
        ),
        
        20 => array( 
          'id'       => 'awf_custom_style',
          'type'     => 'select',
          'options'  => self::get_awf_custom_style_options(),
          'name'     => __( 'Filters style', 'annasta-filters' ),
          'default'  => get_option( 'awf_custom_style', 'none' ),
        ),

        30 => array( 
          'id'       => 'awf_pretty_scrollbars', 
          'type'     => 'checkbox',
          'name'     => __( 'Enable pretty scrollbars', 'annasta-filters' ),
          'default'  => get_option( 'awf_pretty_scrollbars', 'no' ),
          'desc_tip' => __( 'In filters with limited height replace the standard browser scrollbars with minimalistic.', 'annasta-filters' ),
        ),
        
        40 => array( 
          'id'       => 'awf_range_slider_style',
          'type'     => 'select',
          'options'  => A_W_F_admin::get_range_slider_style_options(),
          'name'     => __( 'Range slider style', 'annasta-filters' ),
          'default'  => get_option( 'awf_range_slider_style', 'minimalistic' ),
        ),
        
        50 => array( 
          'id'       => 'awf_color_filter_style',
          'type'     => 'select',
          'options'  => A_W_F_admin::get_color_filter_style_options(),
          'name'     => __( 'Color box style', 'annasta-filters' ),
          'default'  => get_option( 'awf_color_filter_style', 'square' ),
        ),

        70 => array( 
          'id'       => 'awf_fontawesome_font_enqueue',
          'type'     => 'select',
          'options'  => A_W_F_admin::get_fontawesome_font_enqueue_options(),
          'name'     => __( 'Font Awesome support', 'annasta-filters' ),
          'default'  => get_option( 'awf_fontawesome_font_enqueue', 'awf' ),
          'desc_tip' => __( 'Enable Font Awesome support for filters icons. Set to "Disabled" if the full Font Awesome 5 Free support is provided by your theme. The "Extended" option provides basic Font Awesome support for the whole site.', 'annasta-filters' ),
        ),

        array( 'type' => 'sectionend', 'id' => 'awf_styles_settings_tab' ),
      );

    }

    public static function get_range_slider_style_options() {
			return apply_filters( 'awf_range_slider_style_options', array(
				'none'          => __( 'Default', 'annasta-filters' ),
				'minimalistic'  => __( 'Minimalistic Rounded 3D', 'annasta-filters' ),
			) );
		}

    public static function get_color_filter_style_options() {
			return apply_filters( 'awf_color_filter_style_options', array( 'square' => __( 'Square', 'annasta-filters' ) ) );
		}

    public static function get_fontawesome_font_enqueue_options() {
			return array( 'awf' => __( 'Filters only', 'annasta-filters' ), 'yes' => __( 'Extended', 'annasta-filters' ), 'no' => __( 'Disabled', 'annasta-filters' ) );
		}

    public function display_user_css_settings() {
      echo
        '<table class="form-table">',
        '<tbody>',

        '<tr>',
        '<th colspan="2" scope="row" class="awf-info-notice-container"><span class="awf-info-notice">',
					
				sprintf( wp_kses( __( 'For further modification of filters appearance <strong><a href="%1$s">go to the annasta Filters section of Wordpress Customizer</a></strong>.', 'annasta-filters' ), array(  'a' => array( 'href' => array() ), 'strong' => array() ) ), esc_url( admin_url( 'customize.php?autofocus[panel]=annasta-filters' ) ) ),
			
				'</span></th>',
        '</tr>',

        '</tbody>',
        '</table>'
      ;
      echo
        '<table class="form-table">',
        '<tbody>',
				
        '<tr>',
        '<th scope="row" class="titledesc">' , '<label for="awf_user_css">', esc_html__( 'Custom CSS', 'annasta-filters' ), '</label></th>',
        '<td class="forminp forminp-textarea">',
        '<textarea name="awf_user_css" id="awf_user_css" class="awf-code-textarea" placeholder="">',
        stripcslashes( get_option( 'awf_user_css', '' ) ), 
        '</textarea>',
        '</td>',
        '</tr>',

        '</tbody>',
        '</table>'
      ;
    }
    
    public function get_seo_settings() {

      return array(
        array(
          'id' => 'awf_seo_titles_tab',
          'type' => 'title',
          'name' => __( 'SEO Settings', 'annasta-filters' ),
        ),
        
        array( 
          'id'       => 'awf_page_title',
          'type'     => 'select',
          'options'  => array(
            'wc_default'    => __( 'Woocommerce default', 'annasta-filters' ),
            'awf_default'    => __( 'annasta Default title', 'annasta-filters' ),
            'seo'        => __( 'Autogenerated list of annasta filters', 'annasta-filters' ),
          ),
          'name'     => __( 'Page title', 'annasta-filters' ),
          'default'  => get_option( 'awf_page_title', 'wc_default' ),
          'desc_tip' => __( 'Page (HTML document) title can be seen as the name of the page at the top of the browser window (or tab). It is also taken into account by the search engines indexing the pages of your shop.', 'annasta-filters' )
        ),
        
        array( 
          'id'       => 'awf_shop_title',
          'type'     => 'select',
          'options'  => array(
            'wc_default'    => __( 'Woocommerce default', 'annasta-filters' ),
            'awf_default'    => __( 'annasta Default title', 'annasta-filters' ),
            'seo'        => __( 'Autogenerated list of annasta filters', 'annasta-filters' ),
          ),
          'name'     => __( 'Shop title', 'annasta-filters' ),
          'default'  => get_option( 'awf_shop_title', 'wc_default' ),
          'desc_tip' => __( 'The shop page heading above the products list can be left as is, changed with the help of the annasta Default title setting below, or get dynamically adjusted with each filters application, depending on the filters combination.', 'annasta-filters' )
        ),
        
        array( 
          'id'       => 'awf_default_page_title',
          'type'     => 'text',
          'name'     => __( 'Default title', 'annasta-filters' ),
          'default'  => get_option( 'awf_default_page_title', _x( 'Shop', 'Default page title', 'annasta-filters' ) ),
          'desc_tip' => __( 'Choose the "annasta Default title" in the "Page title" or "Shop title" setting to display this string as a title for your shop pages. This title will also be used with the autogenerated filters lists whenever there are no active filters applied to the shop.', 'annasta-filters' ),
        ),
        
        array( 'type' => 'sectionend', 'id' => 'awf_seo_titles_tab' ),
        
        array(
          'id' => 'awf_seo_meta_description_tab',
          'type' => 'title',
          'name' => __( 'Meta Description', 'annasta-filters' ),
        ),

        array( 
          'id'       => 'awf_add_seo_meta_description', 
          'type'     => 'checkbox',
          'name'     => __( 'Add meta description', 'annasta-filters' ),
          'default'  => get_option( 'awf_add_seo_meta_description', 'no' ),
          'desc_tip' => __( 'Add the meta "description" tag to the pages of your shop.', 'annasta-filters' )
        ),
        
        array( 
          'id'       => 'awf_seo_meta_description',
          'type'     => 'textarea',
          'name'     => __( 'Meta description', 'annasta-filters' ),
          'default'  => stripcslashes( trim( get_option( 'awf_seo_meta_description', 'Browse our shop for {annasta_filters}!' ) ) ),
        ),
        
        array( 'type' => 'sectionend', 'id' => 'awf_seo_meta_description_tab' ),
        
        array(
          'id' => 'awf_seo_settings_tab',
          'type' => 'title',
          'name' => __( 'Filters List Generation', 'annasta-filters' ),
        ),
        
        array( 
          'id'       => 'awf_seo_filters_title_prefix',
          'type'     => 'text',
          'name'     => __( 'Filters prefix', 'annasta-filters' ),
          'default'  => get_option( 'awf_seo_filters_title_prefix', 'Shop for' ),
          'desc_tip' => __( 'String to add before the active filters list.', 'annasta-filters' ),
          'css'      => 'width: 200px;'
        ),
        
        array( 
          'id'       => 'awf_seo_filters_separator',
          'type'     => 'text',
          'name'     => __( 'Filters separator', 'annasta-filters' ),
          'default'  => get_option( 'awf_seo_filters_separator', ' - ' ),
          'desc_tip' => __( 'Enter the string that you wish to be used between the different filter groups in the SEO adjusted page and shop title. An example of a title generated using the default value of " - " would be "Fruit, Berries - Red, Green, Purple - Small, Medium".', 'annasta-filters' ),
          'css'      => 'width: 100px;'
        ),
        
        array( 
          'id'       => 'awf_seo_filter_values_separator',
          'type'     => 'text',
          'name'     => __( 'Filter values separator', 'annasta-filters' ),
          'default'  => get_option( 'awf_seo_filter_values_separator', ', ' ),
          'desc_tip' => __( 'Choose a combination of characters to be used between the values of the same filter (for instance, multiple colors selected in a product colors filter). An example of a title created using the default value of ", " would be "Fruit, Berries - Red, Green, Purple - Small, Medium".', 'annasta-filters' ),
          'css'      => 'width: 100px;'
        ),
        
        array( 'type' => 'sectionend', 'id' => 'awf_seo_settings_tab' ),
      );
    }

    public function display_custom_seo_settings() {
      $example_query = (object) array( 'awf' => array(), 'tax' => array() );
      
      $taxonomies = get_object_taxonomies( 'product', 'names' );
      $taxonomies = array_diff( $taxonomies, A_W_F::$excluded_taxonomies );
      
      foreach( $taxonomies as $taxonomy ) {
        $slugs = get_terms( array( 
          'taxonomy' => $taxonomy, 
          'hide_empty' => false,
          'menu_order' => false,
          'orderby' => 'none',
          'fields' => 'slugs',
        ) );

        if( ! is_array( $slugs ) ) { continue; }

        if( 'product_cat' === $taxonomy ) { $slugs = array_diff( $slugs, array( 'uncategorized' ) ); }

        $example_query->tax[$taxonomy] = array_slice( $slugs, 0, rand( 1, 3 ) );
      }
      
      $example_query->awf = array(
        'search' => 'keywords',
        'stock' => 'instock',
        'onsale' => 'yes',
        'featured' => 'yes'
      );
      
      $example_query->range = array(
        'min_price' => '1',
        'max_price' => '50',
        'min_rating' => '3',
        'max_rating' => '5',
      );
      
      ?>
      <table class="widefat awf-seo-page-title-example-table">
        <thead><tr><th><strong><?php esc_html_e( 'Preview for a title with autogenerated filters list', 'annasta-filters' ); ?></strong></th></tr></thead>
        <tbody><tr><td><span><?php echo esc_html( A_W_F::get_seo_title( $example_query ) ); ?></span></td></tr></tbody>
      </table>
      <?php
      
      if( $this instanceof A_W_F_premium_admin ) { $this->display_premium_seo_settings(); }
    }

    public function update_seo_settings() {
            
      $update_filters = false;
      
      if( ! isset( $_POST['awf_default_shop_title'] ) ) {
        update_option( 'awf_default_shop_title', get_option( 'awf_default_page_title', _x( 'Shop', 'Default page title', 'annasta-filters' ) ) );
      } else {
        $update_filters = true;
      }
      
      if( ! isset( $_POST['awf_seo_filters_separator'] ) ) { $_POST['awf_seo_filters_separator'] = ' - '; }
      update_option( 'awf_seo_filters_separator', sanitize_text_field( $this->convert_edge_spaces_to_nbsp( $_POST['awf_seo_filters_separator'] ) ) );
      
      if( ! isset( $_POST['awf_seo_filter_values_separator'] ) ) { $_POST['awf_seo_filter_values_separator'] = ', '; }
      update_option( 'awf_seo_filter_values_separator', sanitize_text_field( $this->convert_edge_spaces_to_nbsp( $_POST['awf_seo_filter_values_separator'] ) ) );
            
      $seo_settings = $this->get_seo_filters_list( $update_filters );
      update_option( 'awf_seo_filters_settings', $seo_settings );
    }
    
    protected function get_seo_filters_list( $update = false ) {
      $seo_settings = array();
      $saved_seo_settings = get_option( 'awf_seo_filters_settings', array() );
      $query_vars = get_option( 'awf_query_vars', array( 'tax' => array(), 'awf' => array(), 'range' => array(), 'meta' => array() ) );
      $position = count( $saved_seo_settings );
      
      foreach( $query_vars['tax'] as $taxonomy => $taxonomy_var_name ) {
        $filter_name = 'taxonomy_' . $taxonomy;
        $this->build_seo_filter_settings( $seo_settings, $saved_seo_settings, $filter_name, $update, $position );
      }
      
      foreach( A_W_F::$modules as $module ) {
        if( in_array( $module, array( 'taxonomy', 'ppp', 'orderby', 'meta' ) ) ) { continue; }
        $this->build_seo_filter_settings( $seo_settings, $saved_seo_settings, $module, $update, $position );
      }
      
      foreach( $query_vars['meta'] as $meta_name => $meta_var_name ) {
        $filter_name = 'meta_filter_' . $meta_name;
        $this->build_seo_filter_settings( $seo_settings, $saved_seo_settings, $filter_name, $update, $position );
        if( ! A_W_F::$premium ) {
          $defaults = $this->get_seo_filter_settings_defaults( $filter_name );
          $seo_settings[$filter_name]['prefix'] = $defaults['prefix'];
        }
      }
      
      uasort( $seo_settings, function( $a, $b ) {
        return $a['position'] - $b['position'];
      });
      
      return apply_filters( 'awf_seo_filters_settings', $seo_settings );
    }
    
    protected function build_seo_filter_settings( &$settings, $saved_settings, $filter_name, $update, &$position ) {
      $settings[$filter_name] = $this->get_seo_filter_settings_defaults( $filter_name );
      
      if( isset( $saved_settings[$filter_name] ) && isset( $saved_settings[$filter_name]['position'] ) ) {
        $settings[$filter_name]['position'] = $saved_settings[$filter_name]['position'];
      } else {
        $settings[$filter_name]['position'] = ++$position;
      }

      if( $update ) {
        if( $this instanceof A_W_F_premium_admin ) { $this->update_seo_filter_settings( $settings[$filter_name], $saved_settings, $filter_name ); }

      } elseif( isset( $saved_settings[$filter_name] ) ) {
        foreach( $settings[$filter_name] as $setting => $value ) {
          if( isset( $saved_settings[$filter_name][$setting] ) ) {
            $settings[$filter_name][$setting] = $saved_settings[$filter_name][$setting];
          }
        }
      }
    }
    
    protected function get_seo_filter_settings_defaults( $module = false ) {
      $filter_defaults = array( 'enabled' => true, 'empty' => '', 'prefix' => '', 'postfix' => '', 'range_separator' => ' - ' );
      
      if( $module ) {
        switch( $module) {
          case 'onsale':
            $filter_defaults['labels'] = array( 'yes' => __( 'on sale', 'annasta-filters' ) );
            break;
          case 'featured':
            $filter_defaults['labels'] = array( 'yes' => __( 'featured', 'annasta-filters' ) );
            break;
          case 'stock':
            $filter_defaults['labels'] = array(
              'instock' => __( 'in stock', 'annasta-filters' ),
              'outofstock' => __( 'out of stock', 'annasta-filters' ),
              'onbackorder' => __( 'on backorder', 'annasta-filters' ),
            );
            break;
          case 'search':
            $filter_defaults['prefix'] = '"';
            $filter_defaults['postfix'] = '"';
            break;
          case 'price':            
            $filter_defaults['prefix'] = __( 'prices ', 'annasta-filters' );
            break;
          case 'rating':
            $filter_defaults['prefix'] = __( 'rating ', 'annasta-filters' );
            $filter_defaults['postfix'] = ' stars';
            break;
          default: break;
        }
      }
      
      if( 0 === strpos( $module, 'meta_filter_' ) ) {
        $meta_name = substr( $module, strlen( 'meta_filter_' ) );

        foreach( A_W_F::$presets as $preset_id => $preset ) {
          foreach( $preset['filters'] as $filter_id => $position ) {
            if( 'meta' === get_option( A_W_F_filter::get_prefix( $preset_id, $filter_id, '' ) . 'module', '' ) ) {
              $filter = new A_W_F_filter( $preset_id, $filter_id );
              if( $filter->settings['meta_name'] === $meta_name && ! empty( $filter->name ) ) {
                $filter_defaults['prefix'] = $filter->settings['title'] . ' ';
                break 2;
              }
            }
          }
        }
      }
      
      return $filter_defaults;
    }

    protected function get_products_max_price() {
      $max_price = 1000000;

      if ( version_compare( WC_VERSION, '3.6', '>=' ) ) {
        global $wpdb;

        $db_row = $wpdb->get_row( "SELECT MAX( max_price ) as max_price FROM {$wpdb->wc_product_meta_lookup}" );

        if( ! empty( $db_row ) && 0 < $db_row->max_price ) {
          if( 1000 < $db_row->max_price ) {
            $max_price = ceil( $db_row->max_price / 100 ) * 100;

          } else {
            $max_price = ceil( $db_row->max_price / 10 ) * 10;
          }
        }
      }
      
      return $max_price;
    }

    protected function get_awf_theme_support_tip() {
      $current_theme = wp_get_theme();
/*print_r( strtolower( $current_theme->__get( 'template' ) ) );*/
      $msg = '';
      
      if( file_exists( A_W_F_PLUGIN_PATH . 'code/themes-support/' . sanitize_title( strtolower( $current_theme->__get( 'template' ) ) ) . '.php' ) ) {
        $msg = sprintf( __( 'Enable built-in support for %1$s theme', 'annasta-filters' ), $current_theme->__get( 'name' ) );
      } else {
        $msg = sprintf( __( 'There are no incompatibility issues registered for the %1$s theme.', 'annasta-filters' ), $current_theme->__get( 'name' ) );
      }
      
      switch( strtolower( $current_theme->__get( 'template' ) ) ) {
        case( 'astra' ):
          if( A_W_F::$premium ) {
            $msg .= '<br><br><span class="awf-theme-support-notice">';
            $msg .= __( 'Please use the Astra theme Customizer (and not the "Product columns" and "Products per page" settings of the current page) to adjust the amount of shop columns and products per page. annasta Filters will use the theme settings.', 'annasta-filters' );
            $msg .= '</span>';
          }
          break;
        case( 'ecommerce-gem' ):
          if( A_W_F::$premium ) {
            $msg .= '<br><br><span class="awf-theme-support-notice">';
            $msg .= __( 'Please use the eCommerce Gem theme Customizer (and not the "Product columns" and "Products per page" settings of the current page) to adjust the amount of shop columns and products per page. annasta Filters will use the theme settings.', 'annasta-filters' );
            $msg .= '</span>';
          }
          break;
        default: break;
      }
      
      return $msg;
    }

    public function get_plugin_settings() {
      $wp_pages_options = array();
      $wp_pages = get_all_page_ids();
      $wc_shop_page = wc_get_page_id( 'shop' );

      foreach( $wp_pages as $page_id ) {
        if( intval( $page_id ) === intval( $wc_shop_page ) ) { continue; }
        $wp_pages_options[$page_id] = get_the_title( $page_id );
      }

      return array(
        10 => array(
          'id' => 'awf_plugin_settings_tab',
          'type' => 'title',
          'name' => __( 'Plugin Settings', 'annasta-filters' ),
        ),

        20 => array( 
          'id'       => 'awf_include_children_on', 
          'type'     => 'checkbox',
          'name'     => __( 'Include subterms\' products', 'annasta-filters' ),
          'default'  => get_option( 'awf_include_children_on', 'yes' ),
          'desc_tip' => __( 'When a parent term of a hierarchical taxonomy is selected, include products belonging to its children terms (for example, subcategories) in the filtered results.', 'annasta-filters' )
        ),

        30 => array( 
          'id'       => 'awf_redirect_archives', 
          'type'     => 'checkbox',
          'name'     => __( 'Redirect archives to shop', 'annasta-filters' ),
          'default'  => get_option( 'awf_redirect_archives', 'no' ),
          'desc_tip' => __( 'Force the redirection of all the archive pages for the products-related taxonomies (such as categories, tags, brands) to the shop page with the corresponding taxonomy filter applied. For example, a product tag page will be redirected from https://mysite.com/tags/tag-1 to https://mysite.com/shop/?product-tags=tag-1). WARNING: this option is not supported by some themes.', 'annasta-filters' )
        ),

        35 => array( 
          'id'       => 'awf_hierarchical_archive_permalinks',
          'type'     => 'checkbox',
          'name'     => __( 'Hierarchical archive links', 'annasta-filters' ),
          'default'  => get_option( 'awf_hierarchical_archive_permalinks', 'no' ),
          'desc_tip' => __( 'Use hierarchical permalinks with paths of type "category/subcatergory/sub-subcategory" in same-taxonomy filters working on archive pages (for example, product categories filter on a category archive page). WARNING: this option only supports single item selection for the same-taxonomy filters of their respective archive pages. On such pages all multi-select filters will be forced into the single-select mode.', 'annasta-filters' )
        ),

        40 => array( 
          'id'       => 'awf_variations_stock_support', 
          'type'     => 'checkbox',
          'name'     => __( 'Stock filter variations support', 'annasta-filters' ),
          'default'  => get_option( 'awf_variations_stock_support', 'no' ),
          'desc_tip' => __( 'Enable stock filter support for variable products. WARNING: this beta option may slow down products display on sites with many products and/or slow servers.', 'annasta-filters' )
        ),

        50 => array( 
          'id'       => 'awf_shortcode_pages', 
          'type'     => 'multiselect',
          'options'     => $wp_pages_options,
          'name'     => __( 'Shortcodes pages', 'annasta-filters' ),
          'default'  => get_option( 'awf_shortcode_pages', array() ),
          'desc_tip' => __( 'Declare the pages at which you wish your filters to work with shortcodes. By default clicking a filter at pages other than shop or category page will redirect the user to the main shop page. Use this in conjunction with registering presets for display on the same pages.', 'annasta-filters' ),
          'class'      => 'chosen_select'
        ),
        
        85 => array(
          'id'       => 'awf_toggle_btn_label',
          'type'     => 'text',
          'name'     => __( 'Filters toggle button label', 'annasta-filters' ),
          'default'  => get_option( 'awf_toggle_btn_label', __( 'Filters', 'annasta-filters' ) ),
          'desc_tip' => __( 'Customize label for the "Filters" toggle button.', 'annasta-filters' ),
        ),
        
        88 => array( 
          'id'       => 'awf_dynamic_price_ranges', 
          'type'     => 'checkbox',
          'name'     => __( 'Dynamic price sliders', 'annasta-filters' ),
          'default'  => get_option( 'awf_dynamic_price_ranges', 'no' ),
          'desc_tip' => __( 'Recalculate price slider ranges for each active filters combination.', 'annasta-filters' )
        ),

        90 => array( 
          'id'       => 'awf_include_parents_in_associations', 
          'type'     => 'checkbox',
          'name'     => __( 'Display parent presets on child pages', 'annasta-filters' ),
          'default'  => get_option( 'awf_include_parents_in_associations', 'yes' ),
          'desc_tip' => __( 'Enable the display of filter presets associated with term\'s parents on child term pages. For example, in a case of Clothes > Jeans category > subcategory structure, enabling this option will make all the presets associated with Clothes category also display for Jeans subcategory.', 'annasta-filters' )
        ),

        100 => array( 
          'id'       => 'awf_force_products_display_on', 
          'type'     => 'checkbox',
          'name'     => __( 'Force products display', 'annasta-filters' ),
          'default'  => get_option( 'awf_force_products_display_on', 'yes' ),
          'desc_tip' => __( 'This option disables categories display on shop pages and subcategories display on product category pages to ensure the accuracy of the filtered results. You can disable it if you are using filters exclusively on shortcode-created pages.', 'annasta-filters' )
        ),
        
				110 => array( 'type' => 'awf_custom_awf_plugin_settings', 'id' => 'awf_custom_awf_plugin_settings' ),

        199 => array( 'type' => 'sectionend', 'id' => 'awf_plugin_settings_tab' ),

      );
    }
    
    public function display_custom_awf_plugin_settings() {
      echo
        '<table class="form-table">',
        '<tbody>',

        '<tr>',
        '<th scope="row" class="titledesc">' , '<label for="awf_user_js">', esc_html__( 'Custom Javascript', 'annasta-filters' ), '</label></th>',
        '<td class="forminp forminp-textarea">',
        '<textarea name="awf_user_js" id="awf_user_js" class="awf-code-textarea" placeholder="">',
        stripcslashes( get_option( 'awf_user_js', '' ) ), 
        '</textarea>',
        '</td>',
        '</tr>',

        '<tr>',
        '<th scope="row" class="titledesc">' ,
        '<label for="awf_counts_cache_days">', esc_html__( 'Product counts cache lifespan', 'annasta-filters' ),
        '<span class="woocommerce-help-tip" data-tip="', esc_html__( 'Amount of days to keep the product counts cache transients in the database. Set to 0 to completely disable any cache produced by filters, including the Woocommerce products loop cache created during AJAX calls.', 'annasta-filters' ), '"></span>',
        '</label></th>',
        '<td class="forminp forminp-number">',
        '<input name="awf_counts_cache_days" id="awf_counts_cache_days" type="number" style="width: 60px;margin-right: 50px;" value="', get_option( 'awf_counts_cache_days', '30' ), '">',
        '<button type="button" id="awf_clear_product_counts_btn" class="button button-secondary"><i class="fas fa-eraser"></i><span>Clear Products Counts Cache</span></button>',
        '</td>',
        '</tr>',

        '</tbody>',
        '</table>'
      ;
    }

    public function generate_styles_css() {

      $languages = array();

      if( class_exists( 'SitePress' ) ) {
        $languages = apply_filters( 'wpml_active_languages', NULL );
        $current_language = apply_filters( 'wpml_current_language', NULL );

        if( is_array( $languages ) ) {
          $languages = array_keys( $languages );
          $languages = array_diff( $languages, array( $current_language ) );

        } else {
          $languages = array();
        }
      }

      $css = '/* annasta Woocommerce Product Filters autogenerated style options css */';
			
      /*
			if( 'yes' === get_option( 'awf_remove_wc_shop_title', 'no' ) ) {
				$css .= '.woocommerce-products-header__title{display:none;}';
			}
      */

			if( 'yes' === get_option( 'awf_remove_wc_orderby', 'no' ) ) {
				$css .= '.woocommerce-ordering{display:none;}';
			}
      
      if( $this instanceof A_W_F_premium_admin ) { $css .= $this->generate_premium_css(); }

      foreach( A_W_F::$presets as $preset_id => $preset ) {
        
        $display_mode = get_option( 'awf_preset_' . $preset_id . '_display_mode', 'visible' );
        switch( $display_mode ) {
          case 'visible-on-s':
            $responsive_width = (int) get_option( 'awf_preset_' . $preset_id . '_responsive_width', '768' );
            if( ! empty( $responsive_width ) ) {
              $css .= '@media(min-width:' . $responsive_width . 'px){.awf-preset-wrapper.awf-preset-' . $preset_id . '-wrapper{display:none;}}';
            }
            
            break;
          case 'visible-on-l':
            $responsive_width = (int) get_option( 'awf_preset_' . $preset_id . '_responsive_width', '768' );
            if( ! empty( $responsive_width ) ) {
              $css .= '@media(max-width:' . $responsive_width . 'px){.awf-preset-wrapper.awf-preset-' . $preset_id . '-wrapper{display:none;}}';
            }

            break;
          case 'togglable':
            $css .= '.awf-preset-wrapper.awf-preset-' . $preset_id . '-wrapper{opacity:0;}';

            break;
          case 'togglable-on-s':
            $responsive_width = (int) get_option( 'awf_preset_' . $preset_id . '_responsive_width', '768' );
            $css .= '@media(max-width:' . $responsive_width . 'px){.awf-preset-wrapper.awf-preset-' . $preset_id . '-wrapper{opacity:0;}}';

            break;
          default: break;
        }
        
        foreach( $preset['filters'] as $filter_id => $position ) {

          $filter = new A_W_F_filter( $preset_id, $filter_id );

          if( ! empty( $filter->settings['height_limit'] ) ) {
            $css .= '.awf-filters-' . $preset_id . '-' . $filter_id . '-container {position:relative;';
            $css .= 'height:' . $filter->settings['height_limit'] . 'px;overflow:hidden;';
            if ( 'yes' !== get_option( 'awf_pretty_scrollbars' ) ) { $css .= 'padding-right:0.5em;overflow-y:auto;'; }
            $css .= '}';
          }

          if( 'icons' === $filter->settings['style'] && isset( $filter->settings['style_options']['icons'] ) ) {

            $css .= '.awf-filters-' . $preset_id . '-' . $filter_id . '-container.awf-style-icons label::before {';
            $css .= 'content:"' . $filter->settings['style_options']['icons'][0] . '";';
            if( ! empty( $filter->settings['style_options']['solid'][0] ) ) {
              $css .= 'font-weight:900;';
            } else { $css .= 'font-weight:inherit;'; }
            $css .= '}';

            $css .= '.awf-filters-' . $preset_id . '-' . $filter_id . '-container.awf-style-icons .awf-filter-container:not(.awf-hover-off) label:hover::before {';
            $css .= 'content:"' . $filter->settings['style_options']['icons'][1] . '";';
            if( ! empty( $filter->settings['style_options']['solid'][1] ) ) {
              $css .= 'font-weight:900;';
            } else { $css .= 'font-weight:inherit;'; }
            $css .= '}';

            $css .= '.awf-filters-' . $preset_id . '-' . $filter_id . '-container.awf-style-icons .awf-filter-container.awf-empty-disabled.awf-empty label:hover::before {';
            $css .= 'content:"' . $filter->settings['style_options']['icons'][0] . '";';
            if( ! empty( $filter->settings['style_options']['solid'][0] ) ) {
              $css .= 'font-weight:900;';
            } else { $css .= 'font-weight:inherit;'; }
            $css .= '}';

            $css .= '.awf-filters-' . $preset_id . '-' . $filter_id . '-container.awf-style-icons .awf-active label::before {';
            $css .= 'content:"' . $filter->settings['style_options']['icons'][2] . '";';
            if( ! empty( $filter->settings['style_options']['solid'][2] ) ) {
              $css .= 'font-weight:900;';
            } else { $css .= 'font-weight:inherit;'; }
            $css .= '}';

            $css .= '.awf-filters-' . $preset_id . '-' . $filter_id . '-container.awf-style-icons .awf-hierarchical-sbs-active-parent label::before {';
            $css .= 'content:"' . $filter->settings['style_options']['icons'][2] . '";';
            if( ! empty( $filter->settings['style_options']['solid'][2] ) ) {
              $css .= 'font-weight:900;';
            } else { $css .= 'font-weight:inherit;'; }
            $css .= '}';

            $css .= '.awf-filters-' . $preset_id . '-' . $filter_id . '-container.awf-style-icons .awf-active:not(.awf-hover-off) label:hover::before {';
            $css .= 'content:"' . $filter->settings['style_options']['icons'][3] . '";';
            if( ! empty( $filter->settings['style_options']['solid'][3] ) ) {
              $css .= 'font-weight:900;';
            } else { $css .= 'font-weight:inherit;'; }
            $css .= '}';

            $css .= '.awf-filters-' . $preset_id . '-' . $filter_id . '-container.awf-style-icons .awf-hierarchical-sbs-active-parent:not(.awf-hover-off) label:hover::before {';
            $css .= 'content:"' . $filter->settings['style_options']['icons'][3] . '";';
            if( ! empty( $filter->settings['style_options']['solid'][3] ) ) {
              $css .= 'font-weight:900;';
            } else { $css .= 'font-weight:inherit;'; }
            $css .= '}';

            $css .= '.awf-filters-' . $preset_id . '-' . $filter_id . '-container.awf-style-icons .awf-active.awf-empty-disabled.awf-empty label:hover::before {';
            $css .= 'content:"' . $filter->settings['style_options']['icons'][2] . '";';
            if( ! empty( $filter->settings['style_options']['solid'][2] ) ) {
              $css .= 'font-weight:900;';
            } else { $css .= 'font-weight:inherit;'; }
            $css .= '}';

            $css .= '.awf-filters-' . $preset_id . '-' . $filter_id . '-container.awf-style-icons .awf-hierarchical-sbs-active-parent.awf-empty-disabled.awf-empty label:hover::before {';
            $css .= 'content:"' . $filter->settings['style_options']['icons'][2] . '";';
            if( ! empty( $filter->settings['style_options']['solid'][2] ) ) {
              $css .= 'font-weight:900;';
            } else { $css .= 'font-weight:inherit;'; }
            $css .= '}';

          } else if( 'colours' === $filter->settings['style'] ) {
            if( isset( $filter->settings['style_options']['colours'] ) ) {
              foreach( $filter->settings['style_options']['colours'] as $term_id => $colour ) {
                $css .= '.awf-filters-' . $preset_id . '-' . $filter_id . '-container label.awf-term-' . $term_id . '::after {';
                $css .= 'background-color:' . $colour . ';}';
                if( ! empty( $languages ) && isset( $filter->settings['taxonomy'] ) ) {
                  foreach( $languages as $language ) {
                    $language_term_id = apply_filters( 'wpml_object_id', $term_id, $filter->settings['taxonomy'], TRUE, $language );
                    if( $language_term_id !== $term_id ) {
                      $css .= '.awf-filters-' . $preset_id . '-' . $filter_id . '-container label.awf-term-' . $language_term_id . '::after {';
                      $css .= 'background-color:' . $colour . ';}';
                    }
                  }
                }
              }
            }
          }
          
          if( $this instanceof A_W_F_premium_admin ) { $css .= $this->generate_premium_filter_css( $filter, $languages ); }
        }
      }
			
			$css .= $this->generate_customizer_css();
      
      $user_css = stripcslashes( trim( get_option( 'awf_user_css', '' ) ) );
      
      if(! empty( $user_css ) ) {
        $css .= '/* User CSS */';
        $css .= $user_css;
      }
      
      $awf_uploads_folder = trailingslashit( wp_upload_dir()['basedir'] ) . 'annasta-filters/css';
      if( wp_mkdir_p( $awf_uploads_folder ) ) {
        require_once( ABSPATH . 'wp-admin/includes/file.php' );
        $old_files = list_files( $awf_uploads_folder, 1 );
        if( $old_files ) {
          foreach( $old_files as $file ) {
            unlink( $file );
          }
        }
        
        $filename = 'style-options-' . time() . '.css';
        file_put_contents( trailingslashit( $awf_uploads_folder ) . $filename, $css );
        update_option( 'awf_style_options_file', $filename );
      }
    }
		
    /**
     * Generate custom CSS based on Customizer settings
     *
     * @param boolean|array $options
     * @return void
     */
    protected function generate_customizer_css( $options = false ) {
			
			$css = '';
			$style = ( false !== $options && isset( $options['awf_custom_style'] ) ) ? $options['awf_custom_style'] : get_option( 'awf_custom_style', 'none' );
			
			if( false === $options ) {
				$options = get_option( 'awf_customizer_options', array() );
				
			} else {
				/* Customizer ajax regeneration */
				
				$current_options = get_option( 'awf_customizer_options', array() );
				$default_options = self::get_awf_custom_style_defaults( $style );
				
				foreach( $options as $option => $value ) {
					
					if( 'true' === $value ) { $options[$option] = 'yes'; }
					elseif( 'false' === $value ) { $options[$option] = ''; }
					
					if( '' === $value && isset( $current_options[$option] ) && '' !== $current_options[$option] && isset( $default_options[$option] ) ) {
						$options[$option] = $default_options[$option];
					}
				}

        $options['awf_customizer_ajax_running'] = true;

        /* Prevent from AJAX updates: */
        unset( $options['awf_range_slider_style'] );
			}
			
			$value = isset( $options['awf_range_slider_style'] ) ? $options['awf_range_slider_style'] : get_option( 'awf_range_slider_style', 'minimalistic' );
			
      if( 'minimalistic' === $value ) {
        $css .= '.noUi-horizontal{height: 3px;}.noUi-handle{border-radius: 50%;}.noUi-horizontal .noUi-handle{top:-15px;width: 29px;}.noUi-handle:after,.noUi-handle:before{display:none;}.noUi-pips-horizontal{padding-top:5px;}.noUi-marker-horizontal.noUi-marker-large{height:4px;width: 4px;border-radius: 50%;}.noUi-marker-normal{display:none;}';
      }
			
			if( $this instanceof A_W_F_premium_admin ) { $css .= $this->generate_premium_customizer_css( $options ); }
			
			/* Continue only if the current style supports Customizer */
			if( 'none' !== $style ) { return $css; }
			
			$preset_css = '';
			
			$customizer_default_font = isset( $options['awf_default_font'] ) ? $options['awf_default_font'] : get_option( 'awf_default_font' );
			if( ! empty( $customizer_default_font ) ) {
				$preset_css = 'font-family:' . sanitize_text_field( str_replace( '+', ' ', $customizer_default_font ) ) . ';';
			}
			
			if( ! empty( $options['awf_preset_color'] ) ) {
				$preset_css .= 'color:' . $this->sanitize_css_color( $options['awf_preset_color'] ) . ';';
			}
			
			if( isset( $options['awf_preset_font_size'] ) && '' !== $options['awf_preset_font_size'] ) {
				$preset_css .= 'font-size:' . $this->absint_or_string_maybe_to_px( $options['awf_preset_font_size'] ) . ';';
			}
			
			if( isset( $options['awf_preset_line_height'] ) && '' !== $options['awf_preset_line_height'] ) {
				$preset_css .= 'line-height:' . $this->absint_or_string_maybe_to_px( $options['awf_preset_line_height'] ) . ';';
			}
			
			if( ! empty( $preset_css ) ) { $css .= '.awf-preset-wrapper{' . $preset_css . '}'; }
			
			if( ! empty( $options['awf_filters_button_hover_color'] ) ) {
				$css .= '.awf-togglable-preset-btn:hover{color:' . $this->sanitize_css_color( $options['awf_filters_button_hover_color'] ) . ';}';
			}
				
			if( isset( $options['awf_filters_button_hide_icon'] ) ) {
				switch( $options['awf_filters_button_hide_icon'] ) {
					case 'yes':
						$css .= '.awf-togglable-preset-btn i{display:none;}';
						break;
					case false:
						$css .= '.awf-togglable-preset-btn i{display:inline-block;}';
						break;
					default: break;
				}
			}
			
			if( ! empty( $options['awf_filters_button_background_color'] ) ) {
				$css .= '.awf-togglable-preset-btn{background-color:' . $this->sanitize_css_color( $options['awf_filters_button_background_color'] ) . ';}';
			}
			
			if( ! empty( $options['awf_filters_button_hover_background_color'] ) ) {
				$css .= '.awf-togglable-preset-btn:hover{background-color:' . $this->sanitize_css_color( $options['awf_filters_button_hover_background_color'] ) . ';}';
			}
			
			if( ! empty( $options['awf_active_badge_reset_icon_position'] ) ) {
				$css .= '.awf-active-badge{flex-direction:' . sanitize_key( $options['awf_active_badge_reset_icon_position'] ) . ';}';
			}
			
			if( ! empty( $options['awf_active_badge_justify_content'] ) ) {
				$css .= '.awf-active-badge{justify-content:' . sanitize_key( $options['awf_active_badge_justify_content'] ) . ';}';
			}
			
			if( ! empty( $options['awf_active_badge_hover_color'] ) ) {
				$css .= '.awf-active-badge:hover{color:' . $this->sanitize_css_color( $options['awf_active_badge_hover_color'] ) . ';}';
			}
			
			if( isset( $options['awf_active_badge_font_size'] ) && '' !== $options['awf_active_badge_font_size'] ) {
				$css .= '.awf-active-badges-container{font-size:' . $this->absint_or_string_maybe_to_px( $options['awf_active_badge_font_size'] ) . ';}';
			}
			
			if( ! empty( $options['awf_reset_btn_hover_color'] ) ) {
				$css .= 'button.awf-reset-btn:hover{color:' . $this->sanitize_css_color( $options['awf_reset_btn_hover_color'] ) . ';}';
			}
			
			if( isset( $options['awf_reset_btn_width'] ) && '' !== $options['awf_reset_btn_width'] ) {
				$css .= 'button.awf-reset-btn{width:' . $this->absint_or_string_maybe_to_percent( $options['awf_reset_btn_width'] ) . ';}';
			}
			
			if( ! empty( $options['awf_reset_btn_background_color'] ) ) {
				$css .= 'button.awf-reset-btn{background-color:' . $this->sanitize_css_color( $options['awf_reset_btn_background_color'] ) . ';}';
			}
			
			if( ! empty( $options['awf_reset_btn_hover_background_color'] ) ) {
				$css .= 'button.awf-reset-btn:hover{background-color:' . $this->sanitize_css_color( $options['awf_reset_btn_hover_background_color'] ) . ';}';
			}
			
			if( ! empty( $options['awf_filter_title_collapse_btn_icon'] ) ) {
				$css .= '.awf-filter-wrapper:not(.awf-dropdown) .awf-collapse-btn::before{content:"\\' . sanitize_text_field( str_replace( '\\', '', $options['awf_filter_title_collapse_btn_icon'] ) ) . '";}';

        if( 'f068' === $options['awf_filter_title_collapse_btn_icon'] ) {
          $css .= '.awf-filter-wrapper:not(.awf-dropdown).awf-collapsed .awf-collapse-btn::before{content:"\\f067";transform: scaleY(-1) rotate(90deg);}';
        } else {
          $css .= '.awf-filter-wrapper:not(.awf-dropdown).awf-collapsed .awf-collapse-btn::before{content:"\\' . sanitize_text_field( str_replace( '\\', '', $options['awf_filter_title_collapse_btn_icon'] ) ) . '";transform:inherit;}';
        }
			}
			
			if( ! empty( $options['awf_dropdown_collapse_btn_icon'] ) ) {
				$css .= '.awf-filter-wrapper.awf-dropdown .awf-collapse-btn::before{content:"\\' . sanitize_text_field( str_replace( '\\', '', $options['awf_dropdown_collapse_btn_icon'] ) ) . '";}';

        if( 'f068' === $options['awf_dropdown_collapse_btn_icon'] ) {
          $css .= '.awf-filter-wrapper.awf-dropdown.awf-collapsed .awf-collapse-btn::before{content:"\\f067";transform: scaleY(-1) rotate(90deg);}';
        } else {
          $css .= '.awf-filter-wrapper.awf-dropdown.awf-collapsed .awf-collapse-btn::before{content:"\\' . sanitize_text_field( str_replace( '\\', '', $options['awf_dropdown_collapse_btn_icon'] ) ) . '";transform:inherit;}';
        }
			}
			
			if( isset( $options['awf_dropdown_height'] ) && '' !== $options['awf_dropdown_height'] ) {
				$height = $this->absint_or_string_maybe_to_px( $options['awf_dropdown_height'] );
				$css .= '.awf-filter-wrapper.awf-dropdown .awf-filter-title-container{height:' . $height . ';max-height:' . $height . ';}';
				$css .= '.awf-filter-wrapper.awf-dropdown .awf-filters-container{top:' . $height . ';}';
			}
			
			if( ! empty( $options['awf_dropdown_filters_container_background_color'] ) ) {
				$css .= '.awf-filter-wrapper.awf-dropdown .awf-filters-container{background-color:' . $this->sanitize_css_color( $options['awf_dropdown_filters_container_background_color'] ) . ';}';
			}
			
			if( ! empty( $options['awf_dropdown_filters_container_border_color'] ) ) {
				$css .= '.awf-filter-wrapper.awf-dropdown .awf-filters-container{border-color:' . $this->sanitize_css_color( $options['awf_dropdown_filters_container_border_color'] ) . ';}';
			}
			
			if( ! empty( $options['awf_dropdown_filters_container_box_shadow_color'] ) ) {
				$css .= '.awf-filter-wrapper.awf-dropdown .awf-filters-container{box-shadow:0px 1px 2px 0px ' . $this->sanitize_css_color( $options['awf_dropdown_filters_container_box_shadow_color'] ) . ';}';
			}
			
			if( ! empty( $options['awf_filter_label_hover_color'] ) ) {
				$css .= '.awf-filter-container label:hover,.awf-filter-container.awf-active label:hover{color:' . $this->sanitize_css_color( $options['awf_filter_label_hover_color'] ) . ';}';
			}
			
			if( ! empty( $options['awf_filter_label_active_color'] ) ) {
				$css .= '.awf-filter-container.awf-active label{color:' . $this->sanitize_css_color( $options['awf_filter_label_active_color'] ) . ';}';
			}
			
			if( ! empty( $options['awf_icons_hover_color'] ) ) {
				$css .= '.awf-style-icons .awf-filter-container:not(.awf-hover-off) label:hover::before,.awf-style-custom-terms .awf-filter-container:not(.awf-hover-off) label:hover::before{color:' . $this->sanitize_css_color( $options['awf_icons_hover_color'] ) . ';}';
			}
			
			$awf_customizer_sections = array(
				'awf_filters_button' => '.awf-togglable-preset-btn',
				'awf_preset_title' => '.awf-preset-title',
				'awf_preset_description' => '.awf-preset-description',
				'awf_active_badge' => '.awf-active-badge',
				'awf_reset_btn' => 'button.awf-reset-btn',
				'awf_filter_title' => '.awf-filter-wrapper:not(.awf-dropdown) .awf-filter-title',
				'awf_dropdown' => '.awf-dropdown .awf-filter-title',
				'awf_filter_label' => '.awf-filter-container label',
				'awf_icons' => '.awf-style-icons label::before, .awf-style-custom-terms label::before'
			);
			
			foreach( $awf_customizer_sections as $section => $selector ) {
				$primary_selector_css = $secondary_selector_css = '';
				
				$current_css = 'primary_selector_css';
				
				if( in_array( $section, array( 'awf_filter_title', 'awf_dropdown', 'awf_filter_label' ) ) ) { $current_css = 'secondary_selector_css'; }
				
				$option = $section . '_margin_top';
				if( isset( $options[$option] ) && '' !== $options[$option] ) { ${$current_css} .= 'margin-top:' . $this->intval_or_string_maybe_to_px( $options[$option] ) . ';'; }
				$option = $section . '_margin_right';
				if( isset( $options[$option] ) && '' !== $options[$option] ) { ${$current_css} .= 'margin-right:' . $this->intval_or_string_maybe_to_px( $options[$option] ) . ';'; }
				$option = $section . '_margin_bottom';
				if( isset( $options[$option] ) && '' !== $options[$option] ) { ${$current_css} .= 'margin-bottom:' . $this->intval_or_string_maybe_to_px( $options[$option] ) . ';'; }
				$option = $section . '_margin_left';
				if( isset( $options[$option] ) && '' !== $options[$option] ) { ${$current_css} .= 'margin-left:' . $this->intval_or_string_maybe_to_px( $options[$option] ) . ';'; }
				
				if( in_array( $section, array( 'awf_filter_title', 'awf_dropdown' ) ) ) { $current_css = 'primary_selector_css'; }
				elseif( in_array( $section, array( 'awf_active_badge' ) ) ) { $current_css = 'secondary_selector_css'; }
				
				$option = $section . '_padding_top';
				if( isset( $options[$option] ) && '' !== $options[$option] ) { ${$current_css} .= 'padding-top:' . $this->absint_or_string_maybe_to_px( $options[$option] ) . ';'; }
				$option = $section . '_padding_right';
				if( isset( $options[$option] ) && '' !== $options[$option] ) { ${$current_css} .= 'padding-right:' . $this->absint_or_string_maybe_to_px( $options[$option] ) . ';'; }
				$option = $section . '_padding_bottom';
				if( isset( $options[$option] ) && '' !== $options[$option] ) { ${$current_css} .= 'padding-bottom:' . $this->absint_or_string_maybe_to_px( $options[$option] ) . ';'; }
				$option = $section . '_padding_left';
				if( isset( $options[$option] ) && '' !== $options[$option] ) { ${$current_css} .= 'padding-left:' . $this->absint_or_string_maybe_to_px( $options[$option] ) . ';'; }
				
				if( in_array( $section, array( 'awf_filter_title', 'awf_dropdown' ) ) ) { $current_css = 'secondary_selector_css'; }
				elseif( in_array( $section, array( 'awf_active_badge' ) ) ) { $current_css = 'primary_selector_css'; }
				
				$option = $section . '_line_height';
				if( isset( $options[$option] ) && '' !== $options[$option] ) { ${$current_css} .= 'line-height:' . $this->absint_or_string_maybe_to_px( $options[$option] ) . ';'; }
				
				if( in_array( $section, array( 'awf_filter_title', 'awf_dropdown', 'awf_filter_label' ) ) ) { $current_css = 'primary_selector_css'; }
				
				if( ! empty( $options[$section . '_text_align'] ) ) { ${$current_css} .= 'text-align:' . sanitize_key( $options[$section . '_text_align'] ) . ';'; }
				if( ! empty( $options[$section . '_color'] ) ) { ${$current_css} .= 'color:' . $this->sanitize_css_color( $options[$section . '_color'] ) . ';'; }
				if( ! empty( $options[$section . '_font_family'] ) ) { ${$current_css} .= 'font-family:' . sanitize_text_field( $options[$section . '_font_family'] ) . ';'; }
				if( isset( $options[$section . '_font_size'] ) && '' !== $options[$section . '_font_size'] ) { ${$current_css} .= 'font-size:' . $this->absint_or_string_maybe_to_px( $options[$section . '_font_size'] ) . ';'; }
				if( ! empty( $options[$section . '_font_weight'] ) ) { ${$current_css} .= 'font-weight:' . $this->absint_or_string( $options[$section . '_font_weight'] ) . ';'; }
				if( ! empty( $options[$section . '_text_transform'] ) ) { ${$current_css} .= 'text-transform:' . sanitize_text_field( $options[$section . '_text_transform'] ) . ';'; }
				
				if( isset( $options[$section . '_font_style_italic'] ) ) {
					switch( $options[$section . '_font_style_italic'] ) {
						case 'yes':
							${$current_css} .= 'font-style:italic;';
							break;
						case false:
							${$current_css} .= 'font-style:normal;';
							break;
						default: break;
					}
				}
				
				if( in_array( $section, array( 'awf_filter_title', 'awf_dropdown', 'awf_filter_label' ) ) ) { $current_css = 'secondary_selector_css'; }
				
				foreach( array( 'top', 'right', 'bottom', 'left' ) as $side ) {
					$option = $section . '_border_' . $side . '_style';
					if( ! empty( $options[$option] ) ) { ${$current_css} .= 'border-' . $side . '-style:' . sanitize_text_field( $options[$option] ) . ';'; }
					
					$option = $section . '_border_' . $side . '_width';
					if( isset( $options[$option] ) && '' !== $options[$option] ) { ${$current_css} .= 'border-' . $side . '-width:' . $this->absint_or_string_maybe_to_px( $options[$option] ) . ';'; }
					
					$option = $section . '_border_' . $side . '_color';
					if( ! empty( $options[$option] ) ) { ${$current_css} .= 'border-' . $side . '-color:' . $this->sanitize_css_color( $options[$option] ) . ';'; }
				}
				
				$option = $section . '_border_radius';
				if( isset( $options[$option] ) && '' !== $options[$option] ) { ${$current_css} .= 'border-radius:' . $this->absint_or_string_maybe_to_px( $options[$option] ) . ';'; }
				
				if( in_array( $section, array( 'awf_filter_title', 'awf_dropdown', 'awf_filter_label' ) ) ) { $current_css = 'primary_selector_css'; }
				
				if( isset( $options[$section . '_white_space_nowrap'] ) ) {
					switch( $options[$section . '_white_space_nowrap'] ) {
						case 'yes':
							${$current_css} .= 'white-space:nowrap;';
							break;
						case false:
							${$current_css} .= 'white-space:normal;';
							break;
						default: break;
					}
				}
				
				if( ! empty( $primary_selector_css ) ) { $css .= $selector . '{' . $primary_selector_css . '}'; }
				
				if( ! empty( $secondary_selector_css ) ) {
					switch( $section ) {
						case 'awf_active_badge':
							$css .= '.awf-active-badge>span{' . $secondary_selector_css . '}';
							break;
						case 'awf_filter_title':
							$css .= '.awf-filter-wrapper:not(.awf-dropdown) .awf-filter-title-container{' . $secondary_selector_css . '}';
							break;
						case 'awf_dropdown':
							$css .= '.awf-dropdown .awf-filter-title-container{' . $secondary_selector_css . '}';
							break;
						case 'awf_filter_label':
							$css .= '.awf-filters-container li.awf-filter-container{' . $secondary_selector_css . '}';
							break;
						default: break;
					}
				}
			}
			
			return $css;
		}
		
		public function absint_or_string_maybe_to_px( $value ) {
			if( is_numeric( $value ) ) {
				return absint( $value ) . 'px';
			}
			
			return sanitize_text_field( $value );
		}
		
		public function absint_or_string_maybe_to_percent( $value ) {
			if( is_numeric( $value ) ) {
				return absint( $value ) . '%';
			}
			
			return sanitize_text_field( $value );
		}
		
		public function absint_or_string( $value ) {
			if( is_numeric( $value ) ) {
				return absint( $value );
			}
			
			return sanitize_text_field( $value );
		}
		
		public function intval_or_string_maybe_to_px( $value ) {
			if( is_numeric( $value ) ) {
				return intval( $value ) . 'px';
			}
			
			return sanitize_text_field( $value );
		}
		
		public function sanitize_css_color( $value ) {
			if( 'inherit' === $value || 'transparent' === $value ) {
				return $value;
			}
			
			return A_W_F_admin::sanitize_hex_rgba_color( $value );
		}

		public static function sanitize_hex_rgba_color( $value ) {
			
			$pattern = '/^#[a-zA-Z0-9]{6}|rgba\((\s*\d+\s*,){3}[\d\.]+\)$/';
			
			preg_match( $pattern, $value, $matches );
			
			if ( isset( $matches[0] ) ) {
				if ( is_string( $matches[0] ) ) {
					return $matches[0];
				}
				if ( is_array( $matches[0] ) && isset( $matches[0][0] ) ) {
					return $matches[0][0];
				}
			}
			
			return '';
		}
		
		public static function get_awf_custom_style_defaults( $style ) {
			
			if( ! in_array( $style, array( 'none' ) ) ) { $style = 'none'; }
			
			$defaults = array(
				'awf_default_font' => 'inherit',
				'awf_preset_color' => 'inherit',
				'awf_preset_font_size' => 'inherit',
				'awf_preset_line_height' => 'inherit',
			);
			
			$customizer_sections = array(
				'awf_filters_button',
				'awf_preset_title',
				'awf_preset_description',
				'awf_active_badge',
				'awf_reset_btn',
				'awf_filter_title',
				'awf_dropdown',
				'awf_filter_label',
				'awf_icons',
			);
			
			foreach( $customizer_sections as $section ) {
				
				if( in_array( $section, array( 'awf_filter_title', 'awf_dropdown' ) ) ) {
					$defaults[$section . '_collapse_btn_icon'] = 'f078';
				}
				
				$defaults[$section . '_margin_top'] = '0';
				$defaults[$section . '_margin_right'] = '0';
				$defaults[$section . '_margin_bottom'] = '0';
				$defaults[$section . '_margin_left'] = '0';
				
				if( 'awf_dropdown' === $section ) {
					$defaults[$section . '_height'] = '38';
				}
				
				$defaults[$section . '_padding_top'] = '0';
				$defaults[$section . '_padding_right'] = '0';
				$defaults[$section . '_padding_bottom'] = '0';
				$defaults[$section . '_padding_left'] = '0';
				
				$defaults[$section . '_line_height'] = 'inherit';
				$defaults[$section . '_text_align'] = 'inherit';
				
				$defaults[$section . '_color'] = 'inherit';
				$defaults[$section . '_font_family'] = 'inherit';
				$defaults[$section . '_font_size'] = 'inherit';
				$defaults[$section . '_font_weight'] = 'inherit';
				$defaults[$section . '_text_transform'] = 'inherit';
				$defaults[$section . '_font_style_italic'] = false;
				
				
				foreach( array( 'top', 'right', 'bottom', 'left' ) as $side ) {
					switch( $section ) {
						case 'awf_filters_button':
							$defaults[$section . '_border_' . $side . '_style'] = 'solid';
							$defaults[$section . '_border_' . $side . '_width'] = '2';
							$defaults[$section . '_border_' . $side . '_color'] = '#888888';
							break;
						case 'awf_dropdown':
							$defaults[$section . '_border_' . $side . '_style'] = 'solid';
							$defaults[$section . '_border_' . $side . '_width'] = '1';
							$defaults[$section . '_border_' . $side . '_color'] = '#d1d1d1';
							break;
						default:
							$defaults[$section . '_border_' . $side . '_style'] = 'none';
							$defaults[$section . '_border_' . $side . '_width'] = 'inherit';
							$defaults[$section . '_border_' . $side . '_color'] = 'inherit';
							break;
					}
					
				}
				
				$defaults[$section . '_border_radius'] = '0';
				
				if( 'awf_preset_description' !== $section ) {
					$defaults[$section . '_white_space_nowrap'] = false;
				}
				
				switch( $section ) {
					case 'awf_filters_button':
						$defaults[$section . '_margin_bottom'] = '20';
						$defaults[$section . '_padding_right'] = '10';
						$defaults[$section . '_padding_left'] = '10';
						$defaults[$section . '_line_height'] = '36';
						$defaults[$section . '_color'] = '#999999';
						$defaults[$section . '_hover_color'] = 'inherit';
						$defaults[$section . '_font_size'] = '14';
						$defaults[$section . '_font_weight'] = '400';
						$defaults[$section . '_background_color'] = 'transparent';
						$defaults[$section . '_hover_background_color'] = '#fbfbfb';
						$defaults[$section . '_border_radius'] = '2';
						$defaults[$section . '_hide_icon'] = false;
						break;
					case 'awf_preset_title':
						$defaults[$section . '_margin_bottom'] = '15';
						$defaults[$section . '_text_align'] = 'left';
						$defaults[$section . '_font_size'] = '1.5em';
						$defaults[$section . '_font_weight'] = '500';
						break;
					case 'awf_preset_description':
						$defaults[$section . '_margin_bottom'] = '15';
						$defaults[$section . '_text_align'] = 'left';
						$defaults[$section . '_font_size'] = '0.8em';
						$defaults[$section . '_font_weight'] = '200';
						break;
					case 'awf_active_badge':
						$defaults[$section . '_hover_color'] = 'inherit';
						$defaults[$section . '_reset_icon_position'] = 'row-reverse';
						$defaults[$section . '_justify_content'] = 'space-between';
						$defaults[$section . '_line_height'] = '1.5em';
						break;
					case 'awf_reset_btn':
						$defaults[$section . '_width'] = 'auto';
						$defaults[$section . '_background_color'] = 'inherit';
						$defaults[$section . '_hover_background_color'] = 'inherit';
						break;
					case 'awf_filter_title':
						$defaults[$section . '_margin_bottom'] = '10';
						$defaults[$section . '_padding_right'] = '20';
						$defaults[$section . '_text_align'] = 'left';
						$defaults[$section . '_font_size'] = '1.2em';
						$defaults[$section . '_font_weight'] = '300';
						break;
					case 'awf_dropdown':
						$defaults[$section . '_padding_right'] = '20';
						$defaults[$section . '_line_height'] = '36';
						$defaults[$section . '_height'] = '38';
						$defaults[$section . '_filters_container_background_color'] = '#ffffff';
						$defaults[$section . '_filters_container_border_color'] = '#cccccc';
						$defaults[$section . '_filters_container_box_shadow_color'] = 'rgba(0, 0, 0, 0.1)';
						break;
					case 'awf_filter_label':
						$defaults[$section . '_hover_color'] = '#000000';
						$defaults[$section . '_active_color'] = 'inherit';
						break;
					case 'awf_icons':
						$defaults[$section . '_hover_color'] = 'inherit';
						$defaults[$section . '_font_size'] = '0.9em';
            $defaults[$section . '_margin_right'] = '5';
            $defaults[$section . '_margin_left'] = '1';
    
						break;
					default: break;
				}
				
			}
			
			switch( $style ) {
				case 'none':
					break;
					
				default: break;
			}
			
			return $defaults;
		}
		
    /* Clear product counts cache on product updates and/or deletes */ 
    public function on_product_update( $product_id ) {  $this->clear_product_counts_cache(); }

    public function on_product_deletion( $product_id ) {
      if( 'product' === get_post_type( $product_id ) && 'trash' !== get_post_status( $product_id ) ) {
        $this->clear_product_counts_cache();
      }
    }

    public function on_product_trashing( $product_id ) {
      if( 'product' === get_post_type( $product_id ) ) {
        $this->clear_product_counts_cache();
      }
    }

    public function on_product_untrashing() {
      if( 'product' === $GLOBALS['post_type'] ) {
        $this->clear_product_counts_cache();
      }
    }
  
    public function on_product_cat_created( $term_id, $taxonomy_id ) {  $this->clear_product_counts_cache(); }
    public function on_product_cat_deleted( $term_id, $taxonomy_id, $deleted_term, $object_ids ) {  $this->clear_product_counts_cache(); }

    public function clear_product_counts_cache() {

      global $wpdb;

      $transient_name = '_transient_awf_counts_%';

      for( $i = 1; $i <= 5; $i++ ) {
        $sql = "SELECT `option_name` FROM $wpdb->options WHERE `option_name` LIKE '%s' LIMIT 10000";

        $transients = $wpdb->get_results( $wpdb->prepare( $sql, $transient_name ), ARRAY_A );

        if ( $transients && ! is_wp_error( $transients ) && is_array( $transients ) ) {

          foreach ( $transients as $transient ) {
            if ( is_array( $transient ) ) { $transient = current( $transient ); }
              delete_transient( str_replace( '_transient_', '', $transient ) );
          }

        } else {
          break;
        }
      }
    }
    /* endof Clear product counts cache on product updates and/or deletes */
    
    final function __clone() {} // prevent cloning
    final function __wakeup() {} // prevent serialization
    
    public static function get_instance() {
      if( is_null( self::$instance ) ) {
        $called_class = get_called_class();
        self::$instance = new $called_class;
      }
      return self::$instance;
    }
    
    /** Helper Functions */
    
    protected function get_sanitized_checkbox_setting( $filter, $setting, $no = false ) {
      $setting_name = $filter->prefix . $setting;

      if( isset( $_POST[$setting_name] ) ) { return ( 'yes' === $_POST[$setting_name] ); }
      else { return $no; }
    }
    
    protected function get_sanitized_text_field_setting( $setting, $default = '' ) {
      if( isset( $_POST[$setting] ) ) {
        return sanitize_text_field( $_POST[$setting] );
      }
      
      return $default;
    }
    
    protected function get_sanitized_int_setting( $setting, $default = 0 ) {
      if( isset( $_POST[$setting] ) ) { return (int) $_POST[$setting]; }
      
      return (int) $default;
    }
    
    public function display_admin_notice( $msg, $type = 'error', $dismissable = ' is-dismissible' ) {
      /* $type can take the following values: error, warning, info, success */ 
      echo ('<div class="notice notice-' . $type . $dismissable . '"><p>' . esc_html( $msg ) . '</p></div>');
    }
    
    public function build_select_html( $options ) {
      $html = '<select';
      if( isset( $options['id'] ) ) { $html .= ' id="' . esc_attr( $options['id'] ) . '"'; }
      if( isset( $options['name'] ) ) { $html .= ' name="' . esc_attr( $options['name'] ) . '"'; }
      if( isset( $options['class'] ) ) { $html .= ' class="' . sanitize_html_class( $options['class'] ) . '"'; }
      if( isset( $options['custom'] ) ) { $html .= $options['custom']; }
      $html .= '>';

      if( isset( $options['options'] ) ) {
        foreach( $options['options'] as $value => $label ) {
          $html .= '<option value="'. esc_attr( $value ) . '"';
          if( isset( $options['selected'] ) && $value === $options['selected'] ) { $html .= ' selected'; }
          $html .= '>' . esc_html( $label ) . '</option>';
        }
      }

      $html .= '</select>';

      return $html;
    }
    
    protected function convert_edge_spaces_to_nbsp( $string ) {
      $int_one = intval( 1 );
      
      if( 0 === strpos( $string, ' ' ) ) { $string = str_replace( ' ', "\xc2\xa0", $string, $int_one ); }
      if( ' ' === substr( $string, -1, 1 ) ) { $string = substr_replace( $string, "\xc2\xa0", -1, 1 ); }
      
      return $string;
    }

  //A_W_F::format_print_r( '' );
  }
}
?>