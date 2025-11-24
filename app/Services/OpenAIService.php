<?php

namespace App\Services;

use OpenAI;
use OpenAI\Client;

class OpenAIService
{
    protected $client;

    public function __construct()
    {
        $this->client = OpenAI::client(env('OPENAI_API_KEY'));
    }
    public function generateResponse($prompt)
    {
        $response = $this->client->chat()->create([
            'model' => 'gpt-4o-mini', 
            'messages' => [
                ['role' => 'system', 'content' => 'You are an assistant that interprets user prompts to extract information from a database schema.'],
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        return $response['choices'][0]['message']['content'];
    }
}
