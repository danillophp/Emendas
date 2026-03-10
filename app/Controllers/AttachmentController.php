<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Demand;
use App\Models\SystemLog;
use App\Services\SecureAttachmentService;

class AttachmentController extends Controller
{
    public function __construct(
        private readonly Demand $demands = new Demand(),
        private readonly SystemLog $logs = new SystemLog(),
        private readonly SecureAttachmentService $attachments = new SecureAttachmentService()
    ) {
    }

    public function downloadDemandAttachment(): void
    {
        $this->requireAuth();

        $demandId = (int)($_GET['demand_id'] ?? 0);
        if ($demandId <= 0) {
            http_response_code(400);
            echo 'Anexo inválido.';
            return;
        }

        $demand = $this->demands->findById($demandId);
        if (!$demand || empty($demand['anexo_emenda'])) {
            http_response_code(404);
            echo 'Anexo não encontrado.';
            return;
        }

        $role = (string)($_SESSION['user']['role'] ?? '');
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        if ($role !== 'master' && (int)$demand['funcionario_id'] !== $userId) {
            http_response_code(403);
            echo 'Acesso negado ao anexo.';
            return;
        }

        $relativePath = (string)$demand['anexo_emenda'];
        $file = $this->attachments->resolveAbsolutePath($relativePath);
        if ($file === null) {
            http_response_code(404);
            echo 'Arquivo indisponível.';
            return;
        }

        $originalName = (string)($demand['anexo_nome_original'] ?? '');
        if ($originalName === '') {
            $originalName = $this->attachments->originalName($relativePath);
        }
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $inline = (($_GET['inline'] ?? '0') === '1') && $extension === 'pdf';

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = (string)$finfo->file($file);

        $this->logs->create([
            'usuario_id' => $userId,
            'acao' => 'download_anexo',
            'entidade' => 'demandas',
            'entidade_id' => $demandId,
            'descricao' => 'Download/visualização de anexo da demanda',
            'ip' => client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);

        header('Content-Description: File Transfer');
        header('Content-Type: ' . ($mime !== '' ? $mime : 'application/octet-stream'));
        header('Content-Length: ' . (string)filesize($file));
        header('X-Content-Type-Options: nosniff');
        header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . addslashes($originalName) . '"');

        readfile($file);
        exit;
    }
}
