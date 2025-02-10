<?php
/*
	Preheader
*/
function pmpro_affiliates_report_preheader() {
	if ( ! is_admin() ) {
		global $post, $current_user;
		if ( ( ! empty( $post->post_content ) && strpos( $post->post_content, '[pmpro_affiliates_report]' ) !== false )
			|| ( ! empty( $post->post_content_filtered ) && strpos( $post->post_content_filtered, '[pmpro_affiliates_report]' ) !== false ) ) {
			/*
				Preheader operations here.
			*/
			// get affiliates
			global $pmpro_affiliates;
			$pmpro_affiliates = pmpro_affiliates_getAffiliatesForUser();

			// no affiliates, get out of here
			if ( empty( $pmpro_affiliates ) ) {
				wp_redirect( pmpro_url( 'account' ) );
				exit;
			}
		}
	}
}
add_action( 'wp', 'pmpro_affiliates_report_preheader', 1 );

/*
	Shortcode Wrapper
*/
function pmpro_affiliates_report_shortcode( $atts, $content = null, $code = '' ) {
	global $post, $wpdb, $current_user, $pmpro_pages;

	$pmpro_affiliates          = pmpro_affiliates_getAffiliatesForUser();
	$pmpro_affiliates_settings = pmpro_affiliates_get_settings();

	// Default values from shortcode attribute. Block defaults are set in the block's register_block_type() function.
	extract(
		shortcode_atts(
			array(
				'back_link'  => '1',
				'export'     => '1',
				'export_csv' => '1',
				'help'       => '1',
				'fields'     => 'user_login,date,membership_level,total',
				'show_commissions_table' => '0'
			),
			$atts
		)
	);

	// Check if the fields values are coming from the shortcode instead.
	if ( ! is_array( $fields ) ) {
		$fields = explode( ',', $fields );
	}

	// Set the fields to values from the Block.
	if ( ! empty( $atts ) && is_array( $atts ) && has_block( 'pmpro-affiliates/pmpro-affiliates-report' ) ) {
		$fields = array_keys( array_filter( $atts ) );
	}

	if ( $back_link === '0' || $back_link === 'false' || $back_link === 'no' || ! $back_link ) {
		$back_link = false;
	} else {
		$back_link = true;
	}

	// Check if the block attribute export_csv value is false and set it to "export" to override the shortcode value.
	if ( ! $export_csv ) {
		$export = $export_csv;
	}

	if ( $export === '0' || $export === 'false' || $export === 'no' || ! $export ) {
		$export = false;
	} else {
		$export = true;
	}

	if ( $help === '0' || $help === 'false' || $help === 'no' || ! $help ) {
		$help = false;
	} else {
		$help = true;
	}

	if ( $show_commissions_table === '0' || $show_commissions_table === 'false' || $show_commissions_table === 'no' || ! $show_commissions_table ) {
		$show_commissions_table = false;
	} else {
		$show_commissions_table = true;
	}

	ob_start();
	/*
		Page Template HTML/ETC
	*/

	$pmpro_affiliates_singular_name = $pmpro_affiliates_settings['pmpro_affiliates_singular_name'];
	$pmpro_affiliates_plural_name   = $pmpro_affiliates_settings['pmpro_affiliates_plural_name'];

	if ( ! empty( $_REQUEST['report'] ) ) {
		$report = intval( $_REQUEST['report'] );
	} else {
		$report = null;
	}

	if ( count( $pmpro_affiliates ) == 1 ) {
		$report = $pmpro_affiliates[0]->id;
	}

	if ( $report ) {
		// show report
		$affiliate = $wpdb->get_row( "SELECT * FROM $wpdb->pmpro_affiliates WHERE id = '" . $report . "' LIMIT 1" );

		// no affiliate found?
		if ( empty( $affiliate ) ) {
			wp_redirect( pmpro_url( 'account' ) );
			exit;
		}

		// make sure admin or affiliate user
		if ( ! current_user_can( 'manage_options' ) && $current_user->user_login != $affiliate->affiliateuser ) {
			wp_redirect( pmpro_url( 'account' ) );
			exit;
		}

		// Get paid and unpaid commissions.
		$paid_commissions   = pmpro_affiliates_get_commissions( $affiliate->code, 'paid' );
		$unpaid_commissions = pmpro_affiliates_get_commissions( $affiliate->code, 'unpaid' );
		$total_commissions  = $paid_commissions + $unpaid_commissions;

		?>
		<?php if ( ! empty( $export ) ) { ?>
			<span class="pmpro_a-right"><a href="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>?action=affiliates_report_csv&report=<?php echo esc_html( $affiliate->id ); ?>"><?php esc_html_e( 'Export CSV', 'pmpro-affiliates' ); ?></a></span>
		<?php } ?>
		<h2><?php echo ucwords( $pmpro_affiliates_singular_name ); ?> <?php echo esc_html__( 'Report for Code:', 'pmpro-affiliates' ) . ' ' . esc_html( $affiliate->code ); ?></h2>
		<?php
			$sqlQuery = "SELECT a.code, o.affiliate_subid as subid, a.name, u.user_login, UNIX_TIMESTAMP(o.timestamp) as timestamp, o.total, o.membership_id, o.status FROM $wpdb->pmpro_membership_orders o LEFT JOIN $wpdb->pmpro_affiliates a ON o.affiliate_id = a.id LEFT JOIN $wpdb->users u ON o.user_id = u.ID WHERE o.affiliate_id <> '' AND o.status NOT IN('pending', 'error', 'refunded', 'refund', 'token', 'review') ";
		if ( $report != 'all' ) {
			$sqlQuery .= " AND a.id = '" . esc_sql( $report ) . "' ";
		}
			$affiliate_orders = $wpdb->get_results( $sqlQuery );

		if ( ! empty( $affiliate_orders ) ) {

			// Attribute to show/hide commission table.
			if ( $show_commissions_table ) {		
			?>
			<!-- Commissions Table -->
			<div class="pmpro_affiliates-table-container pmpro_affiliates-commissions">
				<div class="table-row table-row-heading">
					<div class="row-item"><?php esc_html_e( 'Commission Earned (All Time)', 'pmpro-affiliates' ); ?></div>
					<div class="row-item"><?php esc_html_e( 'Commission Paid (All Time)', 'pmpro-affiliates' ); ?></div>
					<div class="row-item"><?php esc_html_e( 'Commission Due', 'pmpro-affiliates' ); ?></div>
				</div>
				<div class="table-row table-row-body">
					<div class="row-item"><?php echo pmpro_formatPrice( $total_commissions ); ?></div>
					<div class="row-item"><?php echo pmpro_formatPrice( $paid_commissions ); ?></div>
					<div class="row-item"><?php echo pmpro_formatPrice( $unpaid_commissions ); ?></div>
				</div>
			</div>
			<?php } ?>

			<!-- Orders Table -->
			<div class="pmpro_affiliates-table-container pmpro_affiliates-orders">
				<div class="table-row table-row-heading">
					<?php if ( in_array( 'code', $fields ) ) { ?>
							<div class="row-item"><?php esc_html_e( 'Code', 'pmpro-affiliates' ); ?></div>
						<?php } ?>					
					<?php if ( in_array( 'subid', $fields ) ) { ?>
							<div class="row-item"><?php esc_html_e( 'Sub-ID', 'pmpro-affiliates' ); ?></div>
						<?php } ?>					
					<?php if ( in_array( 'name', $fields ) ) { ?>
							<div class="row-item"><?php esc_html_e( 'Name', 'pmpro-affiliates' ); ?></div>
						<?php } ?>					
					<?php if ( in_array( 'user_login', $fields ) ) { ?>
							<div class="row-item"><?php esc_html_e( 'Member', 'pmpro-affiliates' ); ?></div>
						<?php } ?>					
					<?php if ( in_array( 'date', $fields ) ) { ?>
							<div class="row-item"><?php esc_html_e( 'Date', 'pmpro-affiliates' ); ?></div>
						<?php } ?>					
					<?php if ( in_array( 'membership_level', $fields ) ) { ?>
							<div class="row-item"><?php esc_html_e( 'Level', 'pmpro-affiliates' ); ?></div>
						<?php } ?>
					<?php if ( in_array( 'show_commission', $fields ) ) { ?>
							<div class="row-item"><?php esc_html_e( 'Commission', 'pmpro-affiliates' ); ?></div>
					<?php } ?>
					<?php if ( in_array( 'total', $fields ) ) { ?>
							<div class="row-item"><?php esc_html_e( 'Order Total', 'pmpro-affiliates' ); ?></div>
						<?php } ?>
				</div>
				<?php
				global $pmpro_currency_symbol;
				foreach ( $affiliate_orders as $order ) {
					$level = pmpro_getLevel( $order->membership_id );
					?>
						<div class="table-row table-row-body">
						<?php if ( in_array( 'code', $fields ) ) { ?>
								<div class="row-item"><?php echo esc_html( $order->code ); ?></div>
							<?php } ?>
						<?php if ( in_array( 'subid', $fields ) ) { ?>
								<div class="row-item"><?php echo esc_html( $order->subid ); ?></div>
							<?php } ?>
						<?php if ( in_array( 'name', $fields ) ) { ?>
								<div class="row-item"><?php echo stripslashes( $order->name ); ?></div>
							<?php } ?>
						<?php if ( in_array( 'user_login', $fields ) ) { ?>
							<div class="row-item">
								<?php
									if ( ! empty( $order->user_login ) ) {
										echo esc_html( $order->user_login );
									} else { ?>
										[<?php esc_html_e( 'deleted', 'pmpro-affiliates' ); ?>]
									<?php }
								?>
							</div>
						<?php } ?>
						<?php if ( in_array( 'date', $fields ) ) { ?>
								<div class="row-item"><?php echo date_i18n( get_option( 'date_format' ), $order->timestamp ); ?></div>
							<?php } ?>
						<?php if ( in_array( 'membership_level', $fields ) ) { ?>
							<div class="row-item">
								<?php
									if ( ! empty( $level ) ) {
										echo esc_html( $level->name );
									} elseif ( $order->membership_id > 0 ) { ?>
										[<?php esc_html_e( 'deleted', 'pmpro-affiliates' ); ?>]
									<?php } else {
										esc_html_e( '&#8212;', 'pmpro-affiliates' );
									}
								?>
							</div>
						<?php } ?>
						<?php if ( in_array( 'show_commission', $fields ) ) { ?>
							<div class="row-item"><?php echo pmpro_formatPrice( $order->total * $affiliate->commissionrate ); ?></div>
						<?php } ?>
						<?php if ( in_array( 'total', $fields ) ) { ?>
								<div class="row-item"><?php echo pmpro_formatPrice( $order->total ); ?></div>
							<?php } ?>
						</div>
						<?php
				}
				?>
			</div>
				<?php
		} else {
			// there are no orders for this code
			?>
				<p><?php echo sprintf( esc_html__('No %s signups have been tracked yet.', 'pmpro-affiliates' ), $pmpro_affiliates_singular_name ); ?></p>
			<?php
		}
		?>
		<?php if ( ! empty( $help ) ) { ?>
		<div class="pmpro_affiliates-links">
			<h2><?php esc_html_e( 'How to Create Links for this Code', 'pmpro-affiliates' ); ?></h2>
			<p>
			<?php
			// translators: variables for affiliate codes
			echo sprintf(
				__( 'Add the string %1$1s (first parameter) or %2$2s (second or later parameter) to any link to this site. If you would like to track against specific campaigns, you can add the parameter %3$3s or %4$4s to your URL. Some example links are included below.', 'pmpro-affiliates' ),
				'<code>?pa=' . esc_html( $affiliate->code ) . '</code>',
				'<code>&amp;pa=' . esc_html( $affiliate->code ) . '</code>',
				'<code>?subid=CAMPAIGN_NAME</code>',
				'<code>&subid=CAMPAIGN_NAME</code>'
			);
			?>
			</p>

			<p><strong><?php esc_html_e( 'Homepage', 'pmpro-affiliates' ); ?>:</strong> <input type="text" style="width:100%;" readonly value="<?php echo esc_url( site_url() ); ?>/?pa=<?php echo esc_attr( $affiliate->code ); ?>" /></p>
			<p><strong><?php esc_html_e( 'Membership Levels', 'pmpro-affiliates' ); ?>:</strong> <input type="text" style="width:100%;" readonly value="<?php echo esc_attr( pmpro_url( 'levels' ) ); ?>?pa=<?php echo esc_attr( $affiliate->code ); ?>" /></p>
			<p><strong><?php esc_html_e( 'Homepage with Campaign Tracking', 'pmpro-affiliates' ); ?>:</strong> <input type="text" style="width:100%;" readonly value="<?php echo esc_attr( site_url() ); ?>/?pa=<?php echo esc_attr( $affiliate->code ); ?>&subid=FACEBOOK" /></p>
		</div>
		<?php } ?>
		<?php
	} else {
		// show affiliates
		?>
		<h2><?php esc_html_e( 'Select a Code', 'pmpro-affiliates' ); ?></h2>
		<ul>
			<?php foreach ( $pmpro_affiliates as $affiliate ) { ?>
				<li><a href="<?php echo esc_url( get_permalink( $post->ID ) ); ?>?report=<?php echo esc_attr( $affiliate->id ); ?>"><?php echo esc_html( $affiliate->code ); ?></a></li>
			<?php } ?>
		</ul>
		<?php
	}

	if ( ! empty( $back_link ) ) {
		$pmpro_pages_affiliate_report = $pmpro_pages['affiliate_report'];
		?>
		<p class="<?php echo pmpro_get_element_class( 'pmpro_actions_nav pmpro_affiliates-actions_nav', 'pmpro_affiliates-actions_nav' ); ?>">
			<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_actions_nav-right' ) ); ?>"><a href="<?php echo esc_url( pmpro_url( 'account' ) ); ?>"><?php esc_html_e( 'View Your Membership Account &rarr;', 'pmpro-affiliates' ); ?></a></span>
			<?php if ( ! empty( $report ) && ! empty( $pmpro_pages_affiliate_report ) ) { ?>
				<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_actions_nav-left' ) ); ?>"><a href="<?php echo esc_url( get_permalink( $pmpro_pages_affiliate_report ) ); ?>"><?php esc_html_e( '&larr; View All', 'pmpro-affiliates' ); ?></a></span>
			<?php } ?>
		</p> <!-- end pmpro_actions_nav -->
		<?php
	}

	$temp_content = ob_get_contents();
	ob_end_clean();
	return $temp_content;
}
add_shortcode( 'pmpro_affiliates_report', 'pmpro_affiliates_report_shortcode' );
