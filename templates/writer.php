<?php
defined('ABSPATH') || exit;
$block=function_exists('wp_is_block_theme') && wp_is_block_theme();
if($block){?><!doctype html><html <?php language_attributes(); ?>><head><meta charset="<?php bloginfo('charset'); ?>"><meta name="viewport" content="width=device-width,initial-scale=1"><?php wp_head(); ?></head><body <?php body_class(); ?>><?php wp_body_open(); echo do_blocks('<!-- wp:template-part {"slug":"header","tagName":"header"} /-->'); }
else {get_header('shop');}
$writer=get_queried_object();
?>
<main id="main" class="afs-writer-profile">
    <nav aria-label="Navigasi"><a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>">Semua buku</a> / Penulis</nav>
    <header class="afs-writer-hero">
        <?php $photo=(int)get_term_meta($writer->term_id,'afs_writer_photo',true); if($photo)echo wp_get_attachment_image($photo,'medium',false,array('class'=>'afs-writer-portrait','alt'=>$writer->name,'loading'=>'eager')); ?>
        <div><p class="afs-writer-kicker">KENALI PENULIS</p><h1><?php echo esc_html($writer->name); ?></h1><p><?php echo esc_html(get_term_meta($writer->term_id,'afs_writer_short',true)); ?></p></div>
    </header>
    <?php $bio=get_term_meta($writer->term_id,'afs_writer_bio',true);if($bio): ?><section class="afs-writer-bio"><h2>Tentang penulis</h2><?php echo wp_kses_post(wpautop($bio)); ?></section><?php endif; ?>
    <section class="afs-writer-books"><h2>Buku oleh <?php echo esc_html($writer->name); ?></h2>
    <?php if(have_posts()): woocommerce_product_loop_start();while(have_posts()):the_post();wc_get_template_part('content','product');endwhile;woocommerce_product_loop_end();woocommerce_pagination();else: ?><p>Buku penulis ini akan disenaraikan di sini apabila tersedia.</p><?php endif; ?>
    </section>
</main>
<?php if($block){echo do_blocks('<!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->');wp_footer();echo '</body></html>';}else{get_footer('shop');} ?>
