<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\DbRestore\Traits;

use Callcocam\DbRestore\Models\Connection;
use Callcocam\DbRestore\Helpers\RestoreHelper;
use Callcocam\DbRestore\Models\Column;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

trait WithColumns
{
    public function columns()
    {
        $columns = $this->morphMany(Column::class, 'columnable');
        if (!$columns->count()) {
            if (!$this->connectionFrom)
                $this->connectionFrom = $this->restore->connectionFrom;
            if (!$this->connectionTo) {
                $this->connectionTo = $this->restore->connectionTo;
            }
            return  $this->getColumnOptions($this);
        }
        return $columns;
    }


    public function getColumnOptions($record)
    {
        $columnsFrom = $this->getColumns($record->connectionFrom, $record->table_from);
        $columnsTo = $this->getColumns($record->connectionTo, $record->table_to, 'to');
        foreach ($columnsFrom as $key => $column) {
            if (isset($columnsTo[$key])) {
                $record->create([
                    'relation_id' => null,
                    'column_from' => $column,
                    'column_to' => $column,
                    'default_value' => null,
                    'type' => 'string',
                    "status" => "published",
                ]);
            }
        }
        return $this->morphMany(Column::class, 'columnable');
    }

    protected function getDataBases($schema)
    {
        $db = DB::connection($schema);

        $tables = $db->getDoctrineSchemaManager()->listDatabases();

        return array_combine($tables, $tables);
    }

    protected function getColumns(Connection $connection, $from_table, $prefix = 'from')
    {
        $from_database = $connection->database;

        return Cache::rememberForever("{$prefix}-{$connection}-{$from_database}-columns-{$from_table}", function () use ($connection, $from_database, $from_table) {
            $db = DB::connection(RestoreHelper::getConnectionCloneOptions($connection));

            $whitelist = config('restore.tables.whitelist', []);
            $blacklist = config('restore.tables.blacklist', ['migrations']);

            $whitelistString = Str::replace('*', '.*', implode('|', $whitelist));
            $whitelistString = "($whitelistString)$";
            $blacklistString = Str::replace('*', '.*', implode('|', $blacklist));
            $blacklistString = "($blacklistString)$";

            $query = $db
                ->query()
                ->select(['ORDINAL_POSITION as position',   'COLUMN_NAME as field'])
                ->from('INFORMATION_SCHEMA.COLUMNS')
                ->where('TABLE_NAME', $from_table)
                ->where('TABLE_SCHEMA', $from_database)
                ->whereNotIn('TABLE_SCHEMA', ['testing', 'information_schema', 'mysql', 'sys', 'performance_schema'])
                ->where('TABLE_NAME', 'NOT REGEXP', $blacklistString)
                ->orderBy('TABLE_NAME');

            $columns = $query->orderBy('position')->get();

            return $columns->pluck('field', 'field')->toArray();
        });
    }
}
