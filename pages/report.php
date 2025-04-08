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
				'show_conversion_table' => '0',
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

	if ( $show_conversion_table === '0' || $show_conversion_table === 'false' || $show_conversion_table === 'no' || ! $show_conversion_table ) {
		$show_conversion_table = false;
	} else {
		$show_conversion_table = true;
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
	}
	?>
	<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro' ) ); ?>">
		<?php
			if ( ! empty( $report ) ) {
				// Get paid and unpaid commissions.
				$paid_commissions   = pmpro_affiliates_get_commissions( $affiliate->code, 'paid' );
				$unpaid_commissions = pmpro_affiliates_get_commissions( $affiliate->code, 'unpaid' );
				$total_commissions  = $paid_commissions + $unpaid_commissions;
				?>
				<section id="pmpro_affiliates-report" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_section' ) ); ?>">
					<h2 class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_section_title pmpro_font-x-large' ) ); ?>">
						<?php echo esc_html( ucwords( $pmpro_affiliates_singular_name ) ); ?> <?php echo esc_html__( 'Report for Code:', 'pmpro-affiliates' ) . ' ' . esc_html( $affiliate->code ); ?>
					</h2>

					<?php
					$sqlQuery = "SELECT a.code, o.affiliate_subid as subid, a.name, u.user_login, UNIX_TIMESTAMP(o.timestamp) as timestamp, " . esc_sql( 'o.' . pmpro_affiliates_get_commission_calculation_source() ) . " as total, o.membership_id, o.status FROM $wpdb->pmpro_membership_orders o LEFT JOIN $wpdb->pmpro_affiliates a ON o.affiliate_id = a.id LEFT JOIN $wpdb->users u ON o.user_id = u.ID WHERE o.affiliate_id <> '' AND o.status NOT IN('pending', 'error', 'refunded', 'refund', 'token', 'review') ";
					if ( $report != 'all' ) {
						$sqlQuery .= " AND a.id = '" . esc_sql( $report ) . "' ";
					}
					$affiliate_orders = $wpdb->get_results( $sqlQuery );
	}
	?>
	<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro' ) ); ?>">
		<?php
			if ( ! empty( $report ) ) {
				// Get paid and unpaid commissions.
				$paid_commissions   = pmpro_affiliates_get_commissions( $affiliate->code, 'paid' );
				$unpaid_commissions = pmpro_affiliates_get_commissions( $affiliate->code, 'unpaid' );
				$total_commissions  = $paid_commissions + $unpaid_commissions;
				?>
				<section id="pmpro_affiliates-report" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_section' ) ); ?>">
					<h2 class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_section_title pmpro_font-x-large' ) ); ?>">
						<?php echo esc_html( ucwords( $pmpro_affiliates_singular_name ) ); ?> <?php echo esc_html__( 'Report for Code:', 'pmpro-affiliates' ) . ' ' . esc_html( $affiliate->code ); ?>
					</h2>

					<?php
					$sqlQuery = "SELECT a.code, o.affiliate_subid as subid, a.name, u.user_login, UNIX_TIMESTAMP(o.timestamp) as timestamp, " . esc_sql( 'o.' . pmpro_affiliates_get_commission_calculation_source() ) . " as total, o.membership_id, o.status FROM $wpdb->pmpro_membership_orders o LEFT JOIN $wpdb->pmpro_affiliates a ON o.affiliate_id = a.id LEFT JOIN $wpdb->users u ON o.user_id = u.ID WHERE o.affiliate_id <> '' AND o.status NOT IN('pending', 'error', 'refunded', 'refund', 'token', 'review') ";
					if ( $report != 'all' ) {
						$sqlQuery .= " AND a.id = '" . esc_sql( $report ) . "' ";
					}
					$affiliate_orders = $wpdb->get_results( $sqlQuery );

					if ( ! empty( $affiliate_orders ) ) {

						if ( $show_conversion_table ) {
							?>
							<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card' ) ); ?>">
								<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_content' ) ); ?>">
									<!-- Conversion Table -->
									<table class="pmpro_table">
										<thead>
											<tr>
												<th><?php esc_html_e( 'Commission Rate', 'pmpro-affiliates' ); ?></th>
												<th><?php esc_html_e( 'Visits (All Time)', 'pmpro-affiliates' ); ?></th>
												<th><?php esc_html_e( 'Conversion Rating (All Time)', 'pmpro-affiliates' ); ?></th>
											</tr>
										</thead>
										<tbody>
											<tr>
												<td data-title="<?php esc_html_e( 'Commission Rate', 'pmpro-affiliates' ); ?>"><?php echo esc_html( $affiliate->commissionrate * 100 . "%" ); ?></td>
												<td data-title="<?php esc_html_e( 'Visits (All Time)', 'pmpro-affiliates' ); ?>"><?php echo esc_html( $affiliate->visits ); ?></td>
												<td data-title="<?php esc_html_e( 'Conversion Rating (All Time)', 'pmpro-affiliates' ); ?>"><?php echo pmpro_affiliates_get_conversion_rate( $affiliate ); ?></td>
											</tr>
										</tbody>
									</table>
								</div> <!-- end pmpro_card_content -->
							</div> <!-- end pmpro_card -->
							<?php
						} // End of conversion table.

						// Attribute to show/hide commission table.
						if ( $show_commissions_table ) {
							?>
							<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card' ) ); ?>">
								<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_content' ) ); ?>">
									<!-- Commissions Table -->
									<table class="pmpro_table">
										<thead>
											<tr>
												<th><?php esc_html_e( 'Commission Earned (All Time)', 'pmpro-affiliates' ); ?></th>
												<th><?php esc_html_e( 'Commission Paid (All Time)', 'pmpro-affiliates' ); ?></th>
												<th><?php esc_html_e( 'Commission Due', 'pmpro-affiliates' ); ?></th>
											</tr>
										</thead>
										<tbody>
											<tr>
												<td data-title="<?php esc_html_e( 'Commission Earned (All Time)', 'pmpro-affiliates' ); ?>"><?php echo esc_html( pmpro_formatPrice( $total_commissions ) ); ?></td>
												<td data-title="<?php esc_html_e( 'Commission Paid (All Time)', 'pmpro-affiliates' ); ?>"><?php echo esc_html( pmpro_formatPrice( $paid_commissions ) ); ?></td>
												<td data-title="<?php esc_html_e( 'Commission Due', 'pmpro-affiliates' ); ?>"><?php echo esc_html( pmpro_formatPrice( $unpaid_commissions ) ); ?></td>
											</tr>
										</tbody>
									</table>
								</div> <!-- end pmpro_card_content -->
							</div> <!-- end pmpro_card -->
							<?php
						} ?>

						<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card' ) ); ?>">
							<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_content pmpro_affiliates-table' ) ); ?>">
								<!-- Orders Table -->
								<table class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_table' ) ); ?>">
									<thead>
										<tr class="table-row table-row-heading">
										<?php if ( in_array( 'code', $fields ) ) { ?>
												<th><?php esc_html_e( 'Code', 'pmpro-affiliates' ); ?></th>
											<?php } ?>
										<?php if ( in_array( 'subid', $fields ) ) { ?>
												<th><?php esc_html_e( 'Sub-ID', 'pmpro-affiliates' ); ?></th>
											<?php } ?>
										<?php if ( in_array( 'name', $fields ) ) { ?>
												<th><?php esc_html_e( 'Name', 'pmpro-affiliates' ); ?></th>
											<?php } ?>
										<?php if ( in_array( 'user_login', $fields ) ) { ?>
												<th><?php esc_html_e( 'Member', 'pmpro-affiliates' ); ?></th>
											<?php } ?>
										<?php if ( in_array( 'date', $fields ) ) { ?>
												<th><?php esc_html_e( 'Date', 'pmpro-affiliates' ); ?></th>
											<?php } ?>
										<?php if ( in_array( 'membership_level', $fields ) ) { ?>
												<th><?php esc_html_e( 'Level', 'pmpro-affiliates' ); ?></th>
											<?php } ?>
										<?php if ( in_array( 'show_commission', $fields ) ) { ?>
												<th><?php esc_html_e( 'Commission', 'pmpro-affiliates' ); ?></th>
										<?php } ?>
										<?php if ( in_array( 'total', $fields ) ) { ?>
												<th><?php esc_html_e( 'Order Total', 'pmpro-affiliates' ); ?></th>
											<?php } ?>
										</tr>
									</thead>
									<tbody>
									<?php
									foreach ( $affiliate_orders as $order ) {
										$level = pmpro_getLevel( $order->membership_id );
										?>
										<tr>
											<?php if ( in_array( 'code', $fields ) ) { ?>
												<td data-title="<?php esc_html_e( 'Code', 'pmpro-affiliates' ); ?>"><?php echo esc_html( $order->code ); ?></td>
											<?php } ?>
											<?php if ( in_array( 'subid', $fields ) ) { ?>
												<td data-title="<?php esc_html_e( 'Sub-ID', 'pmpro-affiliates' ); ?>"><?php echo esc_html( $order->subid ); ?></td>
											<?php } ?>
											<?php if ( in_array( 'name', $fields ) ) { ?>
												<td data-title="<?php esc_html_e( 'Name', 'pmpro-affiliates' ); ?>"><?php echo esc_html( stripslashes( $order->name ) ); ?></td>
											<?php } ?>
											<?php if ( in_array( 'user_login', $fields ) ) { ?>
												<td data-title="<?php esc_html_e( 'Member', 'pmpro-affiliates' ); ?>">
													<?php
														if ( ! empty( $order->user_login ) ) {
															echo esc_html( $order->user_login );
														} else { ?>
															[<?php esc_html_e( 'deleted', 'pmpro-affiliates' ); ?>]
														<?php }
													?>
												</td>
											<?php } ?>
											<?php if ( in_array( 'date', $fields ) ) { ?>
												<td data-title="<?php esc_html_e( 'Date', 'pmpro-affiliates' ); ?>"><?php echo esc_html( date_i18n( get_option( 'date_format' ), $order->timestamp ) ); ?></td>
											<?php } ?>
											<?php if ( in_array( 'membership_level', $fields ) ) { ?>
												<td data-title="<?php esc_html_e( 'Level', 'pmpro-affiliates' ); ?>">
													<?php
														if ( ! empty( $level ) ) {
															echo esc_html( $level->name );
														} elseif ( $order->membership_id > 0 ) { ?>
															[<?php esc_html_e( 'deleted', 'pmpro-affiliates' ); ?>]
														<?php } else {
															esc_html_e( '&#8212;', 'pmpro-affiliates' );
														}
													?>
												</td>
											<?php } ?>
											<?php if ( in_array( 'show_commission', $fields ) ) { ?>
												<td data-title="<?php esc_html_e( 'Commission', 'pmpro-affiliates' ); ?>"><?php echo esc_html( pmpro_formatPrice( $order->total * $affiliate->commissionrate ) ); ?></td>
											<?php } ?>
											<?php if ( in_array( 'total', $fields ) ) { ?>
												<td data-title="<?php esc_html_e( 'Order Total', 'pmpro-affiliates' ); ?>"><?php echo esc_html( pmpro_formatPrice( $order->total ) ); ?></td>
											<?php } ?>
										</tr>
										<?php
									}
									?>
									</tbody>
								</table> <!-- end pmpro_table -->
							</div> <!-- end pmpro_card_content -->
							<?php if ( ! empty( $export ) ) { ?>
								<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_actions' ) ); ?>">
									<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_btn-plain pmpro_btn-export' ) ); ?>"><a href="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>?action=affiliates_report_csv&report=<?php echo esc_html( $affiliate->id ); ?>"><?php esc_html_e( 'Export CSV', 'pmpro-affiliates' ); ?></a></span>
								</div> <!-- end pmpro_card_actions -->
							<?php } ?>
						</div> <!-- end pmpro_card -->
						<?php
					} else {
						// there are no orders for this code
						?>
						<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card' ) ); ?>">
							<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_content' ) ); ?>">
								<p><?php echo sprintf( esc_html__('No %s signups have been tracked yet.', 'pmpro-affiliates' ), esc_html( $pmpro_affiliates_singular_name ) ); ?></p>
							</div> <!-- end pmpro_card_content -->
						</div> <!-- end pmpro_card -->
						<?php }
					?>

					<?php if ( ! empty( $help ) ) { ?>
						<fieldset class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fieldset pmpro_affiliates-links', 'pmpro_affiliates-links' ) ); ?>">
							<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card' ) ); ?>">
								<legend class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_legend' ) ); ?>">
									<h2 class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_title pmpro_font-large' ) ); ?>"><?php esc_html_e( 'How to Create Links for this Code', 'pmpro-affiliates' ); ?></h2>
								</legend>
								<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_content' ) ); ?>">
									<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fields' ) ); ?>">
										<p>
											<?php
												// translators: variables for affiliate codes
												echo sprintf(
													esc_html__( 'Add the string %1$1s (first parameter) or %2$2s (second or later parameter) to any link to this site. If you would like to track against specific campaigns, you can add the parameter %3$3s or %4$4s to your URL. Some example links are included below.', 'pmpro-affiliates' ),
													'<code>?pa=' . esc_html( $affiliate->code ) . '</code>',
													'<code>&amp;pa=' . esc_html( $affiliate->code ) . '</code>',
													'<code>?subid=CAMPAIGN_NAME</code>',
													'<code>&subid=CAMPAIGN_NAME</code>'
												);
											?>
										</p>
										<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-text' ) ); ?>">
											<label for="homepage_link" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>"><?php esc_html_e( 'Homepage', 'pmpro-affiliates' ); ?></label>
											<input id="homepage_link" type="text" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-text' ) ); ?>" readonly value="<?php echo esc_url( site_url() ); ?>/?pa=<?php echo esc_attr( $affiliate->code ); ?>" />
										</div> <!-- end pmpro_form_field -->
										<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-text' ) ); ?>">
											<label for="levels_link" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>"><?php esc_html_e( 'Membership Levels', 'pmpro-affiliates' ); ?></label>
											<input id="levels_link" type="text" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-text' ) ); ?>" readonly value="<?php echo esc_attr( pmpro_url( 'levels' ) ); ?>?pa=<?php echo esc_attr( $affiliate->code ); ?>" />
										</div> <!-- end pmpro_form_field -->
										<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-text' ) ); ?>">
											<label for="checkout_link" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>"><?php esc_html_e( 'Homepage with Campaign Tracking', 'pmpro-affiliates' ); ?></label>
											<input id="checkout_link" type="text" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-text' ) ); ?>" readonly value="<?php echo esc_attr( site_url() ); ?>/?pa=<?php echo esc_attr( $affiliate->code ); ?>&subid=FACEBOOK" />
										</div> <!-- end pmpro_form_field -->
									</div> <!-- end pmpro_form_fields -->
								</div> <!-- end pmpro_card_content -->
							</div> <!-- end pmpro_card -->
						</fieldset> <!-- end pmpro_form_fieldset -->
					<?php } ?>
				</section> <!-- end pmpro_section -->
		<?php } else { ?>
			<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card' ) ); ?>">
				<h2 class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_title pmpro_font-large' ) ); ?>"><?php esc_html_e( 'Select a Code', 'pmpro-affiliates' ); ?></h2>
				<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_content' ) ); ?>">
					<ul class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_list' ) ); ?>">
						<?php foreach ( $pmpro_affiliates as $affiliate ) { ?>
							<li class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_list_item' ) ); ?>">
								<a href="<?php echo esc_url( get_permalink( $post->ID ) ); ?>?report=<?php echo esc_attr( $affiliate->id ); ?>"><?php echo esc_html( $affiliate->code ); ?></a>
							</li>
						<?php } ?>
					</ul>
				</div> <!-- end pmpro_card_content -->
			</div> <!-- end pmpro_card -->
		<?php } ?>

		<?php if ( ! empty( $back_link ) ) {
			$pmpro_pages_affiliate_report = $pmpro_pages['affiliate_report'];
			?>
			<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_actions_nav pmpro_affiliates-actions_nav', 'pmpro_affiliates-actions_nav' ) ); ?>">
				<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_actions_nav-right' ) ); ?>"><a href="<?php echo esc_url( pmpro_url( 'account' ) ); ?>"><?php esc_html_e( 'View Your Membership Account &rarr;', 'pmpro-affiliates' ); ?></a></span>
				<?php if ( ! empty( $report ) && ! empty( $pmpro_pages_affiliate_report ) ) { ?>
					<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_actions_nav-left' ) ); ?>"><a href="<?php echo esc_url( get_permalink( $pmpro_pages_affiliate_report ) ); ?>"><?php esc_html_e( '&larr; View All', 'pmpro-affiliates' ); ?></a></span>
				<?php } ?>
			</div> <!-- end pmpro_actions_nav -->
		<?php }  ?>
	</div> <!-- end pmpro -->
	<?php
	$temp_content = ob_get_contents();
	ob_end_clean();
	return $temp_content;
}
add_shortcode( 'pmpro_affiliates_report', 'pmpro_affiliates_report_shortcode' );
