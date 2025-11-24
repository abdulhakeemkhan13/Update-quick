<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\GeminiService;
use DB;
use Hash;
use Illuminate\Http\Request;
use Log;
use App\Services\DatabaseService;
use App\Services\OpenAIService;
use Route;

class ciaController extends Controller
{
    protected $databaseService;
    protected $openAIService;
    protected $geminiService;

    public function __construct(DatabaseService $databaseService, OpenAIService $openAIService, GeminiService $geminiService)
    {
        $this->databaseService = $databaseService;
        $this->openAIService = $openAIService;
        $this->geminiService = $geminiService;
    }

    public function performaction(Request $request)
    {
        $prompt = $request->input('prompt');
        // dd($prompt);
        $isopenAction = $this->openAction($prompt);
        $this->databaseService->loadDatabaseSchema();
        $schemaInfo = json_encode($this->databaseService->getTables());

        if ($isopenAction) {
            return $this->handleCommandWithAI($prompt);
        }

        $queryPrompt = "Here is the database schema:\n" . $schemaInfo . "\n\n"
            . "Based on this schema, generate a MySQL query for the following command: '$prompt'. "
            . "Note:if prompt dosn't consist data values then you can put your dumy data based on column    entries."
            . "Please return only the SQL query without any additional explanations.";
        // $openai = $this->openAIService->generateResponse($queryPrompt);
        // $queries = $this->extractSqlQueries($openai);
        // dd($queryPrompt);
        $geminiResponse = $this->geminiService->generateResponse($queryPrompt);
        $queries = $this->extractSqlQueries($geminiResponse);
        // dd($geminiResponse,$queries);
        $results = [];
        DB::beginTransaction();

        try {
            foreach ($queries as $query) {
                if ($this->validateSqlQuery($query)) {
                    $queryType = $this->getSqlQueryType($query);

                    if ($queryType == 'INSERT') {
                        DB::statement($query);
                        $userId = DB::getPdo()->lastInsertId();
                        $userData = DB::table('users')->where('id', $userId)->first();
                        $results[] = ['query' => $query, 'status' => 'executed', 'data' => $userData];
                    } elseif ($queryType == 'UPDATE') {
                        DB::statement($query);
                        $conditions = $this->extractUserIdentifiersFromQuery($query);
                        $updatedUserData = DB::table('users')->where($conditions)->get();
                        $results[] = ['query' => $query, 'status' => 'executed', 'data' => $updatedUserData];
                    } elseif ($queryType == 'SELECT') {
                        $selectResults = DB::select($query);
                        $results[] = ['query' => $query, 'status' => 'executed', 'data' => $selectResults];
                    } elseif ($queryType == 'DELETE') {
                        $selectResults = DB::select($query);
                        $results[] = ['query' => $query, 'status' => 'executed', 'data' => $selectResults];
                    } else {
                        $results[] = ['query' => $query, 'status' => 'Unknown query type'];
                    }
                } else {
                    return response()->json(['success' => false, 'message' => 'Invalid SQL query: ' . $query], 400);
                }
            }

            DB::commit();
            return response()->json(['success' => true, 'results' => $results]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('SQL execution error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Detect if the prompt is a predictive request based on keywords.
     *
     * @param string $prompt
     * @return bool
     */
    protected function isPredictiveRequest($prompt)
    {
        $predictiveKeywords = ['predict', 'forecast', 'estimate', 'next year', 'upcoming', 'suggest', 'Tell me'];
        foreach ($predictiveKeywords as $keyword) {
            if (stripos($prompt, $keyword) !== false) {
                return true;
            }
        }
        return false;
    }
    protected function openAction($prompt)
    {
        $predictiveKeywords = ['open', 'launch', 'start', 'access', 'activate', 'run', 'initiate','show','display'];
        foreach ($predictiveKeywords as $keyword) {
            if (stripos($prompt, $keyword) !== false) {
                return true;
            }
        }
        return false;
    }
    public function dynamicreporting(Request $request)
    {
        $prompt = $request->input('prompt');
        $isPredictiveRequest = $this->isPredictiveRequest($prompt);
        $this->databaseService->loadDatabaseSchema();
        $schemaInfo = json_encode($this->databaseService->getTables());

        if ($isPredictiveRequest) {
            $predictionPrompt = "Here is the database schema:\n" . $schemaInfo . "\n\n"
                . "Based on this schema, provide a predictive analysis for the following command: '$prompt'.Note : just preidict don't display the sql query or any extra information.";
            $predictionResponse = $this->geminiService->generateResponse($predictionPrompt);
            return response()->json(['success' => true, 'prediction' => $predictionResponse]);
        }

        $queryPrompt = "Here is the database schema:\n" . $schemaInfo . "\n\n"
            . "Based on this schema, generate a MySQL query for the following command: '$prompt'. "
            . "Please return only the SQL query without any additional explanations. and don't include any extra database columns that u think no need to display like id's column of forign table if u eligible then relate them and display their text but don't integer id's. ";
        // $openai = $this->openAIService->generateResponse($queryPrompt);
        // $queries = $this->extractSqlQueries($openai);
        $geminiResponse = $this->geminiService->generateResponse($queryPrompt);
        $queries = $this->extractSqlQueries($geminiResponse);

        $results = [];
        DB::beginTransaction();

        try {
            foreach ($queries as $query) {
                if ($this->validateSqlQuery($query)) {
                    $queryType = $this->getSqlQueryType($query);

                    if ($queryType == 'INSERT') {
                        DB::statement($query);
                        $userId = DB::getPdo()->lastInsertId();
                        $userData = DB::table('users')->where('id', $userId)->first();
                        $results[] = ['query' => $query, 'status' => 'executed', 'data' => $userData];
                    } elseif ($queryType == 'UPDATE') {
                        DB::statement($query);
                        $conditions = $this->extractUserIdentifiersFromQuery($query);
                        $updatedUserData = DB::table('users')->where($conditions)->get();
                        $results[] = ['query' => $query, 'status' => 'executed', 'data' => $updatedUserData];
                    } elseif ($queryType == 'SELECT') {
                        $selectResults = DB::select($query);
                        $results[] = ['query' => $query, 'status' => 'executed', 'data' => $selectResults];
                    } else {
                        $results[] = ['query' => $query, 'status' => 'Unknown query type'];
                    }
                } else {
                    return response()->json(['success' => false, 'message' => 'Invalid SQL query: ' . $query], 400);
                }
            }

            DB::commit();
            return response()->json(['success' => true, 'results' => $results]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('SQL execution error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    private function extractUserIdentifiersFromQuery($query)
    {
        $conditions = [];

        // Match fields and values in the WHERE clause
        if (preg_match_all("/WHERE\s+(.*?)(?=AND|$)/i", $query, $matches)) {
            $whereClause = $matches[1][0];
            preg_match_all("/(\w+)\s*=\s*'([^']+)'/i", $whereClause, $fieldMatches, PREG_SET_ORDER);

            foreach ($fieldMatches as $match) {
                $conditions[$match[1]] = $match[2]; // Add each condition as [field => value]
            }
        }
        return $conditions;
    }
    public function reportingview()
    {
        return view('cia.speechtotext');
    }
    private function extractSqlQueries($input)
    {
        // Regular expression to match SQL commands
        // This regex looks for anything that looks like an SQL command
        preg_match_all('/(?<=\n|^)(\s*(?:CREATE|GRANT|FLUSH|INSERT|UPDATE|DELETE|SELECT|ALTER|DROP)[\s\S]*?;)/i', $input, $matches);
        return $matches[0];
    }
    private function getSqlQueryType($query)
    {
        $query = strtolower(trim($query)); // Normalize the query for easier checking
        if (strpos($query, 'update') === 0) {
            return 'UPDATE';
        } elseif (strpos($query, 'insert') === 0) {
            return 'INSERT';
        } elseif (strpos($query, 'delete') === 0) {
            return 'DELETE';
        } elseif (strpos($query, 'select') === 0) {
            // You could also check if it has the conditional part
            if (strpos($query, 'if row_count() > 0') !== false) {
                return 'SELECT_WITH_CONDITIONAL'; // Custom type for your logic
            }
            return 'SELECT';
        }
        return 'UNKNOWN';
    }

    private function validateSqlQuery($query)
    {
        return !empty(trim($query));
    }
    function getAllRoutes()
    {
        return collect(Route::getRoutes())->map(function ($route) {
            return [
                'name' => $route->getName(),
                'uri' => $route->uri(),
                'methods' => $route->methods(),
            ];
        });
    }
    function generateAIPrompt($command)
    {
        $routes = $this->getAllRoutes();
        $prompt = "The user issued the following command: '{$command}'.\n";
        $prompt .= "Here is the list of available routes with their URIs and names:\n";

        foreach ($routes as $route) {
            $prompt .= "URI: {$route['uri']}, Name: {$route['name']}\n";
        }
        $prompt .= "\nInstructions:\n";
        $prompt .= "1. Analyze the user's command and identify keywords (e.g., 'open', 'view', 'list').\n";
        $prompt .= "2. Match the keywords to the available routes by identifying common patterns or modules (e.g., 'HRM workflow' should map to 'workflow_hrm').\n";
        $prompt .= "3. Use the following examples as guidelines:\n";
        $prompt .= "   - Command: 'open HRM workflow' -> Route: 'workflow_hrm'\n";
        $prompt .= "   - Command: 'view project workflow' -> Route: 'workflow_project'\n";
        $prompt .= "   - Command: 'list users' -> Route: 'users.index'\n";
        $prompt .= "   - Command: 'create user' -> Route: 'users.create'\n";
        $prompt .= "   - Command: 'edit account workflow' -> Route: 'workflow_account'\n";
        $prompt .= "4. If no exact match is found, choose the most relevant route by URI or name that aligns with the user's intent.\n";
        $prompt .= "5. Return only the route name (e.g., 'users.index', 'workflow_hrm'). Do not include any additional text or explanation.\n";
        return $prompt;
    }


    function getRouteFromAI($command)
    {
        $prompt = $this->generateAIPrompt($command);
        // dd($prompt);
        $geminiResponse = $this->geminiService->generateResponse($prompt);
        return $geminiResponse ?? null;
    }

    function matchRoute($suggestedRoute, $routes)
    {
        $cleanedRoute = strtolower(trim(strip_tags($suggestedRoute)));
        // dd($cleanedRoute);
        return $routes->first(function ($route) use ($cleanedRoute) {
            $routeName = strtolower(trim($route['name'] ?? ''));
            $routeUri = strtolower(trim($route['uri']));

            return str_contains($routeName, $cleanedRoute) || str_contains($routeUri, $cleanedRoute);
        });
    }
    function extractRouteName($aiResponse)
    {
        if (preg_match('/`([^`]*)`/', $aiResponse, $matches)) {
            return strtolower(trim($matches[1]));
        }
        return strtolower(trim($aiResponse));
    }

    function handleCommandWithAI($command)
    {
        $aiResponse = $this->getRouteFromAI($command);
        $routes = $this->getAllRoutes();
        $suggestedRoute = $this->extractRouteName($aiResponse);
        $matchedRoute = $this->matchRoute($suggestedRoute, $routes);

        if ($matchedRoute) {
            $redirectUrl = !empty($matchedRoute['name'])
                ? route($matchedRoute['name'])
                : url($matchedRoute['uri']);

            return response()->json([
                'status' => 'success',
                'message' => 'Route matched successfully.',
                'redirectUrl' => $redirectUrl,
            ]);
        }
        return response()->json([
            'status' => 'error',
            'message' => 'No matching route found.',
        ], 404);
    }



    // public function transcribeAudio(Request $request)
    // {
    //     $file = $request->file('audio');
    //     $filePath = $file->store('audio', 'local'); // Save audio locally

    //     $speechClient = new SpeechClient([
    //         'credentials' => storage_path('path-to-google-credentials.json')
    //     ]);

    //     $translateClient = new TranslateClient([
    //         'key' => 'your-google-translate-api-key'
    //     ]);

    //     $audioContent = file_get_contents(storage_path("app/{$filePath}"));

    //     $config = [
    //         'encoding' => \Google\Cloud\Speech\V1\RecognitionConfig\AudioEncoding::LINEAR16,
    //         'languageCode' => 'auto' // 'auto' if using Whisper, specify if using Google STT
    //     ];
    //     $audio = ['content' => $audioContent];

    //     $response = $speechClient->recognize($config, $audio);
    //     $transcription = '';

    //     foreach ($response->getResults() as $result) {
    //         $transcription .= $result->getAlternatives()[0]->getTranscript();
    //     }

    //     $translatedText = $translateClient->translate($transcription, ['target' => 'en'])['text'];

    //     $speechClient->close();

    //     return response()->json(['translated_text' => $translatedText]);
    // }
}
