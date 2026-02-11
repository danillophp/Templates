<?php

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>
<section class="section">
    <div class="container">
        <?php while (have_posts()) : the_post(); ?>
            <article <?php post_class('page-content'); ?>>
                <h1><?php the_title(); ?></h1>
                <div class="content">
                    <?php the_content(); ?>
                </div>
            </article>
        <?php endwhile; ?>
    </div>
</section>
<?php
get_footer();
