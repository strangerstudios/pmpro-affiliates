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
		$affiliate_enabled = $wpdb->get_var("SELECT enabled FROM $wpdb->pmpro_affiliates WHERE code = '" . $wpdb->escape($affiliate_code) . "' LIMIT 1");
		if(!empty($affiliate_enabled))
		{
			//build cookie string
            $cookiestring = $affiliate_code;
            if($affiliate_subid)
                    $cookiestring .= "," . $affiliate_subid;

            //how long?
            $cookielength = $wpdb->get_var("SELECT cookiedays FROM $wpdb->pmpro_affiliates WHERE code = '" . $wpdb->escape($affiliate_code) . "' LIMIT 1");
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
       	//!!!
	}
}
add_action("pmpro_after_checkout", "pmpro_affiliates_pmpro_after_checkout");

//add affiliates page to admin

//affiliates page (add new)

//affiliates page (view)

//affiliates page (report)


