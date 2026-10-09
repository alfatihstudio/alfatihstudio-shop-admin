<?php
defined( 'ABSPATH' ) || exit;

final class AFS_Shop_Banners {
    const OPTION = 'afs_shop_banners';
    const FEEDBACK_TTL = 600;

    public static function slots() {
        return array( 'utama' => 'Banner Utama', 'promosi' => 'Banner Promosi', 'iklan' => 'Banner Iklan' );
    }

    public function __construct() {
        add_shortcode( 'alfatih_banner', array( $this, 'shortcode' ) );
        add_action( 'admin_post_afs_save_banners', array( $this, 'save' ) );
        add_action( 'afs_banners_saved', array( $this, 'schedule_transition' ), 10, 1 );
        add_action( 'afs_banner_schedule_transition', array( $this, 'transition' ) );
    }

    public static function all( $fresh = false ) {
        if ( ! $fresh ) { return (array) get_option( self::OPTION, array() ); }
        global $wpdb;
        $record = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", self::OPTION ), ARRAY_A );
        if ( $wpdb->last_error ) { return new WP_Error( 'banner_storage_failed', 'Data banner tidak dapat dibaca. Cuba semula sebelum membuat perubahan.' ); }
        return $record ? (array) maybe_unserialize( $record['option_value'] ) : array();
    }

    public static function date_value( $value ) {
        if ( ! is_string( $value ) || ! preg_match( '/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}$/D', $value ) ) { return false; }
        $date = DateTimeImmutable::createFromFormat( '!Y-m-d\TH:i', $value, wp_timezone() );
        $errors = DateTimeImmutable::getLastErrors();
        if ( ! $date || ( $errors && ( $errors['warning_count'] || $errors['error_count'] ) ) || $date->format( 'Y-m-d\TH:i' ) !== $value ) { return false; }
        return $date;
    }

    public static function status( $banner, $now = null ) {
        if ( empty( $banner['active'] ) || empty( $banner['image'] ) ) { return 'inactive'; }
        $now = null === $now ? time() : $now;
        $start = empty( $banner['start'] ) ? null : self::date_value( $banner['start'] );
        $end = empty( $banner['end'] ) ? null : self::date_value( $banner['end'] );
        if ( false === $start || false === $end || ( $start && $end && $end <= $start ) ) { return 'invalid'; }
        if ( $end && $now >= $end->getTimestamp() ) { return 'expired'; }
        if ( $start && $now < $start->getTimestamp() ) { return 'scheduled'; }
        return 'active';
    }

    public static function status_label( $banner ) {
        $labels = array( 'inactive' => 'Tidak aktif', 'invalid' => 'Jadual tidak sah', 'expired' => 'Tamat tempoh', 'scheduled' => 'Dijadualkan', 'active' => 'Aktif' );
        return $labels[ self::status( $banner ) ];
    }

    public function schedule_transition( $banners ) {
        wp_clear_scheduled_hook( 'afs_banner_schedule_transition' );
        $next = null;
        foreach ( $banners as $banner ) {
            if ( ! is_array( $banner ) || empty( $banner['active'] ) ) { continue; }
            foreach ( array( 'start', 'end' ) as $key ) {
                $date = self::date_value( $banner[ $key ] ?? '' );
                if ( $date && $date->getTimestamp() > time() ) { $next = null === $next ? $date->getTimestamp() : min( $next, $date->getTimestamp() ); }
            }
        }
        if ( null !== $next ) { wp_schedule_single_event( $next, 'afs_banner_schedule_transition' ); }
    }

    public function transition() {
        $banners = self::all( true );
        if ( is_wp_error( $banners ) ) { wp_schedule_single_event( time() + 60, 'afs_banner_schedule_transition' ); return; }
        // Reuse the cache adapters at schedule boundaries and schedule the next boundary.
        $this->schedule_transition( $banners );
        do_action( 'afs_banner_schedule_cache', $banners );
    }

    public static function revision( $values ) {
        return hash( 'sha256', maybe_serialize( $values ) );
    }

