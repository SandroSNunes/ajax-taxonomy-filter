<?php

namespace Sandro_Nunes\Lib;

/**
 * Admin Panel.
 */

if ( class_exists( 'Sandro_Nunes\Lib\Admin_Panel' ) ) {
	return;
}

class Admin_Panel {

	// Parameters for add_submenu_page function.
	protected $menu = array();

	// Parameters for add_submenu_page function.
	protected $submenu = array();

	// Options structure to get from file in /config/pdfc-admin-panel.php.
	public $options = array();

	// Options group name.
	public $option_group = 'panel_group';

		// Option name.
	public $option_name = 'panel_options';

	/**
	 * Constructor.
	 * 
	 * @param array  $submenu      Submenu structure.
	 * @param array  $options      Options structure from file in /config/pdfc-admin-panel.php.
	 * @param string $option_group Option group name.
	 * @param string $option_name  Option name.
	 */
	public function __construct( $menu, $submenu, $options, $option_group = false, $option_name = false ) {

		$this->menu    = apply_filters( 'sandro_nunes\lib\admin_panel\admin_panel_menu', $menu );
		$this->submenu = apply_filters( 'sandro_nunes\lib\admin_panel\admin_panel_submenu', $submenu );
		$this->options = apply_filters( 'sandro_nunes\lib\admin_panel\admin_panel_options', $options );

		if ( $option_group ) {
			$this->option_group = $option_group;
		}

		if ( $option_name ) {
			$this->option_name = $option_name;
		}

		add_action( 'admin_menu', [ $this, 'add_menu_page' ] );
		add_action( 'admin_init', [ $this, 'panel_register_setting' ] );
	}

	/**
	 * Create new menu page.
	 */
	public function add_menu_page() {

		if ( ! empty( $this->menu ) ) {
			add_menu_page(
				$this->menu[0],
				$this->menu[1],
				$this->menu[2],
				$this->menu[3],
				array( $this, isset( $this->menu[4] ) && $this->menu[4] ? $this->menu[4] : 'display_panel_page' ),
				$this->menu[5],
				$this->menu[6]
			);
		}

		if ( ! empty( $this->submenu ) ) {
			
			add_submenu_page(
				$this->submenu[0],
				$this->submenu[1],
				$this->submenu[2],
				$this->submenu[3],
				$this->submenu[4],
				array( $this, isset( $this->submenu[5] ) ? $this->submenu[5] : 'display_panel_page' )
			);
		}
		
	}

	/**
	 * Shows the panel page.
	 */
	public function display_panel_page() {
		$page = $this->get_tab();
		?>
		<div class="wrap">
			<h1><?php echo $this->submenu[1]; ?></h1>
		<?php
		if ( count( $this->options ) > 0 ) {
			if ( count( $this->options ) > 1 ) {
			?>
			<h2 class="nav-tab-wrapper">
				<?php foreach ( $this->options as $k => $tab ) { ?>
				<a class="nav-tab<?php if ( $page == $k ) echo ' nav-tab-active'; ?>" href="<?php echo add_query_arg( 'panel_page', $k ); ?>"><?php echo $tab['label']; ?></a>
				<?php } ?>
				<?php do_action( 'sandro_nunes\lib\admin_panel\admin_panel_after_tabs' ); ?>
			</h2>
			<?php } ?>
		
			<?php do_action( 'sandro_nunes\lib\admin_panel\admin_panel_before_panel', $page, $this->options ); ?>
			<?php if ( ! empty( $this->options[ $page ]['sections'][ $page ]['fields'] ) ) { ?>
			<form action="options.php" method="post">
				<?php do_settings_sections( $this->option_name ); ?>
				<?php settings_fields( $this->option_group ) ?>
				<p class="submit">
					<input type="hidden" name="panel_page" value="<?php echo $page; ?>" />
					<input class="button-primary" type="submit" name="save_options" value="Save Options" />
				</p>
			</form>
			<?php } ?>
			<?php do_action( 'sandro_nunes\lib\admin_panel\admin_panel_after_panel', $page, $this->options ); ?>
		</div>
		<?php
		}
	}

