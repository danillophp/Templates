<?php
/*
Template Name: Página Secretarias
*/

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>
<section class="section section--intro">
    <div class="container">
        <h1><?php the_title(); ?></h1>
        <p><?php esc_html_e('Conheça as secretarias municipais e os canais de atendimento.', 'prefeitura-sad-feminino'); ?></p>
    </div>
</section>
<section class="section">
    <div class="container">
        <div class="content">
            <?php while (have_posts()) : the_post(); ?>
                <?php the_content(); ?>
            <?php endwhile; ?>
        </div>
        <?php
        $secretarias = new WP_Query([
            'post_type' => 'secretarias',
            'posts_per_page' => 12,
        ]);
        if ($secretarias->have_posts()) :
        ?>
            <div class="card-grid">
                <?php while ($secretarias->have_posts()) : $secretarias->the_post(); ?>
                    <article <?php post_class('card'); ?>>
                        <div class="card__content">
                            <h2 class="card__title"><?php the_title(); ?></h2>
                            <p><?php echo esc_html(get_field('secretario_responsavel')); ?></p>
                            <div class="content">
                                <?php the_field('descricao'); ?>
                            </div>
                        </div>
                    </article>
                <?php endwhile; ?>
            </div>
            <?php wp_reset_postdata(); ?>
        <?php else : ?>
            <p><?php esc_html_e('Em breve, mais informações sobre cada secretaria.', 'prefeitura-sad-feminino'); ?></p>
        <?php endif; ?>
    </div>
</section>
<?php
get_footer();
