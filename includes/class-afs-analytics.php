<?php
defined( 'ABSPATH' ) || exit;

/** Branded read-only reports backed by WooCommerce's modern Analytics REST controllers. */
final class AFS_Shop_Analytics {
    public function __construct() {
        add_action( 'admin_post_afs_export_analytics', array( $this, 'export' ) );
    }

    public static function permitted() {
        return current_user_can( 'afs_manage_shop' ) && current_user_can( 'view_woocommerce_reports' ) && AFS_Shop_Access::can_use( 'analytics' );
    }

    public static function comparison_mode( $input ) {
        $mode = $input['compare'] ?? 'previous';
        return is_string( $mode ) && in_array( $mode, array( 'previous', 'none' ), true ) ? $mode : new WP_Error( 'invalid_comparison', 'Pilihan perbandingan tidak sah.' );
    }

    public static function previous_dates( $dates ) {
        $days = (int) $dates['from']->diff( $dates['to'] )->format( '%a' ) + 1;
        return array( 'from' => $dates['from']->modify( '-' . $days . ' days' ), 'to' => $dates['from']->modify( '-1 day' ) );
    }

    public static function percentage( $current, $previous ) {
        if ( 0.0 === (float) $previous ) { return 0.0 === (float) $current ? 0.0 : null; }
        return ( (float) $current - (float) $previous ) / abs( (float) $previous ) * 100;
    }

    public static function params( $dates ) {
        $days = (int) $dates['from']->diff( $dates['to'] )->format( '%a' );
        return array( 'after' => $dates['from']->format( 'Y-m-d\T00:00:00' ), 'before' => $dates['to']->format( 'Y-m-d\T23:59:59' ),
            'interval' => $days > 90 ? 'week' : 'day', 'per_page' => 100, 'order' => 'asc', 'orderby' => 'date' );
    }

    private static function revenue( $dates ) {
        $data = self::report( 'revenue/stats', self::params( $dates ) );
        if ( is_wp_error( $data ) ) { return $data; }
        if ( ! isset( $data['totals'], $data['intervals'] ) || ! is_array( $data['totals'] ) || ! is_array( $data['intervals'] ) ) { return new WP_Error( 'invalid_analytics', 'Data ringkasan Analytics tidak lengkap.' ); }
        $metrics = array( 'total_sales', 'net_revenue', 'refunds', 'orders_count' );
        foreach ( $metrics as $key ) {
            if ( ! isset( $data['totals'][ $key ] ) || ! is_numeric( $data['totals'][ $key ] ) ) { return new WP_Error( 'invalid_analytics', 'Data ringkasan Analytics tidak lengkap.' ); }
        }
        foreach ( $data['intervals'] as $row ) {
            if ( ! is_array( $row ) || ! isset( $row['date_start'], $row['date_end'], $row['subtotals'] ) || ! is_string( $row['date_start'] ) || ! is_string( $row['date_end'] ) || ! is_array( $row['subtotals'] ) ) { return new WP_Error( 'invalid_analytics', 'Data tempoh Analytics tidak lengkap.' ); }
            foreach ( $metrics as $key ) { if ( ! isset( $row['subtotals'][ $key ] ) || ! is_numeric( $row['subtotals'][ $key ] ) ) { return new WP_Error( 'invalid_analytics', 'Data tempoh Analytics tidak lengkap.' ); } }
        }
        return $data;
    }

    public static function dataset( $dates, $mode, $include_products = true ) {
        $current = self::revenue( $dates );
        if ( is_wp_error( $current ) ) { return $current; }
        $previous_dates = self::previous_dates( $dates );
        $previous = 'previous' === $mode ? self::revenue( $previous_dates ) : null;
        $products = array();
        if ( $include_products ) {
            $params = self::params( $dates );
            $products = self::report( 'products', array( 'after' => $params['after'], 'before' => $params['before'], 'per_page' => 10, 'orderby' => 'net_revenue', 'order' => 'desc', 'extended_info' => true ) );
            if ( ! is_wp_error( $products ) ) {
                foreach ( $products as $row ) {
                    if ( ! is_array( $row ) || ! isset( $row['product_id'], $row['items_sold'], $row['net_revenue'] ) || ! is_numeric( $row['product_id'] ) || ! is_numeric( $row['items_sold'] ) || ! is_numeric( $row['net_revenue'] ) ) { $products = new WP_Error( 'invalid_analytics', 'Data produk Analytics tidak lengkap.' ); break; }
                }
            }
        }
        return array( 'dates' => $dates, 'mode' => $mode, 'current' => $current, 'previous_dates' => $previous_dates, 'previous' => $previous, 'products' => $products );
    }

