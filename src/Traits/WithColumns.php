<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\DbRestore\Traits;

use Callcocam\DbRestore\Models\Connection; 
use Callcocam\DbRestore\Helpers\RestoreHelper;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

trait WithColumns
{

    protected function getDataBases($schema)
    {
        $db = DB::connection($schema);

        $tables = $db->getDoctrineSchemaManager()->listDatabases();

        return array_combine($tables, $tables);
    }


    protected function getTables(Connection $connection, $prefix = 'from')
    {

        $db = DB::connection(RestoreHelper::getConnectionCloneOptions($connection));

        $whitelist = config('restore.tables.whitelist', []);
        $blacklist = config('restore.tables.blacklist', ['migrations']);

        $whitelistString = Str::replace('*', '.*', implode('|', $whitelist));
        $whitelistString = "($whitelistString)$";
        $blacklistString = Str::replace('*', '.*', implode('|', $blacklist));
        $blacklistString = "($blacklistString)$";

        $query = $db
            ->query()
            ->select(['TABLE_NAME as name'])
            ->from('INFORMATION_SCHEMA.TABLES')
            ->where('TABLE_SCHEMA', $connection->database)
            ->where('TABLE_TYPE', 'BASE TABLE')
            ->where('TABLE_NAME', 'not regexp', $blacklistString)
            ->where('TABLE_NAME', 'regexp', $whitelistString)
            ->orderBy('TABLE_NAME');

        $tables = $query->get()->pluck('name', 'name')->toArray();


        return $tables;
    }



    protected function getColumns(Connection $connection,  $from_table, $prefix = 'from')
    {
        $from_database = $connection->database;
        return  Cache::rememberForever("{$prefix}-{$connection}-{$from_database}-columns-{$from_table}", function () use ($connection, $from_database, $from_table) {
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

            return  $columns->pluck('field', 'field')->toArray();
        });
    }
}
