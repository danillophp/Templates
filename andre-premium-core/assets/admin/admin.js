jQuery(function($){
  $(document).on('click','.andre-media-pick',function(e){
    e.preventDefault();
    const $wrap=$(this).closest('p');
    const frame=wp.media({title:'Selecionar mídia',multiple:false});
    frame.on('select',function(){
      const media=frame.state().get('selection').first().toJSON();
      $wrap.find('.andre-media-id').val(media.id);
      $wrap.find('.andre-media-preview-id').text(media.id);
    });
    frame.open();
  });
  $(document).on('click','.andre-media-clear',function(e){
    e.preventDefault(); const $wrap=$(this).closest('p');
    $wrap.find('.andre-media-id').val(''); $wrap.find('.andre-media-preview-id').text('');
  });
  $(document).on('click','#andre_pick_gallery',function(e){e.preventDefault();const frame=wp.media({title:'Galeria',multiple:true});frame.on('select',function(){const ids=frame.state().get('selection').map(a=>a.id).join(',');$('#galeria_ids').val(ids);});frame.open();});
});