    /** Neutralize spreadsheet formulas in text cells; numeric columns stay numeric. */
    public static function csv_text( $text ) {
        $text = str_replace( "\0", '', (string) $text );
        return preg_match( '/^[\x00-\x20]*[=+@-]/', $text ) ? "'" . $text : $text;
    }

    private static function csv_row( $stream, $row ) {
        if ( false === fputcsv( $stream, $row, ',', '"', '' ) ) { throw new RuntimeException( 'CSV write failed.' ); }
    }

    public static function write_csv( $stream, $dataset, $type ) {
        if ( ! in_array( $type, array( 'revenue', 'products' ), true ) ) { return new WP_Error( 'invalid_export', 'Jenis export tidak sah.' ); }
        if ( 'revenue' === $type && is_wp_error( $dataset['previous'] ) ) { return $dataset['previous']; }
        if ( 'products' === $type && is_wp_error( $dataset['products'] ) ) { return $dataset['products']; }
        fwrite( $stream, "\xEF\xBB\xBF" );
        $currency = self::csv_text( get_woocommerce_currency() );
        if ( 'products' === $type ) {
            self::csv_row( $stream, array( 'ID Produk', 'Produk (10 Teratas)', 'Unit Terjual', 'Jualan Bersih', 'Mata Wang', 'Dari', 'Hingga' ) );
            foreach ( $dataset['products'] as $row ) {
                self::csv_row( $stream, array( absint( $row['product_id'] ), self::csv_text( $row['extended_info']['name'] ?? 'Produk #' . $row['product_id'] ), (int) $row['items_sold'], wc_format_decimal( $row['net_revenue'], wc_get_price_decimals() ), $currency, $dataset['dates']['from']->format( 'Y-m-d' ), $dataset['dates']['to']->format( 'Y-m-d' ) ) );
            }
        } else {
            self::csv_row( $stream, array( 'Jenis Baris', 'Tempoh', 'Dari', 'Hingga', 'Pesanan', 'Jualan Bersih', 'Jumlah Jualan', 'Pulangan', 'Mata Wang' ) );
            $periods = array( array( 'Dipilih', $dataset['dates'], $dataset['current'] ) );
            if ( is_array( $dataset['previous'] ) ) { $periods[] = array( 'Sebelumnya', $dataset['previous_dates'], $dataset['previous'] ); }
            foreach ( $periods as $period ) {
                $rows = array( array( 'type' => 'Ringkasan', 'from' => $period[1]['from']->format( 'Y-m-d' ), 'to' => $period[1]['to']->format( 'Y-m-d' ), 'values' => $period[2]['totals'] ) );
                foreach ( $period[2]['intervals'] as $interval ) { $rows[] = array( 'type' => 'Butiran', 'from' => substr( $interval['date_start'], 0, 10 ), 'to' => substr( $interval['date_end'], 0, 10 ), 'values' => $interval['subtotals'] ); }
                foreach ( $rows as $row ) {
                    $values = $row['values'];
                    self::csv_row( $stream, array( $row['type'], $period[0], $row['from'], $row['to'], (int) $values['orders_count'], wc_format_decimal( $values['net_revenue'], wc_get_price_decimals() ), wc_format_decimal( $values['total_sales'], wc_get_price_decimals() ), wc_format_decimal( $values['refunds'], wc_get_price_decimals() ), $currency ) );
                }
            }
        }
        return true;
    }

