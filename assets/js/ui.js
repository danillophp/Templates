window.showToast = (message, type = 'success') => {
  const wrap = document.getElementById('toastWrap');
  if (!wrap) return;

  const el = document.createElement('div');
  el.className = `toast align-items-center text-bg-${type} border-0`;
  el.role = 'alert';
  el.innerHTML = `<div class="d-flex"><div class="toast-body">${message}</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>`;
  wrap.appendChild(el);
  const toast = new bootstrap.Toast(el, { delay: 3000 });
  toast.show();
  el.addEventListener('hidden.bs.toast', () => el.remove());
};
