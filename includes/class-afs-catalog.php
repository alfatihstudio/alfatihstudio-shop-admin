<?php
/** Client catalog presentation; native WooCommerce handlers remain authoritative. */
defined( 'ABSPATH' ) || exit;
final class AFS_Shop_Catalog {
    private $kind = '';
    private $writer = false;
    public function __construct() {
        add_action( 'current_screen', array( $this, 'screen' ), 5 );
        add_action( 'admin_enqueue_scripts', array( $this, 'assets' ), 30 );
        add_filter( 'default_hidden_columns', array( $this, 'hidden_columns' ), 100, 2 );
        add_filter( 'admin_body_class', array( $this, 'body_class' ) );
    }
    public static function kind( $screen ) {
        if ( ! $screen ) { return ''; }
        if ( in_array( ( $screen->taxonomy ?? '' ), array( 'product_cat', 'afs_writer' ), true ) && in_array( $screen->base, array( 'edit-tags', 'term' ), true ) ) { return 'categories'; }
        if ( 'product' !== ( $screen->post_type ?? '' ) ) { return ''; }
        if ( 'edit' === $screen->base && 'edit-product' === $screen->id ) { return 'products'; }
        return 'post' === $screen->base ? 'editor' : '';
    }
    public function screen( $screen ) {
        if ( ! AFS_Shop_Access::is_client() ) { return; }
        $this->kind = self::kind( $screen );
        $this->writer = 'afs_writer' === ( $screen->taxonomy ?? '' );
        if ( ! $this->kind ) { return; }
        add_filter( 'gettext', array( $this, 'labels' ), 20, 3 );
        add_filter( 'gettext_with_context', array( $this, 'context_labels' ), 20, 4 );
        add_filter( 'ngettext', array( $this, 'plural' ), 20, 5 );
        if ( 'editor' === $this->kind ) { add_filter( 'tiny_mce_before_init', array( $this, 'editor_style' ) ); }
        if ( 'products' === $this->kind ) { add_filter( 'manage_edit-product_columns', array( $this, 'columns' ), 100 ); }
    }
    public function body_class( $classes ) { return $classes . ( $this->kind ? ' afs-catalog afs-catalog-' . $this->kind : '' ); }
    public function assets() {
        if ( ! $this->kind ) { return; }
        wp_enqueue_style( 'afs-catalog', AFS_SHOP_URL . 'assets/catalog.css', array( 'afs-admin' ), AFS_SHOP_VERSION );
        wp_enqueue_script( 'afs-catalog', AFS_SHOP_URL . 'assets/catalog.js', array(), AFS_SHOP_VERSION, true );
        wp_localize_script( 'afs-catalog', 'afsCatalog', array( 'writer' => $this->writer, 'kind' => $this->kind, 'newProduct' => 'post-new.php' === ( $GLOBALS['pagenow'] ?? '' ) ) );
    }
    public function editor_style( $settings ) {
        $settings['content_style'] = ( $settings['content_style'] ?? '' ) . ' body{font-family:system-ui,-apple-system,BlinkMacSystemFont,sans-serif;font-size:14px;line-height:1.75;color:#24313a;padding:8px 12px} h1,h2,h3{font-family:inherit;line-height:1.4} h2{font-size:20px}';
        return $settings;
    }
    public function context_labels( $translated, $original, $context, $domain ) { return $this->labels( $translated, $original, $domain ); }
    public function hidden_columns( $hidden, $screen ) {
        if ( AFS_Shop_Access::is_client() && 'products' === self::kind( $screen ) ) {
            return array_values( array_unique( array_merge( $hidden, array( 'product_tag', 'taxonomy-product_brand', 'featured' ) ) ) );
        }
        return $hidden;
    }
    public function columns( $columns ) {
        // Keep optional data available through the native Screen Options panel.
        $names = array( 'name'=>'Produk', 'sku'=>'SKU / ISBN', 'is_in_stock'=>'Stok', 'price'=>'Harga', 'product_cat'=>'Kategori', 'product_tag'=>'Tag', 'product_brand'=>'Jenama', 'taxonomy-product_brand'=>'Jenama', 'date'=>'Tarikh' );
        foreach ( $names as $key=>$name ) { if ( isset( $columns[$key] ) ) { $columns[$key]=$name; } }
        return $columns;
    }
    public function plural( $translated, $single, $plural, $number, $domain ) {
        if ( ! $this->kind || ! in_array( $domain, array( 'default', 'woocommerce' ), true ) ) { return $translated; }
        return '%s item' === $single && '%s items' === $plural ? '%s rekod' : $translated;
    }
    public function labels( $translated, $original, $domain ) {
        if ( ! $this->kind || ! in_array( $domain, array( 'default', 'woocommerce' ), true ) ) { return $translated; }
        $labels = array(
            'Screen Options'=>'Paparan Jadual', 'Products'=>'Produk', 'Add new product'=>'Tambah Produk', 'Add New Product'=>'Tambah Produk', 'Edit product'=>'Edit Produk', 'Edit Product'=>'Edit Produk',
            'Search products'=>'Cari produk', 'All'=>'Semua', 'Published'=>'Diterbitkan', 'Drafts'=>'Draf', 'Draft'=>'Draf', 'Trash'=>'Tong sampah', 'Sorting'=>'Susunan',
            'Bulk edit'=>'Edit pukal', 'Bulk actions'=>'Tindakan pukal', 'Apply'=>'Jalankan', 'Filter'=>'Tapis', 'All dates'=>'Semua tarikh', 'Select a category'=>'Semua kategori',
            'Filter by product type'=>'Jenis produk', 'Filter by stock status'=>'Status stok', 'Filter by brand'=>'Jenama',
            'In stock'=>'Ada stok', 'Out of stock'=>'Habis stok', 'On backorder'=>'Tempahan awal', 'On backorders'=>'Tempahan awal',
            'Name'=>'Nama', 'Image'=>'Gambar', 'Stock'=>'Stok', 'Price'=>'Harga', 'Categories'=>'Kategori', 'Tags'=>'Tag', 'Product brands'=>'Jenama', 'Date'=>'Tarikh',
            'Edit'=>'Edit', 'Quick Edit'=>'Edit pantas', 'Quick&nbsp;Edit'=>'Edit&nbsp;pantas', 'View'=>'Lihat', 'Duplicate'=>'Salin produk', 'Preview'=>'Pratonton',
            'Publish'=>'Terbitkan', 'Update'=>'Simpan Perubahan', 'Save Draft'=>'Simpan Draf', 'Preview Changes'=>'Pratonton Perubahan', 'Move to Trash'=>'Pindah ke tong sampah',
            'Permalink:'=>'Pautan produk:', 'Catalog visibility:'=>'Paparan katalog:', 'Shop and search results'=>'Kedai & carian', 'Published on:'=>'Tarikh terbit:', 'Status:'=>'Status:', 'Visibility:'=>'Paparan:', 'Public'=>'Umum', 'Private'=>'Peribadi', 'Password protected'=>'Dilindungi kata laluan',
            'Product description'=>'Penerangan Produk', 'Product short description'=>'Penerangan Ringkas', 'Product data'=>'Harga & Butiran Produk',
            'Product image'=>'Gambar Utama', 'Product gallery'=>'Galeri Produk', 'Product categories'=>'Kategori Produk', 'Product tags'=>'Tag Produk',
            'Set product image'=>'Pilih gambar utama', 'Remove product image'=>'Buang gambar utama', 'Add product gallery images'=>'Tambah gambar galeri',
            'Click the image to edit or update'=>'Klik gambar untuk menukar atau mengemas kini', 'Add Media'=>'Tambah Gambar',
            'General'=>'Harga', 'Inventory'=>'Stok & SKU', 'Shipping'=>'Penghantaran', 'Linked Products'=>'Produk Berkaitan', 'Attributes'=>'Atribut', 'Variations'=>'Variasi', 'Advanced'=>'Tetapan Lanjutan',
            'Simple product'=>'Produk ringkas', 'Variable product'=>'Produk variasi', 'Grouped product'=>'Produk berkumpulan', 'External/Affiliate product'=>'Produk luar / affiliate',
            'Virtual'=>'Tanpa penghantaran', 'Downloadable'=>'Produk muat turun', 'Regular price'=>'Harga biasa', 'Sale price'=>'Harga promosi', 'Schedule'=>'Jadual promosi',
            'Stock status'=>'Status stok', 'Stock quantity'=>'Kuantiti stok', 'Manage stock?'=>'Urus kuantiti stok?', 'Allow backorders?'=>'Benarkan tempahan awal?',
            'Sold individually'=>'Jual satu unit sahaja', 'Weight'=>'Berat', 'Dimensions'=>'Dimensi', 'Length'=>'Panjang', 'Width'=>'Lebar', 'Height'=>'Tinggi',
            'Shipping class'=>'Kelas penghantaran', 'Upsells'=>'Cadangan naik taraf', 'Cross-sells'=>'Cadangan tambahan', 'Purchase note'=>'Nota pembelian',
            'Enable reviews'=>'Benarkan ulasan', 'Menu order'=>'Susunan produk', 'Visual'=>'Editor', 'Code'=>'HTML', 'Text'=>'HTML',
            'Add new category'=>'Tambah Kategori', 'Add New Category'=>'Tambah Kategori', 'Search categories'=>'Cari kategori', 'Parent category'=>'Kategori induk',
            'Description'=>'Penerangan', 'Slug'=>'Pautan ringkas', 'Count'=>'Produk', 'None'=>'Tiada', 'Display type'=>'Jenis paparan', 'Default'=>'Lalai',
            'Thumbnail'=>'Gambar kategori', 'Upload/Add image'=>'Pilih gambar', 'Remove image'=>'Buang gambar', 'Update Category'=>'Simpan Kategori',
            'The name is how it appears on your site.'=>'Nama kategori yang dipaparkan pada website.',
            'The description is not prominent by default; however, some themes may show it.'=>'Penerangan ringkas kategori. Paparannya bergantung pada reka bentuk website.',
            'The “slug” is the URL-friendly version of the name. It is usually all lowercase and contains only letters, numbers, and hyphens.'=>'Bahagian pautan kategori, contohnya buku-kanak-kanak. Kosongkan untuk dijana daripada nama.',
            'Product updated.'=>'Produk berjaya dikemas kini.', 'Product saved.'=>'Produk berjaya disimpan.',
            'Product updated. %1$sView Product%2$s'=>'Produk berjaya dikemas kini. %1$sLihat produk%2$s',
            'Product published. %1$sView Product%2$s'=>'Produk berjaya diterbitkan. %1$sLihat produk%2$s',
        );
        return $labels[$original] ?? $translated;
    }
}
