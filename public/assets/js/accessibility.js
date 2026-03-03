document.addEventListener('DOMContentLoaded',()=>{
  const b=document.body;
  document.getElementById('toggleContrast')?.addEventListener('click',()=>b.classList.toggle('high-contrast'));
  document.getElementById('toggleFont')?.addEventListener('click',()=>b.classList.toggle('font-large'));
});
