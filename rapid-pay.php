<?php
/**
 * Plugin Name: Rapid Pay – Bkash Nagad Payment Gateway for WooCommerce
 * Plugin URI: https://bytesvibe.com/rapid-pay
 * Description: Manual payment gateway for WooCommerce supporting bKash, Nagad, Rocket, and Upay with admin dashboard and analytics.
 * Version: 1.0.0
 * Author: Bytes Vibe
 * Author URI: https://bytesvibe.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: rapid-pay
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.5
 * @package RapidPay
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'RAPID_PAY_VERSION', '1.0.0' );
define( 'RAPID_PAY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'RAPID_PAY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'RAPID_PAY_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Main Rapid Pay Class
 */
class Rapid_Pay {

	/**
	 * Instance of this class.
	 *
	 * @var object
	 */
	protected static $instance = null;

	/**
	 * Initialize the plugin.
	 */
	private function __construct() {
		// Check if WooCommerce is active.
		if ( ! $this->is_woocommerce_active() ) {
			add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
			return;
		}

		// Load plugin text domain.
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );

		// Initialize plugin.
		add_action( 'plugins_loaded', array( $this, 'init' ), 11 );

		// Register activation hook.
		register_activation_hook( __FILE__, array( $this, 'activate' ) );

		// Register deactivation hook.
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
	}

	/**
	 * Return an instance of this class.
	 *
	 * @return object A single instance of this class.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Check if WooCommerce is active.
	 *
	 * @return bool
	 */
	private function is_woocommerce_active() {
		return in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true );
	}

	/**
	 * WooCommerce missing notice.
	 */
	public function woocommerce_missing_notice() {
		echo '<div class="error"><p><strong>' . esc_html__( 'Rapid Pay requires WooCommerce to be installed and active.', 'rapid-pay' ) . '</strong></p></div>';
	}

	/**
	 * Load plugin text domain.
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'rapid-pay', false, dirname( RAPID_PAY_PLUGIN_BASENAME ) . '/languages/' );
	}

	/**
	 * Initialize the plugin.
	 */
	public function init() {
		// Include required files.
		$this->includes();

		// Add payment gateway to WooCommerce.
		add_filter( 'woocommerce_payment_gateways', array( $this, 'add_gateway' ) );

		// Add admin menu.
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );

		// Declare WooCommerce compatibility.
		add_action( 'before_woocommerce_init', array( $this, 'declare_woocommerce_compatibility' ) );

		// Enqueue scripts and styles.
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_enqueue_scripts' ) );

		// AJAX handlers.
		add_action( 'wp_ajax_rapid_pay_update_order_status', array( 'Rapid_Pay_Admin', 'ajax_update_order_status' ) );
		add_action( 'wp_ajax_rapid_pay_get_analytics', array( 'Rapid_Pay_Analytics', 'ajax_get_analytics' ) );

		// Cron job for auto-expire orders.
		add_action( 'rapid_pay_expire_orders', array( 'Rapid_Pay_Order_Handler', 'expire_pending_orders' ) );
	}

	/**
	 * Include required files.
	 */
	private function includes() {
		require_once RAPID_PAY_PLUGIN_DIR . 'includes/class-rapid-pay-gateway.php';
		require_once RAPID_PAY_PLUGIN_DIR . 'includes/class-rapid-pay-admin.php';
		require_once RAPID_PAY_PLUGIN_DIR . 'includes/class-rapid-pay-analytics.php';
		require_once RAPID_PAY_PLUGIN_DIR . 'includes/class-rapid-pay-settings.php';
		require_once RAPID_PAY_PLUGIN_DIR . 'includes/class-rapid-pay-order-handler.php';
	}

	/**
	 * Add payment gateway to WooCommerce.
	 *
	 * @param array $gateways Payment gateways.
	 * @return array
	 */
	public function add_gateway( $gateways ) {
		$gateways[] = 'Rapid_Pay_Gateway';
		return $gateways;
	}

	/**
	 * Add admin menu.
	 */
	public function add_admin_menu() {
		add_menu_page(
			__( 'Rapid Pay', 'rapid-pay' ),
			__( 'Rapid Pay', 'rapid-pay' ),
			'manage_woocommerce',
			'rapid-pay',
			array( 'Rapid_Pay_Admin', 'render_dashboard' ),
			'dashicons-cart',
			56
		);

		add_submenu_page(
			'rapid-pay',
			__( 'Dashboard', 'rapid-pay' ),
			__( 'Dashboard', 'rapid-pay' ),
			'manage_woocommerce',
			'rapid-pay',
			array( 'Rapid_Pay_Admin', 'render_dashboard' )
		);

		add_submenu_page(
			'rapid-pay',
			__( 'Settings', 'rapid-pay' ),
			__( 'Settings', 'rapid-pay' ),
			'manage_woocommerce',
			'rapid-pay-settings',
			array( 'Rapid_Pay_Settings', 'render_settings' )
		);
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function admin_enqueue_scripts( $hook ) {
		if ( strpos( $hook, 'rapid-pay' ) === false ) {
			return;
		}

		wp_enqueue_style( 'rapid-pay-admin', RAPID_PAY_PLUGIN_URL . 'assets/css/admin.css', array(), RAPID_PAY_VERSION );
		wp_enqueue_script( 'rapid-pay-admin', RAPID_PAY_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery' ), RAPID_PAY_VERSION, true );

		wp_localize_script(
			'rapid-pay-admin',
			'rapidPayAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'rapid_pay_admin_nonce' ),
			)
		);
	}

	/**
	 * Enqueue frontend scripts and styles.
	 */
	public function frontend_enqueue_scripts() {
		if ( is_checkout() ) {
			wp_enqueue_style( 'rapid-pay-checkout', RAPID_PAY_PLUGIN_URL . 'assets/css/checkout.css', array(), RAPID_PAY_VERSION );
			wp_enqueue_script( 'rapid-pay-checkout', RAPID_PAY_PLUGIN_URL . 'assets/js/checkout.js', array( 'jquery' ), RAPID_PAY_VERSION, true );
		}
	}

	/**
	 * Plugin activation.
	 */
	public function activate() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$table_name      = $wpdb->prefix . 'rapid_pay_orders';

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			order_id bigint(20) NOT NULL,
			customer_name varchar(255) NOT NULL,
			customer_phone varchar(20) NOT NULL,
			sender_phone varchar(20) NOT NULL,
			transaction_id varchar(100) NOT NULL,
			payment_method varchar(50) NOT NULL,
			amount decimal(10,2) NOT NULL,
			status varchar(20) NOT NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY (id),
			KEY order_id (order_id),
			KEY status (status),
			KEY created_at (created_at)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		// Schedule cron job for auto-expire orders.
		if ( ! wp_next_scheduled( 'rapid_pay_expire_orders' ) ) {
			wp_schedule_event( time(), 'hourly', 'rapid_pay_expire_orders' );
		}

		// Set default options.
		$default_options = array(
			'enabled_methods'       => array( 'bkash', 'nagad', 'rocket', 'upay' ),
			'instruction_text'      => __( 'Please complete your payment using one of the available methods and enter your sender mobile number and transaction ID below.', 'rapid-pay' ),
			'admin_phones'          => array( 'bkash' => '', 'nagad' => '', 'rocket' => '', 'upay' => '' ),
			'currency'              => get_woocommerce_currency(),
			'min_amount'            => 0,
			'max_amount'            => 0,
			'auto_expire_enabled'   => 'no',
			'auto_expire_hours'     => 24,
		);

		if ( ! get_option( 'rapid_pay_settings' ) ) {
			add_option( 'rapid_pay_settings', $default_options );
		}
	}

	/**
	 * Declare WooCommerce compatibility.
	 */
	public function declare_woocommerce_compatibility() {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
		}
	}

	/**
	 * Plugin deactivation.
	 */
	public function deactivate() {
		// Clear scheduled cron job.
		$timestamp = wp_next_scheduled( 'rapid_pay_expire_orders' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'rapid_pay_expire_orders' );
		}
	}
}

// Initialize the plugin.
Rapid_Pay::get_instance();
