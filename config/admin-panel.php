<?php
/**
 * Admin panel configuration strucutre.
 */

defined( 'ATXF' ) || exit;

$config = [

    'general' => [
        'label'    => __( 'Ajax Taxonomy Filter', 'atxf' ),
        'sections' => [

            'general' => [
                'title'       =>  '',
                'description' => '',
                'fields'      => [

					'atxf_ajax_content_element' => [
                        'title'       => __( 'HTML content element', 'atxf' ),
                        'description' => __( 'The HTML element, id or class where the ajax will reload the content. Ex: main, #primary, .products, ...', 'atxf' ),
                        'type'        => 'text',
                        'default'     => 'main',
					],

					'atxf_ajax_active' => [
                        'title'       => __( 'Use ajax', 'atxf' ),
                        'description' => __( 'Whether to use ajax on the filters.', 'atxf' ),
                        'type'        => 'checkbox',
                        'default'     => '1',
					],

				],
			],

		],
	],
	
];
