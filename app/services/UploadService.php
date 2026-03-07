<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

class UploadService
{
    private const MAX_FILE_SIZE = 5242880; // 5MB
    private const ALLOWED_EXTENSIONS = ['png', 'jpg', 'jpeg', 'webp'];
    private const ALLOWED_MIME_TYPES = ['image/png', 'image/jpeg', 'image/webp'];

    public function uploadStudioLogo(array $file): string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            throw new RuntimeException('Selecione uma imagem para upload.');
        }

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Falha ao processar o upload da logo.');
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');
        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            throw new RuntimeException('Arquivo de upload inválido.');
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > self::MAX_FILE_SIZE) {
            throw new RuntimeException('A logo deve ter no máximo 5MB.');
        }

        $originalName = (string) ($file['name'] ?? '');
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw new RuntimeException('Formato inválido. Envie PNG, JPG ou WEBP.');
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? (string) finfo_file($finfo, $tmpName) : '';
        if ($finfo) {
            finfo_close($finfo);
        }

        if (!in_array($mime, self::ALLOWED_MIME_TYPES, true)) {
            throw new RuntimeException('Tipo MIME inválido para imagem.');
        }

        $imageInfo = @getimagesize($tmpName);
        if ($imageInfo === false) {
            throw new RuntimeException('Arquivo enviado não é uma imagem válida.');
        }

        $targetDir = __DIR__ . '/../../public/uploads/logo';
        if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
            throw new RuntimeException('Não foi possível preparar o diretório de upload.');
        }

        $safeBaseName = 'logo_studio_' . date('Y_m_d_His') . '_' . bin2hex(random_bytes(4));
        $filename = $safeBaseName . '.' . $extension;
        $targetFile = $targetDir . '/' . $filename;

        if (!move_uploaded_file($tmpName, $targetFile)) {
            throw new RuntimeException('Não foi possível salvar a logo enviada.');
        }

        @chmod($targetFile, 0644);

        return 'uploads/logo/' . $filename;
    }
}
