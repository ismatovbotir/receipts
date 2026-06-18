<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    private string $apiKey;
    private string $endpoint;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.key', '');
        $model        = config('services.gemini.model', 'gemini-2.0-flash');
        $this->endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";
    }

    public function askWithData(string $question, array $data): array
    {
        if (empty($this->apiKey)) {
            return $this->err('GEMINI_API_KEY is not configured in .env');
        }

        $response = Http::timeout(45)->post(
            "{$this->endpoint}?key={$this->apiKey}",
            [
                'systemInstruction' => [
                    'parts' => [['text' => $this->systemPrompt()]],
                ],
                'contents' => [
                    [
                        'role'  => 'user',
                        'parts' => [['text' => $this->userPrompt($question, $data)]],
                    ],
                ],
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                    'temperature'      => 0.15,
                    'maxOutputTokens'  => 2048,
                ],
            ]
        );

        if ($response->failed()) {
            $msg = $response->json('error.message') ?? ('HTTP ' . $response->status());
            Log::error('GeminiService error', ['status' => $response->status(), 'body' => $response->body()]);
            return $this->err("Gemini API: {$msg}");
        }

        $raw = $response->json('candidates.0.content.parts.0.text');
        if (!$raw) {
            return $this->err('Gemini returned an empty response');
        }

        // Strip markdown code fences if present
        $json = preg_replace('/^```(?:json)?\s*|\s*```$/s', '', trim($raw));
        $parsed = json_decode($json, true);

        if (!is_array($parsed)) {
            Log::warning('GeminiService: could not parse JSON', ['raw' => $raw]);
            return $this->err('Could not parse AI response as JSON');
        }

        return [
            'answer'     => (string) ($parsed['answer']     ?? ''),
            'chart_type' => (string) ($parsed['chart_type'] ?? 'none'),
            'chart_data' => is_array($parsed['chart_data'] ?? null) ? $parsed['chart_data'] : null,
            'highlights' => is_array($parsed['highlights']  ?? null) ? $parsed['highlights'] : [],
            'error'      => null,
        ];
    }

    private function systemPrompt(): string
    {
        return <<<PROMPT
You are an expert retail analytics AI assistant for an Uzbekistan-based retail POS system.
Currency: UZS (Uzbek Som). All monetary amounts are in UZS.

Database context:
- receipts: POS transactions. type="Продажа" adds revenue; type="Возврат" subtracts.
  status="Закрыт" means valid/closed. Fields: cashier, shop, pos, total, date_close.
- items: product lines per receipt. status=true means active line.
  Fields: code, name, category, qty (decimal), price, total, discountTotal.
- payments: per-receipt payment splits. type: "Наличные"=cash, "Безналичные"=card.
- discounts: per-receipt or per-item discounts.

Rules:
1. Respond in the SAME LANGUAGE as the user's question (Uzbek, Russian, or English).
2. Be specific: cite actual numbers from the data. Format large numbers with spaces (1 234 567).
3. Return ONLY valid JSON in this exact schema (no extra keys, no markdown):
{
  "answer": "2-4 paragraph analysis with specific numbers and context",
  "chart_type": "bar" | "line" | "pie" | "doughnut" | "none",
  "chart_data": {
    "labels": ["label1", "label2"],
    "datasets": [{"label": "Name", "data": [1.0, 2.0]}]
  },
  "highlights": ["Key insight with emoji", "Key insight", "Key insight"]
}
4. chart_type="none" and chart_data=null when a chart adds no value.
5. For bar/line: one dataset per series. For pie/doughnut: one dataset with N values.
6. Keep chart labels short (max 20 chars). Round floats to 2 decimal places in data arrays.
7. highlights: 2-4 bullet points, each starting with a relevant emoji.
PROMPT;
    }

    private function userPrompt(string $question, array $data): string
    {
        // Keep data payload compact
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return "User question: {$question}\n\nData from database:\n{$json}";
    }

    private function err(string $msg): array
    {
        return [
            'answer'     => $msg,
            'chart_type' => 'none',
            'chart_data' => null,
            'highlights' => [],
            'error'      => $msg,
        ];
    }
}
