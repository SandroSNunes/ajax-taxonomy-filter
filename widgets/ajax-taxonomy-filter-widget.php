<?php

/**
 * Register and load the widget.
 */

add_action( 'widgets_init', 'atxf_register_widget' );

function atxf_register_widget() {
    register_widget( 'Ajax_Taxonomy_Filter_Widget' );
}


/**
 * Ajax Taxonomy Filter Widget.
 */

class Ajax_Taxonomy_Filter_Widget extends WP_Widget {

	/**
	 * Constructor.
	 */
    public function __construct() {
        
        parent::__construct(
            'ajax-taxonomy-filter-widget',
            __( 'Ajax Taxonomy Filter', 'atxf' ),
            [
                'description' => __( 'Add a filter module', 'atxf' )
            ]
        );

    }
    
    /**
	 * Admin form in the widget area.
	 */
    public function form( $instance ) {

    	$title          = ! empty( $instance ) ? strip_tags( $instance['title']) : '';
    	$taxonomy       = ! empty( $instance ) ? strip_tags( $instance['taxonomy']) : false;
    	$placeholder    = ! empty( $instance ) ? strip_tags( $instance['placeholder']) : '';
    	$orderby        = ! empty( $instance ) ? strip_tags( $instance['orderby']) : false;
    	$order          = ! empty( $instance ) ? strip_tags( $instance['order']) : false;
        $multi_select   = isset( $instance[ 'multi_select' ] ) ? (bool) $instance['multi_select'] : false;
    	$narrow         = isset( $instance[ 'narrow' ] ) ? (bool) $instance['narrow'] : false;
        $show_count     = isset( $instance[ 'show_count' ] ) ? (bool) $instance['show_count'] : false;
        $hide_empty     = isset( $instance[ 'hide_empty' ] ) ? (bool) $instance['hide_empty'] : false;
        $show_hierarchy = isset( $instance[ 'show_hierarchy' ] ) ? (bool) $instance['show_hierarchy'] : false;
    	?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
    	<p>
            <label for="<?php echo $this->get_field_id( 'taxonomy' ); ?>"><?php _e( 'Taxonomy: ', 'search-filter' ); ?>
            <select class="widefat" id="<?php echo $this->get_field_id( 'taxonomy' ); ?>" name="<?php echo $this->get_field_name( 'taxonomy' ); ?>">
                <?php 
                $args = array(
                    'public'   => true,
                ); 
                $taxonomies = get_taxonomies( $args, 'object' );
                foreach( $taxonomies as $taxonomy_item ) {
                    $post_types = implode( ', ', $taxonomy_item->object_type);
                    ?>
                    <option value="<?php echo $taxonomy_item->name; ?>" <?php if($taxonomy_item->name == $taxonomy){ echo 'selected'; } ?>><?php echo $taxonomy_item->label; ?> (<?php echo $post_types; ?>)</option>
                    <?php
                }
                ?>                
            </select>
            </label>
        </p>
        <p>
			<label for="<?php echo $this->get_field_id( 'placeholder' ); ?>"><?php _e( 'Placeholder:' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'placeholder' ); ?>" name="<?php echo $this->get_field_name( 'placeholder' ); ?>" type="text" value="<?php echo esc_attr($placeholder); ?>" />
		</p>
        <p>
            <label for="<?php echo $this->get_field_id( 'orderby' ); ?>"><?php _e( 'Order by: ', 'search-filter' ); ?>
            <select class="widefat" id="<?php echo $this->get_field_id( 'orderby' ); ?>" name="<?php echo $this->get_field_name( 'orderby' ); ?>">
                <option value="name" <?php if($orderby == 'name' ){ echo 'selected'; } ?>>Name</option>
                <option value="slug" <?php if($orderby == 'slug' ){ echo 'selected'; } ?>>Slug</option>
                <option value="term_group" <?php if($orderby == 'term_group' ){ echo 'selected'; } ?>>Term Group</option>
                <option value="term_id" <?php if($orderby == 'term_id' ){ echo 'selected'; } ?>>Term ID</option>
                <option value="id" <?php if($orderby == 'id' ){ echo 'selected'; } ?>>ID</option>
                <option value="description" <?php if($orderby == 'description' ){ echo 'selected'; } ?>>Description</option>
                <option value="parent" <?php if($orderby == 'parent' ){ echo 'selected'; } ?>>Parent</option>
                <option value="count" <?php if($orderby == 'count' ){ echo 'selected'; } ?>>Count</option>
            </select>
            </label>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( 'orderby' ); ?>"><?php _e( 'Order: ', 'search-filter' ); ?>
            <select class="widefat" id="<?php echo $this->get_field_id( 'order' ); ?>" name="<?php echo $this->get_field_name( 'order' ); ?>">
                <option value="ASC" <?php if($order == 'ASC' ){ echo 'selected'; } ?>>ASC</option>
                <option value="DESC" <?php if($order == 'DESC' ){ echo 'selected'; } ?>>DESC</option>
            </select>
            </label>
        </p>
        <p>
            <input class="checkbox" data-toggle="#<?php echo $this->get_field_id( 'narrow_container' ); ?>" type="checkbox" <?php checked( $multi_select ); ?> id="<?php echo $this->get_field_id( 'multi_select' ); ?>" name="<?php echo $this->get_field_name( 'multi_select' ); ?>" />
		    <label for="<?php echo $this->get_field_id( 'multi_select' ); ?>"><?php _e( 'Multi-select' ); ?></label>
        </p>
        <p id="<?php echo $this->get_field_id( 'narrow_container' ); ?>" <?php if ( ! checked( $multi_select, true, false ) ) echo 'style="display:none;"' ?>>
            <input class="checkbox" type="checkbox" <?php checked( $narrow ); ?> id="<?php echo $this->get_field_id( 'narrow' ); ?>" name="<?php echo $this->get_field_name( 'narrow' ); ?>" />
		    <label for="<?php echo $this->get_field_id( 'narrow' ); ?>"><?php _e( 'Narrow results on multiple selections' ); ?></label>
        </p>
        <p>
            <input class="checkbox" type="checkbox" <?php checked( $show_count ); ?> id="<?php echo $this->get_field_id( 'show_count' ); ?>" name="<?php echo $this->get_field_name( 'show_count' ); ?>" />
		    <label for="<?php echo $this->get_field_id( 'show_count' ); ?>"><?php _e( 'Show post counts' ); ?></label>
        </p>
        <p>
            <input class="checkbox" type="checkbox" <?php checked( $hide_empty ); ?> id="<?php echo $this->get_field_id( 'hide_empty' ); ?>" name="<?php echo $this->get_field_name( 'hide_empty' ); ?>" />
		    <label for="<?php echo $this->get_field_id( 'hide_empty' ); ?>"><?php _e( 'Hide empty terms' ); ?></label>
        </p>
        <p>
            <input class="checkbox" type="checkbox" <?php checked( $show_hierarchy ); ?> id="<?php echo $this->get_field_id( 'show_hierarchy' ); ?>" name="<?php echo $this->get_field_name( 'show_hierarchy' ); ?>" />
		    <label for="<?php echo $this->get_field_id( 'show_hierarchy' ); ?>"><?php _e( 'Show hierarchy' ); ?></label>
        </p>
    	<?php
    }

