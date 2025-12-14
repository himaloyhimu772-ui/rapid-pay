<?php
/**
 * Rapid Pay Admin Dashboard
 *
 * @package RapidPay
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Rapid Pay Admin Class
 */
class Rapid_Pay_Admin {

	/**
	 * Render admin dashboard.
	 */
	public static function render_dashboard() {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'rapid-pay' ) );
		}

		// Get filter parameters.
		$status = isset( $_GET['filter_status'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_status'] ) ) : '';
		$date_from = isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : '';
		$date_to = isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : '';

		// Prepare query args.
		$args = array(
			'limit' => 100,
		);

		if ( ! empty( $status ) ) {
			$args['status'] = $status;
		}

		if ( ! empty( $date_from ) ) {
			$args['date_from'] = $date_from . ' 00:00:00';
		}

		if ( ! empty( $date_to ) ) {
			$args['date_to'] = $date_to . ' 23:59:59';
		}

		// Get orders.
		$orders = Rapid_Pay_Order_Handler::get_orders( $args );
		$total_count = Rapid_Pay_Order_Handler::get_orders_count( $args );

		// Get analytics data.
		$analytics = Rapid_Pay_Analytics::get_analytics_data();

		?>
		<div class="wrap rapid-pay-dashboard">
			<h1><?php esc_html_e( 'Rapid Pay Dashboard', 'rapid-pay' ); ?></h1>

			<!-- Analytics Cards -->
			<div class="rapid-pay-analytics-cards">
				<div class="rapid-pay-card">
					<h3><?php esc_html_e( 'Today\'s Earnings', 'rapid-pay' ); ?></h3>
					<p class="amount"><?php echo self::format_currency( $analytics['today'] ); ?></p>
				</div>
				<div class="rapid-pay-card">
					<h3><?php esc_html_e( 'This Week', 'rapid-pay' ); ?></h3>
					<p class="amount"><?php echo self::format_currency( $analytics['week'] ); ?></p>
				</div>
				<div class="rapid-pay-card">
					<h3><?php esc_html_e( 'This Month', 'rapid-pay' ); ?></h3>
					<p class="amount"><?php echo self::format_currency( $analytics['month'] ); ?></p>
				</div>
				<div class="rapid-pay-card">
					<h3><?php esc_html_e( 'Total Earnings', 'rapid-pay' ); ?></h3>
					<p class="amount"><?php echo self::format_currency( $analytics['total'] ); ?></p>
				</div>
			</div>

			<!-- Chart -->
			<div class="rapid-pay-chart-container">
				<h2><?php esc_html_e( 'Income Chart', 'rapid-pay' ); ?></h2>
				<canvas id="rapid-pay-chart" width="400" height="100"></canvas>
			</div>

			<!-- Filters -->
			<div class="rapid-pay-filters">
				<form method="get" action="">
					<input type="hidden" name="page" value="rapid-pay" />
					
					<select name="filter_status">
						<option value=""><?php esc_html_e( 'All Statuses', 'rapid-pay' ); ?></option>
						<option value="on-hold" <?php selected( $status, 'on-hold' ); ?>><?php esc_html_e( 'On Hold', 'rapid-pay' ); ?></option>
						<option value="completed" <?php selected( $status, 'completed' ); ?>><?php esc_html_e( 'Completed', 'rapid-pay' ); ?></option>
						<option value="refunded" <?php selected( $status, 'refunded' ); ?>><?php esc_html_e( 'Refunded', 'rapid-pay' ); ?></option>
						<option value="cancelled" <?php selected( $status, 'cancelled' ); ?>><?php esc_html_e( 'Cancelled', 'rapid-pay' ); ?></option>
						<option value="pending" <?php selected( $status, 'pending' ); ?>><?php esc_html_e( 'Pending', 'rapid-pay' ); ?></option>
					</select>

					<input type="date" name="date_from" value="<?php echo esc_attr( $date_from ); ?>" placeholder="<?php esc_attr_e( 'From Date', 'rapid-pay' ); ?>" />
					<input type="date" name="date_to" value="<?php echo esc_attr( $date_to ); ?>" placeholder="<?php esc_attr_e( 'To Date', 'rapid-pay' ); ?>" />

					<button type="submit" class="button"><?php esc_html_e( 'Filter', 'rapid-pay' ); ?></button>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=rapid-pay' ) ); ?>" class="button"><?php esc_html_e( 'Reset', 'rapid-pay' ); ?></a>
				</form>
			</div>

			<!-- Orders Table -->
			<div class="rapid-pay-orders-table">
				<h2><?php esc_html_e( 'Recent Orders', 'rapid-pay' ); ?></h2>
				<p><?php printf( esc_html__( 'Total: %d orders', 'rapid-pay' ), $total_count ); ?></p>

				<?php if ( ! empty( $orders ) ) : ?>
					<table class="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Order ID', 'rapid-pay' ); ?></th>
								<th><?php esc_html_e( 'Customer', 'rapid-pay' ); ?></th>
								<th><?php esc_html_e( 'Phone', 'rapid-pay' ); ?></th>
								<th><?php esc_html_e( 'Sender Phone', 'rapid-pay' ); ?></th>
								<th><?php esc_html_e( 'TrxID', 'rapid-pay' ); ?></th>
								<th><?php esc_html_e( 'Amount', 'rapid-pay' ); ?></th>
								<th><?php esc_html_e( 'Method', 'rapid-pay' ); ?></th>
								<th><?php esc_html_e( 'Date', 'rapid-pay' ); ?></th>
								<th><?php esc_html_e( 'Status', 'rapid-pay' ); ?></th>
								<th><?php esc_html_e( 'Actions', 'rapid-pay' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $orders as $order ) : ?>
								<tr data-order-id="<?php echo esc_attr( $order->order_id ); ?>">
									<td>
										<a href="<?php echo esc_url( admin_url( 'post.php?post=' . $order->order_id . '&action=edit' ) ); ?>" target="_blank">
											#<?php echo esc_html( $order->order_id ); ?>
										</a>
									</td>
									<td><?php echo esc_html( $order->customer_name ); ?></td>
									<td><?php echo esc_html( $order->customer_phone ); ?></td>
									<td><?php echo esc_html( $order->sender_phone ); ?></td>
									<td><?php echo esc_html( $order->transaction_id ); ?></td>
									<td><?php echo self::format_currency( $order->amount ); ?></td>
									<td><?php echo esc_html( ucfirst( $order->payment_method ) ); ?></td>
									<td><?php echo esc_html( gmdate( 'Y-m-d H:i', strtotime( $order->created_at ) ) ); ?></td>
									<td>
										<span class="rapid-pay-status rapid-pay-status-<?php echo esc_attr( $order->status ); ?>">
											<?php echo esc_html( ucfirst( str_replace( '-', ' ', $order->status ) ) ); ?>
										</span>
									</td>
									<td>
										<select class="rapid-pay-status-select" data-order-id="<?php echo esc_attr( $order->order_id ); ?>">
											<option value=""><?php esc_html_e( 'Status', 'rapid-pay' ); ?></option>
											<option value="completed"><?php esc_html_e( 'Mark Completed', 'rapid-pay' ); ?></option>
											<option value="refunded"><?php esc_html_e( 'Mark Refunded', 'rapid-pay' ); ?></option>
											<option value="cancelled"><?php esc_html_e( 'Mark Cancelled', 'rapid-pay' ); ?></option>
											<option value="pending"><?php esc_html_e( 'Mark Pending', 'rapid-pay' ); ?></option>
										</select>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php else : ?>
					<p><?php esc_html_e( 'No orders found.', 'rapid-pay' ); ?></p>
				<?php endif; ?>
			</div>
		</div>

		<script type="text/javascript">
			var rapidPayChartData = <?php echo wp_json_encode( $analytics['chart_data'] ); ?>;
		</script>
		<?php
	}

	/**
	 * Format currency based on settings.
	 *
	 * @param float $amount Amount to format.
	 * @return string Formatted currency string.
	 */
	public static function format_currency( $amount ) {
		$settings = get_option( 'rapid_pay_settings', array() );
		$currency = isset( $settings['currency'] ) ? $settings['currency'] : get_woocommerce_currency();
		$symbol = get_woocommerce_currency_symbol( $currency );

		return $symbol . number_format( $amount, 2 );
	}

	/**
	 * AJAX handler to update order status.
	 */
	public static function ajax_update_order_status() {
		// Check nonce.
		check_ajax_referer( 'rapid_pay_admin_nonce', 'nonce' );

		// Check user capabilities.
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'rapid-pay' ) ) );
		}

		// Get parameters.
		$order_id = isset( $_POST['order_id'] ) ? intval( $_POST['order_id'] ) : 0;
		$new_status = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : '';

		if ( ! $order_id || ! $new_status ) {
			wp_send_json_error( array( 'message' => __( 'Invalid parameters.', 'rapid-pay' ) ) );
		}

		// Update order status.
		$result = Rapid_Pay_Order_Handler::update_order_status( $order_id, $new_status );

		if ( $result ) {
			wp_send_json_success( array( 'message' => __( 'Order status updated successfully.', 'rapid-pay' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to update order status.', 'rapid-pay' ) ) );
		}
	}
}
