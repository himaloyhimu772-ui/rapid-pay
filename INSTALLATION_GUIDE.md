# Rapid Pay – Installation and Usage Guide

## Overview

**Rapid Pay** is a complete WordPress plugin that integrates with WooCommerce to provide manual payment processing through popular mobile financial services in Bangladesh: bKash, Nagad, Rocket, and Upay.

## Features

### Core Functionality
- **WooCommerce Integration**: Seamlessly integrates as a payment gateway using the official WooCommerce Payment Gateway API
- **Multiple Payment Methods**: Support for bKash, Nagad, Rocket, and Upay
- **Required Checkout Fields**: Collects sender mobile number and transaction ID
- **Order Status Management**: Sets orders to "On Hold" status awaiting verification
- **Automatic Order Notes**: Adds detailed payment information to order notes

### Admin Dashboard
- **Centralized Order Management**: View all Rapid Pay orders in one place
- **Comprehensive Order Table**: Displays order ID, customer name, phone, sender phone, transaction ID, amount, payment method, date, and status
- **Quick Status Updates**: Change order status directly from the dashboard (Completed, Refunded, Cancelled, Pending)
- **WooCommerce Sync**: All status changes sync with WooCommerce orders
- **Filtering Options**: Filter by status and date range

### Analytics & Reporting
- **Earnings Dashboard**: Display daily, weekly, monthly, and total earnings
- **Visual Income Chart**: 30-day income trend chart with no external CDN dependencies
- **Date Range Filtering**: Custom date range analytics
- **Payment Method Breakdown**: Track earnings by payment method

### Settings & Configuration
- **Enable/Disable Methods**: Toggle individual payment methods on/off
- **Custom Instructions**: Add custom checkout instructions for customers
- **Admin Phone Numbers**: Display payment receiving numbers on checkout
- **Amount Limits**: Set minimum and maximum payment amounts
- **Auto-Expire Orders**: Automatically cancel pending orders after specified hours (1-168 hours)

### Security & Standards
- **WordPress Coding Standards**: Follows official WordPress PHP coding standards
- **Input Sanitization**: All user inputs are sanitized using WordPress functions
- **Output Escaping**: All outputs are properly escaped
- **Nonce Verification**: CSRF protection on all forms and AJAX requests
- **Capability Checks**: Proper permission checks (manage_woocommerce)
- **Prepared Statements**: SQL injection prevention
- **Direct Access Protection**: ABSPATH checks in all files

### Design & UX
- **Modern UI**: Clean, professional admin interface
- **Responsive Design**: Works perfectly on desktop, tablet, and mobile
- **WordPress Admin Styles**: Uses native WordPress styling for consistency
- **No External Dependencies**: All assets are self-hosted, no CDN required

## Installation

### Method 1: Upload via WordPress Admin

1. Download the `rapid-pay.zip` file
2. Log in to your WordPress admin panel
3. Navigate to **Plugins > Add New**
4. Click **Upload Plugin** button
5. Choose the `rapid-pay.zip` file
6. Click **Install Now**
7. After installation, click **Activate Plugin**

### Method 2: Manual Installation via FTP

1. Download and extract the `rapid-pay.zip` file
2. Upload the `rapid-pay` folder to `/wp-content/plugins/` directory
3. Log in to WordPress admin panel
4. Navigate to **Plugins**
5. Find **Rapid Pay** in the list and click **Activate**

### Prerequisites

- WordPress 5.8 or higher
- WooCommerce 5.0 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher

## Configuration

### Step 1: Enable the Payment Gateway

1. Go to **WooCommerce > Settings > Payments**
2. Find **Rapid Pay** in the list
3. Toggle the switch to **Enable**
4. Click **Manage** to configure gateway settings
5. Set the **Title** (shown to customers at checkout)
6. Set the **Description** (payment method description)
7. Set **Instructions** (shown on thank you page)
8. Click **Save changes**

### Step 2: Configure Plugin Settings

1. Go to **Rapid Pay > Settings** in WordPress admin
2. **Enabled Payment Methods**: Check the methods you want to offer (bKash, Nagad, Rocket, Upay)
3. **Checkout Instructions**: Enter instructions for customers (e.g., "Please send payment to the numbers below")
4. **Admin Phone Numbers**: Enter your payment receiving numbers (one per line)
   ```
   bKash: 01712345678
   Nagad: 01812345678
   Rocket: 01912345678
   ```
5. **Minimum Payment Amount**: Set minimum order amount (0 to disable)
6. **Maximum Payment Amount**: Set maximum order amount (0 to disable)
7. **Auto Expire Pending Orders**: Enable to automatically cancel old pending orders
8. **Auto Expire After (Hours)**: Set expiration time (1-168 hours)
9. Click **Save Settings**

## Usage

### For Customers (Checkout Process)

1. Customer adds products to cart and proceeds to checkout
2. At checkout, customer selects **Rapid Pay** as payment method
3. Customer sees payment instructions and admin phone numbers
4. Customer completes payment using their mobile wallet (bKash, Nagad, etc.)
5. Customer selects the payment method used
6. Customer enters their sender mobile number (e.g., 01712345678)
7. Customer enters the transaction ID received from mobile wallet
8. Customer places order
9. Order is created with "On Hold" status
10. Customer receives order confirmation with payment details

