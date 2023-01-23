<?php

namespace Sandro_Nunes\Lib;

use Sandro_Nunes\Lib\Util;

/**
 * Plugin.
 */

if ( class_exists( 'Sandro_Nunes\Lib\Plugin' ) ) {
	return;
}

class Plugin {

	private $args       = [];
	public $plugin_data = [];
	public $admin_panel = null;

	/**
	 * Constructor.
	 * 
	 * @return object Admin object.
	 */
	public function __construct( $args = [] ) {
		$this->args = $args;
		$this->includes();
		$this->set_plugin_data();
		$this->define_constants();
		$this->init_panel();
		$this->init_hooks();
		return $this;
	}

	/**
	 * Includes.
	 */
	public function includes() {

		$core_functions_file = plugin_dir_path( $this->args['file'] ) . 'lib/functions-core.php';
		if ( file_exists( $core_functions_file ) ) {
			include $core_functions_file;
		}

	}

	/**
	 * Sets the plugin data.
	 */
	public function set_plugin_data() {
		if ( ! function_exists( 'get_plugin_data' ) ){
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}
		$this->plugin_data             = get_plugin_data( $this->args['file'] );

		$this->plugin_data['id']       = $this->plugin_data['TextDomain'];
		$this->plugin_data['slug']     = dirname( plugin_basename( $this->args['file'] ) );
		$this->plugin_data['basename'] = plugin_basename( $this->args['file'] );
	}

	/**
	 * Define constants.
	 */
	private function define_constants() {
		Util::constants( [
			strtoupper( $this->plugin_data['id'] )               => true,
			strtoupper( $this->plugin_data['id'] ) . '_ID'       => $this->plugin_data['id'],
			strtoupper( $this->plugin_data['id'] ) . '_SLUG'     => $this->plugin_data['slug'],
			strtoupper( $this->plugin_data['id'] ) . '_VERSION'  => $this->plugin_data['Version'],
			strtoupper( $this->plugin_data['id'] ) . '_URL'      => plugin_dir_url( $this->args['file'] ),
			strtoupper( $this->plugin_data['id'] ) . '_DIR'      => plugin_dir_path( $this->args['file'] ),
			strtoupper( $this->plugin_data['id'] ) . '_BASENAME' => plugin_basename( $this->args['file'] ),
		] );
	}

	/**
	 * Initializate hooks.
	 */
	private function init_hooks() {

		// Load plugin text domain.
		add_action( 'init', [ $this, 'load_plugin_textdomain' ] );

		// Sets the links in the plugins page.
		add_filter( 'plugin_action_links_' . plugin_basename( $this->args['file'] ), [ $this, 'action_links' ], 10, 4 );
		
		// Sets the meta links in the plugins page.
		// add_filter( 'plugin_row_meta', [ $this, 'meta_links'], 10, 4 );

		// Adds the plugin info and documentation.
		add_filter( 'site_transient_update_plugins', [ $this, 'site_transitent' ] );
		add_filter( 'pre_set_site_transient_update_plugins', [ $this, 'site_transitent' ] );
		add_filter( 'plugins_api', [ $this, 'set_plugin_info' ], 10, 3 );
		// add_filter( 'plugins_api_result', [ $this, 'remove_author_from_description' ], 10, 3 );

		// Activate and deactivate functions.
		register_activation_hook( $this->args['file'], [ $this, 'activate_plugin' ] );
		register_deactivation_hook( $this->args['file'], [ $this, 'deactivate_plugin' ] );

	}


	/**
	 * Activate plugin functions.
	 */
	public function activate_plugin() {

		// Create options with the default values set on the config/admin-panel.php.
		if ( $this->admin_panel->options ) {
			foreach ( $this->admin_panel->options as $tab ) {
				foreach ( $tab['sections'] as $section ) {
					foreach ( $section['fields'] as $id => $value ) {
						if ( isset( $id ) && isset( $value['default'] ) && $value['default'] !='' ) {
							add_option( $id, $value['default'] );
						}
					}
				}
			}
		}
		
	}

	/**
	 * Deactivate plugin functions.
	 */
	public function deactivate_plugin() {

		// Delete admin options.
		if ( apply_filters( 'sandro_nunes\lib\plugin\delete_options', false ) ) {

			global $wpdb;

			if ( is_multisite() ) {
				$blogs = $wpdb->get_results( "SELECT blog_id FROM $wpdb->blogs", ARRAY_A );
				if ( $blogs ) {
					foreach ( $blogs as $blog ) {
						switch_to_blog( $blog['blog_id'] );
						$wpdb->query( "DELETE FROM $wpdb->options WHERE `option_name` LIKE '" . $this->plugin_data['id'] . "\_%';" );
						restore_current_blog();
					}
				}
			} else {
				$wpdb->query( "DELETE FROM $wpdb->options WHERE `option_name` LIKE '" . $this->plugin_data['id'] . "\_%';" );
			}

		}
		
	}

