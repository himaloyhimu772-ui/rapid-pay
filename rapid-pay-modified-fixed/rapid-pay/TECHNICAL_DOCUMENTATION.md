# Rapid Pay – Technical Documentation

## Plugin Architecture

### Overview

Rapid Pay is built following WordPress and WooCommerce coding standards, utilizing object-oriented programming principles and the MVC-like pattern common in WordPress plugin development.

### Core Components

#### 1. Main Plugin Class (`Rapid_Pay`)

**File**: `rapid-pay.php`

**Responsibilities**:
- Plugin initialization and bootstrapping
- Dependency checking (WooCommerce)
- Loading text domain for translations
- Including required class files
- Registering hooks and filters
- Database table creation on activation
- Cron job scheduling
- Admin menu registration
- Asset enqueueing

**Key Methods**:
- `get_instance()`: Singleton pattern implementation
- `is_woocommerce_active()`: Check WooCommerce dependency
- `init()`: Initialize plugin after WordPress loads
- `includes()`: Load all required class files
- `add_gateway()`: Register payment gateway with WooCommerce
- `activate()`: Plugin activation hook handler
- `deactivate()`: Plugin deactivation hook handler

#### 2. Payment Gateway Class (`Rapid_Pay_Gateway`)

**File**: `includes/class-rapid-pay-gateway.php`

**Extends**: `WC_Payment_Gateway`

**Responsibilities**:
- Implement WooCommerce Payment Gateway API
- Render payment fields on checkout
- Validate customer input
- Process payment submission
- Store order metadata
- Set order status
- Display thank you page content

**Key Methods**:
- `__construct()`: Initialize gateway settings
- `init_form_fields()`: Define WooCommerce settings fields
- `payment_fields()`: Render checkout form fields
- `validate_fields()`: Validate customer input
- `process_payment()`: Process order and payment data
- `thankyou_page()`: Display payment details on thank you page

**Validation Rules**:
- Payment method: Must be in enabled methods array
- Sender phone: Must match pattern `/^01[0-9]{9}$/` (Bangladesh format)
- Transaction ID: Minimum 5 characters
- Amount: Must be within min/max limits if set

#### 3. Order Handler Class (`Rapid_Pay_Order_Handler`)

**File**: `includes/class-rapid-pay-order-handler.php`

**Responsibilities**:
- Manage custom database table operations
- Save and update order records
- Retrieve orders with filtering
- Update order status
- Sync with WooCommerce orders
- Handle auto-expiration of pending orders

**Key Methods**:
- `save_order()`: Insert or update order in custom table
- `get_orders()`: Retrieve orders with filters
- `get_orders_count()`: Get total count of orders
- `update_order_status()`: Update order status in both systems
- `expire_pending_orders()`: Cron job handler for auto-expiration

**Database Operations**:
- Uses `$wpdb` for database queries
- Implements prepared statements for security
- Handles both INSERT and UPDATE operations
- Supports filtering by status and date range

#### 4. Admin Dashboard Class (`Rapid_Pay_Admin`)

**File**: `includes/class-rapid-pay-admin.php`

**Responsibilities**:
- Render admin dashboard page
- Display analytics cards
- Show orders table with filters
- Handle AJAX status updates
- Provide chart data to JavaScript

**Key Methods**:
- `render_dashboard()`: Main dashboard rendering
- `ajax_update_order_status()`: AJAX handler for status changes

**Security Measures**:
- Nonce verification on AJAX requests
- Capability checks (`manage_woocommerce`)
- Input sanitization
- Output escaping

#### 5. Analytics Class (`Rapid_Pay_Analytics`)

**File**: `includes/class-rapid-pay-analytics.php`

**Responsibilities**:
- Calculate earnings statistics
- Generate chart data
- Provide date range analytics
- Track payment method performance
- Order statistics

