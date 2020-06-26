<?php

function register_pmpro_affiliates_settings() {
	//register our settings
	register_setting( 'pmpro-affiliates-settings-group', 'pmpro_affiliates_singular_name' );
	register_setting( 'pmpro-affiliates-settings-group', 'pmpro_affiliates_plural_name' );
	register_setting( 'pmpro-affiliates-settings-group', 'pmpro_affiliates_recurring' );
}
add_action( 'admin_init', 'register_pmpro_affiliates_settings' );

global $pmpro_affiliates_settings;
$pmpro_affiliates_settings = get_option("pmpro_affiliates_settings", array("pmpro_affiliates_singular_name"=>"affiliate","pmpro_affiliates_plural_name"=>"affiliates","pmpro_affiliates_recurring"=>"0"));

if(!empty($_REQUEST['save']))
	$save = true;
else
	$save = false;

if(!empty($_REQUEST['pmpro_affiliates_singular_name']))
	$pmpro_affiliates_singular_name = $_REQUEST['pmpro_affiliates_singular_name'];
else
	$pmpro_affiliates_singular_name = $pmpro_affiliates_settings['pmpro_affiliates_singular_name'];

if(!empty($_REQUEST['pmpro_affiliates_plural_name']))
	$pmpro_affiliates_plural_name = $_REQUEST['pmpro_affiliates_plural_name'];
else
	$pmpro_affiliates_plural_name = $pmpro_affiliates_settings['pmpro_affiliates_plural_name'];

if ( isset( $_REQUEST['pmpro_affiliates_recurring'] ) ) {
	$pmpro_affiliates_recurring = $_REQUEST['pmpro_affiliates_recurring'];
} else {
	$pmpro_affiliates_recurring = $pmpro_affiliates_settings['pmpro_affiliates_recurring'];
}

//get form values
if(!empty($save))
{	
	$pmpro_affiliates_settings = array(
		'pmpro_affiliates_singular_name' => $pmpro_affiliates_singular_name,
		'pmpro_affiliates_plural_name' => $pmpro_affiliates_plural_name,
		'pmpro_affiliates_recurring' => $pmpro_affiliates_recurring,
	);
	update_option("pmpro_affiliates_settings", $pmpro_affiliates_settings);	
}
?>

<form action="" method="post">
	<input name="saveid" type="hidden" value="<?php echo $edit?>" />
	<table class="form-table">
	<tbody>
		<tr>
			<th scope="row"><label for="pmpro_affiliates_singular_name"><?php _e('Singular Name', 'pmpro_affiliates'); ?></label></th>
			<td>
				<input type="text" name="pmpro_affiliates_singular_name" id="pmpro_affiliates_singular_name" value="<?php echo $pmpro_affiliates_singular_name; ?>" /><br />
				<p class="description"><?php _e('i.e. affiliate, referral, invitation', 'pmpro_affiliates'); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="pmpro_affiliates_plural_name"><?php _e('Plural Name', 'pmpro_affiliates'); ?></label></th>
			<td>
				<input type="text" name="pmpro_affiliates_plural_name" id="pmpro_affiliates_plural_name" value="<?php echo $pmpro_affiliates_plural_name; ?>" /><br />
				<p class="description"><?php _e('i.e. affiliates, referrals, invitations', 'pmpro_affiliates'); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="pmpro_affiliates_recurring"><?php _e('Credit for Recurring Orders', 'pmpro_affiliates'); ?></label></th>
			<td>
				<select name="pmpro_affiliates_recurring" id="pmpro_affiliates_recurring">
					<option value="0" <?php selected( $pmpro_affiliates_recurring, 0 ); ?>><?php _e('No - only credit affiliate for initial payment.', 'pmpro_affiliates'); ?></option>
					<option value="1" <?php selected( $pmpro_affiliates_recurring, 1 ); ?>><?php _e('Yes - credit affiliate for initial payment and recurring orders.', 'pmpro_affiliates'); ?></option>
				</select>
			</td>
		</tr>
	</tbody>
	</table>

	<p class="submit">	
		<input name="save" type="hidden" value="1" />
		<input type="submit" class="button button-primary" value="<?php _e('Save Settings', 'pmpro_affiliates'); ?>" />
	</p>
</form>