### For Admins (Order Management)

#### View Orders

1. Go to **Rapid Pay > Dashboard**
2. View analytics cards showing:
   - Today's earnings
   - This week's earnings
   - This month's earnings
   - Total earnings
3. View the 30-day income chart
4. Scroll down to see the orders table

#### Filter Orders

1. Use the filter form above the orders table
2. Select status (All, On Hold, Completed, Refunded, Cancelled, Pending)
3. Select date range (From Date and To Date)
4. Click **Filter** button
5. Click **Reset** to clear filters

#### Update Order Status

1. Find the order in the table
2. In the **Actions** column, select new status from dropdown:
   - **Mark Completed**: Payment verified, order fulfilled
   - **Mark Refunded**: Payment refunded to customer
   - **Mark Cancelled**: Order cancelled
   - **Mark Pending**: Move back to pending status
3. Confirm the action
4. Status updates in both Rapid Pay and WooCommerce
5. Order note is automatically added

#### View Order Details in WooCommerce

1. Click the order ID link in the table
2. Opens the WooCommerce order edit page in new tab
3. View full order details, customer information, and order notes

## Database Structure

The plugin creates a custom table `wp_rapid_pay_orders` with the following structure:

| Column | Type | Description |
|--------|------|-------------|
| id | bigint(20) | Primary key, auto increment |
| order_id | bigint(20) | WooCommerce order ID |
| customer_name | varchar(255) | Customer full name |
| customer_phone | varchar(20) | Customer phone number |
| sender_phone | varchar(20) | Payment sender phone |
| transaction_id | varchar(100) | Transaction ID from mobile wallet |
| payment_method | varchar(50) | Payment method (bkash/nagad/rocket/upay) |
| amount | decimal(10,2) | Order amount |
| status | varchar(20) | Order status |
| created_at | datetime | Order creation date |
| updated_at | datetime | Last update date |

## File Structure

```
rapid-pay/
├── rapid-pay.php                          # Main plugin file
├── readme.txt                             # WordPress plugin readme
├── LICENSE                                # GPL v2 license
├── includes/
│   ├── class-rapid-pay-gateway.php       # WooCommerce payment gateway
│   ├── class-rapid-pay-admin.php         # Admin dashboard
│   ├── class-rapid-pay-analytics.php     # Analytics and reporting
│   ├── class-rapid-pay-settings.php      # Settings page
│   └── class-rapid-pay-order-handler.php # Order management
├── assets/
│   ├── css/
│   │   ├── admin.css                     # Admin styles
│   │   └── checkout.css                  # Checkout styles
│   └── js/
│       ├── admin.js                      # Admin scripts
│       └── checkout.js                   # Checkout scripts
└── languages/
    └── rapid-pay.pot                     # Translation template
```

## Hooks and Filters

### Actions

- `woocommerce_payment_gateways`: Adds Rapid Pay to WooCommerce payment gateways
- `admin_menu`: Adds Rapid Pay menu to WordPress admin
- `admin_enqueue_scripts`: Enqueues admin assets
- `wp_enqueue_scripts`: Enqueues frontend assets
- `wp_ajax_rapid_pay_update_order_status`: AJAX handler for status updates
- `wp_ajax_rapid_pay_get_analytics`: AJAX handler for analytics
- `rapid_pay_expire_orders`: Cron job for auto-expiring orders

### Filters

- `woocommerce_payment_gateways`: Filter to add gateway class

## Translation

The plugin is translation-ready. To translate:

1. Copy `languages/rapid-pay.pot` file
2. Use Poedit or similar tool to create `.po` and `.mo` files
3. Save as `rapid-pay-{locale}.po` and `rapid-pay-{locale}.mo`
4. Place in the `languages/` folder
5. Example: `rapid-pay-bn_BD.po` for Bengali

## Troubleshooting

### Plugin not appearing in WooCommerce Payments

**Solution**: Make sure WooCommerce is installed and activated before activating Rapid Pay.

### Orders not showing in dashboard

**Solution**: Check that the database table was created. Go to Settings page and verify the database table name is shown.

### Chart not displaying

**Solution**: Clear browser cache and reload the page. The chart uses HTML5 Canvas and requires JavaScript to be enabled.

### Status updates not working

**Solution**: Check that you have the `manage_woocommerce` capability. Only shop managers and administrators can update order status.

### Auto-expire not working

**Solution**: Make sure WordPress cron is working. Check that the setting is enabled and the hourly cron job is scheduled.

## Support

For support, feature requests, or bug reports:

1. Check the documentation first
2. Review the FAQ in readme.txt
3. Contact the plugin developer
4. Visit the WordPress plugin support forum

## License

This plugin is licensed under GPL v2 or later.

## Credits

Developed following WordPress and WooCommerce best practices for the e-commerce community.

---

**Version**: 1.0.0  
**Last Updated**: December 2024  
**Compatibility**: WordPress 5.8+, WooCommerce 5.0+, PHP 7.4+
