<?php	
	$pmpro_affiliates_settings = pmpro_affiliates_get_settings();
	$pmpro_affiliates_singular_name = $pmpro_affiliates_settings['pmpro_affiliates_singular_name'];
	$pmpro_affiliates_plural_name = $pmpro_affiliates_settings['pmpro_affiliates_plural_name'];

	if(isset($_REQUEST['report']))	
		$report = sanitize_text_field( $_REQUEST['report'] );
	else
		$report = false;
	
	if($report && $report != "all")
	{
		//get values from DB
		$affiliate_id = $report;		
		$affiliate = $wpdb->get_row("SELECT * FROM $wpdb->pmpro_affiliates WHERE id = '" . intval($affiliate_id) . "' LIMIT 1");
		if(!empty($affiliate) && !empty($affiliate->id))
		{
			$code = $affiliate->code;
			$name = $affiliate->name;
			$affiliateuser = $affiliate->affiliateuser;
			$trackingcode = $affiliate->trackingcode;
			$cookiedays = $affiliate->cookiedays;
			$enabled = $affiliate->enabled;
		}
	}	
?>
	<h2>
		<?php echo ucwords($pmpro_affiliates_singular_name); ?> Report
		<?php 
			if(empty($affiliate_id))
				echo sprintf( esc_html__("for All %s", 'pmpro-affiliates' ), ucwords( $pmpro_affiliates_plural_name ) );
			else
				echo sprintf( esc_html__("for Code %s", 'pmpro-affiliates' ), stripslashes( $code ) );
		?>
		<a href="<?php echo admin_url('admin-ajax.php');?>?action=affiliates_report_csv&report=<?php echo esc_attr( $report );?>" class="add-new-h2"><?php esc_html_e('Export to CSV', 'pmpro-affiliates' ); ?></a>
		<?php 
			if(!empty($affiliate_id))
			{
				?>
				<a href="admin.php?page=pmpro-affiliates&report=all" class="add-new-h2"><?php echo sprintf( esc_html__('View All %s Report', 'pmpro-affiliates' ), ucwords( $pmpro_affiliates_plural_name ) ); ?></a>
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
		echo "<p>". sprintf( esc_html__("Business/Contact Name: %s", 'pmpro-affiliates' ), stripslashes($name) ) . "</p>";
	}

	if ( ! empty( $affiliateuser ) ) {
		// The $affiliate_user_shown is escaped before echoing it out.
		echo "<p>" . esc_html( ucwords($pmpro_affiliates_singular_name) ) . " ". esc_html__("User:", 'pmpro-affiliates' )." " . $affiliate_user_shown . "</p>";
	}
?>

