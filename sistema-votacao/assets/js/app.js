(function () {
  const api = (path) => `${window.APP.basePath}${path}`;
  const state = { token: null, candidato: null, meta: { fingerprint: '', localizacao: '' } };

  const qs = (s) => document.querySelector(s);
  const post = async (url, data) => {
    const form = new URLSearchParams(data);
    const res = await fetch(api(url), { method: 'POST', headers: {'Content-Type':'application/x-www-form-urlencoded'}, body: form });
    return res.json();
  };

  function showStep(id) {
    ['#etapa-token','#etapa-cadastro','#etapa-voto','#etapa-sucesso'].forEach((x) => qs(x).classList.add('hidden'));
    qs(id).classList.remove('hidden');
  }

  async function initMeta() {
    const raw = [navigator.userAgent, navigator.language, Intl.DateTimeFormat().resolvedOptions().timeZone, navigator.platform, `${screen.width}x${screen.height}`].join('|');
    state.meta.fingerprint = (await sha256(raw)).slice(0, 64);
    if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition((pos) => {
        state.meta.localizacao = `${pos.coords.latitude.toFixed(5)},${pos.coords.longitude.toFixed(5)}`;
      });
    }
  }

  async function sha256(str) {
    const buf = await crypto.subtle.digest('SHA-256', new TextEncoder().encode(str));
    return Array.from(new Uint8Array(buf)).map((b) => b.toString(16).padStart(2, '0')).join('');
  }

  if (window.APP.voted || localStorage.getItem('voto_finalizado') === '1') {
    showStep('#etapa-sucesso');
  }

  qs('#btn-token').addEventListener('click', async () => {
    const token = qs('#token').value.toUpperCase().replace(/[^A-Z0-9]/g, '');
    qs('#token').value = token;
    const r = await post('/ajax/validar_token.php', { token, csrf_token: window.APP.csrf, fingerprint: state.meta.fingerprint });
    if (!r.ok) return Swal.fire('Aviso', r.msg, 'warning');
    state.token = token;
    showStep('#etapa-cadastro');
  });

  qs('#btn-cadastro').addEventListener('click', async () => {
    const nome = qs('#nome').value.trim();
    const whatsapp = qs('#whatsapp').value.trim();
    const r = await post('/ajax/salvar_cadastro.php', { nome, whatsapp, fingerprint: state.meta.fingerprint, csrf_token: window.APP.csrf });
    if (!r.ok) return Swal.fire('Aviso', r.msg, 'warning');
    showStep('#etapa-voto');
  });

  qs('#grid-candidatos').addEventListener('click', async (ev) => {
    const btn = ev.target.closest('.candidate-card');
    if (!btn) return;
    const candidatoId = btn.getAttribute('data-id');
    const nome = btn.getAttribute('data-nome');

    const ok = await Swal.fire({ title: 'Confirmar voto', text: `Confirma seu voto em ${nome}?`, icon: 'question', showCancelButton: true });
    if (!ok.isConfirmed) return;

    const r = await post('/ajax/registrar_voto.php', {
      candidato_id: candidatoId,
      localizacao: state.meta.localizacao,
      fingerprint: state.meta.fingerprint,
      csrf_token: window.APP.csrf
    });
    if (!r.ok) return Swal.fire('Erro', r.msg, 'error');

    localStorage.setItem('voto_finalizado', '1');
    showStep('#etapa-sucesso');
    Swal.fire('Sucesso', r.msg, 'success');
  });

  initMeta();
})();
