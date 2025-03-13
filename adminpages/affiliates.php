<?php
	//vars
	global $wpdb, $pmpro_currency_symbol;

	if(isset($_REQUEST['edit']))
		$edit = sanitize_text_field( $_REQUEST['edit'] );
	else
		$edit = false;

	if(isset($_REQUEST['report']))
		$report = sanitize_text_field( $_REQUEST['report'] );
	else
		$report = false;

	if(isset($_REQUEST['settings']))
		$settings = sanitize_text_field( $_REQUEST['settings'] );
	else
		$settings = false;

	if(isset($_REQUEST['s']))
		$s = sanitize_text_field( $_REQUEST['s'] );
	else
		$s = false;

	if(isset($_REQUEST['copy']))
		$copy = sanitize_text_field( $_REQUEST['copy'] );
	else
		$copy = false;

	if(isset($_REQUEST['delete']))
		$delete = sanitize_text_field( $_REQUEST['delete'] );
	else
		$delete = false;

	if(!empty($_REQUEST['save']))
		$save = true;
	else
		$save = false;

	//get form values
	if ( ! empty( $save ) ) {
		if ( isset( $_REQUEST['code'] ) ) {
			$code = sanitize_text_field( preg_replace("[^a-zA-Z0-9]", "", $_REQUEST['code']) );
		}

		if ( isset( $_REQUEST['name'] ) ) {
			$name = sanitize_text_field( $_REQUEST['name'] );
		}

		if ( isset( $_REQUEST['affiliateuser'] ) ) {
			$affiliateuser = sanitize_text_field( $_REQUEST['affiliateuser'] );
		}

		if ( isset( $_REQUEST['trackingcode'] ) ) {
			$trackingcode = sanitize_text_field( $_REQUEST['trackingcode'] );
		}

		if ( isset( $_REQUEST['commissionrate'] ) ) {
			if ( intval( $_REQUEST['commissionrate'] ) > 100 ) {
				$rate = 100;
			} else {
				$rate = intval( $_REQUEST['commissionrate'] );
			}

			$commissionrate = $rate / 100; //convert to decimal
		}

		if ( isset( $_REQUEST['cookiedays'] ) ) {
			$cookiedays = sanitize_text_field( preg_replace( "[^0-9]", "", $_REQUEST['cookiedays'] ) );
		}

		if ( isset( $_REQUEST['enabled'] ) ) {
			$enabled = sanitize_text_field( $_REQUEST['enabled'] );
		}

	} elseif ( $edit > 0 || ($report && $report != "all" ) || $copy ) {
		//get values from DB
		if($edit > 0)
			$affiliate_id = $edit;
		elseif($report)
			$affiliate_id = $report;
		elseif($copy)
			$affiliate_id = $copy;
		$affiliate = $wpdb->get_row("SELECT * FROM $wpdb->pmpro_affiliates WHERE id = '" . intval($affiliate_id) . "' LIMIT 1");
		if(!empty($affiliate->id))
		{
			$code = $affiliate->code;
			$name = $affiliate->name;
			$affiliateuser = $affiliate->affiliateuser;
			$trackingcode = $affiliate->trackingcode;
			$cookiedays = $affiliate->cookiedays;
			$enabled = $affiliate->enabled;
			$commissionrate = $affiliate->commissionrate * 100; //Stored as decimal, but we want to show as percent.
		}
	}
	else
	{
		//defaults
		$code = pmpro_affiliates_getNewCode();
		$name = '';
		$affiliateuser = '';
		$trackingcode = '';
		$cookiedays = 30;
		$commissionrate = 0;
		/**
		 * Filter to adjust the number of days a cookie is valid for by default.
		 * This can also be set and modified for each individual cookie.
		 *
		 * @param mixed $cookiedays - number of days cookie should last, accepts numerical string or integer
		 *
		 * @return mixed  number of days cookie should last
		 */
		$cookiedays = apply_filters( 'pmpro_affiliate_default_cookie_duration' , $cookiedays );
		$cookiedays = intval( $cookiedays );
		$enabled = true;
	}

	if($edit && $save)
	{
		//updating or new?
		if($edit > 0)
		{
			$sqlQuery = "UPDATE $wpdb->pmpro_affiliates SET code = '" . esc_sql($code) . "', name = '" . esc_sql($name) . "', affiliateuser = '" . esc_sql($affiliateuser) . "', trackingcode = '" . esc_sql($trackingcode) . "', commissionrate = '" . esc_sql( $commissionrate ) . "', cookiedays = '" . esc_sql($cookiedays) . "', enabled = '" . esc_sql($enabled) . "' WHERE id = '" . esc_sql( $edit ) . "' LIMIT 1";
			if($wpdb->query($sqlQuery) !== false)
			{
				//all good
				$edit = false;
				$pmpro_msg = __( 'Affiliate saved successfully.', 'pmpro-affiliates');
				$pmpro_msgt = "success";
			}
			else
			{
				//error
				$pmpro_msg = __( 'There was an error saving the affiliate.', 'pmpro-affiliates' );
				$pmpro_msgt = "error";
			}
		}
		else
		{
			$sqlQuery = "INSERT INTO $wpdb->pmpro_affiliates (code, name, affiliateuser, trackingcode, cookiedays, enabled, commissionrate) VALUES('" . esc_sql($code) . "', '" . esc_sql($name) . "', '" . esc_sql($affiliateuser) . "', '" . esc_sql($trackingcode) . "', '" . esc_sql($cookiedays) . "', '" . esc_sql($enabled) . "', '" . esc_sql( $commissionrate ) . "')";
			if($wpdb->query($sqlQuery) !== false)
			{
				//all good
				$edit = false;
				$pmpro_msg = __( 'Affiliate added successfully.', 'pmpro-affiliates' );
				$pmpro_msgt = "success";
			}
			else
			{
				//error
				$pmpro_msg = __( 'There was an error adding the affiliate.', 'pmpro-affiliates' );
				$pmpro_msgt = "error";
			}
		}

	}

	//are we deleting?
	if(!empty($delete))
	{
		$sqlQuery = "DELETE FROM $wpdb->pmpro_affiliates WHERE id=" . esc_sql($delete) . " LIMIT 1";
		if($wpdb->query($sqlQuery) !== false)
		{
			//all good
			$delete = false;
			$pmpro_msg = __( 'Affiliate deleted successfully.', 'pmpro-affiliates' );
			$pmpro_msgt = "success";
		}
		else
		{
			//error
			$pmpro_msg = __( 'There was an error deleting the affiliate.', 'pmpro-affiliates' );
			$pmpro_msgt = "error";
		}
	}

	//get settings for default term names
	$pmpro_affiliates_settings = pmpro_affiliates_get_settings();
	$pmpro_affiliates_singular_name = $pmpro_affiliates_settings['pmpro_affiliates_singular_name'];
	$pmpro_affiliates_plural_name = $pmpro_affiliates_settings['pmpro_affiliates_plural_name'];
