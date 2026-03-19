<?php
/**
 * Finds every membership level that has "Automatically create affiliate code"
 * enabled and dispatches the per-level backfill for it.
 *
 * Runs on the `pmpro_schedule_hourly` action.
 */
function pmpro_affiliates_backfill_missing() {
	global $wpdb;

	// Query the options table directly so we don't need to loop over every level.
	$options = $wpdb->get_results(
		"SELECT option_name, option_value
		 FROM {$wpdb->options}
		 WHERE option_name LIKE 'pmpro_create_affiliate_level_%'
		   AND option_value = '1'"
	);

	if ( empty( $options ) ) {
		return;
	}

	foreach ( $options as $option ) {
		$level_id = intval( str_replace( 'pmpro_create_affiliate_level_', '', $option->option_name ) );
		if ( $level_id > 0 ) {
			pmpro_affiliates_backfill_for_level( $level_id );
		}
	}
}
add_action( 'pmpro_schedule_hourly', 'pmpro_affiliates_backfill_missing' );

/**
 * Create affiliate records for all active members of a level who don't have one.
 *
 * Uses a single LEFT JOIN query so only genuinely missing records are returned,
 * matching the approach used at checkout in the main plugin file.
 *
 * @param int $level_id The membership level ID to process.
 */
function pmpro_affiliates_backfill_for_level( $level_id ) {
	global $wpdb;

	$level_id = intval( $level_id );
	if ( $level_id <= 0 ) {
		return;
	}

	$users = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT u.ID, u.display_name, u.user_login
			 FROM {$wpdb->users} u
			 INNER JOIN {$wpdb->pmpro_memberships_users} mu
			     ON u.ID = mu.user_id
			 LEFT JOIN {$wpdb->pmpro_affiliates} a
			     ON u.user_login = a.affiliateuser
			 WHERE mu.membership_id = %d
			   AND mu.status = 'active'
			   AND a.affiliateuser IS NULL",
			$level_id
		)
	);

	if ( empty( $users ) ) {
		return;
	}

	// Get the level object for the filter.
	$pmpro_level = pmpro_getLevel( $level_id );

	foreach ( $users as $user ) {
		$code = pmpro_affiliates_getNewCode();

		/**
		 * Filters the affiliate cookie duration used during backfill.
		 * Mirrors the filter applied at checkout.
		 *
		 * @param int    $days      Number of days for the affiliate cookie.
		 * @param int    $user_id   The user ID being processed.
		 * @param object $level     The membership level object.
		 */
		$days = intval( apply_filters( 'pmproaf_default_cookie_duration', 30, $user->ID, $pmpro_level ) );

		/**
		 * Filters the affiliate commission rate used during backfill.
		 * Mirrors the filter applied on the admin affiliates page.
		 * 
		 * @param int    $rate      Commission rate as a percentage integer (e.g. 5 for 5%).
		 */
		$commissionrate = intval( apply_filters( 'pmpro_affiliate_default_commission_rate', 5 ) ) / 100;

		$wpdb->insert(
			$wpdb->pmpro_affiliates,
			array(
				'code'           => $code,
				'name'           => $user->display_name,
				'affiliateuser'  => $user->user_login,
				'trackingcode'   => '',
				'cookiedays'     => $days,
				'enabled'        => 1,
				'commissionrate' => $commissionrate,
			),
			array( '%s', '%s', '%s', '%s', '%d', '%d', '%f' )
		);
	}
}
