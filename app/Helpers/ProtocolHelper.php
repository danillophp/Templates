<?php

declare(strict_types=1);

namespace App\Helpers;

final class ProtocolHelper
{
    public static function fromId(int $id): string
    {
        return sprintf('CAT-%s-%06d', date('Y'), $id);
    }
}
