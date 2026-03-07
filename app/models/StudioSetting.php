<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class StudioSetting extends Model
{
    public function get(): array
    {
        $stmt = $this->db->query('SELECT * FROM configuracoes_studio WHERE id = 1 LIMIT 1');
        $row = $stmt->fetch();

        if ($row) {
            return $row;
        }

        return [
            'nome_studio' => 'Studio Bruna Nayara',
            'telefone' => '(61) 99176-7582',
            'endereco' => 'Quadra 87 - Centro, Santo Antônio do Descoberto - GO, 72900-198',
            'instagram' => 'https://www.instagram.com/studiobrunanayaraa/',
            'facebook' => 'https://www.facebook.com/studiobrunanayaraa/',
            'link_localizacao' => '',
            'horario_funcionamento' => "segunda-feira: 08:00–19:00\nterça-feira: 08:00–19:00\nquarta-feira: 08:00–19:00\nquinta-feira: 08:00–19:00\nsexta-feira: 08:00–19:00\nsábado: 08:00–19:00\ndomingo: Fechado",
            'logo_path' => '',
            'ativar_convite_google' => 1,
            'texto_convite_google' => 'Gostou do nosso atendimento? Sua avaliação no Google é muito importante para o Studio Bruna Nayara.',
            'link_avaliacao_google' => '',
        ];
    }

    public function save(array $data): void
    {
        $sql = 'INSERT INTO configuracoes_studio (
                    id, nome_studio, endereco, telefone, instagram, facebook, link_localizacao,
                    horario_funcionamento, logo_path, ativar_convite_google, texto_convite_google, link_avaliacao_google
                ) VALUES (
                    1, :nome_studio, :endereco, :telefone, :instagram, :facebook, :link_localizacao,
                    :horario_funcionamento, :logo_path, :ativar_convite_google, :texto_convite_google, :link_avaliacao_google
                )
                ON DUPLICATE KEY UPDATE
                    nome_studio = VALUES(nome_studio),
                    endereco = VALUES(endereco),
                    telefone = VALUES(telefone),
                    instagram = VALUES(instagram),
                    facebook = VALUES(facebook),
                    link_localizacao = VALUES(link_localizacao),
                    horario_funcionamento = VALUES(horario_funcionamento),
                    logo_path = VALUES(logo_path),
                    ativar_convite_google = VALUES(ativar_convite_google),
                    texto_convite_google = VALUES(texto_convite_google),
                    link_avaliacao_google = VALUES(link_avaliacao_google)';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':nome_studio' => $data['nome_studio'],
            ':endereco' => $data['endereco'],
            ':telefone' => $data['telefone'],
            ':instagram' => $data['instagram'],
            ':facebook' => $data['facebook'],
            ':link_localizacao' => $data['link_localizacao'],
            ':horario_funcionamento' => $data['horario_funcionamento'],
            ':logo_path' => $data['logo_path'],
            ':ativar_convite_google' => $data['ativar_convite_google'],
            ':texto_convite_google' => $data['texto_convite_google'],
            ':link_avaliacao_google' => $data['link_avaliacao_google'],
        ]);
    }
}
