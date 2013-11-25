<?php
	//vars
	global $wpdb, $pmpro_currency_symbol;
	
	if(isset($_REQUEST['edit']))	
		$edit = $_REQUEST['edit'];
	else
		$edit = false;
		
	if(isset($_REQUEST['report']))	
		$report = $_REQUEST['report'];
	else
		$report = false;
		
	if(isset($_REQUEST['copy']))	
		$copy = $_REQUEST['copy'];
	else
		$copy = false;
	
	if(isset($_REQUEST['delete']))
		$delete = $_REQUEST['delete'];
	else
		$delete = false;			
	
	if(!empty($_REQUEST['save']))
		$save = true;
	else
		$save = false;
	
	//get form values
	if(!empty($save))
	{
		if(isset($_REQUEST['code']))
			$code = preg_replace("[^a-zA-Z0-9]", "", $_REQUEST['code']);
		if(isset($_REQUEST['name']))
			$name = $_REQUEST['name'];
		if(isset($_REQUEST['affiliateuser']))
			$affiliateuser = $_REQUEST['affiliateuser'];
		if(isset($_REQUEST['trackingcode']))
			$trackingcode = $_REQUEST['trackingcode'];
		if(isset($_REQUEST['cookiedays']))
			$cookiedays = preg_replace("[^0-9]", "", $_REQUEST['cookiedays']);
		if(isset($_REQUEST['enabled']))
			$enabled = $_REQUEST['enabled'];
	}
	elseif($edit > 0 || ($report && $report != "all") || $copy)
	{
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
		}
	}	
	else
	{
		//defaults
		$code = pmpro_affiliates_getNewCode();
		$name = "";
		$affiliateuser = "";
		$trackingcode = "";
		$cookiedays = "30";
		$enabled = true;
	}
			
	if($edit && $save)
	{		
		//updating or new?
		if($edit > 0)
		{
			$sqlQuery = "UPDATE $wpdb->pmpro_affiliates SET code = '" . $wpdb->escape($code) . "', name = '" . $wpdb->escape($name) . "', affiliateuser = '" . $wpdb->escape($affiliateuser) . "', trackingcode = '" . $wpdb->escape($trackingcode) . "', cookiedays = '" . $wpdb->escape($cookiedays) . "', enabled = '" . $wpdb->escape($enabled) . "' WHERE id = '" . $edit . "' LIMIT 1";
			if($wpdb->query($sqlQuery) !== false)
			{
				//all good
				$edit = false;
				$pmpro_msg = "Affiliate saved successfully.";
				$pmpro_msgt = "success";		
			}
			else
			{
				//error				
				$pmpro_msg = "There was an error saving the affiliate.";
				$pmpro_msgt = "error";		
			}
		}
		else
		{
			$sqlQuery = "INSERT INTO $wpdb->pmpro_affiliates (id, code, name, affiliateuser, trackingcode, cookiedays, enabled) VALUES('', '" . $wpdb->escape($code) . "', '" . $wpdb->escape($name) . "', '" . $wpdb->escape($affiliateuser) . "', '" . $wpdb->escape($trackingcode) . "', '" . $wpdb->escape($cookiedays) . "', '" . $wpdb->escape($enabled) . "')";
			if($wpdb->query($sqlQuery) !== false)
			{
				//all good
				$edit = false;
				$pmpro_msg = "Affiliate added successfully.";
				$pmpro_msgt = "success";		
			}
			else
			{
				//error				
				$pmpro_msg = "There was an error adding the affiliate.";
				$pmpro_msgt = "error";		
			}
		}
		
	}
	
	//are we deleting?
	if(!empty($delete))
	{
		
	}
