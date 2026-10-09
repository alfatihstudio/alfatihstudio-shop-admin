<?php
defined( 'ABSPATH' ) || exit;
/** @var array $all @var array $feedback */
?>
<?php if ( ! empty( $feedback['message'] ) ) : ?>
    <div class="notice notice-error" role="alert"><p><?php echo esc_html( $feedback['message'] ); ?></p><p><?php esc_html_e( 'Tiada perubahan disimpan. Semak borang dan mesej di bawah sebelum mencuba semula.', 'alfatihstudio-shop-admin' ); ?></p></div>
<?php elseif ( isset( $_GET['saved'] ) && '1' === $_GET['saved'] ) : ?>
    <div class="notice notice-success" role="status"><p><?php esc_html_e( 'Banner berjaya disimpan.', 'alfatihstudio-shop-admin' ); ?></p></div>
<?php endif; ?>
<p class="afs-note"><?php esc_html_e( 'Banner hanya muncul pada ruang website yang sudah disambungkan. Jika gambar lama masih kelihatan, hubungi Alfatihstudio untuk menyemak cache halaman.', 'alfatihstudio-shop-admin' ); ?></p>
<?php if ( ! empty( $feedback['conflict'] ) ) : ?>
<details class="afs-card afs-note"><summary>Salinan perubahan anda yang belum disimpan</summary>
<?php foreach ( AFS_Shop_Banners::slots() as $draft_slot => $draft_label ) :
    $draft = $feedback['input'][ $draft_slot ] ?? array(); ?>
    <h3><?php echo esc_html( $draft_label ); ?></h3>
    <?php foreach ( array( 'image', 'mobile' ) as $draft_key ) { if ( ! empty( $draft[ $draft_key ] ) ) { echo wp_get_attachment_image( $draft[ $draft_key ], 'medium', false, array( 'alt' => $draft['alt'] ?? '' ) ); } } ?>
    <p><?php echo esc_html( $draft['alt'] ?? '' ); ?></p><p><?php echo esc_html( $draft['url'] ?? '' ); ?></p>
    <p><?php echo esc_html( AFS_Shop_Banners::status_label( $draft ) ); ?> · <?php echo esc_html( ( $draft['start'] ?? '' ) . ' → ' . ( $draft['end'] ?? '' ) ); ?> · <?php echo absint( $draft['width'] ?? 1200 ); ?>px · <?php echo ! empty( $draft['new_tab'] ) ? 'Tab baharu' : 'Tab sama'; ?></p>