**Key Methods**:
- `get_analytics_data()`: Get comprehensive analytics
- `get_chart_data()`: Generate 30-day chart data
- `get_earnings_by_method()`: Earnings breakdown by payment method
- `get_order_statistics()`: Order count statistics
- `get_custom_range_analytics()`: Custom date range analytics
- `ajax_get_analytics()`: AJAX handler for analytics requests

**Calculations**:
- Today: Current date 00:00:00 to 23:59:59
- Week: Monday to Sunday of current week
- Month: First day to last day of current month
- Total: All completed orders
- Chart: Last 30 days, one data point per day

#### 6. Settings Class (`Rapid_Pay_Settings`)

**File**: `includes/class-rapid-pay-settings.php`

**Responsibilities**:
- Render settings page
- Handle settings form submission
- Validate and sanitize settings
- Display system information

**Key Methods**:
- `render_settings()`: Render settings page HTML
- `save_settings()`: Process and save settings

**Settings Structure**:
```php
array(
    'enabled_methods'     => array('bkash', 'nagad', 'rocket', 'upay'),
    'instruction_text'    => string,
    'admin_phones'        => string,
    'min_amount'          => float,
    'max_amount'          => float,
    'auto_expire_enabled' => 'yes'|'no',
    'auto_expire_hours'   => int (1-168)
)
```

## Database Schema

### Table: `wp_rapid_pay_orders`