	/**
	 * Update function for the widget.
	 */
    public function update( $new_instance, $old_instance ) {
        $instance = $old_instance;
        $instance['title']          = strip_tags( $new_instance['title'] );
        $instance['taxonomy']       = strip_tags( $new_instance['taxonomy'] );
        $instance['placeholder']    = strip_tags( $new_instance['placeholder'] );
        $instance['orderby']        = strip_tags( $new_instance['orderby'] );
        $instance['order']          = strip_tags( $new_instance['order'] );
        $instance['multi_select']   = isset( $new_instance['multi_select'] ) ? (bool) $new_instance['multi_select'] : false;
        $instance['narrow']         = isset( $new_instance['narrow'] ) ? (bool) $new_instance['narrow'] : false;
        $instance['show_count']     = isset( $new_instance['show_count'] ) ? (bool) $new_instance['show_count'] : false;
        $instance['hide_empty']     = isset( $new_instance['hide_empty'] ) ? (bool) $new_instance['hide_empty'] : false;
        $instance['show_hierarchy'] = isset( $new_instance['show_hierarchy'] ) ? (bool) $new_instance['show_hierarchy'] : false;
        return $instance;
    }


	/**
	 * Outputs the widget with the selected settings.
	 */
    public function widget( $args, $instance ) {

		if ( is_admin() ) {
			return;
		}

    	extract( $args );

    	//$settings     = apply_filters( 'search_filter_settings', get_option( 'search_filter_settings' ) );
        $title          = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance, $this->id_base );
        $taxonomy       = strip_tags($instance['taxonomy']);
        $placeholder    = apply_filters( 'widget_placeholder', empty( $instance['placeholder'] ) ? 'Select some options' : $instance['placeholder'], $instance, $this->id_base );
        $orderby        = strip_tags($instance['orderby']);
        $order          = strip_tags($instance['order']);
        $multi_select   = isset( $instance['multi_select'] ) ? $instance['multi_select'] : false;
        $narrow         = isset( $instance['narrow'] ) ? $instance['narrow'] : false;
        $show_count     = isset( $instance['show_count'] ) ? $instance['show_count'] : false;
        $hide_empty     = isset( $instance['hide_empty'] ) ? $instance['hide_empty'] : false;
        $show_hierarchy = isset( $instance['show_hierarchy'] ) ? $instance['show_hierarchy'] : false;

	    // The content of the widget.
        echo $before_widget;

        if ( ! empty( $title ) ) {
            echo $before_title . $title . $after_title;
        }

        // Display the filter.
        atxf( [
            'taxonomy' 		 => $taxonomy,
            'placeholder' 	 => $placeholder,
            'orderby' 		 => $orderby,
            'order' 		 => $order,
            'multi_select' 	 => $multi_select,
            'narrow' 		 => $narrow,
            'show_count' 	 => $show_count,
            'hide_empty' 	 => $hide_empty,
            'show_hierarchy' => $show_hierarchy,
        ] );
        
		echo $after_widget;
    }
}
?>
