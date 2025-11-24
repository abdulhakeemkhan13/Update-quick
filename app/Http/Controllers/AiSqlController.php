<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AiSqlService;

class AiSqlController extends Controller
{
    protected $ai;

    public function __construct(AiSqlService $ai)
    {
        $this->ai = $ai;
    }

    public function index()
    {
        return view('admin.sqlQueryForm');
    }

    public function ask(Request $request)
    {
        $request->validate([
            'question' => 'required|string|max:500',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:1000'
        ]);

        try {
            $question = $request->input('question');
            $page = $request->input('page', 1);       // default page 1
            $perPage = $request->input('per_page', 100); // default 100 rows

            $response = $this->ai->queryWithAi($question, $page, $perPage);

            // Handle entire database dump
            if (isset($response['mode']) && $response['mode'] === 'entire_database') {
                return response()->json([
                    'success' => true,
                    'mode' => 'entire_database',
                    'tables' => $response['tables'],
                    'message' => 'Database dump (paginated) returned successfully'
                ]);
            }

            // Handle normal AI query
            return response()->json([
                'success' => true,
                'sql_query' => $response['sql'] ?? null,
                'results' => $response['results'] ?? null,
                'message' => 'Query processed successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error processing your query: ' . $e->getMessage()
            ], 500);
        }
    }
}
