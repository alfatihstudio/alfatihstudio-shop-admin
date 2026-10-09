<?php
defined( 'ABSPATH' ) || exit;

/** Client permissions are separate from the agency administrator account. */
final class AFS_Shop_Access {
    const ROLE = 'alfatih_shop_manager';
    const ROLE_SCHEMA = '3';

    public static function activate() {
        // Identify upgrades before creating the role/schema; never silently revoke existing policy.
        $existing = false !== get_option( 'afs_shop_role_schema', false ) || (bool) get_role( self::ROLE ) || false !== get_option( 'afs_shop_settings', false );
        self::initialize_policy( $existing );
        $caps = array( 'read' => true, 'upload_files' => true, 'view_admin_dashboard' => true, 'view_woocommerce_reports' => true,
            'afs_manage_shop' => true, 'afs_manage_banners' => true );
        foreach ( array( 'product', 'shop_order', 'shop_coupon' ) as $type ) {
            foreach ( array( 'edit_', 'read_private_', 'edit_private_', 'edit_published_', 'edit_others_',
                'publish_', 'delete_', 'delete_private_', 'delete_published_', 'delete_others_' ) as $prefix ) {
                if ( 'shop_order' === $type && 0 === strpos( $prefix, 'delete_' ) ) { continue; }
                $caps[ $prefix . $type . 's' ] = true;
            }
        }
        foreach ( array( 'manage_product_terms', 'edit_product_terms', 'delete_product_terms', 'assign_product_terms' ) as $cap ) {
            $caps[ $cap ] = true;
        }
        add_role( self::ROLE, 'Pengurus Kedai Alfatihstudio', $caps );
        $role = get_role( self::ROLE );
        if ( $role ) {
            foreach ( $caps as $cap => $grant ) { $role->add_cap( $cap, $grant ); }
            // Revoke only capabilities granted by our old schema; keep unrelated custom grants.
            foreach ( array( 'delete_', 'delete_private_', 'delete_published_', 'delete_others_' ) as $prefix ) {
                $role->remove_cap( $prefix . 'shop_orders' );
            }
        }
        // Do not demote users, alter existing roles, or change the administrator on activation.
        foreach ( array( 'afs_manage_shop', 'afs_manage_banners' ) as $cap ) {
            $admin = get_role( 'administrator' );
            if ( $admin ) { $admin->add_cap( $cap ); }
        }
        update_option( 'afs_shop_role_schema', self::ROLE_SCHEMA, true );
        if ( function_exists( 'wp_set_option_autoload_values' ) ) { wp_set_option_autoload_values( array( 'afs_shop_role_schema' => true ) ); }
    }

    public static function is_client() {
        return in_array( self::ROLE, (array) wp_get_current_user()->roles, true ) && ! current_user_can( 'manage_options' );
    }

    public static function policy_defaults() {
        return array( 'analytics' => true, 'banners' => true, 'attributes' => true,
            'refunds' => false, 'delete_notes' => false );
    }

    public static function initialize_policy( $existing ) {
        $stored = get_option( 'afs_shop_settings', false );
        $defaults = self::policy_defaults();
        if ( $existing ) { $defaults['refunds'] = true; $defaults['delete_notes'] = true; }
        if ( false === $stored ) { add_option( 'afs_shop_settings', $defaults, '', false ); }
        elseif ( is_array( $stored ) ) { update_option( 'afs_shop_settings', array_merge( $defaults, $stored ), false ); }
    }

    /** Explicit administrator-selected extension actions; never trust HTTP Referer as authorization. */
    public static function extension_ajax_actions( $value ) {
        if ( ! is_string( $value ) || strlen( $value ) > 2000 ) { return array(); }
        $items = preg_split( '/[\s,]+/', trim( $value ), -1, PREG_SPLIT_NO_EMPTY );
        if ( count( $items ) > 20 ) { return array(); }
        foreach ( $items as $item ) { if ( ! preg_match( '/^[a-zA-Z0-9_-]{1,80}$/D', $item ) ) { return array(); } }
        return array_values( array_unique( $items ) );
    }

