<?php
/**
 * Process Payouts admin page (two-step form).
 * Included by payouts.php when action=process|preview.
 * The confirm step is handled by pmpro_affiliates_process_payouts_confirm() on admin_init.
 */

// Permissions check.
if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'You do not have permission to access this page.', 'pmpro-affiliates' ) );
}

$action = isset( $_REQUEST['action'] ) ? sanitize_text_field( $_REQUEST['action'] ) : 'process';

// -------------------------------------------------------------------------
// Settings for nav.
// -------------------------------------------------------------------------
$pmpro_affiliates_settings      = pmpro_affiliates_get_settings();
$pmpro_affiliates_plural_name   = $pmpro_affiliates_settings['pmpro_affiliates_plural_name'];
$pmpro_affiliates_singular_name = $pmpro_affiliates_settings['pmpro_affiliates_singular_name'];

// -------------------------------------------------------------------------
// Step 2: Preview — run query and show preview table.
// -------------------------------------------------------------------------
$preview_data = array();
$preview_error = '';

if ( 'preview' === $action ) {
	check_admin_referer( 'pmpro_affiliates_process_payouts' );

	$date_start      = sanitize_text_field( wp_unslash( $_POST['date_start'] ?? '' ) );
	$date_end        = sanitize_text_field( wp_unslash( $_POST['date_end'] ?? '' ) );
	$affiliate_id    = (int) ( $_POST['affiliate_id'] ?? 0 );
	$minimum         = max( 0, (float) ( $_POST['minimum'] ?? 0 ) );
	$method_override = pmpro_affiliates_sanitize_payout_method( sanitize_text_field( wp_unslash( $_POST['method_override'] ?? '' ) ) );

	$preview_data = pmpro_affiliates_get_unpaid_commissions_for_payout( $affiliate_id, $date_start, $date_end, $minimum );

	if ( empty( $preview_data ) ) {
		$preview_error = __( 'No unpaid commissions found for the selected criteria.', 'pmpro-affiliates' );
	}
} else {
	// Step 1 defaults.
	$date_start      = '';
	$date_end        = '';
	$affiliate_id    = 0;
	$minimum         = 0;
	$method_override = '';
}

require_once PMPRO_DIR . '/adminpages/admin_header.php';
?>

<nav class="pmpro-nav-primary" aria-labelledby="pmpro-affiliates-payouts-menu">
	<h2 id="pmpro-affiliates-payouts-menu" class="screen-reader-text"><?php esc_html_e( 'Affiliates Area Menu', 'pmpro-affiliates' ); ?></h2>
	<ul>
		<li><a href="admin.php?page=pmpro-affiliates"><?php esc_html_e( 'Manage', 'pmpro-affiliates' ); ?> <?php echo esc_html( ucwords( $pmpro_affiliates_plural_name ) ); ?></a></li>
		<li><a href="admin.php?page=pmpro-affiliates&report=all"><?php esc_html_e( 'Reports', 'pmpro-affiliates' ); ?></a></li>
		<li><a href="admin.php?page=pmpro-affiliates&settings=1"><?php esc_html_e( 'Settings', 'pmpro-affiliates' ); ?></a></li>
		<li><a href="admin.php?page=pmpro-affiliates-payouts" class="current"><?php esc_html_e( 'Payouts', 'pmpro-affiliates' ); ?></a></li>
	</ul>
</nav>
<br class="clear" />

<h1><?php esc_html_e( 'Process Payouts', 'pmpro-affiliates' ); ?></h1>

<?php
// -------------------------------------------------------------------------
// STEP 2: Preview table.
// -------------------------------------------------------------------------
if ( 'preview' === $action ) :
?>

<?php if ( ! empty( $preview_error ) ) : ?>
	<div class="notice notice-warning"><p><?php echo esc_html( $preview_error ); ?></p></div>
<?php endif; ?>

<?php if ( ! empty( $preview_data ) ) :
	$method_labels = array(
		'paypal' => __( 'PayPal', 'pmpro-affiliates' ),
		'venmo'  => __( 'Venmo', 'pmpro-affiliates' ),
		'check'  => __( 'Check', 'pmpro-affiliates' ),
		'wire'   => __( 'Wire', 'pmpro-affiliates' ),
		'other'  => __( 'Other', 'pmpro-affiliates' ),
	);
	$preview_total     = 0.0;
	$preview_order_ids = array();
	$payable_count     = 0;
	$blocked_count     = 0;
	foreach ( $preview_data as $aid => $d ) {
		$method = ! empty( $method_override ) ? $method_override : pmpro_affiliates_sanitize_payout_method( $d['affiliate']->payout_method );

		$preview_data[ $aid ]['method']        = $method;
		$preview_data[ $aid ]['account_error'] = pmpro_affiliates_get_payout_account_error( $method, $d['affiliate']->payout_email );

		// Affiliates without a usable account are shown but not paid.
		if ( '' !== $preview_data[ $aid ]['account_error'] ) {
			$blocked_count++;
			continue;
		}

		$payable_count++;
		$preview_total    += $d['amount'];
		$preview_order_ids = array_merge( $preview_order_ids, $d['order_ids'] );
	}
