<?php
/** Manual, single-image optimisation using the native WordPress image editor. */
defined( 'ABSPATH' ) || exit;

final class AFS_Shop_Image_Optimizer {
    const META = '_afs_image_optimization';

    public function __construct() {
        add_filter( 'wp_handle_upload_prefilter', array( $this, 'check_upload' ) );
        add_filter( 'wp_handle_sideload_prefilter', array( $this, 'check_upload' ) );
        add_filter( 'attachment_fields_to_edit', array( $this, 'field' ), 20, 2 );
        add_action( 'wp_enqueue_media', array( $this, 'assets' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'attachment_assets' ) );
        add_action( 'wp_ajax_afs_optimize_image', array( $this, 'ajax' ) );
        add_action( 'delete_attachment', array( $this, 'delete_backup' ) );
    }

    public static function limits() {
        $settings = (array) get_option( 'afs_shop_settings', array() );
        return array(
            'mb' => max( 1, min( 10, (int) ( $settings['image_upload_mb'] ?? 3 ) ) ),
            'dimension' => max( 1920, min( 6000, (int) ( $settings['image_upload_dimension'] ?? 6000 ) ) ),
        );
    }

    /** Inspect real image headers and bytes, never trust browser MIME/size alone. */
    public function check_upload( $file ) {
        if ( ! AFS_Shop_Access::is_client() || ! empty( $file['error'] ) || empty( $file['tmp_name'] ) ) { return $file; }
        $size = wp_getimagesize( $file['tmp_name'] );
        if ( ! $size ) { return $file; } // Non-image policy remains with WordPress.
        $limits = self::limits();
        $bytes = filesize( $file['tmp_name'] );
        if ( false === $bytes || $bytes > $limits['mb'] * 1024 * 1024 ) {
            $file['error'] = sprintf( 'Gambar melebihi had %d MB. Sila kecilkan gambar sebelum upload.', $limits['mb'] );
        } elseif ( max( $size[0], $size[1] ) > $limits['dimension'] || $size[0] * $size[1] > 24000000 ) {
            $file['error'] = sprintf( 'Dimensi gambar terlalu besar. Maksimum sisi terpanjang %dpx dan 24 megapiksel. Sila kecilkan gambar sebelum upload.', $limits['dimension'] );
        }
        return $file;
    }

    public static function can_use() {
        return current_user_can( 'upload_files' ) && ( AFS_Shop_Access::is_client() || current_user_can( 'manage_options' ) );
    }

    public static function can_edit( $id ) {
        return self::can_use() && 'attachment' === get_post_type( $id ) && (
            current_user_can( 'edit_post', $id ) || (
                AFS_Shop_Access::is_client() && (int) get_post_field( 'post_author', $id ) === get_current_user_id()
            )
        );
    }

    public function field( $fields, $post ) {
        if ( ! self::can_use() || ! self::can_edit( $post->ID ) || ! wp_attachment_is_image( $post->ID ) ) { return $fields; }
        if ( ! in_array( get_post_mime_type( $post->ID ), array( 'image/jpeg', 'image/png', 'image/webp', 'image/avif' ), true ) ) { return $fields; }
        $state = get_post_meta( $post->ID, self::META, true );
        $html = '<div class="afs-image-tools">';
        if ( is_array( $state ) && ! empty( $state['status'] ) ) {
            $html .= '<p>' . esc_html( self::summary( $state ) ) . '</p>';
            if ( in_array( $state['status'], array( 'optimized', 'pending' ), true ) ) {
                $html .= '<button type="button" class="button afs-image-action" data-id="' . (int) $post->ID . '" data-operation="restore">Pulihkan gambar asal</button>';
            }
        }
        if ( ! is_array( $state ) || ! in_array( $state['status'] ?? '', array( 'optimized', 'pending' ), true ) ) {
            $html .= '<button type="button" class="button button-primary afs-image-action" data-id="' . (int) $post->ID . '" data-operation="optimize">Optimize gambar</button><p class="description">Satu gambar pada satu masa · maksimum 1,600px · kualiti 78%. Fail asal disimpan.</p>';
        }
        $html .= '<p class="afs-image-result" role="status" aria-live="polite"></p></div>';
        $fields['afs_image_optimizer'] = array( 'label' => 'Optimize gambar', 'input' => 'html', 'html' => $html );
        return $fields;
    }

