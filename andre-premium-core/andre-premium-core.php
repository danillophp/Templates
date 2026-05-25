<?php
/** Plugin Name: André Premium Core */
if(!defined('ABSPATH')) exit;
define('ANDRE_CORE_PATH', plugin_dir_path(__FILE__)); define('ANDRE_CORE_URL', plugin_dir_url(__FILE__));
foreach(['helpers','post-types','taxonomies','metaboxes','admin-settings','shortcodes','seo'] as $f){ require_once ANDRE_CORE_PATH.'includes/'.$f.'.php'; }
add_action('wp_enqueue_scripts',function(){ wp_enqueue_style('andre-core-front',ANDRE_CORE_URL.'assets/front/front.css',[],'1.0.0'); wp_enqueue_script('andre-core-front',ANDRE_CORE_URL.'assets/front/front.js',[],'1.0.0',true);});
add_action('admin_enqueue_scripts',function(){ wp_enqueue_style('andre-core-admin',ANDRE_CORE_URL.'assets/admin/admin.css',[],'1.0.0'); wp_enqueue_script('andre-core-admin',ANDRE_CORE_URL.'assets/admin/admin.js',['jquery'],'1.0.0',true); wp_enqueue_media();});
register_activation_hook(__FILE__, function(){ andre_register_post_types(); andre_register_taxonomies(); flush_rewrite_rules(); });
register_deactivation_hook(__FILE__, function(){ flush_rewrite_rules(); });
