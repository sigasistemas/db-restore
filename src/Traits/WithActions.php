<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\DbRestore\Traits;

use Callcocam\DbRestore\Forms\Components\ConnectionToField;
use Callcocam\DbRestore\Forms\Components\SelectColumnField;
use Callcocam\DbRestore\Forms\Components\SelectColumnToField;
use Callcocam\DbRestore\Forms\Components\SelectTableField;
use Callcocam\DbRestore\Forms\Components\SelectTableFromField;
use Callcocam\DbRestore\Forms\Components\TextInputField;
use Callcocam\DbRestore\Helpers\FileHelper;
use Callcocam\DbRestore\Helpers\RestoreHelper;
use Callcocam\DbRestore\Models\Connection;
use Callcocam\DbRestore\Models\Import; 
use Filament\Actions\Action;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

trait WithActions
{
    public function getActionRestoreColumns()
    {
        return Action::make('Restore')
            ->label('Importar')
            ->visible(fn (Import $record) => $record->columns->count() > 0)
            ->icon('fas-upload')
            ->color('success')
            ->action(function (Import $record) {
                if (class_exists('App\Core\Helpers\TenantHelper')) {
                    if (method_exists(app('App\Core\Helpers\TenantHelper'), 'import')) {
                        return app('App\Core\Helpers\TenantHelper')->import($record);
                    }
                } else {
                    //Pega as colunas do modelo Import
                    $columns = $record->columns;
                    //Pega os filhos do modelo Import
                    $childrens = $record->childrens;
                    //pegar os filters
                    $filters = $record->filters;
                    //Verifica se o campo file está preenchido
                    if (!$record->file) {
                        return;
                    }
                    $to_columns = [];
                    //Pega a tabela de destino
                    $from_table = $record->table_to;

                    //Verifica se existe o arquivo
                    if (Storage::disk(config('db-restore.disk'))->exists($record->file)) {
                        //Pega as colunas da tabela de destino
                        $to_columns = RestoreHelper::getColumsSchema($columns, $from_table, 'column_to');
                        //Pega os dados do arquivo e armazena em cache para não ter que ficar lendo o arquivo toda vez
                        $sheetData = Cache::rememberForever("{$record->file}-column", function () use ($record) {
                            $inputFileName = Storage::disk(config('db-restore.disk'))->path($record->file);
                            $testAgainstFormats = [
                                \PhpOffice\PhpSpreadsheet\IOFactory::READER_XLS,
                                \PhpOffice\PhpSpreadsheet\IOFactory::READER_XLSX,
                                \PhpOffice\PhpSpreadsheet\IOFactory::READER_CSV,
                            ];
                            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($inputFileName, 0, $testAgainstFormats);

                            return  $spreadsheet->getActiveSheet()->toArray(true, true, true, true);
                        });
                        unset($sheetData[1]);

                        $chunks = array_chunk($sheetData, 1000);

                        $children = null;
                        if ($childrens) {
                            $children = $childrens->first();
                        }
                        if ($record->type == 'excluir') {
                            $connectionTo = RestoreHelper::getConnectionCloneOptions($record->connectionTo);
                            //Se o tipo for excluir, vamos excluir os dados da tabela de destino
                            $query = DB::connection($connectionTo)->table($from_table);
                            //Vamos verificar se temos filtros
                            if ($filters->count()) {
                                //Vamos pegar os filtros do tipo delete
                                $filterDeletes = $filters->filter(fn ($filter) => $filter->type == 'delete')->all();
                                //Vamos percorrer os filtros
                                foreach ($filterDeletes as $filter) {
                                    //Vamos aplicar os filtros
                                    RestoreHelper::queryFilters($query, $filter->column_to, $filter->operator, $filter->value);
                                }
                            }
                            //Vamos verificar se temos filhos
                            if ($table_from = data_get($children, 'table_from')) {
                                //Vamos pegar os ids dos pais
                                $parents = $query->pluck('id')->toArray();
                                //Vamos excluir os dados da tabela filha
                                $query = DB::connection($connectionTo)->table($table_from)->whereIn($children->join_from_column, $parents)->delete();
                            }
                            //Vamos excluir os dados da tabela de destino
                            $query->delete();
                        }

                        //Vamos verificar se temos o campo tenant_id
                        if ($column = data_get($to_columns, 'tenant_id')) {
                            //Vamos verificar se temos um valor padrão
                            //Se não tiver vamos usar o tenant_id selecionado no formulário
                            if (!data_get($column, 'default_value')) {
                                data_set($column, 'default_value', $record->tenant_id);
                            }
                            //Vamos atualizar o tenant_id da lista de colunas to_columns
                            data_set($to_columns, 'tenant_id', $column);
                        }
                        //Vamos verificar se vamos usar uma tabela de filhos
                        //A coluna table_from é a tabela filha do modelo principal
                        //A ideia é que você possa importar dados para uma tabela filha
                        //Exemplo: A tabela principal vai conter alguns compos que serão preenchidos com os dados do arquivo
                        //Exemplo: A coluna A1 do arquivo vai ser preenchida na coluna nome da tabela principal,
                        //a coluna B1 do arquivo vai ser preenchida na coluna email da tabela principal 
                        if (data_get($children, 'table_from')) {
                            //Eschamos a tabela filha, vamos carregar os dados da tabela filha
                            dd($children->table_from);
                        } else {
                            //Não temos uma tabela filha, vamos carregar os dados em uma coluna da tabela principal,
                            //Que é a coluna join_to_column do modelo filho(children)
                            //Pega as colunas da tabela de destino
                            if ($children && $childremColumns = $children->columns) {
                                $to_columns[$children->join_to_column]['column_from'] = $childremColumns->pluck('column_from', 'column_to')->toArray();
                            }
                        }
                        //TESTE
                        // $connectionTo = RestoreHelper::getConnectionCloneOptions($record->connectionTo);

                        // foreach ($chunks as $chunk) {
                        //     $values = RestoreHelper::getDataValues($chunk, $to_columns, $connectionTo);
                        //     dd($values);
                        // }
                        //FIN TESTE
                        //Inicia o batch
                        $batch =  Bus::batch([])->then(function (Batch $batch) use ($record) {
                        })->name($record->name)->dispatch();
                        //Seta a tabela de destino
                        $record->table_to = $from_table;
                        //Percorre os chunks
                        foreach ($chunks as $chunk) {
                            $batch->add(new \Callcocam\DbRestore\Jobs\DbRestoreFileJob($record, $chunk, $to_columns, $children));
                        }
                    }
                }
            });
    }
    public function getActionGeraColumns()
    {
        return Action::make('gerar-columns')
            ->icon('fas-plus')
            ->color('info')
            ->label('Gerar colunas')
            ->visible(fn (Import $record) => $record->table_to && $record->file)
            ->form(function (Import $record) {

                return [

                    Group::make([
                        SelectColumnField::make('type')
                            ->required()
                            ->options([
                                'string' => 'String',
                                'integer' => 'Integer',
                                'float' => 'Float',
                                'boolean' => 'Boolean',
                                'date' => 'Date',
                                'datetime' => 'Datetime',
                                'time' => 'Time',
                                'timestamp' => 'Timestamp',
                                'json' => 'Json',
                                'jsonb' => 'Jsonb',
                                'uuid' => 'Uuid',
                                'binary' => 'Binary',
                                'enum' => 'Enum',
                                'array' => 'Array',
                                'password' => 'Password',
                            ])
                            ->default('string'),
                        SelectColumnToField::makeColumn('column_to', $record),

                    ])->columns(2)
                ];
            })
            ->action(function (Import $record, array $data) {
                if (class_exists('App\Core\Helpers\TenantHelper')) {
                    if (method_exists(app('App\Core\Helpers\TenantHelper'), 'getGeraColumns')) {
                        return app('App\Core\Helpers\TenantHelper')->getGeraColumns($record);
                    }
                }
                $headers = FileHelper::make($record)
                    ->load()
                    ->getHeaders();

                if ($headers) {
                    $record->columns()->forceDelete();
                    foreach ($headers as $key => $header) {
                        if (!$record->columns()->where('column_from', $key)->exists()) {
                            $record->columns()->create([
                                'tenant_id' => $record->tenant_id,
                                'column_from' => $key,
                                'column_to' => data_get($data, 'column_to', $header),
                                'type' => data_get($data, 'type'),
                            ]);
                        }
                    }
                    Notification::make()
                        ->title('Colunas geradas com sucesso!')
                        ->success()
                        ->send();
                }
            });
    }
    public function getActionGeraColumnsChildren()
    {
        return  Action::make('gerar-columns-childrens')
            ->icon('fas-plus')
            ->color('info')
            ->label('Gerar Filhos')
            ->visible(fn (Import $record) => $record->columns->count() > 0)
            ->form(function (Import $record) {

                return [
                    Group::make([
                        TextInputField::make('name')
                            ->columnSpan([
                                'md' => 5
                            ])->required(),
                        ConnectionToField::make('connection_id')
                            ->columnSpan([
                                'md' => 7
                            ])
                            ->options(Connection::query()->pluck('name', 'id')->toArray())
                            ->default($record->connection_id)
                            ->reactive()
                            ->required(),
                        Section::make('Dados da tebela secundaria')
                            ->description('Obrigatório para gerar os filhos em uma tabela secundaria. ex: tabela de produto_items')
                            ->columnSpanFull()
                            ->columns(12)
                            ->schema([
                                SelectTableFromField::makeTable('table_from', $record, 'table_from_import')
                                    ->columnSpan([
                                        'md' => 4
                                    ]),
                                SelectTableField::make('join_from_column')
                                    ->options(function (Get $get) use ($record) {
                                        $table = $get('table_from');
                                        if ($connectionTo =  $record->connectionTo) {
                                            return $this->getColumns($connectionTo, $table, 'to');
                                        }
                                        return [];
                                    })
                                    ->columnSpan([
                                        'md' => 4
                                    ])->required(function (Get $get) {
                                        return $get('table_from');
                                    }),
                                SelectTableField::make('table_to_fields')
                                    ->options(function (Get $get)  use ($record) {
                                        $connection =  $get('connection_id');
                                        if ($connection) {

                                            return $this->getTablesOptions($connection, 'to');
                                        }
                                        return $this->getTablesOptions($record->connectionTo, 'to');
                                    })
                                    ->columnSpan([
                                        'md' => 4
                                    ])
                                    ->required(function (Get $get) {
                                        return $get('table_from');
                                    }),
                            ]),
                        SelectColumnToField::makeColumn('join_to_column', $record)
                            ->helperText('Coluna da tabela principal que vai ser usada para fazer o join')
                            ->required(function (Get $get) {
                                return !$get('table_from');
                            })
                            ->columnSpan([
                                'md' => 9
                            ]),

                        SelectColumnField::make('type')

                            ->columnSpan([
                                'md' => 3
                            ])
                            ->options([
                                'string' => 'String',
                                'integer' => 'Integer',
                                'float' => 'Float',
                                'boolean' => 'Boolean',
                                'date' => 'Date',
                                'datetime' => 'Datetime',
                                'time' => 'Time',
                                'timestamp' => 'Timestamp',
                                'json' => 'Json',
                                'jsonb' => 'Jsonb',
                                'uuid' => 'Uuid',
                                'binary' => 'Binary',
                                'enum' => 'Enum',
                                'array' => 'Array',
                                'password' => 'Password',
                            ])
                            ->default('string'),

                    ])->columns(12)
                ];
            })
            ->action(function (Import $record, array $data) {
                if (class_exists('App\Core\Helpers\TenantHelper')) {
                    if (method_exists(app('App\Core\Helpers\TenantHelper'), 'generateChildrens')) {
                        return app('App\Core\Helpers\TenantHelper')->generateChildrens($record, $data);
                    }
                }
                $connection = RestoreHelper::getConnectionCloneOptions(Connection::find($data['connection_id']));
                $fields  = DB::connection($connection)->table($data['table_to_fields'])->get()->pluck('name', 'id')->toArray();

                $headers = FileHelper::make($record)
                    ->load()
                    ->getHeaders();
                $reverts = array_flip($headers);
                $values = [];
                foreach ($fields as $key => $header) {
                    if (isset($reverts[$header])) {
                        $values[$reverts[$header]] = $key;
                    }
                }

                if ($childrens = $record->childrens) {
                    foreach ($childrens as $children) {
                        $children->columns()->forceDelete();
                        $children->forceDelete();
                    }
                }
                $childrem =  $record->childrens()->create([
                    'tenant_id' => $record->tenant_id,
                    'name' => data_get($data, 'name'),
                    'table_from' => data_get($data, 'table_from'),
                    'join_from_column' => data_get($data, 'join_from_column'),
                    'table_to' => data_get($data, 'table_to_fields'),
                    'join_to_column' => data_get($data, 'join_to_column'),
                    'relation_type' => 'one-to-many',
                ]);

                if ($headers) {
                    foreach ($values as $key => $header) {
                        $childrem->columns()->create([
                            'tenant_id' => $record->tenant_id,
                            'column_from' => $key,
                            'column_to' => $header,
                            'type' => data_get($data, 'type'),
                        ]);
                    }
                    Notification::make()
                        ->title('Colunas geradas com sucesso!')
                        ->success()
                        ->send();
                }
            });
    }
}
