<?php
/**
 * Register block types for both My Courses and All Courses shortcode.
 * 
 * @since 1.2
 */
function pmpro_courses_register_block_type() {
	register_block_type( 'pmpro-affiliates/pmpro-affiliates-report', 
		array(
        	'editor_script'   => 'pmpro_affiliates_block_report',
			'render_callback' => 'pmpro_affiliates_report_shortcode',
			'attributes' => array(
				'back_link' => array(
					'type' => 'boolean',
					'default' => true,
				),
				'export_csv' => array(
					'type' => 'boolean',
					'default' => true,
				),
				'help' => array(
					'type' => 'boolean',
					'default' => true,
				),
				'code' => array(
					'type' => 'boolean',
					'default' => false,
				),
				'subid' => array(
					'type' => 'boolean',
					'default' => false,
				),
				'name' => array(
					'type' => 'boolean',
					'default' => false,
				),
				'user_login' => array(
					'type' => 'boolean',
					'default' => true,
				),
				'date' => array(
					'type' => 'boolean',
					'default' => true,
				),
				'membership_level' => array(
					'type' => 'boolean',
					'default' => true,
				),
				'show_commission' => array(
					'type' => 'boolean',
					'default' => true,
				),
				'total' => array(
					'type' => 'boolean',
					'default' => true,
				)
			)
		)
	);
}
add_action( 'init', 'pmpro_courses_register_block_type' );

/**
 * Enqueue the Block Scripts for both blocks.
 *
 * @since 1.2
 */
function pmpro_courses_block_scripts() {
	wp_enqueue_script(
		'pmpro_affiliates_block_report',
		plugins_url( 'blocks/build/pmpro_affiliates_report/index.js', __DIR__ ),
		plugins_url( 'blocks/build/pmpro_affiliates_report/index.asset.php')
	);

}
add_action( 'enqueue_block_editor_assets', 'pmpro_courses_block_scripts' );