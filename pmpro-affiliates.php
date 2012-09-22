<?php
/*
Plugin Name: PMPro Affiliates
Plugin URI: http://www.paidmembershipspro.com/pmpro-affiliates/
Description: Create affiliate accounts and codes. If a code is passed to a page as a parameter, a cookie is set. If a cookie is present after checkout, the order is awarded to the affiliate account.
Version: .2.2
Author: Stranger Studios
Author URI: http://www.strangerstudios.com
*/

/*
	Story
	* Admin creates affiliate account and code.
	* Fields: id, code, name, affiliate_user, tracking_code, cookie_length, enabled
	* If affiliate code is passed as a parameter, a cookie is set for the specified number of days.
	* If a cookie is present after checkout, the order is awarded to the affiliate.
	* On the confirmation page, if an order has an affiliate id, show the cooresponding tracking code.
	* Reports in the admin, showing orders for each affiliate.
	* Associate an affiliate with a user to give that user access to view reports.
	
	Questions
	* Allow setting of fees?
	* Track recurring orders?
	* Affiliate reports in front end or back end? How much to show affiliates.	
*/

/*
	Saving an order to test.
*/
function pmproa_test()
{
	$lastorder = new MemberOrder();
	$lastorder->getLastMemberOrder();
	if(!empty($lastorder->id))
	{
		$lastorder->id = NULL;
		$lastorder->code = NULL;		
		$lastorder->saveOrder();	//new order
	}
}
//add_action("init", "pmproa_test", 30);
	
//require Paid Memberships Pro
function pmpro_affiliates_dependencies()
{
	include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	if(function_exists("is_plugin_active"))
	{
		if(!is_plugin_active('paid-memberships-pro/paid-memberships-pro.php'))
		{
			$plugin = plugin_basename(__FILE__);
			deactivate_plugins($plugin);
		}
	}
}
add_action("init", "pmpro_affiliates_dependencies");

//setup options
function pmpro_affiliates_getOptions()
{
	global $pmpro_affiliates_options;
	$pmpro_affiliates_options = get_option("pmpro_affiliates_options", array("db_version"=>0));
}
add_action("init", "pmpro_affiliates_getOptions");

