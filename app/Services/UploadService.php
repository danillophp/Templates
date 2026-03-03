<?php

declare(strict_types=1);

namespace App\Services;

final class UploadService
{
    private const ALLOWED = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'application/pdf' => 'pdf',
    ];

    public function save(array $file, string $directory = 'docs', int $maxSize = 5242880): ?string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK || (int) $file['size'] > $maxSize) {
            throw new \RuntimeException('Upload inválido ou tamanho acima do limite.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $finfo->file($file['tmp_name']);
        if (!isset(self::ALLOWED[$mime])) {
            throw new \RuntimeException('Tipo de arquivo não permitido.');
        }

        $ext = self::ALLOWED[$mime];
        $name = hash('sha256', $file['tmp_name'] . microtime(true) . random_bytes(8)) . '.' . $ext;

        $targetDir = rtrim(UPLOAD_PATH, '/') . '/' . trim($directory, '/');
        if (!is_dir($targetDir) && !mkdir($targetDir, 0750, true) && !is_dir($targetDir)) {
            throw new \RuntimeException('Não foi possível preparar diretório de upload.');
        }

        $dest = $targetDir . '/' . $name;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            throw new \RuntimeException('Falha ao salvar upload.');
        }

        return 'storage/uploads/' . trim($directory, '/') . '/' . $name;
    }
}
