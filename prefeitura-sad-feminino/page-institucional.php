<?php
/*
Template Name: Página Institucional
*/

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>
<section class="section section--intro">
    <div class="container">
        <h1><?php the_title(); ?></h1>
        <p><?php esc_html_e('Conheça a prefeita, os valores da gestão e as prioridades para a cidade.', 'prefeitura-sad-feminino'); ?></p>
    </div>
</section>
<section class="section">
    <div class="container">
        <?php while (have_posts()) : the_post(); ?>
            <div class="content">
                <?php the_content(); ?>
            </div>
        <?php endwhile; ?>
    </div>
</section>
<?php
get_footer();
