<?php	
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
<?php if(!empty($name)) { ?>
	<h2>
		Affiliate Report: <?php echo stripslashes($name) . " (" . stripslashes($code) . ")"; ?>
<?php } else { ?>
	<h2>
		Affiliate Report: All Affiliates								
<?php } ?>
	<a href="<?php echo admin_url('admin-ajax.php');?>?action=affiliates_report_csv&report=<?php echo $report;?>" class="button add-new-h2">Export CSV</a> &nbsp;
	<a href="admin.php?page=pmpro-affiliates" class="button add-new-h2">Back to Affiliates &raquo;</a>
</h2>

<table class="widefat">
<thead>
	<tr>				
		<th>Code</th>
		<th>Sub-ID</th>				
		<th>Name</th>		
		<th>Member</th>						
		<th>Date</th>				
		<th>Order Total</th>
	</tr>
</thead>
<tbody>
	<?php
		$sqlQuery = "SELECT a.code, o.affiliate_subid as subid, a.name, u.user_login, UNIX_TIMESTAMP(o.timestamp) as timestamp, o.total FROM $wpdb->pmpro_membership_orders o LEFT JOIN $wpdb->pmpro_affiliates a ON o.affiliate_id = a.id LEFT JOIN $wpdb->users u ON o.user_id = u.ID WHERE o.affiliate_id <> '' ";
		if($report != "all")
			$sqlQuery .= " AND a.id = '" . esc_sql($report) . "' ";
		$affiliate_orders = $wpdb->get_results($sqlQuery);
		if(empty($affiliate_orders))
		{
		?>
			<tr><td colspan="6" class="pmpro_pad20">					
				<p>No affiliate signups have been tracked yet.</p>
			</td></tr>
		<?php
		}
		else
		{
			global $pmpro_currency_symbol;
			foreach($affiliate_orders as $order)
			{
			?>
			<tr>
				<td><?php echo $order->code;?></td>
				<td><?php echo $order->subid;?></td>
				<td><?php echo stripslashes($order->name);?></td>
				<td><?php echo $order->user_login;?></td>
				<td><?php echo date(get_option("date_format"), $order->timestamp);?></td>
				<td><?php echo $pmpro_currency_symbol . $order->total;?></td>
			</tr>
			<?php
			}
		}
	?>
</tbody>
</table>