	/**
	 * Register a new settings option group.
	 */
	public function panel_register_setting() {
		$page = $this->get_tab();
		$tab = isset( $this->options[ $page ] ) ? $this->options[ $page ] : array();

		if ( ! empty( $tab['sections'] ) ) {

			// Add sections and fields.
			foreach ( $tab['sections'] as $section_name => $section ) {

				// Add the section.
				add_settings_section(
					$section_name,
					$section['title'],
					array( $this, 'panel_section_content' ),
					$this->option_name
				);

				// Add the fields.
				foreach ( $section['fields'] as $option_name => $option ) {
					$option['id'] = $option_name;
					$option['label_for'] = $option_name;

					// Register settings group.
					register_setting(
						$this->option_group,
						$option_name,
						array( $this, 'panel_sanitize' )
					);

					// Add the field.
					add_settings_field(
						$option_name,
						$option['title'],
						array( $this, 'panel_field_content' ),
						$this->option_name,
						$section_name,
						$option
					);
				}

			}
		}
	}

	/**
	 * Display sections content.
	 * 
	 * @param array $section Section definition.
	 */
	public function panel_section_content( $section ) {
		$page = $this->get_tab();
		if ( isset( $this->options[ $page ]['sections'][ $section['id'] ]['description'] ) && $this->options[ $page ]['sections'][ $section['id'] ]['description'] != '' ) {
			echo '<p class="section-description">' . $this->options[ $page ]['sections'][ $section['id'] ]['description'] . '</p>';
		}
		do_action( 'sandro_nunes\lib\admin_panel\admin_panel_section_content', $section );
	}

	/**
	 * Sanitize the option's value.
	 * 
	 * @param string $value Option value.
	 */
	public function panel_sanitize( $value ) {
		return apply_filters( 'sandro_nunes\lib\admin_panel\admin_panel_sanitize', $value );
	}

	/**
	 * Get the active tab. If the page isn't provided, the function will return the first tab name.
	 * 
	 * @return string Tab name.
	 */
	public function get_tab() {
		$panel_page = ! empty( $_REQUEST['panel_page'] )  ? sanitize_title_for_query( $_REQUEST['panel_page'] ) : '';
		$tabs = array_keys( $this->options );
		return ! empty( $panel_page ) ? $panel_page : $tabs[0];
	}