?>
<?php
	require_once( PMPRO_DIR . "/adminpages/admin_header.php" );
	?>
	<h2>
		<?php echo sprintf( esc_html__('%s Add On: Lightweight %s Tracking', 'pmpro-affiliates' ), ucwords($pmpro_affiliates_plural_name), ucwords($pmpro_affiliates_plural_name) ); ?>
	</h2>

	<nav class="nav-tab-wrapper">
		<a href="admin.php?page=pmpro-affiliates" class="nav-tab<?php if(empty($report) && empty($settings)) { ?> nav-tab-active<?php } ?>"><?php _e('Manage', 'pmpro-affiliates' ); ?> <?php echo ucwords($pmpro_affiliates_plural_name); ?></a>
		<a href="admin.php?page=pmpro-affiliates&report=all" class="nav-tab<?php if(!empty($report)) { ?> nav-tab-active<?php } ?>"><?php _e('Reports', 'pmpro-affiliates' ); ?></a>
		<a href="admin.php?page=pmpro-affiliates&settings=1" class="nav-tab<?php if(!empty($settings)) { ?> nav-tab-active<?php } ?>"><?php _e('Settings', 'pmpro-affiliates' ); ?></a>
	</nav>
	<br class="clear" />
	<?php

	if($edit)
	{
		?>
		<h2>
			<?php
				if($edit > 0)
					echo sprintf( esc_html__( 'Edit %s', 'pmpro-affiliates' ), ucwords( $pmpro_affiliates_singular_name ) );
				else
					echo sprintf( esc_html__('Add New %s', 'pmpro-affiliates'), ucwords($pmpro_affiliates_singular_name) );
			?>
		</h2>

		<?php if(!empty($pmpro_msg)) { ?>
			<div id="message" class="<?php if($pmpro_msgt == "success") echo "updated fade"; else echo "error"; ?>"><p><?php echo esc_html( $pmpro_msg )?></p></div>
		<?php } ?>

		<div>

			<form action="" method="post">
				<input name="saveid" type="hidden" value="<?php echo esc_attr( $edit ); ?>" />
				<table class="form-table">
				<tbody>
					<tr>
						<th scope="row" valign="top"><label><?php _e('ID:', 'pmpro-affiliates'); ?></label></th>
						<td class="pmpro_lite"><?php if(!empty($affiliate->id)) echo esc_html( $affiliate->id ); else esc_html_e("This will be generated when you save.", 'pmpro-affiliates'); ?></td>
					</tr>

					<tr>
						<th scope="row" valign="top"><label for="code"><?php _e('Code:', 'pmpro-affiliates'); ?></label></th>
						<td>
							<input id="code" name="code" type="text" size="20" value="<?php if(!empty($code)) echo esc_attr($code);?>" />
							<small><?php echo sprintf( esc_html__('Value added to the site URL to designate the %s link. (e.g. "&pa=CODE" or "?pa=CODE")', 'pmpro-affiliates' ), $pmpro_affiliates_singular_name ); ?></small>
						</td>
					</tr>

					<tr>
						<th scope="row" valign="top"><label for="name"><?php esc_html_e('Business/Contact Name:', 'pmpro-affiliates'); ?></label></th>
						<td>
							<input id="name" name="name" type="text" size="40" value="<?php if(!empty($name)) echo esc_attr(stripslashes($name));?>" />
						</td>
					</tr>

					<tr>
						<th scope="row" valign="top"><label for="affiliateuser"><?php echo sprintf('%s User:', ucwords($pmpro_affiliates_singular_name), 'pmpro-affiliates'); ?></label></th>
						<td>
							<input id="affiliateuser" name="affiliateuser" type="text" size="20" value="<?php if(!empty($affiliateuser)) echo esc_attr($affiliateuser);?>" />
							<small><?php echo sprintf( esc_html__('The username of a WordPress user in your site who should have access to %s reports.', 'pmpro-affiliates'), $pmpro_affiliates_singular_name ); ?></small>
						</td>
					</tr>

					<tr>
						<th scope="row" valign="top"><label for="commissionrate"><?php esc_html_e('Commission Rate (%)', 'pmpro-affiliates'); ?></label></th>
						<td>
							<input name="commissionrate" type="text" size="5" value="<?php if(!empty($commissionrate)) echo esc_attr($commissionrate);?>" maxlength="3" />
							<small><?php esc_html_e( 'Enter the percentage value of the commission to be earned.', 'pmpro-affiliates' ); ?></small>
						</td>
					</tr>

					<tr>
						<th scope="row" valign="top"><label for="trackingcode"><?php _e('Tracking Code:', 'pmpro-affiliates'); ?></label></th>
						<td>
							<textarea id="trackingcode" name="trackingcode" rows="6" cols="60"><?php if(!empty($trackingcode)) echo esc_textarea(stripslashes($trackingcode));?></textarea>
							<br /><small><?php echo sprintf( esc_html__("(Optional) If you are tracking this %s through another system, you can add HTML/JS code here to run on the confirmation page after checkout. Variables:", 'pmpro-affiliates' ), $pmpro_affiliates_singular_name ); ?> !!ORDER_ID!!, !!ORDER_AMOUNT!!, !!LEVEL_NAME!!</small>
						</td>
					</tr>

					<tr>
						<th scope="row" valign="top"><label for="cookiedays"><?php esc_html_e('Cookie Length:', 'pmpro-affiliates'); ?></label></th>
						<td>
							<input name="cookiedays" type="text" size="5" value="<?php if(!empty($cookiedays)) echo esc_attr($cookiedays);?>" />
							<small><?php esc_html_e( 'In days.', 'pmpro-affiliates' ); ?></small>
						</td>
					</tr>

					<tr>
						<th scope="row" valign="top"><label for="enabled"><?php _e('Enabled:', 'pmpro-affiliates'); ?></label></th>
						<td id="enabled">
							<input type="radio" name="enabled" value="1" <?php if(!empty($enabled)) { ?>checked="checked"<?php } ?>><?php esc_html_e( 'Yes', 'pmpro-affiliates'); ?>
							&nbsp;
							<input type="radio" name="enabled" value="0" <?php if(empty($enabled)) { ?>checked="checked"<?php } ?>><?php esc_html_e( 'No', 'pmpro-affiliates'); ?>
						</td>
					</tr>

					<?php 

						// Get totals for commissions and display it as a readonly view.
						if ( $code ) {
							$paid_commissions   = pmpro_affiliates_get_commissions( $code, 'paid' );
							$unpaid_commissions = pmpro_affiliates_get_commissions( $code, 'unpaid' );
							$total_commissions  = $paid_commissions + $unpaid_commissions;
						} else {
							$paid_commissions = 0;
							$unpaid_commissions = 0;
							$total_commissions = 0;
						}
						
					?>
					<tr>
						<th scope="row" valign="top"><label for="commission_earned"><?php esc_html_e('Commission Earned (All time):', 'pmpro-affiliates'); ?></label></th>
						<td>
							<?php echo pmpro_formatPrice( $total_commissions ); ?>
						</td>
					</tr>

					<tr>
						<th scope="row" valign="top"><label for="commission_paid"><?php esc_html_e('Commission Paid (All time):', 'pmpro-affiliates'); ?></label></th>
						<td>
							<?php echo pmpro_formatPrice( $paid_commissions ); ?>
						</td>
					</tr>

					<tr>
						<th scope="row" valign="top"><label for="commission_due"><?php esc_html_e('Commission Due:', 'pmpro-affiliates'); ?></label></th>
						<td>
							<?php 
							echo pmpro_formatPrice( $unpaid_commissions ); 
							if ( ! empty( $affiliate_id ) ) {
								echo ' (<a href="' . esc_url( get_admin_url(NULL, '/admin.php?page=pmpro-affiliates&report=' . (int) $affiliate_id ) ).'">' . esc_html__( 'view report', 'pmpro-affiliates' ) .'</a>)';
							}
							
							?>
						</td>
					</tr>	
				</tbody>
			</table>

			<?php do_action("pmpro_affiliate_after_settings"); ?>

			<p class="submit topborder">
				<input name="edit" type="hidden" value="<?php if(!empty($edit)) echo esc_attr( $edit ); ?>" />
				<input name="save" type="hidden" value="1" />
				<input type="submit" class="button-primary" value="<?php echo sprintf(__('Save %s','pmpro-affiliates'), ucwords($pmpro_affiliates_singular_name) ); ?>" />
				<input name="cancel" class="button" type="button" value="Cancel" onclick="location.href='<?php echo esc_url( get_admin_url( NULL, '/admin.php?page=pmpro-affiliates' ) ); ?>';" />
			</p>
			</form>
		</div>
		<?php
	} elseif ( $settings ) {
		//show the settings for affiliate add ons
		require_once("settings.php");
	} elseif ( $report ) {
		//show the report for affiliate activity
		require_once("report.php");
	} else { ?>
		<form id="posts-filter" method="get" action="">

		<h1 class="wp-heading-inline">
			<?php echo esc_html( ucwords( $pmpro_affiliates_plural_name ) ); ?>
		</h1>

		<a href="admin.php?page=pmpro-affiliates&edit=-1" class="page-title-action pmpro-has-icon pmpro-has-icon-plus"><?php esc_html_e('Add New', 'pmpro-affiliates'); ?> <?php echo esc_html( ucwords( $pmpro_affiliates_singular_name ) ); ?></a>

		<a href="admin.php?page=pmpro-affiliates&report=all" class="page-title-action pmpro-has-icon pmpro-has-icon-admin-users"><?php esc_html_e('View', 'pmpro-affiliates'); ?> <?php echo esc_html( ucwords( $pmpro_affiliates_plural_name ) ); ?> <?php esc_html_e('Report', 'pmpro-affiliates'); ?></a>

		<?php if(!empty($pmpro_msg)) { ?>
			<div id="message" class="<?php if($pmpro_msgt == "success") echo "updated fade"; else echo "error"; ?>"><p><?php echo esc_html( $pmpro_msg ); ?></p></div>
		<?php } ?>

		<?php
			$affiliates = $wpdb->get_results("SELECT * FROM $wpdb->pmpro_affiliates");
			if ( empty( $affiliates ) ) { ?>
				<p><?php echo sprintf( __('Use %s to track orders coming in from different sales campaigns and partners.', 'pmpro-affiliates'), $pmpro_affiliates_plural_name ); ?> <a href="admin.php?page=pmpro-affiliates&edit=-1"><?php echo sprintf( esc_html__( 'Create your first %s now', 'pmpro-affiliates' ), $pmpro_affiliates_singular_name ); ?></a>.</p>
			<?php } else { ?>
				<p class="search-box">
					<label class="screen-reader-text" for="post-search-input"><?php echo sprintf( esc_html__( 'Search %s:', 'pmpro-affiliates' ), ucwords( $pmpro_affiliates_plural_name ) ); ?></label>
					<input type="hidden" name="page" value="pmpro-affiliates" />
					<input id="post-search-input" type="text" value="<?php echo esc_attr( wp_unslash( $s ) ); ?>" name="s" size="30" />
					<input class="button" type="submit" value="Search" id="search-submit "/>
				</p>

				<hr class="wp-header-end">

				<br class="clear" />

				<table class="wp-list-table widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Code', 'pmpro-affiliates' ); ?></th>
						<th><?php esc_html_e( 'Name', 'pmpro-affiliates' ); ?></th>
						<th><?php esc_html_e( 'User', 'pmpro-affiliates' ); ?></th>
						<th><?php esc_html_e( 'Cookie', 'pmpro-affiliates' ); ?></th>
						<th><?php esc_html_e( 'Enabled', 'pmpro-affiliates' ); ?></th>
						<th><?php esc_html_e( 'Visits', 'pmpro-affiliates' ); ?></th>
						<th><?php esc_html_e( 'Commission %', 'pmpro-affiliates' ); ?></th>
						<th><?php esc_html_e( 'Conversion %', 'pmpro-affiliates' ); ?></th>
						<th><?php esc_html_e( 'Commission Earned', 'pmpro-affiliate' ); ?></th>
						<th><?php esc_html_e( 'Revenue Contributed', 'pmpro-affiliates' ); ?></th>
						<?php do_action( "pmpro_affiliate_extra_cols_header" ); ?>
					</tr>
				</thead>
				<tbody>
					<?php
						// Is there a search term?
						if ( ! empty( $s ) ) {
							$affiliates = $wpdb->get_results("SELECT * FROM $wpdb->pmpro_affiliates WHERE code LIKE '%" . esc_sql( $s ) . "%' OR name LIKE '%" . esc_sql( $s ) . "%' OR affiliateuser LIKE '%" . esc_sql( $s ) . "%'");
							if ( empty( $affiliates ) ) {
								echo '<tr><td colspan="100%">' . esc_html__( 'No affiliates found.', 'pmpro-affiliates' ) . '</td></tr>';
							}
						}
						foreach ( $affiliates as $affiliate ) { ?>
						<tr>
							<td class="affiliate_code column-affiliate_code has-row-actions">
								<a href="?page=pmpro-affiliates&report=<?php echo esc_attr( $affiliate->id ); ?>"><?php echo esc_html( $affiliate->code ); ?></a>
								<br />
								<div class="row-actions">
									<span class="id">
										<?php echo sprintf( esc_html__( 'ID: %s', 'pmpro-affiliates' ), $affiliate->id ); ?>
									</span>
									<span class="report">
										<a href="?page=pmpro-affiliates&report=<?php echo esc_attr( $affiliate->id ); ?>"><?php esc_html_e('Report', 'pmpro-affiliates'); ?></a>
									</span> |
									<span class="edit">
										<a href="?page=pmpro-affiliates&edit=<?php echo esc_attr( $affiliate->id ); ?>"><?php esc_html_e('Edit', 'pmpro-affiliates'); ?></a>
									</span> |
									<span class="copy">
										<a href="?page=pmpro-affiliates&edit=-1&copy=<?php echo esc_attr( $affiliate->id ); ?>"><?php esc_html_e('Copy', 'pmpro-affiliates'); ?></a>
									</span> |
									<span class="link">
										<a target="_blank" href="<?php echo pmpro_url("levels", "?pa=" . $affiliate->code);?>"><?php esc_html_e('Link', 'pmpro-affiliates'); ?></a>
									</span> |
									<span class="delete">
										<a href="javascript:askfirst('<?php echo str_replace("'", "\'", sprintf( esc_html__("Deleting affiliates is permanent and can affect active users. Are you sure you want to delete affiliate %s?", 'pmpro-affiliates'), str_replace("'", "", $affiliate->id)));?>', 'admin.php?page=pmpro-affiliates&delete=<?php echo esc_attr( $affiliate->id );?>'); void(0);"><?php esc_html_e('Delete', 'pmpro-affiliates'); ?></a>
									</span>
								</div>
							</td>

							<?php 
								$user = get_user_by( 'login', $affiliate->affiliateuser );
								if ( $user ) {
									$affiliate_user_name = '<a href="' . esc_url( get_edit_user_link( $user->ID ) ) . '">' . esc_html( stripslashes( $affiliate->affiliateuser ) ) . '</a>';
								} else {
									$affiliate_user_name = esc_html( stripslashes( $affiliate->affiliateuser ) );
								}
							?>

							</td>
							<td><?php echo stripslashes($affiliate->name); ?></td>
							<td><?php echo $affiliate_user_name; ?></td>
							<td><?php echo $affiliate->cookiedays . " days"; ?></td>
							<td><?php echo pmpro_affiliates_yesorno($affiliate->enabled); ?></td>
							<td><?php echo intval($affiliate->visits);?></td>
							<td>
								<?php
								echo $affiliate->commissionrate * 100 . "%";
								?>
							</td>
							<td>
								<?php
									$norders = $wpdb->get_var("SELECT COUNT(" . esc_sql( pmpro_affiliates_get_commission_calculation_source() ) . ") FROM $wpdb->pmpro_membership_orders WHERE affiliate_id = '" . esc_sql($affiliate->id) . "' AND status NOT IN('pending', 'error', 'refunded', 'refund', 'token', 'review')");
									if(empty($affiliate->visits))
										echo "0%";
									else
										echo round($norders / $affiliate->visits * 100, 2) . "%";
								?>
							</td>
							<?php
							// Calculate earnings so we can show commission earned and total earnings.
								$earnings = $wpdb->get_var("SELECT SUM(" . esc_sql( pmpro_affiliates_get_commission_calculation_source() ) . ") FROM $wpdb->pmpro_membership_orders WHERE affiliate_id = '" . esc_sql($affiliate->id) . "' AND status NOT IN('pending', 'error', 'refunded', 'refund', 'token', 'review')");
								
							?>
							
							<td>
								<?php
								echo pmpro_formatPrice( $earnings * $affiliate->commissionrate );
								?>
							</td>
							<td>
								<?php echo pmpro_formatPrice( $earnings ); ?>
							</td>
							<?php do_action( "pmpro_affiliate_extra_cols_body", $affiliate, $earnings ); ?>
						</tr>
					<?php } ?>
				</tbody>
				</table>
			<?php }
	}
	?>
	</form>

	<p><a href="https://www.paidmembershipspro.com/add-ons/pmpro-lightweight-affiliate-tracking/?utm_source=plugin&utm_medium=pmpro-affiliates-admin&utm_campaign=add-ons" target="_blank"><?php esc_html_e('Documentation', 'pmpro-affiliates'); ?></a> | <a href="https://www.paidmembershipspro.com/support/?utm_source=plugin&utm_medium=pmpro-affiliates-admin&utm_campaign=support" target="_blank"><?php esc_html_e('Support', 'pmpro-affiliates'); ?></a></p>
	<?php
	require_once( PMPRO_DIR . "/adminpages/admin_footer.php" );
