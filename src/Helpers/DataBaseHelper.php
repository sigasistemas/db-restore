<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\DbRestore\Helpers;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DataBaseHelper
{

    public static function getTables($connection)
    {
        $tables = [];
        $tables = DB::connection($connection)->getDoctrineSchemaManager()->listTableNames();
        return array_combine($tables, $tables);
    }

    public static function getColumns($connection, $table)
    {
        $db = DB::connection($connection);

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
            ->where('TABLE_NAME', $table)
            // ->where('TABLE_SCHEMA', $database)
            ->whereNotIn('TABLE_SCHEMA', ['testing', 'information_schema', 'mysql', 'sys', 'performance_schema'])
            ->where('TABLE_NAME', 'NOT REGEXP', $blacklistString)
            ->orderBy('TABLE_NAME');

        $columns = $query->orderBy('position')->get();

        return $columns->pluck('field', 'field')->toArray();
    }

    public static function getColumnsOptions($connection, $database, $table, $prefix = 'from')
    {
        return Cache::rememberForever("{$prefix}-{$connection}-columns-{$table}", function () use ($connection, $database, $table) {
            $db = DB::connection($connection);

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
                ->where('TABLE_NAME', $table)
                ->where('TABLE_SCHEMA', $database)
                ->whereNotIn('TABLE_SCHEMA', ['testing', 'information_schema', 'mysql', 'sys', 'performance_schema'])
                ->where('TABLE_NAME', 'NOT REGEXP', $blacklistString)
                ->orderBy('TABLE_NAME');

            $columns = $query->orderBy('position')->get();

            return $columns->pluck('field', 'field')->toArray();
        });
    }
}
