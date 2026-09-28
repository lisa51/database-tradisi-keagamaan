<?php
/**
 * Template untuk halaman Single Tradisi
 * Lokasi: wp-content/plugins/tradisi-keagamaan/templates/single-tradisi.php
 */

if (!defined('ABSPATH')) exit;

get_header();

while (have_posts()) :
    the_post();

    $post_id          = get_the_ID();
    $deskripsi_singkat = get_field('deskripsi_singkat');
    $asal_daerah       = get_field('asal_daerah');
    $sumber_referensi  = get_field('sumber_referensi');
    $galeri_foto       = get_field('galeri_foto');
    $video_terkait     = get_field('video_terkait');

    $wilayah_terms  = get_the_terms($post_id, 'wilayah');
    $kategori_terms = get_the_terms($post_id, 'kategori-tradisi');
    $tags           = get_the_tags($post_id);
?>

<div class="container">
    <article class="single-tradisi">

        <div class="single-tradisi__meta-top">
            <?php if ($kategori_terms && !is_wp_error($kategori_terms)) : ?>
                <span class="tag-pill" style="background:#4A2511;color:#fff;">
                    <?php echo esc_html($kategori_terms[0]->name); ?>
                </span>
            <?php endif; ?>
            <span style="color:#9C8C78;font-size:13px;">
                <?php echo esc_html(get_the_date()); ?>
            </span>
        </div>

        <h1 class="single-tradisi__title"><?php the_title(); ?></h1>

        <div class="single-tradisi__meta">
            <span><strong>Penulis:</strong> <?php the_author(); ?></span>
            <?php if ($asal_daerah) : ?>
                <span>&bull; <strong>Lokasi:</strong> <?php echo esc_html($asal_daerah); ?></span>
            <?php endif; ?>
            <?php if ($wilayah_terms && !is_wp_error($wilayah_terms)) : ?>
                <span>&bull; <strong>Wilayah:</strong> <?php echo esc_html($wilayah_terms[0]->name); ?></span>
            <?php endif; ?>
            <span>&bull; <strong>Pembaca:</strong> <?php echo number_format_i18n(tk_get_view_count($post_id)); ?></span>
        </div>

        <?php if (has_post_thumbnail()) : ?>
            <div class="single-tradisi__featured-img">
                <?php the_post_thumbnail('large', ['style' => 'width:100%;height:auto;']); ?>
            </div>
        <?php endif; ?>

        <?php if ($deskripsi_singkat) : ?>
            <div class="single-tradisi__abstract">
                <span class="single-tradisi__abstract-label">Abstrak / Ringkasan Eksekutif</span>
                <?php echo esc_html($deskripsi_singkat); ?>
            </div>
        <?php endif; ?>

        <div class="single-tradisi__content">
            <?php the_content(); ?>
        </div>

        <?php if ($video_terkait) : ?>
            <div class="single-tradisi__video" style="margin:24px 0;">
                <?php echo wp_oembed_get(esc_url($video_terkait)); ?>
            </div>
        <?php endif; ?>

        <?php 
        // Galeri foto: aman untuk format ID, array, atau daftar ID dipisah koma
        if ( $galeri_foto ) :
            // Kalau isinya satu foto (field Image), jadikan daftar berisi satu foto
            if ( is_array( $galeri_foto ) && ( isset( $galeri_foto['ID'] ) || isset( $galeri_foto['url'] ) ) ) {
                $galeri_foto = array( $galeri_foto );
            }
            if ( ! is_array( $galeri_foto ) ) {
                $galeri_foto = array_filter( array_map( 'absint', explode( ',', (string) $galeri_foto ) ) );
            }
        ?>
        <div class="tk-galeri">
            <?php foreach ( $galeri_foto as $foto ) :
                if ( is_array( $foto ) ) {
                    $foto_id = isset( $foto['ID'] ) ? absint( $foto['ID'] ) : ( isset( $foto['id'] ) ? absint( $foto['id'] ) : 0 );
                } else {
                    $foto_id = absint( $foto );
                }
                if ( ! $foto_id ) {
                    continue;
                }
            ?>
            <a href="<?php echo esc_url( wp_get_attachment_image_url( $foto_id, 'large' ) ); ?>" target="_blank" rel="noopener">
                <?php echo wp_get_attachment_image( $foto_id, 'medium', false, array( 'loading' => 'lazy' ) ); ?>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ($tags) : ?>
            <div class="single-tradisi__tags">
                <strong style="font-size:11px;letter-spacing:.04em;text-transform:uppercase;color:#9C8C78;">
                    Kata Kunci:
                </strong>
                <?php foreach ($tags as $tag) : ?>
                    <span class="tag-pill">#<?php echo esc_html($tag->name); ?></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($sumber_referensi) : ?>
            <div style="margin-top:20px;font-size:13px;color:#6B5D4F;">
                <strong>Sumber Referensi:</strong>
                <a href="<?php echo esc_url($sumber_referensi); ?>" target="_blank" rel="noopener">
                    <?php echo esc_html($sumber_referensi); ?>
                </a>
            </div>
        <?php endif; ?>

    </article>
</div>

<?php
endwhile;

get_footer();