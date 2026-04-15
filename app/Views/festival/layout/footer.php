<footer class="festival-footer">
    <div class="container footer-grid">
        <div>
            <h3>Festival 14 de Maio</h3>
            <p>Evento oficial | 14, 15 e 16 de maio</p>
        </div>
        <div>
            <h4>Links úteis</h4>
            <a href="#regulamento">Regulamento</a>
            <a href="?r=festival-admin/login">Administração</a>
        </div>
        <div>
            <h4>Redes sociais</h4>
            <a href="#">Instagram</a>
            <a href="#">Facebook</a>
            <a href="#">YouTube</a>
        </div>
    </div>
    <p class="copy">© <?= date('Y') ?> Festival 14 de Maio · Plataforma oficial de votação.</p>
</footer>
<script src="public/assets/js/festival.js"></script>
<script>
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => navigator.serviceWorker.register('public/service-worker.js'));
}
</script>
</body>
</html>