    public function export() {
        if ( ! self::permitted() ) { wp_die( 'Akses terhad.', '', array( 'response' => 403 ) ); }
        check_admin_referer( 'afs_export_analytics' );
        $input = wp_unslash( $_GET ); $dates = self::dates( $input ); $mode = self::comparison_mode( $input ); $type = $input['type'] ?? '';
        if ( is_wp_error( $dates ) || is_wp_error( $mode ) || ! is_string( $type ) || ! in_array( $type, array( 'revenue', 'products' ), true ) ) { wp_die( 'Tarikh atau pilihan export tidak sah.', '', array( 'response' => 400, 'back_link' => true ) ); }
        $dataset = self::dataset( $dates, 'products' === $type ? 'none' : $mode, 'products' === $type );
        if ( is_wp_error( $dataset ) ) { wp_die( 'Data Analytics belum tersedia. CSV tidak dijana.', '', array( 'response' => 503, 'back_link' => true ) ); }
        $stream = fopen( 'php://temp', 'w+' );
        if ( ! $stream ) { wp_die( 'CSV tidak dapat dijana.', '', array( 'response' => 500 ) ); }
        try { $written = self::write_csv( $stream, $dataset, $type ); } catch ( Throwable $error ) { $written = new WP_Error( 'csv_failed', 'CSV tidak dapat dijana.' ); }
        if ( is_wp_error( $written ) ) { fclose( $stream ); wp_die( 'Laporan belum lengkap. CSV tidak dijana.', '', array( 'response' => 503, 'back_link' => true ) ); }
        rewind( $stream ); nocache_headers();
        header( 'Content-Type: text/csv; charset=UTF-8' );
        header( 'Content-Disposition: attachment; filename="afs-' . $type . '-' . $dates['from']->format( 'Y-m-d' ) . '-' . $dates['to']->format( 'Y-m-d' ) . '.csv"' );
        header( 'X-Content-Type-Options: nosniff' );
        fpassthru( $stream ); fclose( $stream ); exit;
    }

    private static function export_link( $dates, $mode, $type, $label ) {
        $url = add_query_arg( array( 'action' => 'afs_export_analytics', 'from' => $dates['from']->format( 'Y-m-d' ), 'to' => $dates['to']->format( 'Y-m-d' ), 'compare' => $mode, 'type' => $type ), admin_url( 'admin-post.php' ) );
        echo '<a class="button" href="' . esc_url( wp_nonce_url( $url, 'afs_export_analytics' ) ) . '">' . esc_html( $label ) . '</a> ';
    }

    public static function dates( $input ) {
        $today = new DateTimeImmutable( 'today', wp_timezone() );
        $defaults = array( 'from' => $today->modify( 'first day of this month' )->format( 'Y-m-d' ), 'to' => $today->format( 'Y-m-d' ) );
        $dates = array();
        foreach ( $defaults as $key => $default ) {
            $raw = $input[ $key ] ?? $default;
            if ( ! is_string( $raw ) ) { return new WP_Error( 'invalid_dates', 'Tarikh tidak sah.' ); }
            $date = DateTimeImmutable::createFromFormat( '!Y-m-d', $raw, wp_timezone() );
            if ( ! $date || $date->format( 'Y-m-d' ) !== $raw ) { return new WP_Error( 'invalid_dates', 'Gunakan tarikh yang sah.' ); }
            $dates[ $key ] = $date;
        }
        $days = (int) $dates['from']->diff( $dates['to'] )->format( '%r%a' );
        if ( $days < 0 || $days > 365 ) { return new WP_Error( 'invalid_dates', 'Pilih tempoh dari 1 hingga 366 hari, dengan tarikh mula sebelum tarikh tamat.' ); }
        return $dates;
    }

    public static function report( $route, $params ) {
        $request = new WP_REST_Request( 'GET', '/wc-analytics/reports/' . $route );
        $request->set_query_params( $params );
        // Internal dispatch retains the logged-in user and all native permission callbacks.
        try { $response = rest_do_request( $request ); }
        catch ( Throwable $error ) { return new WP_Error( 'afs_analytics_exception', 'Pemprosesan laporan gagal pada server.' ); }
        if ( $response->is_error() ) { return $response->as_error(); }
        $data = self::normalize_response( $response->get_data() );
        return is_array( $data ) ? $data : new WP_Error( 'invalid_analytics', 'Respons Analytics tidak sah.' );
    }

