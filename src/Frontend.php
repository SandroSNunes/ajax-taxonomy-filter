<?php

namespace Sandro_Nunes\Ajax_Taxonomy_Filter;

use Sandro_Nunes\Lib\Util;

/**
 * Frontend.
 */

class Frontend {

	public $atxf_query_vars;
    public $filters         = [];
    public $filters_args    = [];
    public $filters_values;
	public $ajax_active     = true;
    public $is_preload      = false;
    public $is_ajax         = false;

	/**
	 * Constructor.
	 */
	public function __construct() {

		$this->ajax_active = get_option( 'atxf_ajax_active', '' );

		$this->init_hooks();

		// Detects if there is an ajax call
		$this->detect_filter();

		return $this;
	}


	/**
	 * Initializate hooks.
	 */
	private function init_hooks() {

		if ( ! $this->is_ajax ) {

			// Enqueue frontend scripts & styles.
			add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

            // Adds the javascript variables to preload the elements.
            add_action( 'wp_footer', [ $this, 'add_initial_frontend_variables' ], 100 );
        }
	}


	/**
	 * Enqueue frontend scripts & styles.
	 */
	public function enqueue_scripts() {

		$suffix = SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_script( 'popper', 'https://unpkg.com/@popperjs/core@2', array(), 1.0, true );

		wp_enqueue_style( 'fselect', Util::get_active_file_url( 'fSelect' . $suffix . '.css' ), [], '1.0.0', 'all' );
		wp_enqueue_script( 'fselect', Util::get_active_file_url( 'fSelect' . $suffix . '.js' ), [ 'jquery' ], '1.0.0', true );

		wp_enqueue_style( 'atxf', Util::get_active_file_url( 'ajax-taxonomy-filter' . $suffix . '.css' ), [ 'fselect' ], '1.0.0', 'all' );
		wp_enqueue_script( 'atxf', Util::get_active_file_url( 'ajax-taxonomy-filter' . $suffix . '.js' ), [ 'jquery', 'fselect' ], '1.0.0', true );

		global $wp;
		wp_localize_script( 'atxf', 'atxfVars', array(
			'siteUrl'            => site_url(),
			'ajaxUrl'            => admin_url( 'admin-ajax.php' ),
			'ajaxActive'         => $this->ajax_active,
			'ajaxNonce'          => wp_create_nonce('atxf-ajax-nonce'),
			'currentUrl'         => home_url( $wp->request ),
			'ajaxContentElement' => get_option( 'atxf_ajax_content_element', 'main' ),
			'queryVars'          => '',
			'filters'            => '',
			'filtersHtml'        => '',
			'lazyLoading'        => true,
			'pagesLoaded'        => [],
		));

	}


	/**
	 * Adds the javascript variables to preload the elements.
	 */
	public function add_initial_frontend_variables() {
		global $paged;

        $this->is_preload = true;
		
        foreach ( $this->filters_args as $filter ) {
            ob_start();
            $this->display_filter( $filter );
            $out = ob_get_clean();
            $filters_html[ $filter['taxonomy'] ] = $out;
        }
?>
<script>
atxfVars.queryVars = '<?php echo json_encode( $this->atxf_query_vars ); ?>';
atxfVars.filters = '<?php echo json_encode( $this->filters ); ?>';
atxfVars.filtersHtml = <?php echo json_encode( $filters_html ); ?>;
atxfVars.pagesLoaded = [<?php echo ( $paged ? $paged : 1 ); ?>];
preloadFilters();
</script>
<?php
    }


	/**
	 * Detects if a filter has been used.
	 */
    public function detect_filter() {
        $this->is_ajax = ( isset( $_POST['action'] ) && $_POST['action'] == 'atxf_ajax_get_content' ) ? true : false;

        if ( $this->is_ajax ) {
            $this->atxf_query_vars = (array) json_decode( $_POST['atxf_query_vars'] );
            $this->filters_values = $_POST['filters'];

            // Add the selected taxonomies to the existing query vars.
            $this->atxf_query_vars['tax_query'] = $this->get_tax_query( $this->filters_values );
            foreach ( $this->filters_values as $tax => $arr_value_type ) {
                $this->atxf_query_vars[ $tax ] = $arr_value_type['type'] == 'and' ? implode( '+', $arr_value_type['value'] ) : implode( ',', $arr_value_type['value'] );
            }
        } else {
            foreach ( $_GET as $key => $val ) {
                $tax_vals = [];
                //if ( in_array( $key, $this->get_all_widget_taxonomies() ) && ! in_array( $key, [ 'paged', 'per_page', 'sort' ] ) ) {
                if ( ! in_array( $key, [ 'paged', 'per_page', 'sort' ] ) ) {
                    $tax_vals = preg_split( "/[,\+ ]/", $val );
                }
                $this->filters_values[ $key ]['value'] = $tax_vals;
            }
        }
      
        add_action( 'pre_get_posts', [ $this, 'filter_query_posts' ], 999 );

        if( $this->is_ajax ) {
            add_action( 'shutdown', [ $this, 'display_output' ], 0 );
            ob_start();
        }

    }

