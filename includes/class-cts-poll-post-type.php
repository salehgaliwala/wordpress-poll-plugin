<?php
/**
 * Register the "poll" Custom Post Type.
 *
 * @package CTS_Poll
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class CTS_Poll_Post_Type
 */
class CTS_Poll_Post_Type {

	/**
	 * Init hooks.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
	}

	/**
	 * Register the poll CPT.
	 */
	public static function register_post_type() {
		$labels = array(
			'name'               => __( 'Polls', 'cts-poll' ),
			'singular_name'      => __( 'Poll', 'cts-poll' ),
			'add_new'            => __( 'Add New Poll', 'cts-poll' ),
			'add_new_item'       => __( 'Add New Poll', 'cts-poll' ),
			'edit_item'          => __( 'Edit Poll', 'cts-poll' ),
			'new_item'           => __( 'New Poll', 'cts-poll' ),
			'view_item'          => __( 'View Poll', 'cts-poll' ),
			'search_items'       => __( 'Search Polls', 'cts-poll' ),
			'not_found'          => __( 'No polls found', 'cts-poll' ),
			'not_found_in_trash' => __( 'No polls found in Trash', 'cts-poll' ),
			'all_items'          => __( 'All Polls', 'cts-poll' ),
			'menu_name'          => __( 'Daily Polls', 'cts-poll' ),
		);

		$args = array(
			'labels'              => $labels,
			'public'              => true,
			'publicly_queryable'  => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'query_var'           => false,
			'rewrite'             => false,
			'capability_type'     => 'post',
			'has_archive'         => false,
			'hierarchical'        => false,
			'menu_position'       => 20,
			'menu_icon'           => 'dashicons-chart-bar',
			'supports'            => array( 'title', 'editor' ),
			'exclude_from_search' => true,
			'show_in_rest'        => false,
		);

		register_post_type( 'poll', $args );
	}
}