    /** Internal REST dispatch keeps stdClass values that HTTP JSON would serialize. */
    public static function normalize_response( $data ) {
        if ( $data instanceof stdClass ) { $data = get_object_vars( $data ); }
        if ( is_array( $data ) ) {
            foreach ( $data as $key => $value ) { $data[$key] = self::normalize_response( $value ); }
        }
        return $data;
    }

    private static function error_notice( $error ) {
        echo '<p role="alert">Data Analytics belum dapat dimuatkan. Cuba semula atau hubungi Alfatihstudio. Kod ralat: <code>' . esc_html( $error->get_error_code() ) . '</code>.</p>';
        if ( current_user_can( 'manage_options' ) ) {
            echo '<details><summary>Butiran untuk pentadbir</summary><p>' . esc_html( $error->get_error_message() ) . '</p></details>';
        }
    }

    public static function render() {
        if ( ! self::permitted() ) {
            wp_die( 'Akses terhad.', '', array( 'response' => 403 ) );
        }
        echo '<div class="wrap afs-content"><div class="afs-heading"><div><h1>Analytics Kedai</h1><p>Jualan dan prestasi produk menggunakan WooCommerce Analytics moden.</p></div></div>';
        $dates = self::dates( wp_unslash( $_GET ) );
        if ( is_wp_error( $dates ) ) {
            echo '<div class="notice notice-error"><p>' . esc_html( $dates->get_error_message() ) . '</p></div>';
            $dates = self::dates( array() );
        }
        $mode = self::comparison_mode( wp_unslash( $_GET ) );
        if ( is_wp_error( $mode ) ) { echo '<div class="notice notice-error"><p>' . esc_html( $mode->get_error_message() ) . '</p></div>'; $mode = 'previous'; }
        echo '<form method="get" class="afs-card afs-analytics-filter"><input type="hidden" name="page" value="afs-analytics"><label for="afs-from">Dari</label><input type="date" id="afs-from" name="from" value="' . esc_attr( $dates['from']->format( 'Y-m-d' ) ) . '" required><label for="afs-to">Hingga</label><input type="date" id="afs-to" name="to" value="' . esc_attr( $dates['to']->format( 'Y-m-d' ) ) . '" required><label for="afs-compare">Bandingkan</label><select id="afs-compare" name="compare"><option value="previous" ' . selected( $mode, 'previous', false ) . '>Tempoh sebelumnya (sama panjang)</option><option value="none" ' . selected( $mode, 'none', false ) . '>Tiada perbandingan</option></select><button class="button button-primary">Tapis Laporan</button></form>';
        $days = (int) $dates['from']->diff( $dates['to'] )->format( '%a' );
        $dataset = self::dataset( $dates, $mode );
        if ( is_wp_error( $dataset ) ) {
            echo '<div class="notice notice-error">'; self::error_notice( $dataset ); echo '</div></div>';
            return;
        }
        $revenue = $dataset['current']; $previous = $dataset['previous'];
        if ( is_wp_error( $previous ) ) { echo '<div class="notice notice-warning"><p>Data tempoh sebelumnya belum tersedia. Perbandingan dan CSV perbandingan belum dapat dijana.</p></div>'; }
        elseif ( is_array( $previous ) ) {
            echo '<p class="afs-note">Tempoh dipilih: ' . esc_html( $dates['from']->format( 'd/m/Y' ) . ' – ' . $dates['to']->format( 'd/m/Y' ) ) . '. Dibandingkan dengan ' . esc_html( $dataset['previous_dates']['from']->format( 'd/m/Y' ) . ' – ' . $dataset['previous_dates']['to']->format( 'd/m/Y' ) ) . '.</p>';
        }
        echo '<div class="afs-export-actions">';
        if ( ! is_wp_error( $previous ) ) { self::export_link( $dates, $mode, 'revenue', 'Export CSV Jualan' ); }
        if ( ! is_wp_error( $dataset['products'] ) ) { self::export_link( $dates, 'none', 'products', 'CSV 10 Produk Teratas' ); }
        echo '</div>';
        $totals = $revenue['totals'];
        echo '<p class="afs-muted">Angka mengikut tetapan tarikh, status pesanan dan pemprosesan data WooCommerce Analytics kedai. Data boleh lewat semasa import atau pemprosesan latar; angka sifar tidak semestinya bermaksud tiada jualan.</p><div class="afs-stats">';
        foreach ( array( 'total_sales' => 'Jumlah Jualan', 'net_revenue' => 'Jualan Bersih', 'refunds' => 'Pulangan', 'orders_count' => 'Pesanan' ) as $key => $label ) {
            echo '<article class="afs-card"><span>' . esc_html( $label ) . '</span><strong>' . ( 'orders_count' === $key ? esc_html( number_format_i18n( $totals[ $key ] ) ) : wp_kses_post( wc_price( $totals[ $key ] ) ) ) . '</strong>';
            if ( is_array( $previous ) ) {
                $before = $previous['totals'][ $key ]; $percent = self::percentage( $totals[ $key ], $before );
                echo '<p class="afs-comparison">Sebelumnya: ' . ( 'orders_count' === $key ? esc_html( number_format_i18n( $before ) ) : wp_kses_post( wc_price( $before ) ) ) . '<br>';
                echo null === $percent ? 'Tiada asas peratus (nilai sebelumnya sifar).' : esc_html( ( $percent > 0 ? '+' : '' ) . number_format_i18n( $percent, 1 ) . '% berbanding sebelumnya.' );
                echo '</p>';
            }
            echo '</article>';
        }
        echo '</div><section class="afs-card afs-note"><h2>Jualan Mengikut Tempoh</h2><div class="afs-table-scroll"><table class="widefat"><thead><tr><th>Tempoh</th><th>Pesanan</th><th>Jualan Bersih</th><th>Jumlah Jualan</th></tr></thead><tbody>';
        foreach ( $revenue['intervals'] as $row ) {
            if ( ! is_array( $row ) ) { continue; }
            $sub = (array) ( $row['subtotals'] ?? array() );
            $label = substr( (string) ( $row['date_start'] ?? '' ), 0, 10 );
            if ( $days > 90 ) { $label .= ' – ' . substr( (string) ( $row['date_end'] ?? '' ), 0, 10 ); }
            echo '<tr><td>' . esc_html( $label ) . '</td><td>' . esc_html( number_format_i18n( $sub['orders_count'] ?? 0 ) ) . '</td><td>' . wp_kses_post( wc_price( $sub['net_revenue'] ?? 0 ) ) . '</td><td>' . wp_kses_post( wc_price( $sub['total_sales'] ?? 0 ) ) . '</td></tr>';
        }
        if ( ! $revenue['intervals'] ) { echo '<tr><td colspan="4">Tiada data untuk tempoh ini.</td></tr>'; }
        echo '</tbody></table></div></section>';
        $products = $dataset['products'];
        echo '<section class="afs-card"><h2>10 Produk Teratas Mengikut Jualan Bersih</h2>';
        if ( is_wp_error( $products ) ) {
            echo '<p role="alert">Laporan produk belum dapat dimuatkan.</p>';
        } else {
            echo '<div class="afs-table-scroll"><table class="widefat"><thead><tr><th>Produk</th><th>Unit Terjual</th><th>Jualan Bersih</th></tr></thead><tbody>';
            foreach ( $products as $row ) {
                if ( ! is_array( $row ) ) { continue; }
                echo '<tr><td>' . esc_html( $row['extended_info']['name'] ?? 'Produk #' . absint( $row['product_id'] ?? 0 ) ) . '</td><td>' . esc_html( number_format_i18n( $row['items_sold'] ?? 0 ) ) . '</td><td>' . wp_kses_post( wc_price( $row['net_revenue'] ?? 0 ) ) . '</td></tr>';
            }
            if ( ! $products ) { echo '<tr><td colspan="3">Tiada jualan produk untuk tempoh ini.</td></tr>'; }
            echo '</tbody></table></div>';
        }
        echo '</section></div>';
    }
}