?>
<div class="wrap pmpro_admin">	
	<div class="pmpro_banner">		
		<a class="pmpro_logo" title="Paid Memberships Pro - Membership Plugin for WordPress" target="_blank" href="<?php echo pmpro_https_filter("http://www.paidmembershipspro.com")?>"><img src="<?php echo PMPRO_URL?>/images/PaidMembershipsPro.png" width="350" height="45" border="0" alt="Paid Memberships Pro(c) - All Rights Reserved" /></a>
		<div class="pmpro_tagline">Membership Plugin for WordPress</div>
		
		<div class="pmpro_meta"><a href="<?php echo pmpro_https_filter("http://www.paidmembershipspro.com")?>">Plugin Support</a> | <a href="http://www.paidmembershipspro.com/forums/">User Forum</a> | <strong>Version <?php echo PMPRO_VERSION?></strong></div>
	</div>
	<br style="clear:both;" />
	
	<?php
		//include(pmpro_https_filter("http://www.paidmembershipspro.com/notifications/?v=" . PMPRO_VERSION));
	?>
	<div id="pmpro_notifications">
	</div>
	<script>
		jQuery.get('<?php echo pmpro_https_filter("http://www.paidmembershipspro.com/notifications/?v=" . PMPRO_VERSION)?>', function(data) {
			if(data && data != 'NULL')
				jQuery('#pmpro_notifications').html(data);		 
		});
	</script>
	
	<?php if($edit) { ?>
		
		<h2>
			<?php
				if($edit > 0)
					echo "Edit Affiliate";
				else
					echo "Add New Affiliate";
			?>
			<a href="admin.php?page=pmpro-affiliates" class="button add-new-h2">Back to Affiliates &raquo;</a>
		</h2>
		
		<?php if(!empty($pmpro_msg)) { ?>
			<div id="message" class="<?php if($pmpro_msgt == "success") echo "updated fade"; else echo "error"; ?>"><p><?php echo $pmpro_msg?></p></div>
		<?php } ?>
		
		<div>
			
			<form action="" method="post">
				<input name="saveid" type="hidden" value="<?php echo $edit?>" />
				<table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row" valign="top"><label>ID:</label></th>
                        <td class="pmpro_lite"><?php if(!empty($affiliate->id)) echo $affiliate->id; else echo "This will be generated when you save.";?></td>
                    </tr>								                
                    
                    <tr>
                        <th scope="row" valign="top"><label for="code">Code:</label></th>
                        <td>
							<input id="code" name="code" type="text" size="20" value="<?php if(!empty($code)) echo esc_attr($code);?>" />
							<small>Value added to the site URL to designate an affiliate link. (e.g. "&pa=CODE" or "?pa=CODE")</small>
						</td>
                    </tr>

					<tr>
                        <th scope="row" valign="top"><label for="name">Business/Contact Name:</label></th>
                        <td>
							<input id="name" name="name" type="text" size="40" value="<?php if(!empty($name)) echo esc_attr(stripslashes($name));?>" />
						</td>
                    </tr>

					<tr>
                        <th scope="row" valign="top"><label for="affiliateuser">Affiliate User:</label></th>
                        <td>
							<input id="affiliateuser" name="affiliateuser" type="text" size="20" value="<?php if(!empty($affiliateuser)) echo esc_attr($affiliateuser);?>" />
							<small>The username of a WordPress user in your site who should have access to affiliate reports.</small>
						</td>
                    </tr>
					
					<tr>
                        <th scope="row" valign="top"><label for="trackingcode">Tracking Code:</label></th>
                        <td>
							<textarea id="trackingcode" name="trackingcode" rows="6" cols="60"><?php if(!empty($trackingcode)) echo esc_textarea(stripslashes($trackingcode));?></textarea>
							<br /><small>This code is run on the confirmation page after checkout. Variables: !!ORDER_ID!!, !!LEVEL_NAME!!</small>
						</td>
                    </tr>
					
					<tr>
                        <th scope="row" valign="top"><label for="cookiedays">Cookie Length:</label></th>
                        <td>
							<input name="cookiedays" type="text" size="5" value="<?php if(!empty($cookiedays)) echo esc_attr($cookiedays);?>" />
							<small>In days.</small>
						</td>
                    </tr>
					
					<tr>
                        <th scope="row" valign="top"><label for="enabled">Enabled:</label></th>
                        <td id="enabled">
							<input type="radio" name="enabled" value="1" <?php if(!empty($enabled)) { ?>checked="checked"<?php } ?>>Yes
							&nbsp;
							<input type="radio" name="enabled" value="0" <?php if(empty($enabled)) { ?>checked="checked"<?php } ?>>No
						</td>
                    </tr>
                    					
				</tbody>
			</table>
			
			<?php do_action("pmpro_affiliate_after_settings"); ?>
							
			<p class="submit topborder">				
				<input name="edit" type="hidden" value="<?php if(!empty($edit)) echo $edit?>" />
				<input name="save" type="hidden" value="1" />
				<input type="submit" class="button-primary" value="Save Code" /> 					
				<input name="cancel" type="button" value="Cancel" onclick="location.href='<?php echo get_admin_url(NULL, '/admin.php?page=pmpro-affiliates')?>';" />
			</p>
			</form>
		</div>
	
	<?php } elseif($report) { ?>	
			
		<?php if(!empty($name)) { ?>
			<h2>
				Affiliate Report: <?php echo stripslashes($name) . " (" . stripslashes($code) . ")"; ?>
		<?php } else { ?>
			<h2>
				Affiliate Report: All Affiliates								
		<?php } ?>
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
					$sqlQuery .= " AND a.id = '" . $wpdb->escape($report) . "' ";
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
	
	<?php } else { ?>	
	
		<h2>
			Affiliates
			<a href="admin.php?page=pmpro-affiliates&edit=-1" class="button add-new-h2">Add New Affiliate</a>
			<a href="admin.php?page=pmpro-affiliates&report=all" class="button add-new-h2">View All Affiliate Orders</a>
		</h2>		
		
		<?php if(!empty($pmpro_msg)) { ?>
			<div id="message" class="<?php if($pmpro_msgt == "success") echo "updated fade"; else echo "error"; ?>"><p><?php echo $pmpro_msg?></p></div>
		<?php } ?>
		
		<form id="posts-filter" method="get" action="">			
			<p class="search-box">
				<label class="screen-reader-text" for="post-search-input">Search Affiliates:</label>
				<input type="hidden" name="page" value="pmpro-affiliates" />
				<input id="post-search-input" type="text" value="<?php if(!empty($s)) echo $s;?>" name="s" size="30" />
				<input class="button" type="submit" value="Search" id="search-submit "/>
			</p>		
		</form>	
		
		<br class="clear" />
		
		<table class="widefat">
		<thead>
			<tr>
				<th>ID</th>
				<th>Code</th>				
				<th>Name</th>		
				<th>Cookie Length</th>						
				<th>Enabled</th>				
				<th></th>
			</tr>
		</thead>
		<tbody>
			<?php				
				$affiliates = $wpdb->get_results("SELECT * FROM $wpdb->pmpro_affiliates");
				if(empty($affiliates))
				{
				?>
					<tr><td colspan="6" class="pmpro_pad20">					
						<p>Use affiliates to track orders coming in from different sales campaigns and partners. <a href="admin.php?page=pmpro-affiliates&edit=-1">Create your first affiliate now</a>.</p>
					</td></tr>
				<?php
				}
				else
				{	
					foreach($affiliates as $affiliate)
					{
					?>
					<tr>
						<td><?php echo $affiliate->id?></td>
						<td>
							<a href="?page=pmpro-affiliates&report=<?php echo $affiliate->id?>"><?php echo $affiliate->code?></a>
						</td>
						<td><?php echo stripslashes($affiliate->name)?></td>
						<td><?php echo $affiliate->cookiedays?></td>
						<td><?php echo pmpro_affiliates_yesorno($affiliate->enabled)?></td>
						<td>
							<a href="?page=pmpro-affiliates&report=<?php echo $affiliate->id?>">Report</a> &nbsp;
							<a href="?page=pmpro-affiliates&edit=<?php echo $affiliate->id?>">Edit</a> &nbsp;
							<a href="?page=pmpro-affiliates&edit=-1&copy=<?php echo $affiliate->id?>">Copy</a>													
						</td>										
					</tr>
					<?php
					}
				}
				?>
		</tbody>
		</table>
		
	<?php } ?>
	
</div>