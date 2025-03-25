<?php
/*
Plugin Name: Paid Memberships Pro - Affiliates Add On
Plugin URI: https://www.paidmembershipspro.com/add-ons/pmpro-lightweight-affiliate-tracking/
Description: Create affiliate accounts with unique referrer URLs to track membership checkouts.
Version: 0.6.2
Author: Paid Memberships Pro
Author URI: https://www.paidmembershipspro.com
Text Domain: pmpro-affiliates
Domain Path: /languages
*/

define( 'PMPRO_AFFILIATES_VERSION', '0.6.2' );
define( 'PMPRO_AFFILIATES_DIR', dirname( __FILE__ ) );

require_once dirname( __FILE__ ) . '/pages/report.php';
require_once dirname( __FILE__ ) . '/includes/blocks.php';


/**
 * Load the languages folder for translations.
 */
function pmpro_affiliates_load_textdomain() {
	load_plugin_textdomain( 'pmpro-affiliates', false, basename( dirname( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'pmpro_affiliates_load_textdomain' );

// require Paid Memberships Pro
function pmpro_affiliates_dependencies() {
	include_once ABSPATH . 'wp-admin/includes/plugin.php';
	if ( function_exists( 'is_plugin_active' ) ) {
		if ( ! is_plugin_active( 'paid-memberships-pro/paid-memberships-pro.php' ) ) {
			$plugin = plugin_basename( __FILE__ );
			deactivate_plugins( $plugin );
		}
	}
}
add_action( 'init', 'pmpro_affiliates_dependencies' );

// setup options
function pmpro_affiliates_set_wpdb() {
	global $wpdb, $table_prefix;
	$wpdb->pmpro_affiliates = $table_prefix . 'pmpro_affiliates';
}
add_action( 'init', 'pmpro_affiliates_set_wpdb', 5 );

// Get options
function pmpro_affiliates_get_options() {
	$default_options = array( 'db_version' => 0 );

	return get_option( 'pmpro_affiliates_options', $default_options );
}

// Get settings
function pmpro_affiliates_get_settings() {
	$default_settings = array(
		'pmpro_affiliates_singular_name' => esc_html__( 'affiliate', 'pmpro-affiliates' ),
		'pmpro_affiliates_plural_name'   => esc_html__( 'affiliates', 'pmpro-affiliates' ),
		'pmpro_affiliates_recurring'     => '0',
	);

	return get_option( 'pmpro_affiliates_settings', $default_settings );
}

// Add page setting for the frontend Affiliate Report page
function pmpro_affiliates_extra_page_settings( $pages ) {
	$pmpro_affiliates_settings      = pmpro_affiliates_get_settings();
	$pmpro_affiliates_singular_name = $pmpro_affiliates_settings['pmpro_affiliates_singular_name'];

	$pages['affiliate_report'] = array(
		'title' => sprintf( esc_html__( '%s Report', 'pmpro-affiliates' ), ucwords( $pmpro_affiliates_singular_name ) ),
		'content' => '[pmpro_affiliates_report]',
		'hint'    => sprintf( esc_html__( 'Include the shortcode %s or Affiliate Report block.', 'pmpro-affiliates' ), '[pmpro_affiliates_report]' ),
	);
	return $pages;
}
add_action( 'pmpro_extra_page_settings', 'pmpro_affiliates_extra_page_settings' );

// Add links to the bottom of the list
function pmpro_affiliates_member_links_bottom() {
	// Check if the user has affiliate codes
	global $pmpro_affiliates, $pmpro_pages;
	$pmpro_affiliates             = pmpro_affiliates_getAffiliatesForUser();
	$pmpro_pages_affiliate_report = $pmpro_pages['affiliate_report'];

	// If the user has affiliates codes, add the link
	if ( ! empty( $pmpro_affiliates ) && ! empty( $pmpro_pages_affiliate_report ) ) {
		?>
		<li><a href="<?php echo esc_url( get_permalink( $pmpro_pages_affiliate_report ) ); ?>"><?php echo esc_html( get_the_title( $pmpro_pages['affiliate_report'] ) ); ?></a></li>
		<?php
	}
}
add_filter( 'pmpro_member_links_bottom', 'pmpro_affiliates_member_links_bottom' );

// setup db
function pmpro_affiliates_checkDB() {

	$pmpro_affiliates_options  = pmpro_affiliates_get_options();
	$pmpro_affiliates_settings = pmpro_affiliates_get_settings();

	$db_version = $pmpro_affiliates_options['db_version'];

	// if we can't find the DB tables, reset db_version to 0
	global $wpdb, $table_prefix;
	$wpdb->hide_errors();
	$wpdb->pmpro_affiliates = $table_prefix . 'pmpro_affiliates';
	$table_exists           = $wpdb->query( "SHOW TABLES LIKE '" . $wpdb->pmpro_affiliates . "'" );
	if ( ! $table_exists ) {
		$db_version = 0;
	}

	// SQL for the affiliates table
	$sqlQuery = '
		CREATE TABLE `' . $wpdb->pmpro_affiliates . "` (
		  id int(11) unsigned NOT NULL AUTO_INCREMENT,
		  code varchar(32) NOT NULL,
		  name varchar(255) NOT NULL,
		  affiliateuser varchar(255) NOT NULL,
		  trackingcode mediumtext NOT NULL,
		  cookiedays int(11) NOT NULL DEFAULT '30',
		  enabled tinyint(4) NOT NULL DEFAULT '1',
		  commissionrate decimal(10,2) NOT NULL DEFAULT '0.00',
		  visits int(11) unsigned NOT NULL DEFAULT '0',
		  PRIMARY KEY (id),
		  KEY affiliateid (code),
		  KEY affiliateuser (affiliateuser),
		  KEY enabled (enabled)
		);
	";

	// Create the database table.
	if ( $db_version == 0 ) {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sqlQuery );

		// save the db version
		$db_version                             = 0.6;
		$pmpro_affiliates_options['db_version'] = $db_version;
		update_option( 'pmpro_affiliates_options', $pmpro_affiliates_options );
	} elseif ( $db_version > 0 && $db_version < 0.6 ) {
		// update the db version
		require_once dirname( __FILE__ ) . '/includes/updates/upgrade_0_6.php';
		pmpro_affiliates_upgrade_0_6();
	}
}
add_action( 'admin_init', 'pmpro_affiliates_checkDB', 20 );

// check for affiliate code
function pmpro_affiliates_wp_head() {
	global $pmpro_affiliate_code, $pmpro_affiliate_subid;
	if ( ! empty( $_REQUEST['pa'] ) ) {
		$pmpro_affiliate_code = preg_replace( '[^a-zA-Z0-9]', '', $_REQUEST['pa'] );
	}
	if ( ! empty( $_REQUEST['subid'] ) ) {
		$pmpro_affiliate_subid = preg_replace( '[^a-zA-Z0-9]', '', $_REQUEST['subid'] );
	}

	if ( ! empty( $pmpro_affiliate_code ) ) {
		global $wpdb;

		// check that the code is enabled
		$affiliate_enabled = $wpdb->get_var( "SELECT enabled FROM $wpdb->pmpro_affiliates WHERE code = '" . esc_sql( $pmpro_affiliate_code ) . "' LIMIT 1" );
		if ( ! empty( $affiliate_enabled ) ) {
			// build cookie string
			$cookiestring = $pmpro_affiliate_code;
			if ( ! empty( $pmpro_affiliate_subid ) ) {
				$cookiestring .= ',' . $pmpro_affiliate_subid;
			}

			// track the visit
			if ( empty( $_COOKIE['pmpro_affiliate'] ) ) {
				$wpdb->query( "UPDATE $wpdb->pmpro_affiliates SET visits = visits + 1 WHERE code = '" . esc_sql( $pmpro_affiliate_code ) . "' LIMIT 1" );
			}

			// how long?
			$cookielength = $wpdb->get_var( "SELECT cookiedays FROM $wpdb->pmpro_affiliates WHERE code = '" . esc_sql( $pmpro_affiliate_code ) . "' LIMIT 1" );
			?>
			<script type="text/javascript" language="javascript">
					var today = new Date();
					today.setTime( today.getTime() );
					var expires = <?php echo intval( $cookielength ); ?> * 1000 * 60 * 60 * 24;
					var expires_date = new Date( today.getTime() + (expires) );
					document.cookie = 'pmpro_affiliate=<?php echo esc_html( $cookiestring ); ?>;path=/;expires=' + expires_date.toGMTString() + ';';
			</script>
			<?php
		}
	}
}
add_action( 'wp_head', 'pmpro_affiliates_wp_head' );

// update order if cookie is present or the last order in this subscription used an affiliate
function pmpro_affiliates_pmpro_added_order( $order, $savefirst = false ) {
	 global $wpdb, $pmpro_affiliates_saved_order;
	$pmpro_affiliates_saved_order = true;
	$pmpro_affiliates_settings    = pmpro_affiliates_get_settings();
	$pmpro_affiliates_recurring   = $pmpro_affiliates_settings['pmpro_affiliates_recurring'];

	$user_id = $order->user_id;
	// check for an order for this subscription with an affiliate id
	if ( ! empty( $order->subscription_transaction_id ) && ! empty( $pmpro_affiliates_recurring ) ) {
		$lastorder = $wpdb->get_row(
			"SELECT affiliate_id, affiliate_subid
			FROM $wpdb->pmpro_membership_orders
			WHERE user_id = '" . esc_sql( $order->user_id ) . "'
			AND subscription_transaction_id = '" . esc_sql( $order->subscription_transaction_id ) . "'
			AND affiliate_id <> 0
			ORDER BY id DESC LIMIT 1"
		);
		if ( ! empty( $lastorder ) ) {
			$affiliate_id    = $lastorder->affiliate_id;
			$affiliate_subid = $lastorder->affiliate_subid;

			$affiliate_code = $wpdb->get_var( "SELECT code FROM $wpdb->pmpro_affiliates WHERE id = '" . esc_sql( $affiliate_id ) . "' LIMIT 1" );
		}
	}

	// check for cookie
	if ( empty( $affiliate_code ) && ! empty( $_COOKIE['pmpro_affiliate'] ) ) {
		$parts          = explode( ',', $_COOKIE['pmpro_affiliate'] );
		$affiliate_code = sanitize_text_field( $parts[0] );
		if ( isset( $parts[1] ) ) {
			$affiliate_subid = sanitize_text_field( $parts[1] );
		} else {
			$affiliate_subid = '';
		}
		$affiliate_id = $wpdb->get_var( "SELECT id FROM $wpdb->pmpro_affiliates WHERE code = '" . esc_sql( $affiliate_code ) . "' LIMIT 1" );
	}

	if ( ! empty( $affiliate_code ) ) {
		// check that it is enabled
		$affiliate_enabled = $wpdb->get_var( "SELECT enabled FROM $wpdb->pmpro_affiliates WHERE code = '" . esc_sql( $affiliate_code ) . "' LIMIT 1" );
		if ( ! $affiliate_enabled ) {
			return; // don't do anything
		} else {
			// save first?
			if ( $savefirst ) {
				$order->saveOrder();
			}

			// update order in the database
			if ( ! empty( $order->id ) ) {
				$sqlQuery = "UPDATE $wpdb->pmpro_membership_orders SET affiliate_id = '" . esc_sql( $affiliate_id ) . "', affiliate_subid = '" . esc_sql( $affiliate_subid ) . "' WHERE id = " . esc_sql( $order->id ) . ' LIMIT 1';
				$wpdb->query( $sqlQuery );
			}
		}
	}
}
add_action( 'pmpro_added_order', 'pmpro_affiliates_pmpro_added_order' );

/*
	If we get to the after_checkout hook without $pmpro_affiliates_saved_order set, let's pass the order directly.
*/

function pmpro_affiliates_no_order_checkout( $user_id, $morder ) {

	global $pmpro_affiliates_saved_order;

	// if an order was added, we're good already
	if ( $pmpro_affiliates_saved_order ) {
		return;
	}

	//now pass through the function above
	return pmpro_affiliates_pmpro_added_order($morder, true);		//will create an order if there is an affiliate id

}
add_action( 'pmpro_after_checkout', 'pmpro_affiliates_no_order_checkout', 10, 2 );

/*
	If the level is set to create an affiliate, let's create it.
*/
function pmpro_affiliates_generate_affiliate_after_checkout( $user_id, $morder ) {
	global $wpdb;

	//get some info
	$user = get_userdata($user_id);
	$pmpro_level = pmpro_getSpecificMembershipLevelForUser( $user_id, $morder->membership_id );

	//generate the affiliate after membership checkout
	$pmpro_create_affiliate_level = get_option( 'pmpro_create_affiliate_level_' . $pmpro_level->id );
	$code                         = pmpro_affiliates_getNewCode();
	if ( ! empty( $pmpro_create_affiliate_level ) ) {
		/**
		 * Filter the number of days to set the cookie for the affiliate.
		 *
		 * @param int $days The number of days to set the cookie for the affiliate.
		 * @param int $user_id The user ID.
		 * @param object $pmpro_level The membership level object.
		 * @return int The number of days to set the cookie for the affiliate.
		 */
		$days     = intval( apply_filters( 'pmproaf_default_cookie_duration', 30, $user_id, $pmpro_level ) );
		$sqlQuery = "INSERT INTO $wpdb->pmpro_affiliates (code, name, affiliateuser, trackingcode, cookiedays, enabled) VALUES('" . esc_sql( $code ) . "', '" . esc_sql( $user->display_name ) . "', '" . esc_sql( $user->user_login ) . "', '', $days, '1')";
		$wpdb->query( $sqlQuery );
	}
}
add_action( 'pmpro_after_checkout', 'pmpro_affiliates_generate_affiliate_after_checkout', 10, 2 );

// add tracking code to confirmation page
function pmpro_affiliates_pmpro_confirmation_message( $message ) {
	global $current_user, $wpdb, $pmpro_affiliates, $pmpro_pages;
	if ( ! empty( $_COOKIE['pmpro_affiliate'] ) ) {
		$parts          = explode( ',', $_COOKIE['pmpro_affiliate'] );
		$affiliate_code = sanitize_text_field( $parts[0] );

		if ( ! empty( $affiliate_code ) ) {
			global $current_user, $wpdb;

			$affiliate_enabled = $wpdb->get_var( "SELECT enabled FROM $wpdb->pmpro_affiliates WHERE code = '" . esc_sql( $affiliate_code ) . "' LIMIT 1" );
			if ( $affiliate_enabled ) {
				$tracking_code = $wpdb->get_var( "SELECT trackingcode FROM $wpdb->pmpro_affiliates WHERE code = '" . esc_sql( $affiliate_code ) . "' LIMIT 1" );
				if ( ! empty( $tracking_code ) ) {
					// filter
					$order = new MemberOrder();
					$order->getLastMemberOrder();
					$tracking_code = str_replace( '!!ORDER_AMOUNT!!', $order->total, $tracking_code );
					$tracking_code = str_replace( '!!ORDER_ID!!', $order->code, $tracking_code );
					$tracking_code = str_replace( '!!LEVEL_NAME!!', $current_user->membership_level->name, $tracking_code );

					// add to message
					$message .= "\n" . stripslashes( $tracking_code );
				}
			}
		}
	}

	$pmpro_affiliates             = pmpro_affiliates_getAffiliatesForUser( $current_user->ID );
	$pmpro_pages_affiliate_report = $pmpro_pages['affiliate_report'];

	// If the user has affiliates codes, add the link
	if ( ! empty( $pmpro_affiliates ) && ! empty( $pmpro_pages_affiliate_report ) ) {
		$message .= '<p><a href="' . get_permalink( $pmpro_pages_affiliate_report ) . '">' . get_the_title( $pmpro_pages_affiliate_report ) . '</a></p>';
	}
	return $message;
}
add_filter( 'pmpro_confirmation_message', 'pmpro_affiliates_pmpro_confirmation_message' );

/*
 * Add Menu Item for "Affiliates".
 */
function pmpro_affiliates_add_pages() {
	if ( ! defined( 'PMPRO_VERSION' ) ) {
		return;
	}

	if ( version_compare( PMPRO_VERSION, '2.0' ) >= 0 ) {
		add_submenu_page( 'pmpro-dashboard', __( 'Affiliates', 'pmpro-affiliates' ), __( 'Affiliates', 'pmpro-affiliates' ), 'manage_options', 'pmpro-affiliates', 'pmpro_affiliates_adminpage' );
	} else {
		add_submenu_page( 'pmpro-membershiplevels', __( 'Affiliates', 'pmpro-affiliates' ), __( 'Affiliates', 'pmpro-affiliates' ), 'manage_options', 'pmpro-affiliates', 'pmpro_affiliates_adminpage' );
	}
}
add_action( 'admin_menu', 'pmpro_affiliates_add_pages', 20 );

// affiliates page (add new)
function pmpro_affiliates_adminpage() {
	 require_once dirname( __FILE__ ) . '/adminpages/affiliates.php';
}

// add page to admin bar
function pmpro_affiliates_admin_bar_menu() {
	global $wp_admin_bar;
	if ( ! is_super_admin() || ! is_admin_bar_showing() ) {
		return;
	}
	$wp_admin_bar->add_menu(
		array(
			'id'     => 'pmpro-affiliates',
			'parent' => 'paid-memberships-pro',
			'title'  => __( 'Affiliates', 'pmpro-affiliates' ),
			'href'   => get_admin_url(
				null,
				'/admin.php?page=pmpro-affiliates'
			),
		)
	);
}
add_action( 'admin_bar_menu', 'pmpro_affiliates_admin_bar_menu', 1000 );

// Get a new random code for affiliate codes.
function pmpro_affiliates_getNewCode() {
	$code = pmpro_getDiscountCode();

	/**
	 * Filter to allow customization of the code generated for Affiliate
	 *
	 * @param string $code The generated code for the Affiliate.
	 *
	 * @return string The string for the affiliate code.
	 */
	$code = apply_filters( 'pmpro_affiliates_new_code', $code );

	return $code;
}

function pmpro_affiliates_yesorno( $var ) {
	if ( ! empty( $var ) ) {
		return __( 'Yes', 'pmpro-affiliates' );
	} else {
		return __( 'No', 'pmpro-affiliates' );
	}
}

/*
	This next function connects affiliate codes to discount codes with the same code. If one is set, the other will be set.

	If an affiliate code was passed or is already saved in a cookie and a discount code is used, the previous affiliate takes precedence.

	Legacy function for PMPro < 3.0.
*/
function pmpro_affiliates_set_discount_code() {
	global $wpdb, $pmpro_level;

	// Is PMPro active?
	if ( ! function_exists( 'pmpro_is_checkout' ) ) {
		return;
	}

	// Only run the rest of this function if PMPro is not yet updated to 3.0.
	if ( version_compare( PMPRO_VERSION, '3.0' ) >= 0 ) {
		return;
	}
	
	// Make sure we're on the checkout page.
	if ( ! pmpro_is_checkout() ) {
		return;
	}

	//checkout page
	if(  !isset( $_REQUEST['discount_code'] ) && ( ! empty( $_COOKIE['pmpro_affiliate'] ) || ! empty( $_REQUEST['pa'] ) ) ) {
		if( ! empty( $_COOKIE['pmpro_affiliate'] ) ) {
			$affiliate_code = sanitize_text_field( $_COOKIE['pmpro_affiliate'] );
		} else {
			$affiliate_code = sanitize_text_field( $_REQUEST['pa'] );
		}

		//set the discount code if there is an affiliate cookie
		$exists = $wpdb->get_var( "SELECT id FROM $wpdb->pmpro_discount_codes WHERE code = '" . esc_sql( $affiliate_code ) . "' LIMIT 1" );
		if( ! empty( $exists ) ) {
			//check that the code is applicable for this level
			if( ! empty( $pmpro_level ) ) {
				$level_id = $pmpro_level->id;
			} elseif ( ! empty( $_REQUEST['level'] ) ) {
				$level_id = intval( $_REQUEST['level'] );
			} else {
				$level_id = null;
			}
			$codecheck = pmpro_checkDiscountCode( $affiliate_code, $level_id );
			if( $codecheck ) {
				$_REQUEST['discount_code'] = $affiliate_code;
				
				//prevent caching of this page load
				add_action( 'send_headers', 'nocache_headers' );
			}
		}
	} elseif( ! empty( $_REQUEST['discount_code'] ) && empty( $_REQUEST['pa'] ) && empty( $_COOKIE['pmpro_affiliate'] ) ) {
		//set the affiliate id to the discount code
		$exists = $wpdb->get_var( "SELECT id FROM $wpdb->pmpro_affiliates WHERE code = '" . esc_sql( $_REQUEST['discount_code'] ) . "' LIMIT 1" );
		if( ! empty( $exists ) ) {
			//set the affiliate id passed in to the discount code
			$_REQUEST['pa'] = sanitize_text_field( $_REQUEST['discount_code'] );

			// set the cookie to the discount code
			$_COOKIE['pmpro_affiliate'] = sanitize_text_field( $_REQUEST['discount_code'] );
			
			//prevent caching of this page load
			add_action( 'send_headers', 'nocache_headers' );
		}
	}
}
add_action( 'init', 'pmpro_affiliates_set_discount_code', 30 );

/**
 * Set the default discount code to the affiliates code for sites updated to PMPro v3.0.
 *
 * @since 0.6.2
 *
 * @param string|null $code The default discount code.
 * @param int         $level_id The level ID.
 * @return string The default discount code.
 */
function pmpro_affiliates_default_discount_code( $code, $level_id ) {
	global $wpdb;

	// Get the affiliate code.
	if( ! empty( $_COOKIE['pmpro_affiliate'] ) ) {
		$affiliate_code = sanitize_text_field( $_COOKIE['pmpro_affiliate'] );
	} elseif ( ! empty( $_REQUEST['pa'] ) ) {
		$affiliate_code = sanitize_text_field( $_REQUEST['pa'] );
	} else {
		// No affiliate set. Return the code that was passed.
		return $code;
	}

	// Check if the affiliate code exists.
	$exists = $wpdb->get_var( "SELECT id FROM $wpdb->pmpro_discount_codes WHERE code = '" . esc_sql( $affiliate_code ) . "' LIMIT 1" );
	if ( empty( $exists ) ) {
		// The discount code for this affiliate does not exist. Return the code that was passed.
		return $code;
	}

	// Check that the code is applicable for this level.
	$codecheck = pmpro_checkDiscountCode( $affiliate_code, $level_id );
	if ( ! $codecheck ) {
		// The discount code for this affiliate is not applicable for this level. Return the code that was passed.
		return $code;
	}

	// Prevent caching of this page load.
	add_action( 'send_headers', 'nocache_headers' );

	// Set the default discount code to the affiliate code.
	return $affiliate_code;
}
add_filter( 'pmpro_default_discount_code', 'pmpro_affiliates_default_discount_code', 10, 2 );

/**
 * If a discount code is used, check if it is an affiliate code.
 * If so, set the affiliate cookie and 'pa' parameter.
 *
 * @since 0.6.2
 *
 * @param object $level The checkout level object.
 * @return object The checkout level object.
 */
function pmpro_affiliates_discount_code_level( $level ) {
	global $wpdb;

	if ( empty( $level->discount_code ) ) {
		// The 'discount_code' parameter will only be set for PMPro v3.0+.
		// For previous versions, the pmpro_affiliates_set_discount_code() function will handle this behavior.
		return $level;
	}

	// Check if the affiliate exists.
	$exists = $wpdb->get_var( "SELECT id FROM $wpdb->pmpro_affiliates WHERE code = '" . esc_sql( $level->discount_code ) . "' LIMIT 1" );
	if ( empty( $exists ) ) {
		// The discount code for this affiliate does not exist. Return the level object.
		return $level;
	}

	// Set the affiliate cookie and 'pa' parameter.
	$_COOKIE['pmpro_affiliate'] = sanitize_text_field( $level->discount_code );
	$_REQUEST['pa']             = sanitize_text_field( $level->discount_code );

	// Prevent caching of this page load.
	add_action( 'send_headers', 'nocache_headers' );

	return $level;
}
add_filter( 'pmpro_discount_code_level', 'pmpro_affiliates_discount_code_level' );

// service for csv export
function pmpro_wp_ajax_affiliates_report_csv() {
	require_once dirname( __FILE__ ) . '/adminpages/report-csv.php';
	exit;
}
add_action( 'wp_ajax_affiliates_report_csv', 'pmpro_wp_ajax_affiliates_report_csv' );

// check if a user is an affiliate
function pmpro_affiliates_getAffiliatesForUser( $user_id = null ) {
	if ( empty( $user_id ) ) {
		if ( ! is_user_logged_in() ) {
			return array();
		}

		global $current_user;
		$user_id    = $current_user->ID;
		$user_login = $current_user->user_login;
	} else {
		$user       = get_userdata( $user_id );
		$user_login = $user->user_login;
	}

	if ( empty( $user_login ) ) {
		return array();
	}

	global $wpdb;
	$affiliates = $wpdb->get_results( "SELECT * FROM $wpdb->pmpro_affiliates WHERE affiliateuser = '" . esc_sql( $user_login ) . "' and enabled = '1' " );

	if ( ! empty( $affiliates ) ) {
		return $affiliates;
	} else {
		return array();
	}
}

/*
	Add checkbox to automatically create an affiliate code for members of this level.
*/
// show the checkbox on the edit level page
function pmpro_affiliates_pmpro_membership_level_after_other_settings() {
	$pmpro_affiliates_settings      = pmpro_affiliates_get_settings();
	$pmpro_affiliates_singular_name = $pmpro_affiliates_settings['pmpro_affiliates_singular_name'];

	$level_id = intval( $_REQUEST['edit'] );
	if ( $level_id > 0 ) {
		$pmpro_create_affiliate_level = get_option( 'pmpro_create_affiliate_level_' . $level_id );
	} else {
		$pmpro_create_affiliate_level = false;
	}
	?>
<h2 class="topborder"><?php echo esc_html( sprintf( esc_html__( '%s Settings', 'pmpro-affiliates' ), ucwords( $pmpro_affiliates_singular_name ) ) ); ?></h2>
<table>
<tbody class="form-table">
	<tr>
		<th scope="row" valign="top"><label for="pmpro_create_affiliate_level"><?php echo esc_html( sprintf( esc_html__( 'Automatically create %s code?', 'pmpro-affiliates' ), $pmpro_affiliates_singular_name ) ); ?></label></th>
		<td>
			<input type="checkbox" id="pmpro_create_affiliate_level" name="pmpro_create_affiliate_level" value="1" <?php checked( $pmpro_create_affiliate_level, 1 ); ?> />
			<label for="pmpro_create_affiliate_level"><?php echo esc_html( sprintf( esc_html__( 'Check this if you want to automatically create the %s code for members of this level.', 'pmpro-affiliates' ), $pmpro_affiliates_singular_name ) ); ?></label>
		</td>
	</tr>
</tbody>
</table>
	<?php
}
add_action( 'pmpro_membership_level_after_other_settings', 'pmpro_affiliates_pmpro_membership_level_after_other_settings' );

// save affiliate auto-creationg setting when the level is saved/added
function pmpro_affiliate_pmpro_save_membership_level( $level_id ) {
	if ( isset( $_REQUEST['pmpro_create_affiliate_level'] ) ) {
		$pmpro_create_affiliate_level = intval( $_REQUEST['pmpro_create_affiliate_level'] );
	} else {
		$pmpro_create_affiliate_level = 0;
	}
	update_option( 'pmpro_create_affiliate_level_' . $level_id, $pmpro_create_affiliate_level );
}
add_action( 'pmpro_save_membership_level', 'pmpro_affiliate_pmpro_save_membership_level' );

/**
 * Enqueue scripts and styles on the frontend if shortcode is present on the page.
 *
 * @since 0.6.2
 */
function pmpro_affiliates_enqueue_scripts() {
	global $post;

	if ( is_singular() && ( has_shortcode( $post->post_content, 'pmpro_affiliates_report' ) || has_block( 'pmpro-affiliates/pmpro-affiliates-report' ) ) ) {
		wp_enqueue_style( 'pmpro_affiliates', plugins_url( 'includes/css/frontend.css', __FILE__ ) );
	}
}
add_action( 'wp_enqueue_scripts', 'pmpro_affiliates_enqueue_scripts' );
/**
 * Register scripts needed for admin area.
 */
function pmpro_affiliates_register_scripts_styles() {
	// Only load script on PMPro affiliates pages.
	if ( ! isset( $_REQUEST['page'] ) || $_REQUEST['page'] != 'pmpro-affiliates' ) {
		return;
	}

	// Register scripts.
	wp_register_script( 'pmpro_affiliates_admin', plugins_url( 'includes/js/admin.js', __FILE__ ), array( 'jquery', 'jquery-ui-autocomplete' ), PMPRO_AFFILIATES_VERSION, true );

	$localize_data = array(
		'ajaxurl'     => admin_url( 'admin-ajax.php' ),
		'paid_status' => esc_html__( 'Paid', 'pmpro-affiliates' ),
		'search_nonce' => wp_create_nonce( 'pmpro_affiliates_search_nonce' ),
		'reset_status' => esc_html__( 'Payment status reset.', 'pmpro-affiliates' ),
	);

	// Localize scripts with data from PHP.
	wp_localize_script( 'pmpro_affiliates_admin', 'pmpro_affiliates_admin', $localize_data );
	wp_enqueue_script( 'pmpro_affiliates_admin' );
}
add_action( 'admin_enqueue_scripts', 'pmpro_affiliates_register_scripts_styles' );

/**
 * Ajax handler for saving affiliate paid order setting.
 * 
 * @since 0.6.2
 */
function pmpro_affiliates_mark_as_paid() {

	// check the nonce, if it's not valid bail.
	$nonce = $_REQUEST['_wpnonce'];
	if ( ! wp_verify_nonce( $nonce, 'pmpro_affiliates_mark_as_paid') ) {
		return;
	}

	$order_id = (int) $_REQUEST['order_id'];
	update_pmpro_membership_order_meta( $order_id, 'pmpro_affiliate_paid', true );
	exit;
}
add_action( 'wp_ajax_pmpro_affiliates_mark_as_paid', 'pmpro_affiliates_mark_as_paid' );


/**
 * Ajax handler for resetting affiliate paid order to unpaid.
 * 
 * @since 0.6.2
 */
function pmpro_affiliates_reset_paid_status() {

	// check the nonce, if it's not valid bail.
	$nonce = $_REQUEST['_wpnonce'];
	if ( ! wp_verify_nonce( $nonce, 'pmpro_affiliates_reset_paid_status' ) ) {
		return;
	}

	$order_id = (int) $_REQUEST['order_id'];
	delete_pmpro_membership_order_meta( $order_id, 'pmpro_affiliate_paid', NULL );
	exit;
}
add_action( 'wp_ajax_pmpro_affiliates_reset_paid_status', 'pmpro_affiliates_reset_paid_status' );


/**
 * AJAX handler for searching for affiliates and autocomplete.
 * 
 * @since 0.6.2
 */
function pmpro_affiliates_autocomplete_user_search() {

	// Verify the nonce for this action.
	wp_verify_nonce( $_REQUEST['search_nonce'], 'pmpro_affiliates_search_nonce' );

	$search = sanitize_text_field( $_REQUEST['search'] );
	$search_limit = apply_filters( 'pmpro_affiliates_autocomplete_user_search_limit', 25 );

	$user_query = new WP_User_Query( 
		array(
			'search' => '*' . $search . '*',
			'search_columns' => array( 'user_login', 'user_nicename' ),
			'number' => (int) $search_limit
		) 
	);

	$results = array();
	if ( ! empty( $user_query->get_results() ) ) {
		foreach ( $user_query->get_results() as $user ) {
			$results[] = $user->user_login;
		}
	}

	wp_send_json_success( $results );
}
add_action( 'wp_ajax_pmpro_affiliates_autocomplete_user_search', 'pmpro_affiliates_autocomplete_user_search' );

/**
 * Function to get all paid commission and the amount.
 *
 * @param string $affiliate_code The affiliate code to get the paid commissions for.
 * @return float $commission The total amount of commission paid to date.
 */
function pmpro_affiliates_get_commissions( $affiliate_code, $state = 'paid' ) {
	global $wpdb;

	// No affiliate ID passed through, let's bail.
	if ( empty( $affiliate_code ) ) {
		return;
	}

	$paid_commission = 0;

	$sql_query = "SELECT SUM(" . esc_sql( 'o.' . pmpro_affiliates_get_commission_calculation_source() ) . ") as total, a.commissionrate
				FROM $wpdb->pmpro_membership_orders o
				LEFT JOIN $wpdb->pmpro_membership_ordermeta om 
				ON o.id = om.pmpro_membership_order_id
				AND om.meta_key = 'pmpro_affiliate_paid'
				JOIN $wpdb->pmpro_affiliates a
				ON o.affiliate_id = a.id
				AND a.code = '" . esc_sql( $affiliate_code ) . "'";

	// Get paid or unpaid commissions.
	if ( 'paid' === $state ) {
		$sql_query .= " AND om.meta_value IS NOT NULL";
	} else {
		$sql_query .= " AND om.meta_value IS NULL";
	}

	$sql_query .= " GROUP BY o.code";

	$commission_data = $wpdb->get_results( $sql_query );

	// Loop through commission data and calculate the total paid commissions.
	if ( ! empty( $commission_data ) ) {
		foreach( $commission_data as $commission ) {
			$paid_commission += $commission->total *  $commission->commissionrate;
		}
	}

	return $paid_commission;
}

/**
 * Show affiliate information when editing a user in WordPress.
 * 
 * @since 0.6.2
 */
function pmpro_affiliates_edit_user_profile( $user ) {
	if ( ! current_user_can( 'edit_users' ) ) {
		return;
	}

	echo '<h2>' . esc_html__( 'Affiliate Information', 'pmpro-affiliates' ) . '</h2>';
	// Show Affiliate information for this particular user.
	$affiliates = pmpro_affiliates_getAffiliatesForUser( $user->ID );

	// Get values if the user is an affiliate or not.
	if ( ! empty( $affiliates ) ) {
		$is_affiliate = true;
		$affiliate_code = $affiliates[0]->code;
	} else {
		$is_affiliate = false;
	}

	if ( ! $is_affiliate ) {
		$affiliate_actions = '<a href="' . esc_url( admin_url( 'admin.php?page=pmpro-affiliates&edit=-1') ) . '">+ ' . esc_html__( 'Create Affiliate', 'pmpro-affiliates' ) . '</a>';
	} else {
		$affiliate_actions = '<a class="button button-secondary" href="' . esc_url( admin_url( 'admin.php?page=pmpro-affiliates&report=' . (int) $affiliates[0]->id ) ) . '">' . esc_html__( 'View Report', 'pmpro-affiliates' ) . '</a>';
		$affiliate_actions .= ' <a class="button button-secondary" href="' . esc_url( admin_url( 'admin.php?page=pmpro-affiliates&edit=' . (int) $affiliates[0]->id ) ) . '">' . esc_html__( 'Edit Affiliate', 'pmpro-affiliates' ) . '</a>';	
	}


	?>
	<table class="form-table">
		<tbody>
			<tr>
				<th><?php esc_html_e( 'Affiliate Status', 'pmpro-affiliates' ); ?></th>
				<td><?php echo wp_kses_post( $affiliate_actions ); ?></td>
			</tr>
		</tbody>
	</table>
	<?php


}
add_action( 'edit_user_profile', 'pmpro_affiliates_edit_user_profile' );

/**
 * Get the order column to use when calculating commisions.
 * Should be either 'total' or 'subtotal'.
 *
 * @since 0.6.2
 *
 * @return string The order column to use.
 */
function pmpro_affiliates_get_commission_calculation_source() {
	/**
	 * Filter to change the source of the commission calculation.
	 * The valid values are 'total' or 'subtotal'.
	 *
	 * @param string $source The source of the commission calculation.
	 */
	$source = apply_filters( 'pmpro_affiliates_commission_calculation_source', 'total' );

	// Validate the source.
	if ( 'total' !== $source && 'subtotal' !== $source ) {
		$source = 'total';
	}

	return $source;
}

/*
Function to add links to the plugin action links
*/
function pmpro_affiliates_add_action_links( $links ) {
	$new_links = array(
		'<a href="' . get_admin_url( null, 'admin.php?page=pmpro-affiliates' ) . '">' . esc_html__( 'Manage Affiliates', 'pmpro-affiliates' ) . '</a>',
	);
	return array_merge( $new_links, $links );
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'pmpro_affiliates_add_action_links' );

/*
Function to add links to the plugin row meta
*/
function pmpro_affiliates_plugin_row_meta( $links, $file ) {
	if ( strpos( $file, 'pmpro-affiliates.php' ) !== false ) {
		$new_links = array(
			'<a href="' . esc_url( 'https://www.paidmembershipspro.com/add-ons/pmpro-lightweight-affiliate-tracking/?utm_source=plugin&utm_medium=plugin-row-meta&utm_campaign=add-ons' ) . '" title="' . esc_attr( __( 'View Documentation', 'pmpro-affiliates' ) ) . '">' . __( 'Docs', 'pmpro-affiliates' ) . '</a>',
			'<a href="' . esc_url( 'https://www.paidmembershipspro.com/support/?utm_source=plugin&utm_medium=plugin-row-meta&utm_campaign=support' ) . '" title="' . esc_attr( __( 'Visit Customer Support Forum', 'pmpro-affiliates' ) ) . '">' . __( 'Support', 'pmpro-affiliates' ) . '</a>',
		);
		$links     = array_merge( $links, $new_links );
	}
	return $links;
}
add_filter( 'plugin_row_meta', 'pmpro_affiliates_plugin_row_meta', 10, 2 );
