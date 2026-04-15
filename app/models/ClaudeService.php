<?php
/**
 * ====================================================================
 * SYND_GEST - Service Claude AI
 * ====================================================================
 * Couche centralisée pour tous les appels à l'API Anthropic Claude.
 * Utilisée par tous les modules IA (déclaration fiscale, comptabilité admin, etc.)
 */

class ClaudeService {

    private string $apiKey;
    private string $model;
    private int $maxTokens;
    private int $timeout;

    public function __construct(int $maxTokens = 2048, int $timeout = 60) {
        $this->apiKey = ANTHROPIC_API_KEY;
        $this->model = ANTHROPIC_MODEL;
        $this->maxTokens = $maxTokens;
        $this->timeout = $timeout;
    }

    /**
     * Envoyer un message à Claude (texte seul)
     * @param string $systemPrompt Prompt système (contexte + instructions)
     * @param array $messages Historique [{role, content}, ...]
     * @return array ['success' => bool, 'message' => string, 'extractedData' => ?array]
     */
    public function chat(string $systemPrompt, array $messages): array {
        return $this->callApi($systemPrompt, $messages);
    }

    /**
     * Envoyer un message avec des fichiers (vision/document)
     * @param string $systemPrompt Prompt système
     * @param array $history Historique de conversation
     * @param string $userMessage Message texte de l'utilisateur
     * @param array $filePaths Chemins absolus des fichiers à analyser
     * @return array ['success' => bool, 'message' => string, 'extractedData' => ?array]
     */
    public function chatWithFiles(string $systemPrompt, array $history, string $userMessage, array $filePaths): array {
        // Construire les messages historiques
        $messages = [];
        foreach ($history as $msg) {
            $messages[] = ['role' => $msg['role'], 'content' => $msg['content']];
        }

        // Construire le message actuel avec les fichiers
        $content = [];
        foreach ($filePaths as $path) {
            $fileContent = $this->prepareFileContent($path);
            if ($fileContent) {
                $content[] = $fileContent;
            }
        }
        $content[] = ['type' => 'text', 'text' => $userMessage];

        $messages[] = ['role' => 'user', 'content' => $content];

        return $this->callApi($systemPrompt, $messages);
    }

    /**
     * Préparer le contenu d'un fichier pour l'API (base64)
     * @param string $path Chemin absolu du fichier
     * @return array|null Contenu formaté pour l'API ou null si non supporté
     */
    private function prepareFileContent(string $path): ?array {
        if (!file_exists($path)) return null;

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $data = base64_encode(file_get_contents($path));

        if (in_array($ext, ['jpg', 'jpeg'])) {
            return ['type' => 'image', 'source' => ['type' => 'base64', 'media_type' => 'image/jpeg', 'data' => $data]];
        }
        if ($ext === 'png') {
            return ['type' => 'image', 'source' => ['type' => 'base64', 'media_type' => 'image/png', 'data' => $data]];
        }
        if ($ext === 'webp') {
            return ['type' => 'image', 'source' => ['type' => 'base64', 'media_type' => 'image/webp', 'data' => $data]];
        }
        if ($ext === 'pdf') {
            return ['type' => 'document', 'source' => ['type' => 'base64', 'media_type' => 'application/pdf', 'data' => $data]];
        }

        return null;
    }

    /**
     * Appel à l'API Anthropic
     */
    private function callApi(string $systemPrompt, array $messages): array {
        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-api-key: ' . $this->apiKey,
                'anthropic-version: 2023-06-01',
            ],
            CURLOPT_POSTFIELDS => json_encode([
                'model' => $this->model,
                'max_tokens' => $this->maxTokens,
                'system' => $systemPrompt,
                'messages' => $messages,
            ]),
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return ['success' => false, 'message' => "Erreur réseau : $curlError", 'extractedData' => null];
        }

        if (!$response || $httpCode !== 200) {
            $error = json_decode($response, true);
            $msg = $error['error']['message'] ?? "Erreur API (HTTP $httpCode)";
            return ['success' => false, 'message' => $msg, 'extractedData' => null];
        }

        $data = json_decode($response, true);
        $text = $data['content'][0]['text'] ?? 'Pas de réponse.';

        // Extraire les données JSON structurées (entre |||JSON||| et |||/JSON|||)
        $extractedData = $this->extractJsonData($text);
        if ($extractedData) {
            $text = $this->removeJsonBlocks($text);
        }

        return ['success' => true, 'message' => trim($text), 'extractedData' => $extractedData];
    }

    /**
     * Extraire les blocs JSON structurés de la réponse
     */
    public function extractJsonData(string $text): ?array {
        if (preg_match_all('/\|\|\|JSON\|\|\|(.*?)\|\|\|\/JSON\|\|\|/s', $text, $matches)) {
            $allData = [];
            foreach ($matches[1] as $jsonStr) {
                $parsed = json_decode(trim($jsonStr), true);
                if ($parsed) $allData[] = $parsed;
            }
            return !empty($allData) ? (count($allData) === 1 ? $allData[0] : $allData) : null;
        }
        return null;
    }

    /**
     * Supprimer les blocs JSON de la réponse affichée
     */
    public function removeJsonBlocks(string $text): string {
        return preg_replace('/\|\|\|JSON\|\|\|.*?\|\|\|\/JSON\|\|\|/s', '', $text);
    }

    /**
     * Uploader et valider un fichier
     * @param array $file $_FILES['document']
     * @param string $uploadDir Dossier de destination
     * @param string $prefix Préfixe du nom de fichier
     * @return array ['success' => bool, 'filename' => string, 'path' => string, 'ext' => string, 'error' => ?string]
     */
    public static function uploadFile(array $file, string $uploadDir, string $prefix = 'doc'): array {
        $allowedExts = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'xlsx', 'xls'];
        $maxSize = 10 * 1024 * 1024; // 10 Mo

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExts)) {
            return ['success' => false, 'error' => "Type de fichier non autorisé ($ext). Acceptés : PDF, JPG, PNG, XLSX"];
        }
        if ($file['size'] > $maxSize) {
            return ['success' => false, 'error' => "Fichier trop volumineux (max 10 Mo)."];
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => "Erreur d'upload (code {$file['error']})."];
        }

        $filename = $prefix . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $filepath = rtrim($uploadDir, '/') . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            return ['success' => false, 'error' => "Erreur lors de l'écriture du fichier."];
        }

        return [
            'success' => true,
            'filename' => $filename,
            'originalName' => $file['name'],
            'path' => $filepath,
            'ext' => $ext,
            'mime' => $file['type'],
            'size' => $file['size'],
        ];
    }

    /**
     * Formater du markdown basique en HTML (pour le rendu chat)
     */
    public static function markdownToHtml(string $text): string {
        $html = $text;
        $html = preg_replace('/\*\*(.*?)\*\*/s', '<strong>$1</strong>', $html);
        $html = preg_replace('/\*(.*?)\*/s', '<em>$1</em>', $html);
        $html = preg_replace('/^### (.*)/m', '<h5 class="mt-3">$1</h5>', $html);
        $html = preg_replace('/^## (.*)/m', '<h4 class="mt-3">$1</h4>', $html);
        $html = preg_replace('/^# (.*)/m', '<h3 class="mt-3">$1</h3>', $html);
        $html = preg_replace('/^- (.*)/m', '<li>$1</li>', $html);
        $html = preg_replace('/^• (.*)/m', '<li>$1</li>', $html);
        $html = preg_replace('/^(\d+)\. (.*)/m', '<li>$2</li>', $html);
        $html = str_replace("\n", '<br>', $html);
        return $html;
    }
}
