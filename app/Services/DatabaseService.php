<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class DatabaseService
{
    protected $tables = [];

    public function loadDatabaseSchema()
    {
        $this->tables = [];
        $database = config('database.connections.mysql.database');

        $tables = DB::select("SHOW TABLES");
        foreach ($tables as $table) {
            $tableName = $table->{"Tables_in_$database"};
            $columns = DB::select("SHOW COLUMNS FROM $tableName");
            $this->tables[$tableName] = array_map(function ($column) {
                return $column->Field;
            }, $columns);
        }
    }

    public function getTables()
    {
        return $this->tables;
    }
}
