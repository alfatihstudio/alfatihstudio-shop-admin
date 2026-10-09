<?php
defined( 'ABSPATH' ) || exit;

/** Purge page caches after a committed change or scheduled visibility boundary. */
final class AFS_Shop_Cache {
    const OPTION = 'afs_shop_cache_status';

    public function __construct() {
        add_action( 'afs_banners_saved', array( $this, 'purge' ), 20, 2 );
        add_action( 'afs_banner_schedule_cache', array( $this, 'purge_scheduled' ), 20, 1 );
    }

    public function purge_scheduled( $banners ) {
        $this->purge( $banners, array() );
    }

    public static function providers() {
        $providers = array();
        if ( has_action( 'litespeed_purge' ) ) { $providers['litespeed'] = 'LiteSpeed Cache'; }
        if ( function_exists( 'rocket_clean_domain' ) ) { $providers['rocket'] = 'WP Rocket'; }
        if ( function_exists( 'wp_cache_clear_cache' ) ) { $providers['super'] = 'WP Super Cache'; }
        if ( function_exists( 'w3tc_flush_posts' ) ) { $providers['w3'] = 'W3 Total Cache'; }
        if ( has_action( 'afs_shop_purge_external_cache' ) ) { $providers['external'] = 'Cache tambahan'; }
        return $providers;
    }

    public function purge( $clean, $previous ) {
        if ( $clean === $previous ) { return; }
        $results = array();
        foreach ( self::providers() as $provider => $label ) {
            try {
                $result = null;
                switch ( $provider ) {
                    // Public page wildcard, not LiteSpeed's purge-all object/opcache operation.
                    case 'litespeed': do_action( 'litespeed_purge', '*' ); break;
                    case 'rocket': $result = rocket_clean_domain(); break;
                    case 'super': $result = wp_cache_clear_cache( get_current_blog_id() ); break;
                    case 'w3': $result = w3tc_flush_posts(); break;
                    case 'external': do_action( 'afs_shop_purge_external_cache', $clean, $previous ); break;
                }
                $results[ $label ] = false === $result ? 'failed' : 'requested';
            } catch ( Throwable $error ) {
                // The banner is already committed; a cache failure must not report a failed save.
                $results[ $label ] = 'failed';
            }
        }
        update_option( self::OPTION, array( 'time' => time(), 'providers' => $results ), false );
    }

    public static function message() {
        $status = (array) get_option( self::OPTION, array() );
        if ( empty( $status['time'] ) ) {
            return self::providers() ? 'Cache yang disokong akan dibersihkan selepas perubahan banner disimpan.' : 'Tiada sambungan cache yang disokong dikesan. Cache hosting atau CDN perlu disambungkan oleh pentadbir.';
        }
        $requested = array(); $failed = array();
        foreach ( (array) ( $status['providers'] ?? array() ) as $label => $result ) {
            if ( 'requested' === $result ) { $requested[] = $label; } else { $failed[] = $label; }
        }
        $message = 'Semakan cache terakhir: ' . wp_date( 'd/m/Y H:i', (int) $status['time'] ) . '. ';
        $message .= $requested ? 'Permintaan pembersihan dihantar kepada ' . implode( ', ', $requested ) . '. ' : 'Tiada permintaan pembersihan berjaya dihantar. ';
        if ( $failed ) { $message .= 'Perlu semakan: ' . implode( ', ', $failed ) . '. '; }
        if ( ! $requested && ! $failed ) { $message .= 'Tiada sambungan cache yang disokong dikesan. '; }
        return $message . 'Cache browser, hosting dan CDN berasingan mungkin masih perlu disemak.';
    }
}
