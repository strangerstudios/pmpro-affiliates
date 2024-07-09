<?php
	global $wpdb, $pmpro_currency_symbol, $current_user;

if ( isset( $_REQUEST['report'] ) ) {
	$report = sanitize_text_field( $_REQUEST['report'] );
} else {
	$report = false;
}

if ( $report && $report !== 'all' ) {
	// Get values from DB.
	$affiliate_id = $report;
	$affiliate    = $wpdb->get_row( "SELECT * FROM $wpdb->pmpro_affiliates WHERE id = '" . intval( $affiliate_id ) . "' LIMIT 1" );
	if ( ! empty( $affiliate ) && ! empty( $affiliate->id ) ) {
		$code          = $affiliate->code;
		$name          = $affiliate->name;
		$affiliateuser = $affiliate->affiliateuser;
		$trackingcode  = $affiliate->trackingcode;
		$cookiedays    = $affiliate->cookiedays;
		$enabled       = $affiliate->enabled;
	}
}

	// Only admins can get this.
if ( ! function_exists( 'current_user_can' )
	|| ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'pmpro_affiliates_report_csv' ) && ( $report != 'all' && $current_user->user_login != $affiliate->affiliateuser ) )
) {
	die( __( 'You do not have permissions to perform this action.', 'pmpro-affiliates' ) );
}

	$sql_query = "SELECT o.id as order_id, a.code, a.commissionrate, o.affiliate_subid as subid, a.name, u.user_login, o.user_id, o.membership_id, UNIX_TIMESTAMP(o.timestamp) as timestamp, o.total, o.status, om.meta_value as affiliate_paid
	FROM $wpdb->pmpro_membership_orders o 
	LEFT JOIN $wpdb->pmpro_affiliates a 
	ON o.affiliate_id = a.id 
	LEFT JOIN $wpdb->users u 
	ON o.user_id = u.ID
	LEFT JOIN $wpdb->pmpro_membership_ordermeta om
	ON o.id = om.pmpro_membership_order_id
	AND om.meta_key = 'pmpro_affiliate_paid'
	WHERE o.affiliate_id <> ''
	AND o.affiliate_id IS NOT NULL
	AND o.affiliate_id <> 0
	AND o.status NOT IN('pending', 'error', 'refunded', 'refund', 'token', 'review')";


if ( $report !== 'all' ) {
	$sql_query .= " AND a.id = '" . esc_sql( $report ) . "' ";
}
	$affiliate_orders = $wpdb->get_results( $sql_query );

	// Begin output.
	header( 'Content-type: text/csv' );
	header( 'Content-Disposition: attachment; filename=affiliates_report.csv' );

	// Headings.
	$headings = array(
		'code',
		'sub_id',
		'user_id',
		'user_login',
		'affiliate_name',
		'date',
		'commission_rate',
		'commission_earned',
		'total',
	);

	$headings = apply_filters( 'pmpro_affiliate_list_csv_extra_columns', $headings ); // Add to the string.

	echo implode( ',', $headings ) . "\n";

	if ( ! empty( $affiliate_orders ) ) {
		global $pmpro_currency_symbol;
		foreach ( $affiliate_orders as $order ) {
			$level = pmpro_getLevel( $order->membership_id );

			$pmpro_affiliate_report_data = array(
				pmpro_enclose( $order->code ),
				pmpro_enclose( $order->subid ),
				pmpro_enclose( (int) $order->user_id ),
				pmpro_enclose( $order->user_login ),
				pmpro_enclose( $order->name ),
				pmpro_enclose( date_i18n( 'Y-m-d', $order->timestamp ) ),
				pmpro_enclose( $order->commissionrate * 100 ) . '%',
				pmpro_enclose( number_format( $order->total * $order->commissionrate, 2 ) ),
				pmpro_enclose( $order->total ),
			);

			$pmpro_affiliate_report_data = apply_filters( 'pmpro_affiliate_list_csv_extra_column_data', $pmpro_affiliate_report_data, $order, $level );

			echo implode( ',', $pmpro_affiliate_report_data ) . "\n";
		}
	}

	/**
	 * Enclose a string in double quotes.
	 *
	 * @param string $s The string to enclose.
	 *
	 * @return string The enclosed string.
	 */
	function pmpro_enclose( $s ) {
		return '"' . str_replace( '"', '\\"', $s ) . '"';
	}
