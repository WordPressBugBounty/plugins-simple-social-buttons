<?php // phpcs:ignore
/**
 * Uninstall routine for Simple Social Buttons.
 *
 * Fired when the plugin is deleted from the Plugins screen. Removes all
 * plugin options and (on multisite) per-blog options, but only if the user
 * has enabled "Remove data on uninstall" in Advanced settings.
 *
 * @package SimpleSocialButtons
 * @author  WPBrigade
 * @since   5.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Exit if not a real uninstall (WP core or SDK after-uninstall callback).
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) && ! defined( 'SSB_DOING_UNINSTALL' ) ) {
	exit;
}

$ssb_advance_setting = get_option( 'ssb_advanced' );

if ( isset( $ssb_advance_setting['ssb_uninstall_data'] ) && '1' !== $ssb_advance_setting['ssb_uninstall_data'] ) { // phpcs:ignore
	return;
}

/**
 * Option names to delete on uninstall.
 *
 * Includes all SSB options used by the plugin and React admin.
 *
 * @var array<string>
 */
$ssb_unintstall_options = array(
	'ssb_networks',
	'ssb_themes',
	'ssb_positions',
	'ssb_inline',
	'ssb_sidebar',
	'ssb_flyin',
	'ssb_popup',
	'ssb_media',
	'ssb_advanced',
	'ssb_extra',
	'ssb_snapchat',
	'ssb_ngg_gallery',
	'ssb_click_to_tweet',
	'ssb_active_time',
	'ssb_follow_twitter_token',
	'ssb_review_dismiss',
	'widget_ssb_widget',
	'ssb_pr_version', // $this->plugin_prefix . 'version'.
	'ssb_share_queue',
	'ssb_update_2_0_dismiss',
	'run_ssb_update_routine_2',
	'run_ssb_upgrade_routine_71_share_counts',
	'run_ssb_upgrade_routine_icon_space',
);

/**
 * Post meta keys for share-count cache/history and per-post settings (removed on uninstall).
 *
 * @var array<string>
 */
$ssb_uninstall_post_meta_keys = array(
	'ssb_share_counts',
	'ssb_share_counts_latest',
	'ssb_legacy_share_counts_migrated',
	'ssb_share_counts_latest_repaired',
	'ssb_api_counts_verified',
	'ssb_cache_timestamp',
	'ssb_old_counts',
	'ssb_total_counts',
	'_ssb_hide',
);

/**
 * Remove plugin options, transients, cron events and post meta for the current site.
 *
 * Dynamic keys (ssb_<network>_counts meta, ssb_not_exist_post_* options, widget
 * token options and all ssb_* transients) are removed by prefix.
 *
 * @param array<string> $options Option names to delete.
 * @param array<string> $post_meta_keys Post meta keys to delete.
 * @return void
 * @since 5.3.0
 * @version 7.2.0
 */
function ssb_uninstall_cleanup_current_site( array $options, array $post_meta_keys ) {
	global $wpdb;

	foreach ( $options as $ssb_options ) {
		delete_option( $ssb_options );
	}

	wp_clear_scheduled_hook( 'ssb_flush_internal_share_queue' );
	wp_clear_scheduled_hook( 'ssb_purge_all_api_batch' );

	$option_patterns = array(
		$wpdb->esc_like( 'ssb_not_exist_post_' ) . '%',
		$wpdb->esc_like( 'ssb_follow_twitter_token_' ) . '%',
		$wpdb->esc_like( '_transient_ssb_' ) . '%',
		$wpdb->esc_like( '_transient_timeout_ssb_' ) . '%',
	);
	foreach ( $option_patterns as $option_pattern ) {
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $option_pattern ) ); // phpcs:ignore
	}

	// Per-network API counts: ssb_<network>_counts.
	$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE %s", $wpdb->esc_like( 'ssb_' ) . '%' . $wpdb->esc_like( '_counts' ) ) ); // phpcs:ignore

	if ( empty( $post_meta_keys ) ) {
		return;
	}

	$placeholders = implode( ', ', array_fill( 0, count( $post_meta_keys ), '%s' ) );

	$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->postmeta} WHERE meta_key IN ($placeholders)", ...$post_meta_keys ) ); // phpcs:ignore
}

if ( ! is_multisite() ) {
	ssb_uninstall_cleanup_current_site( $ssb_unintstall_options, $ssb_uninstall_post_meta_keys );
} else {

	global $wpdb;
	$ssb_blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" ); // phpcs:ignore

	foreach ( $ssb_blog_ids as $ssb_blog_id ) {

		switch_to_blog( $ssb_blog_id );

		ssb_uninstall_cleanup_current_site( $ssb_unintstall_options, $ssb_uninstall_post_meta_keys );

		restore_current_blog();
	}
}