```sql
CREATE TABLE wp_rapid_pay_orders (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Indexes**:
- Primary key on `id`
- Index on `order_id` for fast lookups
- Index on `status` for filtering
- Index on `created_at` for date range queries

**Relationships**:
- `order_id` references WooCommerce `wp_posts` table (post_type = 'shop_order')

## Order Meta Keys

Rapid Pay stores the following meta keys in WooCommerce orders:

| Meta Key | Description | Type |
|----------|-------------|------|
| `_rapid_pay_method` | Payment method used | string (bkash/nagad/rocket/upay) |
| `_rapid_pay_sender_phone` | Sender mobile number | string (11 digits) |
| `_rapid_pay_transaction_id` | Transaction ID | string |

## Hooks Reference

### Actions

#### Plugin Initialization
```php
add_action('plugins_loaded', array($this, 'load_textdomain'));
add_action('plugins_loaded', array($this, 'init'), 11);
```

#### Admin
```php
add_action('admin_menu', array($this, 'add_admin_menu'));
add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
```

#### Frontend
```php
add_action('wp_enqueue_scripts', array($this, 'frontend_enqueue_scripts'));
```

#### AJAX
```php
add_action('wp_ajax_rapid_pay_update_order_status', array('Rapid_Pay_Admin', 'ajax_update_order_status'));
add_action('wp_ajax_rapid_pay_get_analytics', array('Rapid_Pay_Analytics', 'ajax_get_analytics'));
```

#### Cron
```php
add_action('rapid_pay_expire_orders', array('Rapid_Pay_Order_Handler', 'expire_pending_orders'));
```

#### WooCommerce Gateway
```php
add_action('woocommerce_update_options_payment_gateways_rapid_pay', array($this, 'process_admin_options'));
add_action('woocommerce_thankyou_rapid_pay', array($this, 'thankyou_page'));
```

### Filters

#### Payment Gateway Registration
```php
add_filter('woocommerce_payment_gateways', array($this, 'add_gateway'));
```

## Security Implementation

### 1. Direct Access Protection

All PHP files include:
```php
if (!defined('ABSPATH')) {
    exit;
}
```

### 2. Nonce Verification

**Admin Forms**:
```php
wp_nonce_field('rapid_pay_settings_nonce');
check_admin_referer('rapid_pay_settings_nonce');
```

**AJAX Requests**:
```php
wp_create_nonce('rapid_pay_admin_nonce');
check_ajax_referer('rapid_pay_admin_nonce', 'nonce');
```

### 3. Capability Checks

```php
if (!current_user_can('manage_woocommerce')) {
    wp_die(__('You do not have sufficient permissions...'));
}
```

### 4. Input Sanitization

```php
sanitize_text_field()      // Single line text
sanitize_textarea_field()  // Multi-line text
sanitize_email()           // Email addresses
intval()                   // Integers
floatval()                 // Floats
array_map('sanitize_text_field', $_POST['array'])  // Arrays
```

### 5. Output Escaping

```php
esc_html()      // HTML content
esc_attr()      // HTML attributes
esc_url()       // URLs
esc_js()        // JavaScript
wp_kses_post()  // Post content with allowed HTML
```

### 6. Database Security

```php
$wpdb->prepare("SELECT * FROM table WHERE id = %d", $id);
```

## Asset Management

### CSS Files

**Admin CSS** (`assets/css/admin.css`):
- Dashboard cards styling
- Chart container
- Orders table
- Status badges
- Filters form
- Settings page
- Responsive design

**Checkout CSS** (`assets/css/checkout.css`):
- Payment instructions box
- Admin phone numbers display
- Form field styling
- Thank you page
- Mobile responsive

### JavaScript Files

**Admin JS** (`assets/js/admin.js`):
- Chart rendering (Canvas API)
- AJAX status updates
- Notice display
- Form handling

**Checkout JS** (`assets/js/checkout.js`):
- Phone number formatting
- Field validation
- Real-time error display
- Form submission validation

### Enqueue Strategy

**Admin**:
```php
wp_enqueue_style('rapid-pay-admin', ..., array(), RAPID_PAY_VERSION);
wp_enqueue_script('rapid-pay-admin', ..., array('jquery'), RAPID_PAY_VERSION, true);
wp_localize_script('rapid-pay-admin', 'rapidPayAdmin', array(
    'ajaxUrl' => admin_url('admin-ajax.php'),
    'nonce'   => wp_create_nonce('rapid_pay_admin_nonce')
));
```

**Frontend**:
```php
if (is_checkout()) {
    wp_enqueue_style('rapid-pay-checkout', ...);
    wp_enqueue_script('rapid-pay-checkout', ...);
}
```

## AJAX Implementation

### Update Order Status

**Endpoint**: `wp_ajax_rapid_pay_update_order_status`

**Request**:
```javascript
{
    action: 'rapid_pay_update_order_status',
    nonce: rapidPayAdmin.nonce,
    order_id: 123,
    status: 'completed'
}
```

**Response**:
```javascript
{
    success: true,
    data: {
        message: 'Order status updated successfully.'
    }
}
```

### Get Analytics

**Endpoint**: `wp_ajax_rapid_pay_get_analytics`

**Request**:
```javascript
{
    action: 'rapid_pay_get_analytics',
    nonce: rapidPayAdmin.nonce,
    period: 'custom',
    date_from: '2024-01-01',
    date_to: '2024-01-31'
}
```

**Response**:
```javascript
{
    success: true,
    data: {
        earnings: 15000.50,
        orders: 45
    }
}
```

## Cron Jobs

### Auto-Expire Pending Orders

**Hook**: `rapid_pay_expire_orders`  
**Schedule**: Hourly  
**Function**: `Rapid_Pay_Order_Handler::expire_pending_orders()`

**Logic**:
1. Check if auto-expire is enabled in settings
2. Get expiration hours from settings
3. Calculate cutoff date
4. Query orders with status 'on-hold' older than cutoff
5. Update each order status to 'cancelled'

**Registration**:
```php
if (!wp_next_scheduled('rapid_pay_expire_orders')) {
    wp_schedule_event(time(), 'hourly', 'rapid_pay_expire_orders');
}
```

**Cleanup**:
```php
$timestamp = wp_next_scheduled('rapid_pay_expire_orders');
if ($timestamp) {
    wp_unschedule_event($timestamp, 'rapid_pay_expire_orders');
}
```

## Chart Implementation

### Technology

- **HTML5 Canvas API**: Native browser rendering
- **No External Libraries**: Pure JavaScript implementation
- **Responsive**: Adapts to container width
- **Gradient Fill**: Visual appeal with gradient background

### Data Structure

```javascript
{
    labels: ['Dec 01', 'Dec 02', ...],
    data: [150.50, 200.00, ...]
}
```

### Rendering Process

1. Get canvas context
2. Calculate max value for scaling
3. Calculate point positions
4. Draw grid lines
5. Draw gradient area fill
6. Draw line connecting points
7. Draw data points as circles
8. Draw x-axis labels

## Translation Implementation

### Text Domain

`rapid-pay`

### Loading

```php
load_plugin_textdomain('rapid-pay', false, dirname(RAPID_PAY_PLUGIN_BASENAME) . '/languages/');
```

### Functions Used

```php
__('Text', 'rapid-pay')                    // Return translated
_e('Text', 'rapid-pay')                    // Echo translated
esc_html__('Text', 'rapid-pay')            // Return escaped translated
esc_html_e('Text', 'rapid-pay')            // Echo escaped translated
sprintf(__('Text %s', 'rapid-pay'), $var)  // With variables
```

### POT File

Location: `languages/rapid-pay.pot`  
Contains all translatable strings for use with Poedit or similar tools.

## Performance Considerations

### Database Optimization

1. **Indexes**: Strategic indexes on frequently queried columns
2. **Prepared Statements**: Prevent SQL injection and optimize queries
3. **Limit Queries**: Default limit of 100 orders per page
4. **Selective Columns**: Only select needed columns

### Asset Loading

1. **Conditional Loading**: Only load assets on relevant pages
2. **Minification Ready**: Assets structured for minification
3. **Version Control**: Cache busting with plugin version
4. **No External CDN**: All assets self-hosted for performance and privacy

### Caching Considerations

- Settings stored in `wp_options` (cached by WordPress)
- Order data cached by WooCommerce
- Custom table data not cached (always fresh)

## Extensibility

### Custom Hooks (Future Enhancement)

Developers can add these hooks for extensibility:

```php
// Before saving order
do_action('rapid_pay_before_save_order', $order_id, $order_data);

