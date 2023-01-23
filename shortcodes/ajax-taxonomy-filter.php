<?php

/**
 * Ajax Taxonomy Filter Shortcode.
 */

defined( 'ABSPATH' ) || exit;

add_shortcode( 'ajax-taxonomy-filter' , 'ajax_taxonomy_filter_shortcode' );

function ajax_taxonomy_filter_shortcode( $atts, $content, $tag ) {

	// Default attributes.
	$atts = shortcode_atts(
		[
			'taxonomy'       => '',
			'placeholder'    => '',
			'orderby'        => '',
			'order'          => '',
			'multi_select'   => false,
			'narrow'         => false,
			'show_count'     => false,
			'hide_empty'     => false,
			'show_hierarchy' => false,
		],
		$atts,
		$tag
	);

	// Filter attributes.
	$atts['multi_select']   = filter_var( $atts['multi_select'], FILTER_VALIDATE_BOOLEAN );
	$atts['narrow']         = filter_var( $atts['narrow'], FILTER_VALIDATE_BOOLEAN );
	$atts['show_count']     = filter_var( $atts['show_count'], FILTER_VALIDATE_BOOLEAN );
	$atts['hide_empty']     = filter_var( $atts['hide_empty'], FILTER_VALIDATE_BOOLEAN );
	$atts['show_hierarchy'] = filter_var( $atts['show_hierarchy'], FILTER_VALIDATE_BOOLEAN );

	ob_start();

	// Displays the filter.
	atxf( $atts );

	$out = ob_get_clean();

	return $out;
}