//setup db
function pmpro_affiliates_checkDB()
{
	global $pmpro_affiliates_options;
	$db_version = $pmpro_affiliates_options['db_version'];
	
	//if we can't find the DB tables, reset db_version to 0
	global $wpdb, $table_prefix;
	$wpdb->hide_errors();
	$wpdb->pmpro_affiliates = $table_prefix . 'pmpro_affiliates';
	$table_exists = $wpdb->query("SHOW TABLES LIKE '" . $wpdb->pmpro_affiliates . "'");	
	if(!$table_exists)		
		$db_version = 0;
		
	if($db_version < .1)
	{
		//add the db table		
		$sqlQuery = "		
			CREATE TABLE `" . $wpdb->pmpro_affiliates . "` (		  
			  `id` int(11) NOT NULL AUTO_INCREMENT,
			  `code` varchar(32) NOT NULL,
			  `name` varchar(255) NOT NULL,
			  `affiliateuser` varchar(255) NOT NULL,
			  `trackingcode` mediumtext NOT NULL,	  
			  `cookiedays` int(11) NOT NULL DEFAULT '30',
			  `enabled` tinyint(4) NOT NULL DEFAULT '1',
			  PRIMARY KEY (`id`),
			  KEY `affiliateid` (`code`),
			  KEY `affiliateuser` (`affiliateuser`),
			  KEY `enabled` (`enabled`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 ;
		";
		$wpdb->query($sqlQuery);		
		
		//save the db version
		$db_version = .1;
		$pmpro_affiliates_options['db_version'] = $db_version;
		update_option("pmpro_affiliates_options", $pmpro_affiliates_options);
	}
}
add_action("init", "pmpro_affiliates_checkDB", 20);

//check for affiliate code
function pmpro_affiliates_wp_head()
{
	global $pmpro_affiliate_code, $pmpro_affiliate_subid;
	if(!empty($_REQUEST['pa']))
		$pmpro_affiliate_code = preg_replace("[^a-zA-Z0-9]", "", $_REQUEST['pa']);
	if(!empty($_REQUEST['subid']))
		$pmpro_affiliate_subid = preg_replace("[^a-zA-Z0-9]", "", $_REQUEST['subid']);

	if(!empty($pmpro_affiliate_code))
	{
		global $wpdb;
		
		//check that the code is enabled
		$affiliate_enabled = $wpdb->get_var("SELECT enabled FROM $wpdb->pmpro_affiliates WHERE code = '" . $wpdb->escape($pmpro_affiliate_code) . "' LIMIT 1");
		if(!empty($affiliate_enabled))
		{
			//build cookie string
            $cookiestring = $pmpro_affiliate_code;
            if(!empty($pmpro_affiliate_subid))
				$cookiestring .= "," . $pmpro_affiliate_subid;

            //how long?
            $cookielength = $wpdb->get_var("SELECT cookiedays FROM $wpdb->pmpro_affiliates WHERE code = '" . $wpdb->escape($pmpro_affiliate_code) . "' LIMIT 1");
            ?>
            <script type="text/javascript" language="javascript">
                    var today = new Date();
                    today.setTime( today.getTime() );
                    var expires = <?php echo intval($cookielength); ?> * 1000 * 60 * 60 * 24;
                    var expires_date = new Date( today.getTime() + (expires) );
                    document.cookie = 'pmpro_affiliate=<?php echo $cookiestring; ?>;path=/;expires=' + expires_date.toGMTString() + ';';
            </script>
            <?php		
		}
	}
}
add_action("wp_head", "pmpro_affiliates_wp_head");

//update order if cookie is present or the last order in this subscription used an affiliate
function pmpro_affiliates_pmpro_added_order($order, $savefirst = false)
{
	global $wpdb, $pmpro_affiliates_saved_order;
	$pmpro_affiliates_saved_order = true;
	
	$user_id = $order->user_id;
		
	//check for an order for this subscription with an affiliate id
	if(!empty($order->subscription_transaction_id))
	{
		$lastorder = $wpdb->get_row("SELECT affiliate_id, affiliate_subid FROM $wpdb->pmpro_membership_orders WHERE user_id = '" . $wpdb->escape($order->user_id) . "' ORDER BY id DESC LIMIT 1, 1");
		
		if(!empty($lastorder->affiliate_id))
		{
			$affiliate_id = $lastorder->affiliate_id;
			$affiliate_subid = $lastorder->affiliate_subid;
			
			$affiliate_code = $wpdb->get_var("SELECT code FROM $wpdb->pmpro_affiliates WHERE id = '" . $wpdb->escape($affiliate_id) . "' LIMIT 1");
		}
	}
		
	//check for cookie
	if(empty($affiliate_code) && !empty($_COOKIE['pmpro_affiliate']))
	{				
		$parts = split(",", $_COOKIE['pmpro_affiliate']);
		$affiliate_code = $parts[0];
		$affiliate_subid = $parts[1];
		$affiliate_id = $wpdb->get_var("SELECT id FROM $wpdb->pmpro_affiliates WHERE code = '" . $wpdb->escape($affiliate_code) . "' LIMIT 1");
	}
			
	if(!empty($affiliate_code))
	{
       	//check that it is enabled
        $affiliate_enabled = $wpdb->get_var("SELECT enabled FROM $wpdb->pmpro_affiliates WHERE code = '" . $wpdb->escape($affiliate_code) . "' LIMIT 1");
        if(!$affiliate_enabled)
        {						
			return;	//don't do anything
       	}
       	else
		{
			//save first?			
			if($savefirst)
				$order->saveOrder();
			
			//update order in the database       	
			if(!empty($order->id))
			{
				$sqlQuery = "UPDATE $wpdb->pmpro_membership_orders SET affiliate_id = '" . $affiliate_id . "', affiliate_subid = '" . $affiliate_subid . "' WHERE id = " . $order->id . " LIMIT 1";
				$wpdb->query($sqlQuery);								
			}
		}
	}
}
add_action("pmpro_added_order", "pmpro_affiliates_pmpro_added_order");

/*
	If we get to the after_checkout hook without $pmpro_affiliates_saved_order set, let's add a $0 order
*/
function pmpro_affiliates_no_order_checkout($user_id)
{
	global $pmpro_affiliates_saved_order;
	
	//if an order was added, we're good already
	if($pmpro_affiliates_saved_order)
		return;
		
	//get some info
	$user = get_userdata($user_id);
	$pmpro_level = pmpro_getMembershipLevelForUser($user_id);

	//setup an order
	$morder = new MemberOrder();	
	$morder->membership_id = $pmpro_level->id;
	$morder->membership_name = $pmpro_level->name;	
	$morder->InitialPayment = 0;	
	$morder->user_id = $user_id;
	$morder->Email = $user->user_email;
	$morder->gateway = "check";
	$morder->Gateway = NULL;
	$morder->getMembershipLevel();
	
	//now pass through the function above
	return pmpro_affiliates_pmpro_added_order($morder, true);		//will create an order if there is an affiliate id
}
add_action("pmpro_after_checkout", "pmpro_affiliates_no_order_checkout");

//add tracking code to confirmation page
function pmpro_affiliates_pmpro_confirmation_message($message)
{
	if(!empty($_COOKIE['pmpro_affiliate']))
	{
		$parts = split(",", $_COOKIE['pmpro_affiliate']);
		$affiliate_code = $parts[0];
		
		if(!empty($affiliate_code))
		{
			global $current_user, $wpdb;
			
			$affiliate_enabled = $wpdb->get_var("SELECT enabled FROM $wpdb->pmpro_affiliates WHERE code = '" . $wpdb->escape($affiliate_code) . "' LIMIT 1");
			if($affiliate_enabled)
			{
				$tracking_code = $wpdb->get_var("SELECT trackingcode FROM $wpdb->pmpro_affiliates WHERE code = '" . $wpdb->escape($affiliate_code) . "' LIMIT 1");
				if(!empty($tracking_code))
				{
					//filter
					$order = new MemberOrder();
					$order->getLastMemberOrder();
					$tracking_code = str_replace("!!ORDER_ID!!", $order->code, $tracking_code);
					$tracking_code = str_replace("!!LEVEL_NAME!!", $current_user->membership_level->name, $tracking_code);
					
					//add to message
					$message .= "\n" . stripslashes($tracking_code);
				}
			}
		}
	}
	
	return $message;
}
add_filter("pmpro_confirmation_message", "pmpro_affiliates_pmpro_confirmation_message");

//add affiliates page to admin
function pmpro_affiliates_add_pages()
{
	add_submenu_page('pmpro-membershiplevels', 'Affiliates', 'Affiliates', 'manage_options', 'pmpro-affiliates', 'pmpro_affiliates_adminpage');
}
add_action('admin_menu', 'pmpro_affiliates_add_pages', 20);

//affiliates page (add new)
function pmpro_affiliates_adminpage()
{
	require_once(dirname(__FILE__) . "/adminpages/affiliates.php");
}

//get a new random code for affiliate codes
function pmpro_affiliates_getNewCode()
{
	global $wpdb;
	
	while(empty($code))
	{
		$scramble = md5(AUTH_KEY . time() . SECURE_AUTH_KEY);			
		$code = substr($scramble, 0, 10);
		$check = $wpdb->get_var("SELECT code FROM $wpdb->pmpro_affiliates WHERE code = '$code' LIMIT 1");				
		if($check || is_numeric($code))
			$code = NULL;
	}
	
	return strtoupper($code);
}

function pmpro_affiliates_yesorno($var)
{
	if(!empty($var))
		return "Yes";
	else
		return "No";
}

/*
	This next function connects affiliate codes to discount codes with the same code. If one is set, the other will be set.
	
	If an affiliate code was passed or is already saved in a cookie and a discount code is used, the previous affiliate takes precedence. 
*/
function pmpro_affiliates_set_discount_code()
{
	global $wpdb;
	
	//checkout page
	if(!isset($_REQUEST['discount_code']) && (!empty($_COOKIE['pmpro_affiliate']) || !empty($_REQUEST['pa'])))
	{
		if(!empty($_COOKIE['pmpro_affiliate']))
			$affiliate_code = $_COOKIE['pmpro_affiliate'];
		else
			$affiliate_code = $_REQUEST['pa'];
	
		//set the discount code if there is an affiliate cookie			
		$exists = $wpdb->get_var("SELECT id FROM $wpdb->pmpro_discount_codes WHERE code = '" . $wpdb->escape($affiliate_code) . "' LIMIT 1");
		if(!empty($exists))
		{
			//check that the code is applicable for this level
			$codecheck = pmpro_checkDiscountCode($affiliate_code, $_REQUEST['level']);
			if($codecheck)
				$_REQUEST['discount_code'] = $affiliate_code;
		}
	}
	elseif(!empty($_REQUEST['discount_code']) && empty($_REQUEST['pa']) && empty($_COOKIE['pmpro_affiliate']))
	{
		//set the affiliate id to the discount code			
		$exists = $wpdb->get_var("SELECT id FROM $wpdb->pmpro_affiliates WHERE code = '" . $wpdb->escape($_REQUEST['discount_code']) . "' LIMIT 1");
		if(!empty($exists))
		{
			//set the affiliate id passed in to the discount code
			$_REQUEST['pa'] = $_REQUEST['discount_code'];
									
			//set the cookie to the discount code
			$_COOKIE['pmpro_affiliate'] = $_REQUEST['discount_code'];
		}	
	}			
}
add_action("init", "pmpro_affiliates_set_discount_code", 30);
