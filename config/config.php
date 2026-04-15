<?php

declare(strict_types=1);

final class FestivalConfig
{
    public const APP_NAME = 'Festival 14 de Maio - Garota SADE 2026';
    public const BASE_URL = '/';
    public const TIMEZONE = 'America/Sao_Paulo';
    public const VOTING_OPEN = true;

    public const MAX_VOTES_PER_IP_PER_DAY = 3;
    public const MAX_VOTES_PER_DEVICE_PER_DAY = 2;
    public const MIN_SECONDS_BETWEEN_VOTES = 25;
    public const MIN_INTERACTION_SECONDS = 5;

    // Nunca exponha em produção: preferir variável de ambiente segura.
    public const HASH_SALT = 'trocar-em-producao-com-valor-forte-e-secreto';
}
