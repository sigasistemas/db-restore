<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\DbRestore\Helpers;

use Callcocam\DbRestore\Models\Connection;
use Callcocam\DbRestore\Models\Restore;
use DateTime;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder;

class RestoreHelper
{
    public static $restore;

    public static function validateDate($date, $format = 'Y-m-d H:i:s')
    {
        $d = DateTime::createFromFormat($format, $date);

        return $d && $d->format($format) === $date;
    }

    public static function getModelsOptions($search = '*.php')
    {
        $paths = config('db-restore.models.paths', [app_path('Models')]);
        $options = [];
        foreach ($paths as $path) {
            $finder = new Finder();
            $finder->files()->in($path)->name($search);
            foreach ($finder as $file) {
                $class = Str::of($file->getRelativePathname())->replace('.php', '')->replace('/', '\\')->prepend('App\\Models\\')->__toString();
                if (class_exists($class)) {
                    $options[$class] = $class;
                }
            }
        }

        return $options;
    }

    public static function getConnectionCloneOptions(Connection $connection)
    {

        $connectionName = $connection->slug;

        $newConnection = collect($connection->toArray())->only(['driver', 'host', 'port', 'database', 'username', 'password', 'charset'])->toArray();

        $connections = array_merge(config('database.connections.mysql'), $newConnection);

        Config::set(sprintf('database.connections.%s', $connectionName), $connections);

        return $connectionName;
    }

    public static function getColumsSchema($columns, $from_table, $column_name)
    {

        return collect($columns)->mapWithKeys(function ($column) use ($from_table, $column_name) {
            return [$column[$column_name] => [
                'columns' => sprintf('%s.%s', $from_table, $column[$column_name]),
                'relation_id' => $column['relation_id'],
                'relation' => $column['relation'],
                'restore_id' => $column['restore_id'],
                'column_from' => $column['column_from'],
                'column_to' => $column['column_to'],
                'default_value' => $column['default_value'],
                'type' => $column['type'],
            ]];
        })->toArray();
    }

    public static function getFromDatabaseRows(Restore $restore, $from_table, $filters = null)
    {
        $connection = $restore->connectionFrom;

        $from_connection = RestoreHelper::getConnectionCloneOptions($connection);

        $models = DB::connection($from_connection)
            ->table($from_table);

        if ($filters) {
            foreach ($filters as $filter) {
                $models->where($filter->column, $filter->operator, $filter->value);
            }
        }

        $rows = $models->get();

        return $rows->toArray();
    }

