<?php
/**
 * Rapid Pay Order Handler
 *
 * @package RapidPay
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Rapid Pay Order Handler Class
 */
class Rapid_Pay_Order_Handler {

	/**
	 * Save order to custom table.
	 *
	 * @param int $order_id Order ID.
	 */
	public static function save_order( $order_id ) {
		global $wpdb;

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$table_name = $wpdb->prefix . 'rapid_pay_orders';

		// Check if order already exists.
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM $table_name WHERE order_id = %d",
				$order_id
			)
		);

		$data = array(
			'order_id'       => $order_id,
			'customer_name'  => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
			'customer_phone' => $order->get_billing_phone(),
			'sender_phone'   => $order->get_meta( '_rapid_pay_sender_phone' ),
			'transaction_id' => $order->get_meta( '_rapid_pay_transaction_id' ),
			'payment_method' => $order->get_meta( '_rapid_pay_method' ),
			'amount'         => $order->get_total(),
			'status'         => $order->get_status(),
			'updated_at'     => current_time( 'mysql' ),
		);

		if ( $existing ) {
			// Update existing record.
			$wpdb->update(
				$table_name,
				$data,
				array( 'order_id' => $order_id ),
				array( '%d', '%s', '%s', '%s', '%s', '%s', '%f', '%s', '%s' ),
				array( '%d' )
			);
		} else {
			// Insert new record.
			$data['created_at'] = current_time( 'mysql' );
			$wpdb->insert(
				$table_name,
				$data,
				array( '%d', '%s', '%s', '%s', '%s', '%s', '%f', '%s', '%s', '%s' )
			);
		}
	}

	/**
	 * Get all Rapid Pay orders.
	 *
	 * @param array $args Query arguments.
	 * @return array
	 */
	public static function get_orders( $args = array() ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'rapid_pay_orders';

		$defaults = array(
			'status'     => '',
			'date_from'  => '',
			'date_to'    => '',
			'limit'      => 50,
			'offset'     => 0,
			'orderby'    => 'created_at',
			'order'      => 'DESC',
		);

		$args = wp_parse_args( $args, $defaults );

		$where = array( '1=1' );
		$where_values = array();

		if ( ! empty( $args['status'] ) ) {
			$where[] = 'status = %s';
			$where_values[] = $args['status'];
		}

		if ( ! empty( $args['date_from'] ) ) {
			$where[] = 'created_at >= %s';
			$where_values[] = $args['date_from'];
		}

		if ( ! empty( $args['date_to'] ) ) {
			$where[] = 'created_at <= %s';
			$where_values[] = $args['date_to'];
		}

		$where_sql = implode( ' AND ', $where );

		$orderby = sanitize_sql_orderby( $args['orderby'] . ' ' . $args['order'] );

		$query = "SELECT * FROM $table_name WHERE $where_sql ORDER BY $orderby LIMIT %d OFFSET %d";
		$where_values[] = $args['limit'];
		$where_values[] = $args['offset'];

		if ( ! empty( $where_values ) ) {
			$query = $wpdb->prepare( $query, $where_values );
		}

		return $wpdb->get_results( $query );
	}

	/**
	 * Get total count of orders.
	 *
	 * @param array $args Query arguments.
	 * @return int
	 */
	public static function get_orders_count( $args = array() ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'rapid_pay_orders';

		$where = array( '1=1' );
		$where_values = array();

		if ( ! empty( $args['status'] ) ) {
			$where[] = 'status = %s';
			$where_values[] = $args['status'];
		}

		if ( ! empty( $args['date_from'] ) ) {
			$where[] = 'created_at >= %s';
			$where_values[] = $args['date_from'];
		}

		if ( ! empty( $args['date_to'] ) ) {
			$where[] = 'created_at <= %s';
			$where_values[] = $args['date_to'];
		}

		$where_sql = implode( ' AND ', $where );

		$query = "SELECT COUNT(*) FROM $table_name WHERE $where_sql";

		if ( ! empty( $where_values ) ) {
			$query = $wpdb->prepare( $query, $where_values );
		}

		return (int) $wpdb->get_var( $query );
	}

	/**
	 * Update order status.
	 *
	 * @param int    $order_id Order ID.
	 * @param string $new_status New status.
	 * @return bool
	 */
	public static function update_order_status( $order_id, $new_status ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return false;
		}

		// Map status names.
		$status_map = array(
			'completed' => 'completed',
			'refunded'  => 'refunded',
			'cancelled' => 'cancelled',
			'pending'   => 'pending',
			'on-hold'   => 'on-hold',
		);

		if ( ! isset( $status_map[ $new_status ] ) ) {
			return false;
		}

		// Update WooCommerce order status.
		$order->update_status( $status_map[ $new_status ], __( 'Status updated from Rapid Pay dashboard.', 'rapid-pay' ) );

		// Update custom table.
		self::save_order( $order_id );

		return true;
	}

	/**
	 * Expire pending orders.
	 */
	public static function expire_pending_orders() {
		$settings = get_option( 'rapid_pay_settings', array() );
		$auto_expire_enabled = isset( $settings['auto_expire_enabled'] ) ? $settings['auto_expire_enabled'] : 'no';
		$auto_expire_hours = isset( $settings['auto_expire_hours'] ) ? intval( $settings['auto_expire_hours'] ) : 24;

		if ( 'yes' !== $auto_expire_enabled ) {
			return;
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'rapid_pay_orders';

		$expire_date = gmdate( 'Y-m-d H:i:s', strtotime( "-{$auto_expire_hours} hours" ) );

		$orders = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT order_id FROM $table_name WHERE status = 'on-hold' AND created_at < %s",
				$expire_date
			)
		);

		foreach ( $orders as $order_data ) {
			self::update_order_status( $order_data->order_id, 'cancelled' );
		}
	}
}
