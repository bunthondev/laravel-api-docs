<?php

namespace Puchan\LaravelApiDocs\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SchemaReader
{
    /**
     * Get configured database schemas as array
     */
    private function getSchemas(): array
    {
        $schema = config('api-docs.database.schema', 'public');

        // Support both string and array configuration
        return is_array($schema) ? $schema : [$schema];
    }

    /**
     * Get table schema information
     */
    public function getTableSchema(string $tableName): array
    {
        if (!Schema::hasTable($tableName)) {
            return [];
        }

        $columns = $this->getColumns($tableName);
        $indexes = $this->getIndexes($tableName);
        $foreignKeys = $this->getForeignKeys($tableName);

        return [
            'table' => $tableName,
            'columns' => $columns,
            'indexes' => $indexes,
            'foreign_keys' => $foreignKeys,
        ];
    }

    /**
     * Get all columns for a table
     */
    private function getColumns(string $tableName): array
    {
        $schemas = $this->getSchemas();
        $placeholders = implode(',', array_fill(0, count($schemas), '?'));

        $columns = DB::select("
            SELECT
                column_name,
                data_type,
                is_nullable,
                column_default,
                character_maximum_length,
                numeric_precision,
                numeric_scale
            FROM information_schema.columns
            WHERE table_name = ?
            AND table_schema IN ($placeholders)
            ORDER BY ordinal_position
        ", array_merge([$tableName], $schemas));

        return array_map(function ($column) {
            return [
                'name' => $column->column_name,
                'type' => $this->mapDataType($column->data_type),
                'nullable' => $column->is_nullable === 'YES',
                'default' => $column->column_default,
                'max_length' => $column->character_maximum_length,
                'precision' => $column->numeric_precision,
                'scale' => $column->numeric_scale,
                'required' => $column->is_nullable === 'NO' && $column->column_default === null,
            ];
        }, $columns);
    }

    /**
     * Get indexes for a table
     */
    private function getIndexes(string $tableName): array
    {
        $schemas = $this->getSchemas();
        $placeholders = implode(',', array_fill(0, count($schemas), '?'));

        $indexes = DB::select("
            SELECT
                indexname as name,
                indexdef as definition
            FROM pg_indexes
            WHERE tablename = ?
            AND schemaname IN ($placeholders)
        ", array_merge([$tableName], $schemas));

        return array_map(function ($index) {
            return [
                'name' => $index->name,
                'definition' => $index->definition,
                'unique' => str_contains(strtoupper($index->definition), 'UNIQUE'),
            ];
        }, $indexes);
    }

    /**
     * Get foreign keys for a table
     */
    private function getForeignKeys(string $tableName): array
    {
        $schemas = $this->getSchemas();
        $placeholders = implode(',', array_fill(0, count($schemas), '?'));

        $foreignKeys = DB::select("
            SELECT
                kcu.column_name,
                ccu.table_name AS foreign_table_name,
                ccu.column_name AS foreign_column_name
            FROM information_schema.table_constraints AS tc
            JOIN information_schema.key_column_usage AS kcu
                ON tc.constraint_name = kcu.constraint_name
                AND tc.table_schema = kcu.table_schema
            JOIN information_schema.constraint_column_usage AS ccu
                ON ccu.constraint_name = tc.constraint_name
                AND ccu.table_schema = tc.table_schema
            WHERE tc.constraint_type = 'FOREIGN KEY'
                AND tc.table_name = ?
                AND tc.table_schema IN ($placeholders)
        ", array_merge([$tableName], $schemas));

        return array_map(function ($fk) {
            return [
                'column' => $fk->column_name,
                'references_table' => $fk->foreign_table_name,
                'references_column' => $fk->foreign_column_name,
            ];
        }, $foreignKeys);
    }

    /**
     * Map database data type to readable type
     */
    private function mapDataType(string $type): string
    {
        return match ($type) {
            'character varying', 'varchar', 'text' => 'string',
            'integer', 'bigint', 'smallint' => 'integer',
            'numeric', 'decimal', 'real', 'double precision' => 'number',
            'boolean' => 'boolean',
            'timestamp', 'timestamp without time zone', 'timestamp with time zone' => 'datetime',
            'date' => 'date',
            'time', 'time without time zone' => 'time',
            'json', 'jsonb' => 'json',
            'uuid' => 'uuid',
            default => $type,
        };
    }

    /**
     * Get model table name from controller
     */
    public function getModelTableFromController(string $controllerClass): ?string
    {
        try {
            // Try to find model from controller name
            $modelName = str_replace('Controller', '', class_basename($controllerClass));
            $modelClass = "App\\Models\\{$modelName}";

            if (class_exists($modelClass)) {
                $model = new $modelClass();
                return $model->getTable();
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
