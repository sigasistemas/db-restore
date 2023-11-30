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

trait WithTables
{
    public function getTablesOptions($connection, $prefix = 'from')
    {
        if ($connection instanceof Connection) {
            return $this->getTables($connection, $prefix);
        } elseif (is_string($connection)) {
            $connection =  Cache::rememberForever($connection, function () use ($connection) {
                return ;
            });
            if ($connection) {
                return $this->getTables($connection, $prefix);
            }
        }
        return [];
    }

    protected function getTables(Connection $connection, $prefix = 'from')
    {
        $connectionName = RestoreHelper::getConnectionCloneOptions($connection);
        
        $db = DB::connection($connectionName);

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
}
