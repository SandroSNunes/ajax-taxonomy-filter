<?php

use Sandro_Nunes\Ajax_Taxonomy_Filter\Ajax_Taxonomy_Filter;

/**
 * Displays the filter.
 * 
 * @param array $args[
 *  'taxonomy'       => ''.
 *  'placeholder'    => ''.
 *  'orderby'        => ''.
 *  'order'          => ''.
 *  'multi_select'   => ''.
 *  'narrow'         => ''.
 *  'show_count'     => ''.
 *  'hide_empty'     => ''.
 *  'show_hierarchy' => ''.
 * ]
 * 
 */

if ( ! function_exists( 'atxf' ) ) {

	function atxf( $args = [] ) {
		Ajax_Taxonomy_Filter::instance()->frontend->display_filter( $args );
	}

}