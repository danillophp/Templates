<?php
if (!defined('ABSPATH')) { exit; }
function andre_theme_setup(){
  add_theme_support('title-tag'); add_theme_support('post-thumbnails'); add_theme_support('custom-logo'); add_theme_support('html5', ['search-form','gallery','caption']);
  register_nav_menus(['primary'=>'Menu Principal','footer_institucional'=>'Rodapé Institucional','footer_uteis'=>'Links Úteis']);
}
add_action('after_setup_theme','andre_theme_setup');
function andre_theme_assets(){
  wp_enqueue_style('andre-main', get_template_directory_uri().'/assets/css/main.css', [], '1.0.1');
  wp_enqueue_script('andre-main', get_template_directory_uri().'/assets/js/main.js', [], '1.0.1', true);
}
add_action('wp_enqueue_scripts','andre_theme_assets');

function andre_media_option($key){ $m=get_option('andre_premium_midias',[]); return isset($m[$key])?(int)$m[$key]:0; }
function andre_head_favicon(){ $fid=andre_media_option('favicon'); if($fid){ $url=wp_get_attachment_url($fid); if($url) echo '<link rel="icon" href="'.esc_url($url).'">'; } }
add_action('wp_head','andre_head_favicon',1);
