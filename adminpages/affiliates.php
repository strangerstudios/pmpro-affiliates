<?php
	//vars
	global $wpdb, $pmpro_currency_symbol;
	
	if(isset($_REQUEST['edit']))	
		$edit = $_REQUEST['edit'];
	else
		$edit = false;
	
	if(isset($_REQUEST['delete']))
		$delete = $_REQUEST['delete'];
	else
		$delete = false;
		
	if(isset($_REQUEST['saveid']))
		$saveid = $_POST['saveid'];
	else
		$saveid = false;
	
	if($saveid)
	{
		//get vars		
		
		//fix up dates		
	
		//updating or new?
		if($saveid > 0)
		{
			
		}
		else
		{
			
		}
		
	}
	
	//are we deleting?
	if(!empty($delete))
	{
		
	}
?>
<div class="wrap pmpro_admin">	
	<div class="pmpro_banner">		
		<a class="pmpro_logo" title="Paid Memberships Pro - Membership Plugin for WordPress" target="_blank" href="<?php echo pmpro_https_filter("http://www.paidmembershipspro.com")?>"><img src="<?php echo PMPRO_URL?>/images/PaidMembershipsPro.gif" width="350" height="45" border="0" alt="Paid Memberships Pro(c) - All Rights Reserved" /></a>
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
		</h2>
		
		<?php if(!empty($pmpro_msg)) { ?>
			<div id="message" class="<?php if($pmpro_msgt == "success") echo "updated fade"; else echo "error"; ?>"><p><?php echo $pmpro_msg?></p></div>
		<?php } ?>
		
		<div>
			<?php
				// get the affiliate...
				if($edit > 0)
				{
					
				}
				elseif(!empty($copy) && $copy > 0)		
				{	
					
				}

				// didn't find a discount code, let's add a new one...
				if(empty($affiliate->id)) $edit = -1;

				//defaults for new codes
				if($edit == -1)
				{
				
				}								
			?>
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
                        <td><input name="code" type="text" size="20" value="<?php echo str_replace("\"", "&quot;", stripslashes($affiliate->code))?>" /></td>
                    </tr>                    															
                    					
				</tbody>
			</table>
			
			<?php do_action("pmpro_affiliate_after_settings"); ?>
							
			<p class="submit topborder">
				<input name="save" type="submit" class="button-primary" value="Save Code" /> 					
				<input name="cancel" type="button" value="Cancel" onclick="location.href='<?php echo get_admin_url(NULL, '/admin.php?page=pmpro-affiliates')?>';" />
			</p>
			</form>
		</div>
		
	<?php } else { ?>	
	
		<h2>
			Affiliates
			<a href="admin.php?page=pmpro-affiliates&edit=-1" class="button add-new-h2">Add New Affiliate</a>
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
				<th></th>		
				<th></th>						
			</tr>
		</thead>
		<tbody>
			<?php				
				if(empty($affiliates))
				{
				?>
					<tr><td colspan="7" class="pmpro_pad20">					
						<p>Use affiliates to track orders coming in from different sales campaigns and partners. <a href="admin.php?page=pmpro-affiliates&edit=-1">Create your first affiliate now</a>.</p>
					</td></tr>
				<?php
				}
				else
				{					
				}
				?>
		</tbody>
		</table>
		
	<?php } ?>
	
</div>
<?php
?>
