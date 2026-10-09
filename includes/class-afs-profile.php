<?php
/** A light presentation layer over the native, client-only profile form. */
defined( 'ABSPATH' ) || exit;
final class AFS_Shop_Profile {
    private $active = false;
    public function __construct() {
        add_action( 'load-profile.php', array( $this, 'load' ) );
    }
    public function load() {
        if ( ! AFS_Shop_Access::is_client() ) { return; }
        $this->active = true;
        add_filter( 'gettext', array( $this, 'labels' ), 20, 3 );
        add_filter( 'get_avatar', array( $this, 'avatar' ) );
        add_filter( 'admin_body_class', array( $this, 'body_class' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
    }
    public function avatar( $avatar ) { return $this->active ? '' : $avatar; }
    public function body_class( $classes ) { return $classes . ( $this->active ? ' afs-profile' : '' ); }
    public function assets() {
        if ( ! $this->active ) { return; }
        wp_enqueue_style( 'afs-profile', AFS_SHOP_URL . 'assets/profile.css', array( 'afs-admin' ), AFS_SHOP_VERSION );
        wp_enqueue_script( 'afs-profile', AFS_SHOP_URL . 'assets/profile.js', array( 'user-profile' ), AFS_SHOP_VERSION, true );
    }
    public function labels( $translated, $original, $domain ) {
        if ( ! $this->active || 'default' !== $domain ) { return $translated; }
        $labels = array(
            'Profile' => 'Profil Saya', 'Personal Options' => 'Pilihan Panel',
            'Name' => 'Maklumat Akaun', 'Username' => 'Nama pengguna',
            'Usernames cannot be changed.' => 'Nama pengguna ini digunakan untuk log masuk dan tidak boleh diubah.',
            'First Name' => 'Nama pertama', 'Last Name' => 'Nama akhir', 'Nickname' => 'Nama panggilan',
            '(required)' => '(wajib)', 'Display name publicly as' => 'Nama paparan',
            'Contact Info' => 'Maklumat Perhubungan', 'Email' => 'Email', 'Website' => 'Website',
            'About Yourself' => 'Maklumat Tambahan', 'Biographical Info' => 'Tentang anda',
            'Account Management' => 'Keselamatan Akaun', 'New Password' => 'Kata laluan baharu',
            'Set New Password' => 'Tetapkan kata laluan baharu', 'Generate Password' => 'Jana kata laluan',
            'Cancel' => 'Batal', 'Hide' => 'Sembunyikan', 'Show' => 'Paparkan',
            'Repeat New Password' => 'Ulang kata laluan baharu', 'Confirm Password' => 'Sahkan kata laluan',
            'Sessions' => 'Sesi log masuk', 'Log Out Everywhere Else' => 'Log keluar peranti lain',
            'Update Profile' => 'Simpan Perubahan', 'Profile updated.' => 'Profil berjaya dikemas kini.',
            'Language' => 'Bahasa panel',
            'If you change this, an email will be sent at your new address to confirm it. <strong>The new address will not become active until confirmed.</strong>' => 'Jika email diubah, pautan pengesahan akan dihantar ke alamat baharu. <strong>Email baharu hanya aktif selepas disahkan.</strong>',
            'Share a little biographical information to fill out your profile. This may be shown publicly.' => 'Maklumat ini pilihan dan mungkin dipaparkan kepada umum oleh website.',
            'Type your new password again.' => 'Masukkan semula kata laluan baharu anda.',
            'Confirm use of weak password' => 'Sahkan penggunaan kata laluan lemah',
            'You are only logged in at this location.' => 'Anda hanya log masuk pada peranti ini.',
            'Application Passwords' => 'Akses Aplikasi',
            'Application passwords allow authentication via non-interactive systems, such as XML-RPC or the REST API, without providing your actual password. Application passwords can be easily revoked. They cannot be used for traditional logins to your website.' => 'Urus kata laluan khas untuk sambungan aplikasi. Akses ini boleh dibatalkan dan tidak digunakan untuk log masuk biasa.',
            'The application password feature requires HTTPS, which is not enabled on this site.' => 'Sambungan HTTPS diperlukan untuk menggunakan akses aplikasi.',
            'Strength indicator' => 'Kekuatan kata laluan', 'Very weak' => 'Sangat lemah', 'Weak' => 'Lemah', 'Medium' => 'Sederhana', 'Strong' => 'Kuat',
        );
        return $labels[ $original ] ?? $translated;
    }
}
