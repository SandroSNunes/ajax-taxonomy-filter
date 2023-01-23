<?php

namespace Sandro_Nunes\Ajax_Taxonomy_Filter;

use Sandro_Nunes\Lib\Util;

/**
 * Backend.
 */

class Backend {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->init_hooks();
		return $this;
	}


	/**
	 * Initializate hooks.
	 */
	private function init_hooks() {

		// Enqueue frontend scripts & styles.
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );

	}


	/**
	 * Enqueue frontend scripts & styles.
	 */
	public function admin_enqueue_scripts() {

		$suffix = SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_script( 'atxf-admin', Util::get_active_file_url( 'ajax-taxonomy-filter-admin' . $suffix . '.js' ), [ 'jquery' ], '1.0.0', true );

	}

}
