<?php


/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\DbRestore\Forms\Components;

use Callcocam\DbRestore\Helpers\RestoreHelper;
use Callcocam\DbRestore\Models\AbstractModelRestore;
use Callcocam\DbRestore\Models\Connection;
use Callcocam\DbRestore\Traits\HasTraduction;
use Filament\Forms\Components\Select;
use Filament\Forms\Get;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Mpdf\Tag\A;

class SelectColumnField extends Select
{
    use HasTraduction;


    public static function makeFromOptions(string $name, AbstractModelRestore | null $record = null, $parent = null, $label = null): static
    {
        $static = app(static::class, ['name' => $name]);
        $static->configure()
            ->label($static->getTraductionFormLabel($label ?? $name))
            ->placeholder($static->getTraductionFormPlaceholder($label ?? $name))
            ->options(function (Get $get) use ($record, $parent, $static) {
                if ($record->connectionFrom)
                    return $static->getColumnsOptions($record->connectionFrom, $get($parent));
                return [];
            });

        return $static;
    }

    public static function makeToOptions(string $name, AbstractModelRestore | null $record = null, $parent = null, $label = null): static
    {
        $static = app(static::class, ['name' => $name]);
        $static->configure()
            ->label($static->getTraductionFormLabel($label ?? $name))
            ->placeholder($static->getTraductionFormPlaceholder($label ?? $name))
            ->options(function (Get $get) use ($record, $parent, $static) {
                if ($record->connectionTo) {
                    if ($parent instanceof AbstractModelRestore) {
                        return $static->getColumnsOptions($record->connectionTo, $parent->table_to);
                    }
                    return $static->getColumnsOptions($record->connectionTo, $get($parent));
                }
                return [];
            });

        return $static;
    }


    public static function make(string $name, AbstractModelRestore | null $record = null, $label = null): static
    {
        $static = app(static::class, ['name' => $name]);
        $static->configure()
            ->label($static->getTraductionFormLabel($label ?? $name))
            ->placeholder($static->getTraductionFormPlaceholder($label ?? $name));

        return $static;
    }

    protected function getColumnsOptions(Connection $connection, $from_table, $prefix = 'from')
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
