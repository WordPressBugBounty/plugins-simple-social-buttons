<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Admin Bar purge caches for Simple Social Buttons.
 *
 * @package SimpleSocialButtons
 * @since 7.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin bar menu and purge handlers.
 */
class Ssb_Admin_Bar_Purge {

	/**
	 * Transient key for admin notices.
	 *
	 * @var string
	 */
	const NOTICE_TRANSIENT = 'ssb_purge_admin_notice';

	/**
	 * Transient key for purge-all batch state.
	 *
	 * @var string
	 */
	const PURGE_ALL_STATE_TRANSIENT = 'ssb_purge_all_state';

	/**
	 * Notice TTL in seconds.
	 *
	 * @var int
	 */
	const NOTICE_TTL = 120;

	/**
	 * Plugin instance.
	 *
	 * @var SimpleSocialButtonsPR
	 */
	private $plugin;

	/**
	 * Constructor.
	 *
	 * @param SimpleSocialButtonsPR $plugin Plugin instance.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;

		add_action( 'admin_bar_menu', array( $this, 'register_admin_bar_menu' ), 100 );

		if ( is_admin() ) {
			add_action( 'admin_post_ssb_purge_caches_recent', array( $this, 'handle_purge_recent' ) );
			add_action( 'admin_post_ssb_purge_caches_all', array( $this, 'handle_purge_all' ) );
			add_action( 'admin_notices', array( $this, 'render_admin_notice' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_notice_script' ) );
		}
	}

	/**
	 * Register admin bar nodes.
	 *
	 * @param WP_Admin_Bar $admin_bar Admin bar instance.
	 * @return void
	 */
	public function register_admin_bar_menu( $admin_bar ) {
		if ( ! current_user_can( 'manage_options' ) || wp_doing_ajax() ) {
			return;
		}

		$admin_bar->add_node(
			array(
				'id'    => 'ssb-admin-bar',
				'title' => esc_html__( 'Simple Social Buttons', 'simple-social-buttons' ),
				'href'  => false,
			)
		);

		$admin_bar->add_node(
			array(
				'id'     => 'ssb-purge-recent',
				'parent' => 'ssb-admin-bar',
				'title'  => esc_html__( 'Purge caches (30 recent posts/pages)', 'simple-social-buttons' ),
				'href'   => $this->get_action_url( 'ssb_purge_caches_recent' ),
			)
		);

		if ( apply_filters( 'ssb_show_admin_bar_purge_all', false ) ) {
			$admin_bar->add_node(
				array(
					'id'     => 'ssb-purge-all',
					'parent' => 'ssb-admin-bar',
					'title'  => esc_html__( 'Purge all (internal + API cache)', 'simple-social-buttons' ),
					'href'   => $this->get_action_url( 'ssb_purge_caches_all' ),
				)
			);
		}
	}

	/**
	 * Build admin-post action URL with nonce.
	 *
	 * @param string $action Admin post action.
	 * @return string
	 */
	private function get_action_url( $action ) {
		$url = admin_url( 'admin-post.php?action=' . $action );
		return wp_nonce_url( $url, 'ssb_purge_caches' );
	}

