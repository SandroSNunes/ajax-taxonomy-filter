<?php

/**
 * Ajax Taxonomy Filter Walker.
 */

 class Ajax_Taxonomy_Filter_Walker extends Walker_CategoryDropdown {

    public function start_el( &$output, $category, $depth = 0, $args = array(), $id = 0 ) {

        $cat_name = apply_filters( 'list_cats', $category->name, $category );

        if ( isset( $args['value_field'] ) && isset( $category->{$args['value_field']} ) ) {
            $value_field = $args['value_field'];
        } else {
            $value_field = 'term_id';
        }

        $output .= "\t<option class=\"d$depth\" value=\"" . esc_attr( $category->{$value_field} ) . '"';

        // Get selected query vars.
        $selected_query_var = get_query_var( $category->taxonomy );

        $selected_terms = preg_split("/[,\+ ]/", $selected_query_var);

        if ( (string) $category->{$value_field} === (string) $args['selected'] ) {
            $output .= ' selected="selected"';
        } elseif ( in_array( $category->{$value_field}, $selected_terms ) ) {
            $output .= ' selected="selected"';
        }
        
        $output .= '>';
        $output .= $cat_name;
        if ( $args['show_count'] ) {
            $output .= '&nbsp;&nbsp;(' . number_format_i18n( $category->count ) . ')';
        }
        $output .= "</option>\n";
    }

}