	/**
	 * Display field content.
	 * 
	 * @param array Field strcutre got from the options panel config file.
	 */
	public function panel_field_content( $field ) {

		$value = get_option( $field['id'], isset( $field['default'] ) ? $field['default'] : '' );
		
		$id    = $field['id'];
		$name  = $field['id'];

		$out = '';

		switch( $field['type'] ) {

			/**
			 * Text input box.
			 */
			case 'text':

				$out  = '<input type="text" id="' . $id . '" name="' . $name . '" value="' . $value . '" class="regular-text code" />';

				if ( isset( $field['description'] ) && $field['description'] != '' ) {
					$out .= '<p class="description">' . $field['description'] . '</p>';
				}

				break;

			/**
			 * Number input.
			 */
			case 'number':

				$mms = '';

				if ( isset( $field['min'] ) ) {
					$mms .= ' min="' . $field['min'] . '"';
				}

				if ( isset( $field['max'] ) ) {
					$mms .= ' max="' . $field['max'] . '"';
				}

				if ( isset( $field['step'] ) ) {
					$mms .= ' step="' . $field['step'] . '"';
				}

				$out = '<input type="number" id="' . $id . '" name="' . $name . '" value="' . $value . '" class="small-text" ' . $mms . ' />';

				if ( isset( $field['description'] ) && $field['description'] != '' ) {
					$out .= '<p class="description">' . $field['description'] . '</p>';
				}

				break;

			/**
			 * Textarea.
			 */
			case 'textarea':

				$class = isset( $field['class'] ) ? $field['class'] : 'large-text code';
				$rows  = isset( $field['rows'] ) ? $field['rows'] : 10;
				$cols  = isset( $field['cols'] ) ? $field['cols'] : 50;

				$out  = '<textarea id="' . $id . '" name="' . $name . '" rows="' . $rows . '" cols="' . $cols . '" class="' . $class . '">' . $value . '</textarea>';

				if ( isset( $field['description'] ) && $field['description'] != '' ) {
					$out .= '<p class="description">' . $field['description'] . '</p>';
				}

				break;

			/**
			 * Checkbox.
			 */
			case 'checkbox':

				$out = '<input type="checkbox" id="' . $id . '" name="' . $name . '" value="1" ' . checked( $value, true, false ) . ' />';

				if ( isset( $field['description'] ) && $field['description'] != '' ) {
					$out .= ' <label for="' . $id . '"><span class="description">' . $field['description'] . '</span></label>';
				}
				
				break;

			/**
			 * Select box.
			 */
			case 'select':

				$out  = '<select name="' . $name . '" id="' . $id . '">';

				foreach ( $field['options'] as $val => $label ) {
					$out .= '<option value="' . $val . '" ' . selected( $value, $val, false ) . '>' . $label . '</option>';
				}

				$out .= "</select>";

				if ( isset( $field['description'] ) && $field['description'] != '' ) {
					$out .= '<p class="description">' . $field['description'] . '</p>';
				}
				
				break;

			/**
			 * Select from available pages.
			 */
			case 'select-pages':
				$out  = '<select name="' . $name . '" id="' . $id . '">';

				$args = array(
					'post_type'      => 'page',
					'post_status'    => 'any',
					'posts_per_page' => -1,
					'orderby' => array(
						'post_title' => 'ASC',
					),
				);
				$pages = get_posts( $args );

				foreach ( $pages as $page ) {
					$out .= '<option value="' . $page->ID . '" ' . selected( $value, $page->ID, false ) . '>' . $page->post_title . '</option>';
				}

				$out .= '</select>';

				if ( isset( $field['description'] ) && $field['description'] != '' ) {
					$out .= '<p class="description">' . $field['description'] . '</p>';
				}
				
				break;

			/**
			 * Description.
			 */
			case 'description':

				if ( isset( $field['description'] ) && $field['description'] != '' ) {
					$out = '<div class="description">' . $field['description'] . '</div>';
				}

				break;

			/**
			 * Select from available terms.
			 */
			case 'select-terms':
				
				if ( isset( $field['multiple'] ) && $field['multiple'] ) {
					$out  = '<select name="' . $name . '[]" id="' . $id . '" multiple style="resize: vertical;">';
				} else {
					$out  = '<select name="' . $name . '" id="' . $id . '">';
					$out  .= '<option value=""></option>';
				}

				$args = array(
					'taxonomy'   => $field['taxonomy'],
					'hide_empty' => false,
					'orderby'    => 'name',
				);
				$terms = get_terms( $args );

				foreach ( $terms as $term ) {
					$out .= '<option value="' . $term->term_id . '" ' . ( isset( $field['multiple'] ) && $field['multiple'] ? selected( true, in_array( $term->term_id, (array) $value ), false ) : selected( $value, $term->term_id, false ) ) . '>' . $term->name . '</option>';
				}

				$out .= '</select>';

				if ( isset( $field['description'] ) && $field['description'] != '' ) {
					$out .= '<p class="description">' . $field['description'] . '</p>';
				}
				
				break;

			/**
			 * Links.
			 */
			case 'link':

				$out  = '<a href="' . $field['href'] . '" id="' . $id . '" class="' . $field['field_class'] . '">' . $field['text'] . '</a>';

				if ( isset( $field['description'] ) && $field['description'] != '' ) {
					$out .= '<div class="description">' . $field['description'] . '</div>';
				}

				break;

			default:
				do_action( 'sandro_nunes\lib\admin_panel\admin_panel_field_' . $field['type'] );
				break;
		}

		echo $out;
	}

}