	/**
	 * Load localisation files.
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain( $this->plugin_data['id'], false, dirname( plugin_basename( $this->args['file'] ) ) . '/languages' );
	}
	
	/**
	 * Inits the panel.
	 */
	public function init_panel() {
		
		if ( ! is_admin() ) {
			return;
		}

		Util::include( [
			plugin_dir_path( $this->args['file'] ) . 'lib/Admin_Panel.php',
		] );

		// Sets the menu for the plugin.
		$submenu = apply_filters( 'sandro_nunes\lib\plugin\submenu', [
			'options-general.php',
			$this->plugin_data['Name'],
			$this->plugin_data['Name'],
			'manage_options',
			$this->plugin_data['id']
		] );

		// Sets the panel configuration.
		include_once plugin_dir_path( $this->args['file'] ) . 'config/admin-panel.php';

		$config = apply_filters( 'sandro_nunes\lib\plugin\admin_panel_options', $config );

		if ( ! empty( $config ) ) {
			$this->admin_panel = new Admin_Panel( [], $submenu, $config, $this->plugin_data['id'] . '-group', $this->plugin_data['id'] );
		}
	}

	/**
	 * Sets the links in the plugins page.
	 * 
	 * @param string[] $actions     An array of the plugin's metadata.
	 * @param string   $plugin_file Path to the plugin file relative to the plugins directory.
	 * @param array    $this->plugin_data An array of plugin data.
	 * @param string   $context     The plugin context.
	 * 
	 * @return array All the plugin links.
	 */
	public function action_links( $actions, $plugin_file, $plugin_data, $context ) {
		if ( $this->admin_panel->options ) {
			$plugin_links['settings'] = '<a href="' . admin_url( 'options-general.php?page=' . $this->plugin_data['TextDomain'] ) . '">Settings</a>';
			$actions = array_merge( $plugin_links, $actions );
		}
		return $actions;
	}

	/**
	 * Sets the meta links in the plugins page.
	 * 
	 * @param string[] $plugin_meta An array of the plugin's metadata.
	 * @param string   $plugin_file Path to the plugin file relative to the plugins directory..
	 * @param array    $this->plugin_data An array of plugin data..
	 * @param string   $status Status filter currently applied to the plugin list.
	 * 
	 * @return string[] All the plugin links.
	 */
	// function meta_links( $plugin_meta, $plugin_file, $plugin_data, $status ) {

	// 	$documentation = '';
	// 	if ( file_exists( plugin_dir_path( $this->args['file'] ) . 'documentation.html' ) ) {
	// 		$documentation = file_get_contents( plugin_dir_path( $this->args['file'] ) . 'documentation.html' );
	// 	}

	// 	// Adds the documentation link to open a popup with the documentation content.
	// 	if ( $documentation && dirname( $plugin_file ) == basename( plugin_dir_path( $this->args['file'] ) ) ) {
	// 		$plugin_meta[] = '<a href="#TB_inline?inlineId=' . $this->plugin_data['TextDomain'] . '-documentation" title="' . $this->plugin_data['Name'] . ' Documentation" class="thickbox">Docs</a><div style="display:none;">' . $documentation . '</div>';
	// 	}

	// 	return $plugin_meta;
	// }


	/**
	 * Extract just the content from the documenation file.
	 * 
	 * @param string Documentation file.
	 * 
	 * @return string Documentation content.
	 */
	public function get_documentation_content( $file ) {
		$contents = file_get_contents( $file );
		$contents = substr( $contents, strpos( $contents, '<div id="plugin-information-content">') + 37 );
		$contents = substr( $contents, 0, strrpos( $contents, '</div>' ) );
		$contents = substr( $contents, 0, strrpos( $contents, '</div>' ) );

		return $contents;
	}


	/**
	 * Gets the documentation content.
	 * 
	 * @return string All documentations tabs content.
	 */
	public function get_documentation() {
		$tabs = [ 'index', 'faq', 'changelog', 'screenshots', 'other_notes' ];
		$documentation = [];

		foreach ( $tabs as $tab ) {
			if ( file_exists( plugin_dir_path( $this->args['file'] ) . 'documentation/' . $tab . '.html' ) ) {
				$documentation[ ( $tab == 'index' ? 'description' : $tab ) ] = $this->get_documentation_content( plugin_dir_path( $this->args['file'] ) . 'documentation/' . $tab . '.html' );
			}
		}

		return $documentation;
	}

	/**
	 * Push in plugin version information to get the update notification.
	 */
	public function site_transitent( $transient ) {

		// If we have checked the plugin data before, don't re-check.
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		// $package = $this->githubAPIResult->zipball_url;
	
		// // Include the access token for private GitHub repos.
		// if ( ! empty( $this->accessToken ) ) {
		// 	$package = add_query_arg( array( "access_token" => $this->accessToken ), $package );
		// }

		$obj = new \stdClass();
		$obj->id          = $this->plugin_data['id'];
		$obj->slug        = $this->plugin_data['slug'];
		$obj->plugin      = $this->plugin_data['basename'];
		$obj->new_version = $this->plugin_data['Version'];
		$obj->url         = $this->plugin_data["PluginURI"];
		$obj->package     = '';
		$obj->icons       = [
			'2x' => '',
			'1x' => '',
		];
		$obj->banners     = [
			'2x' => '',
			'1x' => '',
		];
		$obj->banners_rtl = [
			'2x' => '',
			'1x' => '',
		];
		$obj->requires    = $this->plugin_data['RequiresWP'];

		// Check the versions if we need to do an update.
		$update = version_compare( $this->plugin_data['Version'], $transient->checked[ $this->plugin_data['basename'] ] );

		// Update the transient to include our updated plugin data.
		if ( $update == 1 ) {
			$transient->response[ $this->plugin_data['basename'] ] = $obj;
		} else {
			$transient->no_update[ $this->plugin_data['basename'] ] = $obj;
		}
		// $transient->checked[ $this->plugin_data['slug'] ] = $obj->new_version;
		
		return $transient;
	}

