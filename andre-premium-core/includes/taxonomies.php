<?php if(!defined('ABSPATH')) exit;
function andre_register_taxonomies(){register_taxonomy('area_acao',['acoes_projetos','emendas'],['label'=>'Áreas','public'=>true,'hierarchical'=>true,'show_in_rest'=>true]);}
add_action('init','andre_register_taxonomies');
