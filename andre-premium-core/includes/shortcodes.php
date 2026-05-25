<?php if(!defined('ABSPATH')) exit;
function andre_list($type,$n=6){$q=new WP_Query(['post_type'=>$type,'posts_per_page'=>$n]); ob_start(); echo '<div class="andre-grid">'; while($q->have_posts()){ $q->the_post(); echo '<article class="andre-card"><a href="'.esc_url(get_permalink()).'">'.get_the_post_thumbnail(get_the_ID(),'medium',['loading'=>'lazy']).'<h3>'.esc_html(get_the_title()).'</h3></a><p>'.esc_html(wp_trim_words(get_the_excerpt(),16)).'</p></article>'; } wp_reset_postdata(); echo '</div>'; return ob_get_clean(); }
add_shortcode('andre_acoes_projetos',fn()=>andre_list('acoes_projetos',6));
add_shortcode('andre_emendas',fn()=>andre_list('emendas',12));
add_shortcode('andre_emendas_resumo',fn()=>andre_list('emendas',4));
add_shortcode('andre_galerias',fn()=>andre_list('galerias',12));
add_shortcode('andre_galeria',function($a){$id=(int)($a['id']??0); $ids=explode(',',(string)get_post_meta($id,'galeria_ids',true)); ob_start(); echo '<div class="andre-grid">'; foreach($ids as $i){ $src=wp_get_attachment_image_url((int)$i,'large'); if($src) echo '<a href="'.esc_url($src).'" download><img loading="lazy" src="'.esc_url($src).'" alt=""></a>'; } echo '</div>'; return ob_get_clean();});
add_shortcode('andre_instagram',fn()=> (andre_opt('andre_premium_instagram')['codigo']??''));
add_shortcode('andre_redes_sociais',function(){ $r=andre_opt('andre_premium_redes'); $o=''; foreach(['instagram','facebook','youtube','whatsapp','tiktok'] as $s){ if(!empty($r[$s])) $o.='<a href="'.esc_url($r[$s]).'">'.esc_html($s).'</a> '; } return $o;});
add_shortcode('andre_contato',function(){ $c=andre_opt('andre_premium_contatos'); return '<p>'.esc_html($c['telefone']??'').'</p><p>'.esc_html($c['email']??'').'</p>';});
