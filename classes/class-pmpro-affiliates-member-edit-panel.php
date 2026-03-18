<?php
class PMPRO_AFFILIATES_Member_Edit_Panel extends PMPro_Member_Edit_Panel {
	/**
	 * Set up the panel.
	 */
	public function __construct() {
		$this->slug        = 'pmpro-affiliates';
		$this->title       = __( 'Affiliate', 'pmpro-affiliates' );

		// Get the affiliate for the user.
		$affiliate = pmpro_affiliates_getAffiliatesForUser( self::get_user()->ID );
		if ( ! empty( $affiliate ) ) {
			$affiliate = $affiliate[0];
			$this->title_link = '<a class="page-title-action pmpro-has-icon pmpro-has-icon-admin-users" href="' . esc_url( admin_url( 'admin.php?page=pmpro-affiliates&report=' . $affiliate->id ) ) . '">' . esc_html__( 'View Full Report', 'pmpro-affiliates' ) . '</a>  ' . '<a class="page-title-action pmpro-has-icon pmpro-has-icon-admin-users" href="' . esc_url( admin_url( 'admin.php?page=pmpro-affiliates&edit=' . $affiliate->id ) ) . '">' . esc_html__( 'Edit Affiliate', 'pmpro-affiliates' ) . '</a>';
		}
	}

	/**
	 * Display the panel contents.
	 */
	protected function display_panel_contents() {
		$user = self::get_user();

		// Show affiliate ID, status, referral count, paid commission and link to report.
		$affiliates = pmpro_affiliates_getAffiliatesForUser( $user->ID );
		if ( empty( $affiliates ) ) {
			echo '<p>' . esc_html__( 'This member is not an affiliate.', 'pmpro-affiliates' ) . '</p>';
			return;
		}

		// Get settings for terminology
		$pmpro_affiliates_settings = pmpro_affiliates_get_settings();
		$pmpro_affiliates_singular_name = $pmpro_affiliates_settings['pmpro_affiliates_singular_name'];

		$affiliate = $affiliates[0];

		$paid_commission = pmpro_affiliates_get_commissions( $affiliate->code, 'paid' );
		$unpaid_commission = pmpro_affiliates_get_commissions( $affiliate->code, 'unpaid' );
		$total_commission = $paid_commission + $unpaid_commission;

		// Ensure paid_commission is a number (could be NULL if no orders)
		if ( empty( $paid_commission ) ) {
			$paid_commission = 0;
		}

		/**
		 * Filter the number of orders to show per page in the member edit panel.
		 *
		 * @since TBD
		 *
		 * @param int $limit The number of orders per page. Default 5.
		 */
		$limit = apply_filters( 'pmpro_affiliates_member_panel_orders_per_page', 5 );
		?>
		<div class="pmpro-affiliates-panel">			
			<div class="pmpro-affiliates-stats-grid">
				<div class="postbox">
					<div class="postbox-header">
						<h2 class="pmpro-affiliates-stat-title"><?php esc_html_e( 'Commission Earned (All Time)', 'pmpro-affiliates' ); ?></h2>
					</div>
					<div class="inside">
						<p class="pmpro-affiliates-stat-value"><?php echo esc_html( pmpro_formatPrice( $total_commission ) ); ?></p>
					</div>
				</div>
				
				<div class="postbox">
					<div class="postbox-header">
						<h2 class="pmpro-affiliates-stat-title"><?php esc_html_e( 'Commission Due', 'pmpro-affiliates' ); ?></h2>
					</div>
					<div class="inside">
						<p class="pmpro-affiliates-stat-value"><?php echo esc_html( pmpro_formatPrice( $unpaid_commission ) ); ?></p>
					</div>
				</div>

				<div class="postbox">
					<div class="postbox-header">
						<h2 class="pmpro-affiliates-stat-title"><?php esc_html_e( 'Commission Paid', 'pmpro-affiliates' ); ?></h2>
					</div>
					<div class="inside">
						<p class="pmpro-affiliates-stat-value"><?php echo esc_html( pmpro_formatPrice( $paid_commission ) ); ?></p>
					</div>
				</div>

				<div class="postbox">
					<div class="postbox-header">
						<h2 class="pmpro-affiliates-stat-title"><?php esc_html_e( 'Conversion Rate', 'pmpro-affiliates' ); ?></h2>
					</div>
					<div class="inside">
						<p class="pmpro-affiliates-stat-value"><?php echo pmpro_affiliates_get_conversion_rate( $affiliate ); ?></p>
					</div>
				</div>
			</div>		

			<h4><?php esc_html_e( 'Recent Referral Orders', 'pmpro-affiliates' ); ?></h4>
			
			<?php
			pmpro_affiliates_display_orders_table( array(
				'affiliate_id'      => $affiliate->id,
				'show_code'         => false,
				'show_name'         => false,
				'empty_message'     => __( 'No referral orders have been tracked yet.', 'pmpro-affiliates' ),
				'table_class'       => 'widefat striped',
				'limit'             => $limit,
				'show_pagination'   => false,
			) );
			?>
		</div>
		<?php
	}
}