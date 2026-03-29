<?php

namespace App\Adapters;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class GeminiAdapter
{
    /**
     * Send content parts to Gemini and get a structured JSON response.
     *
     * @param  string  $systemPrompt  System instruction for the model
     * @param  array  $parts  Gemini content parts (text, inline_data, etc.)
     * @param  array  $responseSchema  JSON schema for structured output
     * @return array Parsed JSON response
     */
    public function generateJson(string $systemPrompt, array $parts, array $responseSchema): array
    {
        $apiKey = config('services.gemini.api_key');
        $model = config('services.gemini.model', 'gemini-2.5-flash');

        if (! $apiKey) {
            throw new RuntimeException('GEMINI_API_KEY no está configurada.');
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        $response = Http::timeout(120)
            ->connectTimeout(10)
            ->retry(2, 2000, fn ($exception) => $exception->getCode() >= 429)
            ->post($url, [
                'system_instruction' => [
                    'parts' => [['text' => $systemPrompt]],
                ],
                'contents' => [
                    ['parts' => $parts],
                ],
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                    'responseSchema' => $responseSchema,
                ],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException(
                "Error de la API de Gemini: {$response->status()} — {$response->body()}"
            );
        }

        $body = $response->json();
        $text = $body['candidates'][0]['content']['parts'][0]['text'] ?? '';
        $finishReason = $body['candidates'][0]['finishReason'] ?? 'STOP';

        logger()->debug('Gemini API response', [
            'status' => $response->status(),
            'finish_reason' => $finishReason,
            'text_length' => strlen($text),
            'text_preview' => mb_substr($text, 0, 500),
        ]);

        if ($text === '') {
            throw new RuntimeException('La API de Gemini devolvió una respuesta vacía.');
        }

        // Strip markdown code fences if present
        $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
        $text = preg_replace('/\s*```\s*$/', '', $text);
        $text = trim($text);

        // If response was truncated, try to salvage the JSON
        if ($finishReason === 'MAX_TOKENS') {
            logger()->warning('Gemini: response truncated (MAX_TOKENS), attempting to salvage JSON');
            $text = $this->salvageTruncatedJson($text);
        }

        $result = json_decode($text, true);

        if (! is_array($result)) {
            logger()->error('Gemini: failed to parse JSON', ['text' => mb_substr($text, 0, 1000)]);
            throw new RuntimeException('La respuesta de la IA no es un JSON válido.');
        }

        return $result;
    }

    /**
     * Build inline_data part for a file (image or PDF).
     */
    public function filePart(string $fullPath, string $mimeType): array
    {
        return [
            'inline_data' => [
                'mime_type' => $mimeType,
                'data' => base64_encode(file_get_contents($fullPath)),
            ],
        ];
    }

    /**
     * Build a text part.
     */
    public function textPart(string $text): array
    {
        return ['text' => $text];
    }

    /**
     * Attempt to fix truncated JSON by finding the last complete object and closing the array.
     */
    private function salvageTruncatedJson(string $text): string
    {
        $lastBrace = strrpos($text, '}');
        if ($lastBrace === false) {
            return $text;
        }

        $text = substr($text, 0, $lastBrace + 1);

        if (! str_starts_with(trim($text), '[')) {
            $text = '[' . $text;
        }
        if (! str_ends_with(trim($text), ']')) {
            $text = $text . ']';
        }

        return $text;
    }
}
