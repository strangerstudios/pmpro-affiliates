<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function register_pmpro_affiliates_settings() {
	//register our settings
	register_setting( 'pmpro-affiliates-settings-group', 'pmpro_affiliates_singular_name' );
	register_setting( 'pmpro-affiliates-settings-group', 'pmpro_affiliates_plural_name' );
	register_setting( 'pmpro-affiliates-settings-group', 'pmpro_affiliates_recurring' );
}
add_action( 'admin_init', 'register_pmpro_affiliates_settings' );

$pmpro_affiliates_settings = pmpro_affiliates_get_settings();

if(!empty($_REQUEST['save']))
	$save = true;
else
	$save = false;

if(!empty($_REQUEST['pmpro_affiliates_singular_name']))
	$pmpro_affiliates_singular_name = sanitize_text_field( wp_unslash( $_REQUEST['pmpro_affiliates_singular_name'] ) );
else
	$pmpro_affiliates_singular_name = sanitize_text_field( $pmpro_affiliates_settings['pmpro_affiliates_singular_name'] );

if(!empty($_REQUEST['pmpro_affiliates_plural_name']))
	$pmpro_affiliates_plural_name = sanitize_text_field( wp_unslash( $_REQUEST['pmpro_affiliates_plural_name'] ) );
else
	$pmpro_affiliates_plural_name = sanitize_text_field( $pmpro_affiliates_settings['pmpro_affiliates_plural_name'] );

if ( isset( $_REQUEST['pmpro_affiliates_recurring'] ) ) {
	$pmpro_affiliates_recurring = sanitize_text_field( wp_unslash( $_REQUEST['pmpro_affiliates_recurring'] ) );
} else {
	$pmpro_affiliates_recurring = sanitize_text_field( $pmpro_affiliates_settings['pmpro_affiliates_recurring'] );
}

// Verify the nonce before saving. On failure, keep the submitted values in the form so they can be saved again.
if ( ! empty( $save ) && ( empty( $_REQUEST['pmpro_affiliates_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_REQUEST['pmpro_affiliates_nonce'] ) ), 'pmpro_affiliates_save_settings' ) ) ) {
	$save = false;
	?>
		<div id="message" class="error">
			<p><?php esc_html_e( 'Your session has expired. Please try again.', 'pmpro-affiliates' ); ?></p>
		</div>
	<?php
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

	?>
		<div id="message" class="updated fade">
			<p><?php esc_html_e( 'Settings successfully saved.', 'pmpro-affiliates' ); ?></p>
		</div>
	<?php
}
?>
<h1><?php esc_html_e('Affiliate Settings', 'pmpro-affiliates'); ?></h1>
<form action="" method="post">
	<input name="saveid" type="hidden" value="<?php echo esc_attr( $edit ); ?>" />
	<table class="form-table">
	<tbody>
		<tr>
			<th scope="row"><label for="pmpro_affiliates_singular_name"><?php esc_html_e('Singular Name', 'pmpro-affiliates'); ?></label></th>
			<td>
				<input type="text" name="pmpro_affiliates_singular_name" id="pmpro_affiliates_singular_name" value="<?php echo esc_html( $pmpro_affiliates_singular_name ); ?>" /><br />
				<p class="description"><?php esc_html_e('i.e. affiliate, referral, invitation', 'pmpro-affiliates'); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="pmpro_affiliates_plural_name"><?php esc_html_e('Plural Name', 'pmpro-affiliates'); ?></label></th>
			<td>
				<input type="text" name="pmpro_affiliates_plural_name" id="pmpro_affiliates_plural_name" value="<?php echo esc_html( $pmpro_affiliates_plural_name ); ?>" /><br />
				<p class="description"><?php esc_html_e('i.e. affiliates, referrals, invitations', 'pmpro-affiliates'); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="pmpro_affiliates_recurring"><?php esc_html_e('Credit for Recurring Orders', 'pmpro-affiliates'); ?></label></th>
			<td>
				<select name="pmpro_affiliates_recurring" id="pmpro_affiliates_recurring">
					<option value="0" <?php selected( $pmpro_affiliates_recurring, 0 ); ?>><?php esc_html_e('No - only credit affiliate for initial payment.', 'pmpro-affiliates'); ?></option>
					<option value="1" <?php selected( $pmpro_affiliates_recurring, 1 ); ?>><?php esc_html_e('Yes - credit affiliate for initial payment and recurring orders.', 'pmpro-affiliates'); ?></option>
				</select>
			</td>
		</tr>
	</tbody>
	</table>

	<p class="submit">
		<input name="save" type="hidden" value="1" />
		<?php wp_nonce_field( 'pmpro_affiliates_save_settings', 'pmpro_affiliates_nonce' ); ?>
		<input type="submit" class="button button-primary" value="<?php esc_html_e('Save Settings', 'pmpro-affiliates'); ?>" />
	</p>
</form>
