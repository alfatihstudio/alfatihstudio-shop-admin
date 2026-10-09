<?php
/** Simplify the native media picker for shop clients without extra assets. */
defined( 'ABSPATH' ) || exit;

final class AFS_Shop_Media {
    public function __construct() {
        add_filter( 'media_library_show_audio_playlist', array( $this, 'show_optional_media' ), 100 );
        add_filter( 'media_library_show_video_playlist', array( $this, 'show_optional_media' ), 100 );
        add_filter( 'kadence_blocks_show_image_picker', array( $this, 'show_optional_media' ), 100 );
    }

    /** Preserve provider settings and the full administrator media interface. */
    public function show_optional_media( $show ) {
        return AFS_Shop_Access::is_client() ? false : $show;
    }
}
