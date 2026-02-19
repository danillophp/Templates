<?php

if (!defined('ABSPATH')) {
    exit;
}
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#conteudo-principal"><?php esc_html_e('Pular para o conteúdo', 'prefeitura-sad-feminino'); ?></a>
<header class="site-header" role="banner">
    <div class="site-header__inner">
        <div class="site-branding">
            <a class="site-logo" href="<?php echo esc_url(home_url('/')); ?>">
                <?php bloginfo('name'); ?>
            </a>
            <p class="site-description"><?php bloginfo('description'); ?></p>
        </div>
        <nav class="site-nav" role="navigation" aria-label="<?php esc_attr_e('Menu Principal', 'prefeitura-sad-feminino'); ?>">
            <?php
            wp_nav_menu([
                'theme_location' => 'menu_principal',
                'container' => false,
                'menu_class' => 'site-nav__list',
                'fallback_cb' => false,
            ]);
            ?>
        </nav>
    </div>
</header>
<main id="conteudo-principal" class="site-main" role="main">
