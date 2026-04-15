<?php

declare(strict_types=1);

namespace App\Services\Festival;

use App\Models\Festival\CandidateModel;

final class RankingService
{
    private CandidateModel $candidateModel;

    public function __construct()
    {
        $this->candidateModel = new CandidateModel();
    }

    public function top(int $limit): array
    {
        $items = $this->candidateModel->ranking($limit);

        foreach ($items as $index => &$item) {
            $item['posicao'] = $index + 1;
            $item['destaque'] = $item['posicao'] <= 3;
        }

        return $items;
    }
}