    /** Compare and swap in the database: protects concurrent saves even with persistent caches. */
    public static function store( $clean, $revision ) {
        global $wpdb;
        $record = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", self::OPTION ), ARRAY_A );
        if ( $wpdb->last_error ) { return new WP_Error( 'banner_storage_failed', 'Data banner tidak dapat dibaca. Tiada perubahan disimpan.' ); }
        $previous = $record ? (array) maybe_unserialize( $record['option_value'] ) : array();
        if ( ! is_string( $revision ) || ! hash_equals( self::revision( $previous ), $revision ) ) {
            return new WP_Error( 'banner_conflict', 'Banner telah berubah sejak borang ini dibuka. Data terkini dimuatkan; semak salinan perubahan anda sebelum mengisi semula.' );
        }
        if ( $record ) {
            $updated = $wpdb->query( $wpdb->prepare(
                "UPDATE {$wpdb->options} SET option_value = %s WHERE option_name = %s AND BINARY option_value = %s",
                maybe_serialize( $clean ), self::OPTION, $record['option_value']
            ) );
            if ( false === $updated ) { return new WP_Error( 'banner_storage_failed', 'Banner belum dapat disimpan. Sila cuba semula.' ); }
            if ( 0 === $updated ) {
                $now = $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", self::OPTION ) );
                if ( $wpdb->last_error || $now !== maybe_serialize( $clean ) ) {
                    return new WP_Error( 'banner_conflict', 'Banner diubah oleh pengguna lain semasa simpanan. Data terkini dimuatkan; semak salinan perubahan anda.' );
                }
            }
        } elseif ( ! add_option( self::OPTION, $clean, '', false ) ) {
            return new WP_Error( 'banner_conflict', 'Banner sudah disimpan oleh pengguna lain. Semak data terkini sebelum mencuba semula.' );
        }
        wp_cache_delete( self::OPTION, 'options' );
        wp_cache_delete( 'alloptions', 'options' );
        wp_cache_delete( 'notoptions', 'options' );
        if ( $previous !== $clean ) {
            if ( $record ) {
                do_action( 'update_option_' . self::OPTION, $previous, $clean, self::OPTION );
                do_action( 'updated_option', self::OPTION, $previous, $clean );
            }
            do_action( 'afs_banners_saved', $clean, $previous );
        }
        return true;
    }

    /** Keep failed submissions local to one user and one redirect, never in the URL. */
    private static function feedback_key( $token ) {
        return 'afs_banner_feedback_' . get_current_user_id() . '_' . $token;
    }

    public static function feedback() {
        $token = isset( $_GET['feedback'] ) && is_string( $_GET['feedback'] ) ? wp_unslash( $_GET['feedback'] ) : '';
        if ( ! preg_match( '/^[a-zA-Z0-9]{24}$/', $token ) ) { return array(); }
        $key = self::feedback_key( $token );
        $feedback = get_transient( $key );
        delete_transient( $key );
        return is_array( $feedback ) ? $feedback : array();
    }

    public static function form_values( $input ) {
        $values = array();
        foreach ( self::slots() as $slot => $label ) {
            $row = isset( $input[ $slot ] ) && is_array( $input[ $slot ] ) ? $input[ $slot ] : array();
            $values[ $slot ] = array(
                'image' => absint( is_scalar( $row['image'] ?? 0 ) ? $row['image'] ?? 0 : 0 ),
                'mobile' => absint( is_scalar( $row['mobile'] ?? 0 ) ? $row['mobile'] ?? 0 : 0 ),
                'alt' => sanitize_text_field( $row['alt'] ?? '' ),
                'url' => is_string( $row['url'] ?? '' ) ? substr( trim( $row['url'] ?? '' ), 0, 2048 ) : '',
                'start' => is_string( $row['start'] ?? '' ) ? substr( $row['start'] ?? '', 0, 16 ) : '',
                'end' => is_string( $row['end'] ?? '' ) ? substr( $row['end'] ?? '', 0, 16 ) : '',
                'width' => absint( is_scalar( $row['width'] ?? 1200 ) ? $row['width'] ?? 1200 : 1200 ),
                'new_tab' => isset( $row['new_tab'] ) && '1' === (string) ( is_scalar( $row['new_tab'] ) ? $row['new_tab'] : '' ),
                'active' => isset( $row['active'] ) && '1' === (string) ( is_scalar( $row['active'] ) ? $row['active'] : '' ),
            );
        }
        return $values;
    }

    private function reject( $input, $error ) {
        $token = wp_generate_password( 24, false, false );
        $data = $error->get_error_data();
        $feedback = array( 'input' => self::form_values( $input ), 'message' => $error->get_error_message(),
            'slot' => is_array( $data ) ? $data['slot'] ?? '' : '',
            'conflict' => 'banner_conflict' === $error->get_error_code(),
            'revision' => isset( $_POST['afs_banner_revision'] ) && is_string( $_POST['afs_banner_revision'] ) ? sanitize_text_field( wp_unslash( $_POST['afs_banner_revision'] ) ) : '' );
        // If temporary storage is unavailable, never imply the input was preserved.
        if ( ! set_transient( self::feedback_key( $token ), $feedback, self::FEEDBACK_TTL ) ) {
            wp_die( esc_html( $error->get_error_message() . ' Borang tidak dapat dipulihkan. Gunakan butang Back untuk menyemak input anda.' ), 'Banner belum disimpan', array( 'response' => 500, 'back_link' => true ) );
        }
        wp_safe_redirect( admin_url( 'admin.php?page=afs-banners&feedback=' . $token ), 303 ); exit;
    }

