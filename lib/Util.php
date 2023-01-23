<?php

namespace Sandro_Nunes\Lib;

/**
 * Util.
 */

class Util {

	/**
	 * Set constants.
	 * 
	 * @param array $constants An array with key name and values for the corresponding constants.
	 *
	 * @return void.
	 */

	public static function constants( $constants = [] ) {

		foreach ( $constants as $name => $value ) {
		
			if ( ! defined( $name ) ) {
				define( $name, $value );
			}
					
		}
		
	}


	/**
	 * Includes a file within the plugin.
	 * 
	 * @param string|array $files
	 * 
	 * @return void.
	 */

	public static function include( $files = '' ) {
		
		if ( ! is_array( $files ) ) {
			$files = (array) $files;
		}
		
		foreach ( $files as $file_path ) {

			if ( file_exists( $file_path ) ) {
				include_once( $file_path );
			}

		}
		
	}


	/**
	 * Locate if the template is either on child Theme, parent theme or in the plugin.
	 * 
	 * @param string $template_names  Template names.
	 * 
	 * @return string Path of the located file.
	 */
	public static function locate_template( $template_names ) {

		$located = '';

		$plugin_basename = substr( plugin_basename( __DIR__ ), 0, strpos( plugin_basename( __DIR__ ), '/' ) );

		$basenames = apply_filters( 'template_basenames', [
			STYLESHEETPATH . '/' . $plugin_basename,
			WP_PLUGIN_DIR . '/' . $plugin_basename . '/templates',
			WP_PLUGIN_DIR . '/' . $plugin_basename . '/shortcodes/views',
		] );

		foreach ( (array) $basenames as $basename ) {
			$basename = untrailingslashit( $basename );
			foreach ( (array) $template_names as $template_name ) {
				$template_name = ltrim( $template_name, '/\\' );
				if ( $template_name && file_exists( $basename . '/' . $template_name ) ) {
					$located = $basename . '/' . $template_name;
					break 2;
				}
			}
		}

		return $located;
	}


	/**
	 * Return the plugin file url if is either on css or assets/css of the child theme, parent theme or utimately on the assets/css of the plugin.
	 * 
	 * @param string $file File name.
	 * 
	 * @return string Url of the active file.
	 */
	public static function get_active_file_url( $file ) {

		$file_type = strpos( $file, '.css' ) ? 'css' : 'js';

		// Child theme or Main theme.
		if ( file_exists( trailingslashit( get_stylesheet_directory() ) . 'assets/' . $file_type . '/' . $file ) ) {
			return trailingslashit( get_stylesheet_directory_uri() ) . 'assets/' . $file_type . '/' . $file;
		} elseif ( file_exists( trailingslashit( get_stylesheet_directory() ) . $file_type . '/' . $file ) ) {
			return trailingslashit( get_stylesheet_directory_uri() ) . $file_type . '/' . $file;
		}

		// Main theme.
		if ( file_exists( trailingslashit( get_template_directory() ) . 'assets/' . $file_type . '/' . $file ) ) {
			return trailingslashit( get_template_directory_uri() ) . 'assets/' . $file_type . '/' . $file;
		}elseif ( file_exists( trailingslashit( get_template_directory() ) . $file_type . '/' . $file ) ) {
			return trailingslashit( get_template_directory_uri() ) . $file_type . '/' . $file;
		}

		// Plugin.
		return trailingslashit( ATXF_URL ) . 'assets/' . $file_type . '/' . $file;

	}


	/**
	 * Get uploads directory path.
	 * 
	 * @param string $subdir Sub directory.
	 * 
	 * @return string Path of the uploads directory.
	 */
	public static function get_uploads_dir( $subdir = '' ) {
		return get_uploads_option( $subdir, 'basedir' );
		return $uploads_dir;
	}


	/**
	 * Get uploads directory url.
	 *
	 * @param string $subdir Sub directory.
	 * 
	 * @return string Url of the uploads directory.
	 */
	public static function get_uploads_url( $subdir = '' ) {
		return get_uploads_option( $subdir, 'baseurl' );
		return $uploads_url;
	}

	
	/**
	 * Get the uploads directory path or url.
	 * 
	 * @param string $subdir Sub directory.
	 * @param string $option What should the function return: path, url, subdir, basedir, baseurl, error.
	 * 
	 * @return string Path or Url of the uploads directory.
	 */
	public static function get_uploads_option( $subdir = '', $option = 'basedir' ) {
		$plugin_basename = substr( plugin_basename( __DIR__ ), 0, strpos( plugin_basename( __DIR__ ), '/' ) );

		$upload_dir_url = wp_upload_dir();
		$upload_dir_url = $upload_dir_url[ $option ];
		$upload_dir_url = trailingslashit( $upload_dir_url ) . $plugin_basename . '/';
		
		if ( $subdir !== '' ) {
			$upload_dir_url = $upload_dir_url . trim( $subdir, '/\\' ) . '/';
		}
		
		return $upload_dir_url;
	}

}
