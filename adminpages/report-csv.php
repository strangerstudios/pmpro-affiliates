<?php			
	global $wpdb, $pmpro_currency_symbol, $current_user;
	
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
		
	//only admins can get this
	if(!function_exists("current_user_can") || 
		(!current_user_can("manage_options") && !current_user_can("pmpro_affiliates_report_csv") && ($report != "all" && $current_user->user_login != $affiliate->affiliateuser))
	)
	{
		die(__("You do not have permissions to perform this action.", "pmpro"));
	}	
	
	$sqlQuery = "SELECT a.code, o.affiliate_subid as subid, a.name, u.ID as user_id, u.user_login, u.display_name as display_name, o.membership_id, UNIX_TIMESTAMP(o.timestamp) as timestamp, o.total, o.status FROM $wpdb->pmpro_membership_orders o LEFT JOIN $wpdb->pmpro_affiliates a ON o.affiliate_id = a.id LEFT JOIN $wpdb->users u ON o.user_id = u.ID WHERE o.affiliate_id <> '' AND o.status NOT IN('pending', 'error', 'refunded', 'refund', 'token', 'review') ";
	if($report != "all")
		$sqlQuery .= " AND a.id = '" . esc_sql($report) . "' ";
	$affiliate_orders = $wpdb->get_results($sqlQuery);
	
	//begin output
	header("Content-type: text/csv");	
	header("Content-Disposition: attachment; filename=affiliates_report.csv");
		
	//headings
	echo "code,sub-id,user_id,user_login,display_name,membership_level,date,total\n";
	
	if(!empty($affiliate_orders))
	{
		global $pmpro_currency_symbol;
		foreach($affiliate_orders as $order)
		{	
			$level = pmpro_getLevel( $order->membership_id );		
			echo pmpro_enclose($order->code) . ",";
			echo pmpro_enclose($order->subid) . ",";
			echo pmpro_enclose($order->user_id) . ",";
			echo pmpro_enclose($order->user_login) . ",";
			echo pmpro_enclose($order->display_name) . ",";
			echo pmpro_enclose($level->name) . ",";
			echo pmpro_enclose(date("Y-m-d", $order->timestamp)) . ",";
			echo pmpro_enclose($order->total) . "\n";
		}
	}
	
	function pmpro_enclose($s)
	{
		return "\"" . str_replace("\"", "\\\"", $s) . "\"";
	}