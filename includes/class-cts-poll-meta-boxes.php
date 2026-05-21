<?php
/**
 * Meta boxes for the "poll" post type.
 *
 * @package CTS_Poll
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class CTS_Poll_Meta_Boxes
 */
class CTS_Poll_Meta_Boxes {

	/**
	 * Init hooks.
	 */
	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
		add_action( 'save_post_poll', array( __CLASS__, 'save_meta_boxes' ), 10, 2 );
	}

	/**
	 * Register the meta box.
	 */
	public static function add_meta_boxes() {
		add_meta_box(
			'cts_poll_settings',
			__( 'Poll Settings', 'cts-poll' ),
			array( __CLASS__, 'render_meta_box' ),
			'poll',
			'normal',
			'high'
		);
	}

	/**
	 * Render the meta box.
	 *
	 * @param WP_Post $post Current post object.
	 */
	public static function render_meta_box( $post ) {
		wp_nonce_field( 'cts_poll_save_meta', 'cts_poll_meta_nonce' );

		$question     = get_post_meta( $post->ID, '_poll_question', true );
		$options      = get_post_meta( $post->ID, '_poll_options', true );
		$poll_date    = get_post_meta( $post->ID, '_poll_date', true );
		$is_active    = get_post_meta( $post->ID, '_poll_active', true );

		if ( empty( $options ) || ! is_array( $options ) ) {
			$options = array( '', '', '' );
		}

		if ( empty( $poll_date ) ) {
			$poll_date = current_time( 'Y-m-d' );
		}
		?>
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="cts_poll_question"><?php esc_html_e( 'Poll Question', 'cts-poll' ); ?></label>
				</th>
				<td>
					<input type="text" id="cts_poll_question" name="cts_poll_question"
						value="<?php echo esc_attr( $question ); ?>" class="large-text" />
					<p class="description"><?php esc_html_e( 'The question displayed to voters.', 'cts-poll' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label><?php esc_html_e( 'Poll Options', 'cts-poll' ); ?></label>
				</th>
				<td id="cts-poll-options-wrapper">
					<?php foreach ( $options as $index => $option ) : ?>
						<div class="cts-poll-option-row">
							<input type="text" name="cts_poll_options[]"
								value="<?php echo esc_attr( $option ); ?>"
								class="regular-text" placeholder="<?php esc_attr_e( 'Option', 'cts-poll' ); ?>" />
							<button type="button" class="button cts-poll-remove-option">-</button>
						</div>
					<?php endforeach; ?>
					<button type="button" class="button cts-poll-add-option">+ <?php esc_html_e( 'Add Option', 'cts-poll' ); ?></button>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="cts_poll_date"><?php esc_html_e( 'Poll Date', 'cts-poll' ); ?></label>
				</th>
				<td>
					<input type="date" id="cts_poll_date" name="cts_poll_date"
						value="<?php echo esc_attr( $poll_date ); ?>" />
					<p class="description"><?php esc_html_e( 'The date this poll is tied to. Only one poll per date.', 'cts-poll' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<?php esc_html_e( 'Active', 'cts-poll' ); ?>
				</th>
				<td>
					<label>
						<input type="checkbox" name="cts_poll_active" value="1"
							<?php checked( $is_active, '1' ); ?> />
						<?php esc_html_e( 'Accept votes for this poll', 'cts-poll' ); ?>
					</label>
				</td>
			</tr>
		</table>

		<script>
		jQuery(document).ready(function($) {
			$('.cts-poll-add-option').on('click', function() {
				var row = $('<div class="cts-poll-option-row"><input type="text" name="cts_poll_options[]" class="regular-text" placeholder="<?php esc_attr_e( 'Option', 'cts-poll' ); ?>" /><button type="button" class="button cts-poll-remove-option">-</button></div>');
				$('#cts-poll-options-wrapper .cts-poll-option-row:last').after(row);
			});

			$('#cts-poll-options-wrapper').on('click', '.cts-poll-remove-option', function() {
				if ($('#cts-poll-options-wrapper .cts-poll-option-row').length > 1) {
					$(this).closest('.cts-poll-option-row').remove();
				}
			});
		});
		</script>
		<style>
		.cts-poll-option-row {
			margin-bottom: 6px;
			display: flex;
			align-items: center;
			gap: 6px;
		}
		.cts-poll-option-row .regular-text {
			flex: 1;
		}
		</style>
		<?php
	}

	/**
	 * Save meta box data.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public static function save_meta_boxes( $post_id, $post ) {
		// Security checks.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! isset( $_POST['cts_poll_meta_nonce'] ) || ! wp_verify_nonce( $_POST['cts_poll_meta_nonce'], 'cts_poll_save_meta' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Save question.
		if ( isset( $_POST['cts_poll_question'] ) ) {
			update_post_meta( $post_id, '_poll_question', sanitize_text_field( $_POST['cts_poll_question'] ) );
		}

		// Save options.
		if ( isset( $_POST['cts_poll_options'] ) && is_array( $_POST['cts_poll_options'] ) ) {
			$options = array_map( 'sanitize_text_field', $_POST['cts_poll_options'] );
			$options = array_values( array_filter( $options, 'strlen' ) );
			update_post_meta( $post_id, '_poll_options', $options );
		}

		// Save date.
		if ( isset( $_POST['cts_poll_date'] ) ) {
			update_post_meta( $post_id, '_poll_date', sanitize_text_field( $_POST['cts_poll_date'] ) );
		}

		// Save active status.
		$active = isset( $_POST['cts_poll_active'] ) ? '1' : '0';
		update_post_meta( $post_id, '_poll_active', $active );
	}
}