    public static function enabled( $feature ) {
        $settings = (array) get_option( 'afs_shop_settings', array() );
        $defaults = self::policy_defaults();
        return isset( $defaults[ $feature ] ) && ( array_key_exists( $feature, $settings ) ? (bool) $settings[ $feature ] : $defaults[ $feature ] );
    }

    public static function can_use( $feature ) {
        return current_user_can( 'manage_options' ) || self::enabled( $feature );
    }

    public function __construct() {
        if ( self::ROLE_SCHEMA !== get_option( 'afs_shop_role_schema' ) ) { self::activate(); }
        add_action( 'admin_init', array( $this, 'guard_admin' ), 1 );
        add_filter( 'login_redirect', array( $this, 'login_redirect' ), 10, 3 );
        add_filter( 'map_meta_cap', array( $this, 'protect_content' ), 10, 4 );
        add_filter( 'rest_pre_dispatch', array( $this, 'guard_rest' ), 10, 3 );
        add_action( 'admin_bar_menu', array( $this, 'admin_bar' ), 999 );
    }

    public function login_redirect( $url, $requested, $user ) {
        if ( $user instanceof WP_User && in_array( self::ROLE, (array) $user->roles, true ) && ! user_can( $user, 'manage_options' ) ) {
            return admin_url( 'admin.php?page=afs-shop' );
        }
        return $url;
    }

    public function protect_content( $caps, $cap, $user_id, $args ) {
        $user = get_userdata( $user_id );
        // Read already-resolved primitive capabilities here: user_can() would recurse through map_meta_cap.
        if ( ! $user || ! in_array( self::ROLE, (array) $user->roles, true ) || ! empty( $user->allcaps['manage_options'] ) ) { return $caps; }
        if ( in_array( $cap, array( 'delete_shop_order', 'delete_shop_orders', 'delete_private_shop_orders', 'delete_published_shop_orders', 'delete_others_shop_orders' ), true ) ) {
            return array( 'do_not_allow' );
        }
        if ( in_array( $cap, array( 'edit_post', 'delete_post', 'read_post' ), true ) && ! empty( $args[0] ) ) {
            $type = get_post_type( $args[0] );
            if ( 'delete_post' === $cap && in_array( $type, array( 'shop_order', 'shop_order_refund' ), true ) ) {
                return array( 'do_not_allow' );
            }
            if ( $type && ! in_array( $type, array( 'product', 'product_variation', 'shop_order', 'shop_order_refund', 'shop_coupon', 'attachment' ), true ) ) {
                return array( 'do_not_allow' );
            }
        }
        return $caps;
    }

    /** Exact admin screen allowlist; core handlers still enforce their own capabilities/nonces. */
    public static function screen_allowed( $file, $request, $post_type = '' ) {
        $page = isset( $request['page'] ) && is_string( $request['page'] ) ? $request['page'] : '';
        if ( 'edit.php' === $file && 'product_attributes' === $page && 'product' === $post_type ) { return self::enabled( 'attributes' ); }
        if ( isset( $request['page'] ) && ( ! is_string( $request['page'] ) || ( 'admin.php' !== $file && '' !== $page ) ) ) { return false; }
        if ( in_array( $file, array( 'profile.php', 'upload.php', 'media-new.php', 'async-upload.php', 'media-upload.php' ), true ) ) { return true; }
        if ( 'admin.php' === $file ) {
            if ( 'afs-analytics' === $page ) { return self::enabled( 'analytics' ); }
            if ( 'afs-banners' === $page ) { return self::enabled( 'banners' ); }
            if ( 'product_attributes' === $page ) { return self::enabled( 'attributes' ); }
            return in_array( $page, array( 'afs-shop', 'afs-promotions', 'afs-help', 'wc-orders' ), true );
        }
        if ( 'admin-post.php' === $file ) {
            $action = $request['action'] ?? '';
            if ( 'afs_export_analytics' === $action ) { return self::enabled( 'analytics' ); }
            return 'afs_save_banners' === $action && self::enabled( 'banners' );
        }
        if ( in_array( $file, array( 'edit.php', 'post-new.php', 'post.php' ), true ) ) {
            return in_array( $post_type, array( 'product', 'shop_order', 'shop_coupon', 'attachment' ), true );
        }
        if ( 'edit-tags.php' === $file || 'term.php' === $file ) {
            $tax = isset( $request['taxonomy'] ) ? $request['taxonomy'] : '';
            $attribute = is_string( $tax ) && 0 === strpos( $tax, 'pa_' ) && taxonomy_exists( $tax ) && is_object_in_taxonomy( 'product', $tax );
            return ( '' === $post_type || 'product' === $post_type ) && ( in_array( $tax, array( 'product_cat', 'product_tag', 'product_brand', 'product_shipping_class', 'afs_writer' ), true ) || ( $attribute && self::enabled( 'attributes' ) ) );
        }
        return false;
    }

