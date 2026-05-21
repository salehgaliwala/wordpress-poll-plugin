<?php
/**
 * Shortcodes: [daily_poll] and [poll_results].
 *
 * @package CTS_Poll
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class CTS_Poll_Shortcodes
 */
class CTS_Poll_Shortcodes {

	/**
	 * Init hooks.
	 */
	public static function init() {
		add_shortcode( 'daily_poll', array( __CLASS__, 'render_daily_poll' ) );
		add_shortcode( 'poll_results', array( __CLASS__, 'render_poll_results' ) );
	}

	/**
	 * Get the active poll for today (or a specific poll by ID).
	 *
	 * @param int $poll_id Optional specific poll ID.
	 * @return WP_Post|null
	 */
	public static function get_active_poll( $poll_id = 0 ) {
		if ( $poll_id > 0 ) {
			$poll = get_post( $poll_id );
			if ( $poll && 'poll' === $poll->post_type && 'publish' === $poll->post_status ) {
				return $poll;
			}
			return null;
		}

		// Try today's date first.
		$today = current_time( 'Y-m-d' );
		$args  = array(
			'post_type'      => 'poll',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'meta_query'     => array(
				array(
					'key'   => '_poll_date',
					'value' => $today,
				),
				array(
					'key'   => '_poll_active',
					'value' => '1',
				),
			),
		);

		$polls = get_posts( $args );
		if ( ! empty( $polls ) ) {
			return $polls[0];
		}

		// Fallback: most recent active poll.
		$args = array(
			'post_type'      => 'poll',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'meta_key'       => '_poll_date',
			'orderby'        => 'meta_value',
			'order'          => 'DESC',
			'meta_query'     => array(
				array(
					'key'   => '_poll_active',
					'value' => '1',
				),
			),
		);

		$polls = get_posts( $args );
		return ! empty( $polls ) ? $polls[0] : null;
	}