    public static function validate( $input ) {
        if ( ! is_array( $input ) ) { return new WP_Error( 'invalid_banner_input', 'Borang banner tidak sah.' ); }
        $clean = array();
        foreach ( self::slots() as $slot => $label ) {
            $row = isset( $input[ $slot ] ) && is_array( $input[ $slot ] ) ? $input[ $slot ] : array();
            foreach ( array( 'image', 'mobile', 'alt', 'url' ) as $required ) {
                if ( ! array_key_exists( $required, $row ) || ! is_scalar( $row[ $required ] ) ) {
                    return new WP_Error( 'missing_banner_field', $label . ': borang tidak lengkap. Tiada perubahan disimpan.', array( 'slot' => $slot ) );
                }
            }
            foreach ( array( 'image', 'mobile' ) as $id_field ) {
                if ( ! preg_match( '/^[0-9]+$/D', (string) $row[ $id_field ] ) || strlen( (string) $row[ $id_field ] ) > 18 ) {
                    return new WP_Error( 'invalid_image_id', $label . ': pilihan gambar tidak sah.', array( 'slot' => $slot ) );
                }
            }
            if ( preg_match_all( '/./us', (string) $row['alt'] ) > 250 || strlen( (string) $row['url'] ) > 2048 ) {
                return new WP_Error( 'banner_field_too_long', $label . ': penerangan atau pautan terlalu panjang.', array( 'slot' => $slot ) );
            }
            foreach ( array( 'image', 'mobile', 'alt', 'url', 'active', 'start', 'end', 'width', 'new_tab' ) as $field ) {
                if ( isset( $row[ $field ] ) && ! is_scalar( $row[ $field ] ) ) {
                    return new WP_Error( 'invalid_banner_input', $label . ': format medan tidak sah.', array( 'slot' => $slot ) );
                }
            }
            if ( isset( $row['active'] ) && '1' !== (string) $row['active'] ) {
                return new WP_Error( 'invalid_banner_status', $label . ': status banner tidak sah.', array( 'slot' => $slot ) );
            }
            $item = array( 'image' => absint( $row['image'] ?? 0 ), 'mobile' => absint( $row['mobile'] ?? 0 ),
                'alt' => sanitize_text_field( $row['alt'] ?? '' ), 'url' => '', 'active' => isset( $row['active'] ) && '1' === (string) $row['active'] );
            $item['start'] = $row['start'] ?? '';
            $item['end'] = $row['end'] ?? '';
            $item['width'] = $row['width'] ?? 1200;
            $item['new_tab'] = isset( $row['new_tab'] );
            if ( isset( $row['new_tab'] ) && '1' !== (string) $row['new_tab'] ) { return new WP_Error( 'invalid_banner_status', $label . ': pilihan tab baharu tidak sah.', array( 'slot' => $slot ) ); }
            if ( ! preg_match( '/^[0-9]{1,4}$/D', (string) $item['width'] ) || (int) $item['width'] < 1 || (int) $item['width'] > 7680 ) { return new WP_Error( 'invalid_banner_width', $label . ': lebar mesti 1 hingga 7680 piksel.', array( 'slot' => $slot ) ); }
            $item['width'] = (int) $item['width'];
            foreach ( array( 'start', 'end' ) as $key ) {
                if ( '' !== $item[ $key ] && ! self::date_value( $item[ $key ] ) ) { return new WP_Error( 'invalid_banner_date', $label . ': tarikh atau masa jadual tidak sah.', array( 'slot' => $slot ) ); }
            }
            if ( '' !== $item['start'] && '' !== $item['end'] && self::date_value( $item['end'] ) <= self::date_value( $item['start'] ) ) { return new WP_Error( 'invalid_banner_range', $label . ': masa tamat mesti selepas masa mula.', array( 'slot' => $slot ) ); }
            foreach ( array( 'image', 'mobile' ) as $key ) {
                if ( $item[ $key ] && ( 'attachment' !== get_post_type( $item[ $key ] ) || ! wp_attachment_is_image( $item[ $key ] ) ) ) {
                    return new WP_Error( 'invalid_image', $label . ': pilih fail gambar daripada pustaka media.', array( 'slot' => $slot ) );
                }
            }
            $raw_url = trim( (string) ( $row['url'] ?? '' ) );
            if ( '' !== $raw_url ) {
                if ( ! preg_match( '#^https?://#i', $raw_url ) ) {
                    return new WP_Error( 'invalid_link', $label . ': pautan mesti bermula dengan http:// atau https://.', array( 'slot' => $slot ) );
                }
                $url = esc_url_raw( $raw_url, array( 'http', 'https' ) );
                $parts = wp_parse_url( $url );
                if ( ! $url || ! is_array( $parts ) || empty( $parts['host'] ) || empty( $parts['scheme'] ) ||
                    ! in_array( strtolower( $parts['scheme'] ), array( 'http', 'https' ), true ) ) {
                    return new WP_Error( 'invalid_link', $label . ': masukkan pautan penuh bermula dengan http:// atau https://.', array( 'slot' => $slot ) );
                }
                $item['url'] = $url;
            }
            if ( $item['active'] && ( ! $item['image'] || '' === $item['alt'] ) ) {
                return new WP_Error( 'incomplete_banner', $label . ': gambar utama dan penerangan gambar diperlukan sebelum diaktifkan.', array( 'slot' => $slot ) );
            }
            $clean[ $slot ] = $item;
        }
        return $clean;
    }

