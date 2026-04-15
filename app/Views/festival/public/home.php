<main id="inicio">
    <section class="hero">
        <div class="hero-overlay"></div>
        <div class="container hero-content">
            <p class="kicker">Festival 14 de Maio apresenta</p>
            <h1>GAROTA SADE 2026</h1>
            <p class="subtitle">Vote na sua candidata favorita com segurança e acompanhe o ranking em tempo quase real.</p>
            <div class="hero-cta">
                <a class="btn btn-primary" href="#candidatas">Votar agora</a>
                <a class="btn btn-secondary" href="public/assets/img/regulamento-garota-sade-2026.pdf" target="_blank" rel="noopener">Baixar regulamento</a>
            </div>
        </div>
    </section>

    <section class="section" id="candidatas">
        <div class="container">
            <h2>Candidatas</h2>
            <p class="section-lead">Cada voto passa por validações de segurança e antifraude para auditoria.</p>

            <form id="voteForm" class="vote-form" method="post" action="?r=api/festival/vote">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
                <input type="hidden" name="interaction_seconds" id="interactionSeconds" value="0">
                <input type="text" name="website" id="website" class="honeypot" tabindex="-1" autocomplete="off" aria-hidden="true">
                <input type="hidden" name="origin" value="landing_publica">

                <div class="candidate-grid">
                    <?php foreach ($candidates as $candidate): ?>
                        <article class="candidate-card">
                            <img src="public/assets/img/<?= htmlspecialchars($candidate['foto']) ?>" alt="<?= htmlspecialchars($candidate['nome']) ?>">
                            <div class="candidate-body">
                                <h3><?= htmlspecialchars($candidate['nome']) ?></h3>
                                <p>Nº <?= (int) $candidate['numero'] ?> · <?= htmlspecialchars($candidate['cidade']) ?></p>
                                <p>Votos oficiais: <strong data-votes-id="<?= (int) $candidate['id'] ?>"><?= (int) $candidate['votos_total'] ?></strong></p>
                                <button class="btn btn-vote" type="submit" name="candidate_id" value="<?= (int) $candidate['id'] ?>">Votar</button>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </form>
            <div id="voteMessage" class="feedback" role="status" aria-live="polite"></div>
        </div>
    </section>

    <section class="section ranking" id="ranking">
        <div class="container">
            <div class="ranking-head">
                <h2>Ranking Oficial</h2>
                <div class="ranking-filters">
                    <button type="button" data-ranking-limit="3">Top 3</button>
                    <button type="button" data-ranking-limit="5">Top 5</button>
                    <button type="button" data-ranking-limit="10" class="active">Top 10</button>
                </div>
            </div>
            <div id="rankingList" class="ranking-list">
                <?php foreach ($ranking as $item): ?>
                    <div class="ranking-item <?= ((int) $item['posicao'] <= 3) ? 'top3' : '' ?>">
                        <span class="position">#<?= (int) $item['posicao'] ?></span>
                        <span class="name"><?= htmlspecialchars($item['nome']) ?></span>
                        <span class="votes"><?= (int) $item['votos_total'] ?> votos</span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="section" id="atracoes">
        <div class="container">
            <h2>Atrações Musicais</h2>
            <div class="shows-grid">
                <article class="show-card">
                    <img src="public/assets/img/artista-israel-e-rodolfo.jpg" alt="Israel e Rodolfo">
                    <h3>Israel e Rodolfo</h3>
                    <p>14 de maio · 22h30</p>
                </article>
                <article class="show-card">
                    <img src="public/assets/img/artista-ze-ricardo-e-thiago.jpg" alt="Zé Ricardo e Thiago">
                    <h3>Zé Ricardo e Thiago</h3>
                    <p>15 de maio · 23h00</p>
                </article>
                <article class="show-card">
                    <img src="public/assets/img/artista-tribo-da-periferia.jpg" alt="Tribo da Periferia">
                    <h3>Tribo da Periferia</h3>
                    <p>16 de maio · 00h00</p>
                </article>
            </div>
        </div>
    </section>

    <section class="section" id="cronograma">
        <div class="container">
            <h2>Cronograma do Evento</h2>
            <ul class="timeline">
                <li><strong>14/05:</strong> Abertura oficial + show Israel e Rodolfo.</li>
                <li><strong>15/05:</strong> Etapa principal do concurso + show Zé Ricardo e Thiago.</li>
                <li><strong>16/05:</strong> Final Garota SADE + show Tribo da Periferia.</li>
            </ul>
        </div>
    </section>

    <section class="section" id="regulamento">
        <div class="container">
            <h2>Regulamento</h2>
            <p>Leia atentamente as regras de participação, auditoria e política de segurança da votação.</p>
            <a class="btn btn-secondary" href="public/assets/img/regulamento-garota-sade-2026.pdf" target="_blank" rel="noopener">Baixar PDF</a>
        </div>
    </section>
</main>
