<?php
defined( 'ABSPATH' ) || exit;

final class AFS_Shop_Admin {
    private $screens = array();
    private $banner_screen = '';
    const COUNTS_TRANSIENT = 'afs_shop_counts_v3';

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'menu' ), 30 );
        add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
        add_filter( 'admin_body_class', array( $this, 'body_class' ) );
        add_action( 'in_admin_header', array( $this, 'shell' ) );
        add_filter( 'admin_footer_text', array( $this, 'footer' ) );
        add_filter( 'update_footer', array( $this, 'version_footer' ), 20 );
        add_filter( 'admin_title', array( $this, 'title' ), 100 );
        add_action( 'admin_notices', array( $this, 'product_guide' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        foreach ( array( 'woocommerce_new_order', 'woocommerce_update_order', 'woocommerce_order_status_changed',
            'woocommerce_delete_order', 'woocommerce_trash_order', 'woocommerce_untrash_order',
            'woocommerce_new_product', 'woocommerce_update_product', 'woocommerce_delete_product', 'woocommerce_trash_product',
            'woocommerce_product_set_stock', 'woocommerce_variation_set_stock',
            'woocommerce_product_set_stock_status', 'woocommerce_variation_set_stock_status' ) as $hook ) {
            add_action( $hook, array( $this, 'invalidate_counts' ), 10, 0 );
        }
    }

    public function invalidate_counts() {
        delete_transient( self::COUNTS_TRANSIENT );
    }

    public static function settings() {
        return wp_parse_args( (array) get_option( 'afs_shop_settings', array() ), array(
            'company' => get_bloginfo( 'name' ), 'whatsapp' => '60132226295', 'frontend_ajax_actions' => '', 'image_upload_mb' => 3, 'image_upload_dimension' => 6000,
        ) + AFS_Shop_Access::policy_defaults() );
    }

    public function register_settings() {
        register_setting( 'afs_shop_settings_group', 'afs_shop_settings', array(
            'type' => 'array', 'sanitize_callback' => array( $this, 'sanitize_settings' ), 'show_in_rest' => false,
        ) );
    }

    public function sanitize_settings( $input ) {
        $old = self::settings();
        if ( ! is_array( $input ) ) { return $old; }
        if ( ! is_string( $input['company'] ?? '' ) || ! is_scalar( $input['whatsapp'] ?? '' ) ) { return $old; }
        $number = preg_replace( '/[^0-9]/', '', (string) ( $input['whatsapp'] ?? '' ) );
        if ( $number && ! preg_match( '/^[1-9][0-9]{7,14}$/', $number ) ) {
            add_settings_error( 'afs_shop_settings', 'invalid_phone', 'Nombor WhatsApp mesti termasuk kod negara, contoh 60132226295.' );
            return $old;
        }
        $company = sanitize_text_field( $input['company'] );
        if ( '' === $company ) { add_settings_error( 'afs_shop_settings', 'missing_company', 'Nama kedai diperlukan.' ); return $old; }
        $clean = array( 'company' => $company, 'whatsapp' => $number );
        foreach ( AFS_Shop_Access::policy_defaults() as $feature => $default ) {
            $clean[ $feature ] = isset( $input[ $feature ] ) && in_array( $input[ $feature ], array( '1', 1, true ), true );
        }
        $actions = $input['frontend_ajax_actions'] ?? '';
        if ( ! is_string( $actions ) || ( '' !== trim( $actions ) && ! AFS_Shop_Access::extension_ajax_actions( $actions ) ) ) {
            add_settings_error( 'afs_shop_settings', 'invalid_ajax', 'Senarai tindakan tambahan tidak sah. Gunakan maksimum 20 nama tindakan, satu setiap baris.' );
            return $old;
        }
        $clean['frontend_ajax_actions'] = implode( "\n", AFS_Shop_Access::extension_ajax_actions( $actions ) );
        foreach ( array( 'image_upload_mb' => array( 1, 10 ), 'image_upload_dimension' => array( 1920, 6000 ) ) as $key => $range ) {
            $value = $input[ $key ] ?? $old[ $key ];
            if ( ! is_scalar( $value ) || ! preg_match( '/^[0-9]+$/', (string) $value ) || (int) $value < $range[0] || (int) $value > $range[1] ) {
                add_settings_error( 'afs_shop_settings', 'invalid_image_limit', 'Had gambar tidak sah. Saiz 1–10 MB; sisi terpanjang 1,920–6,000px.' );
                return $old;
            }
            $clean[ $key ] = (int) $value;
        }
        return $clean;
    }

    public function menu() {
        $this->screens[] = add_menu_page( 'Ringkasan Kedai', 'Alfatihstudio Shop', 'afs_manage_shop', 'afs-shop', array( $this, 'dashboard' ), 'dashicons-store', 2 );
        $this->screens[] = add_submenu_page( 'afs-shop', 'Ringkasan Kedai', 'Ringkasan Kedai', 'afs_manage_shop', 'afs-shop', array( $this, 'dashboard' ) );
        $this->screens[] = add_submenu_page( 'afs-shop', 'Promosi & Diskaun', 'Promosi & Diskaun', 'afs_manage_shop', 'afs-promotions', array( $this, 'promotions' ) );
        if ( AFS_Shop_Access::can_use( 'analytics' ) ) { $this->screens[] = add_submenu_page( 'afs-shop', 'Analytics Kedai', 'Analytics Kedai', 'view_woocommerce_reports', 'afs-analytics', array( 'AFS_Shop_Analytics', 'render' ) ); }
        if ( AFS_Shop_Access::can_use( 'banners' ) ) {
        $this->banner_screen = add_submenu_page( 'afs-shop', 'Banner Website', 'Banner Website', 'afs_manage_banners', 'afs-banners', array( $this, 'banners' ) );
        $this->screens[] = $this->banner_screen;
        }
        $this->screens[] = add_submenu_page( 'afs-shop', 'Bantuan', 'Bantuan', 'afs_manage_shop', 'afs-help', array( $this, 'help' ) );
        $this->screens[] = add_submenu_page( 'afs-shop', 'Kawalan Panel Kedai', 'Kawalan Panel Kedai', 'manage_options', 'afs-settings', array( $this, 'settings_page' ) );
    }

    private function branded() {
        $screen = get_current_screen();
        return AFS_Shop_Access::is_client() || ( $screen && in_array( $screen->id, $this->screens, true ) );
    }

    public function body_class( $classes ) {
        return $classes . ( $this->branded() ? ' afs-admin' : '' );
    }

    public function assets() {
        if ( ! $this->branded() ) { return; }
        wp_enqueue_style( 'afs-admin', AFS_SHOP_URL . 'assets/admin.css', array( 'dashicons' ), AFS_SHOP_VERSION );
        $screen = get_current_screen();
        if ( $screen && 'afs-help' === ( $_GET['page'] ?? '' ) ) {
            wp_enqueue_script( 'afs-guides', AFS_SHOP_URL . 'assets/guides.js', array(), AFS_SHOP_VERSION, true );
        }
        if ( $screen && $screen->id === $this->banner_screen ) {
            wp_enqueue_media();
            wp_enqueue_script( 'afs-banners', AFS_SHOP_URL . 'assets/banners.js', array( 'media-views' ), AFS_SHOP_VERSION, true );
        }
    }

    public static function orders_url() {
        $hpos = class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) &&
            \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
        return admin_url( $hpos ? 'admin.php?page=wc-orders' : 'edit.php?post_type=shop_order' );
    }

    private function page_url( $page ) { return admin_url( 'admin.php?page=' . $page ); }

    private function support_url() {
        $settings = self::settings();
        return $settings['whatsapp'] ? 'https://wa.me/' . $settings['whatsapp'] . '?text=' . rawurlencode( 'Salam Alfatihstudio, saya dari ' . $settings['company'] . '. Saya perlukan bantuan dashboard kedai.' ) : 'https://alfatihstudio.com';
    }

    private function link( $url, $label, $class = '' ) {
        echo '<a class="' . esc_attr( $class ) . '" href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
    }

    public function shell() {
        if ( ! $this->branded() || ! current_user_can( 'afs_manage_shop' ) ) { return; }
        $settings = self::settings();
        $items = array(
            array( 'Ringkasan Kedai', $this->page_url( 'afs-shop' ), 'admin-home', 'afs-shop' ),
            array( 'Pesanan', self::orders_url(), 'cart', 'wc-orders' ),
            array( 'Produk', admin_url( 'edit.php?post_type=product' ), 'archive', 'product' ),
            array( 'Penulis', admin_url( 'edit-tags.php?taxonomy=afs_writer&post_type=product' ), 'admin-users', 'afs_writer' ),
            array( 'Kategori Produk', admin_url( 'edit-tags.php?taxonomy=product_cat&post_type=product' ), 'category', 'product_cat' ),
            array( 'Promosi & Diskaun', $this->page_url( 'afs-promotions' ), 'tag', 'afs-promotions' ),
            array( 'Banner Website', $this->page_url( 'afs-banners' ), 'format-image', 'afs-banners' ),
            array( 'Analytics Kedai', $this->page_url( 'afs-analytics' ), 'chart-bar', 'afs-analytics' ),
            array( 'Bantuan', $this->page_url( 'afs-help' ), 'editor-help', 'afs-help' ),
        );
        if ( AFS_Shop_Access::can_use( 'attributes' ) ) { $items[] = array( 'Atribut Produk', admin_url( 'edit.php?post_type=product&page=product_attributes' ), 'filter', 'product_attributes' ); }
        $items = array_filter( $items, static function ( $item ) {
            if ( 'afs-banners' === $item[3] ) { return AFS_Shop_Access::can_use( 'banners' ); }
            if ( 'afs-analytics' === $item[3] ) { return AFS_Shop_Access::can_use( 'analytics' ); }
            return true;
        } );
        $screen = get_current_screen();
        $page = isset( $_GET['page'] ) && is_string( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
        echo '<a class="afs-skip-link" href="#wpbody-content">Terus ke kandungan</a>';
        echo '<aside class="afs-sidebar"><a class="afs-logo" href="https://alfatihstudio.com" target="_blank" rel="noopener"><img src="' . esc_url( AFS_SHOP_URL . 'assets/logo-alfatih.webp' ) . '" width="220" height="110" alt="Alfatihstudio — Solusi Web & Digital"></a><span class="afs-kicker">SHOP ADMIN</span><div class="afs-company"><strong>' . esc_html( $settings['company'] ) . '</strong><span>Pengurusan Kedai</span></div><nav aria-label="Pengurusan kedai">';
        foreach ( $items as $item ) {
            $is_category = $screen && 'product_cat' === ( $screen->taxonomy ?? '' );
            $is_writer = $screen && 'afs_writer' === ( $screen->taxonomy ?? '' );
            $active = $page === $item[3] || ( 'afs_writer' === $item[3] && $is_writer ) || ( 'product_cat' === $item[3] && $is_category ) || ( 'product' === $item[3] && $screen && 'product' === $screen->post_type && ! $is_category && ! $is_writer ) ||
                ( 'wc-orders' === $item[3] && $screen && 'shop_order' === $screen->post_type ) ||
                ( 'afs-promotions' === $item[3] && $screen && 'shop_coupon' === $screen->post_type );
            echo '<a href="' . esc_url( $item[1] ) . '"' . ( $active ? ' class="is-active" aria-current="page"' : '' ) . '><span class="dashicons dashicons-' . esc_attr( $item[2] ) . '" aria-hidden="true"></span>' . esc_html( $item[0] ) . '</a>';
        }
        if ( current_user_can( 'manage_options' ) ) {
            $this->link( $this->page_url( 'afs-settings' ), 'Tetapan Alfatihstudio' );
            $this->link( admin_url(), 'Pentadbiran penuh' );
        }
        echo '</nav><div class="afs-support"><strong>Perlukan bantuan?</strong><p>Baca panduan atau hubungi pasukan Alfatihstudio.</p>';
        $this->link( $this->support_url(), $settings['whatsapp'] ? 'Hubungi Alfatihstudio' : 'Laman Alfatihstudio', 'afs-whatsapp' );
        $this->link( 'https://alfatihstudio.com', 'alfatihstudio.com', 'afs-site-link' );
        echo '</div></aside><header class="afs-topbar"><span>Kedai / Pengurusan</span><div>';
        $this->link( home_url( '/' ), 'Lihat Website' );
        $this->link( admin_url( 'profile.php' ), wp_get_current_user()->display_name );
        $this->link( wp_logout_url(), 'Log keluar' );
        echo '</div></header>';
    }

    public function footer( $text ) {
        return $this->branded() ? 'Diuruskan oleh <a href="https://alfatihstudio.com" target="_blank" rel="noopener">Alfatihstudio</a>' : $text;
    }
    public function version_footer( $text ) { return $this->branded() ? 'Shop Admin ' . AFS_SHOP_VERSION : $text; }
    public function title( $title ) {
        return $this->branded() ? str_replace( array( 'WordPress', 'WooCommerce' ), 'Alfatihstudio', $title ) : $title;
    }

    private function heading( $title, $subtitle ) {
        echo '<div class="wrap afs-content"><div class="afs-heading"><div><h1>' . esc_html( $title ) . '</h1><p>' . esc_html( $subtitle ) . '</p></div>';
        if ( 'Ringkasan Kedai' === $title ) { $this->link( admin_url( 'post-new.php?post_type=product' ), '+ Tambah Produk', 'button button-primary' ); }
        echo '</div>';
    }

    public function dashboard() {
        $this->heading( 'Ringkasan Kedai', 'Urus pesanan, produk dan promosi anda di satu tempat.' );
        $today = new DateTimeImmutable( 'today', wp_timezone() );
        $counts = get_transient( self::COUNTS_TRANSIENT );
        if ( ! is_array( $counts ) || ( $counts['day'] ?? '' ) !== $today->format( 'Y-m-d' ) ) {
            $counts = array( 'day' => $today->format( 'Y-m-d' ) );
            foreach ( array( 'today', 'processing', 'on-hold' ) as $status ) {
                $args = array( 'type' => 'shop_order', 'limit' => 1, 'return' => 'ids', 'paginate' => true );
                if ( 'today' === $status ) { $args['status'] = array( 'processing', 'completed' ); $args['date_created'] = $today->getTimestamp() . '...' . ( $today->modify( '+1 day' )->getTimestamp() - 1 ); }
                else { $args['status'] = $status; }
                $result = wc_get_orders( $args );
                $counts[ $status ] = $result->total;
            }
            $stock = wc_get_products( array( 'stock_status' => 'outofstock', 'limit' => 1, 'return' => 'ids', 'paginate' => true ) );
            $counts['outofstock'] = $stock->total;
            set_transient( self::COUNTS_TRANSIENT, $counts, 60 );
        }
        echo '<div class="afs-stats">';
        foreach ( array( array( 'Pesanan Hari Ini (diproses / selesai)', $counts['today'] ), array( 'Perlu Diproses', $counts['processing'] ), array( 'Pesanan Ditahan', $counts['on-hold'] ), array( 'Produk Kehabisan Stok', $counts['outofstock'] ) ) as $stat ) {
            echo '<article class="afs-card"><span>' . esc_html( $stat[0] ) . '</span><strong>' . esc_html( number_format_i18n( $stat[1] ) ) . '</strong></article>';
        }
        echo '</div><p class="afs-muted">Pesanan hari ini dikira mengikut tarikh dibuat dengan status Processing atau Completed sahaja. Pesanan ditahan ditunjukkan secara berasingan. Status pesanan bukan pengesahan wang sudah diterima daripada gateway atau bank. Ringkasan mungkin lewat sehingga satu minit. Jualan terperinci tersedia dalam Analytics Kedai jika modul diaktifkan.</p><div class="afs-grid"><section class="afs-card"><div class="afs-card-head"><h2>Pesanan Perlu Tindakan</h2>';
        $this->link( self::orders_url(), 'Lihat Semua →' );
        echo '</div><div class="afs-table-scroll"><table class="widefat"><thead><tr><th>Pesanan</th><th>Pelanggan</th><th>Jumlah</th><th>Status</th></tr></thead><tbody>';
        $orders = wc_get_orders( array( 'type' => 'shop_order', 'status' => array( 'processing', 'on-hold' ), 'limit' => 8, 'orderby' => 'date', 'order' => 'ASC' ) );
        foreach ( $orders as $order ) {
            echo '<tr><td><a href="' . esc_url( $order->get_edit_order_url() ) . '">#' . esc_html( $order->get_order_number() ) . '</a></td><td>' . esc_html( $order->get_formatted_billing_full_name() ?: 'Pelanggan' ) . '</td><td>' . wp_kses_post( $order->get_formatted_order_total() ) . '</td><td><span class="afs-status afs-status-' . esc_attr( $order->get_status() ) . '">' . esc_html( 'on-hold' === $order->get_status() ? 'Ditahan' : 'Diproses' ) . '</span></td></tr>';
        }
        if ( ! $orders ) { echo '<tr><td colspan="4">Tiada pesanan yang memerlukan tindakan sekarang.</td></tr>'; }
        echo '</tbody></table></div></section><section class="afs-card"><h2>Tindakan Pantas</h2>';
        foreach ( array( array( admin_url( 'post-new.php?post_type=product' ), 'Tambah Produk', 'Masukkan buku atau produk baharu.' ), array( $this->page_url( 'afs-promotions' ), 'Urus Diskaun', 'Harga promosi dan kod kupon.' ), array( $this->page_url( 'afs-banners' ), 'Tukar Banner', 'Kemas kini banner website.' ) ) as $item ) {
            if ( false !== strpos( $item[0], 'page=afs-banners' ) && ! AFS_Shop_Access::can_use( 'banners' ) ) { continue; }
            echo '<a class="afs-quick" href="' . esc_url( $item[0] ) . '"><strong>' . esc_html( $item[1] ) . ' →</strong><span>' . esc_html( $item[2] ) . '</span></a>';
        }
        echo '</section>';
        if ( AFS_Shop_Access::can_use( 'banners' ) ) {
        echo '<section class="afs-card"><div class="afs-card-head"><h2>Banner Website</h2>';
        $this->link( $this->page_url( 'afs-banners' ), 'Ubah →' );
        echo '</div><div class="afs-banner-summary">';
        $all = AFS_Shop_Banners::all();
        foreach ( AFS_Shop_Banners::slots() as $slot => $label ) {
            echo '<div><strong>' . esc_html( $label ) . '</strong>';
            if ( ! empty( $all[ $slot ]['image'] ) ) { echo wp_get_attachment_image( $all[ $slot ]['image'], 'medium', false, array( 'loading' => 'lazy' ) ); }
            else { echo '<p class="afs-placeholder">Belum ditetapkan</p>'; }
            echo '<span class="afs-muted">' . esc_html( AFS_Shop_Banners::status_label( $all[ $slot ] ?? array() ) ) . '</span></div>';
        }
        echo '</div></section>';
        }
        echo '<section class="afs-card"><h2>Panduan Kedai</h2><p>Langkah mudah untuk produk, pesanan, banner, diskaun dan laporan.</p>';
        $this->link( $this->page_url( 'afs-help' ), 'Buka panduan →', 'afs-quick' );
        echo '</section></div></div>';
    }

    public function promotions() {
        $this->heading( 'Promosi & Diskaun', 'Pilih harga tawaran produk atau kod diskaun untuk pelanggan.' );
        echo '<div class="afs-two-col"><section class="afs-card"><h2>Harga Promosi</h2><p>Buka produk, masukkan harga promosi dan tetapkan tarikh mula serta tamat melalui pilihan jadual harga.</p>';
        $this->link( admin_url( 'edit.php?post_type=product' ), 'Urus Harga Produk', 'button button-primary' );
        echo '</section><section class="afs-card"><h2>Kod Diskaun</h2><p>Diskaun peratus atau nilai RM, dengan syarat belian, tarikh luput dan had penggunaan.</p>';
        $this->link( admin_url( 'edit.php?post_type=shop_coupon' ), 'Semua Kod Diskaun', 'button' );
        echo ' ';
        $this->link( admin_url( 'post-new.php?post_type=shop_coupon' ), '+ Tambah Kod', 'button button-primary' );
        echo '</section></div><section class="afs-card afs-note"><h2>Semak sebelum diterbitkan</h2><p>Tentukan sama ada kupon boleh digunakan bersama kupon lain atau produk yang sudah mempunyai harga promosi. Pelanggan perlu memasukkan kod kupon semasa checkout; diskaun automatik belum termasuk dalam versi ini.</p></section></div>';
    }

    public function banners() {
        if ( ! current_user_can( 'afs_manage_banners' ) || ! AFS_Shop_Access::can_use( 'banners' ) ) { wp_die( 'Akses terhad.', '', array( 'response' => 403 ) ); }
        $feedback = AFS_Shop_Banners::feedback();
        $current = AFS_Shop_Banners::all( true );
        if ( is_wp_error( $current ) ) { echo '<div class="wrap"><div class="notice notice-error"><p>' . esc_html( $current->get_error_message() ) . '</p></div></div>'; return; }
        $conflict = ! empty( $feedback['conflict'] );
        $all = ! $conflict && isset( $feedback['input'] ) && is_array( $feedback['input'] ) ? $feedback['input'] : $current;
        $revision = ! $conflict && ! empty( $feedback['revision'] ) ? $feedback['revision'] : AFS_Shop_Banners::revision( $current );
        $this->heading( 'Banner Website', 'Tukar gambar, pautan dan status banner tanpa menyunting susunan halaman.' );
        include AFS_SHOP_DIR . 'templates/banners.php';
        echo '</div>';
    }

    public function help() {
        $this->heading( 'Bantuan & Panduan', 'Langkah lengkap, contoh dan penyelesaian masalah untuk operasi kedai.' );
        $guides = AFS_Shop_Guides::all();
        echo '<p class="afs-note">Pilih topik di bawah. Nama butang WordPress/WooCommerce mungkin berbeza mengikut bahasa kedai; padanan istilah disertakan dalam langkah.</p><nav class="afs-guide-index" aria-label="Topik panduan">';
        $guide_number = 0;
        foreach ( $guides as $guide_title => $guide_steps ) { $guide_number++; echo '<a href="#afs-guide-' . absint( $guide_number ) . '">' . esc_html( $guide_title ) . '</a>'; }
        echo '</nav>';
        $guide_number = 0;
        echo '<div class="afs-guides">';
        foreach ( $guides as $title => $steps ) {
            $guide_number++;
            echo '<details id="afs-guide-' . absint( $guide_number ) . '" class="afs-card"><summary>' . esc_html( $title ) . '</summary><ol>';
            foreach ( $steps as $step ) { echo '<li>' . esc_html( $step ) . '</li>'; }
            echo '</ol></details>';
        }
        echo '</div><section class="afs-card afs-note"><h2>Masih perlukan bantuan?</h2><p>Hubungi Alfatihstudio dan beritahu nama kedai serta langkah yang sedang dibuat.</p>';
        $this->link( $this->support_url(), 'Hubungi Alfatihstudio', 'afs-whatsapp' );
        echo '</section></div>';
    }

    public function product_guide() {
        $screen = get_current_screen();
        if ( ! AFS_Shop_Access::is_client() || ! $screen || 'product' !== $screen->post_type || 'post' !== $screen->base ) { return; }
        echo '<div class="afs-product-guide"><strong>Tambah / Kemas Kini Produk</strong><p>1. Nama & penerangan → 2. Gambar & kategori → 3. Harga & stok → 4. Pratonton & terbitkan.</p><a href="' . esc_url( $this->page_url( 'afs-help' ) ) . '">Buka panduan produk →</a></div>';
    }

    public function settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Akses terhad.', '', array( 'response' => 403 ) ); }
        $this->heading( 'Kawalan Panel Kedai', 'Urus identiti panel, modul klien dan akses operasi melalui akaun pentadbir.' );
        $settings = self::settings();
        settings_errors( 'afs_shop_settings' );
        echo '<form class="afs-card" method="post" action="options.php">';
        settings_fields( 'afs_shop_settings_group' );
        echo '<label for="afs-company">Nama syarikat / kedai</label><input id="afs-company" name="afs_shop_settings[company]" class="regular-text" value="' . esc_attr( $settings['company'] ) . '" required><label for="afs-whatsapp">WhatsApp Alfatihstudio</label><input id="afs-whatsapp" name="afs_shop_settings[whatsapp]" class="regular-text" value="' . esc_attr( $settings['whatsapp'] ) . '" inputmode="tel"><p class="description">Format kod negara tanpa + atau ruang. Kosongkan untuk menggunakan pautan website sahaja.</p>';
        echo '<h2>Modul dan akses klien</h2><p>Pilihan ini terpakai kepada Pengurus Kedai Alfatihstudio. Akses pentadbir kekal tersedia.</p>';
        foreach ( array( 'analytics' => 'Analytics moden', 'banners' => 'Pengurusan banner', 'attributes' => 'Pengurusan atribut global', 'refunds' => 'Benarkan pengurus memulangkan bayaran (refund)', 'delete_notes' => 'Benarkan pemadaman nota pesanan' ) as $feature => $label ) {
            echo '<label class="afs-checkbox"><input type="checkbox" name="afs_shop_settings[' . esc_attr( $feature ) . ']" value="1" ' . checked( ! empty( $settings[ $feature ] ), true, false ) . '> ' . esc_html( $label ) . '</label>';
        }
        echo '<p class="description">Refund ialah pemulangan bayaran. Refund gateway boleh memulangkan wang sebenar; refund manual hanya merekodkan pulangan sehingga bayaran dibuat melalui bank/gateway. Suis ini mengawal akses pengurus, bukan bayaran automatik.</p>';
        echo '<p class="description">Pemasangan baharu mematikan refund dan pemadaman nota secara lalai. Kemas kini mengekalkan pilihan dan akses pemasangan sedia ada sehingga administrator menukarnya. Mematikan banner tidak menutup banner website yang sudah aktif.</p>';
        $hpos = class_exists( '\\Automattic\\WooCommerce\\Utilities\\OrderUtil' ) && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
        echo '<label for="afs-frontend-ajax">Tindakan AJAX tambahan tema / plugin</label><textarea id="afs-frontend-ajax" class="large-text" rows="4" name="afs_shop_settings[frontend_ajax_actions]">' . esc_html( $settings['frontend_ajax_actions'] ) . '</textarea><p class="description">Untuk carian atau quick view kedai yang disekat: masukkan nama tindakan tepat, satu setiap baris (maksimum 20). Dapatkan nama daripada pembangun tema. Pengendali asal masih perlu menyemak akses dan nonce. Suis refund serta pemadaman nota tetap dikuatkuasakan.</p>';
        echo '<h2>Gambar klien</h2><label for="afs-image-mb">Had upload gambar (MB)</label><input id="afs-image-mb" type="number" min="1" max="10" step="1" name="afs_shop_settings[image_upload_mb]" value="' . (int) $settings['image_upload_mb'] . '"><label for="afs-image-dimension">Maksimum sisi terpanjang (px)</label><input id="afs-image-dimension" type="number" min="1920" max="6000" step="1" name="afs_shop_settings[image_upload_dimension]" value="' . (int) $settings['image_upload_dimension'] . '"><p class="description">Had upload hanya untuk klien; had hosting yang lebih rendah masih terpakai. Had keseluruhan 24 megapiksel. Optimize dibuat secara manual satu gambar pada satu masa, maksimum 1,600px dan kualiti 78%. JPG ditukar kepada WebP jika disokong. Fail asal dikekalkan untuk pemulihan.</p>';
        submit_button( 'Simpan Tetapan' );
        echo '</form><section class="afs-card afs-note"><h2>Status Panel</h2><p>Shop Admin ' . esc_html( AFS_SHOP_VERSION ) . ' · WordPress ' . esc_html( get_bloginfo( 'version' ) ) . ' · WooCommerce ' . esc_html( defined( 'WC_VERSION' ) ? WC_VERSION : 'Tidak aktif' ) . '</p><p>Storan pesanan: ' . ( $hpos ? 'HPOS' : 'Legacy' ) . '. Laporan: WooCommerce Analytics moden.</p><p>Untuk import data sejarah dan tetapan statistik, gunakan <a href="' . esc_url( admin_url( 'admin.php?page=wc-admin&path=/analytics/settings' ) ) . '">Tetapan Analytics WooCommerce</a>.</p><p>Banner perlu dipasang melalui shortcode pada halaman website. ' . esc_html( AFS_Shop_Cache::message() ) . '</p></section>';
        echo '<section class="afs-card afs-note"><h2>Akses klien</h2><p>Melalui Users → Edit, tetapkan akaun klien kepada <strong>Pengurus Kedai Alfatihstudio</strong>. Kekalkan akaun anda sendiri sebagai Administrator. Plugin tidak menukar peranan akaun secara automatik.</p><p>Perubahan peranan pengguna ialah tindakan berasingan. Semak skrin kedai menggunakan akaun ujian dahulu.</p></section></div>';
    }
}
