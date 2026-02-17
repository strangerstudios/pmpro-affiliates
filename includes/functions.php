<?php
/**
 * Get affiliate orders from the database.
 *
 * @since TBD
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
		'limit'  => 0,
		'offset' => 0,
		'paged'  => 1,
	);

	$args = wp_parse_args( $args, $defaults );

	$sql_query = 
		"SELECT o.id as order_id, o.code as order_code, a.id as affiliate_id, a.code, a.commissionrate, o.affiliate_subid as subid, a.name, u.ID as user_id, u.user_login, o.membership_id, UNIX_TIMESTAMP(o.timestamp) as timestamp, " . esc_sql( 'o.' . pmpro_affiliates_get_commission_calculation_source() ) . " as total, o.status, om.meta_value as affiliate_paid
		FROM $wpdb->pmpro_membership_orders o 
		LEFT JOIN $wpdb->pmpro_affiliates a 
		ON o.affiliate_id = a.id 
		LEFT JOIN $wpdb->users u 
		ON o.user_id = u.ID
		LEFT JOIN $wpdb->pmpro_membership_ordermeta om
		ON o.id = om.pmpro_membership_order_id
		AND om.meta_key = 'pmpro_affiliate_paid'
		WHERE o.affiliate_id <> ''
		AND o.affiliate_id IS NOT NULL
		AND o.affiliate_id <> 0
		AND o.status NOT IN('pending', 'error', 'refunded', 'refund', 'token', 'review')";

	if ( 'all' !== $affiliate_id ) {
		$sql_query .= $wpdb->prepare( " AND a.id = %d", (int) $affiliate_id );
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

	return $wpdb->get_results( $sql_query );
}

/**
 * Get the total count of affiliate orders.
 *
 * @since TBD
 *
 * @param int|string $affiliate_id The affiliate ID or 'all' for all affiliates.
 * @return int The total number of affiliate orders.
 */
function pmpro_affiliates_get_orders_count( $affiliate_id = 'all' ) {
	global $wpdb;

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

	return (int) $wpdb->get_var( $sql_query );
}

/**
 * Display the affiliate orders table.
 *
 * @since TBD
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

	// If limit is set, get total count and paginated results.
	if ( $args['limit'] > 0 && empty( $orders ) ) {
		$total_orders = pmpro_affiliates_get_orders_count( $args['affiliate_id'] );
		$orders = pmpro_affiliates_get_orders( $args['affiliate_id'], array(
			'limit' => $args['limit'],
			'paged' => $args['paged'],
		) );
	} elseif ( empty( $orders ) ) {
		$orders = pmpro_affiliates_get_orders( $args['affiliate_id'] );
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
				
				if ( $affiliate_paid == '1' ) {
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
							if ( ! empty( $order->user_id ) ) {
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
						<td><?php echo '<span class="pmpro_affiliate_paid_status" id="order_' . esc_attr( $order->order_id ) . '">' . $affiliate_paid . '</span>'; ?></td>
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
 * Display WP List Table style pagination.
 *
 * @since TBD
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
						printf(
							/* translators: 1: Current page number, 2: Total pages. */
							_n( '%1$s of %2$s', '%1$s of %2$s', $total_pages, 'pmpro-affiliates' ),
							'<span class="current-page">' . number_format_i18n( $current_page ) . '</span>',
							'<span class="total-pages">' . number_format_i18n( $total_pages ) . '</span>'
						);
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
