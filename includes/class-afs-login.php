<?php
defined( 'ABSPATH' ) || exit;

/** Brand the native login flows without replacing authentication or form handlers. */
final class AFS_Shop_Login {
    public function __construct() {
        add_action( 'login_enqueue_scripts', array( $this, 'assets' ), 100 );
        add_filter( 'login_body_class', array( $this, 'body_class' ) );
        add_filter( 'login_headerurl', array( $this, 'logo_url' ) );
        add_filter( 'login_headertext', array( $this, 'logo_text' ) );
        add_filter( 'login_title', array( $this, 'title' ), 20, 2 );
        add_filter( 'login_message', array( $this, 'intro' ), 20 );
        add_action( 'login_footer', array( $this, 'footer' ) );
        // Only login requests get these label translations, never the storefront or admin.
        add_action( 'login_init', array( $this, 'translate_labels' ) );
    }

    private static function company() {
        $settings = AFS_Shop_Admin::settings();
        $name = isset( $settings['company'] ) && is_string( $settings['company'] ) ? sanitize_text_field( $settings['company'] ) : '';
        return '' !== $name ? $name : sanitize_text_field( get_bloginfo( 'name' ) );
    }

    public function assets() {
        wp_enqueue_style( 'afs-login', AFS_SHOP_URL . 'assets/login.css', array( 'login' ), AFS_SHOP_VERSION );
    }

    public function body_class( $classes ) {
        $classes[] = 'afs-login';
        return $classes;
    }

    public function logo_url( $url ) { return home_url( '/' ); }
    public function logo_text( $text ) { return self::company() . ' — Alfatihstudio'; }

    public static function heading() {
        global $action;
        switch ( $action ) {
            case 'lostpassword': case 'retrievepassword': return 'Lupa Kata Laluan';
            case 'resetpass': case 'rp': return 'Tetapkan Kata Laluan';
            case 'register': return 'Daftar Akaun';
            case 'confirm_admin_email': return 'Semak Email Pentadbir';
            default: return 'Log Masuk';
        }
    }

    public function title( $title, $original = '' ) {
        return esc_html( self::heading() . ' — ' . self::company() . ' | Alfatihstudio' );
    }

    public function intro( $message ) {
        global $action;
        $description = 'Masuk untuk mengurus pesanan, produk dan operasi kedai anda.';
        if ( in_array( $action, array( 'lostpassword', 'retrievepassword' ), true ) ) { $description = 'Masukkan nama pengguna atau email akaun untuk mendapatkan pautan pemulihan.'; }
        elseif ( in_array( $action, array( 'resetpass', 'rp' ), true ) ) { $description = 'Tetapkan kata laluan baharu melalui pautan pemulihan akaun anda.'; }
        elseif ( 'register' === $action ) { $description = 'Lengkapkan maklumat untuk pendaftaran akaun.'; }
        elseif ( 'confirm_admin_email' === $action ) { $description = 'Semak alamat email pentadbir untuk memastikan akaun boleh dihubungi.'; }
        // Preserve notices from core and security plugins exactly as supplied.
        return '<div class="afs-login-intro"><span class="afs-login-kicker">PANEL PENGURUSAN KEDAI</span><h2>' . esc_html( self::company() ) . '</h2><p class="afs-login-heading">' . esc_html( self::heading() ) . '</p><p>' . esc_html( $description ) . '</p></div>' . $message;
    }

    public function footer() {
        echo '<div class="afs-login-footer"><span>Panel kedai oleh Alfatihstudio</span><a href="' . esc_url( home_url( '/' ) ) . '">Kembali ke website kedai</a></div>';
    }

    public function translate_labels() {
        add_filter( 'gettext', array( $this, 'labels' ), 20, 3 );
    }

    public function labels( $translation, $original, $domain ) {
        if ( 'default' !== $domain ) { return $translation; }
        $labels = array(
            'Username or Email Address' => 'Nama pengguna atau email',
            'Username' => 'Nama pengguna',
            'Email' => 'Email',
            'Password' => 'Kata laluan',
            'Remember Me' => 'Ingat saya',
            'Log In' => 'Log Masuk',
            'Log in' => 'Log masuk',
            'Lost your password?' => 'Lupa kata laluan?',
            'Get New Password' => 'Hantar Pautan Pemulihan',
            'Reset Password' => 'Tetapkan Kata Laluan',
            'Save Password' => 'Simpan Kata Laluan',
            'New password' => 'Kata laluan baharu',
            'Confirm new password' => 'Sahkan kata laluan baharu',
            'Show password' => 'Tunjukkan kata laluan',
            'Hide password' => 'Sembunyikan kata laluan',
            'Register' => 'Daftar',
            'You are now logged out.' => 'Anda telah log keluar.',
            'Go to %s' => 'Kembali ke %s',
            '&larr; Go to %s' => '&larr; Kembali ke %s',
        );
        return $labels[ $original ] ?? $translation;
    }
}
