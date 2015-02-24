<?php	
	/*
		Preheader
	*/
	function pmpro_affiliates_report_preheader()
	{
		if(!is_admin())
		{
			global $post, $current_user;
			if(!empty($post->post_content) && strpos($post->post_content, "[pmpro_affiliates_report]") !== false)
			{
				/*
					Preheader operations here.
				*/
				//get affiliates
				global $pmpro_affiliates;
				$pmpro_affiliates = pmpro_affiliates_getAffiliatesForUser();		
								
				//no affiliates, get out of here
				if(empty($pmpro_affiliates))
				{
					wp_redirect(pmpro_url("account"));
					exit;
				}										
			}
		}
	}
	add_action("wp", "pmpro_affiliates_report_preheader", 1);	
	
	/*
		Shortcode Wrapper
	*/
	function pmpro_affiliates_report_shortcode($atts, $content=null, $code="")
	{			
		global $pmpro_affiliates, $post, $wpdb, $current_user;
		ob_start();
		/*
			Page Template HTML/ETC
		*/
		
		if(!empty($_REQUEST['report']))
			$report = intval($_REQUEST['report']);
		else
			$report = NULL;
			
		if($report)
		{
			//show report
			$affiliate = $wpdb->get_row("SELECT * FROM $wpdb->pmpro_affiliates WHERE id = '" . $report . "' LIMIT 1");
			
			//no affiliate found?
			if(empty($affiliate))
			{
				wp_redirect(pmpro_url("account"));
				exit;
			}
			
			//make sure admin or affiliate user
			if(!current_user_can("manage_options") && $current_user->user_login != $affiliate->affiliateuser)
			{
				wp_redirect(pmpro_url("account"));
				exit;
			}
			
			?>
			<div class="alignright"><small><a href="<?php echo admin_url('admin-ajax.php');?>?action=affiliates_report_csv&report=<?php echo $affiliate->id;?>" class="button add-new-h2">Export CSV</a></small></div>
			<div class="pmpro_clear"></div>
			<h3>Viewing report for code: <?php echo $affiliate->code;?></h3>
			<table class="pmpro_affiliate_report" width="100%" cellpadding="0" cellspacing="0">
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
			<p><a href="<?php echo get_permalink($affiliate_report_post_id); ?>">View All Affiliate Codes</a></p>
			<?php
		}
		else
		{		
			//show affiliates		
			?>
			<h3>Your Affiliate Codes</h3>
			<p>Select an affiliate code to view the report.</p>			
			<ul>
				<?php foreach($pmpro_affiliates as $affiliate) { ?>
					<li><a href="<?php echo get_permalink($post->ID);?>?report=<?php echo $affiliate->id;?>"><?php echo $affiliate->code;?></a></li>
				<?php } ?>
			</ul>
			<?php
		}
		
		$temp_content = ob_get_contents();
		ob_end_clean();
		return $temp_content;			
	}
	add_shortcode("pmpro_affiliates_report", "pmpro_affiliates_report_shortcode");
	
	/*
		Add to Member Links of My Account Page
		
		Note: We actually don't know where the Affiliate Report page is.
		So we need to add a setting to set it and/or automatically generate the affiliate report page.
		Then set $affiliate_report_post_id global.
	*/
	function pmpro_affiliates_report_member_link()
	{
		global $affiliate_report_post_id;		
		if(!empty($affiliate_report_post_id) && pmpro_affiliates_getAffiliatesForUser())
		{
		?>
		<li><a href="<?php echo get_permalink($affiliate_report_post_id);?>">Affiliate Reports</a></li>
		<?php
		}	
	}
	add_action('pmpro_member_links_bottom', 'pmpro_affiliates_report_member_link');
