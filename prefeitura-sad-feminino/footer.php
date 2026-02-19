<?php

if (!defined('ABSPATH')) {
    exit;
}
?>
</main>
<footer class="site-footer" role="contentinfo">
    <div class="site-footer__inner">
        <div class="site-footer__brand">
            <h2><?php bloginfo('name'); ?></h2>
            <p><?php esc_html_e('Prefeitura Municipal de Santo Antônio do Descoberto – GO', 'prefeitura-sad-feminino'); ?></p>
            <p><?php esc_html_e('Gestão acolhedora com transparência e cuidado.', 'prefeitura-sad-feminino'); ?></p>
        </div>
        <div class="site-footer__widgets">
            <?php if (is_active_sidebar('rodape-coluna-1')) : ?>
                <?php dynamic_sidebar('rodape-coluna-1'); ?>
            <?php endif; ?>
            <?php if (is_active_sidebar('rodape-coluna-2')) : ?>
                <?php dynamic_sidebar('rodape-coluna-2'); ?>
            <?php endif; ?>
        </div>
        <nav class="site-footer__nav" aria-label="<?php esc_attr_e('Menu Rodapé', 'prefeitura-sad-feminino'); ?>">
            <?php
            wp_nav_menu([
                'theme_location' => 'menu_rodape',
                'container' => false,
                'menu_class' => 'site-footer__list',
                'fallback_cb' => false,
            ]);
            ?>
        </nav>
        <div class="site-footer__info">
            <p><?php echo esc_html(date_i18n('Y')); ?> · <?php esc_html_e('Todos os direitos reservados', 'prefeitura-sad-feminino'); ?></p>
        </div>
    </div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
