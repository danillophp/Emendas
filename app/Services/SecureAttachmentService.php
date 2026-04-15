<?php

namespace App\Services;

class SecureAttachmentService
{
    private const MAX_SIZE = 5242880; // 5MB
    private const BASE_DIR = 'storage/uploads/emendas';

    /**
     * @param array<string,mixed>|null $file
     * @return array{ok:bool,path:?string,original_name:?string,mime:?string,errors:array<int,string>}
     */
    public function store(?array $file): array
    {
        if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return ['ok' => true, 'path' => null, 'original_name' => null, 'mime' => null, 'errors' => []];
        }

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'path' => null, 'original_name' => null, 'mime' => null, 'errors' => ['Falha no upload do anexo.']];
        }

        $size = (int)($file['size'] ?? 0);
        if ($size <= 0 || $size > self::MAX_SIZE) {
            return ['ok' => false, 'path' => null, 'original_name' => null, 'mime' => null, 'errors' => ['Arquivo inválido. Limite máximo: 5MB.']];
        }

        $tmp = (string)($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            return ['ok' => false, 'path' => null, 'original_name' => null, 'mime' => null, 'errors' => ['Upload não confiável.']];
        }

        $originalName = $this->sanitizeOriginalName((string)($file['name'] ?? 'documento'));
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($extension, ['pdf', 'docx'], true)) {
            return ['ok' => false, 'path' => null, 'original_name' => null, 'mime' => null, 'errors' => ['Apenas arquivos PDF e DOCX são permitidos.']];
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = (string)$finfo->file($tmp);
        if (!$this->isValidMime($extension, $mime)) {
            return ['ok' => false, 'path' => null, 'original_name' => null, 'mime' => null, 'errors' => ['Tipo MIME inválido para o arquivo enviado.']];
        }

        // Anti-mascaramento adicional por assinatura de arquivo.
        if (!$this->matchesBinarySignature($extension, $tmp)) {
            return ['ok' => false, 'path' => null, 'original_name' => null, 'mime' => null, 'errors' => ['Conteúdo do arquivo não corresponde ao tipo permitido.']];
        }

        $base = BASE_PATH . '/' . self::BASE_DIR;
        $metaDir = $base . '/.meta';

        if (!is_dir($base) && !mkdir($base, 0750, true) && !is_dir($base)) {
            return ['ok' => false, 'path' => null, 'original_name' => null, 'mime' => null, 'errors' => ['Falha ao preparar diretório de anexos.']];
        }
        if (!is_dir($metaDir) && !mkdir($metaDir, 0750, true) && !is_dir($metaDir)) {
            return ['ok' => false, 'path' => null, 'original_name' => null, 'mime' => null, 'errors' => ['Falha ao preparar diretório de metadados.']];
        }

        $this->ensureDenyDirectAccess($base);

        $secureName = 'anexo_' . date('YmdHis') . '_' . bin2hex(random_bytes(12)) . '.' . $extension;
        $destination = $base . '/' . $secureName;
        if (!move_uploaded_file($tmp, $destination)) {
            return ['ok' => false, 'path' => null, 'original_name' => null, 'mime' => null, 'errors' => ['Falha ao salvar arquivo no servidor.']];
        }

        $relativePath = self::BASE_DIR . '/' . $secureName;

        $meta = [
            'original_name' => $originalName,
            'mime' => $mime,
            'uploaded_at' => date('c'),
        ];
        @file_put_contents($metaDir . '/' . $secureName . '.json', json_encode($meta, JSON_UNESCAPED_UNICODE));

        return ['ok' => true, 'path' => $relativePath, 'original_name' => $originalName, 'mime' => $mime, 'errors' => []];
    }

    public function resolveAbsolutePath(string $relativePath): ?string
    {
        $relativePath = ltrim($relativePath, '/');
        if (!str_starts_with($relativePath, self::BASE_DIR . '/')) {
            return null;
        }

        $absolute = BASE_PATH . '/' . $relativePath;
        if (!is_file($absolute)) {
            return null;
        }

        return $absolute;
    }

    public function originalName(string $relativePath): string
    {
        $fileName = basename($relativePath);
        $metaPath = BASE_PATH . '/' . self::BASE_DIR . '/.meta/' . $fileName . '.json';
        if (is_file($metaPath)) {
            $raw = @file_get_contents($metaPath);
            if (is_string($raw) && $raw !== '') {
                $json = json_decode($raw, true);
                if (is_array($json) && !empty($json['original_name'])) {
                    return (string)$json['original_name'];
                }
            }
        }

        return $fileName;
    }

    private function sanitizeOriginalName(string $name): string
    {
        $clean = preg_replace('/[^a-zA-Z0-9._-]/', '_', trim($name)) ?? 'documento';
        return mb_substr($clean, 0, 150);
    }

    private function isValidMime(string $ext, string $mime): bool
    {
        if ($ext === 'pdf') {
            return $mime === 'application/pdf';
        }

        if ($ext === 'docx') {
            return in_array($mime, ['application/zip', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'], true);
        }

        return false;
    }

    private function matchesBinarySignature(string $ext, string $tmpPath): bool
    {
        $sample = @file_get_contents($tmpPath, false, null, 0, 8);
        if (!is_string($sample)) {
            return false;
        }

        if ($ext === 'pdf') {
            return str_starts_with($sample, "%PDF");
        }

        if ($ext === 'docx') {
            if (!str_starts_with($sample, "PK")) {
                return false;
            }

            $zip = new \ZipArchive();
            if ($zip->open($tmpPath) !== true) {
                return false;
            }
            $hasManifest = $zip->locateName('[Content_Types].xml') !== false;
            $hasWordDoc = $zip->locateName('word/document.xml') !== false;
            $zip->close();
            return $hasManifest && $hasWordDoc;
        }

        return false;
    }

    private function ensureDenyDirectAccess(string $directory): void
    {
        $htaccess = $directory . '/.htaccess';
        if (!is_file($htaccess)) {
            @file_put_contents($htaccess, "Deny from all\n");
        }
    }
}
