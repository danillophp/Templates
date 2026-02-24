<?php

if (!defined('ABSPATH')) {
    exit;
}

class MapaPoliticoAI
{
    public static function init(): void
    {
        add_action('wp_ajax_mapa_politico_ai_enrich_text', [self::class, 'ajaxEnrichText']);
    }

    public static function ajaxEnrichText(): void
    {
        if (!current_user_can('edit_others_posts') && !current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Sem permissão.'], 403);
        }
        check_ajax_referer('mapa_politico_admin_nonce', 'nonce');

        $name = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
        $position = sanitize_text_field(wp_unslash($_POST['position'] ?? ''));
        $city = sanitize_text_field(wp_unslash($_POST['city'] ?? ''));

        if ($name === '' || mb_strlen($name) < 5) {
            wp_send_json_error(['message' => 'Informe nome completo válido.'], 400);
        }

        $apiKey = self::getOpenAiApiKey();
        if ($apiKey === '') {
            wp_send_json_error(['message' => 'Chave da OpenAI não configurada no servidor.'], 400);
        }

        $prompt = "Pesquise fontes oficiais confiáveis e responda APENAS texto plano (sem HTML/JSON) com:\n"
            . "STATUS: [OK|NAO_ENCONTRADO|AMBIGUO]\n"
            . "BIOGRAFIA: ...\n"
            . "HISTORICO: ...\n"
            . "FONTES: ...\n"
            . "Entrada: Nome={$name}; Cargo={$position}; Município={$city}.";

        $response = wp_remote_post('https://api.openai.com/v1/responses', [
            'timeout' => 20,
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode([
                'model' => 'gpt-4.1-mini',
                'input' => $prompt,
                'max_output_tokens' => 900,
            ]),
        ]);

        if (is_wp_error($response)) {
            self::log('erro', $city, 'ai_texto', 'Falha de conexão com OpenAI', []);
            wp_send_json_error(['message' => 'Falha de comunicação com IA. Tente novamente em instantes.'], 400);
        }

        $statusCode = (int) wp_remote_retrieve_response_code($response);
        if ($statusCode < 200 || $statusCode >= 300) {
            wp_send_json_error(['message' => 'Falha de comunicação com IA.'], 400);
        }

        $json = json_decode((string) wp_remote_retrieve_body($response), true);
        $text = '';
        if (!empty($json['output'][0]['content'][0]['text'])) {
            $text = (string) $json['output'][0]['content'][0]['text'];
        } elseif (!empty($json['output_text'])) {
            $text = (string) $json['output_text'];
        }

        if ($text === '') {
            wp_send_json_error(['message' => 'IA retornou vazio.'], 400);
        }

        $parsed = self::parseText($text);
        $status = strtoupper((string) ($parsed['status'] ?? ''));
        if ($status === 'NAO_ENCONTRADO') {
            wp_send_json_error(['message' => 'Nome não encontrado.'], 400);
        }
        if ($status === 'AMBIGUO') {
            wp_send_json_error(['message' => 'Nome ambíguo. Informe cargo/município.'], 400);
        }

        self::log('sucesso', $city, 'ai_texto', 'Pesquisa IA manual concluída', [$parsed['fontes'] ?? '']);
        wp_send_json_success([
            'biography' => sanitize_textarea_field((string) ($parsed['biografia'] ?? '')),
            'history' => sanitize_textarea_field((string) ($parsed['historico'] ?? '')),
        ]);
    }

    private static function parseText(string $text): array
    {
        $map = [
            'status' => '/^STATUS:\s*(.+)$/mi',
            'biografia' => '/^BIOGRAFIA:\s*(.+)$/mi',
            'historico' => '/^HISTORICO:\s*(.+)$/mi',
            'fontes' => '/^FONTES:\s*(.+)$/mi',
        ];

        $out = [];
        foreach ($map as $k => $regex) {
            if (preg_match($regex, $text, $m)) {
                $out[$k] = trim((string) ($m[1] ?? ''));
            }
        }

        return $out;
    }

    public static function hasApiKey(): bool
    {
        return self::getOpenAiApiKey() !== '';
    }

    private static function getOpenAiApiKey(): string
    {
        if (defined('MAPA_POLITICO_OPENAI_API_KEY') && MAPA_POLITICO_OPENAI_API_KEY) {
            return (string) MAPA_POLITICO_OPENAI_API_KEY;
        }

        $envValue = getenv('OPENAI_API_KEY');
        if (is_string($envValue) && trim($envValue) !== '') {
            return trim($envValue);
        }

        if (isset($_ENV['OPENAI_API_KEY']) && is_string($_ENV['OPENAI_API_KEY']) && trim($_ENV['OPENAI_API_KEY']) !== '') {
            return trim($_ENV['OPENAI_API_KEY']);
        }

        if (isset($_SERVER['OPENAI_API_KEY']) && is_string($_SERVER['OPENAI_API_KEY']) && trim($_SERVER['OPENAI_API_KEY']) !== '') {
            return trim($_SERVER['OPENAI_API_KEY']);
        }

        return '';
    }

    public static function log(string $type, string $city, string $step, string $reason, array $sources = []): void
    {
        $logs = get_option('mapa_politico_ai_logs', []);
        if (!is_array($logs)) {
            $logs = [];
        }

        $logs[] = [
            'type' => sanitize_text_field($type),
            'city' => sanitize_text_field($city),
            'step' => sanitize_text_field($step),
            'reason' => sanitize_text_field($reason),
            'sources' => sanitize_text_field(implode(' | ', array_map('strval', $sources))),
            'created_at' => current_time('mysql'),
        ];

        if (count($logs) > 500) {
            $logs = array_slice($logs, -500);
        }

        update_option('mapa_politico_ai_logs', $logs, false);
    }
}
