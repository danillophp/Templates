<?php

declare(strict_types=1);

namespace App\Services;

final class MessageTemplateService
{
    public static function buildStatusMessage(array $solicitacao): string
    {
        $status = strtoupper((string)($solicitacao['status'] ?? ''));
        $statusTexto = match ($status) {
            'RECUSADO' => 'indeferida',
            'APROVADO' => 'deferida',
            'ALTERADO' => 'atualizada',
            default => 'analisada',
        };

        $nome = trim((string)($solicitacao['nome'] ?? 'Munícipe'));
        $protocolo = trim((string)($solicitacao['protocolo'] ?? '-'));

        return "Olá, Sr. {$nome}.\n\n"
            . "Informamos que a solicitação nº {$protocolo} foi {$statusTexto} após análise do setor responsável.\n\n"
            . "Esclarecemos que, para a realização do recolhimento, os materiais devem estar dispostos na parte externa do imóvel, em local de fácil acesso, pois a equipe não está autorizada a adentrar o interior da propriedade.\n\n"
            . "📊 Pesquisa de Satisfação – Serviço Cata-Treco\n"
            . "Sua opinião é muito importante para nós.\n\n"
            . "Como você avalia o atendimento recebido?\n\n"
            . "1️⃣ Excelente\n"
            . "2️⃣ Bom\n"
            . "3️⃣ Regular\n"
            . "4️⃣ Ruim\n\n"
            . "Caso deseje, deixe também sua sugestão para melhorarmos nossos serviços.\n\n"
            . "Prefeitura Municipal de Santo Antônio do Descoberto – GO";
    }
}
