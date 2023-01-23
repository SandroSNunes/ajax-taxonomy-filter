/**
 * Preload filters.
 */
function preloadFilters() {
    jQuery.each(atxfVars.filtersHtml, function (index, value) { 
        jQuery( '[data-atxf-taxonomy=' + index + ']' ).html( value );
    });
}

/**
 * Reset filters.
 */
function atxfReset() {
    jQuery( '.atxf' ).val( '' );
    jQuery( '.atxf:eq(0)' ).trigger( 'change' );
}

( function( $ ) {
	
    var reloadFilters = false;
    var jqxhr = false;
    var filters_json = {};
	var lazyLoadingOffset = 300;
	var nextPage;
	var noMorePages = false;
	var atxfContentHeightTop = getContentHeightTop();

	/**
	 * Gets the ajax content.
	 */
    function getContent( url, clear, popState ) {

		// Cleans the url.
		url = url.replace( '//', '/' );

		if ( clear ) {
			noMorePages = false;
			// $( atxfVars.ajaxContentElement ).html('');
			$( atxfVars.ajaxContentElement ).prepend( '<div id="atxf-modal-loading"></div>' );
		}

		// If there are no more pages then.
		if ( noMorePages ) {
			return false;
		}

        // Trigger get event.
        $(document).trigger( 'atxf-get' );

        // Cancell the previous ajax call.
        if ( jqxhr ) {
            jqxhr.abort();
		}

        // Prepare the filters data to be sent by ajax.
        filters_json = {};
        $( '.atxf' ).each(function (index, element) {
            filters_json[ $(this).data( 'atxf-taxonomy' ) ] = {};
            filters_json[ $(this).data( 'atxf-taxonomy' ) ]['value'] = $(this).val();
            // TODO: Make this come from the initial settings - atxfVars.settings
            filters_json[ $(this).data( 'atxf-taxonomy' ) ]['type'] = $(this).data( 'atxf-type' );
        });

        // Changes the url on the browser.
        if ( history.pushState && popState === undefined && ( ! atxfVars.lazyLoading || clear ) ) {
            history.pushState({
                'url': url,
                'atxf_query_vars': atxfVars.queryVars,
                'filters_json': filters_json
            }, null, url);
		}

        if ( popState !== undefined && ( ! atxfVars.lazyLoading || clear ) ) {
            atxfVars.queryVars = popState.atxf_query_vars;
            filters_json = popState.filters_json;
        }

        // Make the AJAX call to change the content.
        jqxhr = $.ajax({
            type: "POST",
            url: url,
            dataType: 'text',
            data: {
                'action': 'atxf_ajax_get_content',
                'ajaxnonce': atxfVars.ajaxNonce,
                'atxf_query_vars': atxfVars.queryVars,
                'filters': filters_json
            }
        }).done(function (out) {

			// Parse the html content.
            var pos = out.indexOf( '{"html' );
            if ( pos > -1 ) {
                var json_object = $.parseJSON(out.substr(pos));
            } else {
                var json_object = $.parseJSON(out);
            }
            atxfVars.queryVars = json_object.atxf_query_vars;

			var ajaxContent = $( json_object.html ).find( atxfVars.ajaxContentElement );
			
            if ( ajaxContent.length > 0 ) {

				if ( clear ) {
					atxfVars.pagesLoaded = [0];
					$( atxfVars.ajaxContentElement ).html( ajaxContent.html() );
				} else {
                	$( atxfVars.ajaxContentElement ).append( ajaxContent.html() );
				}
				
				atxfContentHeightTop = getContentHeightTop();
				atxfVars.pagesLoaded.push( atxfVars.pagesLoaded[ atxfVars.pagesLoaded.length - 1 ] + 1 );
				nextPage = atxfVars.pagesLoaded[ atxfVars.pagesLoaded.length - 1 ] + 1;

			} else {
				noMorePages = true;
			}

			// Updates the filtes.
            $( '[data-atxf-taxonomy]' ).each(function (index, element) {
                var atxfTax = $(this).data( 'atxf-taxonomy' );
                var atxfType = $(this).data( 'atxf-type' );
                if ( atxfType == 'or' && $(this).parent().hasClass( 'fs-open' ) ) {
                    $(this).addClass( 'noreload' );
				} else {
                    // $(this).html( $(json_object.html).find( '[data-atxf-taxonomy=' + atxfTax + ']' ).html() );
				}
            });

            reloadFilters = true;
            $( '.atxf:not(.noreload)' ).fSelect( 'reload' );
            $( '.atxf' ).removeClass( 'noreload' );
            reloadFilters = false;

            setATXFComponents();

			// Updates the pagination.
            var ajaxPagination = $(json_object.html).find( '.atxf-pagination' );
            if (ajaxPagination.length > 0) {
                $( '.atxf-pagination' ).html( ajaxPagination.html() );
            }

            $( '.atxf-pagination a' ).click(function (e) { 
				e.preventDefault();
				if ( ! atxfVars.lazyLoading ) {
                	$( 'html,body' ).animate({ scrollTop: $( atxfVars.ajaxContentElement ).offset().top - 96 }, 'slow' );
				}
                getContent( $(this).attr( 'href' ), true );
            });

            // Trigger done event.
            $(document).trigger( 'atxf-done' );

        });

    }

	/**
	 * Generates the final url.
	 */
    function generate_new_url() {
        var new_url = '';
        var atxf_available_taxonomies = [];
        var atxf_query_vars = '';
        var atxf_query_vars = '';

        // ATXF query variables
        $( '[data-atxf-taxonomy]' ).each( function ( index, element ) {
            var tax = $( this ).data( 'atxf-taxonomy' );
            var val = $( this ).val();
            var type = $( this ).data( 'atxf-type' );

            if ( atxf_available_taxonomies.indexOf(tax) === -1 ) {

                atxf_available_taxonomies.push(tax);

                if ( val !== null ) {

                    if ( typeof(val) === 'string' ) {
                        // Single
                        if ( val != 0 && val !='' ) {
                            atxf_query_vars += ( atxf_query_vars ? '&' : '' ) + tax + '=' + val;
						}
                    } else {
                        // Multiple
                        var val_string = '';
                        for ( var i=0; i < val.length; i++ ) {
                            if ( val[i] != 0 && val[i] !='' ) {
                                val_string += ( val_string !='' ? ( type == 'and' ? '+' : ',' ) : '' ) + val[ i ];
							}
                        }
                        if ( val_string != '' ) {
                            atxf_query_vars += ( atxf_query_vars ? '&' : '' ) + tax + '=' + val_string;
						}
					}

                }

            }

        });
        
        // Non ATXF query variables.
        var non_atxf_query_vars = [];
        var get_str = window.location.search.replace( '?', '' ).split( '&' );

        $.each( get_str, function(idx, val) {
            var param_name = val.split( '=' )[0];
            if ( param_name != 'page' && param_name != 'paged' && atxf_available_taxonomies.indexOf(param_name) === -1 ) {
                non_atxf_query_vars.push(val);
            }
        });
        non_atxf_query_vars = non_atxf_query_vars.join( '&' );

        // Generate New Url.
        var new_url = location.pathname;

        // Remove paged or page.
        if ( new_url.indexOf( '/page' ) !== -1) {
            new_url = new_url.substr(0, new_url.indexOf( '/page' ) + 1 );
		}

        if( atxf_query_vars !== '' )
            new_url += '?' + atxf_query_vars;
        if( non_atxf_query_vars !== '' )
            new_url += (atxf_query_vars !== '' ? '&' : '?' ) + non_atxf_query_vars;

        return new_url;

    }


	/**
	 * Sets the components.
	 */
    function setATXFComponents() {
		$( '.pagination, .woocommerce-pagination' ).addClass( 'atxf-pagination' );
    }


	/**
	 * Gets the content height position.
	 */
	function getContentHeightTop() {
		if ( $( atxfVars.ajaxContentElement ).length ) {
			return $( atxfVars.ajaxContentElement ).offset().top + $( atxfVars.ajaxContentElement ).height() + parseInt( $( atxfVars.ajaxContentElement ).css( 'padding-top' ) ) + parseInt( $( atxfVars.ajaxContentElement ).css( 'border-top' ) ) + parseInt( $( atxfVars.ajaxContentElement ).css( 'margin-top' ) );
		} else {
			return false;
		}
	}
	

	/**
	 * On load.
	 */
    $( function () {

		if ( $( atxfVars.ajaxContentElement ).length ) {

			$( atxfVars.ajaxContentElement ).css( 'position', 'relative' );

			setATXFComponents();

			$( '.atxf' ).each(function (index, element) {
				var placeholder = $( this ).data( 'atxf-placeholder' ) !='' ? $( this ).data( 'atxf-placeholder' ) : 'Select some options';
				$(this).fSelect({
					placeholder: placeholder,
					numDisplayed: 3,
					overflowText: '{n} selected',
					noResultsText: 'No results found',
					searchText: 'Search',
					showSearch: false
				});
			});

			// Filter change.
			$( '.atxf' ).change(function (e) { 
				e.preventDefault();

				if ( ! reloadFilters ) {
					
					// Generate the new url location.
					var new_url = generate_new_url();
					
					// If not AJAX then redirect to the new url.
					if ( ! atxfVars.ajaxActive )
						window.location = new_url;
					
					// Get content through AJAX.
					getContent( new_url, true );

				}

			});

			// Pagination through AJAX.
			if ( atxfVars.ajaxActive ) {
				$( '.atxf-pagination a' ).click(function (e) { 
					e.preventDefault();
					if ( ! atxfVars.lazyLoading ) {
						$( 'html,body' ).animate( { scrollTop: $( atxfVars.ajaxContentElement ).offset().top - 96 }, 'slow' );
					}
					getContent( $(this).attr( 'href' ) );
				});
			}

			// Lazy Loading.
			nextPage = atxfVars.pagesLoaded[ atxfVars.pagesLoaded.length - 1 ] + 1;

			$(window).scroll(function() {
				if ( $(window).scrollTop() + $(window).height() >= atxfContentHeightTop - lazyLoadingOffset ) {
					if( ( ! jqxhr || jqxhr.readyState == 4 && jqxhr.status == 200 ) ) {
						var new_url =  generate_new_url();
						getContent( new_url + ( new_url.indexOf( '?' ) !== -1 ? '&paged=' + nextPage : '/page/' + ( nextPage ) ) );
					}
				}
			});

			$(window).resize( function () { 
				atxfContentHeightTop = getContentHeightTop();
			});

			// On back or forward reload the content.
			filters_json = {};
			$( '.atxf' ).each(function (index, element) {
				filters_json[ $(this).data( 'atxf-taxonomy' ) ] = {};
				filters_json[ $(this).data( 'atxf-taxonomy' ) ]['value'] = $( this ).val();
				filters_json[ $(this).data( 'atxf-taxonomy' ) ]['type'] = $( this ).data( 'atxf-type' );
			});

			history.pushState({
				'url': location.href,
				'atxf_query_vars': atxfVars.queryVars,
				'filters_json': filters_json
			}, null, location.href);
			
			window.onpopstate = function(e) {
				if ( e.state ) {
					getContent( e.state.url, true, e.state );
				}
			};

		}

	});

} )( jQuery );