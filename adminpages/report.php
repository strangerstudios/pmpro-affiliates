<?php	
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

	$pmpro_affiliates_settings = pmpro_affiliates_get_settings();
	$pmpro_affiliates_singular_name = $pmpro_affiliates_settings['pmpro_affiliates_singular_name'];
	$pmpro_affiliates_plural_name = $pmpro_affiliates_settings['pmpro_affiliates_plural_name'];

	if ( isset( $_REQUEST['report'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only report view.
		$report = sanitize_text_field( wp_unslash( $_REQUEST['report'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only report view.
	} else {
		$report = false;
	}

	if ( $report && $report != "all" ) {
		//get values from DB
		$affiliate_id = $report;		
		$affiliate = $wpdb->get_row( "SELECT * FROM $wpdb->pmpro_affiliates WHERE id = '" . intval( $affiliate_id ) . "' LIMIT 1" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Plugin custom table; value is intval() inside quotes.
		if ( ! empty( $affiliate ) && ! empty( $affiliate->id ) ) {
			$code = $affiliate->code;
			$name = $affiliate->name;
			$affiliateuser = $affiliate->affiliateuser;
			$trackingcode = $affiliate->trackingcode;
			$cookiedays = $affiliate->cookiedays;
			$enabled = $affiliate->enabled;
		}
	}

	// Check for payout filter.
	$payout_id       = isset( $_REQUEST['payout_id'] ) ? (int) $_REQUEST['payout_id'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only report view.
	$payout          = null;
	$payout_order_ids = array();
	if ( $payout_id > 0 ) {
		$payout = pmpro_affiliates_get_payout( $payout_id );
		if ( $payout ) {
			$payout_order_ids = array_filter( array_map( 'intval', explode( ',', $payout->order_ids ) ) );
		} else {
			$payout_id = 0; // Invalid payout — clear the filter.
		}
	}

	// Get current page for pagination.
	$paged = isset( $_REQUEST['paged'] ) ? max( 1, intval( $_REQUEST['paged'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only pagination.

	/**
	 * Filter the number of orders to show per page.
	 *
	 * @since 0.8
	 *
	 * @param int $limit The number of orders per page. Default 20.
	 */
	$limit = apply_filters( 'pmpro_affiliates_report_orders_per_page', 20 );
?>
	<h1>
		<?php echo esc_html( ucwords($pmpro_affiliates_singular_name) ); ?> Report
		<?php
			if ( $payout_id && $payout ) {
				printf(
					/* translators: 1: affiliate code, 2: payout ID */
					esc_html__( 'for Payout #%1$d (%2$s)', 'pmpro-affiliates' ),
					$payout_id,
					! empty( $code ) ? esc_html( stripslashes( $code ) ) : esc_html( sprintf( __( 'Affiliate #%d', 'pmpro-affiliates' ), $payout->affiliate_id ) )
				);
			} elseif ( empty( $affiliate_id ) ) {
				echo esc_html( sprintf( esc_html__("for All %s", 'pmpro-affiliates' ), ucwords( $pmpro_affiliates_plural_name ) ) );
			} else {
				echo esc_html( sprintf( esc_html__("for Code %s", 'pmpro-affiliates' ), stripslashes( $code ) ) );
			}
		?>
		<?php if ( ! $payout_id ) : ?>
		<a href="<?php echo esc_url( admin_url('admin-ajax.php') );?>?action=affiliates_report_csv&report=<?php echo esc_attr( $report );?>&_wpnonce=<?php echo esc_attr( wp_create_nonce( 'pmpro_affiliates_report_csv' ) ); ?>" class="add-new-h2"><?php esc_html_e('Export to CSV', 'pmpro-affiliates' ); ?></a>
		<?php endif; ?>
		<?php if ( ! empty( $affiliate_id ) ) : ?>
			<?php if ( $payout_id ) : ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-affiliates&report=' . (int) $affiliate_id ) ); ?>" class="add-new-h2"><?php echo esc_html( sprintf( esc_html__( 'View All Orders for %s', 'pmpro-affiliates' ), stripslashes( $code ) ) ); ?></a>
			<?php endif; ?>
			<a href="admin.php?page=pmpro-affiliates&report=all" class="add-new-h2"><?php echo esc_html( sprintf( esc_html__( 'View All %s Report', 'pmpro-affiliates' ), ucwords( $pmpro_affiliates_plural_name ) ) ); ?></a>
		<?php endif; ?>
	</h1>
<?php
	if ( empty( $payout_id ) ) {
		$affiliate_user_object = get_user_by( 'login', stripslashes( $affiliateuser ) );
		if ( ! empty( $affiliate_user_object ) ) {
			$affiliate_user_shown = '<a href="' . esc_url( get_edit_user_link( $affiliate_user_object->ID ) ) . '">' . esc_html( $affiliate_user_object->display_name ) . '</a>';
		} else {
			$affiliate_user_shown = esc_html( stripslashes( $affiliateuser ) );
		}

		if ( ! empty( $name ) ) {
			echo "<p>". esc_html( sprintf( esc_html__("Business/Contact Name: %s", 'pmpro-affiliates' ), stripslashes($name) ) ) . "</p>";
		}

		if ( ! empty( $affiliateuser ) ) {
			// The $affiliate_user_shown is escaped before echoing it out.
			echo "<p>" . esc_html( ucwords($pmpro_affiliates_singular_name) ) . " ". esc_html__("User:", 'pmpro-affiliates' )." " . wp_kses_post( $affiliate_user_shown ) . "</p>";
		}
	}

	if ( $payout_id && $payout ) {
		printf(
			'<p>' . esc_html__( 'Date Range: %s', 'pmpro-affiliates' ) . '</p>',
			esc_html( pmpro_affiliates_format_payout_date_range( $payout ) )
		);
	}

	// A payout with no order IDs would otherwise drop the filter and show every order.
	if ( $payout_id && empty( $payout_order_ids ) ) {
		echo '<p>' . esc_html__( 'This payout has no orders.', 'pmpro-affiliates' ) . '</p>';
	} else {
		// Output the table, shows pagination by default.
		pmpro_affiliates_display_orders_table( array(
			'affiliate_id'  => $report,
			'order_ids'     => $payout_order_ids,
			'empty_message' => sprintf( esc_html__( 'No %s signups have been tracked yet.', 'pmpro-affiliates' ), $pmpro_affiliates_singular_name ),
			'limit'         => $payout_id ? 0 : (int) $limit, // No pagination for payout view (payouts are typically small).
			'paged'         => (int) $paged,
		) );
	}