?>

<p><?php
printf(
	/* translators: 1: affiliate count, 2: total amount */
	esc_html__( 'Ready to pay %1$d affiliate(s) a total of %2$s.', 'pmpro-affiliates' ),
	(int) $payable_count,
	esc_html( pmpro_formatPrice( $preview_total ) )
);
?></p>

<?php if ( $blocked_count > 0 ) : ?>
	<div class="notice notice-warning inline"><p><?php
	printf(
		/* translators: %d: number of affiliates that will not be paid */
		esc_html( _n(
			'%d affiliate will not be paid because their payout account is missing or does not match the payout method. Update the affiliate, then process payouts again.',
			'%d affiliates will not be paid because their payout accounts are missing or do not match the payout method. Update the affiliates, then process payouts again.',
			$blocked_count,
			'pmpro-affiliates'
		) ),
		(int) $blocked_count
	);
	?></p></div>
<?php endif; ?>

<table class="wp-list-table widefat striped">
	<thead>
		<tr>
			<th><?php esc_html_e( 'Affiliate', 'pmpro-affiliates' ); ?></th>
			<th><?php esc_html_e( 'Payout Account', 'pmpro-affiliates' ); ?></th>
			<th><?php esc_html_e( 'Method', 'pmpro-affiliates' ); ?></th>
			<th><?php esc_html_e( '# Orders', 'pmpro-affiliates' ); ?></th>
			<th><?php esc_html_e( 'Commission Total', 'pmpro-affiliates' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ( $preview_data as $data ) :
			$aff          = $data['affiliate'];
			$method       = $data['method'];
			$method_label = isset( $method_labels[ $method ] ) ? $method_labels[ $method ] : $method;
			$saved_method = pmpro_affiliates_sanitize_payout_method( $aff->payout_method );
		?>
		<tr>
			<td>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-affiliates&edit=' . (int) $aff->id ) ); ?>">
					<?php echo esc_html( stripslashes( $aff->name ) ?: $aff->code ); ?>
				</a>
				<small>(<?php echo esc_html( $aff->code ); ?>)</small>
			</td>
			<td>
				<?php if ( ! empty( $aff->payout_email ) ) : ?>
					<?php echo esc_html( $aff->payout_email ); ?>
				<?php endif; ?>
				<?php if ( '' !== $data['account_error'] ) : ?>
					<br />
					<span class="pmpro-affiliates-no-payout-email">
						&#9888; <?php echo esc_html( $data['account_error'] ); ?> &mdash; <?php esc_html_e( 'will not be paid', 'pmpro-affiliates' ); ?>
					</span>
				<?php endif; ?>
			</td>
			<td>
				<?php echo esc_html( $method_label ?: '—' ); ?>
				<?php if ( ! empty( $method_override ) && $saved_method !== $method_override ) : ?>
					<br />
					<span class="pmpro-affiliates-no-payout-email">
						&#9888; <?php
						if ( ! empty( $saved_method ) && isset( $method_labels[ $saved_method ] ) ) {
							printf(
								/* translators: %s: the affiliate's saved payout method */
								esc_html__( 'Overrides saved method (%s)', 'pmpro-affiliates' ),
								esc_html( $method_labels[ $saved_method ] )
							);
						} else {
							esc_html_e( 'Overrides saved method (none set)', 'pmpro-affiliates' );
						}
						?>
					</span>
				<?php endif; ?>
			</td>
			<td><?php echo (int) count( $data['order_ids'] ); ?></td>
			<td><?php echo '' !== $data['account_error'] ? '<del>' . esc_html( pmpro_formatPrice( $data['amount'] ) ) . '</del>' : esc_html( pmpro_formatPrice( $data['amount'] ) ); ?></td>
		</tr>
		<?php endforeach; ?>
	</tbody>
	<tfoot>
		<tr>
			<th colspan="4"><?php esc_html_e( 'Total', 'pmpro-affiliates' ); ?></th>
			<th><?php echo esc_html( pmpro_formatPrice( $preview_total ) ); ?></th>
		</tr>
	</tfoot>
</table>

<form method="post" action="" id="pmpro-affiliates-confirm-payouts-form">
	<?php wp_nonce_field( 'pmpro_affiliates_process_payouts' ); ?>
	<input type="hidden" name="action" value="confirm" />
	<input type="hidden" name="date_start" value="<?php echo esc_attr( $date_start ); ?>" />
	<input type="hidden" name="date_end" value="<?php echo esc_attr( $date_end ); ?>" />
	<input type="hidden" name="affiliate_id" value="<?php echo (int) $affiliate_id; ?>" />
	<input type="hidden" name="minimum" value="<?php echo esc_attr( $minimum ); ?>" />
	<input type="hidden" name="method_override" value="<?php echo esc_attr( $method_override ); ?>" />
	<input type="hidden" name="order_ids" value="<?php echo esc_attr( implode( ',', $preview_order_ids ) ); ?>" />
	<p class="submit topborder">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-affiliates-payouts&action=process' ) ); ?>" class="button"><?php esc_html_e( '&larr; Back', 'pmpro-affiliates' ); ?></a>
		<?php if ( $payable_count > 0 ) : ?>
			<input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Confirm Payouts', 'pmpro-affiliates' ); ?>" />
		<?php endif; ?>
	</p>
</form>
<script>
document.getElementById( 'pmpro-affiliates-confirm-payouts-form' ).addEventListener( 'submit', function() {
	var button = this.querySelector( 'input[type=submit]' );
	if ( button ) {
		button.disabled = true;
	}
} );
</script>

<?php else : ?>
<p>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-affiliates-payouts&action=process' ) ); ?>" class="button"><?php esc_html_e( '&larr; Back', 'pmpro-affiliates' ); ?></a>
</p>
<?php endif; ?>

<?php
// -------------------------------------------------------------------------
// STEP 1: Filter form.
// -------------------------------------------------------------------------
else :
	// Build affiliates list for the select.
	global $wpdb;
	$all_affiliates = $wpdb->get_results( "SELECT id, name, code FROM {$wpdb->pmpro_affiliates} WHERE enabled = 1 ORDER BY name ASC" );
?>

<form method="post" action="">
	<?php wp_nonce_field( 'pmpro_affiliates_process_payouts' ); ?>
	<input type="hidden" name="action" value="preview" />

	<table class="form-table">
		<tbody>
			<tr>
				<th scope="row"><label for="affiliate_id"><?php esc_html_e( 'Affiliate', 'pmpro-affiliates' ); ?></label></th>
				<td>
					<select id="affiliate_id" name="affiliate_id">
						<option value="0"><?php esc_html_e( 'All Affiliates', 'pmpro-affiliates' ); ?></option>
						<?php foreach ( $all_affiliates as $aff ) : ?>
							<option value="<?php echo (int) $aff->id; ?>">
								<?php echo esc_html( stripslashes( $aff->name ) ?: $aff->code ); ?> (<?php echo esc_html( $aff->code ); ?>)
							</option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="date_start"><?php esc_html_e( 'Order Date Start', 'pmpro-affiliates' ); ?></label></th>
				<td>
					<input type="date" id="date_start" name="date_start" value="<?php echo esc_attr( $date_start ); ?>" />
					<small><?php esc_html_e( 'Leave blank for no start limit.', 'pmpro-affiliates' ); ?></small>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="date_end"><?php esc_html_e( 'Order Date End', 'pmpro-affiliates' ); ?></label></th>
				<td>
					<input type="date" id="date_end" name="date_end" value="<?php echo esc_attr( $date_end ); ?>" />
					<small><?php esc_html_e( 'Leave blank for no end limit.', 'pmpro-affiliates' ); ?></small>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="minimum"><?php esc_html_e( 'Minimum Payout Amount', 'pmpro-affiliates' ); ?></label></th>
				<td>
					<input type="number" id="minimum" name="minimum" value="<?php echo esc_attr( $minimum ); ?>" min="0" step="0.01" size="10" />
					<small><?php esc_html_e( 'Affiliates with less than this amount will be excluded. Default 0 (include all).', 'pmpro-affiliates' ); ?></small>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="method_override"><?php esc_html_e( 'Payout Method Override', 'pmpro-affiliates' ); ?></label></th>
				<td>
					<select id="method_override" name="method_override">
						<option value=""><?php esc_html_e( "Use each affiliate's saved method", 'pmpro-affiliates' ); ?></option>
						<option value="paypal"><?php esc_html_e( 'PayPal', 'pmpro-affiliates' ); ?></option>
						<option value="venmo"><?php esc_html_e( 'Venmo', 'pmpro-affiliates' ); ?></option>
						<option value="check"><?php esc_html_e( 'Check', 'pmpro-affiliates' ); ?></option>
						<option value="wire"><?php esc_html_e( 'Wire Transfer', 'pmpro-affiliates' ); ?></option>
						<option value="other"><?php esc_html_e( 'Other', 'pmpro-affiliates' ); ?></option>
					</select>
					<small><?php esc_html_e( 'Override the payment method for all affiliates in this run.', 'pmpro-affiliates' ); ?></small>
				</td>
			</tr>
		</tbody>
	</table>

	<p class="submit">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-affiliates-payouts' ) ); ?>" class="button"><?php esc_html_e( 'Cancel', 'pmpro-affiliates' ); ?></a>
		<input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Preview Payouts', 'pmpro-affiliates' ); ?>" />
	</p>
</form>

<?php endif; ?>

<?php require_once PMPRO_DIR . '/adminpages/admin_footer.php'; ?>