<?php endforeach; ?></details>
<?php endif; ?>
<p class="afs-note" role="status"><?php echo esc_html( AFS_Shop_Cache::message() ); ?></p>
<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
    <?php wp_nonce_field( 'afs_save_banners' ); ?>
    <input type="hidden" name="afs_banner_revision" value="<?php echo esc_attr( $revision ); ?>">
    <input type="hidden" name="action" value="afs_save_banners">
    <div class="afs-banner-editor">
    <?php foreach ( AFS_Shop_Banners::slots() as $slot => $label ) :
        $banner = wp_parse_args( $all[ $slot ] ?? array(), array( 'image' => 0, 'mobile' => 0, 'url' => '', 'alt' => '', 'active' => false, 'start' => '', 'end' => '', 'width' => 1200, 'new_tab' => false ) );
        $has_error = ( $feedback['slot'] ?? '' ) === $slot;
        ?>
        <section class="afs-card<?php echo $has_error ? ' afs-card-error' : ''; ?>" aria-labelledby="afs-<?php echo esc_attr( $slot ); ?>-heading">
            <h2 id="afs-<?php echo esc_attr( $slot ); ?>-heading"><?php echo esc_html( $label ); ?></h2>
            <p class="afs-muted"><?php esc_html_e( 'Gunakan gambar yang dioptimumkan dan kekalkan nisbah ruang banner. Gambar mudah alih adalah pilihan.', 'alfatihstudio-shop-admin' ); ?></p>
            <?php if ( $has_error ) : ?><p class="afs-field-error"><?php echo esc_html( $feedback['message'] ); ?></p><?php endif; ?>
            <?php foreach ( array( 'image' => __( 'Gambar Desktop', 'alfatihstudio-shop-admin' ), 'mobile' => __( 'Gambar Mudah Alih', 'alfatihstudio-shop-admin' ) ) as $key => $name ) :
                $id = 'afs-' . $slot . '-' . $key;
                ?>
                <div class="afs-media">
                    <label for="<?php echo esc_attr( $id ); ?>-select"><?php echo esc_html( $name ); ?></label>
                    <div class="afs-media-preview">
                        <?php if ( $banner[ $key ] && wp_attachment_is_image( $banner[ $key ] ) ) {
                            echo wp_get_attachment_image( $banner[ $key ], 'medium', false, array( 'loading' => 'lazy', 'alt' => $banner['alt'] ) );
                        } ?>
                    </div>
                    <input type="hidden" id="<?php echo esc_attr( $id ); ?>" name="banners[<?php echo esc_attr( $slot ); ?>][<?php echo esc_attr( $key ); ?>]" value="<?php echo absint( $banner[ $key ] ); ?>">
                    <button type="button" id="<?php echo esc_attr( $id ); ?>-select" class="button afs-select-image" aria-label="<?php echo esc_attr( sprintf( __( 'Pilih gambar: %1$s, %2$s', 'alfatihstudio-shop-admin' ), $label, $name ) ); ?>"><?php esc_html_e( 'Pilih Gambar', 'alfatihstudio-shop-admin' ); ?></button>
                    <button type="button" class="button-link-delete afs-clear-image" aria-label="<?php echo esc_attr( sprintf( __( 'Buang pilihan gambar: %1$s, %2$s', 'alfatihstudio-shop-admin' ), $label, $name ) ); ?>"><?php esc_html_e( 'Buang', 'alfatihstudio-shop-admin' ); ?></button>
                </div>
            <?php endforeach; ?>
            <label for="afs-<?php echo esc_attr( $slot ); ?>-alt"><?php esc_html_e( 'Penerangan gambar', 'alfatihstudio-shop-admin' ); ?></label>
            <input class="widefat" type="text" id="afs-<?php echo esc_attr( $slot ); ?>-alt" name="banners[<?php echo esc_attr( $slot ); ?>][alt]" value="<?php echo esc_attr( $banner['alt'] ); ?>" maxlength="250">
            <label for="afs-<?php echo esc_attr( $slot ); ?>-url"><?php esc_html_e( 'Pautan destinasi (pilihan)', 'alfatihstudio-shop-admin' ); ?></label>
            <input class="widefat" type="url" id="afs-<?php echo esc_attr( $slot ); ?>-url" name="banners[<?php echo esc_attr( $slot ); ?>][url]" value="<?php echo esc_attr( $banner['url'] ); ?>" placeholder="https://" maxlength="2048">
            <p class="afs-note">Status: <?php echo esc_html( AFS_Shop_Banners::status_label( $banner ) ); ?></p>
            <?php foreach ( array( 'start' => 'Tarikh / masa mula (pilihan)', 'end' => 'Tarikh / masa tamat (pilihan)' ) as $date_key => $date_label ) : ?>
            <label for="afs-<?php echo esc_attr( $slot . '-' . $date_key ); ?>"><?php echo esc_html( $date_label ); ?></label>
            <input class="widefat" type="datetime-local" id="afs-<?php echo esc_attr( $slot . '-' . $date_key ); ?>" name="banners[<?php echo esc_attr( $slot ); ?>][<?php echo esc_attr( $date_key ); ?>]" value="<?php echo esc_attr( $banner[ $date_key ] ); ?>">
            <?php endforeach; ?>
            <p class="afs-muted">Masa mengikut zon website: <?php echo esc_html( wp_timezone()->getName() ); ?>. Kosongkan untuk tanpa had. Masa tamat tidak termasuk dalam tempoh aktif. Cache halaman berubah apabila tugas berjadual WordPress berjalan; hosting perlu menjalankannya tepat masa.</p>
            <label for="afs-<?php echo esc_attr( $slot ); ?>-width">Lebar maksimum banner (piksel)</label>
            <input type="number" min="1" max="7680" step="1" id="afs-<?php echo esc_attr( $slot ); ?>-width" name="banners[<?php echo esc_attr( $slot ); ?>][width]" value="<?php echo absint( $banner['width'] ); ?>">
            <p class="afs-muted">Lebar ini menghadkan paparan dan membantu pemilihan gambar responsif. Ruang tema yang lebih kecil tetap menghadkan banner.</p>
            <label class="afs-checkbox"><input type="checkbox" name="banners[<?php echo esc_attr( $slot ); ?>][new_tab]" value="1" <?php checked( $banner['new_tab'], true ); ?>> Buka pautan dalam tab baharu</label>
            <label class="afs-checkbox"><input type="checkbox" name="banners[<?php echo esc_attr( $slot ); ?>][active]" value="1" <?php checked( $banner['active'], true ); ?>> <?php esc_html_e( 'Aktifkan banner', 'alfatihstudio-shop-admin' ); ?></label>
            <?php if ( current_user_can( 'manage_options' ) ) : ?><p class="afs-muted">Shortcode: <code>[alfatih_banner slot="<?php echo esc_html( $slot ); ?>"]</code></p><?php endif; ?>
        </section>
    <?php endforeach; ?>
    </div>
    <?php submit_button( __( 'Simpan Banner', 'alfatihstudio-shop-admin' ) ); ?>
</form>