    public static function getValues($connectionName, $chunk, $column)
    {

        $column_from = data_get($column, 'column_from');
        $default_value = data_get($column, 'default_value');
        $type = data_get($column, 'type');
        $relation = data_get($column, 'relation');
        if (!empty($default_value)) {
            $data = $default_value;
        } else {

            if ($relation) {
                $val = data_get($chunk, $column_from);
                //Conexao da tabela de destino
                // $connectionName = $connectionName;
                //Nome da tabela de destino que vamos recuperar o dado
                $tableName = $relation->table_name;
                //Coluna da tabela de destino que vamos recuperar o dado
                //Geraralmente é o id, mas tambem pode ser o campo slug ou o campo que foi salvo o id da tabela de origem
                $columnToName = $relation->column_to;
                //Valor da coluna da tabela de origem que vamos usar para recuperar o dado
                $columnFromName = $relation->column_from;
                //Nome da coluna que vamos recuperar o dado
                $columnValue = $relation->column_value;
                //Se o valor da coluna da tabela de origem for igual ao valor da coluna da tabela de destino
                if (Cache::has(sprintf('_%s_%s', $column_from, $val))) {
                    $data = Cache::get(sprintf('_%s_%s', $column_from, $val));
                } else {
                    $data = DB::connection($connectionName)
                        ->table($tableName)->where($columnToName, $val)->value($columnValue);
                    Cache::forever(sprintf('_%s_%s', $column_from, $val), $data);
                }
                return  $data;
            } else {
                switch ($type) {
                    case 'date':
                        return  static::validateDate(data_get($chunk, $column_from)) ? data_get($chunk, $column_from) : $default_value;

                        break;
                    case 'datetime':
                        return  static::validateDate(data_get($chunk, $column_from)) ? data_get($chunk, $column_from) : $default_value;

                        break;
                    case 'time':
                        return  static::validateDate(data_get($chunk, $column_from)) ? data_get($chunk, $column_from) : $default_value;

                        break;
                    case 'timestamp':
                        return  static::validateDate(data_get($chunk, $column_from)) ? data_get($chunk, $column_from) : $default_value;

                        break;
                    case 'year':
                        return  static::validateDate(data_get($chunk, $column_from)) ? data_get($chunk, $column_from) : $default_value;

                        break;
                    case 'binary':
                        return  data_get($chunk, $column_from, $default_value);

                        break;
                    case 'boolean':
                        return  data_get($chunk, $column_from, $default_value);

                        break;
                    case 'char':
                        return  data_get($chunk, $column_from, $default_value);

                        break;
                    case 'text':
                        return  data_get($chunk, $column_from, $default_value);

                        break;
                    case 'json':
                        if ($data = data_get($chunk, $column_from, $default_value)) {
                            $data = explode(',', data_get($chunk, $column_from, $default_value));
                            return  json_encode($data);
                        } else {
                            return  null;
                        }

                        break;
                    case 'integer':
                        if (in_array($column_from, ['status'])) {
                            $status = data_get($chunk, $column_from, $default_value);
                            return  $status  ? 'published' : 'draft';
                        } else {
                            return  data_get($chunk, $column_from, $default_value);
                        }

                        break;
                    default:
                        return  data_get($chunk, $column_from, $default_value);

                        break;
                }
            }
        }

        return $data;
    }

    public static function afterGetChildresValues($record)
    {

        $childrens = $record->childrens;

        if ($childrens) {

            return $childrens->map(function ($children) use ($record) {

                $batch = Bus::batch([])->then(function (Batch $batch) {
                })->name($children->name)->dispatch();

                $columns = $children->columns;

                $from_table = $children->table_from;

                $from_columns = static::getColumsSchema($columns, $from_table, 'column_from');

                $filterList = $children->filters->filter(fn ($filter) => $filter->type == 'list')->all();

                $rows = static::getFromDatabaseRows($record, $from_table, $filterList);

                static::beforeRemoveFilters($children);

                $to_table = $children->table_to;

                $to_columns = static::getColumsSchema($columns, $to_table, 'column_to');

                $chunks = array_chunk($rows, 1000);

                foreach ($chunks as $chunk) {
                    $batch->add(new \Callcocam\DbRestore\Jobs\DbRestoreChidrenJob($children, $chunk, $to_columns, $from_columns));
                }
            });
        }
    }

