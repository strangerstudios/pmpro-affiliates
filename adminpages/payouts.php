<?php
/**
 * Affiliate Payouts admin page.
 * Handles the payout history list, delete actions, and dispatches to process-payouts.php.
 */

// Permissions check.
if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'You do not have permission to access this page.', 'pmpro-affiliates' ) );
}

$action = isset( $_REQUEST['action'] ) ? sanitize_text_field( $_REQUEST['action'] ) : '';

// Dispatch to process-payouts flow.
if ( in_array( $action, array( 'process', 'preview' ), true ) ) {
	require_once dirname( __FILE__ ) . '/process-payouts.php';
	return;
}

// Check for delete redirect from pmpro_affiliates_process_payout_deletes().
if ( isset( $_REQUEST['deleted'] ) ) {
	$deleted_count = (int) $_REQUEST['deleted'];
	$failed_count  = (int) ( $_REQUEST['delete_failed'] ?? 0 );

	$pmpro_msg  = sprintf(
		/* translators: %d: number of payouts deleted */
		_n( '%d payout deleted and its orders unmarked as paid.', '%d payouts deleted and their orders unmarked as paid.', $deleted_count, 'pmpro-affiliates' ),
		$deleted_count
	);
	$pmpro_msgt = 'success';

	if ( $failed_count > 0 ) {
		$pmpro_msg .= ' ' . sprintf(
			/* translators: %d: number of payouts that could not be deleted */
			_n( '%d payout could not be deleted.', '%d payouts could not be deleted.', $failed_count, 'pmpro-affiliates' ),
			$failed_count
		);
		$pmpro_msgt = 'error';
	}
}

// Check for success redirect from process-payouts.
if ( ! empty( $_REQUEST['success'] ) ) {
	$affiliate_count = (int) ( $_REQUEST['affiliate_count'] ?? 0 );
	$total_amount    = (float) ( $_REQUEST['total_amount'] ?? 0 );

	$pmpro_msg = sprintf(
		/* translators: 1: affiliate count, 2: total amount */
		__( 'Payouts processed for %1$d affiliate(s) totaling %2$s.', 'pmpro-affiliates' ),
		$affiliate_count,
		pmpro_formatPrice( $total_amount )
	);
	$pmpro_msgt = 'success';
}

// Check for empty-run redirect from process-payouts.
if ( ! empty( $_REQUEST['error'] ) ) {
	if ( 'locked' === $_REQUEST['error'] ) {
		$pmpro_msg = __( 'Another payout run is in progress. Wait a moment, then check the payout list before trying again.', 'pmpro-affiliates' );
	} elseif ( ! empty( $_REQUEST['skipped'] ) ) {
		$pmpro_msg = __( 'No payouts were created.', 'pmpro-affiliates' );
	} else {
		$pmpro_msg = __( 'No unpaid commissions found for the selected criteria. No payouts were created.', 'pmpro-affiliates' );
	}
	$pmpro_msgt = 'error';
}

// Note affiliates left out of a payout run because their payout account was missing or invalid.
if ( ! empty( $_REQUEST['skipped'] ) ) {
	$skipped_count = (int) $_REQUEST['skipped'];
	$pmpro_msg    .= ' ' . sprintf(
		/* translators: %d: number of affiliates skipped */
		_n(
			'%d affiliate was not paid because their payout account is missing or does not match the payout method.',
			'%d affiliates were not paid because their payout accounts are missing or do not match the payout method.',
			$skipped_count,
			'pmpro-affiliates'
		),
		$skipped_count
	);
}

// Retrieve recent payout IDs from transient (set by process-payouts.php on confirm).
$recent_payout_ids = array();
if ( ! empty( $_REQUEST['success'] ) ) {
	$transient_key     = 'pmpro_affiliates_recent_payouts_' . get_current_user_id();
	$recent_payout_ids = get_transient( $transient_key );
	if ( ! is_array( $recent_payout_ids ) ) {
		$recent_payout_ids = array();
	}
}

// Get payouts for list.
$paged    = isset( $_REQUEST['paged'] ) ? max( 1, (int) $_REQUEST['paged'] ) : 1;
$per_page = 20;
$total    = pmpro_affiliates_get_payouts( array( 'count' => true ) );
$payouts  = pmpro_affiliates_get_payouts( array(
	'limit'  => $per_page,
	'offset' => ( $paged - 1 ) * $per_page,
) );

// Get settings for nav labels.
$pmpro_affiliates_settings      = pmpro_affiliates_get_settings();
$pmpro_affiliates_plural_name   = $pmpro_affiliates_settings['pmpro_affiliates_plural_name'];
$pmpro_affiliates_singular_name = $pmpro_affiliates_settings['pmpro_affiliates_singular_name'];

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

<h1 class="wp-heading-inline"><?php esc_html_e( 'Affiliate Payouts', 'pmpro-affiliates' ); ?></h1>
<a href="admin.php?page=pmpro-affiliates-payouts&action=process" class="page-title-action"><?php esc_html_e( 'Process Payouts', 'pmpro-affiliates' ); ?></a>