    public static function ajax_allowed( $action ) {
        // Native delete_refund checks edit_shop_orders, not delete_shop_orders.
        if ( ! is_string( $action ) || 'woocommerce_delete_refund' === $action ) { return false; }
        if ( 'woocommerce_add_new_attribute' === $action && ! self::enabled( 'attributes' ) ) { return false; }
        if ( 'woocommerce_refund_line_items' === $action && ! self::enabled( 'refunds' ) ) { return false; }
        if ( 'woocommerce_delete_order_note' === $action && ! self::enabled( 'delete_notes' ) ) { return false; }
        $allowed = array(
            'afs_optimize_image', 'heartbeat', 'query-attachments', 'get-attachment', 'upload-attachment', 'save-attachment',
            'save-attachment-compat', 'send-attachment-to-editor', 'send-link-to-editor', 'image-editor',
            'imgedit-preview', 'inline-save', 'inline-save-tax', 'ajax-tag-search', 'get-tagcloud',
            'add-afs_writer', 'add-tag', 'add-product_cat', 'add-product_tag', 'add-product_brand', 'add-product_shipping_class',
            'delete-tag', 'closed-postboxes', 'hidden-columns', 'meta-box-order', 'wp-remove-post-lock',
            'get-permalink', 'sample-permalink', 'autosave', 'dismiss-wp-pointer',
            'get-post-thumbnail-html', 'set-post-thumbnail',
        );
        foreach ( array(
            'feature_product', 'mark_order_status', 'get_order_details', 'add_attribute', 'add_new_attribute',
            'remove_variations', 'save_attributes', 'add_attributes_and_variations', 'add_variation', 'link_all_variations',
            'revoke_access_to_download', 'grant_access_to_download', 'get_customer_details',
            'add_order_item', 'add_order_fee', 'add_order_shipping', 'add_order_tax', 'add_coupon_discount',
            'remove_order_coupon', 'remove_order_item', 'remove_order_tax', 'calc_line_taxes',
            'save_order_items', 'load_order_items', 'add_order_note', 'delete_order_note',
            'json_search_order_metakeys', 'json_search_products', 'json_search_products_and_variations',
            'json_search_downloadable_products_and_variations', 'json_search_customers', 'json_search_categories',
            'json_search_categories_tree', 'json_search_taxonomy_terms', 'json_search_product_attributes',
            'json_search_pages', 'term_ordering', 'product_ordering', 'refund_line_items',
            'load_variations', 'save_variations', 'bulk_edit_variations', 'order_add_meta', 'order_delete_meta',
            'load_status_widget', 'load_recent_reviews_widget',
        ) as $suffix ) { $allowed[] = 'woocommerce_' . $suffix; }
        $settings = (array) get_option( 'afs_shop_settings', array() );
        $allowed = array_merge( $allowed, self::extension_ajax_actions( $settings['frontend_ajax_actions'] ?? '' ) );
        $allowed = apply_filters( 'afs_shop_allowed_ajax_actions', $allowed );
        return is_array( $allowed ) && in_array( $action, $allowed, true );
    }