    public function assets() {
        if ( ! is_admin() || ! self::can_use() ) { return; }
        wp_enqueue_script( 'afs-image-optimizer', AFS_SHOP_URL . 'assets/image-optimizer.js', array(), AFS_SHOP_VERSION, true );
        wp_localize_script( 'afs-image-optimizer', 'afsImageOptimizer', array( 'url' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce( 'afs_optimize_image' ) ) );
    }

    public function attachment_assets() {
        $screen = get_current_screen();
        if ( $screen && 'attachment' === $screen->post_type ) { $this->assets(); }
    }

    public static function summary( $state ) {
        if ( 'pending' === ( $state['status'] ?? '' ) ) { return 'Pemprosesan belum selesai. Pulihkan gambar asal sebelum meneruskan.'; }
        if ( 'restored' === ( $state['status'] ?? '' ) ) { return 'Gambar asal telah dipulihkan.'; }
        if ( 'unchanged' === ( $state['status'] ?? '' ) ) { return 'Sudah disemak. Fail asal lebih kecil atau sama saiz; gambar dikekalkan.'; }
        $before = (int) ( $state['before'] ?? 0 ); $after = (int) ( $state['after'] ?? 0 );
        return sprintf( 'Dioptimumkan: %s → %s. Jimat %d%%.', size_format( $before ), size_format( $after ), $before > 0 ? round( 100 * ( $before - $after ) / $before ) : 0 );
    }

