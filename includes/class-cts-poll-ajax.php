<?php
/**
 * AJAX handlers for vote submission and results retrieval.
 *
 * @package CTS_Poll
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class CTS_Poll_Ajax
 */
class CTS_Poll_Ajax {

	/**
	 * Init hooks.
	 */
	public static function init() {
		add_action( 'wp_ajax_cts_poll_vote', array( __CLASS__, 'handle_vote' ) );
		add_action( 'wp_ajax_nopriv_cts_poll_vote', array( __CLASS__, 'handle_vote' ) );

		add_action( 'wp_ajax_cts_poll_results', array( __CLASS__, 'handle_results' ) );
		add_action( 'wp_ajax_nopriv_cts_poll_results', array( __CLASS__, 'handle_results' ) );
	}

	/**
	 * Handle vote submission.
	 */
	public static function handle_vote() {
		// Verify nonce.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'cts_poll_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'cts-poll' ) ) );
		}

		$poll_id      = isset( $_POST['poll_id'] ) ? (int) $_POST['poll_id'] : 0;
		$option_index = isset( $_POST['option_index'] ) ? (int) $_POST['option_index'] : -1;

		if ( $poll_id <= 0 || $option_index < 0 ) {
			wp_send_json_error( array( 'message' => __( 'Invalid poll or option.', 'cts-poll' ) ) );
		}

		// Verify poll exists and is active.
		$poll = get_post( $poll_id );
		if ( ! $poll || 'poll' !== $poll->post_type || 'publish' !== $poll->post_status ) {
			wp_send_json_error( array( 'message' => __( 'Poll not found.', 'cts-poll' ) ) );
		}

		$is_active = get_post_meta( $poll_id, '_poll_active', true );
		if ( '1' !== $is_active ) {
			wp_send_json_error( array( 'message' => __( 'This poll is no longer accepting votes.', 'cts-poll' ) ) );
		}

		// Verify option is valid.
		$options = get_post_meta( $poll_id, '_poll_options', true );
		if ( ! is_array( $options ) || ! isset( $options[ $option_index ] ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid option selected.', 'cts-poll' ) ) );
		}

		// Check duplicate vote.
		if ( CTS_Poll_Shortcodes::has_user_voted( $poll_id ) ) {
			wp_send_json_error( array( 'message' => __( 'You have already voted on this poll.', 'cts-poll' ) ) );
		}

		// Basic rate limiting: check if this IP voted within the last 10 seconds.
		global $wpdb;
		$table_name = $wpdb->prefix . 'cts_poll_votes';
		$voter_ip   = CTS_Poll_Shortcodes::get_user_ip();

		$recent_vote = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table_name} WHERE voter_ip = %s AND vote_date > DATE_SUB(NOW(), INTERVAL 10 SECOND)",
				$voter_ip
			)
		);

		if ( (int) $recent_vote > 5 ) {
			wp_send_json_error( array( 'message' => __( 'You are voting too quickly. Please slow down.', 'cts-poll' ) ) );
		}

		// Insert vote.
		$user_id = is_user_logged_in() ? get_current_user_id() : 0;

		$wpdb->insert(
			$table_name,
			array(
				'poll_id'      => $poll_id,
				'option_index' => $option_index,
				'voter_ip'     => $voter_ip,
				'user_id'      => $user_id,
			),
			array( '%d', '%d', '%s', '%d' )
		);

		// Update total votes meta.
		$new_total = (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(*) FROM {$table_name} WHERE poll_id = %d", $poll_id )
		);
		update_post_meta( $poll_id, '_poll_total_votes', $new_total );

		// Clear cached results.
		delete_transient( 'cts_poll_results_' . $poll_id );

		// Set cookie for duplicate prevention (expires in 30 days).
		setcookie( 'cts_poll_voted_' . $poll_id, '1', time() + 30 * DAY_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );

		// Return updated results.
		$results    = CTS_Poll_Shortcodes::get_vote_counts( $poll_id );
		$total      = $new_total;
		$opts       = get_post_meta( $poll_id, '_poll_options', true );
		$data       = array();
		$vote_map   = array();

		foreach ( $results as $r ) {
			$vote_map[ $r->option_index ] = (int) $r->votes;
		}

		foreach ( $opts as $index => $label ) {
			$votes  = isset( $vote_map[ $index ] ) ? $vote_map[ $index ] : 0;
			$pct    = $total > 0 ? round( ( $votes / $total ) * 100 ) : 0;
			$data[] = array(
				'option'      => $label,
				'votes'       => $votes,
				'percent'     => $pct,
			);
		}

		wp_send_json_success(
			array(
				'results'     => $data,
				'total_votes' => $total,
			)
		);
	}

	/**
	 * Handle results retrieval (for AJAX refresh).
	 */
	public static function handle_results() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'cts_poll_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'cts-poll' ) ) );
		}

		$poll_id = isset( $_POST['poll_id'] ) ? (int) $_POST['poll_id'] : 0;
		if ( $poll_id <= 0 ) {
			wp_send_json_error( array( 'message' => __( 'Invalid poll.', 'cts-poll' ) ) );
		}

		$results = CTS_Poll_Shortcodes::get_vote_counts( $poll_id );
		$total   = (int) get_post_meta( $poll_id, '_poll_total_votes', true );
		$opts    = get_post_meta( $poll_id, '_poll_options', true );

		if ( empty( $opts ) ) {
			$opts = array();
		}

		$vote_map = array();
		foreach ( $results as $r ) {
			$vote_map[ $r->option_index ] = (int) $r->votes;
		}

		$data = array();
		foreach ( $opts as $index => $label ) {
			$votes  = isset( $vote_map[ $index ] ) ? $vote_map[ $index ] : 0;
			$pct    = $total > 0 ? round( ( $votes / $total ) * 100 ) : 0;
			$data[] = array(
				'option'  => $label,
				'votes'   => $votes,
				'percent' => $pct,
			);
		}

		wp_send_json_success(
			array(
				'results'     => $data,
				'total_votes' => $total,
			)
		);
	}
}