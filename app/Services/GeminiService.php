<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GeminiService
{
    protected $apiKey;
    protected $baseUrl;

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY');
        $this->baseUrl = env('GEMINI_API_URL');
    }

    public function generateResponse($prompt)
    {
        $payload = [
            "contents" => [
                [
                    "parts" => [
                        ["text" => $prompt]
                    ]
                ]
            ]
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json'
        ])->post($this->baseUrl . '?key=' . $this->apiKey, $payload);

        if ($response->successful()) {
            $candidates = $response->json()['candidates'] ?? [];
            if (!empty($candidates) && isset($candidates[0]['content']['parts'][0]['text'])) {
                return $candidates[0]['content']['parts'][0]['text'];
            }
    
            return 'No content text available';
        } else {
            throw new \Exception('Failed to communicate with Gemini API: ' . $response->body());
        }
    }
}