<?php if ( ! empty( $pmpro_msg ) ) : ?>
	<div id="message" class="<?php echo 'success' === $pmpro_msgt ? 'updated fade' : 'error'; ?>">
		<p><?php echo esc_html( $pmpro_msg ); ?></p>
		<?php if ( ! empty( $recent_payout_ids ) ) : ?>
			<p>
				<strong><?php esc_html_e( 'Download CSV for this payout run:', 'pmpro-affiliates' ); ?></strong>
			</p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" style="display:inline;">
				<input type="hidden" name="action" value="pmpro_affiliates_payout_csv" />
				<input type="hidden" name="format" value="summary" />
				<input type="hidden" name="payout_ids" value="<?php echo esc_attr( implode( ',', array_map( 'intval', $recent_payout_ids ) ) ); ?>" />
				<?php wp_nonce_field( 'pmpro_affiliates_payout_csv' ); ?>
				<button type="submit" class="button"><?php esc_html_e( 'Download Payout Summary CSV', 'pmpro-affiliates' ); ?></button>
			</form>
			<?php
			// Check if any of the recent payouts are PayPal payouts.
			$has_paypal = false;
			foreach ( $recent_payout_ids as $pid ) {
				$p = pmpro_affiliates_get_payout( $pid );
				if ( $p && 'paypal' === $p->payout_method && ! empty( $p->payout_account ) ) {
					$has_paypal = true;
					break;
				}
			}
			if ( $has_paypal ) :
			?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" style="display:inline; margin-left:8px;">
				<input type="hidden" name="action" value="pmpro_affiliates_payout_csv" />
				<input type="hidden" name="format" value="paypal" />
				<input type="hidden" name="payout_ids" value="<?php echo esc_attr( implode( ',', array_map( 'intval', $recent_payout_ids ) ) ); ?>" />
				<?php wp_nonce_field( 'pmpro_affiliates_payout_csv' ); ?>
				<button type="submit" class="button"><?php esc_html_e( 'Download PayPal Payouts CSV', 'pmpro-affiliates' ); ?></button>
			</form>
			<?php endif; ?>
		<?php endif; ?>
	</div>
<?php endif; ?>

<?php if ( empty( $payouts ) && empty( $pmpro_msg ) ) : ?>
	<p><?php esc_html_e( 'No payouts have been processed yet.', 'pmpro-affiliates' ); ?> <a href="admin.php?page=pmpro-affiliates-payouts&action=process"><?php esc_html_e( 'Process your first payout now', 'pmpro-affiliates' ); ?></a>.</p>
<?php elseif ( ! empty( $payouts ) ) : ?>

