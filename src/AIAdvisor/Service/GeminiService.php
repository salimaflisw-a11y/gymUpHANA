<?php

namespace App\AIAdvisor\Service;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GeminiService
{
    private string $effectiveModel;
    private LoggerInterface $logger;

    public function __construct(
        private readonly ?string $apiKey = null,
        ?string $model = null,
        ?LoggerInterface $logger = null
    ) {
        $this->effectiveModel = $model ?: 'gemini-3.5-flash-lite';
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Send structured conversation to Gemini API with system instructions.
     *
     * @param array<array{role: string, text: string}> $history
     */
    public function generateResponse(string $systemPrompt, array $history, string $userMessage): string
    {
        if (empty($this->apiKey)) {
            throw new \RuntimeException('Gemini API key is not configured.');
        }

        $models = array_unique([$this->effectiveModel, 'gemini-3.5-flash-lite', 'gemini-3.8-flash']);
        $lastException = null;

        foreach ($models as $targetModel) {
            try {
                return $this->callModel($targetModel, $systemPrompt, $history, $userMessage);
            } catch (\Throwable $e) {
                $this->logger->warning("Gemini model {$targetModel} attempt failed: " . $e->getMessage());
                $lastException = $e;
            }
        }

        throw new \RuntimeException('Failed to generate response from Gemini: ' . ($lastException ? $lastException->getMessage() : 'All model attempts failed'));
    }

    private function callModel(string $model, string $systemPrompt, array $history, string $userMessage): string
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . urlencode($this->apiKey);

        // Convert conversation history into Gemini format
        $contents = [];

        foreach ($history as $turn) {
            $role = ($turn['role'] === 'user' || $turn['role'] === 'client') ? 'user' : 'model';
            $contents[] = [
                'role' => $role,
                'parts' => [
                    ['text' => $turn['text']]
                ]
            ];
        }

        // Add current user prompt
        $contents[] = [
            'role' => 'user',
            'parts' => [
                ['text' => $userMessage]
            ]
        ];

        $payload = [
            'system_instruction' => [
                'parts' => [
                    ['text' => $systemPrompt]
                ]
            ],
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 1500,
                'topP' => 0.95,
            ]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 25);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);

        if ($curlErr) {
            throw new \RuntimeException("cURL error communicating with Gemini: {$curlErr}");
        }

        if ($httpCode !== 200) {
            $decoded = json_decode($response, true);
            $msg = $decoded['error']['message'] ?? "HTTP {$httpCode}: {$response}";
            throw new \RuntimeException($msg);
        }

        $result = json_decode($response, true);
        $replyText = $result['candidates'][0]['content']['parts'][0]['text'] ?? null;

        if (!$replyText) {
            throw new \RuntimeException('Gemini API returned an empty response.');
        }

        return trim($replyText);
    }
}
