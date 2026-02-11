<?php
/*
Template Name: Página Transparência
*/

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>
<section class="section section--intro">
    <div class="container">
        <h1><?php the_title(); ?></h1>
        <p><?php esc_html_e('Acompanhe dados públicos, relatórios e informações que fortalecem a confiança.', 'prefeitura-sad-feminino'); ?></p>
    </div>
</section>
<section class="section">
    <div class="container">
        <?php while (have_posts()) : the_post(); ?>
            <div class="content">
                <?php the_content(); ?>
            </div>
        <?php endwhile; ?>
        <div class="transparencia__cards">
            <div class="card">
                <div class="card__content">
                    <h2 class="card__title"><?php esc_html_e('Orçamento e finanças', 'prefeitura-sad-feminino'); ?></h2>
                    <p><?php esc_html_e('Transparência sobre receitas, despesas e investimentos.', 'prefeitura-sad-feminino'); ?></p>
                </div>
            </div>
            <div class="card">
                <div class="card__content">
                    <h2 class="card__title"><?php esc_html_e('Licitações e contratos', 'prefeitura-sad-feminino'); ?></h2>
                    <p><?php esc_html_e('Acesso facilitado a editais e contratos vigentes.', 'prefeitura-sad-feminino'); ?></p>
                </div>
            </div>
            <div class="card">
                <div class="card__content">
                    <h2 class="card__title"><?php esc_html_e('Relatórios sociais', 'prefeitura-sad-feminino'); ?></h2>
                    <p><?php esc_html_e('Dados sobre programas sociais e ações de cuidado.', 'prefeitura-sad-feminino'); ?></p>
                </div>
            </div>
        </div>
    </div>
</section>
<?php
get_footer();
