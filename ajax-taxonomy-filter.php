<?php
/**
 * Plugin Name: Ajax Taxonomy Filter
 * Version: 1.0.0
 * Description: Filter archive lists by taxonomy.
 * Author: Sandro Nunes
 * Author URI: https://sandronunes.com
 * Text Domain: atxf
 * Domain Path: /languages/
 * Requires at least: 5.3
 * Tested up to: 6.0.2
 * Requires PHP: 5.6
  */

namespace Sandro_Nunes\Ajax_Taxonomy_Filter;

use Sandro_Nunes\Lib\Util;
use Sandro_Nunes\Lib\Plugin;

defined( 'ABSPATH' ) || exit;

// Include autoloader.
require_once __DIR__ . '/vendor/autoload.php';

/**
 * Ajax_Taxonomy_Filter.
 */
class Ajax_Taxonomy_Filter {

	protected static $_instance = null;
	private $args               = [];
	public $core                = null;
	public $backend             = null;
	public $frontend            = null;

	/**
	 * Ensures only one instance is loaded or can be loaded.
	 */
	public static function instance( $args = [] ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $args );
		}
		return self::$_instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct( $args = [] ) {
		$this->args = $args;
	}

	/**
	 * Initialize.
	 */
	public function initialize() {
		$this->init_core();
		$this->includes();
		$this->instantiate();
	}

	/**
	 * Init plugin core.
	 */
	private function init_core() {
		$this->core = new Plugin( [ 'file' => __FILE__ ] );
	}

	/**
	 * Includes.
	 */
	public function includes() {

		Util::include( [
			ATXF_DIR . 'src/functions-atxf.php',
			ATXF_DIR . 'walkers/ajax-taxonomy-filter-walker.php',
			ATXF_DIR . 'shortcodes/ajax-taxonomy-filter.php',
			ATXF_DIR . 'widgets/ajax-taxonomy-filter-widget.php',
		] );

	}

	/**
	 * Instantiate.
	 */
	private function instantiate() {

		if ( is_admin() ) {
			$this->backend = new Backend();
		}

		if ( ! is_admin() || wp_doing_ajax() ) {
			$this->frontend = new Frontend();
		}

	}

}

/**
 * Unique access to instance of Ajax_Taxonomy_Filter class.
 * 
 * @return object Object instance.
 */
function Ajax_Taxonomy_Filter() {
	return Ajax_Taxonomy_Filter::instance()->initialize();
}

/**
 * Initializate Plugin.
 */
Ajax_Taxonomy_Filter();
