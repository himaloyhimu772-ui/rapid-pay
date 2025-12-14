<?php
/**
 * Rapid Pay Payment Gateway
 *
 * @package RapidPay
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Rapid Pay Gateway Class
 */
class Rapid_Pay_Gateway extends WC_Payment_Gateway {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->id                 = 'rapid_pay';
		$this->icon               = '';
		$this->has_fields         = true;
		$this->method_title       = __( 'Rapid Pay', 'rapid-pay' );
		$this->method_description = __( 'Accept manual payments through bKash, Nagad, Rocket, and Upay.', 'rapid-pay' );

		// Load settings.
		$this->init_form_fields();
		$this->init_settings();

		// Get settings.
		$this->title        = $this->get_option( 'title' );
		$this->description  = $this->get_option( 'description' );
		$this->instructions = $this->get_option( 'instructions' );

		// Actions.
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
	}

	/**
	 * Initialize gateway settings form fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'      => array(
				'title'   => __( 'Enable/Disable', 'rapid-pay' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Rapid Pay', 'rapid-pay' ),
				'default' => 'yes',
			),
			'title'        => array(
				'title'       => __( 'Title', 'rapid-pay' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'rapid-pay' ),
				'default'     => __( 'Rapid Pay', 'rapid-pay' ),
				'desc_tip'    => true,
			),
			'description'  => array(
				'title'       => __( 'Description', 'rapid-pay' ),
				'type'        => 'textarea',
				'description' => __( 'Payment method description that the customer will see on your checkout.', 'rapid-pay' ),
				'default'     => __( 'Pay securely using bKash, Nagad, Rocket, or Upay.', 'rapid-pay' ),
				'desc_tip'    => true,
			),
			'instructions' => array(
				'title'       => __( 'Instructions', 'rapid-pay' ),
				'type'        => 'textarea',
				'description' => __( 'Instructions that will be added to the thank you page.', 'rapid-pay' ),
				'default'     => __( 'Thank you for your order. Your payment is being verified.', 'rapid-pay' ),
				'desc_tip'    => true,
			),
		);
	}

	/**
	 * Payment fields on checkout page.
	 */
	public function payment_fields() {
		$settings = get_option( 'rapid_pay_settings', array() );
		$enabled_methods = isset( $settings['enabled_methods'] ) ? $settings['enabled_methods'] : array( 'bkash', 'nagad', 'rocket', 'upay' );
		$instruction_text = isset( $settings['instruction_text'] ) ? $settings['instruction_text'] : '';
		$admin_phones = isset( $settings['admin_phones'] ) ? $settings['admin_phones'] : array();

		if ( $this->description ) {
			echo wp_kses_post( wpautop( $this->description ) );
		}

		echo '<div class="rapid-pay-payment-fields">';

		// Payment method selection.
		if ( ! empty( $enabled_methods ) ) {
			echo '<p class="form-row form-row-wide">';
			echo '<label for="rapid_pay_method">' . esc_html__( 'Payment Method', 'rapid-pay' ) . ' <span class="required">*</span></label>';
			echo '<select id="rapid_pay_method" name="rapid_pay_method" class="input-text" required>';
			echo '<option value="">' . esc_html__( 'Select payment method', 'rapid-pay' ) . '</option>';

			$method_labels = array(
				'bkash'  => __( 'bKash', 'rapid-pay' ),
				'nagad'  => __( 'Nagad', 'rapid-pay' ),
				'rocket' => __( 'Rocket', 'rapid-pay' ),
				'upay'   => __( 'Upay', 'rapid-pay' ),
			);

			$phone_data = array();
			foreach ( $admin_phones as $method => $phone ) {
				if ( ! empty( $phone ) ) {
					$phone_data[ $method ] = $phone;
				}
			}

			foreach ( $enabled_methods as $method ) {
				if ( isset( $method_labels[ $method ] ) ) {
						$label = esc_html( $method_labels[ $method ] );
						$phone = isset( $phone_data[ $method ] ) ? $phone_data[ $method ] : '';
						if ( ! empty( $phone ) ) {
							$label .= ' (' . esc_html( $phone ) . ')';
						}
						echo '<option value="' . esc_attr( $method ) . '" data-phone="' . esc_attr( $phone ) . '">' . esc_html( $label ) . '</option>';
				}
			}

			echo '</select>';
			echo '</p>';
		}

		// Dynamic instruction area.
		echo '<div id="rapid-pay-dynamic-instructions" class="rapid-pay-instructions-box" style="display:none;">';
		if ( ! empty( $instruction_text ) ) {
			echo '<div class="rapid-pay-static-instructions">' . wp_kses_post( wpautop( $instruction_text ) ) . '</div>';
		}
		echo '</div>';

		// Sender mobile number.
		echo '<p class="form-row form-row-wide">';
		echo '<label for="rapid_pay_sender_phone">' . esc_html__( 'Sender Mobile Number', 'rapid-pay' ) . ' <span class="required">*</span></label>';
		echo '<input type="text" id="rapid_pay_sender_phone" name="rapid_pay_sender_phone" class="input-text" placeholder="' . esc_attr__( 'e.g., 01712345678', 'rapid-pay' ) . '" required />';
		echo '</p>';

		// Transaction ID.
		echo '<p class="form-row form-row-wide">';
		echo '<label for="rapid_pay_transaction_id">' . esc_html__( 'Transaction ID (TrxID)', 'rapid-pay' ) . ' <span class="required">*</span></label>';
		echo '<input type="text" id="rapid_pay_transaction_id" name="rapid_pay_transaction_id" class="input-text" placeholder="' . esc_attr__( 'e.g., 8N7A5D2C1B', 'rapid-pay' ) . '" required />';
		echo '</p>';

		echo '</div>';
	}

	/**
	 * Validate payment fields.
	 *
	 * @return bool
	 */
	public function validate_fields() {
		$settings = get_option( 'rapid_pay_settings', array() );
		$enabled_methods = isset( $settings['enabled_methods'] ) ? $settings['enabled_methods'] : array( 'bkash', 'nagad', 'rocket', 'upay' );

		// Validate payment method.
		if ( empty( $_POST['rapid_pay_method'] ) ) {
			wc_add_notice( __( 'Please select a payment method.', 'rapid-pay' ), 'error' );
			return false;
		}

		$payment_method = sanitize_text_field( wp_unslash( $_POST['rapid_pay_method'] ) );
		if ( ! in_array( $payment_method, $enabled_methods, true ) ) {
			wc_add_notice( __( 'Invalid payment method selected.', 'rapid-pay' ), 'error' );
			return false;
		}

		// Validate sender phone.
		if ( empty( $_POST['rapid_pay_sender_phone'] ) ) {
			wc_add_notice( __( 'Please enter your sender mobile number.', 'rapid-pay' ), 'error' );
			return false;
		}

		$sender_phone = sanitize_text_field( wp_unslash( $_POST['rapid_pay_sender_phone'] ) );
		if ( ! preg_match( '/^01[0-9]{9}$/', $sender_phone ) ) {
			wc_add_notice( __( 'Please enter a valid mobile number (e.g., 01712345678).', 'rapid-pay' ), 'error' );
			return false;
		}

		// Validate transaction ID.
		if ( empty( $_POST['rapid_pay_transaction_id'] ) ) {
			wc_add_notice( __( 'Please enter the transaction ID.', 'rapid-pay' ), 'error' );
			return false;
		}

		$transaction_id = sanitize_text_field( wp_unslash( $_POST['rapid_pay_transaction_id'] ) );
		if ( strlen( $transaction_id ) < 5 ) {
			wc_add_notice( __( 'Transaction ID must be at least 5 characters long.', 'rapid-pay' ), 'error' );
			return false;
		}

		// Validate amount limits.
		$min_amount = isset( $settings['min_amount'] ) ? floatval( $settings['min_amount'] ) : 0;
		$max_amount = isset( $settings['max_amount'] ) ? floatval( $settings['max_amount'] ) : 0;
		$order_total = WC()->cart->total;

		if ( $min_amount > 0 && $order_total < $min_amount ) {
			/* translators: %s: minimum amount */
			wc_add_notice( sprintf( __( 'Minimum payment amount is %s.', 'rapid-pay' ), wc_price( $min_amount ) ), 'error' );
			return false;
		}

		if ( $max_amount > 0 && $order_total > $max_amount ) {
			/* translators: %s: maximum amount */
			wc_add_notice( sprintf( __( 'Maximum payment amount is %s.', 'rapid-pay' ), wc_price( $max_amount ) ), 'error' );
			return false;
		}

		return true;
	}

	/**
	 * Process the payment.
	 *
	 * @param int $order_id Order ID.
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		// Get payment data.
		$payment_method  = isset( $_POST['rapid_pay_method'] ) ? sanitize_text_field( wp_unslash( $_POST['rapid_pay_method'] ) ) : '';
		$sender_phone    = isset( $_POST['rapid_pay_sender_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['rapid_pay_sender_phone'] ) ) : '';
		$transaction_id  = isset( $_POST['rapid_pay_transaction_id'] ) ? sanitize_text_field( wp_unslash( $_POST['rapid_pay_transaction_id'] ) ) : '';

		// Save order meta.
		$order->update_meta_data( '_rapid_pay_method', $payment_method );
		$order->update_meta_data( '_rapid_pay_sender_phone', $sender_phone );
		$order->update_meta_data( '_rapid_pay_transaction_id', $transaction_id );
		$order->save();

		// Set order status to on-hold.
		$order->update_status( 'on-hold', __( 'Awaiting payment verification.', 'rapid-pay' ) );

		// Add order note.
		$order->add_order_note(
			sprintf(
				/* translators: 1: payment method, 2: sender phone, 3: transaction ID */
				__( 'Payment via %1$s. Sender: %2$s, TrxID: %3$s. Awaiting verification.', 'rapid-pay' ),
				ucfirst( $payment_method ),
				$sender_phone,
				$transaction_id
			)
		);

		// Save to custom table.
		Rapid_Pay_Order_Handler::save_order( $order_id );

		// Reduce stock levels.
		wc_reduce_stock_levels( $order_id );

		// Remove cart.
		WC()->cart->empty_cart();

		// Return success and redirect to thank you page.
		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order ),
		);
	}

	/**
	 * Output for the thank you page.
	 *
	 * @param int $order_id Order ID.
	 */
	public function thankyou_page( $order_id ) {
		if ( $this->instructions ) {
			echo wpautop( wptexturize( wp_kses_post( $this->instructions ) ) );
		}

		$order = wc_get_order( $order_id );
		$payment_method  = $order->get_meta( '_rapid_pay_method' );
		$sender_phone    = $order->get_meta( '_rapid_pay_sender_phone' );
		$transaction_id  = $order->get_meta( '_rapid_pay_transaction_id' );

		echo '<h2>' . esc_html__( 'Payment Details', 'rapid-pay' ) . '</h2>';
		echo '<ul class="woocommerce-order-overview woocommerce-thankyou-order-details order_details">';
		echo '<li class="woocommerce-order-overview__payment-method payment-method"><strong>' . esc_html__( 'Payment Method:', 'rapid-pay' ) . '</strong> ' . esc_html( ucfirst( $payment_method ) ) . '</li>';
		echo '<li class="woocommerce-order-overview__sender-phone sender-phone"><strong>' . esc_html__( 'Sender Phone:', 'rapid-pay' ) . '</strong> ' . esc_html( $sender_phone ) . '</li>';
		echo '<li class="woocommerce-order-overview__transaction-id transaction-id"><strong>' . esc_html__( 'Transaction ID:', 'rapid-pay' ) . '</strong> ' . esc_html( $transaction_id ) . '</li>';
		echo '</ul>';
	}
}
