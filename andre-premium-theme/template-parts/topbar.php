<?php $topo=get_option('andre_premium_topo',[]); $social=get_option('andre_premium_redes',[]); ?>
<div class="topbar"><div class="container topbar-inner">
<?php foreach(['al','consulta','transparencia','alegodigital'] as $k): if(!empty($topo[$k.'_txt'])&&!empty($topo[$k.'_url'])): ?><a href="<?php echo esc_url($topo[$k.'_url']); ?>" <?php echo !empty($topo[$k.'_blank'])?'target="_blank" rel="noopener"':''; ?>><?php echo esc_html($topo[$k.'_txt']); ?></a><?php endif; endforeach; ?>
<?php get_search_form(); ?><div class="acc"><button data-font="minus">A-</button><button data-font="plus">A+</button><button data-contrast="toggle">Contraste</button><a href="https://www.gov.br/governodigital/pt-br/vlibras" target="_blank" rel="noopener">VLibras</a><button data-font="reset">Reset</button></div>
<div class="social"><?php foreach(['instagram','facebook','youtube'] as $s){ if(!empty($social[$s])) echo '<a href="'.esc_url($social[$s]).'" target="_blank" rel="noopener">'.esc_html(ucfirst($s)).'</a>'; } ?></div>
</div></div>
