<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\DbRestore\Helpers;

use Callcocam\DbRestore\Models\AbstractModelRestore;
use Callcocam\DbRestore\Models\Connection;
use Carbon\Carbon;
use DateTime;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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

    public static function queryFilters(&$query, $column, $operator, $value)
    {
        switch ($operator):
            case '=':
                $query->where($column, $operator, $value);
                break;
            case '!=':
                $query->where($column, $operator, $value);
                break;
            case '<':
                $query->where($column, $operator, $value);
                break;
            case '<=':
                $query->where($column, $operator, $value);
                break;
            case '>':
                $query->where($column, $operator, $value);
                break;
            case '>=':
                $query->where($column, $operator, $value);
                break;
            case 'like':
                $query->where($column, $operator, "%{$value}%");
                break;
            case 'not like':
                $query->where($column, $operator, "%{$value}%");
                break;
            case 'in':
                $query->whereIn($column, $value);
                break;
            case 'not in':
                $query->whereNotIn($column, $value);
                break;
            case 'between':
                $query->whereBetween($column, $value);
                break;
            case 'not between':
                $query->whereNotBetween($column, $value);
                break;
            case 'is null':
                $query->whereNull($column);
                break;
            case 'is not null':
                $query->whereNotNull($column);
                break;
        endswitch;
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

    public static function getFromDatabaseRows(AbstractModelRestore $restore, $from_table, $filters = null, $orderings = null, $tableTo = null)
    {
        $connection = $restore->connectionFrom;

        $from_connection = RestoreHelper::getConnectionCloneOptions($connection);

        $query = DB::connection($from_connection)
            ->table($from_table);

        $type = $restore->type;
        switch ($type) {
            case 'ignorar':
                $toConnection = RestoreHelper::getConnectionCloneOptions($restore->connectionTo);
                $tableName = $tableTo ?? $restore->table_to;
                $others = DB::connection($toConnection)
                    ->table($tableName)->get()->pluck('old_id', 'id')->toArray();
                $others = array_filter($others);
                if ($others) {
                    $query->whereNotIn('id', $others);
                }
                break;
            case 'excluir':
                $others = DB::connection($from_connection)
                    ->table($from_table)->get()->pluck('id', 'id')->toArray();
                $others = array_filter($others);
                if ($others) {
                    $toConnection = RestoreHelper::getConnectionCloneOptions($restore->connectionTo);
                    $tableName = $tableTo ?? $restore->table_to;
                    if ($childrens = $restore->childrens) {
                        foreach ($childrens as $children) {
                            $parents = DB::connection($toConnection)
                                ->table($tableName)->get()->pluck('id')->toArray();
                            DB::connection($toConnection)->table($children->table_to)
                                ->whereIn($children->join_from_column, $parents)
                                ->delete();
                        }
                    }
                    DB::connection($toConnection)->table($tableName)->whereIn('old_id', $others)
                        ->delete();
                }
                break;
        }

        if ($filters) {
            foreach ($filters as $filter) {
                static::queryFilters($query, $filter->column_to, $filter->operator, $filter->value);
            }
        }

        if ($orderings) {
            foreach ($orderings as $ordering) {
                $query->orderBy($ordering->column_to, $ordering->direction);
            }
        }

        $rows = $query->get();

        return $rows->toArray();
    }

    public static function getFromColumnsFileOptions($record)
    {
        return Cache::rememberForever("{$record->file}-header", function () use ($record) {
            $inputFileName = Storage::path($record->file);

            $testAgainstFormats = [
                \PhpOffice\PhpSpreadsheet\IOFactory::READER_XLS,
                \PhpOffice\PhpSpreadsheet\IOFactory::READER_XLSX,
                \PhpOffice\PhpSpreadsheet\IOFactory::READER_CSV,
            ];

            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($inputFileName, 0, $testAgainstFormats);
            $headers = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
            if (isset($headers[1])) {
                return $headers[1];
            }
            return [];
        });
    }

    public static function getValues($connectionName, $chunk, $column)
    {


        $column_from = data_get($column, 'column_from');
        $default_value = data_get($column, 'default_value');
        $type = data_get($column, 'type');
        $relation = data_get($column, 'relation');

        if (in_array($column_from, ['slug'])) {
            if (!data_get($chunk, $column_from)) {
                if ($default_value) {
                    return Str::of(data_get($chunk, $default_value))->slug()->append('-' . data_get($chunk, 'id'))->__toString();
                }
                return Str::of(data_get($chunk, 'name'))->slug()->append('-' . data_get($chunk, 'id'))->__toString();
            }
        }


        if ($relation) {
            $val = data_get($chunk, $column_from);
            //Conexao da tabela de destino
            // $connectionName = $connectionName;
            //Nome da tabela de destino que vamos recuperar o dado
            $tableName = $relation->table_from;
            //Coluna da tabela de destino que vamos recuperar o dado
            //Geraralmente é o id, mas tambem pode ser o campo slug ou o campo que foi salvo o id da tabela de origem
            $columnToName = $relation->column_from;
            //Valor da coluna da tabela de origem que vamos usar para recuperar o dado
            // $columnFromName = $relation->column_from;
            //Nome da coluna que vamos recuperar o dado
            $columnValue = $relation->column_value;
            //Se o valor da coluna da tabela de origem for igual ao valor da coluna da tabela de destino
            $data = DB::connection($connectionName)
                ->table($tableName)->where($columnToName, $val)->value($columnValue);
            return  $data;
        } else {

            return static::getValueType($type, $chunk, $column_from, $default_value);
        }

        return null;
    }

    public static function afterGetChildresValues($record)
    {

        //Pega os filhos do restore ex: users tem os filhos categories, posts, etc...
        $childrens = $record->childrens;
        //Se existir filhos
        if ($childrens) {
            //Vamos percorrer os filhos
            return $childrens->map(function ($children) use ($record) {
                //Vamos criar um batch para cada filho
                $batch = Bus::batch([])->then(function (Batch $batch) {
                })->name($children->name)->dispatch();
                //Todo filho tem que ter colunas
                $columns = $children->columns;
                //Todo filho tem uma tabela de origem
                $from_table = $children->table_from;
                //Vamos pegar as colunas da tabela de origem
                $from_columns = static::getColumsSchema($columns, $from_table, 'column_from');
                //Vamos pegar os filtros do filho que são do tipo list
                $filterList = $children->filters->filter(fn ($filter) => $filter->type == 'list')->all();
                //Vamos pegar os dados da tabela de origem
                $rows = static::getFromDatabaseRows($record, $from_table, $filterList, null, $children->table_to);
                //Vamos pegar a conexao da tabela de destino
                $connectionName = RestoreHelper::getConnectionCloneOptions($record->connectionTo);

                array_map(function ($row) use ($children,  $record,  $connectionName) {
                    //Vamos pegar a tabela de destino  
                    $query = DB::connection($connectionName)->table($record->table_to);
                    //Vamos pegar o valor do id da tabela de destino
                    $query->where($children->join_to_column, data_get($row, $children->join_from_column));
                    //Vamos pegar o id da tabela de destino
                    $parentId = $query->value('id');
                    //Vamos adicionar a coluna de tipo e id ex: category_id da tabela de posts ou post_id da tabela de comments
                    $parent =   $children->join_from_column;
                    //Vamos adicionar os valores da coluna de tipo e id 
                    $row->{$parent} = $parentId;
                    return $row;
                }, $rows);
                //Vamos excluir os dados baseados nos filtros do filho do tipo delete ou excluir
                static::beforeRemoveFilters($children);
                //Vamos pegar o nome da tabela de destino
                $to_table = $children->table_to;
                //Vamos pegar as colunas da tabela de destino
                $to_columns = static::getColumsSchema($columns, $to_table, 'column_to');
                //Vamos dividir os dados em pedaços de 1000
                $chunks = array_chunk($rows, 1000);
                //Vamos adicionar os jobs na fila
                foreach ($chunks as $chunk) {
                    $batch->add(new \Callcocam\DbRestore\Jobs\DbRestoreChidrenJob($children, $chunk, $to_columns, $from_columns, $record));
                }
            });
        }
    }

    public static function afterGetSharedValues($record)
    {

        $sharedItems = $record->shareds;

        if ($sharedItems) {

            return $sharedItems->map(function ($sharedItem) use ($record) {
                //Todo shared item tem um shared
                //O sahred tem um restore
                $shared = $sharedItem->shared;
                //Todo shared pode ter um filtro
                $filters = $shared->filters;
                $batch = Bus::batch([])->then(function (Batch $batch) {
                })->name($shared->name)->dispatch();
                //Todo shared tem colunas
                $columns = $shared->columns;
                //Todo shared tem uma tabela de origem
                $from_table = $shared->table_from;
                //Vamos pegar as colunas da tabela de origem
                $from_columns = static::getColumsSchema($columns, $from_table, 'column_from');
                //Vamos pegar os filtros do shared que são do tipo list e que tem o nome da tabela de destino
                //Ex: Se o shared for de endereços, vamos pegar os filtros que são do tipo list e que tem o nome da tabela de destino(ou tabela pai) que pode ser usuarios, empresas, etc...
                $filterList = $filters->filter(fn ($filter) => $filter->type == 'list' && $filter->name == data_get($record, 'table_to'))->all();
                //Vamos pegar os dados da tabela de origem
                $rows = static::getFromDatabaseRows($record, $from_table, $filterList, null, $shared->table_to);
                //Vamos pegar a conexao da tabela de destino
                $connectionName = RestoreHelper::getConnectionCloneOptions($record->connectionTo);
                //Como o shared é um relacionamento polimorfico, vamos adicionar a coluna de tipo e id
                array_map(function ($row) use ($sharedItem, $shared, $connectionName, $record) {
                    //Vamos pegar a tabela de destino
                    $query = DB::connection($connectionName)->table($record->table_to)->where($shared->column_to, data_get($row, $shared->column_from));
                    //Vamos pegar o id da tabela de destino
                    $parentId = $query->value('id');
                    //Vamos adicionar a coluna de tipo e id ex: addressable_id, addressable_type
                    $morph_column_type =   $sharedItem->morph_column_type;
                    $morph_column_id = $sharedItem->morph_column_id;
                    //Vamos adicionar os valores da coluna de tipo e id
                    $row->{$morph_column_type} = $sharedItem->restore_momdel_name;
                    $row->{$morph_column_id} = $parentId;
                    return $row;
                }, $rows);

                //Vamos remover os dados baseados nos filtros do shared do tipo delete ou excluir
                static::beforeRemoveFilters($shared);
                //Vamos pegar o nome da tabela de destino
                $to_table = $shared->table_to;
                //Vamos pegar as colunas da tabela de destino
                $to_columns = static::getColumsSchema($columns, $to_table, 'column_to');
                //Vamos dividir os dados em pedaços de 1000
                $chunks = array_chunk($rows, 1000);
                //Vamos adicionar os jobs na fila
                foreach ($chunks as $chunk) {
                    $batch->add(new \Callcocam\DbRestore\Jobs\DbRestoreSharedJob($shared, $chunk, $to_columns, $from_columns, $record));
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
                static::queryFilters($query, $filterDelete->column_to, $filterDelete->operator, $filterDelete->value);
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

    public static function getDataValues($rows, $to_columns, $connectionTo)
    {
        $values = [];
        foreach ($rows as $row) {
            $data = [];
            foreach ($to_columns as $key => $column) {

                if ($default_value = data_get($column, 'default_value')) {
                    $data[$key] =  $default_value;
                } else {
                    if (in_array($key, config('tenant.default_tenant_columns'))) {
                        $data[$key] =  static::getTenantId($row);
                    } elseif (in_array($key, ['user_id'])) {
                        $data[$key] = DB::connection($connectionTo)->table(config('db-restore.tables.user', 'users'))
                            ->where(config('db-restore.oldId', 'old_id'), data_get($row, 'user_id'))->value('id');
                    } else {
                        $value = static::getValues($connectionTo, $row, $column);
                        $data[$key] =  $value == 'NULL' ? null : $value;
                    }
                }
            }

            //Verifica se existe a coluna id na tabela de destino
            //Se não existir, cria uma coluna id com o valor do ulid
            //Se existir, significa que a tabela de origem usa o id como chave primaria o ulid ou uuid
            if (!array_key_exists('id', $to_columns)) {
                $data['id'] = strtolower((string) Str::ulid());
                if (data_get($row, 'id')) {
                    $data[config('db-restore.oldId', 'old_id')] = data_get($row, 'id');
                }
            } else {
                //Se estiver vazio, cria um ulid para a coluna id
                if (empty($data['id'])) {
                    $data['id'] = strtolower((string) Str::ulid());
                }
            }

            $data['created_at'] = static::validateDate(data_get($row, 'created_at')) ? data_get($row, 'created_at') : now()->format('Y-m-d H:i:s');
            $data['updated_at'] = static::validateDate(data_get($row, 'updated_at')) ? data_get($row, 'updated_at') : now()->format('Y-m-d H:i:s');
            $data['deleted_at'] = static::validateDate(data_get($row, 'deleted_at')) ? data_get($row, 'deleted_at') : null;

            $data = static::getDataStatusValues($row, $data);

            $data = static::getDataSlugValues($row, $data);

            // if ($children) {
            //     $data = static::getDataChildremValues($row, $children, $data);
            // }
            $values[] = $data;
        }
        return $values;
    }


    public static function getExportValues($connectionName, $chunk, $column)
    {
        $column_from = data_get($column, 'column_to');
        $default_value = data_get($column, 'default_value');
        $type = data_get($column, 'type');
        $relation = data_get($column, 'relation');
        if (!empty($default_value)) {
            $data = $default_value;
        } else {
            $val = data_get($chunk, $column_from);

            if ($relation) {
                //Conexao da tabela de destino
                // $connectionName = $connectionName;
                //Nome da tabela de destino que vamos recuperar o dado
                $tableName = $relation->table_from;
                //Coluna da tabela de destino que vamos recuperar o dado
                //Geraralmente é o id, mas tambem pode ser o campo slug ou o campo que foi salvo o id da tabela de origem
                $columnToName = $relation->column_to;
                //Valor da coluna da tabela de origem que vamos usar para recuperar o dado
                $columnFromName = $relation->column_from;
                //Nome da coluna que vamos recuperar o dado
                $columnValue = $relation->column_value;
                if ($connectionName instanceof Connection) {
                    $connectionName = RestoreHelper::getConnectionCloneOptions($connectionName);
                }
                //Se o valor da coluna da tabela de origem for igual ao valor da coluna da tabela de destino
                if (Cache::has(sprintf('_%s_%s', $column_from, $val))) {
                    $val = Cache::get(sprintf('_%s_%s', $column_from, $val));
                } else {
                    $val = DB::connection($connectionName)
                        ->table($tableName)->where($columnToName, $val)->value($columnValue);
                    Cache::forever(sprintf('_%s_%s', $column_from, $val), $val);
                }
            }

            switch ($type) {
                case 'date':
                    return  Carbon::parse($val);

                    break;
                case 'datetime':
                case 'timestamp':
                    return  Carbon::parse($val);

                    break;
                case 'time':
                    return  Carbon::parse($val);

                    break;
                case 'year':
                    return  Carbon::parse($val);

                    break;
                case 'binary':
                case 'boolean':
                case 'char':
                    return  $val;
                    break;
                case 'text':
                    return  $val;

                    break;
                case 'json':
                    $data = json_decode($val, true);
                    return implode(',', $data);

                    break;
                case 'integer':
                    return $val;

                    break;
                default:
                    return $val;

                    break;
            }
        }

        return $data;
    }


    public static function getDataExportValues($rows, $to_columns, $connectionTo)
    {
        $values = [];
        foreach ($rows as $row) {
            $data = [];
            foreach ($to_columns as $key => $column) {
                $data[$key] = static::getExportValues($connectionTo, $row, $column);
            }
            $values[] = $data;
        }
        return $values;
    }

    protected static function getDataChildremValues($row, $children, $data)
    {
        $childrenDatas = [];
        $columns = $children->columns;
        foreach ($columns as $column) {
            $childrenData['id'] = strtolower((string) Str::ulid());
            $childrenData['field_id'] =   $column->id;
            $childrenData['description'] = data_get($row, $column->column_from);
            $childrenData['created_at'] = now();
            $childrenData['updated_at'] = now();
            $childrenData[$children->join_from_column] = data_get($data, $children->join_to_column);
            $childrenDatas[] = $childrenData;
        }
        $data['childrens'] = $childrenDatas;

        return $data;
    }

    protected static function getDataPolymorphicValues($row, $type, $tableName, $restoreModel, $data)
    {
        if ($type == 'polymorphic' && $restoreModel) {
            if ($tableName) {
                $tableName = Str::singular($tableName);
                $data[sprintf('%sable_type', $tableName)] = $restoreModel->name;
                $data[sprintf('%sable_id', $tableName)] = static::getTenantId($row);
            }
        }
        return $data;
    }

    protected static function getDataStatusValues($row, $data)
    {
        if (isset($data['status'])) {
            $status = data_get($row, 'status');
            if (!in_array($data['status'], ['published', 'draft'])) {
                $data['status'] = (int)$status ? 'published' : 'draft';
            }
        }
        return $data;
    }

    protected static function getDataSlugValues($row, $data)
    { 
        if (isset($data['slug'])) {
            $slug = data_get($data, 'slug');
            if (!$slug) {
                $data['slug'] = Str::of(data_get($data, 'name'))->__toString();
            } else {
                $data['slug'] = Str::of($slug)->slug()->__toString();
            }
        }
        return $data;
    }

    protected static function getTenantId($row)
    {
        return DB::connection(config('database.default'))->table('tenants')
            ->where(config('db-restore.oldId', 'old_id'), data_get($row, 'company_id'))->value('id');
    }



    protected static function getValueType($type, $chunk, $column_from, $default_value)
    {
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
                    return  (int) data_get($chunk, $column_from, $default_value);
                }
                break;
            case 'array':
                if (is_array($column_from)) {
                    $data = [];
                    foreach ($column_from as $key => $value) {
                        $data[$key] = data_get($chunk, $value, $default_value);
                    }
                    return  json_encode($data);
                }
                return  [];

                break;
            default:
                return  data_get($chunk, $column_from, $default_value);

                break;
        }
    }
}