<?php
	$sqlQuery = 
	"SELECT o.id as order_id, o.code as order_code, a.id as affiliate_id, a.code, a.commissionrate, o.affiliate_subid as subid, a.name, u.ID as user_id, u.user_login, o.membership_id, UNIX_TIMESTAMP(o.timestamp) as timestamp, o.total, o.status, om.meta_value as affiliate_paid
	FROM $wpdb->pmpro_membership_orders o 
	LEFT JOIN $wpdb->pmpro_affiliates a 
	ON o.affiliate_id = a.id 
	LEFT JOIN $wpdb->users u 
	ON o.user_id = u.ID
	LEFT JOIN $wpdb->pmpro_membership_ordermeta om
	ON o.id = om.pmpro_membership_order_id
	AND om.meta_key = 'pmpro_affiliate_paid'
	WHERE o.affiliate_id <> ''
	AND o.status NOT IN('pending', 'error', 'refunded', 'refund', 'token', 'review')";

	if ( $report != "all" ) {
		$sqlQuery .= " AND a.id = '" . esc_sql($report) . "' ";
	}
	$affiliate_orders = $wpdb->get_results($sqlQuery);

	// Show a message of there are no affiliate orders.
	if ( empty( $affiliate_orders ) ) { ?>
		<p><?php echo sprintf( esc_html__('No %s signups have been tracked yet.', 'pmpro-affiliates' ), $pmpro_affiliates_singular_name ); ?></p>
	<?php } else { ?>
		<table class="widefat striped fixed">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Code', 'pmpro-affiliates' ); ?></th>
					<th><?php esc_html_e( 'Order', 'pmpro-affiliates' ); ?></th>
					<th><?php esc_html_e( 'Name', 'pmpro-affiliates' ); ?></th>
					<th><?php esc_html_e( 'Member', 'pmpro-affiliates' ); ?></th>
					<th><?php esc_html_e( 'Membership Level', 'pmpro-affiliates' ); ?></th>
					<th><?php esc_html_e( 'Date', 'pmpro-affiliates' ); ?></th>
					<th><?php esc_html_e( 'Comission %', 'pmpro-affiliates' ); ?></th>
					<th><?php esc_html_e( 'Commission Earned', 'pmpro-affiliates' ); ?></th>
					<th><?php esc_html_e( 'Order Total', 'pmpro-affiliates' ); ?></th>
					<th><?php esc_html_e( 'Status', 'pmpro-affiliates' ); ?></th>
					<?php
						/**
						 * Action to add additional columns to the affiliates report table.
						 */
						do_action( "pmpro_affiliate_report_extra_cols_header" );
					?>
				</tr>
			</thead>
			<tbody>
				<?php
					global $pmpro_currency_symbol;
					foreach ( $affiliate_orders as $order ) {
						$level = pmpro_getLevel( $order->membership_id ); 
						// Get the affiliate paid status and generate a mark as paid link if not paid. Add nonce.
						$affiliate_paid = $order->affiliate_paid;
						if ( $affiliate_paid == '1' ) {
							$nonce = wp_create_nonce( 'pmpro_affiliates_reset_paid_status' );
							$affiliate_paid = esc_html__( 'Paid', 'pmpro-affiliates' );
							$affiliate_paid .= ' [<a class="pmpro_affiliates_reset_paid_status" href="javascript:void(0)" order_id="' . esc_attr( $order->order_id ) . '" _wpnonce="' . esc_attr( $nonce ) . '" title="' . esc_html__( 'Reset Payment Status', 'pmpro-affiliates' ) . '" >x</a>]'  ;
						} else {
							$nonce = wp_create_nonce( 'pmpro_affiliates_mark_as_paid' );
							$affiliate_paid = '<a class="pmpro_affiliates_mark_as_paid" href="javascript:void(0)" order_id="' . esc_attr( $order->order_id ) . '" _wpnonce="' . esc_attr( $nonce ) . '" title="' . esc_html__( 'Mark as Paid', 'pmpro-affiliates' ) . '" >' . esc_html__( 'Mark as Paid', 'pmpro-affiliates' ) . '</a>';
						}
						?>
						<tr>
							<td><?php echo "<a href='" . esc_url( get_admin_url(NULL, '/admin.php?page=pmpro-affiliates&edit=' . (int) $order->affiliate_id  ) ) . "'>" . esc_html( $order->code ) . "</a>";
							if ( $order->subid ) {
								echo '<br><span class="pmpro-affiliates-sub-id-report" style="font-size:12px;">';
								echo  '<strong>' . esc_html__( 'Sub-ID', 'pmpro-affiliates' ) . ':</strong> ' . esc_html( $order->subid );
								echo '</span>';
							}
							?>
						</td>
							<td><?php echo "<a href='" . esc_url( get_admin_url(NULL, '/admin.php?page=pmpro-orders&order=' . (int) $order->order_id ) ) . "'>" . esc_html( $order->order_code ) . "</a>"; ?></td>
							<td><?php echo ! empty( $order->name ) ? stripslashes($order->name) : '';?></td>
							<td>
								<?php
									if ( ! empty( $order->user_id ) ) {
										if ( ! empty( get_user_by( 'id', $order->user_id ) ) ) { ?>
											<a href="<?php echo esc_url( get_edit_user_link( $order->user_id ) ); ?>"><?php echo esc_html( $order->user_login ); ?></a>
											<?php
										} else {
											echo esc_html( $order->user_login );
										}
									} else { ?>
										[<?php esc_html_e( 'deleted', 'pmpro-affiliates' ); ?>]<?php
									}
								?>
							</td>
							<td>
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
							<td><?php echo date_i18n( get_option( 'date_format' ), $order->timestamp );?></td>
							<td><?php echo esc_html( $order->commissionrate * 100 );?>%</td>
							<td><?php echo pmpro_formatPrice( $order->total * $order->commissionrate ); ?></td>
							<td><?php echo pmpro_formatPrice( $order->total ); ?></td>
							<td><?php echo '<span class="pmpro_affiliate_paid_status" id="order_' . esc_attr( $order->order_id ) . '">' . $affiliate_paid . '</span>'; // We escape the $affiliate_paid before outputting further up.?></td>
							<?php
								/**
								 * Action to populate additional columns in the affiliates report table.
								 *
								 * @param object $order The order object.
								 */
								do_action( "pmpro_affiliate_report_extra_cols_body", $order );
							?>
						</tr>
						<?php
					}
				?>
			</tbody>
		</table>
		<?php
	}
?>
