<?php if(!defined('ABSPATH')) exit;
add_action('wp_head',function(){
  $s=andre_opt('andre_premium_seo'); $m=andre_opt('andre_premium_midias');
  $og_img = !empty($s['og_image']) ? $s['og_image'] : (!empty($m['og_imagem_padrao']) ? wp_get_attachment_url((int)$m['og_imagem_padrao']) : '');
  echo '<meta property="og:type" content="'.esc_attr($s['og_type']??'website').'">';
  if(!empty($s['descricao'])) echo '<meta name="description" content="'.esc_attr($s['descricao']).'">';
  if(!empty($og_img)) echo '<meta property="og:image" content="'.esc_url($og_img).'">';
  echo '<meta name="twitter:card" content="'.esc_attr($s['twitter_card']??'summary_large_image').'">';
});
