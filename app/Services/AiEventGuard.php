<?php

namespace App\Services;

use App\Models\EventCategory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Client\ConnectionException;

class AiEventGuard
{
    public function check(string $text, ?string $mediaPublicPath = null): array
    {
        $categories = EventCategory::query()->get(['category_id', 'name']);

        $categoryList = $categories
            ->map(fn ($c) => "{$c->category_id}: {$c->name}")
            ->implode("\n");

        // ---------- build image input ----------
        $imagePart = null;

        if ($mediaPublicPath) {
            $fullPath = Storage::disk('public')->path($mediaPublicPath);

            if (!file_exists($fullPath)) {
                return [
                    "related" => false,
                    "category_id" => null,
                    "reason" => "file_missing",
                ];
            }

            $mime = mime_content_type($fullPath) ?: 'image/jpeg';
            $b64  = base64_encode(file_get_contents($fullPath));
            $dataUrl = "data:$mime;base64,$b64";

            // ✅ correct type for Responses API
            $imagePart = [
                "type" => "input_image",
                "image_url" => $dataUrl,
            ];
        }

        $prompt = <<<TEXT
You are a strict classifier for an events platform.

Allowed categories (ID: NAME):
$categoryList

Task:
- Decide if the provided TEXT and/or IMAGE belongs clearly to exactly ONE category above.
- If yes: related=true and set category_id to the matching ID.
- If unsure or not matching: related=false and category_id=null.

Return ONLY JSON (no markdown, no extra text):
{"related":true|false,"category_id":number|null,"reason":"short"}

User content:
$text
TEXT;

        $contentParts = array_values(array_filter([
            ["type" => "input_text", "text" => $prompt],
            $imagePart,
        ]));

        $payload = [
            "model" => "gpt-4o-mini",
            "temperature" => 0,
            "input" => [
                [
                    "role" => "user",
                    "content" => $contentParts,
                ],
            ],
        ];

        try {
            $resp = Http::withToken(config('services.openai.key'))
                ->acceptJson()
                ->timeout(60)
                ->connectTimeout(30)
                ->post('https://api.openai.com/v1/responses', $payload);
        } catch (ConnectionException $e) {
            Log::error('AI Guard: connection error', ['err' => $e->getMessage()]);
            return ["related" => false, "category_id" => null, "reason" => "ai_unreachable"];
        }

        if (!$resp->ok()) {
            Log::error('AI Guard: HTTP error', [
                'status' => $resp->status(),
                'body' => $resp->body(),
            ]);

            return [
                "related" => false,
                "category_id" => null,
                "reason" => "ai_http_error",
                "http_status" => $resp->status(),
                "http_body" => substr($resp->body(), 0, 800), // keep logs small
            ];
        }

        $json = $resp->json();
        if (!is_array($json)) {
            Log::error('AI Guard: non-json body', ['body' => $resp->body()]);
            return ["related" => false, "category_id" => null, "reason" => "ai_bad_json"];
        }

        // ---------- Extract AI text robustly ----------
        // Responses output is usually:
        // output[0].content[0].type = "output_text"
        // output[0].content[0].text = "..."
        $outText = null;

        $outputArr = data_get($json, 'output', []);
        if (is_array($outputArr)) {
            foreach ($outputArr as $outItem) {
                $content = $outItem['content'] ?? [];
                if (!is_array($content)) continue;

                foreach ($content as $part) {
                    if (($part['type'] ?? null) === 'output_text' && isset($part['text'])) {
                        $outText = $part['text'];
                        break 2;
                    }
                }
            }
        }

        if (!is_string($outText) || trim($outText) === '') {
            Log::error('AI Guard: missing output_text', ['json' => $json]);
            return ["related" => false, "category_id" => null, "reason" => "ai_missing_output"];
        }

        $data = json_decode($outText, true);

        if (!is_array($data) || !array_key_exists('related', $data)) {
            Log::error('AI Guard: invalid JSON format', ['outText' => $outText]);
            return ["related" => false, "category_id" => null, "reason" => "bad_ai_format"];
        }

        return [
            "related" => (bool)($data["related"] ?? false),
            "category_id" => $data["category_id"] ?? null,
            "reason" => (string)($data["reason"] ?? ""),
        ];
    }
}