    public static function beforeRemoveFilters($record)
    {
        if (!$record->filters) {
            return;
        }

        $filterDeletes = $record->filters->filter(fn ($filter) => $filter->type == 'delete')->all();

        if ($filterDeletes) {
            $connection = RestoreHelper::getConnectionCloneOptions($record->connectionTo);

            foreach ($filterDeletes as $filterDelete) {
                $query = DB::connection($connection)->table($record->table_to);
                $operator = $filterDelete->operator;
                switch ($operator):
                    case '=':
                        $query->where($filterDelete->column, $operator, $filterDelete->value);
                        break;
                    case '!=':
                        $query->where($filterDelete->column, $operator, $filterDelete->value);
                        break;
                    case '<':
                        $query->where($filterDelete->column, $operator, $filterDelete->value);
                        break;
                    case '<=':
                        $query->where($filterDelete->column, $operator, $filterDelete->value);
                        break;
                    case '>':
                        $query->where($filterDelete->column, $operator, $filterDelete->value);
                        break;
                    case '>=':
                        $query->where($filterDelete->column, $operator, $filterDelete->value);
                        break;
                    case 'like':
                        $query->where($filterDelete->column, $operator, "%{$filterDelete->value}%");
                        break;
                    case 'not like':
                        $query->where($filterDelete->column, $operator, "%{$filterDelete->value}%");
                        break;
                    case 'in':
                        $query->whereIn($filterDelete->column, $filterDelete->value);
                        break;
                    case 'not in':
                        $query->whereNotIn($filterDelete->column, $filterDelete->value);
                        break;
                    case 'between':
                        $query->whereBetween($filterDelete->column, $filterDelete->value);
                        break;
                    case 'not between':
                        $query->whereNotBetween($filterDelete->column, $filterDelete->value);
                        break;
                    case 'is null':
                        $query->whereNull($filterDelete->column);
                        break;
                    case 'is not null':
                        $query->whereNotNull($filterDelete->column);
                        break;
                endswitch;

                if ($childrens = $record->childrens) {
                    $data = $query->get()->pluck('id')->toArray();
                    foreach ($childrens as $children) {
                        $queryDelete = DB::connection($connection)->table($children->table_to);
                        $tableName = $children->table_to;
                        $type = $children->type;
                        if ($type == 'polymorphic') {
                            if ($tableName) {
                                $tableName = Str::singular($tableName);
                                $queryDelete->whereIn(sprintf('%sable_id', $tableName), $data);
                            }
                        }
                        $queryDelete->delete();
                    }
                }

                $query->delete();
            }
        }
    }

    public static function getDataValues($rows, $to_columns, $connectionTo, $tableName = null, $type = null, $restore = null)
    {
        $values = [];
        foreach ($rows as $row) {
            $data = [];
            foreach ($to_columns as $key => $column) {
                if (in_array($key, config('tenant.default_tenant_columns'))) {
                    $data[$key] =  static::getTenantId($row);
                } elseif (in_array($key, ['user_id'])) {
                    $data[$key] = DB::connection($connectionTo)->table(config('db-restore.tables.user', 'users'))
                        ->where(config('db-restore.oldId', 'old_id'), data_get($row, 'user_id'))->value('id');
                } else {
                    $data[$key] = static::getValues($connectionTo, $row, $column);
                }
            }

            //Verifica se existe a coluna id na tabela de destino
            //Se não existir, cria uma coluna id com o valor do ulid
            //Se existir, significa que a tabela de origem usa o id como chave primaria o ulid ou uuid
            if (!array_key_exists('id', $to_columns)) {
                $data['id'] = strtolower((string) Str::ulid());
                $data[config('db-restore.oldId', 'old_id')] = data_get($row, 'id');
            } else {
                //Se estiver vazio, cria um ulid para a coluna id
                if (empty($data['id'])) {
                    $data['id'] = strtolower((string) Str::ulid());
                }
            }

            $data['created_at'] = static::validateDate(data_get($row, 'created_at')) ? data_get($row, 'created_at') : now()->format('Y-m-d H:i:s');
            $data['updated_at'] = static::validateDate(data_get($row, 'updated_at')) ? data_get($row, 'updated_at') : now()->format('Y-m-d H:i:s');
            $data['deleted_at'] = static::validateDate(data_get($row, 'deleted_at')) ? data_get($row, 'deleted_at') : null;

            if ($type == 'polymorphic') {
                if ($tableName) {
                    $tableName = Str::singular($tableName);
                    $data[sprintf('%sable_type', $tableName)] = $restore->restoreModel->name;
                    if (in_array($key, config('tenant.default_tenant_columns'))) {
                        $data[sprintf('%sable_id', $tableName)] = data_get($data, 'tenant_id');
                    } else {
                        $data[sprintf('%sable_id', $tableName)] = static::getTenantId($row);
                    }
                }
            }
            $values[] = $data;
        }
        return $values;
    }

    public static function getTenantId($row)
    {
        return DB::connection(config('database.default'))->table('tenants')
            ->where(config('db-restore.oldId', 'old_id'), data_get($row, 'company_id'))->value('id');
    }
}
