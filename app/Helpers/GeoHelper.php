<?php

declare(strict_types=1);

namespace App\Helpers;

final class GeoHelper
{
    public static function validarCepSantoAntonio(string $cep): array
    {
        $clean = preg_replace('/\D+/', '', $cep) ?? '';
        if (strlen($clean) !== 8) {
            return ['ok' => false, 'message' => 'CEP inválido.'];
        }

        $url = 'https://viacep.com.br/ws/' . $clean . '/json/';
        $ctx = stream_context_create(['http' => ['timeout' => 8]]);
        $raw = @file_get_contents($url, false, $ctx);
        if ($raw === false) {
            return ['ok' => false, 'message' => 'Não foi possível validar o CEP no momento.'];
        }

        $json = json_decode($raw, true);
        if (!is_array($json) || !empty($json['erro'])) {
            return ['ok' => false, 'message' => 'CEP não encontrado.'];
        }

        $localidade = self::norm((string)($json['localidade'] ?? ''));
        $uf = strtoupper(trim((string)($json['uf'] ?? '')));

        if ($localidade !== self::norm('Santo Antônio do Descoberto') || $uf !== 'GO') {
            return ['ok' => false, 'message' => 'Atendimento exclusivo para Santo Antônio do Descoberto - GO.'];
        }

        return ['ok' => true, 'data' => $json];
    }

    public static function geocodeGoogle(string $address, string $apiKey): array
    {
        if ($apiKey === '') {
            return ['ok' => false, 'message' => 'Chave Google Maps não configurada.'];
        }

        $query = rawurlencode($address . ', Santo Antônio do Descoberto, GO, Brasil');
        $url = 'https://maps.googleapis.com/maps/api/geocode/json?address=' . $query . '&region=br&key=' . rawurlencode($apiKey);
        $ctx = stream_context_create(['http' => ['timeout' => 10]]);
        $raw = @file_get_contents($url, false, $ctx);
        if ($raw === false) {
            return ['ok' => false, 'message' => 'Falha no geocoding do Google Maps.'];
        }

        $json = json_decode($raw, true);
        if (!is_array($json) || ($json['status'] ?? '') !== 'OK' || empty($json['results'][0]['geometry']['location'])) {
            return ['ok' => false, 'message' => 'Endereço não localizado no Google Maps.'];
        }

        $loc = $json['results'][0]['geometry']['location'];
        return ['ok' => true, 'lat' => (float)$loc['lat'], 'lng' => (float)$loc['lng']];
    }

    private static function norm(string $v): string
    {
        $v = mb_strtolower(trim($v));
        return strtr($v, ['á'=>'a','à'=>'a','â'=>'a','ã'=>'a','é'=>'e','ê'=>'e','í'=>'i','ó'=>'o','ô'=>'o','õ'=>'o','ú'=>'u','ç'=>'c']);
    }
}
