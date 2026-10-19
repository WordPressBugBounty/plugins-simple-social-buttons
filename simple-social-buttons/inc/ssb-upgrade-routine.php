<?php // phpcs:ignore

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'ssb_run_upgrade_routines' );

/**
 * Run pending upgrade routines.
 *
 * Hooked to init (not upgrader_process_complete) so the routines run on the first
 * request served by the new code, whatever the update method (dashboard, auto-update,
 * zip upload or FTP). upgrader_process_complete fires while the old plugin files are
 * still loaded, so a callback added in a new version never runs for its own update.
 *
 * Each routine is idempotent (early-returns when its flag option is set).
 *
 * @since 7.2.0
 * @return void
 */
function ssb_run_upgrade_routines() {
	ssb_upgrade_routine_2();
	ssb_upgrade_routine_71_share_counts();
	ssb_upgrade_routine_icon_space_values();
}

/**
 * Upgrade Routine for V 2.0
 *
 * @since 2.0.0
 */
function ssb_upgrade_routine_2() {

	if ( get_option( 'run_ssb_update_routine_2' ) || get_option( 'ssb_networks' ) ) {
		return;
	}

	// Store Icon Order.
	if ( get_option( 'ssb_icons_order' ) ) {
		$_default = array(
			'icon_selection' => get_option( 'ssb_icons_order' ),
			'custom_buttons' => array(),
		);
		SimpleSocialButtonsPR::ssb_update_networks_option( $_default );
		delete_option( 'ssb_icons_order' );
	} else {
		$_default = array(
			'icon_selection' => 'fbshare,twitter,googleplus,linkedin',
			'custom_buttons' => array(),
		);
		SimpleSocialButtonsPR::ssb_update_networks_option( $_default );
	}

	// If settings avaliable.
	if ( get_option( 'ssb_pr_settings' ) ) {

		$_old_value = get_option( 'ssb_pr_settings' );

		// Set Position of Inline Icons.
		$before_post = rest_sanitize_boolean( isset( $_old_value['beforepost'] ) && '1' === $_old_value['beforepost'] ? true : false ); // phpcs:ignore
		$after_post  = rest_sanitize_boolean( isset( $_old_value['afterpost'] ) && '1' === $_old_value['afterpost'] ? true : false ); // phpcs:ignore

		if ( $before_post && $after_post ) {
			$inline_location = 'above_below';
		} elseif ( $before_post ) {
			$inline_location = 'above';
		} else {
			$inline_location = 'below';
		}

		// Page.
		$before_page = rest_sanitize_boolean( isset( $_old_value['beforepage'] ) && '1' === $_old_value['beforepage'] ? true : false ); // phpcs:ignore
		$after_page  = rest_sanitize_boolean( isset( $_old_value['afterpage'] ) && '1' === $_old_value['afterpage'] ? true : false ); // phpcs:ignore

		$inline_posts = array(
			'post' => 'post',
		);

		if ( $before_page || $after_page ) {
			$inline_posts['page'] = 'page';
		}

		$_default_inline = array(
			'location' => $inline_location,
			'posts'    => $inline_posts,
		);

		$on_archive  = rest_sanitize_boolean( isset( $_old_value['showarchive'] ) && '1' === $_old_value['showarchive'] ? true : false ); // phpcs:ignore
		$on_tag      = rest_sanitize_boolean( isset( $_old_value['showtag'] ) && '1' === $_old_value['showtag'] ? true : false ); // phpcs:ignore
		$on_category = rest_sanitize_boolean( isset( $_old_value['showcategory'] ) && '1' === $_old_value['showcategory'] ? true : false ); // phpcs:ignore

		if ( $on_archive ) {
			$_default_inline['show_on_archive'] = 1;
		}
		if ( $on_tag ) {
			$_default_inline['show_on_tag'] = 1;
		}
		if ( $on_category ) {
			$_default_inline['show_on_category'] = 1;
		}
		update_option( 'ssb_inline', $_default_inline );
		// End of Inline Icons.

		$_default_position = array(
			'position' => array(
				'inline' => 'inline',
			),
		);
		update_option( 'ssb_positions', $_default_position );

		$_default_theme = array(
			'icon_style' => 'sm-round',
		);
			update_option( 'ssb_themes', $_default_theme );

		// Set Extra tab settings.
		if ( isset( $_old_value['twitterusername'] ) ) {
			update_option(
				'ssb_extra',
				array(
					'twitter_handle' => $_old_value['twitterusername'],
				)
			);
		}

		delete_option( 'ssb_pr_settings' );
	}

	update_option( 'run_ssb_update_routine_2', 'yes' );
}

/**
 * One-time migration of legacy share counts into internal history (7.1).
 *
 * @since 7.1.0
 * @return void
 */
function ssb_upgrade_routine_71_share_counts() {
	if ( get_option( 'run_ssb_upgrade_routine_71_share_counts' ) ) {
		return;
	}

	if ( ! function_exists( 'ssb_get_all_post_ids_for_purge' ) ) {
		return;
	}

	foreach ( ssb_get_all_post_ids_for_purge() as $post_id ) {
		ssb_migrate_legacy_share_counts_for_post( (int) $post_id );
		ssb_maybe_repair_internal_share_counts_latest( (int) $post_id );
	}

	update_option( 'run_ssb_upgrade_routine_71_share_counts', 'yes' );
}

/**
 * Normalize legacy icon_space_value strings that included CSS units (e.g. "20px").
 *
 * Older admin UI accepted free-text like "10px"; frontend CSS used absint() so
 * spacing still worked. Fired on init via ssb_run_upgrade_routines().
 *
 * @since 7.2.0
 * @return void
 */
function ssb_upgrade_routine_icon_space_values() {
	if ( get_option( 'run_ssb_upgrade_routine_icon_space' ) ) {
		return;
	}

	if ( ! function_exists( 'ssb_sanitize_icon_space_value' ) ) {
		return;
	}

	$option_keys = array(
		'ssb_inline',
		'ssb_sidebar',
		'ssb_media',
		'ssb_flyin',
		'ssb_popup',
	);

	foreach ( $option_keys as $option_key ) {
		$settings = get_option( $option_key );
		if ( ! is_array( $settings ) || ! array_key_exists( 'icon_space_value', $settings ) ) {
			continue;
		}

		$raw       = $settings['icon_space_value'];
		$sanitized = ssb_sanitize_icon_space_value( $raw );
		if ( (string) $raw === (string) $sanitized ) {
			continue;
		}

		$settings['icon_space_value'] = $sanitized;
		update_option( $option_key, $settings );
	}

	update_option( 'run_ssb_upgrade_routine_icon_space', 'yes' );
}
