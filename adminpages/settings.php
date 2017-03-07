<?php

function register_pmpro_affiliates_settings() {
	//register our settings
	register_setting( 'pmpro-affiliates-settings-group', 'pmpro_affiliates_singular_name' );
	register_setting( 'pmpro-affiliates-settings-group', 'pmpro_affiliates_plural_name' );
}
add_action( 'admin_init', 'register_pmpro_affiliates_settings' );

global $pmpro_affiliates_settings;
$pmpro_affiliates_settings = get_option("pmpro_affiliates_settings", array("pmpro_affiliates_singular_name"=>"affiliate","pmpro_affiliates_plural_name"=>"affiliates"));

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

//get form values
if(!empty($save))
{	
	$pmpro_affiliates_settings = array(
		'pmpro_affiliates_singular_name' => $pmpro_affiliates_singular_name,
		'pmpro_affiliates_plural_name' => $pmpro_affiliates_plural_name,
	);
	update_option("pmpro_affiliates_settings", $pmpro_affiliates_settings);	
}
?>

<form action="" method="post">
	<input name="saveid" type="hidden" value="<?php echo $edit?>" />
	<table class="form-table">
	<tbody>
		<tr>
			<th scope="row" colspan="2"><label><?php _e('Global Term Settings', 'pmpro_affiliates'); ?></label>
		</tr>
		<tr>
			<th scope="row"><label for="pmpro_affiliates_singular_name"><?php _e('Singular Name', 'pmpro_affiliates'); ?></label></th>
			<td>
				<input type="text" name="pmpro_affiliates_singular_name" id="pmpro_affiliates_singular_name" value="<?php echo $pmpro_affiliates_singular_name; ?>" /><br />
				<small class="muted">i.e. affiliate, referral, invitation</small>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="pmpro_affiliates_plural_name"><?php _e('Plural Name', 'pmpro_affiliates'); ?></label></th>
			<td>
				<input type="text" name="pmpro_affiliates_plural_name" id="pmpro_affiliates_plural_name" value="<?php echo $pmpro_affiliates_plural_name; ?>" /><br />
				<small class="muted">i.e. affiliates, referrals, invitations</small>
			</td>
		</tr>
	</tbody>
	</table>

	<p class="submit topborder">	
		<input name="save" type="hidden" value="1" />
		<input type="submit" class="button-primary" value="Save Settings" />
	</p>
</form>