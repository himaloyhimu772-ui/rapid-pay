<?php
/**
 * Rapid Pay Settings
 *
 * @package RapidPay
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Rapid Pay Settings Class
 */
class Rapid_Pay_Settings {

	/**
	 * Render settings page.
	 */
	public static function render_settings() {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'rapid-pay' ) );
		}

		// Save settings.
		if ( isset( $_POST['rapid_pay_save_settings'] ) && check_admin_referer( 'rapid_pay_settings_nonce' ) ) {
			self::save_settings();
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Settings saved successfully.', 'rapid-pay' ) . '</p></div>';
		}

		// Get current settings.
		$settings = get_option( 'rapid_pay_settings', array() );

		$enabled_methods = isset( $settings['enabled_methods'] ) ? $settings['enabled_methods'] : array( 'bkash', 'nagad', 'rocket', 'upay' );
		$instruction_text = isset( $settings['instruction_text'] ) ? $settings['instruction_text'] : '';
		$admin_phones = isset( $settings['admin_phones'] ) ? $settings['admin_phones'] : array();
	$currency = isset( $settings['currency'] ) ? $settings['currency'] : get_woocommerce_currency();
		$min_amount = isset( $settings['min_amount'] ) ? $settings['min_amount'] : 0;
		$max_amount = isset( $settings['max_amount'] ) ? $settings['max_amount'] : 0;
		$auto_expire_enabled = isset( $settings['auto_expire_enabled'] ) ? $settings['auto_expire_enabled'] : 'no';
		$auto_expire_hours = isset( $settings['auto_expire_hours'] ) ? $settings['auto_expire_hours'] : 24;

		?>
		<div class="wrap rapid-pay-settings">
			<h1><?php esc_html_e( 'Rapid Pay Settings', 'rapid-pay' ); ?></h1>

			<form method="post" action="">
				<?php wp_nonce_field( 'rapid_pay_settings_nonce' ); ?>

				<table class="form-table">
					<!-- Payment Methods -->
					<tr>
						<th scope="row">
							<label><?php esc_html_e( 'Enabled Payment Methods', 'rapid-pay' ); ?></label>
						</th>
						<td>
							<fieldset>
								<label>
									<input type="checkbox" name="enabled_methods[]" value="bkash" <?php checked( in_array( 'bkash', $enabled_methods, true ) ); ?> />
									<?php esc_html_e( 'bKash', 'rapid-pay' ); ?>
								</label><br />
								<label>
									<input type="checkbox" name="enabled_methods[]" value="nagad" <?php checked( in_array( 'nagad', $enabled_methods, true ) ); ?> />
									<?php esc_html_e( 'Nagad', 'rapid-pay' ); ?>
								</label><br />
								<label>
									<input type="checkbox" name="enabled_methods[]" value="rocket" <?php checked( in_array( 'rocket', $enabled_methods, true ) ); ?> />
									<?php esc_html_e( 'Rocket', 'rapid-pay' ); ?>
								</label><br />
								<label>
									<input type="checkbox" name="enabled_methods[]" value="upay" <?php checked( in_array( 'upay', $enabled_methods, true ) ); ?> />
									<?php esc_html_e( 'Upay', 'rapid-pay' ); ?>
								</label>
								<p class="description"><?php esc_html_e( 'Select which payment methods to enable on checkout.', 'rapid-pay' ); ?></p>
							</fieldset>
						</td>
					</tr>

					<!-- Instruction Text -->
					<tr>
						<th scope="row">
							<label for="instruction_text"><?php esc_html_e( 'Checkout Instructions', 'rapid-pay' ); ?></label>
						</th>
						<td>
							<textarea id="instruction_text" name="instruction_text" rows="5" class="large-text"><?php echo esc_textarea( $instruction_text ); ?></textarea>
							<p class="description"><?php esc_html_e( 'Instructions displayed on the checkout page.', 'rapid-pay' ); ?></p>
						</td>
					</tr>

					<!-- Admin Phone Numbers per Method -->
					<tr>
						<th scope="row">
							<label><?php esc_html_e( 'Payment Receiving Numbers', 'rapid-pay' ); ?></label>
						</th>
						<td>
							<?php
							$methods = array(
								'bkash'  => __( 'bKash', 'rapid-pay' ),
								'nagad'  => __( 'Nagad', 'rapid-pay' ),
								'rocket' => __( 'Rocket', 'rapid-pay' ),
								'upay'   => __( 'Upay', 'rapid-pay' ),
							);
							foreach ( $methods as $key => $label ) :
								$phone = isset( $admin_phones[ $key ] ) ? $admin_phones[ $key ] : '';
								?>
								<p>
									<label for="admin_phones_<?php echo esc_attr( $key ); ?>">
										<strong><?php echo esc_html( $label ); ?>:</strong>
									</label>
									<input type="text" id="admin_phones_<?php echo esc_attr( $key ); ?>" name="admin_phones[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $phone ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'e.g., 017XXXXXXXXX', 'rapid-pay' ); ?>" />
								</p>
							<?php endforeach; ?>
							<p class="description"><?php esc_html_e( 'Enter the payment receiving mobile number for each method. This will be shown to the customer when they select the method.', 'rapid-pay' ); ?></p>
						</td>
					</tr>

					<!-- Currency Setting -->
					<tr>
						<th scope="row">
							<label for="currency"><?php esc_html_e( 'Dashboard Currency', 'rapid-pay' ); ?></label>
						</th>
						<td>
							<select id="currency" name="currency">
								<?php
								$currencies = get_woocommerce_currencies();
								foreach ( $currencies as $code => $name ) {
									echo '<option value="' . esc_attr( $code ) . '" ' . selected( $currency, $code, false ) . '>' . esc_html( $name ) . ' (' . esc_html( get_woocommerce_currency_symbol( $code ) ) . ')</option>';
								}
								?>
							</select>
							<p class="description"><?php esc_html_e( 'Select the currency to display in the Rapid Pay dashboard analytics and tables.', 'rapid-pay' ); ?></p>
						</td>
					</tr>

					<!-- Minimum Amount -->
					<tr>
						<th scope="row">
							<label for="min_amount"><?php esc_html_e( 'Minimum Payment Amount', 'rapid-pay' ); ?></label>
						</th>
						<td>
							<input type="number" id="min_amount" name="min_amount" value="<?php echo esc_attr( $min_amount ); ?>" step="0.01" min="0" class="regular-text" />
							<p class="description"><?php esc_html_e( 'Set minimum order amount for this payment method. Set to 0 to disable.', 'rapid-pay' ); ?></p>
						</td>
					</tr>

					<!-- Maximum Amount -->
					<tr>
						<th scope="row">
							<label for="max_amount"><?php esc_html_e( 'Maximum Payment Amount', 'rapid-pay' ); ?></label>
						</th>
						<td>
							<input type="number" id="max_amount" name="max_amount" value="<?php echo esc_attr( $max_amount ); ?>" step="0.01" min="0" class="regular-text" />
							<p class="description"><?php esc_html_e( 'Set maximum order amount for this payment method. Set to 0 to disable.', 'rapid-pay' ); ?></p>
						</td>
					</tr>

					<!-- Auto Expire -->
					<tr>
						<th scope="row">
							<label for="auto_expire_enabled"><?php esc_html_e( 'Auto Expire Pending Orders', 'rapid-pay' ); ?></label>
						</th>
						<td>
							<label>
								<input type="checkbox" id="auto_expire_enabled" name="auto_expire_enabled" value="yes" <?php checked( $auto_expire_enabled, 'yes' ); ?> />
								<?php esc_html_e( 'Enable automatic cancellation of pending orders', 'rapid-pay' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Automatically cancel orders that remain on-hold after specified hours.', 'rapid-pay' ); ?></p>
						</td>
					</tr>

					<!-- Auto Expire Hours -->
					<tr>
						<th scope="row">
							<label for="auto_expire_hours"><?php esc_html_e( 'Auto Expire After (Hours)', 'rapid-pay' ); ?></label>
						</th>
						<td>
							<input type="number" id="auto_expire_hours" name="auto_expire_hours" value="<?php echo esc_attr( $auto_expire_hours ); ?>" min="1" max="168" class="regular-text" />
							<p class="description"><?php esc_html_e( 'Number of hours after which pending orders will be automatically cancelled (1-168 hours).', 'rapid-pay' ); ?></p>
						</td>
					</tr>
				</table>

				<p class="submit">
					<input type="submit" name="rapid_pay_save_settings" class="button button-primary" value="<?php esc_attr_e( 'Save Settings', 'rapid-pay' ); ?>" />
				</p>
			</form>

			<!-- System Information -->
			<div class="rapid-pay-system-info">
				<h2><?php esc_html_e( 'System Information', 'rapid-pay' ); ?></h2>
				<table class="widefat">
					<tbody>
						<tr>
							<td><strong><?php esc_html_e( 'Plugin Version:', 'rapid-pay' ); ?></strong></td>
							<td><?php echo esc_html( RAPID_PAY_VERSION ); ?></td>
						</tr>
						<tr>
							<td><strong><?php esc_html_e( 'WordPress Version:', 'rapid-pay' ); ?></strong></td>
							<td><?php echo esc_html( get_bloginfo( 'version' ) ); ?></td>
						</tr>
						<tr>
							<td><strong><?php esc_html_e( 'WooCommerce Version:', 'rapid-pay' ); ?></strong></td>
							<td><?php echo esc_html( defined( 'WC_VERSION' ) ? WC_VERSION : 'N/A' ); ?></td>
						</tr>
						<tr>
							<td><strong><?php esc_html_e( 'PHP Version:', 'rapid-pay' ); ?></strong></td>
							<td><?php echo esc_html( phpversion() ); ?></td>
						</tr>
					</tbody>
				</table>
				<p class="rapid-pay-built-with">
					<?php esc_html_e( 'Built with', 'rapid-pay' ); ?> <span style="color: #dc3232;">&hearts;</span> <?php esc_html_e( 'by', 'rapid-pay' ); ?> <a href="https://bytesvibe.com" target="_blank">Bytes Vibe</a>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Save settings.
	 */
	private static function save_settings() {
		$enabled_methods = isset( $_POST['enabled_methods'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['enabled_methods'] ) ) : array();
		$instruction_text = isset( $_POST['instruction_text'] ) ? sanitize_textarea_field( wp_unslash( $_POST['instruction_text'] ) ) : '';
		$admin_phones = isset( $_POST['admin_phones'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['admin_phones'] ) ) : array();
		$currency = isset( $_POST['currency'] ) ? sanitize_text_field( wp_unslash( $_POST['currency'] ) ) : get_woocommerce_currency();
		$min_amount = isset( $_POST['min_amount'] ) ? floatval( $_POST['min_amount'] ) : 0;
		$max_amount = isset( $_POST['max_amount'] ) ? floatval( $_POST['max_amount'] ) : 0;
		$auto_expire_enabled = isset( $_POST['auto_expire_enabled'] ) ? 'yes' : 'no';
		$auto_expire_hours = isset( $_POST['auto_expire_hours'] ) ? intval( $_POST['auto_expire_hours'] ) : 24;

		// Validate enabled methods.
		$valid_methods = array( 'bkash', 'nagad', 'rocket', 'upay' );
		$enabled_methods = array_intersect( $enabled_methods, $valid_methods );

		// Validate auto expire hours.
		if ( $auto_expire_hours < 1 ) {
			$auto_expire_hours = 1;
		} elseif ( $auto_expire_hours > 168 ) {
			$auto_expire_hours = 168;
		}

		$settings = array(
			'enabled_methods'     => $enabled_methods,
			'instruction_text'    => $instruction_text,
			'admin_phones'        => $admin_phones,
			'currency'            => $currency,
			'min_amount'          => $min_amount,
			'max_amount'          => $max_amount,
			'auto_expire_enabled' => $auto_expire_enabled,
			'auto_expire_hours'   => $auto_expire_hours,
		);

		update_option( 'rapid_pay_settings', $settings );
	}
}