// After saving order
do_action('rapid_pay_after_save_order', $order_id, $order_data);

// Filter payment methods
$methods = apply_filters('rapid_pay_payment_methods', $methods);

// Filter validation rules
$rules = apply_filters('rapid_pay_validation_rules', $rules);
```

## Testing Checklist

### Functional Testing

- [ ] Plugin activation/deactivation
- [ ] WooCommerce dependency check
- [ ] Payment gateway registration
- [ ] Checkout form display
- [ ] Field validation
- [ ] Order creation
- [ ] Order status updates
- [ ] Analytics calculations
- [ ] Chart rendering
- [ ] Settings save/load
- [ ] Auto-expire functionality

### Security Testing

- [ ] SQL injection prevention
- [ ] XSS prevention
- [ ] CSRF protection (nonces)
- [ ] Capability checks
- [ ] Direct file access protection

### Compatibility Testing

- [ ] WordPress 5.8+
- [ ] WooCommerce 5.0+
- [ ] PHP 7.4+
- [ ] MySQL 5.6+
- [ ] Common themes
- [ ] Common plugins

## Deployment Checklist

- [ ] Remove development/debug code
- [ ] Update version numbers
- [ ] Test on clean WordPress installation
- [ ] Verify all translations
- [ ] Check file permissions
- [ ] Validate against WordPress coding standards
- [ ] Test plugin activation/deactivation
- [ ] Verify database table creation
- [ ] Test uninstall cleanup
- [ ] Create plugin banner and icon
- [ ] Prepare screenshots
- [ ] Write comprehensive readme.txt

---

**Version**: 1.0.0  
**Last Updated**: December 2024  
**Maintained By**: Plugin Development Team
