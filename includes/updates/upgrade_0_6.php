<?php
/**
 * Upgrade for version 0.6
 * Update the database to support commission rates.
 */
function pmpro_affiliates_upgrade_0_6() {
	global $wpdb;
	$wpdb->hide_errors();

    $affiliate_options =  get_option( 'pmpro_affiliates_options', false );

    if ( ! $affiliate_options ) {
        $affiliate_options = array();
    }

    // Let's not run this code if we are running a later version.
    if ( $affiliate_options['db_version'] >= 0.6 ) {
        return;
    }

    // Add the commissionrate column.
	$sqlQuery = "
		ALTER TABLE  `" . $wpdb->pmpro_affiliates . "` ADD  `commissionrate` decimal(10,2) NOT NULL DEFAULT '0.10' AFTER  `enabled`;
	";
	$wpdb->query( $sqlQuery );

    $affiliate_options['db_version'] = '0.6';
    update_option( 'pmpro_affiliates_options', $affiliate_options );

	return 0.6;
}