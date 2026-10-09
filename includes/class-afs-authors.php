<?php
/** Author profiles share one taxonomy archive; books remain native products. */
defined('ABSPATH') || exit;
final class AFS_Shop_Authors {
    const TAX = 'afs_writer';
    public function __construct() {
        add_action('init', array(__CLASS__, 'register'));
        add_action('init', array($this,'rewrite'), 99);
        add_action(self::TAX.'_add_form_fields', array($this,'add_fields'));
        add_action(self::TAX.'_edit_form_fields', array($this,'edit_fields'));
        add_action('created_'.self::TAX, array($this,'save'));
        add_action('edited_'.self::TAX, array($this,'save'));
        add_action('admin_enqueue_scripts', array($this,'assets'));
        add_filter('manage_edit-'.self::TAX.'_columns', array($this,'columns'));
        add_filter('manage_edit-tags_columns', array($this,'ajax_columns'));
        add_filter('manage_'.self::TAX.'_custom_column', array($this,'column'),10,3);
        add_filter('template_include', array($this,'template'),99);
        add_action('wp_enqueue_scripts', array($this,'frontend_assets'));
        add_action('pre_get_posts', array($this,'query'));
        add_action('woocommerce_product_meta_end', array($this,'product_links'));
    }
    public static function register() {
        register_taxonomy(self::TAX, array('product'), array(
            'labels'=>array('name'=>'Penulis','singular_name'=>'Penulis','menu_name'=>'Penulis','search_items'=>'Cari penulis','all_items'=>'Semua penulis','edit_item'=>'Edit Penulis','update_item'=>'Simpan Penulis','add_new_item'=>'Tambah Penulis','new_item_name'=>'Nama penulis','not_found'=>'Tiada penulis ditemui','choose_from_most_used'=>'Pilih penulis sedia ada','separate_items_with_commas'=>'Pisahkan nama penulis dengan koma'),
            'public'=>true,'hierarchical'=>false,'show_ui'=>true,'show_in_rest'=>false,'show_admin_column'=>true,
            'rewrite'=>array('slug'=>'penulis','with_front'=>false),
            'capabilities'=>array('manage_terms'=>'manage_product_terms','edit_terms'=>'edit_product_terms','delete_terms'=>'delete_product_terms','assign_terms'=>'assign_product_terms')
        ));
    }
    public function rewrite() {
        if ('1' !== get_option('afs_writer_rewrite_schema')) { flush_rewrite_rules(false); update_option('afs_writer_rewrite_schema','1',false); }
    }
    private function fields($term=null) {
        $id=$term ? $term->term_id : 0;
        wp_nonce_field('afs_writer_profile','afs_writer_nonce');
        $photo=(int)get_term_meta($id,'afs_writer_photo',true);
        echo '<div class="afs-writer-photo"><input type="hidden" name="afs_writer_photo" value="'. $photo .'">';
        echo '<div class="afs-writer-preview">'.($photo ? wp_get_attachment_image($photo,'thumbnail',false,array('alt'=>'')) : '').'</div><button type="button" class="button afs-writer-pick">Pilih foto penulis</button> <button type="button" class="button afs-writer-remove">Buang foto</button></div>';
        echo '<p><label for="afs-writer-short">Biodata ringkas</label><textarea id="afs-writer-short" name="afs_writer_short" rows="3" maxlength="600">'.esc_textarea(get_term_meta($id,'afs_writer_short',true)).'</textarea></p>';
        echo '<label for="afs-writer-bio">Biodata penuh</label>';
        wp_editor(get_term_meta($id,'afs_writer_bio',true),'afs-writer-bio',array('textarea_name'=>'afs_writer_bio','media_buttons'=>false,'textarea_rows'=>9,'teeny'=>true));
        echo '<p class="description">Buku yang dikaitkan dengan penulis akan dipaparkan secara automatik. Pilih penulis melalui medan Penulis pada produk.</p>';
    }
    public function add_fields() { echo '<div class="form-field afs-writer-fields">';$this->fields();echo '</div>'; }
    public function edit_fields($term) { echo '<tr class="form-field"><th>Profil Penulis</th><td class="afs-writer-fields">';$this->fields($term);echo '</td></tr>'; }
    public function save($id) {
        if (!current_user_can('edit_product_terms') || empty($_POST['afs_writer_nonce']) || !is_string($_POST['afs_writer_nonce']) || !wp_verify_nonce(wp_unslash($_POST['afs_writer_nonce']),'afs_writer_profile')) return;
        $input=wp_unslash($_POST);
        foreach(array('afs_writer_short','afs_writer_bio') as $key) {
            if(isset($input[$key]) && is_string($input[$key])) update_term_meta($id,$key,$key==='afs_writer_short'?sanitize_textarea_field($input[$key]):wp_kses_post($input[$key]));
        }
        if(isset($input['afs_writer_photo']) && is_scalar($input['afs_writer_photo'])) {
            $photo=absint($input['afs_writer_photo']);
            if(!$photo || ('attachment'===get_post_type($photo) && wp_attachment_is_image($photo) && AFS_Shop_Image_Optimizer::can_edit($photo))) update_term_meta($id,'afs_writer_photo',$photo);
        }
    }
    public function assets() {
        $s=get_current_screen();if(!$s || self::TAX!==$s->taxonomy) return;
        wp_enqueue_media();wp_enqueue_editor();
        wp_enqueue_script('afs-writers',AFS_SHOP_URL.'assets/writers.js',array('jquery'),AFS_SHOP_VERSION,true);
        wp_enqueue_style('afs-writers-admin',AFS_SHOP_URL.'assets/writers-admin.css',array(),AFS_SHOP_VERSION);
    }
    public function ajax_columns($cols) { return isset($_POST['taxonomy']) && self::TAX === $_POST['taxonomy'] ? $this->columns($cols) : $cols; }
    public function columns($cols) { unset($cols['description']); $cols['posts']='Buku';return array_slice($cols,0,1,true)+array('afs_photo'=>'Foto')+array_slice($cols,1,null,true); }
    public function column($content,$name,$id) { return 'afs_photo'===$name ? wp_get_attachment_image((int)get_term_meta($id,'afs_writer_photo',true),'thumbnail',false,array('alt'=>'')) : $content; }
    public function template($path) { return is_tax(self::TAX) && false === strpos($path, 'coming-soon') ? AFS_SHOP_DIR.'templates/writer.php' : $path; }
    public function frontend_assets() { if(is_tax(self::TAX)) wp_enqueue_style('afs-writer-profile',AFS_SHOP_URL.'assets/writer-profile.css',array(),AFS_SHOP_VERSION); }
    public function query($q) {
        if(!is_admin() && $q->is_main_query() && $q->is_tax(self::TAX)) {
            $q->set('post_type','product');$q->set('posts_per_page',12);
            $tax=(array)$q->get('tax_query');
            $tax[]=array('taxonomy'=>'product_visibility','field'=>'name','terms'=>array('exclude-from-catalog'),'operator'=>'NOT IN');
            $q->set('tax_query',$tax);
        }
    }
    public function product_links() {
        $terms=get_the_terms(get_the_ID(),self::TAX);if(!$terms || is_wp_error($terms))return;
        $links=array();foreach($terms as $term){$url=get_term_link($term);if(!is_wp_error($url))$links[]='<a href="'.esc_url($url).'">'.esc_html($term->name).'</a>';}
        if($links)echo '<span class="posted_in">Penulis: '.implode(', ',$links).'</span>';
    }
}
