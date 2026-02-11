<?php

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>
<section class="section section--intro">
    <div class="container">
        <h1><?php esc_html_e('Notícias e Informações', 'prefeitura-sad-feminino'); ?></h1>
        <p><?php esc_html_e('Acompanhe ações, programas e serviços voltados para as cidadãs e suas famílias.', 'prefeitura-sad-feminino'); ?></p>
    </div>
</section>
<section class="section">
    <div class="container">
        <?php if (have_posts()) : ?>
            <div class="card-grid">
                <?php while (have_posts()) : the_post(); ?>
                    <article <?php post_class('card'); ?>>
                        <?php if (has_post_thumbnail()) : ?>
                            <div class="card__media">
                                <a href="<?php the_permalink(); ?>">
                                    <?php the_post_thumbnail('large'); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                        <div class="card__content">
                            <h2 class="card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                            <p class="card__meta"><?php echo esc_html(get_the_date()); ?></p>
                            <p><?php echo esc_html(wp_trim_words(get_the_excerpt(), 24, '...')); ?></p>
                            <a class="button" href="<?php the_permalink(); ?>"><?php esc_html_e('Ler notícia', 'prefeitura-sad-feminino'); ?></a>
                        </div>
                    </article>
                <?php endwhile; ?>
            </div>
            <div class="pagination">
                <?php the_posts_pagination(); ?>
            </div>
        <?php else : ?>
            <p><?php esc_html_e('Nenhuma publicação encontrada no momento.', 'prefeitura-sad-feminino'); ?></p>
        <?php endif; ?>
    </div>
</section>
<?php
get_footer();
