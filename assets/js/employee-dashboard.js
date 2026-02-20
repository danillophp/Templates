async function action(url, id) {
  const fd = new FormData();
  fd.append('_csrf', window.CSRF);
  fd.append('request_id', id);
  const res = await fetch(url, { method: 'POST', body: fd });
  const json = await res.json();
  showToast(json.ok ? 'Ação concluída.' : (json.message || 'Erro'), json.ok ? 'success' : 'danger');
  if (json.ok) setTimeout(() => location.reload(), 500);
}

document.querySelectorAll('.btnStart').forEach((btn) => btn.addEventListener('click', () => action('?r=api/employee/start', btn.dataset.id)));
document.querySelectorAll('.btnFinish').forEach((btn) => btn.addEventListener('click', () => action('?r=api/employee/finish', btn.dataset.id)));
