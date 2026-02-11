<?php

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>
<section class="section">
    <div class="container">
        <?php while (have_posts()) : the_post(); ?>
            <article <?php post_class('single-content'); ?>>
                <h1><?php the_title(); ?></h1>
                <p class="card__meta"><?php echo esc_html(get_the_date()); ?></p>
                <?php if (has_post_thumbnail()) : ?>
                    <div class="single-content__media">
                        <?php the_post_thumbnail('large'); ?>
                    </div>
                <?php endif; ?>
                <div class="content">
                    <?php the_content(); ?>
                </div>
            </article>
        <?php endwhile; ?>
    </div>
</section>
<?php
get_footer();
