<?php

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>
<section class="section section--intro">
    <div class="container">
        <h1><?php the_archive_title(); ?></h1>
        <?php the_archive_description('<p>', '</p>'); ?>
    </div>
</section>
<section class="section">
    <div class="container">
        <?php if (have_posts()) : ?>
            <div class="card-grid">
                <?php while (have_posts()) : the_post(); ?>
                    <article <?php post_class('card'); ?>>
                        <div class="card__content">
                            <h2 class="card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                            <p class="card__meta"><?php echo esc_html(get_the_date()); ?></p>
                            <p><?php echo esc_html(wp_trim_words(get_the_excerpt(), 20, '...')); ?></p>
                        </div>
                    </article>
                <?php endwhile; ?>
            </div>
            <div class="pagination">
                <?php the_posts_pagination(); ?>
            </div>
        <?php else : ?>
            <p><?php esc_html_e('Nada por aqui ainda, mas seguimos trabalhando para você.', 'prefeitura-sad-feminino'); ?></p>
        <?php endif; ?>
    </div>
</section>
<?php
get_footer();
