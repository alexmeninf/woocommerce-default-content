<?php

defined( 'ABSPATH' ) or die( 'Access denied' );
if ( !class_exists( 'A_W_F' ) ) {
    class A_W_F
    {
        /** Current running instance of A_W_F object
         *
         * @since 1.0.0
         * @var A_W_F object
         */
        private static  $instance ;
        /** Current running instance of annasta admin
         *
         * @since 1.0.0
         * @var mixed
         */
        public static  $admin = null ;
        /** Current running instance of annasta frontend
         *
         * @since 1.0.0
         * @var A_W_F_frontend object
         */
        public static  $front = null ;
        /** Premium version check
         *
         * @since 1.2.5
         * @var boolean
         */
        public static  $premium = false ;
        /** Wc products taxonomy names that aren't allowed to become a filter taxonomy
         *
         * @since 1.0.0
         * @var array
         */
        public static  $excluded_taxonomies = array( 'product_type', 'product_visibility', 'product_shipping_class' ) ;
        /** Master array of presets
         *
         * @since 1.0.0
         * @var array
         */
        public static  $presets = array() ;
        /** An array of ids for all the available modules
         *
         * @since 1.0.0
         * @var array
         */
        public static  $modules = array(
            'taxonomy',
            'search',
            'price',
            'stock',
            'featured',
            'rating',
            'onsale',
            'ppp',
            'orderby',
            'meta'
        ) ;
        /** Callers id count for the current session
         *
         * @since 1.0.0
         * @var int
         */
        public static  $caller_id = 1 ;
        /** Current theme compatibility support class, if exists, or false
         *
         * @since 1.2.6
         * @var theme compatibility support class or boolean false when no support class exists
         */
        public static  $theme_support = false ;
        /** Caching avoidance workaround
         *
         * @since 1.0.0
         * @var string
         */
        public static  $plugin_version ;
        private function __construct()
        {
            self::$plugin_version = A_W_F_VERSION;
            
            if ( !class_exists( 'WooCommerce' ) ) {
                if ( is_admin() ) {
                    add_action( 'load-plugins.php', array( $this, 'add_wc_absence_warning' ) );
                }
                return;
            }
            
            spl_autoload_register( array( $this, 'awf_autoloader' ) );
            add_action( 'init', array( $this, 'load_textdomain' ) );
            add_action( 'widgets_init', array( $this, 'register_widget' ) );
            add_action( 'init', array( $this, 'register_shortcode' ) );
            add_action( 'customize_register', array( $this, 'register_customizer' ) );
            self::$presets = get_option( 'awf_presets', array() );
            if ( !is_array( self::$presets ) ) {
                self::$presets = array();
            }
            
            if ( is_admin() ) {
                
                if ( wp_doing_ajax() && isset( $_REQUEST['awf_front'] ) ) {
                    add_action( 'wp_ajax_awf', array( $this, 'frontend_ajax_controller' ) );
                    add_action( 'wp_ajax_nopriv_awf', array( $this, 'frontend_ajax_controller' ) );
                } else {
                    $this->initialize_admin();
                }
            
            } else {
                $this->initialize_frontend();
            }
            
            
            if ( 'yes' === get_option( 'awf_theme_support', 'yes' ) ) {
                $theme_template = sanitize_title( strtolower( get_template() ) );
                $theme_support_file = A_W_F_PLUGIN_PATH . 'code/themes-support/' . $theme_template . '.php';
                
                if ( file_exists( $theme_support_file ) ) {
                    include_once $theme_support_file;
                    $theme_class_name = 'A_W_F_' . $theme_template . '_theme_support';
                    if ( class_exists( $theme_class_name ) ) {
                        self::$theme_support = new $theme_class_name();
                    }
                }
            
            }
        
        }
        
        private function initialize_admin()
        {
            self::$admin = A_W_F_admin::get_instance();
        }
        
        private function initialize_frontend()
        {
            self::$front = A_W_F_frontend::get_instance();
        }
        
        public static function get_instance()
        {
            if ( is_null( self::$instance ) ) {
                self::$instance = new self();
            }
            return self::$instance;
        }
        
        public static function activate_plugin()
        {
            if ( !current_user_can( 'activate_plugins' ) ) {
                return;
            }
            $plugin = ( isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '' );
            check_admin_referer( "activate-plugin_{$plugin}" );
            
            if ( empty(get_option( 'awf_presets' )) ) {
                self::$presets = array(
                    1 => array(
                    'position'     => 1,
                    'filters'      => array(),
                    'associations' => array(
                    0 => 'all',
                ),
                    'overrides'    => array(),
                ),
                );
                update_option( 'awf_presets', self::$presets );
                update_option( 'awf_preset_1_name', __( 'Default Preset', 'annasta-filters' ) );
                require 'class-a-w-f-admin.php';
                require 'class-a-w-f-filter.php';
                self::$admin = A_W_F_admin::get_instance();
                self::$admin->add_filter( intval( 1 ), 'price' );
                
                if ( false !== get_taxonomy( 'product_cat' ) ) {
                    $pc_filter = self::$admin->add_filter( intval( 1 ), 'taxonomy--product_cat' );
                    $pc_filter->settings['style_options']['icons'] = array(
                        '',
                        '',
                        '',
                        ''
                    );
                    $pc_filter->settings['style_options']['solid'] = array(
                        '',
                        '',
                        'awf-solid',
                        ''
                    );
                    update_option( $pc_filter->prefix . 'settings', $pc_filter->settings );
                }
                
                self::$admin->add_filter( intval( 1 ), 'search' );
            }
        
        }
        
        public static function deactivate_plugin()
        {
            if ( !current_user_can( 'activate_plugins' ) ) {
                return;
            }
            require 'class-a-w-f-admin.php';
            self::$admin = A_W_F_admin::get_instance();
            self::$admin->clear_product_counts_cache();
        }
        
        public static function uninstall_plugin()
        {
            if ( !current_user_can( 'activate_plugins' ) ) {
                return;
            }
            
            if ( empty(self::$admin) ) {
                require 'class-a-w-f-admin.php';
                self::$admin = A_W_F_admin::get_instance();
            }
            
            self::$admin->clear_product_counts_cache();
            unregister_widget( 'A_W_F_widget' );
            $all_options = wp_load_alloptions();
            $awf_options = array(
                'awf_version',
                'awf_premium',
                'awf_query_vars',
                'awf_seo_filters_settings',
                'awf_page_title',
                'awf_shop_title',
                'awf_default_page_title',
                'awf_default_shop_title',
                'awf_add_seo_meta_description',
                'awf_seo_meta_description',
                'awf_seo_filters_title_prefix',
                'awf_seo_filters_title_postfix',
                'awf_seo_filters_separator',
                'awf_seo_filter_values_separator',
                'awf_shortcode_pages',
                'awf_shop_title_badges',
                'awf_remove_wc_orderby',
                'awf_ajax_scroll_on',
                'awf_ajax_scroll_adjustment',
                'awf_force_products_display_on',
                'awf_shop_columns',
                'awf_redirect_search',
                'awf_products_html_wrapper',
                'awf_pretty_scrollbars',
                'awf_color_filter_style',
                'awf_image_filter_style',
                'awf_user_css',
                'awf_ppp_default',
                'awf_include_children_on',
                'awf_cat_views_on',
                'awf_presets',
                'awf_custom_style',
                'awf_range_slider_style',
                'awf_style_options_file',
                'awf_badges_exceptions',
                'awf_reset_all_exceptions',
                'awf_badge_reset_label',
                'awf_redirect_archives',
                'awf_user_js',
                'awf_ajax_pagination',
                'awf_theme_support',
                'awf_daterangepicker_enabled',
                'awf_customizer_options',
                'awf_default_font',
                'awf_default_font_enqueue',
                'awf_fontawesome_font_enqueue',
                'awf_display_wc_shop_title',
                'awf_display_wc_orderby',
                'awf_remove_wc_shop_title',
                'awf_product_list_template_options',
                'awf_force_wrapper_reload',
                'awf_hierarchical_archive_permalinks',
                'awf_variations_stock_support',
                'awf_toggle_btn_label',
                'awf_include_parents_in_associations',
                'awf_ppp_limit',
                'awf_hide_empty_filters',
                'awf_dynamic_price_ranges',
                'awf_counts_cache_days'
            );
            foreach ( $all_options as $name => $value ) {
                if ( 0 !== strpos( $name, 'awf_' ) ) {
                    continue;
                }
                if ( stristr( $name, 'awf_preset_' ) || stristr( $name, 'awf_filter_' ) || substr( $name, -12 ) === '_pretty_name' || in_array( $name, $awf_options ) ) {
                    delete_option( $name );
                }
            }
        }
        
        public function add_wc_absence_warning()
        {
            add_action( 'admin_notices', array( $this, 'display_wc_absence_warning' ) );
        }
        
        public function display_wc_absence_warning()
        {
            echo  '<div class="notice notice-warning is-dismissible"><p>', esc_html__( 'annasta Woocommerce Product Filters requires WooCommerce plugin to function properly. Please install WooCommerce online shop to use the annasta Woocommerce Product Filters plugin.', 'annasta-filters' ), '</p></div>' ;
        }
        
        public function load_textdomain()
        {
            load_plugin_textdomain( 'annasta-filters', false, plugin_basename( dirname( A_W_F_PLUGIN_FILE ) ) . '/languages' );
        }
        
        public function frontend_ajax_controller()
        {
            
            if ( !empty($_GET['awf_action']) ) {
                $this->initialize_frontend();
                self::$front->ajax_controller();
            } else {
                die;
            }
        
        }
        
        public function register_widget()
        {
            register_widget( 'A_W_F_widget' );
        }
        
        public function register_shortcode()
        {
            add_shortcode( 'annasta_filters', array( $this, 'filters_shortcode' ) );
        }
        
        public function filters_shortcode( $atts )
        {
            
            if ( !empty(self::$front) && !empty(self::$presets) ) {
                $caller_id = 'shortcode-' . self::$caller_id++;
                $filtered_atts = shortcode_atts( array(
                    'shortcode_id' => $caller_id,
                    'preset_id'    => '',
                    'preset_name'  => '',
                ), $atts );
                if ( !empty($filtered_atts['preset_name']) ) {
                    foreach ( self::$presets as $id => $data ) {
                        $name = get_option( 'awf_preset_' . $id . '_name', '' );
                        
                        if ( $name === $filtered_atts['preset_name'] ) {
                            $filtered_atts['preset_id'] = $id;
                            break;
                        }
                    
                    }
                }
                $filtered_atts['preset_id'] = (int) $filtered_atts['preset_id'];
                if ( isset( self::$presets[$filtered_atts['preset_id']] ) ) {
                    return self::$front->display_shortcode( $filtered_atts );
                }
            }
            
            return '';
        }
        
        public function register_customizer( $wp_customizer )
        {
            new A_W_F_customizer( $wp_customizer );
        }
        
        /** Get the default font of the current annasta Filters style
         * 
         * @since 1.3.0
         * 
         * Use only the Google fonts from the list returned by the A_W_F_customizer::get_google_fonts_choices()
         * @return (string) Google font name, has to be url-encoded (spaces changed to +)
         */
        public static function get_awf_custom_style_default_font()
        {
            switch ( get_option( 'awf_custom_style', 'none' ) ) {
                default:
                    return 'inherit';
                    break;
            }
        }
        
        public static function build_query_vars()
        {
            $vars = array(
                'tax'   => array(),
                'awf'   => array(
                'orderby'  => 'orderby',
                'search'   => 's-filter',
                'ppp'      => 'ppp',
                'stock'    => 'availability',
                'featured' => 'featured',
                'onsale'   => 'onsale',
            ),
                'meta'  => array(),
                'range' => array(
                'min_price'  => 'price-min',
                'max_price'  => 'price-max',
                'min_rating' => 'rating-min',
                'max_rating' => 'rating-max',
            ),
            );
            $daterangepicker_enabled = false;
            $taxonomies = get_object_taxonomies( 'product', 'objects' );
            foreach ( $taxonomies as $t ) {
                if ( in_array( $t->name, A_W_F::$excluded_taxonomies ) ) {
                    continue;
                }
                
                if ( empty($t->query_var) ) {
                    $taxonomy_var_name = $t->name;
                } else {
                    $taxonomy_var_name = $t->query_var;
                }
                
                if ( !isset( $vars['tax'][$t->name] ) ) {
                    
                    if ( $pretty_name = get_option( 'awf_' . $t->name . '_pretty_name', false ) ) {
                        $vars['tax'][$t->name] = sanitize_key( $pretty_name );
                    } else {
                        $vars['tax'][$t->name] = sanitize_title_with_dashes( $taxonomy_var_name ) . '-filter';
                    }
                
                }
                $vars['range']['min_' . $t->name] = $vars['tax'][$t->name] . '-min';
                $vars['range']['max_' . $t->name] = $vars['tax'][$t->name] . '-max';
            }
            foreach ( $vars['awf'] as $var => $name ) {
                $pretty_name = get_option( 'awf_' . $var . '_awf_module_pretty_name', '' );
                if ( !empty($pretty_name) ) {
                    $vars['awf'][$var] = sanitize_key( $pretty_name );
                }
            }
            foreach ( A_W_F::$presets as $preset_id => $preset ) {
                foreach ( $preset['filters'] as $filter_id => $position ) {
                    
                    if ( 'meta' === get_option( A_W_F_filter::get_prefix( $preset_id, $filter_id, '' ) . 'module', '' ) ) {
                        $filter = new A_W_F_filter( $preset_id, $filter_id );
                        if ( empty($filter->settings['meta_name']) ) {
                            continue;
                        }
                        
                        if ( !isset( $vars['meta'][$filter->settings['meta_name']] ) ) {
                            $pretty_name = get_option( 'awf_' . $filter->settings['meta_name'] . '_awf_meta_pretty_name', '' );
                            
                            if ( !empty($pretty_name) ) {
                                $vars['meta'][$filter->settings['meta_name']] = $pretty_name;
                            } else {
                                $vars['meta'][$filter->settings['meta_name']] = $filter->settings['meta_name'] . '-filter';
                            }
                            
                            $vars['range']['min_' . $filter->settings['meta_name']] = $vars['meta'][$filter->settings['meta_name']] . '-min';
                            $vars['range']['max_' . $filter->settings['meta_name']] = $vars['meta'][$filter->settings['meta_name']] . '-max';
                        }
                        
                        
                        if ( 'date' === $filter->settings['type'] ) {
                            $daterangepicker_enabled = true;
                            $db_date_format = ( isset( $filter->settings['style_options']['db_date_format'] ) ? $filter->settings['style_options']['db_date_format'] : 'c' );
                            $meta_query_var = 'awf_date_filter_' . $db_date_format . '_' . $filter->settings['meta_name'];
                            $vars['meta'][$meta_query_var] = $vars['meta'][$filter->settings['meta_name']];
                            if ( substr( $vars['meta'][$meta_query_var], -strlen( '-filter' ) ) === '-filter' ) {
                                $vars['meta'][$meta_query_var] = substr( $vars['meta'][$meta_query_var], 0, strlen( $vars['meta'][$meta_query_var] ) - strlen( '-filter' ) );
                            }
                            $vars['meta'][$meta_query_var] .= '-' . $db_date_format . '-date-filter';
                        }
                    
                    }
                
                }
            }
            update_option( 'awf_query_vars', $vars );
            update_option( 'awf_daterangepicker_enabled', $daterangepicker_enabled );
        }
        
        public static function get_db_date_formats()
        {
            $db_date_formats = array(
                'a' => array(
                'format' => 'U',
                'label'  => __( 'Unix Time Stamp', 'annasta-filters' ),
            ),
                'b' => array(
                'format' => 'Ymd',
                'label'  => __( 'YYYYMMDD', 'annasta-filters' ),
            ),
                'c' => array(
                'format' => 'Y/m/d',
                'label'  => __( 'YYYY/MM/DD', 'annasta-filters' ),
            ),
                'd' => array(
                'format' => 'Y-m-d',
                'label'  => __( 'YYYY-MM-DD', 'annasta-filters' ),
            ),
                'e' => array(
                'format' => 'Y/m/d H:i:s',
                'label'  => __( 'YYYY/MM/DD HH:MI:SS', 'annasta-filters' ),
            ),
                'f' => array(
                'format' => 'Y-m-d H:i:s',
                'label'  => __( 'YYYY-MM-DD HH:MI:SS', 'annasta-filters' ),
            ),
            );
            return $db_date_formats;
        }
        
        public static function get_seo_meta_description( $query )
        {
            $description = stripcslashes( get_option( 'awf_seo_meta_description', '' ) );
            $parts = self::get_seo_parts( $query );
            $filters = '';
            
            if ( isset( $parts['empty_filters'] ) ) {
                $filters .= implode( ' ', $parts['empty_filters'] ) . ' ';
            } else {
                $parts['empty_filters'] = '';
            }
            
            $filters .= implode( get_option( 'awf_seo_filters_separator', ' - ' ), $parts['filters'] ) . ' ';
            if ( isset( $parts['disabled'] ) ) {
                $filters .= implode( get_option( 'awf_seo_filters_separator', ' - ' ), $parts['disabled'] );
            }
            $filters = str_replace( " ", ' ', $filters );
            $filters = str_replace( '  ', ' ', trim( $filters ) );
            if ( empty(str_replace( $parts['empty_filters'], '', $filters )) ) {
                return self::get_seo_title( $query, 'shop' );
            }
            $description = str_replace( '{annasta_filters}', $filters, $description );
            return $description;
        }
        
        public static function get_seo_title( $query, $type = 'page' )
        {
            $parts = self::get_seo_parts( $query );
            $title = '';
            
            if ( empty($parts['filters']) ) {
                $title = get_option( 'awf_default_' . $type . '_title', _x( 'Shop', 'Default page title', 'annasta-filters' ) );
            } else {
                $title = get_option( 'awf_seo_filters_title_prefix', __( 'Shop for ', 'annasta-filters' ) ) . ' ';
                if ( isset( $parts['empty_filters'] ) ) {
                    $title .= implode( ' ', $parts['empty_filters'] ) . ' ';
                }
                $title .= implode( get_option( 'awf_seo_filters_separator', ' - ' ), $parts['filters'] ) . ' ';
                $title .= get_option( 'awf_seo_filters_title_postfix', '' );
            }
            
            $title = str_replace( " ", ' ', $title );
            $title = str_replace( '  ', ' ', trim( $title ) );
            return $title;
        }
        
        private static function get_seo_parts( $query )
        {
            $get_all = ( 'yes' === get_option( 'awf_add_seo_meta_description', 'no' ) ? true : false );
            $seo_parts = array(
                'filters' => array(),
            );
            if ( !empty(self::$front) ) {
                
                if ( empty(self::$front->seo_parts) ) {
                    self::$front->seo_parts = array(
                        'filters' => array(),
                    );
                    $seo_parts =& self::$front->seo_parts;
                } else {
                    return self::$front->seo_parts;
                }
            
            }
            $filters_settings = get_option( 'awf_seo_filters_settings', array() );
            foreach ( $filters_settings as $filter => $settings ) {
                $part_name = 'filters';
                if ( empty($settings['enabled']) ) {
                    
                    if ( $get_all ) {
                        $part_name = 'disabled';
                    } else {
                        continue;
                    }
                
                }
                
                if ( 0 === strpos( $filter, 'taxonomy_' ) ) {
                    $taxonomy = substr( $filter, strlen( 'taxonomy_' ) );
                    
                    if ( isset( $query->range['min_' . $taxonomy] ) && isset( $query->range['max_' . $taxonomy] ) ) {
                        $seo_parts[$part_name][] = $settings['prefix'] . $query->range['min_' . $taxonomy] . $settings['range_separator'] . $query->range['max_' . $taxonomy] . $settings['postfix'];
                    } elseif ( !empty($query->tax[$taxonomy]) ) {
                        $names = array();
                        foreach ( $query->tax[$taxonomy] as $slug ) {
                            $term = get_term_by( 'slug', $slug, $taxonomy );
                            if ( !empty($term) ) {
                                $names[] = $term->name;
                            }
                        }
                        $seo_parts[$part_name][] = $settings['prefix'] . implode( get_option( 'awf_seo_filter_values_separator', ', ' ), $names ) . $settings['postfix'];
                    } elseif ( !empty($settings['empty']) ) {
                        $seo_parts['empty_filters'][] = $settings['empty'];
                    }
                
                } elseif ( 0 === strpos( $filter, 'meta_filter_' ) ) {
                    $meta_name = substr( $filter, strlen( 'meta_filter_' ) );
                    
                    if ( isset( $query->meta[$meta_name] ) ) {
                        $seo_parts[$part_name][] = $settings['prefix'] . implode( get_option( 'awf_seo_filter_values_separator', ', ' ), $query->meta[$meta_name] ) . $settings['postfix'];
                    } elseif ( isset( $query->range['min_' . $meta_name] ) && isset( $query->range['max_' . $meta_name] ) ) {
                        $seo_parts[$part_name][] = $settings['prefix'] . $query->range['min_' . $meta_name] . $settings['range_separator'] . $query->range['max_' . $meta_name] . $settings['postfix'];
                    } elseif ( !empty($settings['empty']) ) {
                        $seo_parts['empty_filters'][] = $settings['empty'];
                    }
                
                } else {
                    
                    if ( !empty($query->awf[$filter]) ) {
                        
                        if ( 'search' === $filter ) {
                            $seo_parts[$part_name][] = str_replace( array( '\\"', "\\'" ), array( '"', "'" ), $settings['prefix'] . $query->awf[$filter] . $settings['postfix'] );
                        } elseif ( isset( $settings['labels'] ) && isset( $settings['labels'][$query->awf[$filter]] ) ) {
                            $seo_parts[$part_name][] = $settings['prefix'] . $settings['labels'][$query->awf[$filter]] . $settings['postfix'];
                        }
                    
                    } elseif ( isset( $query->range['min_' . $filter] ) && isset( $query->range['max_' . $filter] ) ) {
                        $min = $query->range['min_' . $filter];
                        $max = $query->range['max_' . $filter];
                        
                        if ( 'price' === $filter ) {
                            $min = wp_strip_all_tags( wc_price( $min ) );
                            $max = wp_strip_all_tags( wc_price( $max ) );
                        }
                        
                        $seo_parts[$part_name][] = $settings['prefix'] . $min . $settings['range_separator'] . $max . $settings['postfix'];
                    } else {
                        if ( !empty($settings['empty']) ) {
                            $seo_parts['empty_filters'][] = $settings['empty'];
                        }
                    }
                
                }
            
            }
            return apply_filters( 'awf_seo_filters', $seo_parts );
        }
        
        private function awf_autoloader( $class )
        {
            if ( 0 !== strpos( $class, 'A_W_F_' ) ) {
                return;
            }
            $class = strtolower( $class );
            $path = A_W_F_PLUGIN_PATH . 'code/';
            $file = 'class-' . str_replace( '_', '-', $class ) . '.php';
            $file_path = $path . $file;
            if ( is_readable( $file_path ) ) {
                include $file_path;
            }
        }
        
        public static function enqueue_style_options_css()
        {
            $uploads_dir = wp_upload_dir();
            $path = '/annasta-filters/css/' . get_option( 'awf_style_options_file' );
            
            if ( file_exists( $uploads_dir['basedir'] . $path ) ) {
                $path = $uploads_dir['baseurl'] . $path;
                if ( is_ssl() ) {
                    $path = str_replace( 'http://', 'https://', $path );
                }
                wp_enqueue_style(
                    'awf-style-options',
                    $path,
                    array(),
                    A_W_F::$plugin_version
                );
            }
        
        }
        
        final function __clone()
        {
        }
        
        // prevent cloning
        final function __wakeup()
        {
        }
        
        // prevent serialization
        /** Helper Functions */
        public static function format_print_r( $print )
        {
            print "<pre>";
            print_r( $print );
            print "</pre>";
            //A_W_F::format_print_r( '' );
        }
    
    }
}