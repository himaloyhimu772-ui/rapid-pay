<?php
/**
 * Rapid Pay Analytics
 *
 * @package RapidPay
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Rapid Pay Analytics Class
 */
class Rapid_Pay_Analytics {

	/**
	 * Get analytics data.
	 *
	 * @param array $args Query arguments.
	 * @return array
	 */
	public static function get_analytics_data( $args = array() ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'rapid_pay_orders';

		$defaults = array(
			'period' => 'all',
		);

		$args = wp_parse_args( $args, $defaults );

		// Calculate today's earnings.
		$today_start = gmdate( 'Y-m-d 00:00:00' );
		$today_end = gmdate( 'Y-m-d 23:59:59' );
		$today_earnings = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT SUM(amount) FROM " . esc_sql( $table_name ) . " WHERE status = 'completed' AND created_at >= %s AND created_at <= %s",
				$today_start,
				$today_end
			)
		);

		// Calculate this week's earnings.
		$week_start = gmdate( 'Y-m-d 00:00:00', strtotime( 'monday this week' ) );
		$week_end = gmdate( 'Y-m-d 23:59:59', strtotime( 'sunday this week' ) );
		$week_earnings = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT SUM(amount) FROM " . esc_sql( $table_name ) . " WHERE status = 'completed' AND created_at >= %s AND created_at <= %s",
				$week_start,
				$week_end
			)
		);

		// Calculate this month's earnings.
		$month_start = gmdate( 'Y-m-01 00:00:00' );
		$month_end = gmdate( 'Y-m-t 23:59:59' );
		$month_earnings = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT SUM(amount) FROM " . esc_sql( $table_name ) . " WHERE status = 'completed' AND created_at >= %s AND created_at <= %s",
				$month_start,
				$month_end
			)
		);

		// Calculate total earnings.
		$total_earnings = $wpdb->get_var(
			"SELECT SUM(amount) FROM " . esc_sql( $table_name ) . " WHERE status = 'completed'"
		);

		// Get chart data for the last 30 days.
		$chart_data = self::get_chart_data( 30 );

		return array(
			'today'      => floatval( $today_earnings ),
			'week'       => floatval( $week_earnings ),
			'month'      => floatval( $month_earnings ),
			'total'      => floatval( $total_earnings ),
			'chart_data' => $chart_data,
		);
	}

	/**
	 * Get chart data for specified number of days.
	 *
	 * @param int $days Number of days.
	 * @return array
	 */
	public static function get_chart_data( $days = 30 ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'rapid_pay_orders';

		$labels = array();
		$data = array();

		for ( $i = $days - 1; $i >= 0; $i-- ) {
			$date = gmdate( 'Y-m-d', strtotime( "-{$i} days" ) );
			$labels[] = gmdate( 'M d', strtotime( $date ) );

			$start = $date . ' 00:00:00';
			$end = $date . ' 23:59:59';

			$amount = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT SUM(amount) FROM " . esc_sql( $table_name ) . " WHERE status = 'completed' AND created_at >= %s AND created_at <= %s",
					$start,
					$end
				)
			);

			$data[] = floatval( $amount );
		}

		return array(
			'labels' => $labels,
			'data'   => $data,
		);
	}

	/**
	 * Get earnings by payment method.
	 *
	 * @return array
	 */
	public static function get_earnings_by_method() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'rapid_pay_orders';

		$results = $wpdb->get_results(
			"SELECT payment_method, SUM(amount) as total FROM " . esc_sql( $table_name ) . " WHERE status = 'completed' GROUP BY payment_method"
		);

		$earnings = array(
			'bkash'  => 0,
			'nagad'  => 0,
			'rocket' => 0,
			'upay'   => 0,
		);

		foreach ( $results as $result ) {
			if ( isset( $earnings[ $result->payment_method ] ) ) {
				$earnings[ $result->payment_method ] = floatval( $result->total );
			}
		}

		return $earnings;
	}

	/**
	 * Get order statistics.
	 *
	 * @return array
	 */
	public static function get_order_statistics() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'rapid_pay_orders';

		$total_orders = $wpdb->get_var( "SELECT COUNT(*) FROM " . esc_sql( $table_name ) );
		$completed_orders = $wpdb->get_var( "SELECT COUNT(*) FROM " . esc_sql( $table_name ) . " WHERE status = 'completed'" );
		$pending_orders = $wpdb->get_var( "SELECT COUNT(*) FROM " . esc_sql( $table_name ) . " WHERE status IN ('on-hold', 'pending')" );
		$cancelled_orders = $wpdb->get_var( "SELECT COUNT(*) FROM " . esc_sql( $table_name ) . " WHERE status = 'cancelled'" );
		$refunded_orders = $wpdb->get_var( "SELECT COUNT(*) FROM " . esc_sql( $table_name ) . " WHERE status = 'refunded'" );

		return array(
			'total'     => intval( $total_orders ),
			'completed' => intval( $completed_orders ),
			'pending'   => intval( $pending_orders ),
			'cancelled' => intval( $cancelled_orders ),
			'refunded'  => intval( $refunded_orders ),
		);
	}

	/**
	 * Get custom date range analytics.
	 *
	 * @param string $date_from Start date.
	 * @param string $date_to End date.
	 * @return array
	 */
	public static function get_custom_range_analytics( $date_from, $date_to ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'rapid_pay_orders';

		$start = $date_from . ' 00:00:00';
		$end = $date_to . ' 23:59:59';

		$total_earnings = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT SUM(amount) FROM " . esc_sql( $table_name ) . " WHERE status = 'completed' AND created_at >= %s AND created_at <= %s",
				$start,
				$end
			)
		);

		$total_orders = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM $table_name WHERE created_at >= %s AND created_at <= %s",
				$start,
				$end
			)
		);

		return array(
			'earnings' => floatval( $total_earnings ),
			'orders'   => intval( $total_orders ),
		);
	}

	/**
	 * AJAX handler to get analytics.
	 */
	public static function ajax_get_analytics() {
		// Check nonce.
		check_ajax_referer( 'rapid_pay_admin_nonce', 'nonce' );

		// Check user capabilities.
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'rapid-pay' ) ) );
		}

		// Get parameters.
		$period = isset( $_POST['period'] ) ? sanitize_text_field( wp_unslash( $_POST['period'] ) ) : 'all';
		$date_from = isset( $_POST['date_from'] ) ? sanitize_text_field( wp_unslash( $_POST['date_from'] ) ) : '';
		$date_to = isset( $_POST['date_to'] ) ? sanitize_text_field( wp_unslash( $_POST['date_to'] ) ) : '';

		if ( 'custom' === $period && ! empty( $date_from ) && ! empty( $date_to ) ) {
			$analytics = self::get_custom_range_analytics( $date_from, $date_to );
		} else {
			$analytics = self::get_analytics_data( array( 'period' => $period ) );
		}

		wp_send_json_success( $analytics );
	}
}
