<?php
namespace App\Services;

class GeoService
{
    public function validateCepLocation(string $cep): array
    {
        $cep = preg_replace('/\D+/', '', $cep);
        $viaCep = @file_get_contents('https://viacep.com.br/ws/' . $cep . '/json/');
        if (!$viaCep) {
            throw new \RuntimeException('Falha ao consultar CEP.');
        }
        $data = json_decode($viaCep, true);
        if (($data['uf'] ?? '') !== 'GO' || stripos($data['localidade'] ?? '', 'Santo Antônio do Descoberto') === false) {
            throw new \RuntimeException('CEP fora da área atendida.');
        }
        $query = urlencode(trim(($data['logradouro'] ?? '') . ' ' . ($data['bairro'] ?? '') . ' Santo Antônio do Descoberto GO'));
        $nom = @file_get_contents('https://nominatim.openstreetmap.org/search?format=json&countrycodes=br&limit=1&q=' . $query);
        $geo = json_decode($nom ?: '[]', true);
        if (empty($geo[0])) {
            throw new \RuntimeException('Não foi possível geocodificar o endereço.');
        }
        return ['address' => $data, 'lat' => (float)$geo[0]['lat'], 'lng' => (float)$geo[0]['lon']];
    }
}