    public function ajax() {
        if ( ! check_ajax_referer( 'afs_optimize_image', 'nonce', false ) || ! self::can_use() ) {
            wp_send_json_error( array( 'message' => 'Akses tidak sah. Muat semula halaman dan cuba lagi.' ), 403 );
        }
        $raw = $_POST['id'] ?? '';
        $id = is_scalar( $raw ) && preg_match( '/^[1-9][0-9]*$/', (string) $raw ) ? (int) $raw : 0;
        $operation = $_POST['operation'] ?? '';
        if ( ! $id || ! in_array( $operation, array( 'optimize', 'restore' ), true ) || 'attachment' !== get_post_type( $id ) || ! self::can_edit( $id ) ) {
            wp_send_json_error( array( 'message' => 'Gambar atau tindakan tidak sah.' ), 403 );
        }
        $uploads = wp_upload_dir();
        if ( ! empty( $uploads['error'] ) ) { wp_send_json_error( array( 'message' => 'Storan gambar tidak tersedia.' ), 500 ); }
        // A server-wide file lock serialises requests, including separate tabs/users.
        // The OS releases it on errors/disconnects; no stale database lock remains.
        $lock = @fopen( $uploads['basedir'] . '/.afs-image-optimize.lock', 'c' );
        if ( ! $lock || ! flock( $lock, LOCK_EX | LOCK_NB ) ) {
            if ( $lock ) { fclose( $lock ); }
            wp_send_json_error( array( 'message' => 'Satu gambar sedang diproses. Sila tunggu dan cuba lagi.' ), 409 );
        }
        try {
            $result = 'restore' === $operation ? $this->restore( $id ) : $this->optimize( $id );
        } catch ( Throwable $error ) {
            $result = new WP_Error( 'processing', 'Gambar tidak dapat diproses. Cuba gambar yang lebih kecil.' );
        } finally {
            flock( $lock, LOCK_UN ); fclose( $lock );
        }
        if ( is_wp_error( $result ) ) { wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 ); }
        wp_send_json_success( array( 'message' => self::summary( $result ), 'status' => $result['status'] ) );
    }

    /** Only work on local files inside the site's upload directory. */
    private static function local_file( $file ) {
        $uploads = wp_upload_dir();
        $root = realpath( $uploads['basedir'] ); $real = is_string( $file ) ? realpath( $file ) : false;
        return $root && $real && 0 === strpos( $real, $root . DIRECTORY_SEPARATOR ) && is_file( $real ) && is_readable( $real ) ? $real : false;
    }

    public function optimize( $id ) {
        $state = get_post_meta( $id, self::META, true );
        if ( is_array( $state ) && ! empty( $state['status'] ) ) {
            if ( ! in_array( $state['status'], array( 'restored', 'unchanged' ), true ) ) { return $state; }
            if ( 'restored' === $state['status'] && ! empty( $state['optimized_file'] ) ) {
                $cleanup = self::cleanup_restored( $id, $state );
                if ( is_wp_error( $cleanup ) ) { return $cleanup; }
            }
        }
        $file = self::local_file( get_attached_file( $id ) );
        if ( ! $file ) { return new WP_Error( 'local', 'Gambar perlu berada dalam storan tempatan kedai.' ); }
        clearstatcache( true, $file );
        $size = wp_getimagesize( $file ); $before = filesize( $file ); $limits = self::limits();
        $mime = $size['mime'] ?? '';
        if ( ! $size || ! in_array( $mime, array( 'image/jpeg', 'image/png', 'image/webp', 'image/avif' ), true ) ) {
            return new WP_Error( 'format', 'Gunakan gambar statik JPG, PNG, WebP atau AVIF.' );
        }
        if ( $before > $limits['mb'] * 1024 * 1024 || max( $size[0], $size[1] ) > $limits['dimension'] || $size[0] * $size[1] > 24000000 ) {
            return new WP_Error( 'large', 'Gambar melebihi had pemprosesan. Sila kecilkan gambar dahulu.' );
        }
        // Preserve animated WebP/APNG rather than silently reducing it to a still.
        $header = file_get_contents( $file );
        if ( ( 'image/webp' === $mime && strpos( $header, 'ANIM' ) !== false ) || ( 'image/png' === $mime && strpos( $header, 'acTL' ) !== false ) ) {
            return new WP_Error( 'animation', 'Gambar animasi dikekalkan dan tidak dioptimumkan.' );
        }
        unset( $header );
        $memory = wp_convert_hr_to_bytes( ini_get( 'memory_limit' ) );
        if ( $memory > 0 && memory_get_usage( true ) + $size[0] * $size[1] * 8 + 16 * 1024 * 1024 > $memory ) {
            return new WP_Error( 'memory', 'Memori server tidak mencukupi untuk gambar ini. Sila kecilkan dimensi dahulu.' );
        }
        $editor = wp_get_image_editor( $file );
        if ( is_wp_error( $editor ) ) { return new WP_Error( 'editor', 'Server tidak menyokong pemprosesan format gambar ini.' ); }
        if ( method_exists( $editor, 'maybe_exif_rotate' ) ) { $rotate = $editor->maybe_exif_rotate(); if ( is_wp_error( $rotate ) ) { return $rotate; } }
        $dimensions = $editor->get_size();
        if ( max( $dimensions['width'], $dimensions['height'] ) > 1600 ) {
            $result = $editor->resize( 1600, 1600, false );
            if ( is_wp_error( $result ) ) { return $result; }
        }
        $result = $editor->set_quality( 78 );
        if ( is_wp_error( $result ) ) { return $result; }
        $output_mime = 'image/jpeg' === $mime && wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) ) ? 'image/webp' : $mime;
        $extensions = array( 'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/avif' => 'avif' );
        $name = pathinfo( $file, PATHINFO_FILENAME ) . '-afs.' . $extensions[ $output_mime ];
        $target = dirname( $file ) . '/' . wp_unique_filename( dirname( $file ), $name );
        $saved = $editor->save( $target, $output_mime );
        clearstatcache();
        if ( 'image/webp' === $output_mime && 'image/jpeg' === $mime && (
            is_wp_error( $saved ) || ! is_file( $saved['path'] ?? '' ) || filesize( $saved['path'] ) >= $before
        ) ) {
            // Some artwork is smaller as JPEG. Fall back instead of retaining a
            // larger WebP or failing a server-specific WebP encoder.
            if ( ! is_wp_error( $saved ) && ! empty( $saved['path'] ) ) { wp_delete_file( $saved['path'] ); }
            $name = pathinfo( $file, PATHINFO_FILENAME ) . '-afs.jpg';
            $target = dirname( $file ) . '/' . wp_unique_filename( dirname( $file ), $name );
            $saved = $editor->save( $target, 'image/jpeg' );
        }
        if ( is_wp_error( $saved ) ) { return $saved; }
        $target = self::local_file( $saved['path'] ?? '' );
        if ( ! $target || $target === $file ) { return new WP_Error( 'save', 'Fail optimize tidak dapat disimpan.' ); }
        clearstatcache( true, $target );
        $after = filesize( $target );
        if ( false === $after || $after >= $before ) {
            wp_delete_file( $target );
            $state = array( 'status' => 'unchanged', 'before' => $before, 'after' => $before );
            update_post_meta( $id, self::META, $state );
            return $state;
        }
        $old_metadata = wp_get_attachment_metadata( $id );
        $old_mime = get_post_mime_type( $id );
        $backup = array( 'file' => $file, 'metadata' => $old_metadata, 'mime' => $old_mime );
        // Persist the recovery record before native metadata generation (which can
        // save partial metadata). The original files are never overwritten.
        $state = array( 'status' => 'pending', 'before' => $before, 'after' => $after, 'backup' => $backup, 'optimized_file' => $target );
        if ( ! update_post_meta( $id, self::META, $state ) ) { wp_delete_file( $target ); return new WP_Error( 'backup', 'Tidak dapat menyimpan rekod pemulihan.' ); }
        require_once ABSPATH . 'wp-admin/includes/image.php';
        try {
            $metadata = wp_generate_attachment_metadata( $id, $target );
            if ( empty( $metadata['width'] ) || empty( $metadata['height'] ) || empty( $metadata['file'] ) ) { throw new RuntimeException( 'metadata' ); }
            $state['optimized_metadata'] = $metadata;
            update_post_meta( $id, self::META, $state );
            if ( ! update_attached_file( $id, $target ) ) { throw new RuntimeException( 'file' ); }
            $post = wp_update_post( array( 'ID' => $id, 'post_mime_type' => $saved['mime-type'] ), true );
            if ( ! $post || is_wp_error( $post ) ) { throw new RuntimeException( 'mime' ); }
            wp_update_attachment_metadata( $id, $metadata );
            if ( wp_get_attachment_metadata( $id ) !== $metadata ) { throw new RuntimeException( 'metadata save' ); }
            $state['optimized_metadata'] = $metadata;
            $state['status'] = 'optimized';
            if ( ! update_post_meta( $id, self::META, $state ) ) { throw new RuntimeException( 'state' ); }
            return $state;
        } catch ( Throwable $error ) {
            $partial = wp_get_attachment_metadata( $id );
            if ( ! empty( $partial['file'] ) && basename( $partial['file'] ) === basename( $target ) ) {
                $state['optimized_metadata'] = $partial;
            }
            update_attached_file( $id, $file );
            wp_update_post( array( 'ID' => $id, 'post_mime_type' => $old_mime ) );
            wp_update_attachment_metadata( $id, $old_metadata );
            $state['status'] = 'pending'; update_post_meta( $id, self::META, $state );
            return new WP_Error( 'metadata', 'Optimize tidak selesai. Gambar asal dikekalkan; gunakan Pulihkan gambar asal.' );
        }
    }

    public function restore( $id ) {
        $state = get_post_meta( $id, self::META, true );
        $backup = is_array( $state ) ? ( $state['backup'] ?? array() ) : array();
        $file = self::local_file( $backup['file'] ?? '' );
        $current = self::local_file( get_attached_file( $id ) );
        if ( $current && $current !== ( $state['optimized_file'] ?? '' ) && $current !== $file ) {
            return new WP_Error( 'edited', 'Gambar telah diubah selepas optimize. Pemulihan tidak diteruskan supaya suntingan itu dikekalkan.' );
        }
        if ( ! $file || empty( $backup['metadata'] ) || ! in_array( $state['status'] ?? '', array( 'optimized', 'pending' ), true ) ) {
            return new WP_Error( 'backup', 'Fail asal tidak tersedia untuk pemulihan.' );
        }
        if ( get_attached_file( $id ) !== $file && ! update_attached_file( $id, $file ) ) { return new WP_Error( 'restore', 'Pemulihan fail gagal.' ); }
        $result = wp_update_post( array( 'ID' => $id, 'post_mime_type' => $backup['mime'] ), true );
        if ( ! $result || is_wp_error( $result ) ) { return new WP_Error( 'restore', 'Pemulihan jenis fail gagal. Cuba lagi.' ); }
        wp_update_attachment_metadata( $id, $backup['metadata'] );
        if ( wp_get_attachment_metadata( $id ) !== $backup['metadata'] ) { return new WP_Error( 'restore', 'Pemulihan metadata gagal. Cuba lagi.' ); }
        $state['status'] = 'restored'; update_post_meta( $id, self::META, $state );
        if ( get_post_meta( $id, self::META, true ) !== $state ) { return new WP_Error( 'restore', 'Rekod pemulihan belum disimpan. Cuba lagi.' ); }
        $cleanup = self::cleanup_restored( $id, $state );
        if ( is_wp_error( $cleanup ) ) { return $cleanup; }
        $state = array( 'status' => 'restored' );
        update_post_meta( $id, self::META, $state );
        return $state;
    }

    /** Remove only the inactive generated set; never remove active/original files. */
    private static function cleanup_restored( $id, $state ) {
        $active = self::local_file( get_attached_file( $id ) );
        $original = self::local_file( $state['backup']['file'] ?? '' );
        if ( ! $active || $active !== $original ) { return new WP_Error( 'edited', 'Fail telah berubah selepas pemulihan. Rekod asal dikekalkan.' ); }
        $generated = self::local_file( $state['optimized_file'] ?? '' );
        if ( ! $generated ) { return true; }
        $protected = array( $active );
        $current_meta = wp_get_attachment_metadata( $id );
        foreach ( array( $current_meta, $state['backup']['metadata'] ?? array() ) as $metadata ) {
            foreach ( $metadata['sizes'] ?? array() as $size ) {
                if ( ! empty( $size['file'] ) ) { $protected[] = dirname( $active ) . '/' . basename( $size['file'] ); }
            }
            if ( ! empty( $metadata['original_image'] ) ) { $protected[] = dirname( $active ) . '/' . basename( $metadata['original_image'] ); }
        }
        $files = array( $generated );
        $metadata = $state['optimized_metadata'] ?? array();
        foreach ( $metadata['sizes'] ?? array() as $size ) {
            if ( ! empty( $size['file'] ) && basename( $size['file'] ) === $size['file'] ) { $files[] = dirname( $generated ) . '/' . $size['file']; }
        }
        if ( ! empty( $metadata['original_image'] ) && basename( $metadata['original_image'] ) === $metadata['original_image'] ) { $files[] = dirname( $generated ) . '/' . $metadata['original_image']; }
        foreach ( array_unique( $files ) as $candidate ) {
            $file = self::local_file( $candidate );
            if ( ! $file || in_array( $file, $protected, true ) ) { continue; }
            wp_delete_file( $file ); clearstatcache( true, $file );
            if ( is_file( $file ) ) { return new WP_Error( 'cleanup', 'Gambar asal dipulihkan tetapi fail optimize lama belum dapat dibersihkan. Cuba lagi.' ); }
        }
        return true;
    }

    public function delete_backup( $id ) {
        $state = get_post_meta( $id, self::META, true );
        if ( ! is_array( $state ) || empty( $state['backup']['file'] ) ) { return; }
        // Core deletes the active set; clean up the retained inactive set as well.
        $original_active = self::local_file( get_attached_file( $id ) ) === self::local_file( $state['backup']['file'] );
        $file = $original_active ? ( $state['optimized_file'] ?? '' ) : $state['backup']['file'];
        $metadata = $original_active ? ( $state['optimized_metadata'] ?? array() ) : ( $state['backup']['metadata'] ?? array() );
        $file = self::local_file( $file );
        if ( ! $file || $file === get_attached_file( $id ) ) { return; }
        foreach ( $metadata['sizes'] ?? array() as $size ) {
            if ( ! empty( $size['file'] ) && basename( $size['file'] ) === $size['file'] ) { wp_delete_file( dirname( $file ) . '/' . $size['file'] ); }
        }
        if ( ! empty( $metadata['original_image'] ) && basename( $metadata['original_image'] ) === $metadata['original_image'] ) { wp_delete_file( dirname( $file ) . '/' . $metadata['original_image'] ); }
        wp_delete_file( $file );
    }
}