    public function save() {
        if ( ! current_user_can( 'afs_manage_banners' ) || ! AFS_Shop_Access::can_use( 'banners' ) ) { wp_die( 'Akses terhad.', '', array( 'response' => 403 ) ); }
        check_admin_referer( 'afs_save_banners' );
        if ( ! isset( $_POST['banners'] ) || ! is_array( $_POST['banners'] ) ) {
            $this->reject( self::all(), new WP_Error( 'invalid_banner_input', 'Borang banner tidak lengkap. Tiada perubahan disimpan.' ) );
        }
        $input = wp_unslash( $_POST['banners'] );
        foreach ( self::slots() as $slot => $label ) {
            if ( ! isset( $input[ $slot ] ) || ! is_array( $input[ $slot ] ) ) {
                $this->reject( array_replace( self::all(), $input ), new WP_Error( 'missing_banner_slot', $label . ': borang tidak lengkap. Tiada perubahan disimpan.', array( 'slot' => $slot ) ) );
            }
        }
        $clean = self::validate( $input );
        if ( is_wp_error( $clean ) ) {
            $this->reject( $input, $clean );
        }
        $revision = isset( $_POST['afs_banner_revision'] ) && is_string( $_POST['afs_banner_revision'] ) ? wp_unslash( $_POST['afs_banner_revision'] ) : '';
        $stored = self::store( $clean, $revision );
        if ( is_wp_error( $stored ) ) { $this->reject( $input, $stored ); }
        wp_safe_redirect( admin_url( 'admin.php?page=afs-banners&saved=1' ), 303 ); exit;
    }

    public function shortcode( $atts ) {
        $atts = shortcode_atts( array( 'slot' => 'utama' ), $atts, 'alfatih_banner' );
        $slot = sanitize_key( $atts['slot'] );
        if ( ! isset( self::slots()[ $slot ] ) ) { return ''; }
        $all = self::all();
        $banner = $all[ $slot ] ?? array();
        if ( ! is_array( $banner ) || 'active' !== self::status( $banner ) ) { return ''; }
        $width = max( 1, min( 7680, absint( $banner['width'] ?? 1200 ) ) );
        $hero = 'utama' === $slot && is_front_page();
        $image = wp_get_attachment_image( $banner['image'], 'full', false, array(
            'alt' => $banner['alt'] ?? '', 'loading' => $hero ? 'eager' : 'lazy',
            'fetchpriority' => $hero ? 'high' : 'auto', 'decoding' => 'async',
            'sizes' => sanitize_text_field( apply_filters( 'afs_banner_sizes', '(max-width: ' . $width . 'px) 100vw, ' . $width . 'px', $slot, $banner ) ), 'style' => 'display:block;width:100%;height:auto;max-width:' . $width . 'px;margin-inline:auto',
        ) );
        if ( ! $image ) { return ''; }
        if ( ! empty( $banner['mobile'] ) ) {
            $mobile = wp_get_attachment_image_src( $banner['mobile'], 'full' );
            if ( $mobile ) {
                $srcset = wp_get_attachment_image_srcset( $banner['mobile'], 'full' );
                $mobile_sizes = sanitize_text_field( apply_filters( 'afs_banner_mobile_sizes', '100vw', $slot, $banner ) );
                $image = '<picture><source media="(max-width:600px)" srcset="' . esc_attr( $srcset ?: $mobile[0] ) . '" sizes="' . esc_attr( $mobile_sizes ) . '" width="' . absint( $mobile[1] ) . '" height="' . absint( $mobile[2] ) . '">' . $image . '</picture>';
            }
        }
        if ( ! empty( $banner['url'] ) ) {
            $target = ! empty( $banner['new_tab'] ) ? ' target="_blank" rel="noopener noreferrer"' : '';
            $image = '<a' . $target . ' href="' . esc_url( $banner['url'] ) . '" style="display:block">' . $image . '</a>';
        }
        return '<div class="afs-banner afs-banner-' . esc_attr( $slot ) . '">' . $image . '</div>';
    }
}