    public function guard_admin() {
        if ( ! self::is_client() ) { return; }
        if ( wp_doing_ajax() ) {
            // Match the source used by admin-ajax.php dispatch; native handlers still check nonces/caps.
            if ( ! self::ajax_allowed( $_REQUEST['action'] ?? '' ) ) {
                wp_die( 'Tindakan ini hanya boleh diakses oleh pentadbir Alfatihstudio.', 'Akses terhad', array( 'response' => 403 ) );
            }
            return;
        }
        global $pagenow;
        // List/plugin screens route by GET. Only native form handlers need POST parameters.
        $request = wp_unslash( $_GET );
        if ( in_array( $pagenow, array( 'edit-tags.php', 'term.php', 'admin-post.php' ), true ) ) {
            $request = wp_unslash( $_REQUEST );
        }
        if ( in_array( $pagenow, array( 'post.php', 'term.php', 'admin-post.php', 'async-upload.php', 'media-upload.php' ), true ) ) {
            $request = array_merge( $request, wp_unslash( $_POST ) );
        }
        $type = isset( $request['post_type'] ) && is_string( $request['post_type'] ) ? sanitize_key( $request['post_type'] ) : '';
        if ( in_array( $pagenow, array( 'post.php', 'media-upload.php', 'async-upload.php' ), true ) ) {
            $id = 'post.php' === $pagenow ? absint( $_GET['post'] ?? $_POST['post_ID'] ?? 0 ) : absint( $request['attachment_id'] ?? $request['post_id'] ?? 0 );
            if ( $id ) { $type = get_post_type( $id ); }
        }
        if ( 'index.php' === $pagenow ) {
            wp_safe_redirect( admin_url( 'admin.php?page=afs-shop' ) ); exit;
        }
        if ( ! self::screen_allowed( $pagenow, $request, $type ) ) {
            wp_die( 'Halaman ini hanya boleh diakses oleh pentadbir Alfatihstudio.', 'Akses terhad', array( 'response' => 403, 'back_link' => true ) );
        }
    }

    /** Extra scope restriction only; every native REST permission callback still runs. */
    public static function rest_allowed( $route, $method ) {
        $read = in_array( $method, array( 'GET', 'HEAD', 'OPTIONS' ), true );
        // Store API owns cart sessions, checkout validation and Store nonces. Never grant admin caps.
        if ( preg_match( '#^/wc/store/v[0-9]+(?:/|$)#', $route ) ) { return true; }
        if ( preg_match( '#^/wp/v2/media(?:/[0-9]+)?$#', $route ) || '/wp/v2/users/me' === $route ) { return true; }
        if ( '/wp/v2/settings' === $route || preg_match( '#^/wc(?:-analytics)?/v[0-9]+/(?:settings|system_status|data|webhooks)#', $route ) ) { return false; }
        if ( $read && self::enabled( 'analytics' ) && preg_match( '#^/wc-analytics/reports(?:/|$)#', $route ) ) { return true; }
        // Product editor integrations may read catalog data; mutations use the native admin handlers.
        if ( $read && preg_match( '#^/wc/v[23]/products(?:/|$)#', $route ) ) { return true; }
        // Extensions can opt in narrowly. Their own capabilities and nonces remain mandatory.
        return (bool) apply_filters( 'afs_shop_rest_route_allowed', false, $route, $method );
    }

    public function guard_rest( $result, $server, $request ) {
        if ( ! self::is_client() || null !== $result ) { return $result; }
        if ( self::rest_allowed( $request->get_route(), $request->get_method() ) ) { return $result; }
        return new WP_Error( 'afs_access_denied', 'Akses API ini terhad kepada pentadbir.', array( 'status' => 403 ) );
    }

    public function admin_bar( $bar ) {
        if ( ! self::is_client() ) { return; }
        foreach ( array( 'wp-logo', 'updates', 'comments', 'new-content', 'woocommerce-site-visibility-badge', 'customize' ) as $id ) {
            $bar->remove_node( $id );
        }
        $bar->add_node( array( 'id' => 'afs-shop', 'title' => 'Alfatihstudio Shop Admin', 'href' => admin_url( 'admin.php?page=afs-shop' ) ) );
    }
}
