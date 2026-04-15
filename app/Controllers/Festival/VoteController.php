<?php

declare(strict_types=1);

namespace App\Controllers\Festival;

use App\Core\Csrf;
use App\Helpers\ResponseHelper;
use App\Helpers\SecurityHelper;
use App\Models\Festival\CandidateModel;
use App\Models\Festival\VoteModel;
use App\Services\Festival\AntiFraudService;
use App\Services\Festival\RankingService;
use Throwable;

final class VoteController
{
    public function store(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            ResponseHelper::json(['ok' => false, 'message' => 'Método não permitido.'], 405);
            return;
        }

        $token = $_POST['_csrf'] ?? '';
        if (!Csrf::validate(is_string($token) ? $token : null)) {
            ResponseHelper::json(['ok' => false, 'message' => 'Falha de segurança CSRF.'], 419);
            return;
        }

        $candidateId = filter_input(INPUT_POST, 'candidate_id', FILTER_VALIDATE_INT);
        if (!$candidateId) {
            ResponseHelper::json(['ok' => false, 'message' => 'Candidata inválida.'], 422);
            return;
        }

        $interactionSeconds = (int) filter_input(INPUT_POST, 'interaction_seconds', FILTER_VALIDATE_INT);
        $honeypot = SecurityHelper::sanitizeText($_POST['website'] ?? '', 100);

        $ipHash = SecurityHelper::hashValue(SecurityHelper::ip());
        $uaHash = SecurityHelper::hashValue($_SERVER['HTTP_USER_AGENT'] ?? 'unknown');
        $fingerprint = SecurityHelper::fingerprint();
        $sessionToken = session_id();

        $fraudService = new AntiFraudService();
        $decision = $fraudService->evaluate([
            'candidate_id' => $candidateId,
            'ip_hash' => $ipHash,
            'user_agent_hash' => $uaHash,
            'device_fingerprint' => $fingerprint,
            'sessao_token' => $sessionToken,
            'honeypot' => $honeypot,
            'interaction_seconds' => $interactionSeconds,
            'origin' => SecurityHelper::sanitizeText($_POST['origin'] ?? 'web', 50),
        ]);

        $candidateModel = new CandidateModel();
        $candidate = $candidateModel->findById($candidateId);

        if (!$candidate || $candidate['status'] !== 'ATIVA') {
            ResponseHelper::json(['ok' => false, 'message' => 'Candidata não disponível.'], 422);
            return;
        }

        try {
            $voteModel = new VoteModel();
            $status = $decision['decision'] === 'VALIDO' ? 'VALIDO' : ($decision['decision'] === 'SUSPEITO' ? 'SUSPEITO' : 'BLOQUEADO');

            $voteModel->create([
                'candidata_id' => $candidateId,
                'ip_hash' => $ipHash,
                'user_agent_hash' => $uaHash,
                'device_fingerprint' => $fingerprint,
                'cookie_token' => SecurityHelper::hashValue($_COOKIE['festival_vote_token'] ?? ''),
                'sessao_token' => $sessionToken,
                'origem' => SecurityHelper::sanitizeText($_POST['origin'] ?? 'web', 50),
                'status' => $status,
            ]);

            if ($status === 'VALIDO') {
                $candidateModel->incrementVoteTotal($candidateId);
            }

            $ranking = (new RankingService())->top(10);
            ResponseHelper::json([
                'ok' => $status === 'VALIDO',
                'status' => $status,
                'message' => $status === 'VALIDO' ? 'Voto registrado com sucesso!' : $decision['reason'],
                'ranking' => $ranking,
            ], $status === 'VALIDO' ? 200 : 429);
        } catch (Throwable $e) {
            error_log('[festival_vote_error] ' . $e->getMessage());
            ResponseHelper::json(['ok' => false, 'message' => 'Erro interno ao registrar voto.'], 500);
        }
    }

    public function ranking(): void
    {
        $limit = (int) ($_GET['limit'] ?? 10);
        $ranking = (new RankingService())->top($limit);
        ResponseHelper::json(['ok' => true, 'ranking' => $ranking]);
    }
}
