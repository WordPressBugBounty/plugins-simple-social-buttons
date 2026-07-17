<?php // phpcs:ignore
/**
 * Utility functions for Simple Social Buttons.
 *
 * @package Simple_Social_Buttons
 * @since   2.0
 */

/**
 * Fetch share count responses with WordPress HTTP API.
 *
 * @param array $data Array of URLs or URL data.
 * @param array $args Optional request args for wp_safe_remote_get.
 * @return array Array of response bodies keyed by network.
 * @since 7.0.0
 */
function ssb_fetch_shares_via_http_api( $data, $args = array() ) {
	$result = array();

	if ( ! is_array( $data ) || empty( $data ) ) {
		return $result;
	}

	$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) : ''; // phpcs:ignore
	$defaults   = array(
		'redirection' => 0,
		'sslverify'   => true,
		'timeout'     => 5,
		'user-agent'  => $user_agent,
	);

	foreach ( $data as $id => $request_data ) {
		$url = ( is_array( $request_data ) && ! empty( $request_data['url'] ) ) ? $request_data['url'] : $request_data;
		if ( empty( $url ) ) {
			$result[ $id ] = '';
			continue;
		}

		$request_args = $defaults;
		if ( is_array( $args ) && ! empty( $args ) ) {
			$request_args = array_merge( $request_args, $args );
		}

		if ( is_array( $request_data ) && isset( $request_data['args'] ) && is_array( $request_data['args'] ) ) {
			$request_args = array_merge( $request_args, $request_data['args'] );
		}

		$response = wp_safe_remote_get( $url, $request_args );
		if ( is_wp_error( $response ) ) {
			$result[ $id ] = '';
			continue;
		}

		$status = (int) wp_remote_retrieve_response_code( $response );
		if ( $status < 200 || $status >= 300 ) {
			$result[ $id ] = '';
			continue;
		}

		$result[ $id ] = (string) wp_remote_retrieve_body( $response );
	}

	return $result;
}

/**
 * Sanitize custom CSS to prevent XSS when output in style context.
 *
 * Strips HTML tags and removes dangerous sequences that could break out of
 * a style tag or inject script.
 *
 * @param string $css Raw CSS input.
 * @return string Sanitized CSS.
 * @since 7.0.0
 */
function ssb_sanitize_custom_css( $css ) {
	if ( ! is_string( $css ) ) {
		return '';
	}
	$css = wp_strip_all_tags( $css );
	$css = preg_replace( '#</style>#i', '', $css );
	$css = preg_replace( '#</script>#i', '', $css );
	$css = preg_replace( '#<script#i', '', $css );
	$css = preg_replace( '#javascript\s*:#i', '', $css );
	return $css;
}

/**
 * Return Custom JS string ready for display or frontend output.
 *
 * Handles both legacy base64-stored values and raw-stored values. Decodes at most
 * once when the stored value is valid base64 and decodes to printable text.
 *
 * @param string $stored Stored ssb_js value (raw or base64).
 * @return string JS code to use (display or wp_add_inline_script).
 * @since 7.0.0
 */
function ssb_get_custom_js_for_output( $stored ) {
	if ( ! is_string( $stored ) || '' === trim( $stored ) ) {
		return '';
	}
	// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
	$decoded = base64_decode( $stored, true );
	if ( false !== $decoded && preg_match( '/^[\x20-\x7e\r\n\t]*$/s', $decoded ) ) {
		return $decoded;
	}
	return $stored;
}

/**
 * Remove API credentials from a settings section so they are never exported or imported.
 *
 * Facebook App ID/Secret live in ssb_advanced; Snap Kit Client ID lives in ssb_snapchat.
 *
 * @param string $option_id Option name (e.g. ssb_advanced).
 * @param mixed  $settings  Option value.
 * @return mixed Settings with sensitive keys removed.
 * @since 7.0.0
 */
function ssb_strip_sensitive_settings( $option_id, $settings ) {
	if ( ! is_array( $settings ) ) {
		return $settings;
	}

	if ( 'ssb_advanced' === $option_id ) {
		unset( $settings['facebook_app_id'], $settings['facebook_app_secret'] );
	}

	if ( 'ssb_snapchat' === $option_id ) {
		unset( $settings['snapchat_client_id'] );
	}

	return $settings;
}

/**
 * Recursively sanitize imported settings array for safe update_option.
 *
 * @param array $array_data Imported section data.
 * @return array Sanitized array.
 * @since 7.0.0
 */
