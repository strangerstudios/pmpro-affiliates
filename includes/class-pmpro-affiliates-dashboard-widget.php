<?php
/**
 * Dashboard Widget: Affiliate Summary
 *
 * An admin dashboard widget showing a quick snapshot
 * of affiliate activity (today, this month, last month, totals, and recent referrals).
 *
 * @since 0.8
 * @package PMPro_Affiliates
 */

defined( 'ABSPATH' ) || exit;

/**
 * Dashboard widget class.
 *
 * Displays affiliate performance metrics in the WordPress admin dashboard.
 *
 * @since 0.8
 */
class PMPRO_AFFILIATES_Dashboard_Widget {

	/**
	 * Register the dashboard widget and cache-busting hooks.
	 *
	 * @since 0.8
	 *
	 * @return void
	 */
	public static function register_widget(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		wp_add_dashboard_widget(
			'pmpro_affiliates_dashboard_widget',
			__( 'Affiliate Summary', 'pmpro-affiliates' ),
			array( __CLASS__, 'display' )
		);
	}

	/**
	 * Display the widget content.
	 *
	 * @since 0.8
	 *
	 * @return void
	 */
	public static function display(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$data = self::get_data();
		$total_count    = $data['totals']['count'];
		$total_earnings = $data['totals']['earnings'];

		// Show the empty state when there is no data.
		if ( $total_count < 1 && $total_earnings < 0.01 ) {
			self::display_empty();
			return;
		}

		$settings    = pmpro_affiliates_get_settings();
		$plural_name = $settings['pmpro_affiliates_plural_name'];
		$view_all_url = admin_url( 'admin.php?page=pmpro-affiliates&report=all' );
		?>
		<div id="pmpro_affiliates_dashboard_widget">
			<style>
				#pmpro_affiliates_dashboard_widget .pmpro-affiliates-dashboard-summary {
					position: relative;
				}
				#pmpro_affiliates_dashboard_widget .pmpro-affiliates-dashboard-grid {
					display: grid;
					grid-template-columns: repeat(2, 1fr);
					gap: 12px;
					margin-bottom: 16px;
				}
				#pmpro_affiliates_dashboard_widget .pmpro-affiliates-dashboard-card {
					background: #f6f7f7;
					border: 1px solid #c3c4c7;
					border-radius: 8px;
					padding: 12px;
				}
				.pmpro-affiliates-dashboard-card-inner {
					display: flex;
					align-items: center;
					justify-content: space-between;
					border-bottom: 1px solid #ddd;
					padding-bottom: 5px;
				}
				#pmpro_affiliates_dashboard_widget .pmpro-affiliates-dashboard-card h3 {
					margin: 0 0 0px;
					padding: 0;
					font-size: 13px;
					font-weight: 600;
					color: #1d2327;
				}
				#pmpro_affiliates_dashboard_widget .pmpro-affiliates-dashboard-amount {
					font-size: 20px;
					font-weight: 700;
					margin: 4px 0;
					color: #1d2327;
				}
				#pmpro_affiliates_dashboard_widget .pmpro-affiliates-dashboard-count {
					font-size: 12px;
					margin: 0px;
					color: #50575e;
				}
				#pmpro_affiliates_dashboard_widget .pmpro-affiliates-dashboard-recent h3 {
					margin: 0 0 8px;
					padding: 0;
					font-size: 13px;
					font-weight: 600;
				}
				#pmpro_affiliates_dashboard_widget .pmpro-affiliates-dashboard-recent table {
					margin-top: 4px;
				}
				#pmpro_affiliates_dashboard_widget .pmpro-affiliates-view-all {
					margin-top: 10px;
					display: block;
					text-decoration: none;
				}
				#pmpro_affiliates_dashboard_widget .pmpro-affiliates-view-all a{
					
				}
				@media only screen and (max-width: 782px) {
					#pmpro_affiliates_dashboard_widget .pmpro-affiliates-dashboard-grid {
						grid-template-columns: repeat(1, 1fr);
					}
				}
			</style>

			<div class="pmpro-affiliates-dashboard-summary">
				<div class="pmpro-affiliates-dashboard-grid">
					<?php self::render_card( __( 'Today', 'pmpro-affiliates' ), $data['today']['earnings'], $data['today']['count'] ); ?>
					<?php self::render_card( __( 'This Month', 'pmpro-affiliates' ), $data['current_month']['earnings'], $data['current_month']['count'] ); ?>
					<?php self::render_card( __( 'Last Month', 'pmpro-affiliates' ), $data['last_month']['earnings'], $data['last_month']['count'] ); ?>
					<?php self::render_card( __( 'Totals', 'pmpro-affiliates' ), $data['totals']['earnings'], $data['totals']['count'] ); ?>
				</div>

				<?php
				/**
				 * Fires before the recent referrals table in the dashboard widget.
				 *
				 * @since 0.8
				 *
				 * @param array $data The full widget dataset.
				 */
				do_action( 'pmpro_affiliates_dashboard_widget_before_table', $data );
				?>

				<div class="pmpro-affiliates-dashboard-recent">
					<h3><?php esc_html_e( 'Recent Referrals', 'pmpro-affiliates' ); ?></h3>
					<table class="widefat striped">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Affiliate Code', 'pmpro-affiliates' ); ?></th>
								<th><?php esc_html_e( 'Member', 'pmpro-affiliates' ); ?></th>
								<th><?php esc_html_e( 'Status', 'pmpro-affiliates' ); ?></th>
								<th><?php esc_html_e( 'Amount', 'pmpro-affiliates' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php if ( empty( $data['recent'] ) ) : ?>
								<tr>
									<td colspan="4"><?php esc_html_e( 'No recent referrals.', 'pmpro-affiliates' ); ?></td>
								</tr>
							<?php else : ?>
								<?php foreach ( $data['recent'] as $referral ) : ?>
									<tr>
										<td>
											<a href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-affiliates&report=' . intval( $referral['affiliate_id'] ) ) ); ?>">
												<?php echo esc_html( $referral['affiliate_code'] ); ?>
											</a>
										</td>
										<td><?php echo esc_html( $referral['member_name'] ); ?></td>
										<td><?php echo esc_html( $referral['status'] ); ?></td>
										<td><?php echo esc_html( pmpro_formatPrice( $referral['amount'] ) ); ?></td>
									</tr>
								<?php endforeach; ?>
							<?php endif; ?>
						</tbody>
					</table>
				</div>

				<a href="<?php echo esc_url( $view_all_url ); ?>" class="pmpro-affiliates-view-all">
					<?php
					echo esc_html(
						sprintf(
							/* translators: %s: Plural affiliate label (e.g., "affiliates") */
							__( 'View All %s', 'pmpro-affiliates' ),
							ucwords( $plural_name )
						)
					);
					?> &rarr;
				</a>

				<?php
				/**
				 * Fires at the bottom of the dashboard widget, after all content.
				 *
				 * @since 0.8
				 *
				 * @param array $data The full widget dataset.
				 */
				do_action( 'pmpro_affiliates_dashboard_widget_after', $data );
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render a single stat card.
	 *
	 * @since 0.8
	 *
	 * @param string $title    Card title.
	 * @param float  $earnings Earnings amount.
	 * @param int    $count    Referral count.
	 * @return void
	 */
	private static function render_card( string $title, float $earnings, int $count ): void {
		?>
		<div class="pmpro-affiliates-dashboard-card">
			<div class="pmpro-affiliates-dashboard-card-inner">
				<h3><?php echo esc_html( $title ); ?></h3>
				<p class="pmpro-affiliates-dashboard-count">
					<?php
					echo esc_html(
						sprintf(
							/* translators: %d: Number of referrals */
							_n( '%d referral', '%d referrals', $count, 'pmpro-affiliates' ),
							$count
						)
					);
					?>
				</p>
			</div>
			<p class="pmpro-affiliates-dashboard-amount"><?php echo esc_html( pmpro_formatPrice( $earnings ) ); ?></p>
		</div>
		<?php
	}

	/**
	 * Get the widget data, with transient caching.
	 *
	 * Data is cached in a site transient for 5 minutes. The cache is
	 * automatically busted when a new order is attributed to an affiliate
	 * (via the pmpro_added_order hook).
	 *
	 * @since 0.8
	 *
	 * @return array {
	 *     Widget dataset.
	 *
	 *     @type array $today {
	 *         @type float $earnings Today's commission-earned revenue.
	 *         @type int   $count    Today's referral count.
	 *     }
	 *     @type array $current_month {
	 *         @type float $earnings This month-to-date revenue.
	 *         @type int   $count    This month-to-date referral count.
	 *     }
	 *     @type array $last_month {
	 *         @type float $earnings Last month's revenue.
	 *         @type int   $count    Last month's referral count.
	 *     }
	 *     @type array $totals {
	 *         @type float $earnings All-time revenue.
	 *         @type int   $count    All-time referral count.
	 *     }
	 *     @type array $recent[] {
	 *         @type int    $order_id       Order ID.
	 *         @type int    $affiliate_id   Affiliate ID.
	 *         @type string $affiliate_code Affiliate code.
	 *         @type string $affiliate_name Affiliate display name.
	 *         @type string $member_name    Purchasing user's login.
	 *         @type float  $amount         Order total.
	 *         @type string $status         'Paid' or 'Unpaid'.
	 *     }
	 * }
	 */
	private static function get_data(): array {
		global $wpdb;

		// Return cached data if available.
		$cached = get_transient( 'pmproaff_dashboard_widget' );
		if ( false !== $cached ) {
			/** This filter is documented later in this method. */
			return apply_filters( 'pmpro_affiliates_dashboard_widget_data', $cached );
		}

		// Validate and whitelist the commission calculation source column.
		$source = pmpro_affiliates_get_commission_calculation_source();
		if ( ! in_array( $source, array( 'total', 'subtotal' ), true ) ) {
			$source = 'total';
		}

		$affiliate_filter = "o.affiliate_id IS NOT NULL AND o.affiliate_id <> '' AND o.affiliate_id <> 0";
		$status_filter    = "o.status NOT IN('pending','error','refunded','refund','token','review')";

		$now          = current_time( 'timestamp' );
		$today_start  = gmdate( 'Y-m-d 00:00:00', $now );
		$today_end    = gmdate( 'Y-m-d 23:59:59', $now );
		$month_start  = gmdate( 'Y-m-01 00:00:00', $now );
		$month_end    = gmdate( 'Y-m-t 23:59:59', $now );

		$last_month_start = gmdate( 'Y-m-01 00:00:00', strtotime( 'first day of last month', $now ) );
		$last_month_end   = gmdate( 'Y-m-t 23:59:59', strtotime( 'last day of last month', $now ) );

		$base_select = "SELECT COUNT(*) as count, COALESCE(SUM({$source}), 0) as earnings";
		$base_from   = "FROM {$wpdb->pmpro_membership_orders} o WHERE {$affiliate_filter} AND {$status_filter}";

		// Today.
		$today_row = $wpdb->get_row(
			$wpdb->prepare(
				"{$base_select} {$base_from} AND o.timestamp >= %s AND o.timestamp <= %s", // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$today_start,
				$today_end
			)
		);

		// Current month.
		$current_month_row = $wpdb->get_row(
			$wpdb->prepare(
				"{$base_select} {$base_from} AND o.timestamp >= %s AND o.timestamp <= %s", // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$month_start,
				$month_end
			)
		);

		// Last month.
		$last_month_row = $wpdb->get_row(
			$wpdb->prepare(
				"{$base_select} {$base_from} AND o.timestamp >= %s AND o.timestamp <= %s", // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$last_month_start,
				$last_month_end
			)
		);

		// Totals (all time).
		$totals_row = $wpdb->get_row(
			"{$base_select} {$base_from}" // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		);

		/**
		 * Filters the number of recent referrals to display in the dashboard widget.
		 *
		 * @since 0.8
		 *
		 * @param int $count Number of recent referrals. Default 5.
		 */
		$recent_count = apply_filters( 'pmpro_affiliates_dashboard_widget_recent_count', 5 );

		$recent_rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT o.id as order_id,
				        a.id as affiliate_id,
				        a.code as affiliate_code,
				        a.name as affiliate_name,
				        u.user_login as member_name,
				        o.{$source} as amount,
				        om.meta_value as affiliate_paid
				FROM {$wpdb->pmpro_membership_orders} o
				LEFT JOIN {$wpdb->pmpro_affiliates} a
				    ON o.affiliate_id = a.id
				LEFT JOIN {$wpdb->users} u
				    ON o.user_id = u.ID
				LEFT JOIN {$wpdb->pmpro_membership_ordermeta} om
				    ON o.id = om.pmpro_membership_order_id
				    AND om.meta_key = 'pmpro_affiliate_paid'
				WHERE {$affiliate_filter}
				  AND {$status_filter}
				ORDER BY o.timestamp DESC
				LIMIT %d",
				$recent_count
			)
		);

		// Build structured data array.
		$data = array(
			'today'         => array(
				'earnings' => isset( $today_row->earnings ) ? (float) $today_row->earnings : 0.0,
				'count'    => isset( $today_row->count ) ? (int) $today_row->count : 0,
			),
			'current_month' => array(
				'earnings' => isset( $current_month_row->earnings ) ? (float) $current_month_row->earnings : 0.0,
				'count'    => isset( $current_month_row->count ) ? (int) $current_month_row->count : 0,
			),
			'last_month'    => array(
				'earnings' => isset( $last_month_row->earnings ) ? (float) $last_month_row->earnings : 0.0,
				'count'    => isset( $last_month_row->count ) ? (int) $last_month_row->count : 0,
			),
			'totals'        => array(
				'earnings' => isset( $totals_row->earnings ) ? (float) $totals_row->earnings : 0.0,
				'count'    => isset( $totals_row->count ) ? (int) $totals_row->count : 0,
			),
			'recent'        => array(),
		);

		if ( ! empty( $recent_rows ) ) {
			foreach ( $recent_rows as $row ) {
				$data['recent'][] = array(
					'order_id'       => (int) $row->order_id,
					'affiliate_id'   => (int) $row->affiliate_id,
					'affiliate_code' => isset( $row->affiliate_code ) ? $row->affiliate_code : '',
					'affiliate_name' => isset( $row->affiliate_name ) ? $row->affiliate_name : '',
					'member_name'    => isset( $row->member_name ) ? $row->member_name : '',
					'amount'         => isset( $row->amount ) ? (float) $row->amount : 0.0,
					'status'         => '1' === $row->affiliate_paid ? __( 'Paid', 'pmpro-affiliates' ) : __( 'Unpaid', 'pmpro-affiliates' ),
				);
			}
		}

		/**
		 * Filter the full dashboard widget dataset.
		 *
		 * Allows other plugins to modify any of the widget's computed data
		 * before it is rendered or cached.
		 *
		 * @since 0.8
		 *
		 * @param array $data {
		 *     Widget dataset.
		 *
		 *     @type array $today         Today's stats.
		 *     @type array $current_month Current month stats.
		 *     @type array $last_month    Last month stats.
		 *     @type array $totals        All-time stats.
		 *     @type array $recent        Recent referrals.
		 * }
		 */
		$data = apply_filters( 'pmpro_affiliates_dashboard_widget_data', $data );

		set_transient( 'pmproaff_dashboard_widget', $data, 5 * MINUTE_IN_SECONDS );

		return $data;
	}

	/**
	 * Display the empty state when no affiliate data exists.
	 *
	 * @since 0.8
	 *
	 * @return void
	 */
	private static function display_empty(): void {
		$message = __( 'No affiliate activity has been tracked yet.', 'pmpro-affiliates' );

		/**
		 * Filter the empty state message shown in the dashboard widget.
		 *
		 * @since 0.8
		 *
		 * @param string $message The empty state message.
		 */
		$message = apply_filters( 'pmpro_affiliates_dashboard_widget_empty_message', $message );
		?>
		<div id="pmpro_affiliates_dashboard_widget">
			<p><?php echo esc_html( $message ); ?></p>
		</div>
		<?php
	}

	/**
	 * Bust the widget data cache.
	 *
	 * Hooked to `pmpro_added_order` so that the dashboard widget
	 * reflects new referral orders without waiting for the full TTL.
	 *
	 * @since 0.8
	 *
	 * @return void
	 */
	public static function bust_cache(): void {
		delete_transient( 'pmproaff_dashboard_widget' );
	}
}