	/**
	 * Verify request and redirect on failure.
	 *
	 * @return bool
	 */
	private function verify_request() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to purge share caches.', 'simple-social-buttons' ) );
		}
		check_admin_referer( 'ssb_purge_caches' );
		return true;
	}

	/**
	 * Redirect after purge with referer fallback.
	 *
	 * @return void
	 */
	private function redirect_back() {
		$redirect = wp_get_referer();
		if ( ! $redirect ) {
			$redirect = admin_url();
		}
		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Store admin notice transient.
	 *
	 * @param string $status   processing|completed|error.
	 * @param string $message  Notice message.
	 * @param array  $progress Optional progress done/total.
	 * @return void
	 */
	private function set_notice( $status, $message, $progress = array() ) {
		set_transient(
			self::NOTICE_TRANSIENT,
			array(
				'status'   => $status,
				'message'  => $message,
				'progress' => $progress,
			),
			self::NOTICE_TTL
		);
	}

	/**
	 * Purge internal + API for a list of post IDs (sync).
	 *
	 * @param array $post_ids Post IDs.
	 * @return array{processed:int,internal_flushed:int,api_refetched:int}
	 */
	private function purge_posts_sync( $post_ids ) {
		if ( function_exists( 'set_time_limit' ) ) {
			set_time_limit( 240 );
		}

		$post_ids = array_values( array_unique( array_map( 'intval', $post_ids ) ) );
		$internal = ssb_purge_internal_for_post_ids( $post_ids );
		$api_ok   = 0;

		foreach ( $post_ids as $post_id ) {
			if ( $post_id <= 0 ) {
				continue;
			}
			if ( ssb_refetch_api_counts_for_post( $post_id, $this->plugin ) ) {
				++$api_ok;
			}
		}

		return array(
			'processed'        => count( $post_ids ),
			'internal_flushed' => $internal,
			'api_refetched'    => $api_ok,
		);
	}

	/**
	 * Handle purge for 30 recent posts/pages.
	 *
	 * @return void
	 */
	public function handle_purge_recent() {
		$this->verify_request();

		$post_ids = ssb_get_recent_post_ids_for_purge();

		$this->set_notice(
			'processing',
			sprintf(
				/* translators: %d: number of posts. */
				__( 'Purging share caches for %d recent posts/pages…', 'simple-social-buttons' ),
				count( $post_ids )
			)
		);

		$result = $this->purge_posts_sync( $post_ids );

		$this->set_notice(
			'completed',
			sprintf(
				/* translators: 1: posts processed, 2: internal queue flushes, 3: API refetches. */
				__(
					'Purged %1$d posts/pages. Internal queue flushed for %2$d. API counts refetched for %3$d.',
					'simple-social-buttons'
				),
				$result['processed'],
				$result['internal_flushed'],
				$result['api_refetched']
			)
		);

		$this->redirect_back();
	}

	/**
	 * Handle site-wide purge (internal now, API via cron batches).
	 *
	 * @return void
	 */
	public function handle_purge_all() {
		$this->verify_request();

		if ( ! apply_filters( 'ssb_show_admin_bar_purge_all', false ) ) {
			$this->set_notice(
				'error',
				__( 'Purge all is not enabled on this site.', 'simple-social-buttons' )
			);
			$this->redirect_back();
		}

		wp_clear_scheduled_hook( 'ssb_purge_all_api_batch' );

		$this->plugin->ssb_flush_internal_share_queue();

		$post_ids = ssb_get_all_post_ids_for_purge();
		$total    = count( $post_ids );

		set_transient(
			self::PURGE_ALL_STATE_TRANSIENT,
			array(
				'post_ids' => $post_ids,
				'offset'   => 0,
				'total'    => $total,
				'done'     => 0,
			),
			HOUR_IN_SECONDS
		);

		if ( $total > 0 ) {
			wp_schedule_single_event( time() + 5, 'ssb_purge_all_api_batch' );
			$this->set_notice(
				'processing',
				__( 'Purging all share caches in the background…', 'simple-social-buttons' ),
				array(
					'done'  => 0,
					'total' => $total,
				)
			);
		} else {
			$this->set_notice(
				'completed',
				__( 'Internal share queue flushed. No posts found for API refetch.', 'simple-social-buttons' )
			);
		}

		$this->redirect_back();
	}

	/**
	 * Cron batch: refetch API counts for next chunk of posts.
	 *
	 * @param SimpleSocialButtonsPR $plugin Plugin instance.
	 * @return void
	 */
	public static function process_api_batch( $plugin ) {
		$state = get_transient( self::PURGE_ALL_STATE_TRANSIENT );
		if ( ! is_array( $state ) || empty( $state['post_ids'] ) || ! is_array( $state['post_ids'] ) ) {
			wp_clear_scheduled_hook( 'ssb_purge_all_api_batch' );
			return;
		}

		$batch_size = max( 1, (int) apply_filters( 'ssb_purge_all_batch_size', 15 ) );
		$offset     = isset( $state['offset'] ) ? (int) $state['offset'] : 0;
		$post_ids   = $state['post_ids'];
		$total      = isset( $state['total'] ) ? (int) $state['total'] : count( $post_ids );
		$done       = isset( $state['done'] ) ? (int) $state['done'] : 0;
		$slice      = array_slice( $post_ids, $offset, $batch_size );

		foreach ( $slice as $post_id ) {
			ssb_refetch_api_counts_for_post( (int) $post_id, $plugin );
		}

		$processed = count( $slice );
		$offset   += $processed;
		$done     += $processed;

		if ( $offset < count( $post_ids ) ) {
			$state['offset'] = $offset;
			$state['done']   = $done;
			set_transient( self::PURGE_ALL_STATE_TRANSIENT, $state, HOUR_IN_SECONDS );

			self::set_notice_static(
				'processing',
				__( 'Purging all share caches in the background…', 'simple-social-buttons' ),
				array(
					'done'  => $done,
					'total' => $total,
				)
			);

			wp_schedule_single_event( time() + 10, 'ssb_purge_all_api_batch' );
			return;
		}

		delete_transient( self::PURGE_ALL_STATE_TRANSIENT );
		wp_clear_scheduled_hook( 'ssb_purge_all_api_batch' );

		self::set_notice_static(
			'completed',
			sprintf(
				/* translators: %d: number of posts. */
				__( 'Purge all complete. API counts refetched for %d posts/pages.', 'simple-social-buttons' ),
				$done
			),
			array(
				'done'  => $done,
				'total' => $total,
			)
		);
	}

	/**
	 * Store notice transient (static for cron).
	 *
	 * @param string $status   Notice status.
	 * @param string $message  Message.
	 * @param array  $progress Optional progress.
	 * @return void
	 */
	private static function set_notice_static( $status, $message, $progress = array() ) {
		set_transient(
			self::NOTICE_TRANSIENT,
			array(
				'status'   => $status,
				'message'  => $message,
				'progress' => $progress,
			),
			self::NOTICE_TTL
		);
	}

	/**
	 * Output dismissible admin notice from transient.
	 *
	 * @return void
	 */
	public function render_admin_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$notice = get_transient( self::NOTICE_TRANSIENT );
		if ( ! is_array( $notice ) || empty( $notice['message'] ) ) {
			return;
		}

		$status = isset( $notice['status'] ) ? $notice['status'] : 'info';
		$class  = 'notice-info';
		if ( 'completed' === $status ) {
			$class = 'notice-success';
		} elseif ( 'error' === $status ) {
			$class = 'notice-error';
		}

		$message = $notice['message'];
		if ( ! empty( $notice['progress']['total'] ) ) {
			$done     = isset( $notice['progress']['done'] ) ? (int) $notice['progress']['done'] : 0;
			$total    = (int) $notice['progress']['total'];
			$message .= ' ' . sprintf(
				/* translators: 1: done count, 2: total count. */
				__( '(%1$d / %2$d posts)', 'simple-social-buttons' ),
				$done,
				$total
			);
		}

		printf(
			'<div class="notice %1$s is-dismissible ssb-purge-notice ssb-purge-notice--%2$s" data-ssb-purge-autodismiss="%3$s"><p>%4$s</p></div>',
			esc_attr( $class ),
			esc_attr( $status ),
			esc_attr( in_array( $status, array( 'completed', 'error' ), true ) ? '1' : '0' ),
			esc_html( $message )
		);
	}

	/**
	 * Enqueue auto-dismiss script when a purge notice is active.
	 *
	 * @return void
	 */
	public function enqueue_notice_script() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$notice = get_transient( self::NOTICE_TRANSIENT );
		if ( ! is_array( $notice ) || empty( $notice['message'] ) ) {
			return;
		}

		wp_enqueue_script(
			'ssb-admin-purge-notice',
			SSB_PLUGIN_URL . 'assets/js/admin-purge-notice.js',
			array(),
			SSB_VERSION,
			true
		);
	}
}
