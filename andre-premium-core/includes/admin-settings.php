<?php if(!defined('ABSPATH')) exit;
function andre_menu(){
  add_menu_page('André Premium','André Premium','manage_options','andre-premium','andre_settings_page');
  $subs=['geral'=>'Configurações Gerais','identidade'=>'Identidade Visual','topo'=>'Configurações do Topo','redes'=>'Redes Sociais','instagram'=>'Instagram Embed','seo'=>'SEO e Compartilhamento','contatos'=>'Contatos','rodape'=>'Rodapé','midias'=>'Mídias do Site'];
  foreach($subs as $k=>$t){ add_submenu_page('andre-premium',$t,$t,'manage_options','andre-'.$k,function() use($k,$t){ andre_settings_page($k,$t);}); }
}
add_action('admin_menu','andre_menu');

function andre_media_fields(){
  return ['logo_principal','logo_rodape','favicon','hero_imagem','og_imagem_padrao','noticia_imagem_padrao','acoes_imagem_padrao','emendas_imagem_padrao','icone_mapa_politico','banners_institucionais','pdfs_opcionais','imagens_galeria','midias_extras'];
}

function andre_settings_page($slug='geral',$title='Configurações Gerais'){
  if(!current_user_can('manage_options')) return;
  $key='andre_premium_'.$slug;
  if(isset($_POST['andre_save'])){
    check_admin_referer('andre_save_'.$slug);
    $raw=wp_unslash($_POST['opt']??[]); $clean=[];
    foreach((array)$raw as $k=>$v){ $clean[sanitize_key($k)] = is_array($v) ? array_map('sanitize_text_field',$v) : sanitize_text_field($v); }
    update_option($key,$clean);
    echo '<div class="updated"><p>Salvo.</p></div>';
  }
  $o=get_option($key,[]);
  echo '<div class="wrap"><h1>'.esc_html($title).'</h1><form method="post">'; wp_nonce_field('andre_save_'.$slug);

  if('midias'===$slug){
    foreach(andre_media_fields() as $f){
      $val=isset($o[$f])?(int)$o[$f]:0;
      echo '<p><label><strong>'.esc_html($f).'</strong></label><input type="hidden" class="andre-media-id" name="opt['.esc_attr($f).']" value="'.esc_attr($val).'">';
      echo '<button class="button andre-media-pick">Selecionar mídia</button> <button class="button andre-media-clear">Limpar</button> ';
      echo '<span>ID: <span class="andre-media-preview-id">'.esc_html((string)$val).'</span></span></p>';
    }
  } else {
    $fields=['nome','subtitulo','hero_texto','hero_img','btn1_txt','btn1_url','btn2_txt','btn2_url','al_txt','al_url','consulta_txt','consulta_url','transparencia_txt','transparencia_url','alegodigital_txt','alegodigital_url','instagram','facebook','youtube','whatsapp','tiktok','telefone','email','endereco','horario','texto','creditos','logo','codigo','og_type','descricao','og_image','twitter_card'];
    foreach($fields as $f){ echo '<p><label>'.esc_html($f).'</label><input style="width:100%" name="opt['.esc_attr($f).']" value="'.esc_attr($o[$f]??'').'"></p>'; }
  }
  echo '<p><button class="button button-primary" name="andre_save" value="1">Salvar</button></p></form></div>';
}
