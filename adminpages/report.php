<?php	
	$pmpro_affiliates_settings = pmpro_affiliates_get_settings();
	$pmpro_affiliates_singular_name = $pmpro_affiliates_settings['pmpro_affiliates_singular_name'];
	$pmpro_affiliates_plural_name = $pmpro_affiliates_settings['pmpro_affiliates_plural_name'];

	if ( isset( $_REQUEST['report'] ) ) {	
		$report = sanitize_text_field( $_REQUEST['report'] );
	} else {
		$report = false;
	}
	
	if ( $report && $report != "all" ) {
		//get values from DB
		$affiliate_id = $report;		
		$affiliate = $wpdb->get_row( "SELECT * FROM $wpdb->pmpro_affiliates WHERE id = '" . intval( $affiliate_id ) . "' LIMIT 1" );
		if ( ! empty( $affiliate ) && ! empty( $affiliate->id ) ) {
			$code = $affiliate->code;
			$name = $affiliate->name;
			$affiliateuser = $affiliate->affiliateuser;
			$trackingcode = $affiliate->trackingcode;
			$cookiedays = $affiliate->cookiedays;
			$enabled = $affiliate->enabled;
		}
	}	

	// Get current page for pagination.
	$paged = isset( $_REQUEST['paged'] ) ? max( 1, intval( $_REQUEST['paged'] ) ) : 1;

	/**
	 * Filter the number of orders to show per page.
	 *
	 * @since TBD
	 *
	 * @param int $limit The number of orders per page. Default 20.
	 */
	$limit = apply_filters( 'pmpro_affiliates_report_orders_per_page', 20 );
?>
	<h2>
		<?php echo esc_html( ucwords($pmpro_affiliates_singular_name) ); ?> Report
		<?php 
			if ( empty( $affiliate_id ) ) {
				echo esc_html( sprintf( esc_html__("for All %s", 'pmpro-affiliates' ), ucwords( $pmpro_affiliates_plural_name ) ) );
			} else {
				echo esc_html( sprintf( esc_html__("for Code %s", 'pmpro-affiliates' ), stripslashes( $code ) ) );
			}
		?>
		<a href="<?php echo esc_url( admin_url('admin-ajax.php') );?>?action=affiliates_report_csv&report=<?php echo esc_attr( $report );?>" class="add-new-h2"><?php esc_html_e('Export to CSV', 'pmpro-affiliates' ); ?></a>
		<?php 
			if ( ! empty( $affiliate_id ) ) {
				?>
				<a href="admin.php?page=pmpro-affiliates&report=all" class="add-new-h2"><?php echo esc_html( sprintf( esc_html__( 'View All %s Report', 'pmpro-affiliates' ), ucwords( $pmpro_affiliates_plural_name ) ) ); ?></a>
				<?php
			}
		?>
	</h2>
<?php
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

	// Output the table, shows pagination by default.
	pmpro_affiliates_display_orders_table( array(
		'affiliate_id'  => $report,
		'empty_message' => sprintf( esc_html__( 'No %s signups have been tracked yet.', 'pmpro-affiliates' ), $pmpro_affiliates_singular_name ),
		'limit'         => 1,
		'paged'         => $paged,
	) );
