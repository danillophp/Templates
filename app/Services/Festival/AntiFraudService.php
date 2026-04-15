<?php

declare(strict_types=1);

namespace App\Services\Festival;

use App\Helpers\SecurityHelper;
use App\Models\Festival\FraudAttemptModel;
use App\Models\Festival\VoteModel;

final class AntiFraudService
{
    private VoteModel $voteModel;
    private FraudAttemptModel $fraudModel;

    public function __construct()
    {
        $this->voteModel = new VoteModel();
        $this->fraudModel = new FraudAttemptModel();
    }

    /**
     * @return array{decision:string, reason:string}
     */
    public function evaluate(array $context): array
    {
        $ipHash = $context['ip_hash'];
        $fingerprint = $context['device_fingerprint'];
        $sessionToken = $context['sessao_token'];

        if (($context['honeypot'] ?? '') !== '') {
            $this->registerAttempt($context, 'honeypot_preenchido');
            return ['decision' => 'BLOQUEADO', 'reason' => 'Atividade automatizada detectada.'];
        }

        $interactionSeconds = (int) ($context['interaction_seconds'] ?? 0);
        if ($interactionSeconds < FestivalConfig::MIN_INTERACTION_SECONDS) {
            $this->registerAttempt($context, 'tempo_interacao_insuficiente');
            return ['decision' => 'SUSPEITO', 'reason' => 'Interação muito rápida.'];
        }

        $countIp = $this->voteModel->countRecentByIp($ipHash);
        if ($countIp >= FestivalConfig::MAX_VOTES_PER_IP_PER_DAY) {
            $this->registerAttempt($context, 'limite_ip_24h');
            return ['decision' => 'BLOQUEADO', 'reason' => 'Limite de votos por IP atingido em 24h.'];
        }

        $countDevice = $this->voteModel->countRecentByFingerprint($fingerprint);
        if ($countDevice >= FestivalConfig::MAX_VOTES_PER_DEVICE_PER_DAY) {
            $this->registerAttempt($context, 'limite_dispositivo_24h');
            return ['decision' => 'SUSPEITO', 'reason' => 'Limite por dispositivo atingido em 24h.'];
        }

        $lastVoteTs = $this->voteModel->lastVoteUnixBySession($sessionToken);
        if ($lastVoteTs !== null && (time() - $lastVoteTs) < FestivalConfig::MIN_SECONDS_BETWEEN_VOTES) {
            $this->registerAttempt($context, 'repeticao_imediata');
            return ['decision' => 'BLOQUEADO', 'reason' => 'Aguarde alguns segundos para votar novamente.'];
        }

        return ['decision' => 'VALIDO', 'reason' => 'Voto aprovado.'];
    }

    private function registerAttempt(array $context, string $reason): void
    {
        $this->fraudModel->create([
            'ip_hash' => $context['ip_hash'],
            'user_agent_hash' => $context['user_agent_hash'],
            'fingerprint' => $context['device_fingerprint'],
            'motivo' => $reason,
            'payload_json' => json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }
}
