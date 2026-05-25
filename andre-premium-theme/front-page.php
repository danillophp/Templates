<?php get_header(); get_template_part('template-parts/hero'); ?>
<section class="section news"><h2>Notícias</h2><div class="grid grid-2"><?php $q=new WP_Query(['post_type'=>'post','posts_per_page'=>4]); while($q->have_posts()):$q->the_post(); get_template_part('template-parts/news-card'); endwhile; wp_reset_postdata(); ?></div><a class="btn" href="<?php echo esc_url(get_permalink(get_option('page_for_posts'))); ?>">Ver mais notícias</a></section>
<section class="section"><h2>Ações e Projetos</h2><?php echo do_shortcode('[andre_acoes_projetos]'); ?></section>
<section class="section"><h2>Emendas</h2><?php echo do_shortcode('[andre_emendas_resumo]'); ?></section>
<?php get_footer();