function ssb_sanitize_imported_settings( $array_data ) {
	if ( ! is_array( $array_data ) ) {
		return is_string( $array_data ) ? sanitize_text_field( $array_data ) : $array_data;
	}
	$out = array();
	foreach ( $array_data as $key => $value ) {
		if ( is_array( $value ) ) {
			$out[ $key ] = ssb_sanitize_imported_settings( $value );
		} elseif ( 'ssb_css' === $key ) {
			$out[ $key ] = ssb_sanitize_custom_css( $value );
		} elseif ( 'ssb_js' === $key ) {
			$out[ $key ] = $value;
		} else {
			$out[ $key ] = sanitize_text_field( $value );
		}
	}
	return $out;
}

/**
 * Return false if to fetch the new counts.
 *
 * @param int $post_id Post ID.
 * @return bool
 * @since 2.0
 */
function ssb_is_cache_fresh( $post_id ) {
	// Bail early if it's a crawl bot. If so, ONLY SERVE CACHED RESULTS FOR MAXIMUM SPEED.
	if ( isset( $_SERVER['HTTP_USER_AGENT'] ) && preg_match( '/bot|crawl|slurp|spider/i', wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) ) { // phpcs:ignore
		return true;
	}

	$fresh_cache = false;

	if ( isset( $_POST['ssb_cache'] ) && 'rebuild' === $_POST['ssb_cache'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		return false;
	}

	$post_age = floor( gmdate( 'U' ) - get_post_time( 'U', false, $post_id ) );

	if ( $post_age < ( 21 * 86400 ) ) {
		$hours = 1;
	} elseif ( $post_age < ( 60 * 86400 ) ) {
		$hours = 4;
	} else {
		$hours = 12;
	}

	$time         = floor( ( ( gmdate( 'U' ) / 60 ) / 60 ) );
	$last_checked = get_post_meta( $post_id, 'ssb_cache_timestamp', true );

	if ( $last_checked > ( $time - $hours ) && $last_checked > 390000 ) {
		$fresh_cache = true;
	} else {
		$fresh_cache = false;
	}

	return $fresh_cache;
}


/**
 * Fetch fresh counts and cached them.
 *
 * @param array $stats          Stats array.
 * @param int   $post_id        Post ID.
 * @param array $alt_share_link Alternative share links.
 * @return array Simple array with counts.
 * @since 2.0
 * @version 7.0.0
 */
function ssb_fetch_fresh_counts( $stats, $post_id, $alt_share_link ) {

	$stats_result = array();
	$total        = 0;

	$networks = ssb_get_old_network_counts( $post_id );

	if ( empty( $networks ) ) {
		$_result = ssb_fetch_shares_via_http_api( array_filter( $alt_share_link ) );
		ssb_fetch_http_or_https_counts( $_result, $post_id );
		$networks = ssb_get_old_network_counts( $post_id );
	}

	foreach ( $stats as $social_name => $counts ) {
		if ( ! ssb_is_network_has_counts( $social_name ) ) {
			continue;
		}
		$stats_counts      = call_user_func( 'ssb_format_' . $social_name . '_response', $counts );
		$old_network_count = isset( $networks[ $social_name ] ) ? (int) $networks[ $social_name ] : 0;
		$new_counts        = $stats_counts + $old_network_count;

		$old_counts = get_post_meta( $post_id, 'ssb_' . $social_name . '_counts', true );

		// This will solve if new plugin install.
		$old_counts = $old_counts ? $old_counts : 0;
		// If old counts less than new, return old.
		if ( $new_counts > $old_counts ) {
			$stats_result[ $social_name ] = $new_counts;
		} else {
			$stats_result[ $social_name ] = $old_counts;
		}

		// Special case if post id not exist for example short code run on widget out side the loop in archive page.
		if ( 0 !== $post_id ) {
			if ( $new_counts > $old_counts ) {
				update_post_meta( $post_id, 'ssb_' . $social_name . '_counts', $new_counts );
			} else {
				// Set new counts equal to old counts for total calculation.
				$new_counts = $old_counts;
			}
		} else {
			update_option( 'ssb_not_exist_post_' . $social_name . '_counts', $new_counts );
		}

		$total += $new_counts;
	}

	$stats_result['total'] = $total;
	// Special case if post id not exist for example short code run on widget out side the loop in archive page.
	if ( 0 !== $post_id ) {
		update_post_meta( $post_id, 'ssb_total_counts', $total );
	} else {
		update_option( 'ssb_not_exist_post_total_counts', $total );
	}

	return $stats_result;
}
/**
 * Fetch counts + http or https resolve.
 *
 * @param array $stats   Stats array.
 * @param int   $post_id Post ID.
 * @return void
 * @since 2.0.12
 * @version 7.0.0
 */
function ssb_fetch_http_or_https_counts( $stats, $post_id ) {
	$networks = array();
	foreach ( $stats as $social_name => $counts ) {
		if ( ! ssb_is_network_has_counts( $social_name ) ) {
			continue;
		}
		$stats_counts             = call_user_func( 'ssb_format_' . $social_name . '_response', $counts );
		$networks[ $social_name ] = $stats_counts;
	}
	ssb_set_old_network_counts( $post_id, $networks );
}

/**
 * Get resolved old network counts.
 *
 * @param int $post_id Post ID.
 * @return array
 * @since 7.0.0
 */
function ssb_get_old_network_counts( $post_id ) {
	if ( 0 !== $post_id ) {
		$networks = get_post_meta( $post_id, 'ssb_old_counts', true );
	} else {
		$networks = get_option( 'ssb_not_exist_post_old_counts' );
	}

	return is_array( $networks ) ? $networks : array();
}

/**
 * Save resolved old network counts.
 *
 * @param int   $post_id  Post ID.
 * @param array $networks Resolved network count array.
 * @return void
 * @since 7.0.0
 */
function ssb_set_old_network_counts( $post_id, $networks ) {
	if ( 0 !== $post_id ) {
		update_post_meta( $post_id, 'ssb_old_counts', $networks );
	} else {
		update_option( 'ssb_not_exist_post_old_counts', $networks );
	}
}

/**
 * Whether a network key is a custom button ID.
 *
 * @param string $network Network key.
 * @return bool
 * @since 7.0.0
 */
function ssb_is_custom_button_id( $network ) {
	return is_string( $network ) && preg_match( '/^custom_\d+$/', $network );
}

/**
 * Darken a hex color by a percentage (0–100).
 *
 * Used for round-btm-border bottom-edge shadows that must read against a
 * solid matching background.
 *
 * @param string $hex     Hex color (e.g. #0865ff).
 * @param int    $percent Darken amount; default 25.
 * @return string Darkened hex, or empty string if invalid.
 * @since 7.0.0
 */
function ssb_darken_hex_color( $hex, $percent = 25 ) {
	$hex = sanitize_hex_color( $hex );
	if ( ! $hex ) {
		return '';
	}

	$percent = max( 0, min( 100, (int) $percent ) );
	$hex     = ltrim( $hex, '#' );

	if ( 3 === strlen( $hex ) ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}

	if ( 6 !== strlen( $hex ) ) {
		return '';
	}

	$factor = 1 - ( $percent / 100 );
	$r      = max( 0, (int) round( hexdec( substr( $hex, 0, 2 ) ) * $factor ) );
	$g      = max( 0, (int) round( hexdec( substr( $hex, 2, 2 ) ) * $factor ) );
	$b      = max( 0, (int) round( hexdec( substr( $hex, 4, 2 ) ) * $factor ) );

	return sprintf( '#%02x%02x%02x', $r, $g, $b );
}

/**
 * Whether Simple Social Buttons Pro is active.
 *
 * Uses the active_plugins option so this works before Pro's class file is loaded
 * (core constructs selected_networks in its constructor, earlier on plugins_loaded).
 *
 * @return bool
 * @since 7.0.0
 */
function ssb_is_pro_active() {
	if ( class_exists( 'Simple_Social_Buttons_Pro' ) ) {
		return true;
	}

	static $pro_active = null;

	if ( null !== $pro_active ) {
		return $pro_active;
	}

	if ( ! function_exists( 'is_plugin_active' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	$pro_plugin = 'simple-social-buttons-pro/simple-social-buttons-pro.php';
	$pro_active = is_plugin_active( $pro_plugin )
		|| ( function_exists( 'is_plugin_active_for_network' ) && is_plugin_active_for_network( $pro_plugin ) );

	return $pro_active;
}

/**
 * Remove custom button IDs from a network order when Pro is inactive.
 *
 * @param array<int, string> $network_ids Ordered network IDs.
 * @return array<int, string>
 * @since 7.0.0
 */
function ssb_filter_custom_button_ids_from_order( $network_ids ) {
	if ( ssb_is_pro_active() || ! is_array( $network_ids ) ) {
		return $network_ids;
	}

	return array_values(
		array_filter(
			$network_ids,
			function ( $network_id ) {
				return ! ssb_is_custom_button_id( (string) $network_id );
			}
		)
	);
}

/**
 * Custom button IDs configured in ssb_networks.
 *
 * @return array<int, string>
 * @since 7.0.0
 */
function ssb_get_custom_button_ids() {
	$networks = get_option( 'ssb_networks', array() );
	if ( ! is_array( $networks ) || empty( $networks['custom_buttons'] ) || ! is_array( $networks['custom_buttons'] ) ) {
		return array();
	}

	$ids = array();
	foreach ( array_keys( $networks['custom_buttons'] ) as $id ) {
		if ( ssb_is_custom_button_id( (string) $id ) ) {
			$ids[] = (string) $id;
		}
	}

	return $ids;
}

/**
 * Share count markup for a button when counts are enabled.
 *
 * @param string  $network      Network key.
 * @param array   $share_counts Network => count map.
 * @param boolean $show_count   Whether counts are enabled.
 * @param string  $theme        Active icon theme.
 * @return string
 * @since 7.0.0
 */
function ssb_get_button_counter_markup( $network, $share_counts, $show_count, $theme ) {
	if ( ! $show_count ) {
		return '';
	}

	$count = isset( $share_counts[ $network ] ) ? max( 0, (int) $share_counts[ $network ] ) : 0;
	if ( 'simple-icons' === $theme ) {
		return '<span class="ssb_counter">' . ssb_count_format( $count ) . '</span>';
	}

	return '<span class="ssb_counter ssb_' . esc_attr( $network ) . '_counter">' . ssb_count_format( $count ) . '</span>';
}

/**
 * Networks that use both API counts and internal click counts (display = API + internal).
 *
 * @return array
 * @since 7.0.0
 */
function ssb_get_hybrid_api_internal_share_networks() {
	return (array) apply_filters( 'ssb_hybrid_api_internal_share_networks', array( 'fbshare' ) );
}

/**
 * Networks tracked via internal click counter.
 *
 * @return array
 * @since 7.0.0
 */
function ssb_get_internal_share_trackable_networks() {
	$networks = array();

	// Prefer deriving from existing "has counts" logic to avoid duplicate lists.
	global $_ssb_pr;
	if ( isset( $_ssb_pr->arr_known_buttons ) && is_array( $_ssb_pr->arr_known_buttons ) ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
		foreach ( $_ssb_pr->arr_known_buttons as $network ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
			if ( 'totalshare' === $network || ssb_is_network_has_counts( $network ) ) {
				continue;
			}
			$networks[] = $network;
		}
	}

	if ( empty( $networks ) ) {
		$networks = array( 'twitter', 'linkedin', 'whatsapp', 'viber', 'messenger', 'email', 'copylink', 'print' );
	}

	$networks = array_values(
		array_unique(
			array_merge( $networks, ssb_get_hybrid_api_internal_share_networks(), ssb_get_custom_button_ids() )
		)
	);

	return (array) apply_filters( 'ssb_internal_share_trackable_networks', $networks );
}

/**
 * Return internal share queue from option.
 *
 * @return array
 * @since 7.0.0
 */
function ssb_get_internal_share_queue() {
	$queue = get_option( 'ssb_share_queue', array() );
	return is_array( $queue ) ? $queue : array();
}

/**
 * Increment internal share queue for post/network.
 *
 * @param int    $post_id Post ID.
 * @param string $network Network key.
 * @param int    $increment Increment amount.
 * @return array
 * @since 7.0.0
 */
function ssb_increment_internal_share_queue( $post_id, $network, $increment = 1 ) {
	$post_id   = (int) $post_id;
	$increment = max( 1, (int) $increment );
	if ( $post_id <= 0 || ! in_array( $network, ssb_get_internal_share_trackable_networks(), true ) ) {
		return array();
	}

	$queue = ssb_get_internal_share_queue();
	if ( ! isset( $queue[ $post_id ] ) || ! is_array( $queue[ $post_id ] ) ) {
		$queue[ $post_id ] = array();
	}

	$current_count                 = isset( $queue[ $post_id ][ $network ] ) ? (int) $queue[ $post_id ][ $network ] : 0;
	$queue[ $post_id ][ $network ] = $current_count + $increment;
	update_option( 'ssb_share_queue', $queue, false );

	return $queue;
}

/**
 * Pop and clear internal share queue.
 *
 * @return array
 * @since 7.0.0
 */
function ssb_pop_internal_share_queue() {
	$queue = ssb_get_internal_share_queue();
	update_option( 'ssb_share_queue', array(), false );
	return $queue;
}

/**
 * Flush queued internal share counts for a single post into post meta.
 *
 * @param int $post_id Post ID.
 * @return bool True if a queue entry was flushed, false otherwise.
 * @since 7.0.0
 */
function ssb_flush_internal_share_queue_for_post( $post_id ) {
	$post_id = (int) $post_id;
	if ( $post_id <= 0 ) {
		return false;
	}

	$queue = ssb_get_internal_share_queue();
	if ( ! isset( $queue[ $post_id ] ) || ! is_array( $queue[ $post_id ] ) || empty( $queue[ $post_id ] ) ) {
		return false;
	}

	ssb_merge_internal_share_history( $post_id, $queue[ $post_id ] );
	unset( $queue[ $post_id ] );
	update_option( 'ssb_share_queue', $queue, false );

	return true;
}

/**
 * Flush internal share queue for multiple posts.
 *
 * @param array $post_ids Post IDs.
 * @return int Number of posts that had queue data flushed.
 * @since 7.0.0
 */
function ssb_purge_internal_for_post_ids( $post_ids ) {
	$flushed = 0;
	if ( ! is_array( $post_ids ) ) {
		return $flushed;
	}
	foreach ( $post_ids as $post_id ) {
		if ( ssb_flush_internal_share_queue_for_post( (int) $post_id ) ) {
			++$flushed;
		}
	}
	return $flushed;
}

/**
 * Return recent published post/page IDs for admin purge.
 *
 * @param int $limit Max posts to return.
 * @return array Post IDs.
 * @since 7.0.0
 */
function ssb_get_recent_post_ids_for_purge( $limit = 30 ) {
	$limit      = max( 1, (int) apply_filters( 'ssb_purge_recent_limit', $limit ) );
	$post_types = apply_filters( 'ssb_purge_recent_post_types', array( 'post', 'page' ) );
	if ( ! is_array( $post_types ) ) {
		$post_types = array( 'post', 'page' );
	}

	$query = new WP_Query(
		array(
			'post_type'              => $post_types,
			'post_status'            => 'publish',
			'posts_per_page'         => $limit,
			'orderby'                => 'modified',
			'order'                  => 'DESC',
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);

	return is_array( $query->posts ) ? array_map( 'intval', $query->posts ) : array();
}

/**
 * Return all published post/page IDs for site-wide purge batches.
 *
 * @return array Post IDs.
 * @since 7.0.0
 */
function ssb_get_all_post_ids_for_purge() {
	$post_types = apply_filters( 'ssb_purge_all_post_types', array( 'post', 'page' ) );
	if ( ! is_array( $post_types ) ) {
		$post_types = array( 'post', 'page' );
	}

	$query = new WP_Query(
		array(
			'post_type'              => $post_types,
			'post_status'            => 'publish',
			'posts_per_page'         => -1,
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);

	return is_array( $query->posts ) ? array_map( 'intval', $query->posts ) : array();
}

/**
 * Refetch API share counts for a single post via plugin instance.
 *
 * @param int                   $post_id Post ID.
 * @param SimpleSocialButtonsPR $plugin  Plugin instance.
 * @return bool True on success, false on failure or skipped.
 * @since 7.0.0
 */
function ssb_refetch_api_counts_for_post( $post_id, $plugin ) {
	$post_id = (int) $post_id;
	if ( $post_id <= 0 || ! $plugin instanceof SimpleSocialButtonsPR ) {
		return false;
	}

	$permalink = get_permalink( $post_id );
	if ( ! $permalink ) {
		return false;
	}

	$counts = $plugin->ssb_refetch_api_share_counts_for_post( $post_id );
	if ( ! is_array( $counts ) ) {
		return false;
	}

	update_post_meta( $post_id, 'ssb_cache_timestamp', floor( ( ( gmdate( 'U' ) / 60 ) / 60 ) ) );
	return true;
}

/**
 * Return latest internal share counts for a post.
 *
 * @param int $post_id Post ID.
 * @return array
 * @since 7.0.0
 */
function ssb_get_internal_share_counts_latest( $post_id ) {
	$post_id = (int) $post_id;
	if ( $post_id <= 0 ) {
		return array(
			'date'     => gmdate( 'Y-m-d' ),
			'networks' => array(),
			'total'    => 0,
		);
	}

	$latest = get_post_meta( $post_id, 'ssb_share_counts_latest', true );
	if ( is_array( $latest ) && isset( $latest['networks'] ) && is_array( $latest['networks'] ) ) {
		$latest['total'] = isset( $latest['total'] ) ? (int) $latest['total'] : array_sum( $latest['networks'] );
		$latest['date']  = isset( $latest['date'] ) ? (string) $latest['date'] : gmdate( 'Y-m-d' );
		return $latest;
	}

	return array(
		'date'     => gmdate( 'Y-m-d' ),
		'networks' => array(),
		'total'    => 0,
	);
}

/**
 * Merge queued increments into per-post share history.
 *
 * @param int    $post_id Post ID.
 * @param array  $network_counts Network => increment map.
 * @param string $bucket_date Date bucket (Y-m-d). Defaults to current date.
 * @return array
 * @since 7.0.0
 */
function ssb_merge_internal_share_history( $post_id, $network_counts, $bucket_date = '' ) {
	$post_id = (int) $post_id;
	if ( $post_id <= 0 || ! is_array( $network_counts ) || empty( $network_counts ) ) {
		return ssb_get_internal_share_counts_latest( $post_id );
	}

	$bucket_date = $bucket_date ? $bucket_date : gmdate( 'Y-m-d' );
	$history     = get_post_meta( $post_id, 'ssb_share_counts', true );
	$history     = is_array( $history ) ? $history : array();

	if ( ! isset( $history[ $bucket_date ] ) || ! is_array( $history[ $bucket_date ] ) ) {
		$history[ $bucket_date ] = array(
			'networks' => array(),
			'total'    => 0,
		);
	}
	if ( ! isset( $history[ $bucket_date ]['networks'] ) || ! is_array( $history[ $bucket_date ]['networks'] ) ) {
		$history[ $bucket_date ]['networks'] = array();
	}

	$trackable_networks = ssb_get_internal_share_trackable_networks();
	foreach ( $network_counts as $network => $increment ) {
		if ( ! in_array( $network, $trackable_networks, true ) ) {
			continue;
		}
		$increment = (int) $increment;
		if ( $increment <= 0 ) {
			continue;
		}
		$current_count                                   = isset( $history[ $bucket_date ]['networks'][ $network ] )
			? (int) $history[ $bucket_date ]['networks'][ $network ]
			: 0;
		$history[ $bucket_date ]['networks'][ $network ] = $current_count + $increment;
	}

	$history[ $bucket_date ]['total'] = array_sum( $history[ $bucket_date ]['networks'] );
	update_post_meta( $post_id, 'ssb_share_counts', $history );

	$latest = array(
		'date'     => $bucket_date,
		'networks' => $history[ $bucket_date ]['networks'],
		'total'    => (int) $history[ $bucket_date ]['total'],
	);
	update_post_meta( $post_id, 'ssb_share_counts_latest', $latest );

	return $latest;
}

/**
 * Merge API and internal counts for display output.
 *
 * @param array      $share_counts    Current API/cached counts.
 * @param int        $post_id         Post ID.
 * @param array|null $active_networks Active network order map; total uses only these when provided.
 * @return array
 * @since 7.0.0
 */
function ssb_merge_api_and_internal_share_counts( $share_counts, $post_id, $active_networks = null ) {
	if ( ! is_array( $share_counts ) ) {
		$share_counts = array();
	}

	$latest          = ssb_get_internal_share_counts_latest( (int) $post_id );
	$internal_total  = isset( $latest['total'] ) ? (int) $latest['total'] : 0;
	$internal_map    = isset( $latest['networks'] ) && is_array( $latest['networks'] ) ? $latest['networks'] : array();
	$hybrid_networks = ssb_get_hybrid_api_internal_share_networks();
	$api_total       = isset( $share_counts['total'] ) ? (int) $share_counts['total'] : 0;

	foreach ( ssb_get_internal_share_trackable_networks() as $network ) {
		$internal_count = isset( $internal_map[ $network ] ) ? (int) $internal_map[ $network ] : 0;

		if ( in_array( $network, $hybrid_networks, true ) ) {
			$api_count                = isset( $share_counts[ $network ] ) ? (int) $share_counts[ $network ] : 0;
			$share_counts[ $network ] = $api_count + $internal_count;
			continue;
		}

		$share_counts[ $network ] = $internal_count;
	}

	$share_counts['api_total']      = $api_total;
	$share_counts['internal_total'] = $internal_total;
	$share_counts['total']          = $api_total + $internal_total;

	if ( is_array( $active_networks ) && ! empty( $active_networks ) ) {
		$share_counts['total'] = ssb_sum_share_counts_for_active_networks( $share_counts, $active_networks );
	}

	return $share_counts;
}

/**
 * Sum share counts for currently active networks only.
 *
 * @param array $share_counts    Network => count map.
 * @param array $active_networks Active network order map.
 * @return int
 * @since 7.0.0
 */
function ssb_sum_share_counts_for_active_networks( $share_counts, $active_networks ) {
	if ( ! is_array( $share_counts ) || ! is_array( $active_networks ) ) {
		return 0;
	}

	$total = 0;
	foreach ( $active_networks as $network => $priority ) {
		if ( 'totalshare' === $network || ssb_is_custom_button_id( (string) $network ) ) {
			continue;
		}
		if ( isset( $share_counts[ $network ] ) && is_numeric( $share_counts[ $network ] ) ) {
			$total += (int) $share_counts[ $network ];
		}
	}

	return $total;
}

/**
 * Get the cached counts.
 *
 * @param array $network_name Network names array.
 * @param int   $post_id      Post ID.
 * @return array Counts of each network.
 * @since 2.0
 */
function ssb_fetch_cached_counts( $network_name, $post_id ) {
	$network_name[] = 'total';
	$result         = array();
	foreach ( $network_name as $social_name ) {
		// Special case if post id not exist for example short code run on widget out side the loop in archive page.
		if ( 0 !== $post_id ) {
			$result[ $social_name ] = get_post_meta( $post_id, 'ssb_' . $social_name . '_counts', true );
		} else {
			$result[ $social_name ] = get_option( 'ssb_not_exist_post_' . $social_name . '_counts' );
		}
	}
	return $result;
}


/**
 * Detect if mobile.
 *
 * @return bool True if mobile device, false otherwise.
 * @since 2.0.13
 */
function ssb_is_mobile() {

	$useragent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) : 'none'; // phpcs:ignore

	$mobile_pattern_1 = '/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i'; // phpcs:ignore
	$mobile_pattern_2 = '/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i'; // phpcs:ignore

	if ( preg_match( $mobile_pattern_1, $useragent ) || preg_match( $mobile_pattern_2, substr( $useragent, 0, 4 ) ) ) {
		return true;
	} else {
		return false;
	}
}

/**
 * Generate WhatsApp share link.
 *
 * @param string $url URL to share.
 * @return string Final url after detection is it mobile or desktop.
 * @since 2.0.23
 * @version 5.0.0
 */
function ssb_whats_app_share_link( $url ) {
	$whats_share_link = 'https://api.whatsapp.com/send?text=' . $url;

	return $whats_share_link;
}

/**
 * Generate Viber share link.
 *
 * @param string $url URL to share.
 * @return string Final url after detection is it desktop.
 * @since 3.2.0
 */
function ssb_viber_share_link( $url ) {
	$viber_share_link = 'viber://forward?text=' . $url;
	return $viber_share_link;
}

/**
 * Generate LinkedIn share link.
 *
 * @param string $url URL to share.
 * @return string Final url after detection is it desktop.
 * @since 3.2.0
 */
function ssb_linkdin_share_link( $url ) {
	$linkdin_share_link = 'https://www.linkedin.com/sharing/share-offsite/?url=' . $url;
	return $linkdin_share_link;
}

/**
 * Snapchat web share URL for a permalink.
 *
 * @param string $permalink URL to share.
 * @return string
 * @since 7.0.0
 */
function ssb_get_snapchat_web_share_url( $permalink ) {
	return 'https://www.snapchat.com/share?link=' . rawurlencode( (string) $permalink );
}

/**
 * Snapchat Creative Kit client ID from plugin settings (optional).
 *
 * @return string
 * @since 7.0.0
 */
function ssb_get_snapchat_client_id() {
	$options = get_option( 'ssb_snapchat', array() );
	if ( ! is_array( $options ) ) {
		return '';
	}

	$client_id = isset( $options['snapchat_client_id'] ) ? $options['snapchat_client_id'] : '';

	return sanitize_text_field( (string) $client_id );
}

/**
 * Snapchat share URL for a permalink (web share sheet / Creative Kit data-share-url).
 *
 * @param string $permalink URL to share.
 * @return string
 * @since 7.0.0
 */
function ssb_get_snapchat_share_url( $permalink ) {
	return ssb_get_snapchat_web_share_url( $permalink );
}

/**
 * Inline Snapchat ghost icon markup for button output.
 *
 * @return string
 * @since 7.0.0
 */
function ssb_get_snapchat_icon_svg() {
	return '<svg width="17" height="16" viewBox="0 0 17 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">'
		. '<path d="M8.48 15.943c1.678 0 2.103-1.064 3.096-1.419.745-.266 1.805-.135 2.29-.05'
		. 'a.28.28 0 0 0 .324-.22l.152-.677a.29.29 0 0 1 .266-.22c1.72-.081 2.018-.57 2.039-.861'
		. 'a.28.28 0 0 0-.188-.273c-3.805-1.38-3.613-3.007-3.241-3.777a.89.89 0 0 1 .674-.475'
		. 'c1.493-.23 1.326-1.826.429-1.344-.972.522-1.49.142-1.49.142V4.48c0-2.437-1.946-4.139-4.35-4.139'
		. 'S4.13 2.028 4.13 4.464v2.302s-.518.379-1.49-.142c-.897-.483-1.063 1.113.43 1.344a.89.89 0 0 1 .673.475'
		. 'c.372.77.564 2.397-3.24 3.776a.28.28 0 0 0-.189.273c.021.291.32.78 2.04.862a.29.29 0 0 1 .265.22'
		. 'l.153.677a.28.28 0 0 0 .322.22c.486-.085 1.546-.216 2.29.05.994.358 1.42 1.422 3.097 1.422" fill="#fff"/>'
		. '<path d="M8.38 16a3.4 3.4 0 0 1-2.291-.87 3.2 3.2 0 0 0-.88-.535c-.709-.255-1.773-.113-2.18-.042'
		. 'a.497.497 0 0 1-.568-.38l-.152-.677a.07.07 0 0 0-.068-.057c-1.794-.085-2.209-.624-2.24-1.063A.49.49 0 0 1 .33 11.9'
		. 'c1.741-.63 2.837-1.418 3.191-2.23a1.52 1.52 0 0 0-.053-1.255.67.67 0 0 0-.514-.355 1.44 1.44 0 0 1-1.262-.972'
		. 'a.71.71 0 0 1 .198-.77.69.69 0 0 1 .77 0 1.42 1.42 0 0 0 1.173.196V4.337C3.833 1.855 5.794 0 8.397 0'
		. 's4.564 1.862 4.564 4.337V6.51a1.44 1.44 0 0 0 1.173-.198.69.69 0 0 1 .77 0 .71.71 0 0 1 .198.77'
		. ' 1.44 1.44 0 0 1-1.262.97.67.67 0 0 0-.514.356 1.52 1.52 0 0 0-.053 1.255c.354.826 1.418 1.599 3.191 2.23'
		. 'a.49.49 0 0 1 .33.486c-.032.436-.447.975-2.241 1.064a.07.07 0 0 0-.068.057l-.152.677a.497.497 0 0 1-.567.38'
		. 'c-.419-.072-1.476-.213-2.181.042-.32.13-.617.311-.88.535a3.4 3.4 0 0 1-2.326.865" fill="#231f20"/>'
		. '</svg>';
}

/**
 * Check if SSB network has count/s.
 *
 * @param string $network Network name.
 * @return bool True if network has counts, false otherwise.
 * @since 2.1.4
 * @version 6.0.0
 */
function ssb_is_network_has_counts( $network ) {
	// phpcs:ignore Generic.Files.LineLength.MaxExceeded
	$no_count_networks = array( 'totalshare', 'viber', 'whatsapp', 'print', 'email', 'messenger', 'linkedin', 'twitter', 'pinterest', 'reddit', 'copylink', 'telegram', 'threads', 'bluesky', 'mastodon', 'vk', 'snapchat' );
	if ( in_array( $network, $no_count_networks, true ) ) {
		return false;
	} else {
		return true;
	}
}

/**
 * Pretty counts format.
 *
 * @param int $n         Number to format.
 * @param int $precision Precision for decimal places.
 * @return int|string Formatted number.
 * @since 2.0.0
 */
function ssb_count_format( $n, $precision = 1 ) {

	// Initialize default values for $n_format and $suffix.
	$n_format = 0;
	$suffix   = '';
	if ( $n >= 0 && $n < 1000 ) {
		// 1 - 999.
		$n_format = floor( $n );
		$suffix   = '';
	} elseif ( $n >= 1000 && $n < 1000000 ) {
		// 1k-999k.
		$n_format = number_format( $n / 1000, $precision );
		$suffix   = 'K+';
	} elseif ( $n >= 1000000 && $n < 1000000000 ) {
		// 1m-999m.
		$n_format = number_format( $n / 1000000, $precision );
		$suffix   = 'M+';
	} elseif ( $n >= 1000000000 && $n < 1000000000000 ) {
		// 1b-999b.
		$n_format = number_format( $n / 1000000000, $precision );
		$suffix   = 'B+';
	} elseif ( $n >= 1000000000000 ) {
		// 1t+.
		$n_format = number_format( $n / 1000000000000, $precision );
		$suffix   = 'T+';
	}
	return ! empty( $n_format . $suffix ) ? floatval( $n_format ) . $suffix : $n;
}