<form method="post" id="pmpro-affiliates-payouts-form" action="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-affiliates-payouts' ) ); ?>">
	<?php wp_nonce_field( 'pmpro_affiliates_bulk_delete_payouts' ); ?>

	<div class="tablenav top">
		<div class="alignleft actions bulkactions">
			<select name="bulk_action">
				<option value=""><?php esc_html_e( 'Bulk Actions', 'pmpro-affiliates' ); ?></option>
				<option value="bulk_delete"><?php esc_html_e( 'Delete', 'pmpro-affiliates' ); ?></option>
			</select>
			<button type="submit" class="button action"><?php esc_html_e( 'Apply', 'pmpro-affiliates' ); ?></button>
		</div>
		<?php if ( $total > $per_page ) : ?>
		<div class="tablenav-pages">
			<span class="displaying-num">
				<?php echo esc_html( sprintf(
					/* translators: %d: total number of payouts */
					_n( '%d payout', '%d payouts', $total, 'pmpro-affiliates' ),
					$total
				) ); ?>
			</span>
		</div>
		<?php endif; ?>
		<br class="clear" />
	</div>

	<table class="wp-list-table widefat striped">
		<thead>
			<tr>
				<td class="manage-column column-cb check-column"><input type="checkbox" id="cb-select-all" /></td>
				<th><?php esc_html_e( 'ID', 'pmpro-affiliates' ); ?></th>
				<th><?php esc_html_e( 'Affiliate', 'pmpro-affiliates' ); ?></th>
				<th><?php esc_html_e( 'Amount', 'pmpro-affiliates' ); ?></th>
				<th><?php esc_html_e( 'Method', 'pmpro-affiliates' ); ?></th>
				<th><?php esc_html_e( 'Account Used', 'pmpro-affiliates' ); ?></th>
				<th><?php esc_html_e( '# Orders', 'pmpro-affiliates' ); ?></th>
				<th><?php esc_html_e( 'Date Range', 'pmpro-affiliates' ); ?></th>
				<th><?php esc_html_e( 'Created By', 'pmpro-affiliates' ); ?></th>
				<th><?php esc_html_e( 'Date', 'pmpro-affiliates' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php
			$method_labels = array(
				'paypal' => __( 'PayPal', 'pmpro-affiliates' ),
				'venmo'  => __( 'Venmo', 'pmpro-affiliates' ),
				'check'  => __( 'Check', 'pmpro-affiliates' ),
				'wire'   => __( 'Wire', 'pmpro-affiliates' ),
				'other'  => __( 'Other', 'pmpro-affiliates' ),
			);
			foreach ( $payouts as $payout ) :
				$order_ids    = array_filter( array_map( 'intval', explode( ',', $payout->order_ids ) ) );
				$order_count  = count( $order_ids );
				$created_user = get_userdata( (int) $payout->created_by );
				$created_name = $created_user ? $created_user->display_name : __( 'Unknown', 'pmpro-affiliates' );
				$method_label = isset( $method_labels[ $payout->payout_method ] ) ? $method_labels[ $payout->payout_method ] : esc_html( $payout->payout_method );
				$delete_url   = wp_nonce_url(
					add_query_arg( array(
						'page'      => 'pmpro-affiliates-payouts',
						'action'    => 'delete',
						'payout_id' => $payout->id,
					), admin_url( 'admin.php' ) ),
					'pmpro_affiliates_delete_payout_' . $payout->id
				);
				$csv_url    = wp_nonce_url(
					add_query_arg( array(
						'action'     => 'pmpro_affiliates_payout_csv',
						'format'     => 'summary',
						'payout_ids' => $payout->id,
					), admin_url( 'admin-ajax.php' ) ),
					'pmpro_affiliates_payout_csv'
				);
				$date_range = pmpro_affiliates_format_payout_date_range( $payout );
			?>
			<tr id="payout-row-<?php echo (int) $payout->id; ?>">
				<th class="check-column">
					<input type="checkbox" name="payout_ids[]" value="<?php echo (int) $payout->id; ?>" />
				</th>
				<td><strong><?php echo (int) $payout->id; ?></strong></td>
				<td>
					<?php if ( ! empty( $payout->affiliate_name ) ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-affiliates&edit=' . (int) $payout->affiliate_id ) ); ?>">
							<?php echo esc_html( stripslashes( $payout->affiliate_name ) ); ?>
						</a><br />
						<small><?php echo esc_html( $payout->affiliate_code ); ?></small>
					<?php else : ?>
						<?php echo esc_html( sprintf( __( 'ID: %d', 'pmpro-affiliates' ), $payout->affiliate_id ) ); ?>
					<?php endif; ?>
				</td>
				<td><?php echo esc_html( pmpro_formatPrice( $payout->amount ) ); ?></td>
				<td><?php echo esc_html( $method_label ); ?></td>
				<td><?php echo esc_html( $payout->payout_account ); ?></td>
				<td>
					<?php echo (int) $order_count; ?>
					<div class="row-actions">
						<span class="view">
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-affiliates&report=' . (int) $payout->affiliate_id . '&payout_id=' . (int) $payout->id ) ); ?>">
								<?php esc_html_e( 'View Orders', 'pmpro-affiliates' ); ?>
							</a>
						</span>
					</div>
				</td>
				<td><?php echo esc_html( $date_range ); ?></td>
				<td><?php echo esc_html( $created_name ); ?></td>
				<td>
					<?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $payout->date ) ) ); ?>
					<div class="row-actions">
						<span class="csv">
							<a href="<?php echo esc_url( $csv_url ); ?>"><?php esc_html_e( 'CSV', 'pmpro-affiliates' ); ?></a>
						</span>
						|
						<span class="delete">
							<a href="javascript:askfirst('<?php echo esc_js( sprintf( __( 'Deleting this payout will unmark %d order(s) as paid. Are you sure?', 'pmpro-affiliates' ), $order_count ) ); ?>', '<?php echo esc_js( $delete_url ); ?>')">
								<?php esc_html_e( 'Delete', 'pmpro-affiliates' ); ?>
							</a>
						</span>
					</div>
				</td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<div class="tablenav bottom">
		<div class="alignleft actions bulkactions">
			<select name="bulk_action2">
				<option value=""><?php esc_html_e( 'Bulk Actions', 'pmpro-affiliates' ); ?></option>
				<option value="bulk_delete"><?php esc_html_e( 'Delete', 'pmpro-affiliates' ); ?></option>
			</select>
			<button type="submit" class="button action"><?php esc_html_e( 'Apply', 'pmpro-affiliates' ); ?></button>
		</div>
		<?php
		pmpro_affiliates_display_pagination( $paged, $total, $per_page );
		?>
		<br class="clear" />
	</div>
</form>

<script>
jQuery(document).ready(function($) {
    // Checkbox select all.
    $('#cb-select-all').on('change', function() {
        $('input[name="payout_ids[]"]').prop('checked', this.checked);
    });
});
</script>

<?php endif; ?>

<?php require_once PMPRO_DIR . '/adminpages/admin_footer.php'; ?>
