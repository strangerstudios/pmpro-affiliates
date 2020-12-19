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
				echo __("for All", "pmpro_affiliates")." " . ucwords($pmpro_affiliates_plural_name);
			else
				echo __("for Code", "pmpro_affiliates")." " . stripslashes($code);
		?>
		<a href="<?php echo admin_url('admin-ajax.php');?>?action=affiliates_report_csv&report=<?php echo $report;?>" class="add-new-h2"><?php _e('Export to CSV', 'pmpro_affiliates'); ?></a>
		<?php 
			if(!empty($affiliate_id))
			{
				?>
				<a href="admin.php?page=pmpro-affiliates&report=all" class="add-new-h2"><?php echo sprintf( __('View All %s Report', 'pmpro_affiliates'), ucwords( $pmpro_affiliates_plural_name ) ); ?></a>
				<?php
			}
		?>
	</h2>
<?php
	if(!empty($name))
		echo "<p>".__("Business/Contact Name:", "pmpro_affiliates")." " . stripslashes($name) . "</p>";
	if(!empty($affiliateuser))
		echo "<p>" . ucwords($pmpro_affiliates_singular_name) . " ".__("User:","pmpro_affiliates")." " . stripslashes($affiliateuser) . "</p>";
?>
	
<table class="widefat">
<thead>
	<tr>				
		<th><?php _e('Code', 'pmpro_affiliates'); ?></th>
		<th><?php _e('Sub-ID', 'pmpro_affiliates'); ?></th>
		<th><?php _e('Name', 'pmpro_affiliates'); ?></th>
		<th><?php _e('Member', 'pmpro_affiliates'); ?></th>
		<th><?php _e('Membership Level', 'pmpro_affiliates'); ?></th>
		<th><?php _e('Date', 'pmpro_affiliates'); ?></th>
		<th><?php _e('Order Total', 'pmpro_affiliates'); ?></th>
	</tr>
</thead>
<tbody>
	<?php
		$count = 0;
		$sqlQuery = "SELECT a.code, o.affiliate_subid as subid, a.name, u.user_login, o.membership_id, UNIX_TIMESTAMP(o.timestamp) as timestamp, o.total FROM $wpdb->pmpro_membership_orders o LEFT JOIN $wpdb->pmpro_affiliates a ON o.affiliate_id = a.id LEFT JOIN $wpdb->users u ON o.user_id = u.ID WHERE o.affiliate_id <> '' ";
		if($report != "all")
			$sqlQuery .= " AND a.id = '" . esc_sql($report) . "' ";
		$affiliate_orders = $wpdb->get_results($sqlQuery);
		if(empty($affiliate_orders))
		{
		?>
			<tr><td colspan="6" class="pmpro_pad20">					
				<p><?php echo sprintf( __('No %s signups have been tracked yet.', 'pmpro_affiliates'), $pmpro_affiliates_singular_name ); ?></p>
			</td></tr>
		<?php
		}
		else
		{
			global $pmpro_currency_symbol;
			foreach($affiliate_orders as $order)
			{
				$level = pmpro_getLevel( $order->membership_id );
			?>
			<tr<?php if($count++ % 2 == 1) { ?> class="alternate"<?php } ?>>
				<td><?php echo $order->code;?></td>
				<td><?php echo $order->subid;?></td>
				<td><?php echo stripslashes($order->name);?></td>
				<td><?php echo $order->user_login;?></td>
				<td><?php echo $level->name; ?></td>
				<td><?php echo date(get_option("date_format"), $order->timestamp);?></td>
				<td><?php echo pmpro_formatPrice( $order->total ); ?></td>
			</tr>
			<?php
			}
		}
	?>
</tbody>
</table>