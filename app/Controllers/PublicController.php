<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Models\ContactModel;
use App\Models\DocumentModel;
use App\Models\NewsModel;
use App\Models\SchoolModel;
use App\Services\CacheService;

final class PublicController extends Controller
{
    private CacheService $cache;

    public function __construct()
    {
        $this->cache = new CacheService();
    }

    public function home(): void
    {
        $key = 'page_home';
        $newsModel = new NewsModel();
        $lastModified = $newsModel->lastModifiedPublic();
        $news = $this->cache->remember($key, 120, ['home', 'news'], static fn(): array => $newsModel->latestPublic(3));
        $etag = hash('sha256', json_encode($news));
        if ($this->applyHttpCacheHeaders($etag, $lastModified)) {
            return;
        }
        $this->view('public/home', ['news' => $news]);
    }

    public function secretaria(): void
    {
        $etag = hash('sha256', 'secretaria-static');
        if ($this->applyHttpCacheHeaders($etag, date('Y-m-d H:i:s'))) {
            return;
        }
        $this->view('public/secretaria');
    }

    public function transparencia(): void { $this->view('public/transparencia'); }
    public function primeiraInfancia(): void { $this->view('public/primeira_infancia'); }
    public function mapaPublico(): void { $this->view('public/mapa_publico'); }

    public function noticias(): void
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $key = 'page_news_' . $page;
        $model = new NewsModel();
        $lastModified = $model->lastModifiedPublic();
        $news = $this->cache->remember($key, 180, ['news', 'home'], static fn(): array => $model->latestPublic(20));
        $etag = hash('sha256', json_encode($news) . $page);
        if ($this->applyHttpCacheHeaders($etag, $lastModified)) {
            return;
        }
        $this->view('public/noticias', ['news' => $news]);
    }

    public function documentos(): void
    {
        $year = (int) ($_GET['year'] ?? 0);
        $key = 'page_docs_' . $year;
        $model = new DocumentModel();
        $lastModified = $model->lastModifiedPublic();
        $docs = $this->cache->remember($key, 180, ['docs'], static fn(): array => $model->allPublic());
        $etag = hash('sha256', json_encode($docs) . $year);
        if ($this->applyHttpCacheHeaders($etag, $lastModified)) {
            return;
        }
        $this->view('public/documentos', ['documents' => $docs]);
    }

    public function contato(): void
    {
        $this->view('public/contato', ['csrf' => Csrf::token()]);
    }

    public function salvarContato(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::validate($_POST['_csrf'] ?? null)) {
            $this->redirect('contato');
        }

        if (!empty($_POST['company'] ?? '')) {
            $this->redirect('contato');
        }

        (new ContactModel())->create([
            ':name' => trim((string) ($_POST['name'] ?? '')),
            ':email' => trim((string) ($_POST['email'] ?? '')),
            ':subject' => trim((string) ($_POST['subject'] ?? '')),
            ':message' => trim((string) ($_POST['message'] ?? '')),
            ':ip_address' => (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'),
        ]);

        $this->view('public/contato', ['csrf' => Csrf::token(), 'success' => 'Mensagem enviada com sucesso.']);
    }
}
