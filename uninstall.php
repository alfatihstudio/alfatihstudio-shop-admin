<?php
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// Explicit opt-in via wp-config.php. Default uninstall keeps the shop configuration.
if ( ! defined( 'AFS_SHOP_DELETE_DATA' ) || true !== AFS_SHOP_DELETE_DATA ) { return; }

foreach ( array( 'afs_shop_banners', 'afs_shop_settings', 'afs_shop_role_schema', 'afs_shop_cache_status' ) as $option ) {
    delete_option( $option );
}
delete_transient( 'afs_shop_counts' );
delete_transient( 'afs_shop_counts_v2' );
delete_transient( 'afs_shop_counts_v3' );
wp_clear_scheduled_hook( 'afs_banner_schedule_transition' );
// Keep an assigned role so existing clients do not become role-less.
if ( ! get_users( array( 'role' => 'alfatih_shop_manager', 'fields' => 'ID', 'number' => 1 ) ) ) {
    remove_role( 'alfatih_shop_manager' );
}
$admin = get_role( 'administrator' );
if ( $admin ) {
    $admin->remove_cap( 'afs_manage_shop' );
    $admin->remove_cap( 'afs_manage_banners' );
}
// Media and business records are never deleted. Failed-form transients expire after ten minutes.
