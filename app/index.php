<?php

declare(strict_types=1);

$services = [
    [
        'name' => 'Cata Treco',
        'slug' => 'catatreco',
        'description' => 'Solicite a coleta de resíduos volumosos com acompanhamento digital e fluxo organizado para atendimento municipal.',
        'url' => '/catatreco/public/index.php',
        'icon' => 'truck',
        'badge' => 'Serviço ao cidadão',
    ],
    [
        'name' => 'Solicitação de Lâmpadas',
        'slug' => 'iluminacao',
        'description' => 'Registre pontos sem iluminação pública, informe localização e acompanhe a manutenção do atendimento.',
        'url' => '/iluminacao/',
        'icon' => 'bulb',
        'badge' => 'Infraestrutura urbana',
    ],
    [
        'name' => 'Chamado de TI',
        'slug' => 'chamado',
        'description' => 'Abra chamados técnicos, acompanhe tratativas e organize o suporte interno com mais agilidade.',
        'url' => '/chamado/',
        'icon' => 'headset',
        'badge' => 'Atendimento interno',
    ],
    [
        'name' => 'Emendas',
        'slug' => 'emendas',
        'description' => 'Consulte informações estruturadas sobre emendas, acompanhamento administrativo e transparência de execução.',
        'url' => '/emendas/',
        'icon' => 'chart',
        'badge' => 'Gestão e transparência',
    ],
    [
        'name' => 'EducaSADE',
        'slug' => 'educasad',
        'description' => 'Acesse recursos educacionais, ambientes administrativos e fluxos digitais da área de educação municipal.',
        'url' => '/educasad/',
        'icon' => 'book',
        'badge' => 'Educação digital',
    ],
    [
        'name' => 'Suporte',
        'slug' => 'suporte',
        'description' => 'Central de apoio para orientações operacionais, suporte técnico e encaminhamento institucional.',
        'url' => '/suporte/',
        'icon' => 'shield',
        'badge' => 'Suporte institucional',
    ],
];

$quickActions = [
    [
        'title' => 'Consultar protocolo',
        'description' => 'Acesse rapidamente um atendimento em andamento ou protocolo já registrado.',
        'url' => '#servicos',
        'icon' => 'search',
    ],
    [
        'title' => 'Abrir solicitação',
        'description' => 'Encontre o serviço correto para iniciar um novo atendimento digital.',
        'url' => '#servicos',
        'icon' => 'plus',
    ],
    [
        'title' => 'Suporte técnico',
        'description' => 'Direcione usuários para o canal apropriado de apoio institucional e tecnológico.',
        'url' => '/suporte/',
        'icon' => 'support',
    ],
    [
        'title' => 'Acompanhar sistema',
        'description' => 'Consulte avisos operacionais, disponibilidade e orientações dos ambientes digitais.',
        'url' => '#comunicados',
        'icon' => 'pulse',
    ],
];

$announcements = [
    [
        'title' => 'Disponibilidade dos sistemas',
        'description' => 'Os serviços digitais podem receber atualizações programadas. Consulte este hub antes de abrir novo atendimento.',
        'tag' => 'Monitoramento',
    ],
    [
        'title' => 'Orientação de atendimento',
        'description' => 'Antes de registrar uma demanda, selecione o módulo correspondente para garantir triagem correta e resposta mais rápida.',
        'tag' => 'Atendimento',
    ],
    [
        'title' => 'Mobilidade e futuro app oficial',
        'description' => 'Esta plataforma já está preparada para instalação como PWA e servirá de base para os aplicativos oficiais Android e iOS.',
        'tag' => 'Inovação pública',
    ],
];

$stats = [
    ['value' => count($services), 'label' => 'Serviços integrados'],
    ['value' => '24/7', 'label' => 'Disponibilidade web'],
    ['value' => '1 hub', 'label' => 'Acesso centralizado'],
];

$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/app/index.php')), '/');
$basePath = $basePath === '' ? '' : $basePath;

