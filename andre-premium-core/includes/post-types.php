<?php if(!defined('ABSPATH')) exit;
function andre_register_post_types(){
 register_post_type('acoes_projetos',['label'=>'Ações e Projetos','public'=>true,'supports'=>['title','editor','thumbnail','excerpt'],'has_archive'=>true,'rewrite'=>['slug'=>'acoes-projetos'],'show_in_rest'=>true]);
 register_post_type('emendas',['label'=>'Emendas','public'=>true,'supports'=>['title','editor','thumbnail','excerpt'],'has_archive'=>true,'show_in_rest'=>true]);
 register_post_type('galerias',['label'=>'Galerias','public'=>true,'supports'=>['title','editor','thumbnail'],'has_archive'=>true,'show_in_rest'=>true]);
}
add_action('init','andre_register_post_types');