	/**
	 * Push in plugin version information to display in the details lightbox.
	 */
	public function set_plugin_info( $result, $action, $response ) {

		// If nothing is found, do nothing.
		if ( empty( $response->slug ) || $response->slug != dirname( plugin_basename( $this->args['file'] ) ) ) {
			return $result;
		}

		$zipball_url = isset( $this->githubAPIResult->zipball_url ) ? $this->githubAPIResult->zipball_url : '';
		$body        = isset( $this->githubAPIResult->body ) ? $this->githubAPIResult->body : '';

		// Add our plugin information.
		$response->slug            = isset( $this->slug ) ? $this->slug : '';
		$response->name            = isset( $this->plugin_data["Name"] ) ? $this->plugin_data["Name"] : '';
		$response->version         = isset( $this->plugin_data["Version"] ) ? $this->plugin_data["Version"] : '';
		$response->author          = isset( $this->plugin_data["Author"] ) ? $this->plugin_data["Author"] : '';
		$response->last_updated    = '';
		$response->requires        = isset( $this->plugin_data["RequiresWP"] ) ? $this->plugin_data["RequiresWP"] : '';
		$response->tested          = isset( $this->plugin_data["Tested up to"] ) ? $this->plugin_data["Tested up to"] : '';
		$response->requires_php    = isset( $this->plugin_data["RequiresPHP"] ) ? $this->plugin_data["RequiresPHP"] : '';
		$response->homepage        = isset( $this->plugin_data["PluginURI"] ) ? $this->plugin_data["PluginURI"] : '';

		if ( file_exists( plugin_dir_path( $this->args['file'] ) . 'icon-high.jpg' ) ) {
			$response->icons['high'] = plugin_dir_url( $this->args['file'] ) . 'icon-high.jpg';
		}
		if ( file_exists( plugin_dir_path( $this->args['file'] ) . 'icon-high.jpg' ) ) {
			$response->icons['low'] = plugin_dir_url( $this->args['file'] ) . 'icon-high.jpg';
		}
		if ( file_exists( plugin_dir_path( $this->args['file'] ) . 'banner-high.jpg' ) ) {
			$response->banners['high'] = plugin_dir_url( $this->args['file'] ) . 'banner-high.jpg';
		}
		if ( file_exists( plugin_dir_path( $this->args['file'] ) . 'banner-high.jpg' ) ) {
			$response->banners['low'] = plugin_dir_url( $this->args['file'] ) . 'banner-low.jpg';
		}

		// This is our release download zip file.
		$downloadLink = $zipball_url;
		
		// Include the access token for private GitHub repos.
		if ( ! empty( $this->accessToken ) ) {
			$downloadLink = add_query_arg(
				array( "access_token" => $this->accessToken ),
				$downloadLink
			);
		}
		$response->download_link = $downloadLink;

		// We're going to parse the GitHub markdown release notes, include the parser.
		// require_once( plugin_dir_path( __FILE__ ) . "Parsedown.php" );

		// Create tabs in the lightbox (available sections: 'description', 'installation', 'faq', 'changelog', 'screenshots', 'reviews', 'other_notes').
		$response->sections = $this->get_documentation();

		// Gets the required version of WP if available.
		$matches = null;
		preg_match( "/requires:\s([\d\.]+)/i", $body, $matches );
		if ( ! empty( $matches ) ) {
			if ( is_array( $matches ) ) {
				if ( count( $matches ) > 1 ) {
					$response->requires = $matches[1];
				}
			}
		}
		
		// Gets the tested version of WP if available.
		$matches = null;
		preg_match( "/tested:\s([\d\.]+)/i", $body, $matches );
		if ( ! empty( $matches ) ) {
			if ( is_array( $matches ) ) {
				if ( count( $matches ) > 1 ) {
					$response->tested = $matches[1];
				}
			}
		}
		
		return $response;
	}

	/**
	 * Remove "By Author" from plugin description.
	 */
	public function remove_author_from_description( $result, $action, $args ) {

		// Bail if it is not the current plugin.
		if ( ! empty( $args->slug ) && $args->slug != $this->slug ) {
			return $result;
		}

		$remove = sprintf( ' <cite>' . __( 'By %s.' ) . '</cite>', $result->author );
		$result->sections['description'] = trim( substr( $result->sections['description'], 0, strrpos( $result->sections['description'], $remove ) ) );

		return $result;
	}

}
