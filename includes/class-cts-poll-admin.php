<?php
/**
 * Admin customizations for the "poll" post type.
 *
 * @package CTS_Poll
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class CTS_Poll_Admin
 */
class CTS_Poll_Admin {

	/**
	 * Init hooks.
	 */
	public static function init() {
		add_filter( 'manage_poll_posts_columns', array( __CLASS__, 'add_admin_columns' ) );
		add_action( 'manage_poll_posts_custom_column', array( __CLASS__, 'render_admin_columns' ), 10, 2 );
		add_filter( 'manage_edit-poll_sortable_columns', array( __CLASS__, 'sortable_columns' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'sort_by_poll_date' ) );
		add_filter( 'post_row_actions', array( __CLASS__, 'add_shortcode_row_action' ), 10, 2 );
	}

	/**
	 * Add custom columns to the poll list table.
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public static function add_admin_columns( $columns ) {
		$new_columns = array();

		// Insert after title column.
		foreach ( $columns as $key => $label ) {
			$new_columns[ $key ] = $label;
			if ( 'title' === $key ) {
				$new_columns['poll_date']      = __( 'Poll Date', 'cts-poll' );
				$new_columns['poll_status']    = __( 'Status', 'cts-poll' );
				$new_columns['poll_votes']     = __( 'Votes', 'cts-poll' );
				$new_columns['poll_shortcode'] = __( 'Shortcode', 'cts-poll' );
			}
		}

		return $new_columns;
	}

	/**
	 * Render custom column content.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Post ID.
	 */
	public static function render_admin_columns( $column, $post_id ) {
		switch ( $column ) {
			case 'poll_date':
				$date = get_post_meta( $post_id, '_poll_date', true );
				echo esc_html( $date ? $date : '—' );
				break;

			case 'poll_status':
				$active = get_post_meta( $post_id, '_poll_active', true );
				if ( '1' === $active ) {
					echo '<span style="color:#46b450;">' . esc_html__( 'Active', 'cts-poll' ) . '</span>';
				} else {
					echo '<span style="color:#a00;">' . esc_html__( 'Closed', 'cts-poll' ) . '</span>';
				}
				break;

			case 'poll_votes':
				$votes = (int) get_post_meta( $post_id, '_poll_total_votes', true );
				echo esc_html( number_format_i18n( $votes ) );
				break;

			case 'poll_shortcode':
				echo '<code>[daily_poll poll_id="' . esc_attr( $post_id ) . '"]</code>';
				break;
		}
	}

	/**
	 * Make poll_date column sortable.
	 *
	 * @param array $columns Sortable columns.
	 * @return array
	 */
	public static function sortable_columns( $columns ) {
		$columns['poll_date'] = 'poll_date';
		return $columns;
	}

	/**
	 * Sort by poll date meta value.
	 *
	 * @param WP_Query $query The query.
	 */
	public static function sort_by_poll_date( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		$orderby = $query->get( 'orderby' );
		if ( 'poll_date' === $orderby ) {
			$query->set( 'meta_key', '_poll_date' );
			$query->set( 'orderby', 'meta_value' );
		}
	}

	/**
	 * Add a "Copy Shortcode" row action.
	 *
	 * @param array   $actions Existing row actions.
	 * @param WP_Post $post    Current post.
	 * @return array
	 */
	public static function add_shortcode_row_action( $actions, $post ) {
		if ( 'poll' !== $post->post_type ) {
			return $actions;
		}

		$actions['poll_shortcode'] = sprintf(
			'<a href="#" class="cts-poll-copy-shortcode" data-shortcode="[daily_poll poll_id=\'%d\']">%s</a>',
			$post->ID,
			esc_html__( 'Copy Shortcode', 'cts-poll' )
		);

		return $actions;
	}
}