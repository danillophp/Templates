<?php
namespace App\Services;

class GeoService
{
    public function cidadeUfValida(string $cidade, string $uf): bool
    {
        return mb_strtolower(trim($cidade)) === 'santo antônio do descoberto' && strtoupper(trim($uf)) === 'GO';
    }
}