$asset = static function (string $path) use ($basePath): string {
    return ($basePath ?: '') . '/' . ltrim($path, '/');
};

$absoluteUrl = static function (string $path): string {
    return preg_match('#^https?://#i', $path) ? $path : $path;
};

function iconSvg(string $icon): string
{
    $icons = [
        'truck' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6.75A2.75 2.75 0 0 1 5.75 4h8.5A2.75 2.75 0 0 1 17 6.75V8h1.59c.77 0 1.5.35 1.98.95l1.63 2.03c.39.49.6 1.09.6 1.71v2.56a1.75 1.75 0 0 1-1.75 1.75h-.69a2.75 2.75 0 0 1-5.36 0H8.36a2.75 2.75 0 0 1-5.36 0H2.75A1.75 1.75 0 0 1 1 15.25V13.5A1.5 1.5 0 0 1 2.5 12H3V6.75Zm2.5-.25v5.5h8V6.5h-8Zm12 5V13H21v-.31l-1.5-1.87H17.5ZM5.68 16a1.25 1.25 0 1 0 0 2.5 1.25 1.25 0 0 0 0-2.5Zm12 0a1.25 1.25 0 1 0 0 2.5 1.25 1.25 0 0 0 0-2.5Z"/></svg>',
        'bulb' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2.5a7 7 0 0 1 4.88 12.02c-.74.73-1.2 1.37-1.47 1.98H8.59c-.27-.61-.73-1.25-1.47-1.98A7 7 0 0 1 12 2.5Zm-2.45 15.5h4.9v.8a2.45 2.45 0 0 1-4.9 0V18Zm-.05 3h5a1 1 0 1 1-5 0Z"/></svg>',
        'headset' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3a8 8 0 0 1 8 8v5.25A2.75 2.75 0 0 1 17.25 19H16a2 2 0 0 1-2-2v-2.5A2.5 2.5 0 0 1 16.5 12H18v-1a6 6 0 1 0-12 0v1h1.5A2.5 2.5 0 0 1 10 14.5V17a2 2 0 0 1-2 2H6.75A2.75 2.75 0 0 1 4 16.25V11a8 8 0 0 1 8-8Zm5.25 10.5a1 1 0 0 0-1 1V17a.5.5 0 0 0 .5.5h.5a1.25 1.25 0 0 0 1.25-1.25V13.5h-1.25Zm-10.5 0H5.5v2.75A1.25 1.25 0 0 0 6.75 17.5h.5a.5.5 0 0 0 .5-.5v-2.5a1 1 0 0 0-1-1Z"/></svg>',
        'chart' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 19.25A1.25 1.25 0 0 1 5.25 18H19v1.25A1.75 1.75 0 0 1 17.25 21H5.75A1.75 1.75 0 0 1 4 19.25ZM7 10.75A1.75 1.75 0 0 1 8.75 9h.5A1.75 1.75 0 0 1 11 10.75V17H7v-6.25Zm6-4A1.75 1.75 0 0 1 14.75 5h.5A1.75 1.75 0 0 1 17 6.75V17h-4V6.75Zm-9 8A1.75 1.75 0 0 1 5.75 13h.5A1.75 1.75 0 0 1 8 14.75V17H4v-2.25Zm12 0A1.75 1.75 0 0 1 17.75 13h.5A1.75 1.75 0 0 1 20 14.75V17h-4v-2.25Z"/></svg>',
        'book' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5.5 4A2.5 2.5 0 0 0 3 6.5v10A2.5 2.5 0 0 0 5.5 19H19a2 2 0 0 0 2-2V6.75A2.75 2.75 0 0 0 18.25 4H5.5Zm0 1.5h12.75c.69 0 1.25.56 1.25 1.25v9.75a.5.5 0 0 1-.5.5H6.25A2.72 2.72 0 0 0 4.5 17.64V6.5c0-.55.45-1 1-1Zm2 2h8a.75.75 0 0 1 0 1.5h-8a.75.75 0 0 1 0-1.5Zm0 3h8a.75.75 0 0 1 0 1.5h-8a.75.75 0 0 1 0-1.5Z"/></svg>',
        'shield' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2.25c2.27 1.54 4.86 2.43 7.59 2.62a1 1 0 0 1 .91 1v4.51c0 4.76-2.87 8.97-7.28 10.68a1.75 1.75 0 0 1-1.24 0C7.57 19.35 4.7 15.14 4.7 10.38V5.87a1 1 0 0 1 .91-1A14.47 14.47 0 0 0 12 2.25Zm3.28 6.97-4.03 4.04-1.53-1.54a.75.75 0 0 0-1.06 1.06l2.06 2.06a.75.75 0 0 0 1.06 0l4.56-4.56a.75.75 0 0 0-1.06-1.06Z"/></svg>',
        'search' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10.5 4a6.5 6.5 0 1 1 0 13 6.5 6.5 0 0 1 0-13Zm0 1.5a5 5 0 1 0 0 10 5 5 0 0 0 0-10Zm7.78 10.72 2.5 2.5a.75.75 0 0 1-1.06 1.06l-2.5-2.5a.75.75 0 1 1 1.06-1.06Z"/></svg>',
        'plus' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 4.25a.75.75 0 0 1 .75.75v6.25H19a.75.75 0 0 1 0 1.5h-6.25V19a.75.75 0 0 1-1.5 0v-6.25H5a.75.75 0 0 1 0-1.5h6.25V5a.75.75 0 0 1 .75-.75Z"/></svg>',
        'support' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3.25A7.75 7.75 0 0 0 4.25 11v1.48A2.25 2.25 0 0 0 2.5 14.67v.66a2.42 2.42 0 0 0 2.42 2.42h1.33A1.75 1.75 0 0 0 8 16v-3.67a1.75 1.75 0 0 0-1.75-1.75H5.78a6.25 6.25 0 0 1 12.44 0h-.47A1.75 1.75 0 0 0 16 12.33V16a1.75 1.75 0 0 0 1.75 1.75h.63a2.92 2.92 0 0 1-2.88 2.25h-2.13a.75.75 0 0 0 0 1.5h2.13a4.42 4.42 0 0 0 4.39-4.1A2.38 2.38 0 0 0 21.5 15.33v-.66a2.25 2.25 0 0 0-1.75-2.19V11A7.75 7.75 0 0 0 12 3.25Z"/></svg>',
        'pulse' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 12.75A.75.75 0 0 1 3.75 12h3.06l1.56-3.11a.75.75 0 0 1 1.36.08l2.27 6.07 1.53-3.06a.75.75 0 0 1 .67-.41h5.03a.75.75 0 0 1 0 1.5h-4.57l-2.03 4.06a.75.75 0 0 1-1.4-.07l-2.21-5.89-1.02 2.04a.75.75 0 0 1-.67.41H3.75A.75.75 0 0 1 3 12.75Z"/></svg>',
        'arrow' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M13.22 5.22a.75.75 0 0 1 1.06 0l5 5a.75.75 0 0 1 0 1.06l-5 5a.75.75 0 1 1-1.06-1.06l3.72-3.72H5a.75.75 0 0 1 0-1.5h11.94l-3.72-3.72a.75.75 0 0 1 0-1.06Z"/></svg>',
    ];

    return $icons[$icon] ?? $icons['arrow'];
}
?><!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>PrefSADE Serviços Digitais</title>
    <meta name="description" content="Hub oficial de serviços digitais da Prefeitura de Santo Antônio do Descoberto.">
    <meta name="theme-color" content="#0d2d62">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="PrefSADE">
    <link rel="manifest" href="<?= htmlspecialchars($asset('manifest.json'), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="icon" type="image/svg+xml" href="<?= htmlspecialchars($asset('assets/icons/icon-app.svg'), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="apple-touch-icon" href="<?= htmlspecialchars($asset('assets/icons/icon-app.svg'), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars($asset('assets/css/style.css'), ENT_QUOTES, 'UTF-8') ?>">
</head>
<body>
<div class="app-shell">
    <header class="site-header">
        <div class="container header-inner">
            <a class="brand" href="<?= htmlspecialchars($asset('index.php'), ENT_QUOTES, 'UTF-8') ?>" aria-label="Página inicial PrefSADE Serviços Digitais">
                <img src="<?= htmlspecialchars($asset('assets/img/prefsade-mark.svg'), ENT_QUOTES, 'UTF-8') ?>" alt="Marca PrefSADE" class="brand-mark">
                <span class="brand-copy">
                    <strong>PrefSADE Serviços Digitais</strong>
                    <small>Prefeitura de Santo Antônio do Descoberto - GO</small>
                </span>
            </a>

            <nav class="top-actions" aria-label="Ações institucionais">
                <a class="btn btn-ghost" href="https://www.santoantoniododescoberto.go.gov.br/" target="_blank" rel="noreferrer">Portal oficial</a>
                <a class="btn btn-primary" href="#servicos">Acessar serviços</a>
            </nav>
        </div>
    </header>

    <main>
        <section class="hero-section">
            <div class="container hero-grid">
                <div class="hero-copy">
                    <span class="eyebrow">Hub oficial de serviços digitais</span>
                    <h1>Serviços públicos digitais com acesso rápido, visual institucional e base pronta para aplicativo.</h1>
                    <p>
                        O novo ambiente centraliza o acesso aos sistemas já existentes da Prefeitura,
                        preserva as rotas atuais e prepara a experiência para uso moderno em navegador,
                        celular, PWA e futuras publicações móveis.
                    </p>

                    <div class="hero-actions">
                        <a class="btn btn-primary btn-large" href="#servicos">Acessar serviços</a>
                        <a class="btn btn-secondary btn-large" href="#comunicados">Ver comunicados</a>
                    </div>

                    <ul class="hero-stats" aria-label="Indicadores do ambiente">
                        <?php foreach ($stats as $stat): ?>
                            <li>
                                <strong><?= htmlspecialchars((string) $stat['value'], ENT_QUOTES, 'UTF-8') ?></strong>
                                <span><?= htmlspecialchars($stat['label'], ENT_QUOTES, 'UTF-8') ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <aside class="hero-panel" aria-label="Resumo institucional do ambiente">
                    <div class="hero-panel-card">
                        <span class="panel-tag">Transformação digital</span>
                        <h2>Uma entrada única para os sistemas municipais</h2>
                        <p>Estrutura responsiva, segura e escalável para evoluir com novos módulos sem romper os sistemas já publicados.</p>
                        <ul>
                            <li>Experiência otimizada para celular</li>
                            <li>Busca rápida por nome e descrição</li>
                            <li>PWA com suporte offline</li>
                            <li>Base pronta para Android e iOS</li>
                        </ul>
                    </div>
                </aside>
            </div>
        </section>

        <section class="search-section" id="servicos">
            <div class="container section-head">
                <div>
                    <span class="section-kicker">Catálogo institucional</span>
                    <h2>Localize o serviço digital desejado em poucos segundos</h2>
                    <p>Use a busca por nome ou descrição para encontrar rapidamente o módulo correto.</p>
                </div>
                <div class="search-card">
                    <label class="search-label" for="service-search">Busca rápida de serviços</label>
                    <div class="search-field-wrap">
                        <span class="search-icon" aria-hidden="true"><?= iconSvg('search') ?></span>
                        <input
                            id="service-search"
                            class="search-field"
                            type="search"
                            placeholder="Pesquisar por serviço, área ou funcionalidade"
                            autocomplete="off"
                            data-service-search
                        >
                    </div>
                    <p class="search-help">A filtragem acontece em tempo real por nome e descrição do serviço.</p>
                </div>
            </div>
        </section>

        <section class="services-section">
            <div class="container">
                <div class="services-grid" data-services-grid>
                    <?php foreach ($services as $service): ?>
                        <?php $searchIndex = $service['name'] . ' ' . $service['description']; ?>
                        <article class="service-card" data-service-card data-search="<?= htmlspecialchars($searchIndex, ENT_QUOTES, 'UTF-8') ?>">
                            <div class="service-card-top">
                                <span class="service-icon" aria-hidden="true"><?= iconSvg($service['icon']) ?></span>
                                <span class="service-badge"><?= htmlspecialchars($service['badge'], ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <div class="service-card-body">
                                <h3><?= htmlspecialchars($service['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                                <p><?= htmlspecialchars($service['description'], ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                            <a class="service-link" href="<?= htmlspecialchars($absoluteUrl($service['url']), ENT_QUOTES, 'UTF-8') ?>">
                                <span>Acessar serviço</span>
                                <span class="service-link-icon" aria-hidden="true"><?= iconSvg('arrow') ?></span>
                            </a>
                        </article>
                    <?php endforeach; ?>
                </div>

                <div class="empty-state" data-empty-state hidden>
                    <h3>Nenhum serviço encontrado</h3>
                    <p>Tente buscar por outro termo, como o nome do módulo ou o tipo de atendimento desejado.</p>
                </div>
            </div>
        </section>

        <section class="shortcuts-section">
            <div class="container">
                <div class="section-head compact">
                    <div>
                        <span class="section-kicker">Atalhos rápidos</span>
                        <h2>Ações frequentes para agilizar o atendimento digital</h2>
                    </div>
                </div>
                <div class="shortcuts-grid">
                    <?php foreach ($quickActions as $action): ?>
                        <a class="shortcut-card" href="<?= htmlspecialchars($absoluteUrl($action['url']), ENT_QUOTES, 'UTF-8') ?>">
                            <span class="shortcut-icon" aria-hidden="true"><?= iconSvg($action['icon']) ?></span>
                            <strong><?= htmlspecialchars($action['title'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <p><?= htmlspecialchars($action['description'], ENT_QUOTES, 'UTF-8') ?></p>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="announcements-section" id="comunicados">
            <div class="container">
                <div class="section-head compact">
                    <div>
                        <span class="section-kicker">Comunicados</span>
                        <h2>Orientações institucionais para uso do ambiente digital</h2>
                    </div>
                </div>
                <div class="announcements-grid">
                    <?php foreach ($announcements as $announcement): ?>
                        <article class="announcement-card">
                            <span class="announcement-tag"><?= htmlspecialchars($announcement['tag'], ENT_QUOTES, 'UTF-8') ?></span>
                            <h3><?= htmlspecialchars($announcement['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                            <p><?= htmlspecialchars($announcement['description'], ENT_QUOTES, 'UTF-8') ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <div class="container footer-grid">
            <div>
                <h2>PrefSADE Serviços Digitais</h2>
                <p>Ambiente oficial para acesso centralizado aos serviços digitais da Prefeitura de Santo Antônio do Descoberto.</p>
            </div>
            <div>
                <h3>Institucional</h3>
                <ul>
                    <li>Prefeitura de Santo Antônio do Descoberto - GO</li>
                    <li>Secretaria Municipal de Comunicação</li>
                    <li><a href="tel:+556136261289">(61) 3626-1289</a></li>
                    <li><a href="mailto:secomsade@santoantoniododescoberto.go.gov.br">secomsade@santoantoniododescoberto.go.gov.br</a></li>
                </ul>
            </div>
            <div>
                <h3>Portal oficial</h3>
                <ul>
                    <li><a href="https://www.santoantoniododescoberto.go.gov.br/" target="_blank" rel="noreferrer">www.santoantoniododescoberto.go.gov.br</a></li>
                    <li><a href="#servicos">Acessar catálogo de serviços</a></li>
                    <li><a href="#comunicados">Consultar comunicados</a></li>
                </ul>
            </div>
        </div>
    </footer>
</div>
<script src="<?= htmlspecialchars($asset('assets/js/app.js'), ENT_QUOTES, 'UTF-8') ?>" defer></script>
</body>
</html>
