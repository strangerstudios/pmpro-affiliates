<?php	
	$pmpro_affiliates_settings = pmpro_affiliates_get_settings();
	$pmpro_affiliates_singular_name = $pmpro_affiliates_settings['pmpro_affiliates_singular_name'];
	$pmpro_affiliates_plural_name = $pmpro_affiliates_settings['pmpro_affiliates_plural_name'];

	if(isset($_REQUEST['report']))	
		$report = $_REQUEST['report'];
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
				echo __("for All", 'pmpro-affiliates' )." " . ucwords($pmpro_affiliates_plural_name);
			else
				echo __("for Code", 'pmpro-affiliates' )." " . stripslashes($code);
		?>
		<a href="<?php echo admin_url('admin-ajax.php');?>?action=affiliates_report_csv&report=<?php echo $report;?>" class="add-new-h2"><?php _e('Export to CSV', 'pmpro-affiliates' ); ?></a>
		<?php 
			if(!empty($affiliate_id))
			{
				?>
				<a href="admin.php?page=pmpro-affiliates&report=all" class="add-new-h2"><?php echo sprintf( __('View All %s Report', 'pmpro-affiliates' ), ucwords( $pmpro_affiliates_plural_name ) ); ?></a>
				<?php
			}
		?>
	</h2>
<?php
	$affiliate_user_object = get_user_by( 'login', stripslashes( $affiliateuser ) );
	if ( ! empty( $affiliate_user_object ) ) {
		$affiliate_user_shown = '<a href="' . get_edit_user_link( $affiliate_user_object->ID ) . '">' . $affiliate_user_object->display_name . '</a>';
	} else {
		$affiliate_user_shown = stripslashes( $affiliateuser );
	}
	if(!empty($name))
		echo "<p>".__("Business/Contact Name:", 'pmpro-affiliates' )." " . stripslashes($name) . "</p>";
	if(!empty($affiliateuser))
		echo "<p>" . ucwords($pmpro_affiliates_singular_name) . " ".__("User:", 'pmpro-affiliates' )." " . $affiliate_user_shown . "</p>";
?>

<?php
	$sqlQuery = 
	"SELECT o.id as order_id, a.code, a.commissionrate, o.affiliate_subid as subid, a.name, u.user_login, o.membership_id, UNIX_TIMESTAMP(o.timestamp) as timestamp, o.total, o.status, om.meta_value as affiliate_paid
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
		<p><?php echo sprintf( __('No %s signups have been tracked yet.', 'pmpro-affiliates' ), $pmpro_affiliates_singular_name ); ?></p>
	<?php } else { ?>
		<table class="widefat striped fixed">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Code', 'pmpro-affiliates' ); ?></th>
					<th><?php esc_html_e( 'Sub-ID', 'pmpro-affiliates' ); ?></th>
					<th><?php esc_html_e( 'Name', 'pmpro-affiliates' ); ?></th>
					<th><?php esc_html_e( 'Member', 'pmpro-affiliates' ); ?></th>
					<th><?php esc_html_e( 'Membership Level', 'pmpro-affiliates' ); ?></th>
					<th><?php esc_html_e( 'Date', 'pmpro-affiliates' ); ?></th>
					<th><?php esc_html_e( 'Comission %', 'pmpro-affiliates' ); ?></th>
					<th><?php esc_html_e( 'Commission Earned', 'pmpro-affiliates' ); ?></th>
					<th><?php esc_html_e( 'Order Total', 'pmpro-affiliates' ); ?></th>
					<th><?php esc_html_e( 'Status', 'pmpro-affiliates' ); ?></th>
					<?php do_action( "pmpro_affiliate_report_extra_cols_header" ); ?>
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
							$affiliate_paid = esc_html__( 'Paid', 'pmpro-affiliates' );
						} else {
							$nonce = wp_create_nonce( 'pmpro_affiliates_mark_as_paid' );
							$affiliate_paid = '<a id="pmpro_affiliates_mark_as_paid" href="javascript:void(0)" order_id="' . esc_attr( $order->order_id ) . '" _wpnonce="' . esc_attr( $nonce ) . '" >' . esc_html__( 'Mark as Paid', 'pmpro-affiliates' ) . '</a>';
						}
						
						?>
						<tr>
							<td><?php echo $order->code;?></td>
							<td><?php echo $order->subid;?></td>
							<td><?php echo stripslashes($order->name);?></td>
							<td><?php echo $order->user_login;?></td>
							<td><?php echo $level->name; ?></td>
							<td><?php echo date_i18n( get_option( 'date_format' ), $order->timestamp );?></td>
							<td><?php echo $order->commissionrate * 100;?>%</td>
							<td><?php echo pmpro_formatPrice( $order->total * $order->commissionrate ); ?></td>
							<td><?php echo pmpro_formatPrice( $order->total ); ?></td>
							<td><?php echo '<span class="pmpro_affiliate_paid_status">' . $affiliate_paid . '</span>'; ?></td>
							<?php do_action( "pmpro_affiliate_report_extra_cols_body", $order ); ?>
						</tr>
						<?php
					}
				?>
			</tbody>
		</table>
		<?php
	}
?>
