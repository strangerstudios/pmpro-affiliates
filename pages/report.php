<?php	
	/*
		Preheader
	*/
	function pmpro_affiliates_report_preheader()
	{
		if(!is_admin())
		{
			global $post, $current_user;
			if((!empty($post->post_content) && strpos($post->post_content, "[pmpro_affiliates_report]") !== false)
				|| (!empty($post->post_content_filtered) && strpos($post->post_content_filtered, "[pmpro_affiliates_report]") !== false))
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
		global $post, $wpdb, $current_user;

		$pmpro_affiliates = pmpro_affiliates_getAffiliatesForUser();
		$pmpro_affiliates_settings = pmpro_affiliates_get_settings();

		extract(shortcode_atts(array(
			'back_link' => '1',
			'export' => '1',
			'fields' => 'code,subid,name,user_login,date,membership_level,total',
			'help' => '1'
		), $atts));
		
		if($back_link === "0" || $back_link === "false" || $back_link === "no")
			$back_link = false;
		else
			$back_link = true;
		
		$fields = explode(",", $fields);
		
		if($export === "0" || $export === "false" || $export === "no")
			$export = false;
		else
			$export = true;
		
		if($help === "0" || $help === "false" || $help === "no")
			$help = false;
		else
			$help = true;
	
		ob_start();
		/*
			Page Template HTML/ETC
		*/

		$pmpro_affiliates_singular_name = $pmpro_affiliates_settings['pmpro_affiliates_singular_name'];
		$pmpro_affiliates_plural_name = $pmpro_affiliates_settings['pmpro_affiliates_plural_name'];
	
		if(!empty($_REQUEST['report']))
			$report = intval($_REQUEST['report']);
		else
			$report = NULL;

		if ( count($pmpro_affiliates) == 1 ) {
			$report = $pmpro_affiliates[0]->id;
		}

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
			<?php if(!empty($export)) { ?>
				<span class="pmpro_a-right"><a href="<?php echo admin_url('admin-ajax.php');?>?action=affiliates_report_csv&report=<?php echo $affiliate->id;?>"><?php _e('Export CSV', 'pmpro_affiliates'); ?></a></span>
			<?php } ?>
			<h2><?php echo ucwords($pmpro_affiliates_singular_name); ?> <?php echo __('Report for Code:', 'pmpro_affiliates') .' '. $affiliate->code; ?></h2>
			<?php
				$sqlQuery = "SELECT a.code, o.affiliate_subid as subid, a.name, u.user_login, UNIX_TIMESTAMP(o.timestamp) as timestamp, o.total, o.membership_id FROM $wpdb->pmpro_membership_orders o LEFT JOIN $wpdb->pmpro_affiliates a ON o.affiliate_id = a.id LEFT JOIN $wpdb->users u ON o.user_id = u.ID WHERE o.affiliate_id <> '' ";
				if($report != "all")
					$sqlQuery .= " AND a.id = '" . esc_sql($report) . "' ";
				$affiliate_orders = $wpdb->get_results($sqlQuery);
				if(!empty($affiliate_orders))
				{
					?>
					<table class="pmpro_affiliate_report" width="100%" cellpadding="0" cellspacing="0">
					<thead>
						<tr>
							<?php if(in_array('code', $fields)) { ?>
								<th><?php _e('Code', 'pmpro_affiliates'); ?></th>
							<?php } ?>					
							<?php if(in_array('subid', $fields)) { ?>
								<th><?php _e('Sub-ID', 'pmpro_affiliates'); ?></th>				
							<?php } ?>					
							<?php if(in_array('name', $fields)) { ?>
								<th><?php _e('Name', 'pmpro_affiliates'); ?></th>		
							<?php } ?>					
							<?php if(in_array('user_login', $fields)) { ?>
								<th><?php _e('Member', 'pmpro_affiliates'); ?></th>						
							<?php } ?>					
							<?php if(in_array('date', $fields)) { ?>
								<th><?php _e('Date', 'pmpro_affiliates'); ?></th>				
							<?php } ?>					
							<?php if(in_array('membership_level', $fields)) { ?>
								<th><?php _e('Membership Level', 'pmpro_affiliates'); ?></th>
							<?php } ?>
							<?php if(in_array('total', $fields)) { ?>
								<th><?php _e('Order Total', 'pmpro_affiliates'); ?></th>
							<?php } ?>					
						</tr>
					</thead>
					<tbody>
					<?php
						global $pmpro_currency_symbol;
						foreach($affiliate_orders as $order)
						{
							$level = pmpro_getLevel($order->membership_id);
							?>
							<tr>
								<?php if(in_array('code', $fields)) { ?>
									<td><?php echo $order->code;?></td>
								<?php } ?>					
								<?php if(in_array('subid', $fields)) { ?>
									<td><?php echo $order->subid;?></td>
								<?php } ?>
								<?php if(in_array('name', $fields)) { ?>
									<td><?php echo stripslashes($order->name);?></td>
								<?php } ?>
								<?php if(in_array('user_login', $fields)) { ?>
									<td><?php echo $order->user_login;?></td>
								<?php } ?>
								<?php if(in_array('date', $fields)) { ?>
									<td><?php echo date_i18n(get_option("date_format"), $order->timestamp);?></td>
								<?php } ?>
								<?php if(in_array('membership_level', $fields)) { ?>
									<td><?php echo $level->name; ?></td>
								<?php } ?>
								<?php if(in_array('total', $fields)) { ?>
									<td><?php echo pmpro_formatPrice($order->total);?></td>
								<?php } ?>
							</tr>
							<?php
						}
					?>
					</tbody>
					</table>
					<?php
				}
				else
				{
					//there are no orders for this code
					?>
					<p><?php echo sprintf('No %s signups have been tracked yet.', $pmpro_affiliates_singular_name, 'pmpro_affiliates'); ?></p>
					<?php
				}
			?>		
			<?php if(!empty($help)) { ?>
			<div class="pmpro_content_message">
				<h3><?php _e('How to Create Links for this Code', 'pmpro_affilliates'); ?></h2>
				
				<p><?php echo sprintf( 
					__( 'Add the string %s or %s to any link to this site. If you would like to track against specific campaigns, you can add the parameter %s or to your URL. Some example links are included below.', 'pmpro_affiliates' ),
					'<code>?pa='.$affiliate->code.'</code>',
					'<code>&amp;pa='.$affiliate->code.'</code>',
					'<code>?subid=CAMPAIGN_NAME</code>',
					'<code>&subid=CAMPAIGN_NAME</code>'
				); ?></p>				 

				<p><strong><?php _e('Homepage', 'pmpro_affiliates'); ?>:</strong> <input type="text" style="width:100%;" readonly value="<?php echo site_url(); ?>/?pa=<?php echo $affiliate->code;?>" /></p>
				<p><strong><?php _e('Membership Levels', 'pmpro_affiliates'); ?>:</strong> <input type="text" style="width:100%;" readonly value="<?php echo pmpro_url("levels"); ?>?pa=<?php echo $affiliate->code;?>" /></p>
				<p><strong><?php _e('Homepage with Campaign Tracking', 'pmpro_affiliates'); ?>:</strong> <input type="text" style="width:100%;" readonly value="<?php echo site_url(); ?>/?pa=<?php echo $affiliate->code;?>&subid=FACEBOOK" /></p>
			</div>
			<?php } ?>
			<?php
		}
		else
		{		
			//show affiliates		
			?>
			<h2><?php _e('Select a Code', 'pmpro_affiliates'); ?></h2>
			<ul>
				<?php foreach($pmpro_affiliates as $affiliate) { ?>
					<li><a href="<?php echo get_permalink($post->ID);?>?report=<?php echo $affiliate->id;?>"><?php echo $affiliate->code;?></a></li>
				<?php } ?>
			</ul>
			<?php
		}
		
		if(!empty($back_link))
		{
			?>
			<hr />
			<nav id="nav-below" class="navigation" role="navigation">
				<div class="nav-next alignright">
					<a href="<?php echo pmpro_url("account")?>"><?php _e('View Your Membership Account &rarr;', 'pmpro_affiliates');?></a>
				</div>
				<?php if(!empty($report)) { ?>
					<div class="nav-prev alignleft">
						<a href="<?php echo get_permalink($affiliate_report_post_id); ?>"><?php _e('&larr; View All Affiliate Codes', 'pmpro_affiliates');?></a>
					</div>
				<?php } ?>
			</nav>
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
