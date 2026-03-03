<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Models\ContactModel;
use App\Models\DocumentModel;
use App\Models\NewsModel;
use App\Models\SchoolModel;

final class PublicController extends Controller
{
    public function home(): void
    {
        $news = (new NewsModel())->latestPublic(3);
        $this->view('public/home', ['news' => $news]);
    }

    public function secretaria(): void { $this->view('public/secretaria'); }
    public function transparencia(): void { $this->view('public/transparencia'); }
    public function primeiraInfancia(): void { $this->view('public/primeira_infancia'); }
    public function mapaPublico(): void { $this->view('public/mapa_publico'); }

    public function noticias(): void
    {
        $this->view('public/noticias', ['news' => (new NewsModel())->latestPublic(20)]);
    }

    public function documentos(): void
    {
        $this->view('public/documentos', ['documents' => (new DocumentModel())->allPublic()]);
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
