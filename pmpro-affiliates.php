<?php
/*
Plugin Name: PMPro Affiliates
Plugin URI: http://www.paidmembershipspro.com/pmpro-affiliates/
Description: Create affiliate accounts and codes. If a code is passed to a page as a parameter, a cookie is set. If a cookie is present after checkout, the order is awarded to the affiliate account.
Version: .1
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

//update order if cookie is present
function pmpro_affiliates_pmpro_after_checkout($user_id)
{
	//check for cookie
	if(!empty($_COOKIE['pmpro_affiliate']))
	{		
		global $wpdb;       
       
        $parts = split(",", $_COOKIE['pmpro_affiliate']);
        $affiliate_code = $parts[0];

       	//check that it is enabled
        $affiliate_enabled = $wpdb->get_var("SELECT enabled FROM $wpdb->pmpro_affiliates WHERE code = '" . $wpdb->escape($affiliate_code) . "' LIMIT 1");
        if($affiliate_enabled)
        {
                $affiliate_subid = $parts[1];
                $affiliate_id = $wpdb->get_var("SELECT id FROM $wpdb->pmpro_affiliates WHERE code = '" . $wpdb->escape($affiliate_code) . "' LIMIT 1");
       	}
        else
        {
                $affiliate_code = NULL;
       	}
       	
       	//update order in the database
       	$order = new MemberOrder();
		$order->getLastMemberOrder($user_id);
		if(!empty($order->id))
		{
			$sqlQuery = "UPDATE $wpdb->pmpro_membership_orders SET affiliate_id = '" . $affiliate_id . "', affiliate_subid = '" . $affiliate_subid . "' WHERE id = " . $order->id . " LIMIT 1";
			$wpdb->query($sqlQuery);
		}
	}
}
add_action("pmpro_after_checkout", "pmpro_affiliates_pmpro_after_checkout");

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