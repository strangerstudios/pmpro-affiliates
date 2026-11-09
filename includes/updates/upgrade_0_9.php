<?php
/**
 * Upgrade for version 0.9
 * Add payout_email and payout_method columns to the affiliates table.
 * Create the wp_pmpro_affiliate_payouts log table.
 */
function pmpro_affiliates_upgrade_0_9() {
	global $wpdb;
	$wpdb->hide_errors();

	$affiliate_options = get_option( 'pmpro_affiliates_options', array() );

	// Don't run if already at 0.9+.
	if ( ! empty( $affiliate_options['db_version'] ) && (float) $affiliate_options['db_version'] >= 0.9 ) {
		return;
	}

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	// Add payout_email and payout_method columns to the affiliates table if missing.
	$existing_columns = $wpdb->get_col( "SHOW COLUMNS FROM {$wpdb->pmpro_affiliates}", 0 );

	if ( ! in_array( 'payout_email', $existing_columns ) ) {
		$wpdb->query( "ALTER TABLE {$wpdb->pmpro_affiliates} ADD COLUMN payout_email varchar(255) NOT NULL DEFAULT ''" );
	}

	if ( ! in_array( 'payout_method', $existing_columns ) ) {
		$wpdb->query( "ALTER TABLE {$wpdb->pmpro_affiliates} ADD COLUMN payout_method varchar(50) NOT NULL DEFAULT ''" );
	}

	// Create the payouts log table.
	$payouts_table = $wpdb->prefix . 'pmpro_affiliate_payouts';
	$sql           = "CREATE TABLE {$payouts_table} (
	  id              int(11) unsigned NOT NULL AUTO_INCREMENT,
	  affiliate_id    int(11) unsigned NOT NULL,
	  order_ids       mediumtext NOT NULL,
	  amount          decimal(10,2) NOT NULL,
	  payout_method   varchar(50) NOT NULL,
	  payout_account  varchar(255) NOT NULL,
	  status          varchar(20) NOT NULL DEFAULT 'paid',
	  created_by      bigint(20) NOT NULL,
	  date_start      datetime NOT NULL,
	  date_end        datetime NOT NULL,
	  date            datetime NOT NULL,
	  notes           text NOT NULL,
	  PRIMARY KEY (id),
	  KEY affiliate_id (affiliate_id),
	  KEY status (status),
	  KEY date (date)
	) " . $wpdb->get_charset_collate() . ';';

	dbDelta( $sql );

	// Leave db_version alone if the schema changes didn't apply, so the upgrade runs again next time.
	$existing_columns = $wpdb->get_col( "SHOW COLUMNS FROM {$wpdb->pmpro_affiliates}", 0 );
	$payouts_exists   = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $payouts_table ) ) ) === $payouts_table;
	if ( ! in_array( 'payout_email', $existing_columns, true ) || ! in_array( 'payout_method', $existing_columns, true ) || ! $payouts_exists ) {
		return;
	}

	// Bump the DB version.
	$affiliate_options['db_version'] = 0.9;
	update_option( 'pmpro_affiliates_options', $affiliate_options );
}
