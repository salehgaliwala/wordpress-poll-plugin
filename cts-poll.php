<?php
/**
 * Plugin Name: CTS Daily Poll
 * Plugin URI:  https://example.com/cts-poll
 * Description: Create and manage daily polls. Use [daily_poll] to display the active poll and [poll_results] to show historical results.
 * Version:     1.0.0
 * Author:      CTS
 * Text Domain: cts-poll
 * Domain Path: /languages
 *
 * @package CTS_Poll
 */

defined( 'ABSPATH' ) || exit;

define( 'CTS_POLL_VERSION', '1.0.0' );
define( 'CTS_POLL_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CTS_POLL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Load classes.
require_once CTS_POLL_PLUGIN_DIR . 'includes/class-cts-poll-post-type.php';
require_once CTS_POLL_PLUGIN_DIR . 'includes/class-cts-poll-meta-boxes.php';
require_once CTS_POLL_PLUGIN_DIR . 'includes/class-cts-poll-shortcodes.php';
require_once CTS_POLL_PLUGIN_DIR . 'includes/class-cts-poll-ajax.php';
require_once CTS_POLL_PLUGIN_DIR . 'includes/class-cts-poll-admin.php';

/**
 * Main plugin bootstrap.
 */
final class CTS_Poll {

	/**
	 * Singleton instance.
	 *
	 * @var CTS_Poll
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Hook into WordPress.
	 */
	private function init_hooks() {
		register_activation_hook( __FILE__, array( __CLASS__, 'activate' ) );
		register_deactivation_hook( __FILE__, array( __CLASS__, 'deactivate' ) );

		add_action( 'plugins_loaded', array( $this, 'load_classes' ) );
	}

	/**
	 * Load plugin classes and enqueues.
	 */
	public function load_classes() {
		CTS_Poll_Post_Type::init();
		CTS_Poll_Meta_Boxes::init();
		CTS_Poll_Shortcodes::init();
		CTS_Poll_Ajax::init();
		CTS_Poll_Admin::init();

		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_frontend_assets' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
	}

	/**
	 * Enqueue front-end styles and scripts.
	 */
	public static function enqueue_frontend_assets() {
		wp_enqueue_style(
			'cts-poll-styles',
			CTS_POLL_PLUGIN_URL . 'assets/css/poll-styles.css',
			array(),
			CTS_POLL_VERSION
		);

		wp_enqueue_script(
			'cts-poll-scripts',
			CTS_POLL_PLUGIN_URL . 'assets/js/poll-scripts.js',
			array( 'jquery' ),
			CTS_POLL_VERSION,
			true
		);

		wp_localize_script(
			'cts-poll-scripts',
			'ctsPollVars',
			array(
				'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
				'nonce'            => wp_create_nonce( 'cts_poll_nonce' ),
				'noOptionSelected' => __( 'Please select an option.', 'cts-poll' ),
				'errorMessage'     => __( 'An error occurred. Please try again.', 'cts-poll' ),
				'noVotesMessage'   => __( 'No votes yet.', 'cts-poll' ),
				'totalVotesText'   => __( 'Total votes:', 'cts-poll' ),
			)
		);
	}

	/**
	 * Enqueue admin assets for the "Copy Shortcode" functionality.
	 *
	 * @param string $hook The current admin page.
	 */
	public static function enqueue_admin_assets( $hook ) {
		if ( 'edit.php' !== $hook || ! isset( $_GET['post_type'] ) || 'poll' !== $_GET['post_type'] ) {
			return;
		}

		wp_enqueue_script(
			'cts-poll-admin',
			CTS_POLL_PLUGIN_URL . 'assets/js/poll-scripts.js',
			array( 'jquery' ),
			CTS_POLL_VERSION,
			true
		);
	}

	/**
	 * On activation – create DB table and flush rewrite rules.
	 */
	public static function activate() {
		self::create_votes_table();
		flush_rewrite_rules();
	}

	/**
	 * On deactivation – flush rewrite rules.
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}

	/**
	 * Create the custom votes table.
	 */
	public static function create_votes_table() {
		global $wpdb;

		$table_name      = $wpdb->prefix . 'cts_poll_votes';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
			id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			poll_id BIGINT(20) UNSIGNED NOT NULL,
			option_index INT(11) NOT NULL,
			voter_ip VARCHAR(64) DEFAULT '',
			user_id BIGINT(20) UNSIGNED DEFAULT 0,
			vote_date DATETIME DEFAULT CURRENT_TIMESTAMP,
			INDEX poll_id_idx (poll_id),
			INDEX poll_voter_idx (poll_id, voter_ip)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}
}

/**
 * Start the plugin.
 */
CTS_Poll::instance();