	/**
	 * Builds the taxonomy query.
	 */
    public function get_tax_query( $arr_tax_value ) {
       
        foreach ( $arr_tax_value as $tax => $arr_value_type ) {

            $arr_value = $arr_value_type['value'];
            $type      = $arr_value_type['type'];

            if ( $arr_value != '' || is_array( $arr_value ) ) {

                if ( $type == 'and' ) {

                    foreach ( $arr_value as $value ) {

						if ( $value != 0 ) {

							$taxquery[] = [
								'taxonomy'         => $tax,
								'field'            => 'slug',
								'terms'            => $value,
								'include_children' => true,
								'operator'         => 'IN'
							];

						}


                    }

                } else {

					if ( $arr_value != 0 ) {

						$taxquery[] = [
							'taxonomy'         => $tax,
							'field'            => 'slug',
							'terms'            => $arr_value,
							'include_children' => true,
							'operator'         => 'IN'
						];

					}

                }
                
            }

        }

        return $taxquery;

    }

	/**
	 * Prepares the main query based on the filters.
	 */
    public function filter_query_posts( $query ) {

        $is_main_query = $query->get( 'atxf' ) ? (bool) $query->get( 'atxf' ) : $query->is_main_query();
        $is_main_query = apply_filters( 'atxf_is_main_query', $is_main_query, $query );
        
        if ( $is_main_query ) {

            if ( is_array( $this->filters_values ) && count( $this->filters_values ) > 0 ) {
                    
                $taxquery = [];
                foreach ( $this->filters_values as $tax => $arr_value_type ) {

                    $arr_value  = $arr_value_type['value'];
                    $type       = $arr_value_type['type'];

                    if ( $arr_value != '' || is_array( $arr_value ) ) {

                        if ( $type == 'and' ) {

                            foreach ( $arr_value as $value ) {

								if ( $value != '0' ) {

									$taxquery[] = [
										'taxonomy'         => $tax,
										'field'            => 'slug',
										'terms'            => $value,
										'include_children' => true,
										'operator'         => 'IN'
									];

								}

                            }

                            $query->set( $tax , implode( '+', $arr_value ) );

                        } else {

							if ( $arr_value != '0' ) {

								$taxquery[] = [
									'taxonomy'         => $tax,
									'field'            => 'slug',
									'terms'            => $arr_value,
									'include_children' => true,
									'operator'         => 'IN'
								];

							}

                            $query->set( $tax , implode( ',', $arr_value ) );

                        }

                    }

                }

                if ( $taxquery )
                    $query->set( 'tax_query', $taxquery );

			}

            $this->atxf_query_vars = $query->query_vars;

        }

        return $query;
    }


	/**
	 * Display the ajax output.
	 */
    function display_output() {
		
        $html = ob_get_clean();

        // Grab the <body> contents.
        preg_match( "/<body(.*?)>(.*?)<\/body>/s", $html, $matches );

        if ( ! empty( $matches ) ) {
            $html = trim( $matches[2] );
        }

        $this->output['html'] = $html;
        $this->output['atxf_query_vars'] = json_encode( $this->atxf_query_vars );

        do_action( 'atxf_html_output', $this->output );

        wp_send_json( $this->output );
    }


	/**
	 * Gets all widgets options.
	 */
	function get_all_widget_taxonomies() {
        $widgets_taxonomies = [];

        $widgets_options = get_option( 'widget_ajax-taxonomy-filter-widget' );

        foreach ( $widgets_options as $widgets_id => $widgets_option ) {
            if ( $widgets_option['taxonomy'] ) {
                $widgets_taxonomies[] = $widgets_option['taxonomy'];
			}
        }

        return $widgets_taxonomies;        
    }


    /**
	 * Gets all widgets options by taxonomy.
	 */
    function get_widget_options_by_taxonomy( $taxonomy = '' ) {

        $widgets_options = get_option( 'widget_ajax-taxonomy-filter-widget' );

        foreach ( $widgets_options as $widgets_id => $widgets_option ) {
            if ( $widgets_option['taxonomy'] == $taxonomy ) {
                $widget['id'] = $widgets_id;
                $widget = $widgets_option;
            }
        }

        return $widget;        

    }


