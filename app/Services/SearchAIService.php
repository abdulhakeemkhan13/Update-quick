<?php

namespace App\Services;

use OpenAI;

class SearchAIService
{
    protected $client;
    protected $enabled = false;

    public function __construct()
    {
        $apiKey = env('OPENAI_API_KEY');
        if (!empty($apiKey)) {
            $this->client = OpenAI::client($apiKey);
            $this->enabled = true;
        }
    }

    /**
     * Check if AI service is available
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Check if query is complex enough to warrant AI processing
     */
    public function isComplexQuery(string $query): bool
    {
        // Simple queries don't need AI
        if (strlen($query) < 10) return false;
        
        // Check for question words or complex patterns
        $complexPatterns = [
            '/how much/i',
            '/how many/i',
            '/what is/i',
            '/who (owes|has|is)/i',
            '/show me (all|the|my)/i',
            '/total|sum|average|count/i',
            '/biggest|largest|highest|most/i',
            '/smallest|lowest|least/i',
            '/between .+ and/i',
            '/from .+ to/i',
            '/last (week|month|year|quarter)/i',
            '/this (week|month|year|quarter)/i',
            '/unpaid|overdue|pending|due/i',
        ];

        foreach ($complexPatterns as $pattern) {
            if (preg_match($pattern, $query)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Parse natural language query using OpenAI
     */
    public function parseQuery(string $query): array
    {
        if (!$this->enabled) {
            return ['type' => 'search', 'query' => $query];
        }

        try {
            $systemPrompt = <<<PROMPT
You are a search query parser for an accounting/invoicing application. Parse the user's natural language query and return a JSON object with the following structure:

{
    "type": "navigation|aggregate|filter|top_n|search",
    "action": "specific action to take",
    "entity": "invoice|bill|customer|vendor|product|transaction",
    "filters": {
        "status": "paid|unpaid|overdue|draft|null",
        "date_range": "today|yesterday|this_week|last_week|this_month|last_month|this_year|2024|null",
        "amount_operator": "gt|lt|eq|null",
        "amount_value": number or null,
        "name": "search term or null"
    },
    "aggregate": "sum|count|average|max|min|null",
    "aggregate_field": "amount|total|balance|null",
    "limit": number or null,
    "order_by": "amount|date|name|null",
    "order_dir": "asc|desc|null"
}

Examples:
- "How much revenue this month?" -> {"type":"aggregate","action":"calculate","entity":"invoice","aggregate":"sum","aggregate_field":"total","filters":{"status":"paid","date_range":"this_month"}}
- "Who owes me the most?" -> {"type":"top_n","action":"find","entity":"customer","order_by":"balance","order_dir":"desc","limit":5}
- "Show unpaid invoices" -> {"type":"filter","action":"list","entity":"invoice","filters":{"status":"unpaid"}}
- "Profile" -> {"type":"navigation","action":"redirect","target":"profile"}

Return ONLY valid JSON, no explanation.
PROMPT;

            $response = $this->client->chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $query],
                ],
                'temperature' => 0.1,
                'max_tokens' => 300,
            ]);

            $content = $response['choices'][0]['message']['content'];
            
            // Clean up response (remove markdown code blocks if present)
            $content = preg_replace('/```json\s*/', '', $content);
            $content = preg_replace('/```\s*/', '', $content);
            $content = trim($content);
            
            $parsed = json_decode($content, true);
            
            if (json_last_error() === JSON_ERROR_NONE && is_array($parsed)) {
                return $parsed;
            }

            // Fallback to simple search if parsing fails
            return ['type' => 'search', 'query' => $query];

        } catch (\Throwable $e) {
            \Log::error('SearchAI Error: ' . $e->getMessage());
            return ['type' => 'search', 'query' => $query];
        }
    }

    /**
     * Execute an aggregate query based on parsed intent
     */
    public function executeAggregate(array $intent): ?array
    {
        $entity = $intent['entity'] ?? 'invoice';
        $aggregate = $intent['aggregate'] ?? 'sum';
        $field = $intent['aggregate_field'] ?? 'total';
        $filters = $intent['filters'] ?? [];

        try {
            $model = $this->getModelClass($entity);
            if (!$model) return null;

            $query = $model::query();
            $query = $this->applyFilters($query, $filters, $entity);

            switch ($aggregate) {
                case 'sum':
                    $result = $query->sum($field);
                    break;
                case 'count':
                    $result = $query->count();
                    break;
                case 'average':
                    $result = $query->avg($field);
                    break;
                case 'max':
                    $result = $query->max($field);
                    break;
                case 'min':
                    $result = $query->min($field);
                    break;
                default:
                    return null;
            }

            return [
                'type' => 'Answer',
                'icon' => 'ti ti-calculator',
                'label' => ucfirst($aggregate) . ' of ' . ucfirst($entity) . ' ' . ucfirst($field),
                'sub_label' => \Auth::user()->priceFormat($result),
                'url' => '#',
                'is_answer' => true,
            ];

        } catch (\Throwable $e) {
            \Log::error('Aggregate Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get model class from entity name
     */
    protected function getModelClass(string $entity): ?string
    {
        $map = [
            'invoice' => \App\Models\Invoice::class,
            'bill' => \App\Models\Bill::class,
            'customer' => \App\Models\Customer::class,
            'vendor' => \App\Models\Vender::class,
            'product' => \App\Models\ProductService::class,
        ];

        return $map[$entity] ?? null;
    }

    /**
     * Apply filters to a query builder
     */
    protected function applyFilters($query, array $filters, string $entity)
    {
        $user = \Auth::user();
        $query->where('created_by', $user->creatorId());

        // Date range filter
        if (!empty($filters['date_range'])) {
            $dateField = $entity === 'invoice' ? 'issue_date' : ($entity === 'bill' ? 'bill_date' : 'created_at');
            
            switch ($filters['date_range']) {
                case 'today':
                    $query->whereDate($dateField, now()->toDateString());
                    break;
                case 'yesterday':
                    $query->whereDate($dateField, now()->subDay()->toDateString());
                    break;
                case 'this_week':
                    $query->whereBetween($dateField, [now()->startOfWeek(), now()->endOfWeek()]);
                    break;
                case 'last_week':
                    $query->whereBetween($dateField, [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()]);
                    break;
                case 'this_month':
                    $query->whereMonth($dateField, now()->month)->whereYear($dateField, now()->year);
                    break;
                case 'last_month':
                    $query->whereMonth($dateField, now()->subMonth()->month)->whereYear($dateField, now()->subMonth()->year);
                    break;
                case 'this_year':
                    $query->whereYear($dateField, now()->year);
                    break;
                default:
                    // Check if it's a year like "2024"
                    if (preg_match('/^\d{4}$/', $filters['date_range'])) {
                        $query->whereYear($dateField, $filters['date_range']);
                    }
            }
        }

        // Status filter
        if (!empty($filters['status'])) {
            switch ($filters['status']) {
                case 'unpaid':
                    $query->whereIn('status', [0, 1, 2]); // Draft, Sent, Unpaid
                    break;
                case 'paid':
                    $query->where('status', 4);
                    break;
                case 'overdue':
                    $query->where('status', '!=', 4)->where('due_date', '<', now());
                    break;
            }
        }

        // Amount filter
        if (!empty($filters['amount_operator']) && isset($filters['amount_value'])) {
            $op = $filters['amount_operator'];
            $val = $filters['amount_value'];
            
            if ($op === 'gt') $query->where('total', '>', $val);
            elseif ($op === 'lt') $query->where('total', '<', $val);
            elseif ($op === 'eq') $query->where('total', $val);
        }

        return $query;
    }
}
