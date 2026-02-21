const map=L.map('map').setView([-15.95,-48.26],13);L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
(window.points||[]).forEach(p=>L.marker([p.latitude,p.longitude]).addTo(map).bindPopup(p.nome));
const fMap=L.map('form-map').setView([-15.95,-48.26],14);L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(fMap);
let marker=L.marker([-15.95,-48.26],{draggable:true}).addTo(fMap);
marker.on('dragend',()=>{const p=marker.getLatLng();latitude.value=p.lat;longitude.value=p.lng;});
const latitude=document.getElementById('latitude'),longitude=document.getElementById('longitude');
latitude.value=marker.getLatLng().lat;longitude.value=marker.getLatLng().lng;
document.getElementById('cep').addEventListener('blur',async e=>{const cep=e.target.value.replace(/\D/g,'');if(cep.length!==8)return;
const r=await fetch(`https://viacep.com.br/ws/${cep}/json/`);const d=await r.json();
if(d.uf!=='GO'||!String(d.localidade||'').includes('Santo Antônio do Descoberto')){alert('CEP fora da área');return;}
const q=encodeURIComponent(`${d.logradouro||''} ${d.bairro||''} Santo Antônio do Descoberto GO`);
const g=await fetch(`https://nominatim.openstreetmap.org/search?format=json&countrycodes=br&limit=1&q=${q}`);const a=await g.json();if(!a[0])return;
const lat=Number(a[0].lat),lng=Number(a[0].lon);marker.setLatLng([lat,lng]);fMap.setView([lat,lng],16);latitude.value=lat;longitude.value=lng;
});