	/**
	 * Displays the filter.
	 */
    public function display_filter( $args = [] ) {

        $this->filters_args[] = $args;

        $id             = 'atxf-' . ( count( $this->filters ) + 1 );
        $taxonomy       = isset( $args['taxonomy'] ) ? $args['taxonomy'] : '';
        $placeholder    = isset( $args['placeholder'] ) ? $args['placeholder'] : '';
        $orderby        = isset( $args['orderby'] ) ? $args['orderby'] : '';
        $order          = isset( $args['order'] ) ? $args['order'] : '';
        $multi_select   = isset( $args['multi_select'] ) ? $args['multi_select'] : false;
        $narrow         = isset( $args['narrow'] ) ? $args['narrow'] : false;
        $show_count     = isset( $args['show_count'] ) ? $args['show_count'] : false;
        $hide_empty     = isset( $args['hide_empty'] ) ? $args['hide_empty'] : false;
        $show_hierarchy = isset( $args['show_hierarchy'] ) ? $args['show_hierarchy'] : false;

        $this->filters[] = [
            'id'           => $id,
            'taxonomy'     => $taxonomy,
            'placeholder'  => $placeholder,
            'multi_select' => $multi_select,
            'type'         => ( $narrow ? 'and' : 'or' ),
        ];

        if ( $taxonomy ) {
        ?>
            <div id="<?php echo $id; ?>" class="atxf-filter">
                <?php
                $terms = get_terms( $taxonomy );

                if( ! is_wp_error( $terms ) ) {
                    ?>

                    <label class="search-filter-label"><?php echo apply_filters( 'atxf_taxonomy_label', $taxonomy ); ?></label>
                    <?php
                    global $wp_query;

                    $new_query_vars = $this->atxf_query_vars && ! is_archive() ? $this->atxf_query_vars : $wp_query->query_vars;

                    $object_ids = [];

                    if ( is_archive() || $this->is_preload || $this->is_ajax ) {

                        $new_query_vars['nopaging'] = true;
                        $new_query_vars['posts_per_page'] = -1;
                        $new_query_vars['atxf'] = false;

                        // If not narrow results - Removes the taxonomy itself to allow to show them all
                        $tax_query = $new_query_vars['tax_query'];
                        if ( ! $narrow ) {
                            $new_tax_query = [];
                            if ( $tax_query ) {
                                foreach ( $tax_query as $tax_item ) {
                                    if ( isset( $tax_item['taxonomy'] ) && $tax_item['taxonomy'] != $taxonomy ) {
                                        $new_tax_query = $tax_item;
									}
                                }
                            }
                            $new_query_vars[$taxonomy] = false;
                            $new_query_vars['taxonomy'] = false;
                            $new_query_vars['term'] = false;
                            $new_query_vars['tax_query'] = $new_tax_query;
                        }

                        // Get total posts that are affected by the filters
                        $new_posts = get_posts( $new_query_vars );
                        foreach( $new_posts as $new_post ) {
                            $object_ids[] = $new_post->ID;
                        }

                    }

                    // Dropdown
                    $dropdown_args = array(
                        'object_ids'        => $object_ids,
                        'show_option_all'   => $multi_select ? '' : __( 'Show all', 'atxf' ),
                        'selected'          => '',
                        'taxonomy'          => $taxonomy,
                        'name'              => 'atxf-'.$taxonomy,
                        'value_field'       => 'slug',
                        'show_count'        => $show_count,
                        'hide_empty'        => $hide_empty,
                        'orderby'           => $orderby,
                        'order' 		    => $order,
                        'hierarchical'      => $show_hierarchy,
                        'echo'              => 0,
                        'class'			    => 'atxf atxf-select',
                        'walker'            => new \Ajax_Taxonomy_Filter_Walker( 'widget' ),
                    );

                    // Apply filter on the arguments to let users modify them first!
                    $dropdown_args = apply_filters( 'atxf_dropdown_categories', $dropdown_args, $taxonomy );

                    // Create the dropdown
                    $filterdropdown = wp_dropdown_categories( $dropdown_args );

                    // Add custom attributes
                    $filterdropdown = str_replace('<select ', '<select data-atxf-taxonomy="' . $taxonomy . '" data-atxf-placeholder="' . $placeholder . '" ', $filterdropdown );

                    // Multi select
                    if( $multi_select )
                        $filterdropdown = str_replace('<select ', '<select multiple data-atxf-type="' . ( $narrow ? 'and' : 'or' ) . '" ', $filterdropdown );
                        
                    echo $filterdropdown;

                }
                ?>
            </div>
		<?php
        }

	}

}
