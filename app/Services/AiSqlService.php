<?php

namespace App\Services;

class AiSqlService
{
    public function __construct()
    {
        //
    }

    public function ask($question)
    {
        return "AI SQL service is not implemented yet.";
    }

    public function queryWithAi($question, $page = 1, $perPage = 100)
    {
        return [
            'sql' => 'SELECT * FROM table',
            'results' => []
        ];
    }
}