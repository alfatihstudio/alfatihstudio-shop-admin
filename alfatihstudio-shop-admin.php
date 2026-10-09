<?php
/**
 * Plugin Name: Alfatihstudio Shop Admin
 * Plugin URI: https://alfatihstudio.com
 * Description: Panel pengurusan kedai, banner dan panduan berjenama Alfatihstudio.
 * Version: 0.5.1
 * Author: Alfatihstudio
 * Author URI: https://alfatihstudio.com
 * Requires at least: 6.5
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce
 * WC requires at least: 8.2
 * Text Domain: alfatihstudio-shop-admin
 * License: GPL-2.0-or-later
 */
defined( 'ABSPATH' ) || exit;
define( 'AFS_SHOP_VERSION', '0.5.1' );
define( 'AFS_SHOP_FILE', __FILE__ );
define( 'AFS_SHOP_DIR', plugin_dir_path( __FILE__ ) );
define( 'AFS_SHOP_URL', plugin_dir_url( __FILE__ ) );
require_once AFS_SHOP_DIR . 'includes/class-afs-access.php';
require_once AFS_SHOP_DIR . 'includes/class-afs-banners.php';
require_once AFS_SHOP_DIR . 'includes/class-afs-admin.php';
require_once AFS_SHOP_DIR . 'includes/class-afs-analytics.php';
require_once AFS_SHOP_DIR . 'includes/class-afs-cache.php';
require_once AFS_SHOP_DIR . 'includes/class-afs-guides.php';
require_once AFS_SHOP_DIR . 'includes/class-afs-login.php';
require_once AFS_SHOP_DIR . 'includes/class-afs-media.php';
require_once AFS_SHOP_DIR . 'includes/class-afs-image-optimizer.php';
require_once AFS_SHOP_DIR . 'includes/class-afs-profile.php';
require_once AFS_SHOP_DIR . 'includes/class-afs-catalog.php';
require_once AFS_SHOP_DIR . 'includes/class-afs-authors.php';
register_activation_hook( __FILE__, array( 'AFS_Shop_Access', 'activate' ) );
add_action( 'before_woocommerce_init', static function () {
    if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', AFS_SHOP_FILE, true );
    }
} );
add_action( 'plugins_loaded', static function () {
    load_plugin_textdomain( 'alfatihstudio-shop-admin', false, dirname( plugin_basename( AFS_SHOP_FILE ) ) . '/languages' );
    // Keep client restrictions and banner rendering alive if WooCommerce is temporarily unavailable.
    new AFS_Shop_Access();
    new AFS_Shop_Banners();
    new AFS_Shop_Cache();
    new AFS_Shop_Login();
    new AFS_Shop_Media();
    new AFS_Shop_Image_Optimizer();
    new AFS_Shop_Profile();
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_menu', static function () {
            add_menu_page( 'Pengurusan Kedai', 'Alfatihstudio Shop', 'afs_manage_shop', 'afs-shop', static function () {
                echo '<div class="wrap"><h1>Pengurusan Kedai</h1><p>WooCommerce tidak aktif. Hubungi pentadbir Alfatihstudio untuk memulihkan fungsi kedai.</p></div>';
            }, 'dashicons-store', 2 );
        } );
        add_action( 'admin_notices', static function () {
            if ( current_user_can( 'activate_plugins' ) ) {
                echo '<div class="notice notice-warning"><p>Alfatihstudio Shop Admin memerlukan WooCommerce yang aktif.</p></div>';
            }
        } );
        return;
    }
    new AFS_Shop_Admin();
    new AFS_Shop_Analytics();
    new AFS_Shop_Catalog();
    new AFS_Shop_Authors();
} );
