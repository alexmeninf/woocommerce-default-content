var a_w_f = { url_params: false };
var awf_expanded_filters = [];

jQuery( document ).ready( function( $ ) {
  'use strict';

  a_w_f.settings_wrapper = $( '#awf-settings' );
  
  a_w_f.get_filter_id = function( el ) {

    var filter = $( el ).closest( '.awf-filter-wrapper' ).attr( 'id' );

    if( 'undefined' === typeof( filter ) ) {
      return '';
    }

    filter = filter.substring( 11 );
    
    var filter_id = filter.split( '-' );
    if( $.isArray( filter_id ) ) {
      filter_id = filter_id.pop();
    } else {
      return '';
    }
    
    return filter_id;
  };
  
  a_w_f.ajax_error_response = function( response ) {
    var message_type = false;
    var message_text = 'An unexpected error occured. Please see console for details.';
    
    if( ( typeof response === 'object' ) ) {
      
      if( response.hasOwnProperty( 'responseJSON' ) && ( typeof response.responseJSON === 'object' ) && response.responseJSON.hasOwnProperty( 'data' ) && ( typeof response.responseJSON.data === 'object' ) ) {
        
        if( response.responseJSON.data.hasOwnProperty( 'awf_error_message' ) ) {
          message_type = 'error';
          message_text = response.responseJSON.data.awf_error_message;
          
        } else if( response.responseJSON.data.hasOwnProperty( 'awf_warning_message' ) ) {
          message_type = 'warning';
          message_text = response.responseJSON.data.awf_warning_message;
        }

      } else if( response.hasOwnProperty( 'responseText' ) ) {

        if( ( typeof response.responseText === 'object' || ( ( typeof response.responseText === 'string' ) && 0 !== response.responseText.length ) ) ) {
          try {
            var response_object = JSON.parse( response.responseText );
            if( ( typeof response_object === 'object' ) && response_object.hasOwnProperty( 'data' ) && ( typeof response_object.data === 'object' ) ) {
              
              if( response_object.data.hasOwnProperty( 'awf_error_message' ) ) {
                message_type = 'error';
                message_text = response_object.data.awf_error_message;

              } else if( response_object.data.hasOwnProperty( 'awf_warning_message' ) ) {
                message_type = 'warning';
                message_text = response_object.data.awf_warning_message;
              }
            }
            
          } catch (error) {
            console.log( response.responseText );
            console.log( error );
          }
          
        }
      }
      
      if( ! message_type && response.hasOwnProperty( 'status' ) && 403 == response.status ) {
        message_type = 'error';
        message_text = 'Error: permission denied. Please reload the page and try again.';
      }
    }
    
    if( message_type ) {
      a_w_f.display_admin_notice( message_text, message_type );
      $( '.awf-spinner-overlay' ).hide();
      $('.blockUI').each( function() { $(this).parent().unblock(); });
      
    } else {
      a_w_f.display_admin_notice( 'An unexpected error occured. Please see console for details.' );
      console.log( response );
    }
                         
  };
  
  a_w_f.display_admin_notice = function( msg, type ) {
    if( 'undefined' === typeof( type ) || 0 === type.length ) { type = 'error'; }
    
    $('<div class="notice notice-' + type + '"><p>' + msg + '</p></div>' ).insertBefore('#awf-settings');
    $( 'html, body' ).animate({
        scrollTop: $('#mainform').offset().top
    }, 1000);
  };
  
  a_w_f.confirm_deletion = function() {
    if ( 'undefined' !== typeof showNotice ) { return showNotice.warn(); }
    else {
      return !! confirm( 'Are you sure you want to proceed with this deletion?' );
    }
    return true;
  };
  
  a_w_f.ajax_html_response_to_jquery = function( response, awf_action ) {
    var $response;
    
    if( undefined === typeof( awf_action ) ) {
      awf_action = 'awf action';
    }

    try {
      $response = $( response );
      
    } catch( error ) {
      $response = $( '' );
      console.log( 'Error retrieving AJAX response in ' + awf_action + ': ' + error );
    }

    return $response;
  };
    
  a_w_f.rebuild_filter_style_options = function( $select ) {
    
    var filter_id = a_w_f.get_filter_id( $select );
    var preset_id = $( '#awf-preset-id' ).val();
    var $style_container = $( '#awf-filter-' + preset_id + '-' + filter_id + '-style-container' );
    var $style_options_cnt = $( '#awf_filter_' + preset_id + '-' + filter_id + '_style_options_container' );
    
    $style_container.block({ message: '' });
    
    $.ajax({
      type:     "post",
      url:      "admin-ajax.php",
      dataType: "html",
      data:     { 
        action: 'awf_admin',
        awf_action: 'rebuild-style-options',
        awf_preset: preset_id,
        awf_filter: filter_id,
        awf_filter_type: $( '#awf_filter_' + preset_id + '-' + filter_id + '_type' ).val(),
        awf_filter_style: $select.val(),
        awf_ajax_referer: awf_js_data.awf_ajax_referer
      },
      success:  function( response ) {
        $style_container.unblock();
        $style_options_cnt.html( response );
        awf_set_style_events( $style_container );
      },
      error: function( response ) { a_w_f.ajax_error_response( response ); }
    });
  };
  
  a_w_f.copy_to_clipboard = function( el ) {
    var $temp_input = $("<input>");
    $( 'body' ).append( $temp_input );
    $temp_input.val( $( el ).text() ).select();
    document.execCommand( 'copy' );
    $temp_input.remove();
    
    var tip = $('<span />').addClass('awf-tip-msg').text( $( el ).parent().data( 'tip' ) );
    tip.appendTo( el ).fadeIn( 100 ).delay( 1000 ).fadeOut( 1000 ).queue(function() { $(this).remove(); });
  };
  
  a_w_f.set_new_url = function() {
    var new_url = window.location.href.toString().replace( window.location.search.toString(), '?' + a_w_f.url_params.toString() );
    window.history.pushState( '', '', new_url );
  };

  a_w_f.hierarchical_sbs_onchange = function( $hierarchical_sbs ) {
    if( true === $hierarchical_sbs.prop( 'checked' ) ) {
      $hierarchical_sbs.closest( '.awf-filter-wrapper' ).addClass( 'awf-hierarchical-sbs-enabled' );
    } else {
      $hierarchical_sbs.closest( '.awf-filter-wrapper' ).removeClass( 'awf-hierarchical-sbs-enabled' );
    }
  };
  
  a_w_f.adjust_active_dropdown_title = function( $select ) {
    var $filter_options_wrapper = $select.closest( '.awf-filter-options' );
    var is_dropdown = 'is_dropdown' === ( $select.hasClass( 'awf-filter-collapse-options-select' ) ? $select.val() : $filter_options_wrapper.find( '.awf-filter-collapse-options-select').val() );
    var is_single_type = 'single' === ( $select.hasClass( 'awf-filter-type-select' ) ? $select.val() : $filter_options_wrapper.find( '.awf-filter-type-select').val() );

    if( is_dropdown && is_single_type ) {
      $select.closest( 'tbody' ).find( '.awf-active-dropdown-title-container' ).show();
    } else {
      $select.closest( 'tbody' ).find( '.awf-active-dropdown-title-container' ).hide();
    }
  };
  
  a_w_f.change_icon_weight = function( $checkbox, input_selector ) {

    if( 'undefined' === typeof( input_selector ) ) { input_selector = '.awf-filter-icon'; }
    var $container = $checkbox.closest( '.awf-filter-style-container' );
    var $input = $checkbox.closest( 'tr' ).find( input_selector );
    
    $input.toggleClass( 'awf-solid' );
    
    if( $input.hasClass( 'awf-unselected-icon' ) ) {
      $container.find( '.awf-unselected-icon-preview > span' ).toggleClass( 'awf-solid' );
    } else if( $input.hasClass( 'awf-selected-icon' ) ) {
      $container.find( '.awf-selected-icon-preview > span' ).toggleClass( 'awf-solid' );
    }
  };

  a_w_f.build_taxonomy_associations_select = function( data ) {
    var $associations_container = $( '.awf-associations-table' );
    $associations_container.block({ message: '' });

    $.ajax({
      type:     "post",
      url:      "admin-ajax.php",
      dataType: "html",
      data:     { 
        action: 'awf_admin',
        awf_action: 'build-taxonomy-associations',
        awf_preset: $( '#awf-preset-id' ).val(),
        awf_request: data,
        awf_ajax_referer: awf_js_data.awf_ajax_referer
      },
      success:  function( response ) {
        $( '#awf-taxonomy-associations-select' ).html( response );
        $associations_container.unblock();
      },
      error: function( response ) { a_w_f.ajax_error_response( response ); }
    });
  };
  
  function awf_set_filter_events( $container ) {
		
    $container.find( '.awf-delete-filter-btn' ).on( 'click', function( event ) {
      event.stopPropagation();
      if( a_w_f.confirm_deletion() ) { awf_delete_filter( $( this ) ); }
    } );
		
    $container.find( '.awf-filter-header .awf-preset-filter-title' ).on( 'click', function() { awf_toggle_filter( $( this ), true ); } );
    $container.find( '.awf-filter-header .awf-buttons-column' ).on( 'click', function() { awf_toggle_filter( $( this ), true ); } );
    $container.find( '.awf-collapse-filter-btn' ).on( 'click', function() { awf_toggle_filter( $( this ), true ); } );
    $container.find( '.awf-is-collapsible' ).on( 'change', function() {
      if( $( this ).is(':checked') ) {
        $( this ).closest( 'tr' ).siblings( '.awf-show-title-container' ).hide();
      } else {
        $( this ).closest( 'tr' ).siblings( '.awf-show-title-container' ).show();
      }
    });
    $container.find( '.awf-filter-collapse-options-select' ).on( 'change', function() {
			var $select = $( this );
			var select_value = $select.val();
			
      if( 'is_collapsible' === select_value || 'is_dropdown' === select_value ) {
        $select.closest( 'tr' ).siblings( '.awf-show-title-container' ).hide();
        
        if( 'is_collapsible' === select_value ) {
          $select.siblings( '.awf-collapsed-on-container' ).show();
        } else { $select.siblings( '.awf-collapsed-on-container' ).hide(); }
        
      } else {
        $select.closest( 'tr' ).siblings( '.awf-show-title-container' ).show();
        $select.siblings( '.awf-collapsed-on-container' ).hide();
      }

      a_w_f.adjust_active_dropdown_title( $select );
    });
    $container.find( '.awf-filter-type-select' ).on( 'change', function() {
      var $select = $( this );
      awf_rebuild_filter_type_and_styles( $select.closest( '.awf-filter-options' ) );
      a_w_f.adjust_active_dropdown_title( $select );
    } );
    $container.find( '.awf-filter-style-select' ).on( 'change', function() { a_w_f.rebuild_filter_style_options( $( this ) ); } );
    $container.find( '.awf-style-options-btn' ).on( 'click', function() { $( this ).parents( '.awf-filter-style-container' ).toggleClass( 'awf-style-options-collapsed' ); });

    var $hierarchical_sbs = $container.find( '.awf-hierarchical-sbs' );
    a_w_f.hierarchical_sbs_onchange( $hierarchical_sbs );
    $hierarchical_sbs.on( 'click', function() { a_w_f.hierarchical_sbs_onchange( $( this ) ); } );
    
    $container.find( '.awf-add-ppp-value-btn' ).on( 'click', function() { awf_add_ppp_value( $( this ) ); });
    $container.find( '.awf-remove-ppp-value-btn' ).on( 'click', function() { awf_remove_ppp_value( $( this ) ); });
    $container.find( '.awf-autocomplete-option' ).on( 'change', function() { $( this ).siblings( '.awf-autocomplete-options-container' ).toggleClass( 'awf-collapsed' ); });
    
    if( 'premium' in a_w_f ) { a_w_f.set_premium_filter_events( $container ); }
  }
  
  function awf_set_style_events( $container ) {
    var $filter_container = $container.closest( '.awf-filter-options' );
    
    var type = $filter_container.find( '.awf-filter-type-select' ).val();
    var style = $container.find( '.awf-filter-style-select' ).val();
    
    var style_options_content = $container.find( '.awf-style-options-container' ).first();
    
    if( style_options_content.length > 0 ) {
      style_options_content = style_options_content.html().trim();
      $container.removeClass( 'awf-style-options-collapsed' );
      $container.removeClass( 'awf-hide-style-options-btn' );
      
    } else {
      $container.addClass( 'awf-style-options-collapsed' );
      $container.addClass( 'awf-hide-style-options-btn' );
    }
    
    if( 'range' === type ) {
      $filter_container.find('.awf-range-type-select').on( 'change', function() {
        awf_rebuild_filter_type_and_styles( $filter_container );
      });
      $filter_container.find('.awf-add-custom-range-value-btn').on( 'click', function() { awf_add_custom_range_value( $( this ) ); });
      $filter_container.find('.awf-delete-custom-range-value-btn').on( 'click', function() { awf_delete_custom_range_value( $( this ) ); });
      $filter_container.closest( '.awf-filter-wrapper' ).addClass( 'awf-range-type-filter' );
      
    } else {
      $filter_container.closest( '.awf-filter-wrapper' ).removeClass( 'awf-range-type-filter' );
    }
    
    if( 'icons' === style ) {
      $container.find( '.awf-solid-icon' ).on( 'change', function() { a_w_f.change_icon_weight( $( this ) ); });
      $container.find( 'input.awf-unselected-icon' ).on( 'change', function() { awf_update_icon_preview( $container, $( this ) ); });
      $container.find( '.awf-unselected-icon-preview' ).hover( 
        function() { awf_icon_preview_hover_in( $( this ), 'unselected' ); }, 
        function() { awf_icon_preview_hover_out( $( this ), 'unselected' ); }
      );
      $container.find( 'input.awf-selected-icon' ).on( 'change', function() { awf_update_icon_preview( $container, $( this ) ); });
      $container.find( '.awf-selected-icon-preview' ).hover( 
        function() { awf_icon_preview_hover_in( $( this ), 'selected' ); }, 
        function() { awf_icon_preview_hover_out( $( this ), 'selected' ); }
      );
      
    } else if( 'colours' === style ) {
      $container.find('.awf-colorpicker').each( function( i, el ) {
        $( el ).wpColorPicker();
      } );
      
    }
    
    if( 'range-slider' === style ) {
      $filter_container.closest( '.awf-filter-wrapper' ).addClass( 'awf-range-slider-filter' );
      awf_build_range_slider_previews();
      
    } else {
      $filter_container.closest( '.awf-filter-wrapper' ).removeClass( 'awf-range-slider-filter' );
    }
    
    if( 'daterangepicker' === style ) {
      $filter_container.closest( '.awf-filter-wrapper' ).addClass( 'awf-daterangepicker-filter' );
      awf_build_range_slider_previews();
      
    } else {
      $filter_container.closest( '.awf-filter-wrapper' ).removeClass( 'awf-daterangepicker-filter' );
    }
    
    awf_refresh_wc_help_tips( $container );

    $( document ).on( 'awf_after_premium_admin_setup', function() { a_w_f.set_premium_style_events( type, style, $container ); } );
  }
    
  function awf_add_template_option() {
    
    $('.awf-spinner-overlay').show();
    
    $.ajax({
      type:     "post",
      url:      "admin-ajax.php",
      dataType: "html",
      data:     { 
        action: 'awf_admin', 
        awf_action: 'add_product_list_template_option', 
        awf_template_option: $( '#awf-template-options-select' ).val(),
        awf_ajax_referer: awf_js_data.awf_ajax_referer, 
      },
      success:  function( response ) {
        $( '.awf-template-options-table' ).replaceWith( response );
        $( document ).trigger( 'awf_product_list_template_options_reload' );
        $('.awf-spinner-overlay').hide();
      },
      error: function( response ) { a_w_f.ajax_error_response( response ); }
    });
  }
    
  function awf_delete_template_option( $delete_btn ) {
    
    $('.awf-spinner-overlay').show();
    
    $.ajax({
      type:     "post",
      url:      "admin-ajax.php",
      dataType: "html",
      data:     { 
        action: 'awf_admin', 
        awf_action: 'delete_product_list_template_option', 
        awf_template_option: $delete_btn.attr( 'data-option' ),
        awf_template_setting_id: $delete_btn.attr( 'data-setting-id' ),
        awf_ajax_referer: awf_js_data.awf_ajax_referer, 
      },
      success:  function( response ) {
        $( '.awf-template-options-table' ).replaceWith( response );
        $('.awf-spinner-overlay').hide();
      },
      error: function( response ) { a_w_f.ajax_error_response( response ); }
    });
  }
  
  function awf_add_association() {
    
    var new_association = $( '#awf-associations-select' ).val();

    if( new_association.startsWith( 'awf-open--' ) ) {
      new_association = $( '#awf-taxonomy-associations-select' ).val();
      if( null === new_association || 0 === new_association.length ) { return; }
    }
    
    $( '.awf-spinner-overlay' ).show();
    
    $.ajax({
      type:     "post",
      url:      "admin-ajax.php",
      dataType: "html",
      data:     { 
        action: 'awf_admin', 
        awf_action: 'add-preset-association', 
        awf_preset: $( '#awf-preset-id' ).val(),
        awf_association: new_association,
        awf_ajax_referer: awf_js_data.awf_ajax_referer, 
      },
      success:  function( response ) {
        $( '.awf-associations-table' ).replaceWith( response );
        $('.awf-spinner-overlay').hide();
      },
      error: function( response ) { a_w_f.ajax_error_response( response ); }
    });    
  }
  
  function awf_delete_association( $delete_btn ) {
    
    $('.awf-spinner-overlay').show();
    var association_id = $delete_btn.attr( 'data-association' );
    
    $.ajax({
      type:     "post",
      url:      "admin-ajax.php",
      data:     { 
        action: 'awf_admin',
        awf_action: 'delete-preset-association',
        awf_ajax_referer: awf_js_data.awf_ajax_referer,
        awf_preset: $( '#awf-preset-id' ).val(),
        awf_association: association_id
      },
      success:  function( response ) {
        $( '.awf-associations-table' ).replaceWith( response );
        $('.awf-spinner-overlay').hide();
      },
      error: function( response ) { a_w_f.ajax_error_response( response ); }
    } );
  }
  
  function awf_update_filters_positions( $sortable ) {
    
    $('.awf-spinner-overlay').show();
    
    var positions = [];
		$( '#awf-settings table.awf-preset-filters-table > tbody .awf-filter-options-container' ).each( function() {
      positions.push( a_w_f.get_filter_id( this ) );
		});
    
    $.ajax({
      type:     "post",
      url:      "admin-ajax.php",
      data:     {
        action: 'awf_admin',
        awf_action: 'update_filters_positions',
        awf_preset: $( '#awf-preset-id' ).val(),
        awf_filters_positions: positions,
        awf_ajax_referer: awf_js_data.awf_ajax_referer
      },
      success:  function( response ) {
        var priority = 1;
        $( '#awf-settings table.awf-preset-filters-table .awf-filter-priority' ).each( function() {
          $( this ).html( priority++ );
        });
        $('.awf-spinner-overlay').hide();
      },
      error: function( response ) {
        $sortable.sortable( 'cancel' );
        a_w_f.ajax_error_response( response );
      }
    });
    
  }
  
  function awf_add_filter() {
    var $filters_wrapper = $( '#awf-settings .awf-preset-filters-table' ).first();
    $filters_wrapper.block({ message: '' });
    
    var new_filter = $('#awf_filters_select').val();
    
    $.ajax({
      type:     "post",
      url:      "admin-ajax.php",
      dataType: "html",
      data:     { 
        action: 'awf_admin',
        awf_action: 'add-filter',
        awf_preset: $( '#awf-preset-id' ).val(),
        awf_filter: new_filter,
        awf_ajax_referer: awf_js_data.awf_ajax_referer
      },
      success:  function( response ) {
        
        if( response ) {
          var $new_filter_row = $( response ).appendTo( '.awf-preset-filters-table > tbody' );
          
          awf_set_filter_events( $new_filter_row );
          awf_set_style_events( $new_filter_row.find( '.awf-filter-style-container' ).first() );
          
          $new_filter_row.find( '.awf-preset-filter-title' ).first().trigger( 'click' );
          $new_filter_row.find( '.tips, .help_tip, .woocommerce-help-tip' ).tipTip({
                attribute: "data-tip",
                fadeIn: 50,
                fadeOut: 50,
                delay: 200
          });
          
          $( [document.documentElement, document.body] ).animate( { scrollTop: $new_filter_row.offset().top - 30 }, 1000);
          
          $('#awf_filters_select option[value="' + new_filter + '"]').remove();
        }
        
        $filters_wrapper.unblock();
      },
      error: function( response ) { a_w_f.ajax_error_response( response ); }
    }); 
  }
  
  function awf_delete_filter( $delete_btn ) {
    var $filters_wrapper = $( '#awf-settings .awf-preset-filters-table' ).first();
    $filters_wrapper.block({ message: '' });
    
    var filter_id = a_w_f.get_filter_id( $delete_btn );
    
    $.ajax({
      type:     "post",
      url:      "admin-ajax.php",
      dataType: "json",
      data:     { 
        action: 'awf_admin',
        awf_action: 'delete-filter',
        awf_preset: $( '#awf-preset-id' ).val(),
        awf_filter: filter_id,
        awf_ajax_referer: awf_js_data.awf_ajax_referer
      },
      success:  function( response ) {
        if( typeof response === 'object' ) {
          $delete_btn.closest( '.awf-filter-wrapper' ).remove();
          
          if( response.hasOwnProperty( 'option_value' ) ) {
            var new_option_atts = { 
              value: response.option_value,
              text: response.option_label,
            };

            $( '#awf_filters_select' ).append( $( '<option>', new_option_atts) );
          }

          var priority = 1;
          $( '#awf-settings table.awf-preset-filters-table .awf-filter-priority' ).each( function() {
            $( this ).html( priority++ );
          });
          
          if( false !== a_w_f.url_params ) {
            var index = awf_expanded_filters.indexOf( filter_id );

            if (index > -1) {
              awf_expanded_filters.splice( index, 1 );

              if( awf_expanded_filters.length === 0 ) {
                a_w_f.url_params.delete( 'awf-expanded-filters' );
              } else {
                a_w_f.url_params.set( 'awf-expanded-filters', awf_expanded_filters.join('-') );
              }

              a_w_f.set_new_url();
            }            
          }
        }
        
        $filters_wrapper.unblock();
      },
      error: function( response ) { a_w_f.ajax_error_response( response ); }
    }); 
  }
  
  function awf_toggle_filter( btn, scroll ) {
    
    var $wrapper = $( btn ).closest( '.awf-filter-wrapper' );
    $wrapper.toggleClass( 'awf-filter-collapsed' );

    var filter_id = a_w_f.get_filter_id( btn );
    var index = awf_expanded_filters.indexOf( filter_id );
    
    if( $wrapper.hasClass( 'awf-filter-collapsed' ) ) {
      if (index > -1) { awf_expanded_filters.splice( index, 1 ); }
    } else {
      if (index === -1) { awf_expanded_filters.push( filter_id ); }
    }
    
    if( false !== a_w_f.url_params ) {
      if( awf_expanded_filters.length === 0 ) {
        a_w_f.url_params.delete( 'awf-expanded-filters' );
      } else {
        a_w_f.url_params.set( 'awf-expanded-filters', awf_expanded_filters.join('-') );
      }

      a_w_f.set_new_url();
    }
    
    var $toggle_btn = $wrapper.find('.awf-filter-toggle-btn');
    var new_title = $toggle_btn.attr( 'data-toggle-title' );
    $toggle_btn.attr( 'data-toggle-title', $toggle_btn.attr( 'title' ) ).attr( 'title', new_title );
    
    if( scroll ) {
      $( [document.documentElement, document.body] ).animate( { scrollTop: $wrapper.offset().top - 30 }, 700);
    }
  }
  
  function awf_update_icon_preview( $container, input ) {
    if( $( input ).hasClass( 'awf-unselected-icon' ) ) {
      $container.find( '.awf-unselected-icon-preview > span' ).text( $( input ).val() );
    } else if( $( input ).hasClass( 'awf-selected-icon' ) ) {
      $container.find( '.awf-selected-icon-preview > span' ).text( $( input ).val() );
    }
  }
  
  function awf_icon_preview_hover_in( $container, type ) {
    
    var icon = $container.find( '.awf-filter-icon' );
    var input = $container.closest( '.awf-style-options-container' ).find( '.awf-' + type + '-icon-hover' );
    
    $( icon ).text( $( input ).val() );
    
    if( $( input ).hasClass( 'awf-solid' ) ) {
      $( icon ).addClass( 'awf-solid' );
    } else {
      $( icon ).removeClass( 'awf-solid' );
    }
  }
  
  function awf_icon_preview_hover_out( $container, type ) {
    
    var icon = $container.find( '.awf-filter-icon' );
    var input = $container.closest( '.awf-style-options-container' ).find( '.awf-' + type + '-icon' );
    
    $( icon ).text( $( input ).val() );
    
    if( $( input ).hasClass( 'awf-solid' ) ) {
      $( icon ).addClass( 'awf-solid' );
    } else {
      $( icon ).removeClass( 'awf-solid' );
    }
  }
  
  function awf_rebuild_filter_type_and_styles( $filter_container ) {

    $filter_container.block({ message: '' });
    
    var preset_id = $( '#awf-preset-id' ).val();
    var filter_id = a_w_f.get_filter_id( $filter_container );
    var filter_type = $filter_container.find( '.awf-filter-type-select' ).first().val();
    var range_type = $filter_container.find( '.awf-range-type-select' ).first().val();
    
    $filter_container.find( '.range-type-container' ).remove();
    
    if( 'range' === filter_type ) {
      
      $.ajax({
        type:     "post",
        url:      "admin-ajax.php",
        dataType: "html",
        data:     { 
          action: 'awf_admin',
          awf_action: 'rebuild-range-type-options',
          awf_preset: preset_id,
          awf_filter: filter_id,
          awf_filter_range_type: range_type,
          awf_ajax_referer: awf_js_data.awf_ajax_referer
        },
        success:  function( response ) {
          $filter_container.find( '.filter-type-container' ).after( $( response ) );
          awf_rebuild_filter_styles( $filter_container, preset_id, filter_id, filter_type, true );
        },
        error: function( response ) { a_w_f.ajax_error_response( response ); }
      }); 
      
    } else {
      awf_rebuild_filter_styles( $filter_container, preset_id, filter_id, filter_type, false );
    }
  }
  
  function awf_rebuild_filter_styles( $filter_container, preset_id, filter_id, filter_type, force_submit ) {
    var $style_container = $filter_container.find( '.awf-filter-style-container' ).first();
    $style_container.html( '' );

    $.ajax({
      type:     "post",
      url:      "admin-ajax.php",
      dataType: "html",
      data:     { 
        action: 'awf_admin',
        awf_action: 'rebuild-styles',
        awf_preset: preset_id,
        awf_filter: filter_id,
        awf_filter_type: filter_type,
        awf_ajax_referer: awf_js_data.awf_ajax_referer
      },
      success:  function( response ) {
        $style_container.html( $( response ) );
        
        if( force_submit ) {
          if( false !== a_w_f.url_params ) {
            a_w_f.url_params.set( 'awf-goto', 'awf-filter-' + preset_id + '-' + filter_id );
            a_w_f.set_new_url();
          }
          
					window.onbeforeunload = null;
          $( '.woocommerce-save-button' ).first().trigger( 'click' );
          
        } else {
          $style_container.find( '.awf-style-options-btn' ).first().on( 'click', function() { $( this ).parents( '.awf-filter-style-container' ).toggleClass( 'awf-style-options-collapsed' ); });
          
          $style_container.find( '.awf-filter-style-select' ).first().on( 'change', function() { a_w_f.rebuild_filter_style_options( $( this ) ); } );
          
          awf_set_style_events( $style_container );
          $filter_container.unblock();
        }
      },
      error: function( response ) { a_w_f.ajax_error_response( response ); }
    }); 
  }
  
  function awf_add_ppp_value( btn ) {
    $('.awf-spinner-overlay').show();
    var $wrapper = $( btn ).closest( '.awf-filter-wrapper' );
    
    $.ajax({
      type:     "post",
      url:      "admin-ajax.php",
      dataType: "html",
      data:     { 
        action: 'awf_admin',
        awf_action: 'add-ppp-value',
        awf_preset: $( '#awf-preset-id' ).val(),
        awf_filter: a_w_f.get_filter_id( btn ),
        awf_add_ppp_value: $wrapper.find( '.awf-add-ppp-value' ).val(),
        awf_add_ppp_label: $wrapper.find( '.awf-add-ppp-label' ).val(),
        awf_ajax_referer: awf_js_data.awf_ajax_referer
      },
      success:  function( response ) {
        var new_ppp_values = $( response );
        new_ppp_values.find( '.awf-remove-ppp-value-btn' ).on( 'click', function() { awf_remove_ppp_value( $( this ) ); });
        $( btn ).closest( 'tr' ).find( '.awf-add-ppp-value' ).val( '' );
        $( btn ).closest( 'tr' ).find( '.awf-add-ppp-label' ).val( '' );
        
        $( btn ).closest( '.awf-ppp-values-table' ).find( 'tbody' ).html( new_ppp_values );
        
        $('.awf-spinner-overlay').hide();
        
      },
      error: function( response ) { a_w_f.ajax_error_response( response ); }
    }); 
  }
  
  function awf_remove_ppp_value( btn ) {
    $('.awf-spinner-overlay').show();

    var filter_id;
    var remove_ppp_value;
    var remove_ppp_value_pieces = $( btn ).closest( '.awf-ppp-value-container' ).attr( 'id' );
    remove_ppp_value_pieces = remove_ppp_value_pieces.split( '_' );
    if( $.isArray( remove_ppp_value_pieces ) ) {
      remove_ppp_value = remove_ppp_value_pieces.pop();
      filter_id = remove_ppp_value_pieces.pop();
    } else {
      return;
    }
    
    $.ajax({
      type:     "post",
      url:      "admin-ajax.php",
      dataType: "html",
      data:     { 
        action: 'awf_admin',
        awf_action: 'remove-ppp-value',
        awf_preset: $( '#awf-preset-id' ).val(),
        awf_filter: filter_id,
        awf_remove_ppp_value: remove_ppp_value,
        awf_ajax_referer: awf_js_data.awf_ajax_referer
      },
      success:  function( response ) {
        var new_ppp_values = $( response );
        new_ppp_values.find( '.awf-remove-ppp-value-btn' ).on( 'click', function() { awf_remove_ppp_value( $( this ) ); });
        $( btn ).closest( '.awf-ppp-values-table' ).find( 'tbody' ).html( new_ppp_values );
        
        $('.awf-spinner-overlay').hide();
        
      },
      error: function( response ) { a_w_f.ajax_error_response( response ); }
    }); 
  }
  
  function awf_add_custom_range_value( $btn ) {
    var $container = $btn.parents( '.awf-filter-options' );
    $container.block({ message: '' });
    
    $.ajax({
      type:     "post",
      url:      "admin-ajax.php",
      dataType: "html",
      data:     { 
        action: 'awf_admin',
        awf_action: 'add-custom-range-value',
        awf_preset: $( '#awf-preset-id' ).val(),
        awf_filter: a_w_f.get_filter_id( $btn ),
        awf_new_range_value: $container.find( '.awf-new-range-value' ).first().val(),
        awf_ajax_referer: awf_js_data.awf_ajax_referer
      },
      success:  function( response ) {
        $container.unblock();
        
        if( response ) {
          $container.find( '.range-type-container' ).replaceWith( $( response ) );
          a_w_f.rebuild_filter_style_options( $container.find( '.awf-filter-style-select' ).first() );
        }
      },
      error: function( response ) { a_w_f.ajax_error_response( response ); }
    }); 
  }
  
  function awf_delete_custom_range_value( $btn ) {
    var $container = $btn.parents('.awf-filter-options');
    $container.block({ message: '' });
    
    $.ajax({
      type:     "post",
      url:      "admin-ajax.php",
      dataType: "html",
      data:     { 
        action: 'awf_admin',
        awf_action: 'delete-custom-range-value',
        awf_preset: $( '#awf-preset-id' ).val(),
        awf_filter: a_w_f.get_filter_id( $btn ),
        awf_delete_range_value: $btn.html(),
        awf_ajax_referer: awf_js_data.awf_ajax_referer
      },
      success:  function( response ) {
        $container.unblock();
        
        $btn.parent().remove();
        a_w_f.rebuild_filter_style_options( $container.find( '.awf-filter-style-select' ).first() );
      },
      error: function( response ) { a_w_f.ajax_error_response( response ); }
    }); 
  }
  
  function awf_build_range_slider_previews() {
    
    var $containers = $( '.awf-range-slider-preview' );
    
    if( 0 === $containers.length ) { return; }
    
    $containers.each( function( i, container ) {
      var $container = $( container );

      if( $container.hasClass( 'noUi-target' ) ) { return true; }
      
      var range_values = $container.attr( 'data-values' ).split( '_+_' );
      $( range_values ).each( function( i, v ) {
        range_values[i] = parseFloat( v );
      });

      var min = range_values[0];
      var max = range_values[range_values.length-1];
      
      var step = parseFloat( $container.attr( 'data-step' ) );
      
      var range_labels = [];
      if( 'undefined' !== typeof $container.attr( 'data-taxonomy-range' ) ) {
        range_labels = $container.attr( 'data-labels' );
        range_labels = range_labels.split( '_+_' );
      }
      
      var number_format = wNumb({
        decimals: $container.attr( 'data-decimals' ),
        mark: $container.attr( 'data-decimals-separator' ),
        thousand: $container.attr( 'data-thousand-separator' ),  
        prefix: $container.attr( 'data-prefix' ), 
        suffix: $container.attr( 'data-postfix' )
      });

      if( number_format.mark === number_format.thousand ) {
        number_format.thousand = '';
      }
      
      var labels_formatter = {
        to: function( value ) {
          if( 'undefined' === typeof $container.attr( 'data-taxonomy-range' ) ) {
            return number_format.to( value );
          } else {
            var i = value - 1;
            
            if( i in range_labels ) {
              return range_labels[i];
            }
            
            return value;
          }
        },
        from: function( value ) {
          if( 'undefined' === typeof $container.attr( 'data-taxonomy-range' ) ) {
            return number_format.from( value );
          } else {
            return value;
          }
        }
      };
      
      var display_tooltips = false;
      if( 'above_handles' === $container.attr( 'data-tooltips' ) ) {
        display_tooltips = [labels_formatter, labels_formatter];
      }
      
      noUiSlider.create( container, {
        range: {
          'min': [min],
          'max': [max]
        },
        start: [min, max],
        step: step,
        margin: step,
        pips: {
          mode: 'values',
          values: range_values,
          density: 5,
          format: {
            to: labels_formatter.to,
            from: labels_formatter.from
          },
        },
        connect: true,
        tooltips: display_tooltips,
        behaviour: 'drag'
      });
      
      if( 'interactive_above' === $container.attr( 'data-tooltips' ) ) {
        $( document ).on( 'awf_after_premium_admin_setup', function() { a_w_f.set_interactive_slider_tooltips( container ); } );
      }

    });
  }
  
  function awf_update_terms_limitation_mode( $select ) {
    var $limitations_container = $select.closest( '.awf-terms-limitations-container' );
    $limitations_container.block({ message: '' });

    $.ajax({
      type:     "post",
      url:      "admin-ajax.php",
      dataType: "html",
      data:     {
        action: 'awf_admin',
        awf_action: 'update-terms-limitation-mode',
        awf_preset: $( '#awf-preset-id' ).val(),
        awf_filter: a_w_f.get_filter_id( $select ),
        awf_terms_limitation_mode: $select.val(),
        awf_ajax_referer: awf_js_data.awf_ajax_referer
      },
      success:  function( response ) {
        var $style_select = $select.closest( '.awf-filter-options-table' ).find( '.awf-filter-style-select' );
        var filter_style = $style_select.val();
        
        if( 'range-slider' === filter_style ) {
          awf_rebuild_filter_type_and_styles( $style_select.closest( '.awf-filter-options' ) );
        }
        
        $select.siblings( '.awf-terms-limitations-table' ).replaceWith( $( response ) );
        $limitations_container.unblock();
      },
      error: function( response ) { a_w_f.ajax_error_response( response ); }
    });
  }
  
  function awf_add_terms_limitation( $btn ) {
    var $limitations_container = $btn.closest( '.awf-terms-limitations-container' );
    $limitations_container.block({ message: '' });
    
    var filter_id = a_w_f.get_filter_id( $btn );
    var preset_id = $( '#awf-preset-id' ).val();
    
    var add_terms_limitation = $( '#awf-terms-limitations-' + preset_id + '-' + filter_id ).val();
    
    $.ajax({
      type:     "post",
      url:      "admin-ajax.php",
      dataType: "html",
      data:     { 
        action: 'awf_admin',
        awf_action: 'add-terms-limitation',
        awf_preset: preset_id,
        awf_filter: filter_id,
        awf_add_terms_limitation: add_terms_limitation,
        awf_ajax_referer: awf_js_data.awf_ajax_referer
      },
      success:  function( response ) {
        var $style_select = $btn.closest( '.awf-filter-options-table' ).find( '.awf-filter-style-select' );
        var filter_style = $style_select.val();
        
        if( 'range-slider' === filter_style ) {
          awf_rebuild_filter_type_and_styles( $style_select.closest( '.awf-filter-options' ) );
        }
        
        $btn.closest( '.awf-terms-limitations-table' ).replaceWith( $( response ) );
        $limitations_container.unblock();
      },
      error: function( response ) { a_w_f.ajax_error_response( response ); }
    });
  }
  
  function awf_remove_terms_limitation( $btn ) {
    var $limitations_container = $btn.closest( '.awf-terms-limitations-container' );
    $limitations_container.block({ message: '' });

    var remove_terms_limitation = $btn.closest( '.awf-terms-limitation-container' ).attr( 'id' );
    remove_terms_limitation = remove_terms_limitation.split( '_' );
    if( $.isArray( remove_terms_limitation ) ) {
      remove_terms_limitation = remove_terms_limitation.pop();
    } else {
      return;
    }
    
    $.ajax({
      type:     "post",
      url:      "admin-ajax.php",
      dataType: "html",
      data:     { 
        action: 'awf_admin',
        awf_action: 'remove-terms-limitation',
        awf_preset: $( '#awf-preset-id' ).val(),
        awf_filter: a_w_f.get_filter_id( $btn ),
        awf_remove_terms_limitation: remove_terms_limitation,
        awf_ajax_referer: awf_js_data.awf_ajax_referer
      },
      success:  function( response ) {
        var $style_select = $btn.closest( '.awf-filter-options-table' ).find( '.awf-filter-style-select' );
        var filter_style = $style_select.val();
        
        if( 'range-slider' === filter_style ) {
          awf_rebuild_filter_type_and_styles( $style_select.closest( '.awf-filter-options' ) );
        }
        
        $btn.closest( '.awf-terms-limitations-table' ).replaceWith( $( response ) );
        $limitations_container.unblock();
      },
      error: function( response ) { a_w_f.ajax_error_response( response ); }
    });
  }
  
  function awf_refresh_wc_help_tips( $container ) {
    $container.find( '.woocommerce-help-tip' ).tipTip({
      attribute: "data-tip",
      fadeIn: 50,
      fadeOut: 50,
      delay: 200
    });
  }
  
  function awf_add_spinner( $container ) {
		$container.toggleClass( 'awf-overlay-container' ).append( '<div class="awf-overlay"><i class="fas fa-cog fa-spin"></i></div>' );
  }
  
  function awf_remove_spinner( $container ) {
		$container.toggleClass( 'awf-overlay-container' ).find( 'div.awf-overlay' ).remove();
  }
  
  if( 0 < a_w_f.settings_wrapper.length ) {
    var $document = $( document );

    if( 'undefined' !== typeof $.blockUI ) {
      $.blockUI.defaults.overlayCSS = { backgroundColor: '#fff', opacity: 0.5 };
      $.blockUI.defaults.css = { border: 'none' };
    }

    if ( false === a_w_f.url_params && ( 'URLSearchParams' in window ) ) {
      a_w_f.url_params = new URLSearchParams( window.location.search );
    }
      
    if( false !== a_w_f.url_params ) {
      
      if( a_w_f.url_params.has( 'tab' ) && 'annasta-filters' === a_w_f.url_params.get( 'tab' ) ) {
        $( '#toplevel_page_annasta-filters, #toplevel_page_annasta-filters > a' ).addClass( 'wp-has-current-submenu' ).removeClass( 'wp-not-current-submenu' );
        
        if( a_w_f.url_params.has( 'section' ) ) {
          if( 'product-list-settings' === a_w_f.url_params.get( 'section' ) ) {
            $( '#toplevel_page_annasta-filters .wp-submenu > li:nth-of-type(3)' ).addClass( 'current' );
            
          } else if( 'styles-settings' === a_w_f.url_params.get( 'section' ) ) {
            $( '#toplevel_page_annasta-filters .wp-submenu > li:nth-of-type(4)' ).addClass( 'current' );
            
          } else if( 'seo-settings' === a_w_f.url_params.get( 'section' ) ) {
            $( '#toplevel_page_annasta-filters .wp-submenu > li:nth-of-type(5)' ).addClass( 'current' );
            
          } else if( 'plugin-settings' === a_w_f.url_params.get( 'section' ) ) {
            $( '#toplevel_page_annasta-filters .wp-submenu > li:nth-of-type(6)' ).addClass( 'current' );
            
          } else if( 0 === a_w_f.url_params.get( 'section' ).length ){
            $( '#toplevel_page_annasta-filters .wp-first-item' ).addClass( 'current' );
          }
          
        } else {
          $( '#toplevel_page_annasta-filters .wp-first-item' ).addClass( 'current' );
        }
      }
      
      if( a_w_f.url_params.has( 'awf-expanded-filters' ) ) {
        awf_expanded_filters = a_w_f.url_params.get( 'awf-expanded-filters' ).split('-');
        a_w_f.url_params.delete( 'awf-expanded-filters' );
        awf_expanded_filters.forEach( function( filter_id ) {
          awf_toggle_filter( $( '#awf-filter-' + $( '#awf-preset-id' ).val() + '-' + filter_id + ' .awf-preset-filter-title' ).first(), false );
        });
      }
      
      if( a_w_f.url_params.has( 'awf-force-submit' ) ) {
        a_w_f.url_params.delete( 'awf-force-submit' );
        a_w_f.set_new_url();
        window.onbeforeunload = null;
        $( '.woocommerce-save-button' ).first().trigger( 'click' );

      } else {
        if( a_w_f.url_params.has( 'awf-goto' ) ) {
          $( [document.documentElement, document.body] ).animate( { scrollTop: $( '#' + a_w_f.url_params.get( 'awf-goto' ) ).offset().top - 25 }, 750, 'swing' );
          a_w_f.url_params.delete( 'awf-goto' );
          a_w_f.set_new_url();
        }
      }
      
    }
    
    $( window ).on( 'load', function() { $( '.select2' ).css( 'width', '100%' ); } );

    if( a_w_f.settings_wrapper.hasClass( 'awf-preset-settings' ) ) {

      a_w_f.settings_wrapper.find( 'table.awf-preset-filters-table > tbody' ).sortable({
        items: '> tr',
        containment: 'parent',
        handle: '.awf-filter-priority',
        placeholder: 'awf-sortable-placeholder',
        tolerance: 'pointer',
        axis: 'y',
        stop: function( event, ui ) {
          if( ! ui.item.hasClass( 'awf-filter-collapsed' ) ) {
            awf_toggle_filter( ui.item.find( '.awf-preset-filter-title' ).first(), true );
            $( this ).sortable( 'cancel' );
          }
        },
        update: function() {
          awf_update_filters_positions( $( this ) );
        }
      });
      
      $( '.awf-presets-table .awf-preset-id-column, .awf-presets-table .awf-preset-name-column, .awf-presets-table .awf-preset-comments-column, .awf-presets-table .awf-associations-column' ).click( function() {
        window.location.href = $( this ).siblings( '.awf-buttons-column' ).find( '.awf-edit-preset-btn' ).attr( 'href' );
      });
      
      if( 0 < $( '.awf-preset-type' ).length ) {
        $( '.awf-sbs-type' ).closest( '.form-table' ).addClass( 'awf-sbs-container' ).hide().prev( 'h2' ).hide();
        if( 'sbs' === $( '.awf-preset-type' ).val() ) {
          $( '.awf-sbs-type' ).closest( '.form-table' ).show().prev( 'h2' ).show();
        }
        
        $( '.awf-preset-type' ).on( 'change', function() {
          if( 'sbs' === $( this ).val() ) {
            $( '.awf-sbs-type' ).closest( '.form-table' ).show().prev( 'h2' ).show();
          } else {
            $( '.awf-sbs-type' ).closest( '.form-table' ).hide().prev( 'h2' ).hide();
          }
        });
      }
      
      if( 0 < $( '.awf-preset-display-mode' ).length ) {
        $( '.awf-preset-togglable-mode' ).closest( 'tr' ).hide();
        
        if( $( '.awf-preset-display-mode' ).val().indexOf( 'togglable' ) > -1 ) {
          $( '.awf-preset-togglable-mode' ).closest( 'tr' ).show();
        }
        
        $( '.awf-preset-display-mode' ).on( 'change', function() {
          if( $( this ).val().indexOf( 'togglable' ) > -1 ) {
            $( '.awf-preset-togglable-mode' ).closest( 'tr' ).show();
          } else {
            $( '.awf-preset-togglable-mode' ).closest( 'tr' ).hide();
          }
        });
      }

      $document.on( 'change', '#awf-associations-select', function() {
        if( $( this ).val().startsWith( 'awf-open--' ) ) {
          a_w_f.build_taxonomy_associations_select( $( this ).val() );
          $( '#awf-taxonomy-associations-select' ).show();
        } else {
          $( '#awf-taxonomy-associations-select' ).hide().html( '' );
        }
      });

      $document.on( 'click', '#awf-add-association-btn', function() { awf_add_association(); } );
      $document.on( 'click', '.awf-delete-association-btn', function() {
        if( a_w_f.confirm_deletion() ) { awf_delete_association( $( this ) ); }
      } );

      $document.on( 'change', '.terms-limitation-mode-select', function() {
        awf_update_terms_limitation_mode( $( this ) );
      } );
      $document.on( 'click', '.awf-add-terms-limitation-btn', function() {
        awf_add_terms_limitation( $( this ) );
      } );
      $document.on( 'click', '.awf-remove-terms-limitation-btn', function() {
        awf_remove_terms_limitation( $( this ) );
      } );  
      
      $( '#awf-add-filter' ).on( 'click', function() { awf_add_filter(); });
      awf_set_filter_events( $( '.awf-preset-filters-table' ) );
      $( '.awf-filter-style-container' ).each( function( i, el ) { awf_set_style_events( $( el ) ); });

      $document.on( 'click', '.awf-icon-example', function() { a_w_f.copy_to_clipboard( $( this ) ); } );

      $document.on( 'click', '.awf-style-options-container .woocommerce-save-button', function() {
        if( false !== a_w_f.url_params ) {
          a_w_f.url_params.set( 'awf-goto', 'awf-filter-' + $( '#awf-preset-id' ).val() + '-' + a_w_f.get_filter_id( this ) );
          a_w_f.set_new_url();
        }
        window.onbeforeunload = null;
      } );
    
    } else if( a_w_f.settings_wrapper.hasClass( 'awf-tab-product-list-settings' ) ) {
      $document.on( 'click', '#awf-add-template-option-btn', function() {
        awf_add_template_option();
      });
    
      $document.on( 'click', '.awf-delete-template-option-btn', function() {
        awf_delete_template_option( $( this ) );
      });

    } else if( a_w_f.settings_wrapper.hasClass( 'awf-tab-seo-settings' ) ) {
      $( '#awf_seo_meta_description' ).after(
        $( '<button type="button" id="awf_add_seo_filters_btn" class="button button-secondary"><i class="fas fa-plus-circle"></i><span>' + awf_js_data.l10n.add_seo_filters_btn_label + '</span></button>' ).on( 'click', function() {
          $( '#awf_seo_meta_description' ).val( $( '#awf_seo_meta_description' ).val() + '{annasta_filters}' );
        } )
      );

    } else if( a_w_f.settings_wrapper.hasClass( 'awf-tab-plugin-settings' ) ) {
      $( '#awf_clear_product_counts_btn' ).on( 'click', function() {
        $('.awf-spinner-overlay').show();
        
        $.ajax({
          type:     "post",
          url:      "admin-ajax.php",
          data:     { 
            action: 'awf_admin',
            awf_action: 'clear_product_counts_cache',
            awf_ajax_referer: awf_js_data.awf_ajax_referer,
          },
          success:  function() { $('.awf-spinner-overlay').hide(); },
          error: function( response ) { a_w_f.ajax_error_response( response ); }
        } );
      } );
    }

  }
    
});