	/**
	 * Render [daily_poll] shortcode.
	 *
	 * @param array  $atts    Shortcode attributes.
	 * @param string $content Enclosed content.
	 * @return string
	 */
	public static function render_daily_poll( $atts, $content = '' ) {
		$atts = shortcode_atts(
			array(
				'poll_id'      => 0,
				'show_results' => 'after_vote',
				'title'        => '',
			),
			$atts,
			'daily_poll'
		);

		$poll = self::get_active_poll( (int) $atts['poll_id'] );
		if ( ! $poll ) {
			return '<p>' . esc_html__( 'No active poll available.', 'cts-poll' ) . '</p>';
		}

		$poll_id      = $poll->ID;
		$question     = get_post_meta( $poll_id, '_poll_question', true );
		$options      = get_post_meta( $poll_id, '_poll_options', true );
		$show_results = ! empty( $atts['show_results'] ) ? $atts['show_results'] : 'after_vote';

		if ( empty( $question ) ) {
			$question = $poll->post_title;
		}
		if ( empty( $options ) || ! is_array( $options ) ) {
			return '<p>' . esc_html__( 'This poll has no options configured.', 'cts-poll' ) . '</p>';
		}

		$title_html = ! empty( $atts['title'] )
			? '<h3 class="cts-poll-title">' . esc_html( $atts['title'] ) . '</h3>'
			: '<h3 class="cts-poll-title">' . esc_html( $question ) . '</h3>';

		ob_start();
		?>
		<div class="cts-poll-widget" data-poll-id="<?php echo esc_attr( $poll_id ); ?>"
			data-show-results="<?php echo esc_attr( $show_results ); ?>">

			<?php echo $title_html; ?>

			<?php if ( self::has_user_voted( $poll_id ) && 'after_vote' === $show_results ) : ?>
				<div class="cts-poll-results" data-poll-id="<?php echo esc_attr( $poll_id ); ?>">
					<?php self::render_results_html( $poll_id, $options ); ?>
				</div>
			<?php elseif ( 'always' === $show_results ) : ?>
				<div class="cts-poll-results" data-poll-id="<?php echo esc_attr( $poll_id ); ?>">
					<?php self::render_results_html( $poll_id, $options ); ?>
				</div>
			<?php else : ?>
				<form class="cts-poll-form" data-poll-id="<?php echo esc_attr( $poll_id ); ?>">
					<div class="cts-poll-options">
						<?php foreach ( $options as $index => $option ) : ?>
							<label class="cts-poll-option">
								<input type="radio" name="cts_poll_option" value="<?php echo esc_attr( $index ); ?>" />
								<span><?php echo esc_html( $option ); ?></span>
							</label>
						<?php endforeach; ?>
					</div>
					<button type="submit" class="cts-poll-vote-btn">
						<?php esc_html_e( 'Vote', 'cts-poll' ); ?>
					</button>
					<div class="cts-poll-message" style="display:none;"></div>
				</form>
				<div class="cts-poll-results" data-poll-id="<?php echo esc_attr( $poll_id ); ?>" style="display:none;">
					<?php self::render_results_html( $poll_id, $options ); ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render [poll_results] shortcode.
	 *
	 * @param array  $atts    Shortcode attributes.
	 * @param string $content Enclosed content.
	 * @return string
	 */
	public static function render_poll_results( $atts, $content = '' ) {
		$atts = shortcode_atts(
			array(
				'count' => 10,
			),
			$atts,
			'poll_results'
		);

		$per_page = max( 1, (int) $atts['count'] );
		$paged    = max( 1, get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1 );

		$args = array(
			'post_type'      => 'poll',
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
			'paged'          => $paged,
			'meta_key'       => '_poll_date',
			'orderby'        => 'meta_value',
			'order'          => 'DESC',
			'meta_query'     => array(
				array(
					'key'   => '_poll_active',
					'value' => '0',
				),
			),
		);

		$query = new WP_Query( $args );

		if ( ! $query->have_posts() ) {
			return '<p>' . esc_html__( 'No completed polls found.', 'cts-poll' ) . '</p>';
		}

		ob_start();
		?>
		<table class="cts-poll-history-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Date', 'cts-poll' ); ?></th>
					<th><?php esc_html_e( 'Question', 'cts-poll' ); ?></th>
					<th><?php esc_html_e( 'Winner', 'cts-poll' ); ?></th>
					<th><?php esc_html_e( 'Total Votes', 'cts-poll' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php while ( $query->have_posts() ) : $query->the_post(); ?>
					<?php
					$poll_id   = get_the_ID();
					$question  = get_post_meta( $poll_id, '_poll_question', true );
					$options   = get_post_meta( $poll_id, '_poll_options', true );
					$poll_date = get_post_meta( $poll_id, '_poll_date', true );
					$results   = self::get_vote_counts( $poll_id );

					$total_votes = (int) get_post_meta( $poll_id, '_poll_total_votes', true );
					$winner      = '—';
					$max_votes   = 0;

					if ( ! empty( $results ) ) {
						foreach ( $results as $r ) {
							if ( $r->votes > $max_votes ) {
								$max_votes = $r->votes;
								$idx       = $r->option_index;
								$winner    = isset( $options[ $idx ] ) ? $options[ $idx ] . ' (' . $r->votes . ')' : '—';
							}
						}
					}
					?>
					<tr>
						<td><?php echo esc_html( $poll_date ); ?></td>
						<td><?php echo esc_html( $question ? $question : get_the_title() ); ?></td>
						<td><?php echo esc_html( $winner ); ?></td>
						<td><?php echo esc_html( number_format_i18n( $total_votes ) ); ?></td>
					</tr>
				<?php endwhile; ?>
			</tbody>
		</table>

		<div class="cts-poll-pagination">
			<?php
			echo paginate_links(
				array(
					'total'   => $query->max_num_pages,
					'current' => $paged,
				)
			);
			?>
		</div>
		<?php
		wp_reset_postdata();
		return ob_get_clean();
	}

	/**
	 * Check if the current user has already voted on a poll.
	 *
	 * @param int $poll_id Poll ID.
	 * @return bool
	 */
	public static function has_user_voted( $poll_id ) {
		global $wpdb;

		// Cookie check (client-side deterrent).
		if ( isset( $_COOKIE[ 'cts_poll_voted_' . $poll_id ] ) ) {
			return true;
		}

		$table_name = $wpdb->prefix . 'cts_poll_votes';
		$voter_ip   = self::get_user_ip();

		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();
			$count   = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$table_name} WHERE poll_id = %d AND ( user_id = %d OR voter_ip = %s )",
					$poll_id,
					$user_id,
					$voter_ip
				)
			);
		} else {
			$count = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$table_name} WHERE poll_id = %d AND voter_ip = %s",
					$poll_id,
					$voter_ip
				)
			);
		}

		return (int) $count > 0;
	}

	/**
	 * Get vote counts for a poll.
	 *
	 * @param int $poll_id Poll ID.
	 * @return array
	 */
	public static function get_vote_counts( $poll_id ) {
		global $wpdb;

		$cache_key = 'cts_poll_results_' . $poll_id;
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		$table_name = $wpdb->prefix . 'cts_poll_votes';
		$results    = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT option_index, COUNT(*) as votes FROM {$table_name} WHERE poll_id = %d GROUP BY option_index ORDER BY option_index ASC",
				$poll_id
			)
		);

		set_transient( $cache_key, $results, 5 * MINUTE_IN_SECONDS );
		return $results;
	}

	/**
	 * Helper: render results HTML for a poll.
	 *
	 * @param int   $poll_id Poll ID.
	 * @param array $options Poll options.
	 */
	private static function render_results_html( $poll_id, $options ) {
		$results     = self::get_vote_counts( $poll_id );
		$total_votes = (int) get_post_meta( $poll_id, '_poll_total_votes', true );

		if ( empty( $results ) ) :
			?>
			<p class="cts-poll-no-votes"><?php esc_html_e( 'No votes yet.', 'cts-poll' ); ?></p>
			<?php
		else :
			$vote_map = array();
			foreach ( $results as $r ) {
				$vote_map[ $r->option_index ] = (int) $r->votes;
			}
			?>
			<div class="cts-poll-results-bars">
				<?php foreach ( $options as $index => $option ) : ?>
					<?php
					$votes  = isset( $vote_map[ $index ] ) ? $vote_map[ $index ] : 0;
					$pct    = $total_votes > 0 ? round( ( $votes / $total_votes ) * 100 ) : 0;
					?>
					<div class="cts-poll-bar-row">
						<span class="cts-poll-bar-label"><?php echo esc_html( $option ); ?></span>
						<div class="cts-poll-bar-track">
							<div class="cts-poll-bar-fill" style="width:<?php echo esc_attr( $pct ); ?>%;"></div>
						</div>
						<span class="cts-poll-bar-stats"><?php echo esc_html( $votes . ' (' . $pct . '%)' ); ?></span>
					</div>
				<?php endforeach; ?>
			</div>
			<p class="cts-poll-total-text">
				<?php echo esc_html( sprintf( __( 'Total votes: %s', 'cts-poll' ), number_format_i18n( $total_votes ) ) ); ?>
			</p>
			<?php
		endif;
	}

	/**
	 * Get the visitor's IP address.
	 *
	 * @return string
	 */
	public static function get_user_ip() {
		$ip = '';
		if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ips = explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] );
			$ip  = trim( $ips[0] );
		} elseif ( ! empty( $_SERVER['HTTP_X_REAL_IP'] ) ) {
			$ip = $_SERVER['HTTP_X_REAL_IP'];
		} elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = $_SERVER['REMOTE_ADDR'];
		}
		return sanitize_text_field( $ip );
	}
}