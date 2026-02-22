function initConfirmMap(lat=-15.9,lng=-48.1){
  const map = L.map('mapConfirm').setView([lat,lng], 13);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19}).addTo(map);
  const marker = L.marker([lat,lng],{draggable:true}).addTo(map);
  marker.on('dragend',()=>{const p=marker.getLatLng();document.getElementById('latitude').value=p.lat;document.getElementById('longitude').value=p.lng;});
  return {map, marker};
}
async function buscarCep(){
  const cep=(document.getElementById('cep').value||'').replace(/\D/g,'');
  if(cep.length!==8) return alert('CEP inválido');
  const via=await fetch(`https://viacep.com.br/ws/${cep}/json/`).then(r=>r.json());
  if(via.erro) return alert('CEP não encontrado');
  if((via.localidade||'').toLowerCase()!=='santo antônio do descoberto' || (via.uf||'')!=='GO') return alert('Atendemos somente Santo Antônio do Descoberto/GO');
  document.getElementById('cidade').value=via.localidade;document.getElementById('uf').value=via.uf;
  const endereco=`${via.logradouro||''}, ${via.bairro||''}, ${via.localidade}-${via.uf}, Brasil`;
  document.getElementById('endereco').value=endereco;
  const nom=await fetch(`https://nominatim.openstreetmap.org/search?format=json&limit=1&countrycodes=br&q=${encodeURIComponent(endereco)}`).then(r=>r.json());
  if(!nom.length) return alert('Não foi possível geocodificar');
  const lat=parseFloat(nom[0].lat), lon=parseFloat(nom[0].lon);
  document.getElementById('latitude').value=lat;document.getElementById('longitude').value=lon;
  if(window.confirmMapObj){window.confirmMapObj.map.setView([lat,lon],16);window.confirmMapObj.marker.setLatLng([lat,lon]);}
}
