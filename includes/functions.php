<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get affiliate orders from the database.
 *
 * @since 0.8
 *
 * @param int|string $affiliate_id The affiliate ID or 'all' for all affiliates.
 * @param array      $args {
 *     Optional. Arguments for the query.
 *
 *     @type int $limit  Number of orders to return. Default 0 (no limit).
 *     @type int $offset Number of orders to offset. Default 0.
 *     @type int $paged  Page number. Default 1. Used with $limit to calculate offset.
 * }
 * @return array The affiliate orders.
 */
function pmpro_affiliates_get_orders( $affiliate_id = 'all', $args = array() ) {
	global $wpdb;

	$defaults = array(
		'limit'     => 0,
		'offset'    => 0,
		'paged'     => 1,
		'order_ids' => array(), // Restrict to specific order IDs (e.g. from a payout).
	);

	$args = wp_parse_args( $args, $defaults );

	$sql_query =
		"SELECT o.id as order_id, o.code as order_code, a.id as affiliate_id, a.code, a.commissionrate, o.affiliate_subid as subid, a.name, u.ID as user_id, u.user_login, o.membership_id, UNIX_TIMESTAMP(o.timestamp) as timestamp, " . esc_sql( 'o.' . pmpro_affiliates_get_commission_calculation_source() ) . " as total, o.status, om.meta_value as affiliate_paid, pm.meta_value as affiliate_payout_id
		FROM $wpdb->pmpro_membership_orders o
		LEFT JOIN $wpdb->pmpro_affiliates a
		ON o.affiliate_id = a.id
		LEFT JOIN $wpdb->users u
		ON o.user_id = u.ID
		LEFT JOIN $wpdb->pmpro_membership_ordermeta om
		ON o.id = om.pmpro_membership_order_id
		AND om.meta_key = 'pmpro_affiliate_paid'
		LEFT JOIN $wpdb->pmpro_membership_ordermeta pm
		ON o.id = pm.pmpro_membership_order_id
		AND pm.meta_key = 'pmpro_affiliate_payout_id'
		WHERE o.affiliate_id <> ''
		AND o.affiliate_id IS NOT NULL
		AND o.affiliate_id <> 0
		AND o.status NOT IN('pending', 'error', 'refunded', 'refund', 'token', 'review')";

	if ( 'all' !== $affiliate_id ) {
		$sql_query .= $wpdb->prepare( " AND a.id = %d", (int) $affiliate_id );
	}

	if ( ! empty( $args['order_ids'] ) ) {
		$clean_ids = implode( ',', array_map( 'intval', $args['order_ids'] ) );
		$sql_query .= " AND o.id IN ($clean_ids)";
	}

	$sql_query .= " ORDER BY o.timestamp DESC";

	$limit = (int) $args['limit'];
	if ( $limit > 0 ) {
		if ( ! empty( $args['paged'] ) && $args['paged'] > 1 ) {
			$offset = ( (int) $args['paged'] - 1 ) * $limit;
		} else {
			$offset = (int) $args['offset'];
		}
		$sql_query .= $wpdb->prepare( " LIMIT %d, %d", $offset, $limit );
	}

	return $wpdb->get_results( $sql_query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Static SQL; column whitelisted to total/subtotal; dynamic parts built with $wpdb->prepare().
}

/**
 * Get the total count of affiliate orders.
 *
 * @since 0.8
 *
 * @param int|string $affiliate_id The affiliate ID or 'all' for all affiliates.
 * @return int The total number of affiliate orders.
 */
function pmpro_affiliates_get_orders_count( $affiliate_id = 'all', $args = array() ) {
	global $wpdb;

	$defaults = array(
		'order_ids' => array(),
	);
	$args = wp_parse_args( $args, $defaults );

	$sql_query =
		"SELECT COUNT(DISTINCT o.id)
		FROM $wpdb->pmpro_membership_orders o
		LEFT JOIN $wpdb->pmpro_affiliates a
		ON o.affiliate_id = a.id
		WHERE o.affiliate_id <> ''
		AND o.affiliate_id IS NOT NULL
		AND o.affiliate_id <> 0
		AND o.status NOT IN('pending', 'error', 'refunded', 'refund', 'token', 'review')";

	if ( 'all' !== $affiliate_id ) {
		$sql_query .= $wpdb->prepare( " AND a.id = %d", (int) $affiliate_id );
	}

	if ( ! empty( $args['order_ids'] ) ) {
		$clean_ids = implode( ',', array_map( 'intval', $args['order_ids'] ) );
		$sql_query .= " AND o.id IN ($clean_ids)";
	}

	return (int) $wpdb->get_var( $sql_query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Static SQL; dynamic part built with $wpdb->prepare() or intval().
}

/**
 * Display the affiliate orders table.
 *
 * @since 0.8
 *
 * @param array $args {
 *     Optional. Arguments for displaying the affiliate orders table.
 *
 *     @type int|string $affiliate_id   The affiliate ID or 'all' for all affiliates. Default 'all'.
 *     @type array      $orders         The affiliate orders to display. If not provided, will be fetched from DB.
 *     @type bool       $show_code      Whether to show the affiliate code column. Default true.
 *     @type bool       $show_order     Whether to show the order column. Default true.
 *     @type bool       $show_name      Whether to show the affiliate name column. Default true.
 *     @type bool       $show_member    Whether to show the member column. Default true.
 *     @type bool       $show_level     Whether to show the membership level column. Default true.
 *     @type bool       $show_date      Whether to show the date column. Default true.
 *     @type bool       $show_commission_pct Whether to show the commission percentage column. Default true.
 *     @type bool       $show_commission_earned Whether to show the commission earned column. Default true.
 *     @type bool       $show_order_total Whether to show the order total column. Default true.
 *     @type bool       $show_status    Whether to show the status column. Default true.
 *     @type string     $empty_message  Message to show when no orders. Default ''.
 *     @type string     $table_class    CSS class for the table. Default 'widefat striped fixed'.
 *     @type int        $limit          Number of orders per page. Default 0 (no pagination).
 *     @type int        $paged          Current page number. Default 1.
 *     @type string     $page_var       Query var name for pagination. Default 'paged'.
 *     @type bool       $show_pagination Whether to show pagination. Default true.
 * }
 */
function pmpro_affiliates_display_orders_table( $args = array() ) {
	$defaults = array(
		'affiliate_id'           => 'all',
		'orders'                 => array(),
		'order_ids'              => array(), // Filter to specific order IDs (e.g. from a payout).
		'show_code'              => true,
		'show_order'             => true,
		'show_name'              => true,
		'show_member'            => true,
		'show_level'             => true,
		'show_date'              => true,
		'show_commission_pct'    => true,
		'show_commission_earned' => true,
		'show_order_total'       => true,
		'show_status'            => true,
		'empty_message'          => '',
		'table_class'            => 'widefat striped fixed',
		'limit'                  => 0,
		'paged'                  => 1,
		'page_var'               => 'paged',
		'show_pagination'        => true,
	);

	$args = wp_parse_args( $args, $defaults );

	$total_orders = 0;
	$orders       = $args['orders'];

	$query_args = array(
		'order_ids' => $args['order_ids'],
	);

	// If limit is set, get total count and paginated results.
	if ( $args['limit'] > 0 && empty( $orders ) ) {
		$total_orders = pmpro_affiliates_get_orders_count( $args['affiliate_id'], $query_args );
		$orders = pmpro_affiliates_get_orders( $args['affiliate_id'], array_merge( $query_args, array(
			'limit' => $args['limit'],
			'paged' => $args['paged'],
		) ) );
	} elseif ( empty( $orders ) ) {
		$orders = pmpro_affiliates_get_orders( $args['affiliate_id'], $query_args );
	}

	// Show an empty message if there are no orders to display.
	if ( empty( $orders ) ) {
		if ( ! empty( $args['empty_message'] ) ) {
			echo '<p>' . esc_html( $args['empty_message'] ) . '</p>';
		}
		return;
	}
	?>
	<table class="<?php echo esc_attr( $args['table_class'] ); ?>">
		<thead>
			<tr>
				<?php if ( $args['show_code'] ) : ?>
					<th><?php esc_html_e( 'Code', 'pmpro-affiliates' ); ?></th>
				<?php endif; ?>
				<?php if ( $args['show_order'] ) : ?>
					<th><?php esc_html_e( 'Order', 'pmpro-affiliates' ); ?></th>
				<?php endif; ?>
				<?php if ( $args['show_name'] ) : ?>
					<th><?php esc_html_e( 'Name', 'pmpro-affiliates' ); ?></th>
				<?php endif; ?>
				<?php if ( $args['show_member'] ) : ?>
					<th><?php esc_html_e( 'Member', 'pmpro-affiliates' ); ?></th>
				<?php endif; ?>
				<?php if ( $args['show_level'] ) : ?>
					<th><?php esc_html_e( 'Membership Level', 'pmpro-affiliates' ); ?></th>
				<?php endif; ?>
				<?php if ( $args['show_date'] ) : ?>
					<th><?php esc_html_e( 'Date', 'pmpro-affiliates' ); ?></th>
				<?php endif; ?>
				<?php if ( $args['show_commission_pct'] ) : ?>
					<th><?php esc_html_e( 'Commission %', 'pmpro-affiliates' ); ?></th>
				<?php endif; ?>
				<?php if ( $args['show_commission_earned'] ) : ?>
					<th><?php esc_html_e( 'Commission Earned', 'pmpro-affiliates' ); ?></th>
				<?php endif; ?>
				<?php if ( $args['show_order_total'] ) : ?>
					<th><?php esc_html_e( 'Order Total', 'pmpro-affiliates' ); ?></th>
				<?php endif; ?>
				<?php if ( $args['show_status'] ) : ?>
					<th><?php esc_html_e( 'Status', 'pmpro-affiliates' ); ?></th>
				<?php endif; ?>
				<?php do_action( 'pmpro_affiliate_report_extra_cols_header' ); ?>
			</tr>
		</thead>
		<tbody>
			<?php
			foreach ( $orders as $order ) {
				$level = pmpro_getLevel( $order->membership_id );
				$affiliate_paid = $order->affiliate_paid;
				
				if ( ! empty( $order->affiliate_payout_id ) ) {
					// Orders in a payout are reversed by deleting the payout, not individually.
					$affiliate_paid = sprintf(
						/* translators: %s: link to the payout */
						esc_html__( 'Paid (%s)', 'pmpro-affiliates' ),
						'<a href="' . esc_url( admin_url( 'admin.php?page=pmpro-affiliates&report=' . (int) $order->affiliate_id . '&payout_id=' . (int) $order->affiliate_payout_id ) ) . '">' . esc_html( sprintf( __( 'Payout #%d', 'pmpro-affiliates' ), (int) $order->affiliate_payout_id ) ) . '</a>'
					);
				} elseif ( $affiliate_paid === '1' ) {
					$nonce = wp_create_nonce( 'pmpro_affiliates_reset_paid_status' );
					$affiliate_paid = esc_html__( 'Paid', 'pmpro-affiliates' );
					$affiliate_paid .= ' [<a class="pmpro_affiliates_reset_paid_status" href="javascript:void(0)" order_id="' . esc_attr( $order->order_id ) . '" _wpnonce="' . esc_attr( $nonce ) . '" title="' . esc_html__( 'Reset Payment Status', 'pmpro-affiliates' ) . '" >x</a>]';
				} else {
					$nonce = wp_create_nonce( 'pmpro_affiliates_mark_as_paid' );
					$affiliate_paid = '<a class="pmpro_affiliates_mark_as_paid" href="javascript:void(0)" order_id="' . esc_attr( $order->order_id ) . '" _wpnonce="' . esc_attr( $nonce ) . '" title="' . esc_html__( 'Mark as Paid', 'pmpro-affiliates' ) . '" >' . esc_html__( 'Mark as Paid', 'pmpro-affiliates' ) . '</a>';
				}
				?>
				<tr>
					<?php if ( $args['show_code'] ) : ?>
						<td>
							<?php
							echo "<a href='" . esc_url( get_admin_url( null, '/admin.php?page=pmpro-affiliates&edit=' . (int) $order->affiliate_id ) ) . "'>" . esc_html( $order->code ) . "</a>";
							if ( $order->subid ) {
								echo '<br><span class="pmpro-affiliates-sub-id-report" style="font-size:12px;">';
								echo '<strong>' . esc_html__( 'Sub-ID', 'pmpro-affiliates' ) . ':</strong> ' . esc_html( $order->subid );
								echo '</span>';
							}
							?>
						</td>
					<?php endif; ?>
					<?php if ( $args['show_order'] ) : ?>
						<td>
							<?php
							echo "<a href='" . esc_url( get_admin_url( null, '/admin.php?page=pmpro-orders&order=' . (int) $order->order_id . '&id=' . (int) $order->order_id ) ) . "'>" . esc_html( $order->order_code ) . "</a>";
							?>
						</td>
					<?php endif; ?>
					<?php if ( $args['show_name'] ) : ?>
						<td><?php echo ! empty( $order->name ) ? esc_html( stripslashes( $order->name ) ) : ''; ?></td>
					<?php endif; ?>
					<?php if ( $args['show_member'] ) : ?>
						<td>
							<?php
							if ( ! empty( $order->user_id ) && $order->user_id > 0 ) {
								if ( ! empty( get_user_by( 'id', $order->user_id ) ) ) {
									?>
									<a href="<?php echo esc_url( get_edit_user_link( $order->user_id ) ); ?>"><?php echo esc_html( $order->user_login ); ?></a>
									<?php
								} else {
									echo esc_html( $order->user_login );
								}
							} else {
								?>[<?php esc_html_e( 'deleted', 'pmpro-affiliates' ); ?>]<?php
							}
							?>
						</td>
					<?php endif; ?>
					<?php if ( $args['show_level'] ) : ?>
						<td>
							<?php
							if ( ! empty( $level ) ) {
								echo esc_html( $level->name );
							} elseif ( $order->membership_id > 0 ) {
								?>[<?php esc_html_e( 'deleted', 'pmpro-affiliates' ); ?>]<?php
							} else {
								echo '&#8212;';
							}
							?>
						</td>
					<?php endif; ?>
					<?php if ( $args['show_date'] ) : ?>
						<td><?php echo esc_html( date_i18n( get_option( 'date_format' ), $order->timestamp ) ); ?></td>
					<?php endif; ?>
					<?php if ( $args['show_commission_pct'] ) : ?>
						<td><?php echo esc_html( $order->commissionrate * 100 ); ?>%</td>
					<?php endif; ?>
					<?php if ( $args['show_commission_earned'] ) : ?>
						<td><?php echo esc_html( pmpro_formatPrice( $order->total * $order->commissionrate ) ); ?></td>
					<?php endif; ?>
					<?php if ( $args['show_order_total'] ) : ?>
						<td><?php echo esc_html( pmpro_formatPrice( $order->total ) ); ?></td>
					<?php endif; ?>
					<?php if ( $args['show_status'] ) : ?>
						<td><?php echo '<span class="pmpro_affiliate_paid_status" id="order_' . esc_attr( $order->order_id ) . '">' . $affiliate_paid . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $affiliate_paid is built from escaped strings above. ?></td>
					<?php endif; ?>
					<?php do_action( 'pmpro_affiliate_report_extra_cols_body', $order ); ?>
				</tr>
				<?php
			}
			?>
		</tbody>
	</table>
	<?php

	// Show pagination if enabled, limit is set, and there are more orders than the limit.
	if ( $args['show_pagination'] && $args['limit'] > 0 && $total_orders > $args['limit'] ) {
		pmpro_affiliates_display_pagination( $args['paged'], $total_orders, $args['limit'], $args['page_var'] );
	}
}

/**
 * Get all unpaid commissions grouped by affiliate, optionally filtered by affiliate and date range.
 *
 * @since TBD
 *
 * @param int    $affiliate_id The affiliate ID, or 0 for all affiliates.
 * @param string $date_start   Start date string (YYYY-MM-DD), or empty for no limit.
 * @param string $date_end     End date string (YYYY-MM-DD), or empty for no limit.
 * @param float  $minimum      Minimum commission amount to include. Default 0.
 * @param array  $order_ids    Limit to these order IDs (e.g. the set shown in a preview). Default empty (no limit).
 * @return array Keyed by affiliate_id => [ 'affiliate' => obj, 'order_ids' => [], 'amount' => float, 'date_first' => string, 'date_last' => string ] Order dates are GMT.
 */
function pmpro_affiliates_get_unpaid_commissions_for_payout( $affiliate_id = 0, $date_start = '', $date_end = '', $minimum = 0.00, $order_ids = array() ) {
	global $wpdb;

	$commission_source = pmpro_affiliates_get_commission_calculation_source();

	$sql = "SELECT
				a.id            AS affiliate_id,
				a.code,
				a.name,
				a.commissionrate,
				a.payout_email,
				a.payout_method,
				o.id            AS order_id,
				o.timestamp     AS order_date,
				o.{$commission_source} AS order_total
			FROM {$wpdb->pmpro_membership_orders} o
			JOIN {$wpdb->pmpro_affiliates} a
				ON o.affiliate_id = a.id
			LEFT JOIN {$wpdb->pmpro_membership_ordermeta} om
				ON o.id = om.pmpro_membership_order_id
				AND om.meta_key = 'pmpro_affiliate_paid'
			LEFT JOIN {$wpdb->pmpro_membership_ordermeta} pm
				ON o.id = pm.pmpro_membership_order_id
				AND pm.meta_key = 'pmpro_affiliate_payout_id'
			WHERE o.affiliate_id > 0
			AND o.status NOT IN ('pending', 'error', 'refunded', 'refund', 'token', 'review')
			AND om.meta_value IS NULL
			AND pm.meta_value IS NULL
			AND a.enabled = 1";

	if ( $affiliate_id > 0 ) {
		$sql .= $wpdb->prepare( ' AND a.id = %d', (int) $affiliate_id );
	}

	if ( ! empty( $order_ids ) ) {
		$clean_ids = implode( ',', array_map( 'intval', $order_ids ) );
		$sql      .= " AND o.id IN ($clean_ids)";
	}

	// Order timestamps are stored in GMT; entered dates are in the site's timezone.
	if ( ! empty( $date_start ) ) {
		$sql .= $wpdb->prepare( ' AND o.timestamp >= %s', get_gmt_from_date( $date_start . ' 00:00:00' ) );
	}

	if ( ! empty( $date_end ) ) {
		$sql .= $wpdb->prepare( ' AND o.timestamp <= %s', get_gmt_from_date( $date_end . ' 23:59:59' ) );
	}

	$sql .= ' ORDER BY a.id, o.timestamp';

	$rows = $wpdb->get_results( $sql );

	if ( empty( $rows ) ) {
		return array();
	}

	// Group by affiliate_id.
	$result = array();
	foreach ( $rows as $row ) {
		$aid = (int) $row->affiliate_id;
		if ( ! isset( $result[ $aid ] ) ) {
			$result[ $aid ] = array(
				'affiliate' => (object) array(
					'id'             => $aid,
					'code'           => $row->code,
					'name'           => $row->name,
					'commissionrate' => (float) $row->commissionrate,
					'payout_email'   => $row->payout_email,
					'payout_method'  => $row->payout_method,
				),
				'order_ids'  => array(),
				'amount'     => 0.0,
				'date_first' => $row->order_date,
				'date_last'  => $row->order_date,
			);
		}
		$result[ $aid ]['order_ids'][] = (int) $row->order_id;
		$result[ $aid ]['date_last']   = $row->order_date;
		$result[ $aid ]['amount']      = round(
			$result[ $aid ]['amount'] + ( (float) $row->order_total * (float) $row->commissionrate ),
			2
		);
	}

	// Apply minimum filter.
	if ( $minimum > 0 ) {
		foreach ( $result as $aid => $data ) {
			if ( $data['amount'] < (float) $minimum ) {
				unset( $result[ $aid ] );
			}
		}
	}

	return $result;
}

/**
 * Create a payout record and mark all included orders as paid.
 *
 * @since TBD
 *
 * @param int    $affiliate_id   The affiliate ID.
 * @param array  $order_ids      Array of order IDs included in this payout.
 * @param float  $amount         Total commission amount.
 * @param string $payout_method  Payment method (paypal, venmo, check, wire, other).
 * @param string $payout_account Email/handle/name used for payout.
 * @param string $date_start     Date range start (YYYY-MM-DD).
 * @param string $date_end       Date range end (YYYY-MM-DD).
 * @param string $notes          Optional notes.
 * @return int|WP_Error Payout ID on success, WP_Error on failure.
 */
function pmpro_affiliates_create_payout( $affiliate_id, $order_ids, $amount, $payout_method, $payout_account, $date_start, $date_end, $notes = '' ) {
	global $wpdb;

	if ( empty( $wpdb->pmpro_affiliate_payouts ) ) {
		$wpdb->pmpro_affiliate_payouts = $wpdb->prefix . 'pmpro_affiliate_payouts';
	}

	$order_ids_str = implode( ',', array_map( 'intval', $order_ids ) );

	$inserted = $wpdb->insert(
		$wpdb->pmpro_affiliate_payouts,
		array(
			'affiliate_id'   => (int) $affiliate_id,
			'order_ids'      => $order_ids_str,
			'amount'         => round( (float) $amount, 2 ),
			'payout_method'  => sanitize_text_field( $payout_method ),
			'payout_account' => sanitize_text_field( $payout_account ),
			'status'         => 'paid',
			'created_by'     => (int) get_current_user_id(),
			'date_start'     => sanitize_text_field( $date_start ),
			'date_end'       => sanitize_text_field( $date_end ),
			'date'           => current_time( 'mysql' ),
			'notes'          => sanitize_textarea_field( $notes ),
		),
		array( '%d', '%s', '%f', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s' )
	);

	if ( false === $inserted ) {
		return new WP_Error( 'payout_creation_failed', __( 'Failed to create payout record.', 'pmpro-affiliates' ) );
	}

	$payout_id = (int) $wpdb->insert_id;

	// Mark all included orders as paid and link them to this payout.
	foreach ( $order_ids as $order_id ) {
		$order_id = (int) $order_id;
		update_pmpro_membership_order_meta( $order_id, 'pmpro_affiliate_paid', 1 );
		update_pmpro_membership_order_meta( $order_id, 'pmpro_affiliate_payout_id', $payout_id );
	}

	return $payout_id;
}

/**
 * Get a single payout record by ID.
 *
 * @since TBD
 *
 * @param int $payout_id The payout ID.
 * @return object|null Payout record or null if not found.
 */
function pmpro_affiliates_get_payout( $payout_id ) {
	global $wpdb;

	if ( empty( $wpdb->pmpro_affiliate_payouts ) ) {
		$wpdb->pmpro_affiliate_payouts = $wpdb->prefix . 'pmpro_affiliate_payouts';
	}

	return $wpdb->get_row(
		$wpdb->prepare( "SELECT * FROM {$wpdb->pmpro_affiliate_payouts} WHERE id = %d", (int) $payout_id )
	);
}

/**
 * Get payout records, with optional filters.
 *
 * @since TBD
 *
 * @param array $args {
 *     Optional. Query arguments.
 *
 *     @type int    $affiliate_id Limit to a specific affiliate ID. Default 0 (all).
 *     @type string $status       Limit to a specific status. Default '' (all).
 *     @type string $date_start   Payouts on or after this date (YYYY-MM-DD).
 *     @type string $date_end     Payouts on or before this date (YYYY-MM-DD).
 *     @type int    $limit        Number of records. Default 0 (no limit).
 *     @type int    $offset       Offset for pagination. Default 0.
 *     @type string $orderby      Column to sort by. Default 'date'.
 *     @type string $order        ASC or DESC. Default 'DESC'.
 *     @type bool   $count        Return total count instead of records. Default false.
 * }
 * @return array|int Array of payout objects, or count integer if $args['count'] is true.
 */
function pmpro_affiliates_get_payouts( $args = array() ) {
	global $wpdb;

	if ( empty( $wpdb->pmpro_affiliate_payouts ) ) {
		$wpdb->pmpro_affiliate_payouts = $wpdb->prefix . 'pmpro_affiliate_payouts';
	}

	$defaults = array(
		'affiliate_id' => 0,
		'status'       => '',
		'date_start'   => '',
		'date_end'     => '',
		'limit'        => 0,
		'offset'       => 0,
		'orderby'      => 'date',
		'order'        => 'DESC',
		'count'        => false,
	);

	$args = wp_parse_args( $args, $defaults );

	if ( $args['count'] ) {
		$select = 'COUNT(*)';
	} else {
		$select = 'p.*, a.name AS affiliate_name, a.code AS affiliate_code';
	}

	$sql = "SELECT {$select}
			FROM {$wpdb->pmpro_affiliate_payouts} p
			LEFT JOIN {$wpdb->pmpro_affiliates} a ON p.affiliate_id = a.id
			WHERE 1=1";

	if ( ! empty( $args['affiliate_id'] ) ) {
		$sql .= $wpdb->prepare( ' AND p.affiliate_id = %d', (int) $args['affiliate_id'] );
	}

	if ( ! empty( $args['status'] ) ) {
		$sql .= $wpdb->prepare( ' AND p.status = %s', $args['status'] );
	}

	if ( ! empty( $args['date_start'] ) ) {
		$sql .= $wpdb->prepare( ' AND p.date >= %s', $args['date_start'] . ' 00:00:00' );
	}

	if ( ! empty( $args['date_end'] ) ) {
		$sql .= $wpdb->prepare( ' AND p.date <= %s', $args['date_end'] . ' 23:59:59' );
	}

	if ( $args['count'] ) {
		return (int) $wpdb->get_var( $sql );
	}

	$allowed_orderby = array( 'id', 'date', 'amount', 'affiliate_id', 'status' );
	$orderby         = in_array( $args['orderby'], $allowed_orderby ) ? $args['orderby'] : 'date';
	$order           = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';
	$sql            .= " ORDER BY p.{$orderby} {$order}";

	if ( (int) $args['limit'] > 0 ) {
		$sql .= $wpdb->prepare( ' LIMIT %d OFFSET %d', (int) $args['limit'], (int) $args['offset'] );
	}

	return $wpdb->get_results( $sql );
}

/**
 * Format a payout's date range for display.
 *
 * @since TBD
 *
 * @param object $payout The payout record.
 * @return string The formatted date range, or an em dash if no range was stored.
 */
function pmpro_affiliates_format_payout_date_range( $payout ) {
	$dates = array();
	foreach ( array( $payout->date_start, $payout->date_end ) as $date ) {
		// Payouts created before date ranges were required stored zero dates.
		if ( empty( $date ) || 0 === strpos( $date, '0000-00-00' ) ) {
			return '&#8212;';
		}
		$dates[] = date_i18n( get_option( 'date_format' ), strtotime( $date ) );
	}

	return implode( ' &ndash; ', $dates );
}

/**
 * Sanitize a payout method against the list of supported methods.
 *
 * @since TBD
 *
 * @param string $method The payout method to check.
 * @return string The method if supported, otherwise an empty string.
 */
function pmpro_affiliates_sanitize_payout_method( $method ) {
	$allowed_methods = array( 'paypal', 'venmo', 'check', 'wire', 'other' );
	return in_array( $method, $allowed_methods, true ) ? $method : '';
}

/**
 * Check whether a payout account can be used with a payout method.
 *
 * @since TBD
 *
 * @param string $method  The payout method.
 * @param string $account The payout account (email, handle, payee name, etc.).
 * @return string An error message if the account can't be used, or an empty string if it can.
 */
function pmpro_affiliates_get_payout_account_error( $method, $account ) {
	if ( '' === trim( (string) $account ) ) {
		return __( 'No payout account set', 'pmpro-affiliates' );
	}

	if ( 'paypal' === $method && ! is_email( $account ) ) {
		return __( 'Payout account is not a valid PayPal email', 'pmpro-affiliates' );
	}

	return '';
}

/**
 * Escape a value for a CSV cell so spreadsheet apps don't run it as a formula.
 *
 * @since TBD
 *
 * @param string $value The cell value.
 * @return string The escaped value.
 */
function pmpro_affiliates_escape_csv_cell( $value ) {
	$value = (string) $value;
	if ( '' !== $value && in_array( $value[0], array( '=', '+', '-', '@', "\t", "\r" ), true ) ) {
		$value = "'" . $value;
	}
	return $value;
}

/**
 * Delete a payout record and unmark all its orders as paid (reversal).
 *
 * @since TBD
 *
 * @param int $payout_id The payout ID.
 * @return bool True on success, false on failure.
 */
function pmpro_affiliates_delete_payout( $payout_id ) {
	global $wpdb;

	if ( empty( $wpdb->pmpro_affiliate_payouts ) ) {
		$wpdb->pmpro_affiliate_payouts = $wpdb->prefix . 'pmpro_affiliate_payouts';
	}

	$payout = pmpro_affiliates_get_payout( $payout_id );
	if ( empty( $payout ) ) {
		return false;
	}

	// Unmark all orders that were part of this payout.
	$order_ids = array_filter( array_map( 'intval', explode( ',', $payout->order_ids ) ) );
	foreach ( $order_ids as $order_id ) {
		delete_pmpro_membership_order_meta( $order_id, 'pmpro_affiliate_paid', null );
		delete_pmpro_membership_order_meta( $order_id, 'pmpro_affiliate_payout_id', null );
	}

	return (bool) $wpdb->delete(
		$wpdb->pmpro_affiliate_payouts,
		array( 'id' => (int) $payout_id ),
		array( '%d' )
	);
}

/**
 * Display WP List Table style pagination.
 *
 * @since 0.8
 *
 * @param int    $current_page Current page number.
 * @param int    $total_items  Total number of items.
 * @param int    $per_page     Items per page.
 * @param string $page_var     Query var name for pagination.
 */
function pmpro_affiliates_display_pagination( $current_page, $total_items, $per_page, $page_var = 'paged' ) {
	$total_pages = ceil( $total_items / $per_page );
	
	// Only one page, no need for pagination.
	if ( $total_pages <= 1 ) {
		return;
	}

	// Build the base URL from the current request, removing the pagination query arg if present.
	$base_url = remove_query_arg( $page_var ); // Remove the paged variable from the URL if present.
	// Build pagination URLs.
	$first_page_url = add_query_arg( $page_var, 1, $base_url );
	$prev_page_url  = add_query_arg( $page_var, max( 1, $current_page - 1 ), $base_url );
	$next_page_url  = add_query_arg( $page_var, min( $total_pages, $current_page + 1 ), $base_url );
	$last_page_url  = add_query_arg( $page_var, $total_pages, $base_url );
	?>
	<div class="tablenav bottom">
		<div class="tablenav-pages">
			<span class="pagination-links">
				<?php if ( $current_page == 1 ) : ?>
					<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&laquo;</span>
					<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&lsaquo;</span>
				<?php else : ?>
					<a class="first-page button" href="<?php echo esc_url( $first_page_url ); ?>">
						<span class="screen-reader-text"><?php esc_html_e( 'First page', 'pmpro-affiliates' ); ?></span>
						<span aria-hidden="true">&laquo;</span>
					</a>
					<a class="prev-page button" href="<?php echo esc_url( $prev_page_url ); ?>">
						<span class="screen-reader-text"><?php esc_html_e( 'Previous page', 'pmpro-affiliates' ); ?></span>
						<span aria-hidden="true">&lsaquo;</span>
					</a>
				<?php endif; ?>

				<span class="screen-reader-text"><?php esc_html_e( 'Current Page', 'pmpro-affiliates' ); ?></span>
				<span id="table-paging" class="paging-input">
					<span class="tablenav-paging-text">
						<?php
						// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Static spans around number_format_i18n() output.
						printf(
							/* translators: 1: Current page number, 2: Total pages. */
							esc_html( _n( '%1$s of %2$s', '%1$s of %2$s', $total_pages, 'pmpro-affiliates' ) ),
							'<span class="current-page">' . number_format_i18n( $current_page ) . '</span>',
							'<span class="total-pages">' . number_format_i18n( $total_pages ) . '</span>'
						);
						// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
						?>
					</span>
				</span>

				<?php if ( $current_page == $total_pages ) : ?>
					<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&rsaquo;</span>
					<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&raquo;</span>
				<?php else : ?>
					<a class="next-page button" href="<?php echo esc_url( $next_page_url ); ?>">
						<span class="screen-reader-text"><?php esc_html_e( 'Next page', 'pmpro-affiliates' ); ?></span>
						<span aria-hidden="true">&rsaquo;</span>
					</a>
					<a class="last-page button" href="<?php echo esc_url( $last_page_url ); ?>">
						<span class="screen-reader-text"><?php esc_html_e( 'Last page', 'pmpro-affiliates' ); ?></span>
						<span aria-hidden="true">&raquo;</span>
					</a>
				<?php endif; ?>
			</span>
		</div>
		<br class="clear">
	</div>
	<?php
}
