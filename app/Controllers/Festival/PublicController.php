<?php

declare(strict_types=1);

namespace App\Controllers\Festival;

use App\Helpers\SecurityHelper;
use App\Models\Festival\CandidateModel;
use App\Services\Festival\RankingService;

final class PublicController
{
    public function home(): void
    {
        $candidateModel = new CandidateModel();
        $rankingService = new RankingService();

        $candidates = $candidateModel->listPublic();
        $ranking = $rankingService->top(10);

        $_SESSION['festival_vote_form_started_at'] = time();
        $csrf = \App\Core\Csrf::token();
        $cookieToken = SecurityHelper::ensureCookieToken();

        require __DIR__ . '/../../Views/festival/layout/header.php';
        require __DIR__ . '/../../Views/festival/public/home.php';
        require __DIR__ . '/../../Views/festival/layout/footer.php';
    }
}
